<?php
declare(strict_types=1);

function writeDebug(mixed $data): void {
    if (!defined('DEBUG_LOG') || DEBUG_LOG !== true) return;

    $logFile = __DIR__ . '/../debug.txt';
    $timestamp = date('Y-m-d H:i:s');
    $content = "--- LOG $timestamp ---\n";

    if (is_array($data) || is_object($data)) {
        $content .= print_r($data, true);
    } else {
        $content .= (string)$data . "\n";
    }

    $content .= "\n--------------------------\n\n";
    file_put_contents($logFile, $content, FILE_APPEND);
}
