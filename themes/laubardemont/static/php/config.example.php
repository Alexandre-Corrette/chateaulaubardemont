<?php
declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────
// Template de configuration — copier en `config.php` puis remplir.
// Le vrai `config.php` est gitignoré : ne le commite jamais.
// ─────────────────────────────────────────────────────────────────

const CONTACT_TO   = 'contact@chateau-laubardemont.com';
const MAIL_FROM    = 'formulaire-contact@chateau-laubardemont.com';
const REDIRECT_OK  = '/contact/?success=1';
const REDIRECT_ERR = '/contact/?error=1';
const DEBUG_LOG    = false;  // Mettre true pour débugger

// Transport mail — vide = mail() natif PHP, sinon DSN SMTP
// Dev (MailCatcher) : 'smtp://localhost:1025'
// Prod              : '' (utilise sendmail/mail())
const MAILER_DSN = '';

const ALLOWED_HOSTS = [
    'chateau-laubardemont.com',
    'www.chateau-laubardemont.com',
    'preprod.chateau-laubardemont.com',
];

// BelEvent API — laisser vide = appel silencieusement ignoré (safe avant déploiement API)
const BELEVENT_API_URL  = '';  // ex: https://api.belevent.io
const BELEVENT_API_KEY  = '';  // ex: bel_live_xxxxxxxxxxxxx
const BELEVENT_VENUE_SLUG = 'laubardemont';

// ── Configuration Zapier (fallback BelEvent tant que l'API n'est pas branchée) ──
define('ZAPIER_ENABLED', false); // Kill switch
define('ZAPIER_WEBHOOK_URL', 'https://hooks.zapier.com/hooks/catch/XXXXXX/YYYYYY/');
define('ZAPIER_WEBHOOK_SECRET', 'TOKEN_64_CHARS_HEX');
