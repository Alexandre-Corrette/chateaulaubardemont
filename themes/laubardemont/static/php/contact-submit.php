<?php
declare(strict_types=1);

$base = __DIR__;
require_once $base . '/config.php';
require_once $base . '/helpers/logger.php';
require_once $base . '/helpers/http.php';
require_once $base . '/helpers/sanitize.php';
require_once $base . '/helpers/validate.php';
require_once $base . '/helpers/template.php';
require_once $base . '/helpers/mailer.php';
require_once $base . '/helpers/belevent.php';

// Vérifications
requirePost();
requireAllowedHost();
rateLimit(30);

// Honeypot
if (isHoneypotTriggered($_POST)) {
    writeDebug('Honeypot triggered');
    redirect303(REDIRECT_OK);  // On fait croire que ça a marché
}

// Champs requis
$required = ['last_name', 'email', 'message', 'date'];
if (!requireFields($_POST, $required)) {
    writeDebug('Champs manquants');
    redirect303(REDIRECT_ERR);
}

// Validation email
$email = cleanEmail((string)$_POST['email']);
if (!isValidEmail($email)) {
    writeDebug('Email invalide: ' . $email);
    redirect303(REDIRECT_ERR . '&reason=email');
}

// Sanitize
$data = [
    'first_name' => cleanText($_POST['first_name'] ?? ''),
    'last_name'  => cleanText((string)$_POST['last_name']),
    'email'      => $email,
    'phone'      => cleanText($_POST['phone'] ?? ''),
    'reason'     => cleanText($_POST['reason'] ?? 'Non précisé'),
    'date'       => normalizeDate(cleanText((string)$_POST['date'])),
    'message'    => cleanMessage((string)$_POST['message']),
];

// Envoi mail (prioritaire)
$subject = "Contact – {$data['first_name']} {$data['last_name']}";
$body    = buildContactBody($data);
$headers = buildHeaders(MAIL_FROM, $data['email']);

$sent = sendContactMail(CONTACT_TO, $subject, $body, $headers);

writeDebug($sent ? 'Mail envoyé' : 'Échec envoi mail');

if (!$sent) {
    redirect303(REDIRECT_ERR);
}

// Envoi API BelEvent (fire-and-forget)
// L'email est déjà parti. Si l'API échoue, le visiteur ne voit rien.
$beleventResult = sendToBelEvent([
    'first_name' => $data['first_name'],
    'last_name'  => $data['last_name'],
    'email'      => $data['email'],
    'phone'      => $data['phone'],
    'date'       => $data['date'],
    'reason'     => $data['reason'],
    'message'    => strip_tags((string)$_POST['message']),
]);

if (DEBUG_LOG) {
    writeDebug(sprintf(
        '[BelEvent] Visit request %s — requestId: %s',
        $beleventResult['success'] ? 'OK' : 'FAILED: ' . $beleventResult['error'],
        $beleventResult['requestId'] ?? 'n/a'
    ));
}

// Succès (quoi qu'il arrive avec l'API)
redirect303(REDIRECT_OK);
