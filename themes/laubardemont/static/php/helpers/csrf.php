<?php
declare(strict_types=1);

const CSRF_TOKEN_LIFETIME = 1800; // 30 minutes

/**
 * Génère un token CSRF, le stocke en session avec son timestamp.
 * Retourne le token brut.
 */
function generateCsrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token']      = $token;
    $_SESSION['csrf_token_time'] = time();

    return $token;
}

/**
 * Vérifie le token CSRF soumis.
 * - Présence en session
 * - Correspondance exacte (timing-safe)
 * - Non expiré (30 min)
 *
 * Consomme le token après vérification (usage unique).
 */
function verifyCsrfToken(string $submittedToken): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $storedToken = $_SESSION['csrf_token'] ?? '';
    $storedTime  = $_SESSION['csrf_token_time'] ?? 0;

    // Consommer immédiatement (usage unique)
    unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);

    if ($storedToken === '' || $submittedToken === '') {
        return false;
    }

    // Comparaison timing-safe
    if (!hash_equals($storedToken, $submittedToken)) {
        return false;
    }

    // Vérifier expiration
    if ((time() - $storedTime) > CSRF_TOKEN_LIFETIME) {
        return false;
    }

    return true;
}

/**
 * Vérifie que l'Origin ou le Referer de la requête correspond
 * à un host autorisé. Retourne true si au moins un des deux headers
 * est présent et valide.
 *
 * Rejet si aucun des deux headers n'est présent.
 */
function isAllowedOrigin(array $allowedHosts): bool {
    $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    // Au moins un header doit être présent
    if ($origin === '' && $referer === '') {
        return false;
    }

    // Vérifier Origin
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        if ($originHost !== null && in_array($originHost, $allowedHosts, true)) {
            return true;
        }
    }

    // Vérifier Referer
    if ($referer !== '') {
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if ($refererHost !== null && in_array($refererHost, $allowedHosts, true)) {
            return true;
        }
    }

    return false;
}
