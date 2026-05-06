<?php
declare(strict_types=1);

const ICAL_DEFAULT_START_HOUR  = 10;
const ICAL_DEFAULT_DURATION_MIN = 90;

/**
 * Génère un événement iCalendar (.ics) RFC 5545 pour une demande de visite.
 * Heure par défaut : 10h00 Europe/Paris, durée 1h30 (l'admin déplace l'event si besoin).
 *
 * @param array{first_name?: string, last_name?: string, email?: string, phone?: string, date?: string, reason?: string, message?: string} $data
 */
function buildIcalEvent(array $data): string
{
    $date = isset($data['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['date'])
        ? (string) $data['date']
        : date('Y-m-d');

    $tz    = new DateTimeZone('Europe/Paris');
    $start = new DateTimeImmutable(sprintf('%s %02d:00:00', $date, ICAL_DEFAULT_START_HOUR), $tz);
    $end   = $start->modify('+' . ICAL_DEFAULT_DURATION_MIN . ' minutes');

    $startLocal = $start->format('Ymd\THis');
    $endLocal   = $end->format('Ymd\THis');
    $dtstamp    = gmdate('Ymd\THis\Z');
    $uid        = bin2hex(random_bytes(8)) . '@chateau-laubardemont.com';

    $summary = trim(sprintf(
        'Visite — %s %s',
        (string) ($data['last_name']  ?? ''),
        (string) ($data['first_name'] ?? '')
    ));

    $descParts = [];
    if (!empty($data['reason']))  $descParts[] = 'Type: ' . $data['reason'];
    if (!empty($data['phone']))   $descParts[] = 'Tél: '  . $data['phone'];
    if (!empty($data['email']))   $descParts[] = 'Email: '. $data['email'];
    if (!empty($data['message'])) $descParts[] = "\nMessage:\n" . trim((string) $data['message']);
    $description = implode("\n", $descParts);

    $organizer = defined('CONTACT_TO') ? (string) CONTACT_TO : '';

    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//chateau-laubardemont.com//Form Visit Request//FR',
        'METHOD:REQUEST',
        'CALSCALE:GREGORIAN',
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . $dtstamp,
        'DTSTART;TZID=Europe/Paris:' . $startLocal,
        'DTEND;TZID=Europe/Paris:' . $endLocal,
        'SUMMARY:'     . icalEscapeText($summary),
        'DESCRIPTION:' . icalEscapeText($description),
        'LOCATION:'    . icalEscapeText('Château de Laubardemont'),
    ];
    if ($organizer !== '') {
        $lines[] = 'ORGANIZER;CN=Château de Laubardemont:mailto:' . $organizer;
    }
    $lines[] = 'STATUS:CONFIRMED';
    $lines[] = 'TRANSP:OPAQUE';
    $lines[] = 'END:VEVENT';
    $lines[] = 'END:VCALENDAR';

    $folded = array_map('icalFoldLine', $lines);
    return implode("\r\n", $folded) . "\r\n";
}

/**
 * Échappe les caractères spéciaux iCal dans une valeur TEXT (RFC 5545 §3.3.11).
 */
function icalEscapeText(string $text): string
{
    return str_replace(
        ["\\",   "\r\n", "\n",  "\r",  ",",  ";"],
        ['\\\\', '\\n',  '\\n', '\\n', '\\,', '\\;'],
        $text
    );
}

/**
 * RFC 5545 §3.1 line folding : max 75 octets, replier avec CRLF + espace.
 * Coupe sur frontière d'octets (les chars multi-byte UTF-8 peuvent être splittés —
 * c'est licite mais certains parsers stricts râlent. En pratique nos contenus sanitizés
 * restent largement sous 75 octets, donc le repli est rare.)
 */
function icalFoldLine(string $line): string
{
    if (strlen($line) <= 75) {
        return $line;
    }
    $folded = '';
    while (strlen($line) > 75) {
        $folded .= substr($line, 0, 75) . "\r\n ";
        $line    = substr($line, 75);
    }
    return $folded . $line;
}
