<?php
declare(strict_types=1);

$base = __DIR__;
require_once $base . '/config.php';
require_once $base . '/helpers/logger.php';
require_once $base . '/helpers/http.php';
require_once $base . '/helpers/sanitize.php';
require_once $base . '/helpers/validate.php';
require_once $base . '/helpers/csrf.php';
require_once $base . '/helpers/template.php';
require_once $base . '/helpers/mailer.php';
require_once $base . '/helpers/belevent.php';

// ── 1. Vérifications HTTP ──────────────────────────────────────
requirePost();
requireAllowedHost();

// ── 2. Rate limiting (retourne un tableau, ne fait plus exit) ──
$rl = rateLimit(120, 5);
if ($rl['blocked']) {
    logBlockedAttempt('rate_limit', ['sub_reason' => $rl['reason']]);
    redirect303(REDIRECT_OK); // Silencieux pour ne pas informer l'attaquant
}

// ── 3. Honeypot ────────────────────────────────────────────────
if (isHoneypotTriggered($_POST)) {
    logBlockedAttempt('honeypot');
    redirect303(REDIRECT_OK);
}

// ── 4. CSRF — vérification Origin/Referer + token session ──────
if (!isAllowedOrigin(ALLOWED_HOSTS)) {
    logBlockedAttempt('csrf_origin');
    redirect303(REDIRECT_ERR);
}

$csrfToken = (string) ($_POST['_csrf_token'] ?? '');
if (!verifyCsrfToken($csrfToken)) {
    logBlockedAttempt('csrf_token');
    redirect303(REDIRECT_ERR);
}

// ── 5. Challenge timing anti-bot ───────────────────────────────
if (isTimingChallengeFailed($_POST)) {
    logBlockedAttempt('timing', [
        '_timing' => $_POST['_timing'] ?? 'absent',
    ]);
    redirect303(REDIRECT_OK);
}

// ── 6. Champs requis ───────────────────────────────────────────
$required = ['last_name', 'email', 'message', 'date'];
if (!requireFields($_POST, $required)) {
    logBlockedAttempt('missing_fields');
    redirect303(REDIRECT_ERR);
}

// ── 7. Validation email ────────────────────────────────────────
$email = cleanEmail((string)$_POST['email']);
if (!isValidEmail($email)) {
    logBlockedAttempt('invalid_email', ['email' => $email]);
    redirect303(REDIRECT_ERR . '&reason=email');
}

// ── 8. Email jetable ───────────────────────────────────────────
if (isDisposableEmail($email)) {
    logBlockedAttempt('disposable_email', ['email' => $email]);
    redirect303(REDIRECT_ERR . '&reason=email');
}

// ── 9. Validation date ─────────────────────────────────────────
if (!isValidEventDate((string)$_POST['date'])) {
    logBlockedAttempt('invalid_date', ['date' => (string)$_POST['date']]);
    redirect303(REDIRECT_ERR);
}

// ── 10. Validation message (longueur + URLs excessives) ────────
$rawMessage = (string)$_POST['message'];
if (!isValidMessage($rawMessage)) {
    logBlockedAttempt('invalid_message', ['length' => mb_strlen(trim($rawMessage), 'UTF-8')]);
    redirect303(REDIRECT_ERR);
}

if (hasExcessiveUrls($rawMessage)) {
    logBlockedAttempt('excessive_urls', ['message_excerpt' => mb_substr($rawMessage, 0, 100, 'UTF-8')]);
    redirect303(REDIRECT_OK); // Silencieux — spam probable
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
