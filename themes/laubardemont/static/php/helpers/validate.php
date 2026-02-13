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