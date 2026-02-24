<?php
declare(strict_types=1);

function requirePost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée');
    }
}

function requireAllowedHost(): void {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!in_array($host, ALLOWED_HOSTS, true)) {
        http_response_code(403);
        exit;
    }
}

function redirect303(string $location): never {
    header('Location: ' . $location, true, 303);
    exit;
}

/**
 * Sanitise une adresse IP pour usage sûr dans un nom de fichier.
 * Accepte uniquement IPv4 et IPv6 valides. Retourne null si invalide.
 * Protège contre le path traversal via X-Forwarded-For forgé.
 */
function sanitizeIp(string $ip): ?string {
    // filter_var rejette tout ce qui n'est pas une IP stricte
    // (pas de ../, pas de caractères spéciaux, pas de notation CIDR)
    $filtered = filter_var($ip, FILTER_VALIDATE_IP);
    if ($filtered === false) {
        return null;
    }
    return $filtered;
}

/**
 * Rate limiting durci.
 * - Cooldown de $cooldown secondes entre deux soumissions (défaut 120s)
 * - Maximum $maxPerHour soumissions par IP par heure (défaut 5)
 * - Garbage collection probabiliste (1 chance sur 10) des fichiers expirés
 * - IP sanitisée pour éviter path traversal
 *
 * Retourne un tableau ['blocked' => bool, 'reason' => string|null]
 * au lieu de faire exit(), pour permettre un logging enrichi par le caller.
 */
function rateLimit(int $cooldown = 120, int $maxPerHour = 5): array {
    $rawIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = sanitizeIp($rawIp);

    // IP invalide/forgée → bloquer
    if ($ip === null) {
        return ['blocked' => true, 'reason' => 'invalid_ip'];
    }

    $dir  = sys_get_temp_dir() . '/contact_ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    $filePrefix = $dir . '/rl_' . md5($ip);
    $cooldownFile = $filePrefix . '_last';
    $counterFile  = $filePrefix . '_count';

    // Garbage collection probabiliste (1/10)
    if (random_int(1, 10) === 1) {
        rateLimitGarbageCollect($dir, 3600);
    }

    // 1. Cooldown entre soumissions
    if (file_exists($cooldownFile)) {
        $lastTime = (int) file_get_contents($cooldownFile);
        if ((time() - $lastTime) < $cooldown) {
            return ['blocked' => true, 'reason' => 'cooldown'];
        }
    }

    // 2. Compteur horaire
    $count = 0;
    $windowStart = 0;
    if (file_exists($counterFile)) {
        $data = json_decode((string) file_get_contents($counterFile), true);
        if (is_array($data)) {
            $windowStart = (int) ($data['window_start'] ?? 0);
            $count       = (int) ($data['count'] ?? 0);
        }
    }

    $now = time();

    // Fenêtre expirée → reset
    if (($now - $windowStart) >= 3600) {
        $windowStart = $now;
        $count = 0;
    }

    if ($count >= $maxPerHour) {
        return ['blocked' => true, 'reason' => 'hourly_limit'];
    }

    // Enregistrer cette soumission
    $count++;
    file_put_contents($cooldownFile, (string) $now);
    file_put_contents($counterFile, json_encode([
        'window_start' => $windowStart,
        'count'        => $count,
    ]));

    return ['blocked' => false, 'reason' => null];
}

/**
 * Supprime les fichiers de rate limiting plus vieux que $maxAge secondes.
 */
function rateLimitGarbageCollect(string $dir, int $maxAge): void {
    $files = @scandir($dir);
    if ($files === false) {
        return;
    }

    $now = time();
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $dir . '/' . $file;
        if (is_file($path) && ($now - filemtime($path)) > $maxAge) {
            @unlink($path);
        }
    }
}
