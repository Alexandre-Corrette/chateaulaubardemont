<?php
declare(strict_types=1);

$base = __DIR__; // static/php

require_once $base . '/config.php';
require_once $base . '/helpers/logger.php';
require_once $base . '/helpers/http.php';
require_once $base . '/helpers/sanitize.php';
require_once $base . '/helpers/validate.php';
require_once $base . '/helpers/template.php';
require_once $base . '/helpers/mailer.php';

requirePost();
requireAllowedHost();
rateLimit(30); // 1 envoi max toutes les 30 secondes / IP


// Honeypot
if (isHoneypotTriggered($_POST)) {
    redirect303(REDIRECT_OK);
}

// Champs requis
$required = ['last_name', 'email', 'message', 'date'];
if (!requireFields($_POST, $required)) {
    redirect303(REDIRECT_ERR);
}

// Sanitize
$email = cleanEmail((string)$_POST['email']);
if (!isValidEmail($email)) {
    redirect303('/sites-preprod-1/contact/?error=email');
}

$data = [
    'first_name' => cleanText($_POST['first_name'] ?? ''),
    'last_name'  => cleanText((string)$_POST['last_name']),
    'email'      => $email,
    'phone'      => cleanText($_POST['phone'] ?? ''),
    'reason'     => cleanText($_POST['reason'] ?? 'Non précisé'),
    'date'       => normalizeDate(cleanText((string)$_POST['date'])),
    'message'    => cleanMessage((string)$_POST['message']),
];

// Mail
$subject = "Contact – {$data['first_name']} {$data['last_name']}";
$body    = buildContactBody($data);
$headers = buildHeaders(MAIL_FROM, $data['email']);

writeDebug(['subject' => $subject, 'headers' => $headers]);

$sent = sendContactMail(CONTACT_TO, $subject, $body, $headers);
writeDebug("Résultat envoi mail : " . ($sent ? "SUCCÈS" : "ÉCHEC"));

redirect303($sent ? REDIRECT_OK : REDIRECT_ERR);
