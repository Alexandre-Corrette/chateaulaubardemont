<?php
declare(strict_types=1);

function buildHeaders(string $from, string $replyTo): string {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers .= "From: {$from}\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    return $headers;
}

/**
 * Parse un DSN SMTP de type "smtp://host:port" ou "smtp://user:pass@host:port".
 * @return array{host: string, port: int, user: ?string, pass: ?string}|null
 */
function parseMailerDsn(string $dsn): ?array {
    if ($dsn === '') {
        return null;
    }

    $parts = parse_url($dsn);
    if (!$parts || ($parts['scheme'] ?? '') !== 'smtp') {
        return null;
    }

    return [
        'host' => $parts['host'] ?? 'localhost',
        'port' => $parts['port'] ?? 25,
        'user' => isset($parts['user']) ? urldecode($parts['user']) : null,
        'pass' => isset($parts['pass']) ? urldecode($parts['pass']) : null,
    ];
}

/**
 * Envoie un mail via socket SMTP (sans dépendance externe).
 * Compatible MailCatcher, MailHog, ou tout SMTP sans TLS.
 */
function sendSmtp(array $smtp, string $to, string $subject, string $body, string $from, string $replyTo): bool {
    $sock = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 5);
    if (!$sock) {
        error_log("[Mailer] SMTP connect failed: {$errstr} ({$errno})");
        return false;
    }

    $read = function () use ($sock): string {
        return fgets($sock, 512) ?: '';
    };

    $send = function (string $cmd) use ($sock, $read): string {
        fwrite($sock, $cmd . "\r\n");
        return $read();
    };

    $greeting = $read();
    if (strpos($greeting, '220') !== 0) {
        fclose($sock);
        return false;
    }

    $send("EHLO localhost");
    // Consomme les lignes multi-lignes EHLO (250-)
    while (true) {
        $line = $read();
        if ($line === '' || strpos($line, '250 ') === 0) {
            break;
        }
    }

    $send("MAIL FROM:<{$from}>");
    $send("RCPT TO:<{$to}>");
    $resp = $send("DATA");

    if (strpos($resp, '354') !== 0) {
        $send("QUIT");
        fclose($sock);
        return false;
    }

    $message  = "To: {$to}\r\n";
    $message .= "From: {$from}\r\n";
    $message .= "Reply-To: {$replyTo}\r\n";
    $message .= "Subject: {$subject}\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=utf-8\r\n";
    $message .= "\r\n";
    $message .= $body;
    $message .= "\r\n.\r\n";

    fwrite($sock, $message);
    $result = $read();

    $send("QUIT");
    fclose($sock);

    return strpos($result, '250') === 0;
}

function sendContactMail(string $to, string $subject, string $body, string $headers): bool {
    $dsn = defined('MAILER_DSN') ? MAILER_DSN : '';
    $smtp = parseMailerDsn($dsn);

    if ($smtp !== null) {
        // Extraire From et Reply-To des headers pour les passer au SMTP
        $from = MAIL_FROM;
        $replyTo = $from;
        if (preg_match('/Reply-To:\s*(.+)/i', $headers, $m)) {
            $replyTo = trim($m[1]);
        }
        return sendSmtp($smtp, $to, $subject, $body, $from, $replyTo);
    }

    return mail($to, $subject, $body, $headers);
}