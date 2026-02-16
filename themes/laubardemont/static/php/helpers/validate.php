<?php
declare(strict_types=1);

function isHoneypotTriggered(array $post): bool {
    return !empty($post['hp_zx8']) || !empty($post['hp_qv3']);
}

function requireFields(array $post, array $fields): bool {
    foreach ($fields as $field) {
        if (empty($post[$field])) {
            return false;
        }
    }
    return true;
}

function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide une date d'événement.
 * Accepte dd/mm/yyyy ou yyyy-mm-dd (format HTML5 input[type=date]).
 * La date doit être dans le futur et au maximum 3 ans à partir d'aujourd'hui.
 */
function isValidEventDate(string $date): bool {
    $date = trim($date);

    // Tenter dd/mm/yyyy
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $m)) {
        $day   = (int) $m[1];
        $month = (int) $m[2];
        $year  = (int) $m[3];
    // Tenter yyyy-mm-dd (format navigateur)
    } elseif (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $date, $m)) {
        $year  = (int) $m[1];
        $month = (int) $m[2];
        $day   = (int) $m[3];
    } else {
        return false;
    }

    // Date calendaire valide
    if (!checkdate($month, $day, $year)) {
        return false;
    }

    $eventDate = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    $today     = new \DateTimeImmutable('today');
    $maxDate   = $today->modify('+3 years');

    // Doit être dans le futur (aujourd'hui exclu) et max 3 ans
    return $eventDate > $today && $eventDate <= $maxDate;
}