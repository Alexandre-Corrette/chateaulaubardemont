<?php
declare(strict_types=1);

$base = __DIR__;
require_once $base . '/config.php';
require_once $base . '/helpers/csrf.php';

// Vérifier Origin/Referer avant de délivrer un token
if (!isAllowedOrigin(ALLOWED_HOSTS)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

// Démarrer/reprendre la session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Sécuriser le cookie de session
$params = session_get_cookie_params();
setcookie(session_name(), session_id(), [
    'expires'  => 0,
    'path'     => $params['path'],
    'domain'   => $params['domain'],
    'secure'   => true,
    'httponly'  => true,
    'samesite' => 'Strict',
]);

$token = generateCsrfToken();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
echo json_encode(['token' => $token]);
