<?php
declare(strict_types=1);

function buildHeaders(string $from, string $replyTo): string {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html; charset=utf-8\r\n";
    $headers .= "From: Chateau Laubardemont <{$from}>\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    return $headers;
}

function sendContactMail(string $to, string $subject, string $body, string $headers): bool {
    return mail($to, $subject, $body, $headers);
}
