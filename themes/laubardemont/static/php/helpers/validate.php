<?php
declare(strict_types=1);

function isHoneypotTriggered(array $post): bool {
    // généralement OR (un seul champ rempli suffit à être suspect)
    return !empty($post['name']) || !empty($post['website']);
}

function requireFields(array $post, array $required): bool {
    foreach ($required as $field) {
        if (!isset($post[$field]) || trim((string)$post[$field]) === '') {
            return false;
        }
    }
    return true;
}

function isValidEmail(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

// valider une date (HTML date => YYYY-MM-DD)
function normalizeDate(string $raw): string {
    $raw = trim($raw);
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
    if (!$dt) return $raw; // fallback sans casser
    return $dt->format('d-m-Y');
}
