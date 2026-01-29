<?php
declare(strict_types=1);

function requirePost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée');
    }
}

function requireAllowedHost(): void {
    if (!isset($_SERVER['HTTP_HOST'])) {
        http_response_code(403);
        exit;
    }

    if (!in_array($_SERVER['HTTP_HOST'], ALLOWED_HOSTS, true)) {
        http_response_code(403);
        exit;
    }
}

function redirect303(string $location): never {
    header('Location: ' . $location, true, 303);
    exit;
}

function rateLimit(int $seconds = 30): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/contact_' . md5($ip);

    if (file_exists($file) && (time() - filemtime($file)) < $seconds) {
        http_response_code(429);
        exit;
    }

    touch($file);
}
