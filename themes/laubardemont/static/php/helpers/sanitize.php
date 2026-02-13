<?php
declare(strict_types=1);

function cleanText(string $value): string {
    return trim(strip_tags($value));
}

function cleanEmail(string $value): string {
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
}

function cleanMessage(string $value): string {
    return nl2br(htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8'));
}

function normalizeDate(string $date): string {
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : $date;
}