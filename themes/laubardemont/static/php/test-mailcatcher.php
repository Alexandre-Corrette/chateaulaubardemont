<?php
/**
 * Test end-to-end de l'envoi d'email via MailCatcher.
 *
 * Prérequis : MailCatcher actif sur localhost:1025
 *   gem install mailcatcher && mailcatcher
 *   Interface web : http://localhost:1080
 *
 * Usage : php test-mailcatcher.php
 */
declare(strict_types=1);

// Force le DSN MailCatcher pour ce test
define('MAILER_DSN', 'smtp://localhost:1025');
define('MAIL_FROM', 'formulaire-contact@chateau-laubardemont.com');
define('CONTACT_TO', 'contact@chateau-laubardemont.com');

require_once __DIR__ . '/helpers/mailer.php';
require_once __DIR__ . '/helpers/template.php';

echo "=== Test MailCatcher E2E ===\n\n";

// 1. Vérifier que MailCatcher est joignable
echo "1. Connexion SMTP localhost:1025... ";
$sock = @fsockopen('localhost', 1025, $errno, $errstr, 3);
if (!$sock) {
    echo "ECHEC ({$errstr})\n";
    echo "   -> Lancez MailCatcher : gem install mailcatcher && mailcatcher\n";
    exit(1);
}
fclose($sock);
echo "OK\n";

// 2. Envoyer un email de test via le pipeline complet
echo "2. Envoi email de test... ";

$data = [
    'first_name' => 'Jean',
    'last_name'  => 'Dupont',
    'email'      => 'jean.dupont@example.com',
    'phone'      => '06 12 34 56 78',
    'date'       => '15/06/2026',
    'reason'     => 'Mariage',
    'message'    => 'Ceci est un <strong>test</strong> end-to-end via MailCatcher.',
];

$subject = "Contact – {$data['first_name']} {$data['last_name']}";
$body    = buildContactBody($data);
$headers = buildHeaders(MAIL_FROM, $data['email']);

$sent = sendContactMail(CONTACT_TO, $subject, $body, $headers);
echo $sent ? "OK\n" : "ECHEC\n";

if (!$sent) {
    echo "   -> Vérifiez que MailCatcher est bien démarré.\n";
    exit(1);
}

// 3. Vérifier via l'API HTTP de MailCatcher
echo "3. Vérification via API MailCatcher (http://localhost:1080)... ";

$response = @file_get_contents('http://localhost:1080/messages');
if ($response === false) {
    echo "API inaccessible (non bloquant)\n";
} else {
    $messages = json_decode($response, true);
    if (is_array($messages) && count($messages) > 0) {
        $last = end($messages);
        echo "OK — Dernier mail : \"{$last['subject']}\"\n";
    } else {
        echo "Aucun message trouvé\n";
    }
}

echo "\n=== Résultat : " . ($sent ? "SUCCES" : "ECHEC") . " ===\n";
echo "Ouvrez http://localhost:1080 pour voir l'email dans MailCatcher.\n";
