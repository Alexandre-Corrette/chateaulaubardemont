<?php
declare(strict_types=1);

function buildContactBody(array $data): string {
    $first   = $data['first_name'];
    $last    = $data['last_name'];
    $email   = $data['email'];
    $reason  = $data['reason'];
    $date    = $data['date'];
    $message = $data['message'];

    $body  = "<h2>Nouveau message de contact</h2>";
    $body .= "<p><strong>Nom :</strong> {$first} {$last}</p>";
    $body .= "<p><strong>Email :</strong> {$email}</p>";
    if ($reason !== '') {
        $body .= "<p><strong>Évènement :</strong> {$reason}</p>";
    }
    $body .= "<p><strong>Date :</strong> {$date}</p>";
    $body .= "<p><strong>Message :</strong><br>{$message}</p>";

    return $body;
}
