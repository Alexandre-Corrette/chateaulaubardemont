<?php
declare(strict_types=1);

// Définir les constantes nécessaires avant de charger les helpers
if (!defined('MAIL_FROM')) {
    define('MAIL_FROM', 'test@example.com');
}
if (!defined('CONTACT_TO')) {
    define('CONTACT_TO', 'dest@example.com');
}
if (!defined('MAILER_DSN')) {
    define('MAILER_DSN', '');
}
if (!defined('DEBUG_LOG')) {
    define('DEBUG_LOG', false);
}
if (!defined('REDIRECT_OK')) {
    define('REDIRECT_OK', '/contact/?success=1');
}
if (!defined('REDIRECT_ERR')) {
    define('REDIRECT_ERR', '/contact/?error=1');
}
if (!defined('ALLOWED_HOSTS')) {
    define('ALLOWED_HOSTS', ['localhost']);
}
if (!defined('BELEVENT_API_URL')) {
    define('BELEVENT_API_URL', '');
}
if (!defined('BELEVENT_API_KEY')) {
    define('BELEVENT_API_KEY', '');
}
if (!defined('BELEVENT_VENUE_SLUG')) {
    define('BELEVENT_VENUE_SLUG', 'test');
}

require_once __DIR__ . '/../vendor/autoload.php';