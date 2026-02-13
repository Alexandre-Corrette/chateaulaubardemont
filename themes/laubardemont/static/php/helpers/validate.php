<?php
declare(strict_types=1);

function isHoneypotTriggered(array $post): bool {
    return !empty($post['company_fax']) || !empty($post['url_callback']);
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