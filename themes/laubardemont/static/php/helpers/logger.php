<?php
declare(strict_types=1);

function writeDebug(mixed $data): void {
    if (!defined('DEBUG_LOG') || DEBUG_LOG !== true) {
        return;
    }
    
    $logFile = __DIR__ . '/../debug.txt';
    $timestamp = date('Y-m-d H:i:s');
    $content = "[$timestamp] ";
    
    if (is_array($data) || is_object($data)) {
        $content .= print_r($data, true);
    } else {
        $content .= (string)$data;
    }
    
    file_put_contents($logFile, $content . "\n", FILE_APPEND);
}