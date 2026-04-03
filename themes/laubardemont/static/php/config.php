<?php
declare(strict_types=1);

// All config via environment variables — set in .env or server config
// Fallbacks provided for backwards compatibility

$contactTo  = getenv('CONTACT_TO')  ?: '';
$mailFrom   = getenv('MAIL_FROM')   ?: '';
$redirectOk = getenv('REDIRECT_OK') ?: '/contact/?success=1';
$redirectErr = getenv('REDIRECT_ERR') ?: '/contact/?error=1';

if (!defined('CONTACT_TO'))   define('CONTACT_TO',   $contactTo);
if (!defined('MAIL_FROM'))    define('MAIL_FROM',     $mailFrom);
if (!defined('REDIRECT_OK'))  define('REDIRECT_OK',   $redirectOk);
if (!defined('REDIRECT_ERR')) define('REDIRECT_ERR',  $redirectErr);
if (!defined('DEBUG_LOG'))    define('DEBUG_LOG',     (bool)(getenv('DEBUG_LOG') ?: false));

// Transport mail — empty = native PHP mail(), otherwise SMTP DSN
// Dev (MailCatcher) : 'smtp://localhost:1025'
// Prod              : '' (uses sendmail/mail())
if (!defined('MAILER_DSN'))   define('MAILER_DSN',    getenv('MAILER_DSN') ?: '');

// Allowed hosts — comma-separated in env, or empty to skip check
$hostsEnv = getenv('ALLOWED_HOSTS') ?: '';
if (!defined('ALLOWED_HOSTS')) {
    define('ALLOWED_HOSTS', $hostsEnv ? explode(',', $hostsEnv) : []);
}

// BelEvent API — leave empty to silently skip
if (!defined('BELEVENT_API_URL'))    define('BELEVENT_API_URL',    getenv('BELEVENT_API_URL')    ?: '');
if (!defined('BELEVENT_API_KEY'))    define('BELEVENT_API_KEY',    getenv('BELEVENT_API_KEY')    ?: '');
if (!defined('BELEVENT_VENUE_SLUG')) define('BELEVENT_VENUE_SLUG', getenv('BELEVENT_VENUE_SLUG') ?: '');
