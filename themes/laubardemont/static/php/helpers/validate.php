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

/**
 * Valide un numéro de téléphone français.
 * Accepte les formats courants : 06 07 08 09, +33, 0033, avec ou sans
 * espaces/points/tirets. Champ optionnel : retourne true si vide.
 */
function isValidPhone(string $phone): bool {
    $phone = trim($phone);

    // Champ optionnel — vide = valide
    if ($phone === '') {
        return true;
    }

    // Supprimer espaces, points, tirets pour normaliser
    $normalized = preg_replace('/[\s.\-]/', '', $phone);

    // Formats acceptés :
    // 0X XX XX XX XX  → 10 chiffres commençant par 0
    // +33X XX XX XX XX → +33 suivi de 9 chiffres
    // 0033X XX XX XX XX → 0033 suivi de 9 chiffres
    if (preg_match('/^0[1-9]\d{8}$/', $normalized)) {
        return true;
    }
    if (preg_match('/^\+33[1-9]\d{8}$/', $normalized)) {
        return true;
    }
    if (preg_match('/^0033[1-9]\d{8}$/', $normalized)) {
        return true;
    }

    return false;
}

/**
 * Valide la longueur du message (20-5000 caractères).
 * Compte sur le texte brut avant sanitization HTML.
 */
function isValidMessage(string $message): bool {
    $message = trim($message);
    $length = mb_strlen($message, 'UTF-8');

    return $length >= 20 && $length <= 5000;
}

/**
 * Détecte un nombre excessif d'URLs dans un texte.
 * Retourne true si le nombre d'URLs dépasse $max (défaut 2).
 */
function hasExcessiveUrls(string $text, int $max = 2): bool {
    // Compter les occurrences de patterns URL
    $count = preg_match_all(
        '#https?://[^\s<>\'"]+|www\.[^\s<>\'"]+#i',
        $text
    );

    return $count > $max;
}

/**
 * Vérifie si l'email utilise un domaine jetable connu.
 * Retourne true si le domaine est dans la blacklist.
 */
function isDisposableEmail(string $email): bool {
    $disposableDomains = [
        'mailinator.com',
        'guerrillamail.com',
        'guerrillamail.net',
        'tempmail.com',
        'throwaway.email',
        'yopmail.com',
        'yopmail.fr',
        'trashmail.com',
        'trashmail.net',
        'sharklasers.com',
        'guerrillamailblock.com',
        'grr.la',
        'dispostable.com',
        'mailnesia.com',
        'maildrop.cc',
        'temp-mail.org',
        'fakeinbox.com',
        'tempail.com',
        'mohmal.com',
        'getnada.com',
        'emailondeck.com',
    ];

    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return false;
    }

    $domain = strtolower(trim($parts[1]));

    return in_array($domain, $disposableDomains, true);
}