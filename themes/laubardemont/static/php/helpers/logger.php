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

/**
 * Enregistre une tentative bloquée par les protections anti-spam.
 * Écrit dans security.log (toujours actif, indépendant de DEBUG_LOG).
 *
 * @param string $reason  Identifiant court du motif (ex: 'honeypot', 'rate_limit', 'timing')
 * @param array  $context Données supplémentaires (champs partiels, seuils, etc.)
 */
function logBlockedAttempt(string $reason, array $context = []): void {
    $logFile = __DIR__ . '/../security.log';

    $entry = [
        'time'       => date('Y-m-d H:i:s'),
        'reason'     => $reason,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referer'    => $_SERVER['HTTP_REFERER'] ?? '',
    ];

    if ($context !== []) {
        $entry['context'] = $context;
    }

    file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
}