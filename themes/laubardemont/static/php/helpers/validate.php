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

/**
 * Challenge timing anti-bot.
 * Vérifie que le formulaire a mis au moins $minSeconds à être soumis.
 * Le champ _timing contient le timestamp Unix (secondes) injecté par JS au chargement.
 * Retourne true si le challenge échoue (soumission trop rapide ou champ absent/invalide).
 */
function isTimingChallengeFailed(array $post, int $minSeconds = 3): bool {
    if (empty($post['_timing'])) {
        return true; // Champ absent → probablement un bot sans JS
    }

    $loadTime = filter_var($post['_timing'], FILTER_VALIDATE_INT);
    if ($loadTime === false) {
        return true; // Valeur non numérique
    }

    $elapsed = time() - $loadTime;

    // Trop rapide ou timestamp dans le futur (manipulation)
    return $elapsed < $minSeconds || $elapsed < 0;
}