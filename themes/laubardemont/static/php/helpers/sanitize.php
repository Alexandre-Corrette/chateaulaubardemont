<?php
declare(strict_types=1);

function cleanText(?string $value): string {
    return strip_tags(trim($value ?? ''));
}

function cleanEmail(string $value): string {
    return (string)filter_var($value, FILTER_SANITIZE_EMAIL);
}

function cleanMessage(string $value): string {
    // Le nl2br après htmlspecialchars est OK si tu assumes HTML email.
    return nl2br(htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}
