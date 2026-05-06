<?php
declare(strict_types=1);

/**
 * Envoie la demande de contact à un webhook Zapier (fallback BelEvent).
 * Fire-and-forget : ne bloque jamais le flow, ne lance jamais d'exception au caller.
 *
 * @return array{success: bool, error: ?string}
 */
function sendToZapier(array $data): array
{
    if (!defined('ZAPIER_ENABLED') || ZAPIER_ENABLED !== true) {
        return ['success' => false, 'error' => 'Zapier disabled'];
    }

    $webhookUrl = defined('ZAPIER_WEBHOOK_URL') ? ZAPIER_WEBHOOK_URL : '';
    $webhookSecret = defined('ZAPIER_WEBHOOK_SECRET') ? ZAPIER_WEBHOOK_SECRET : '';

    if ($webhookUrl === '' || $webhookSecret === '') {
        return ['success' => false, 'error' => 'Zapier not configured'];
    }

    $payload = json_encode([
        'nom'            => $data['last_name'] ?? '',
        'prenom'         => $data['first_name'] ?? '',
        'email'          => $data['email'] ?? '',
        'telephone'      => !empty($data['phone']) ? $data['phone'] : null,
        'date_souhaitee' => !empty($data['date']) ? $data['date'] : null,
        'type_evenement' => !empty($data['reason']) ? $data['reason'] : null,
        'nb_invites'     => !empty($data['guest_count']) ? (int) $data['guest_count'] : null,
        'message'        => $data['message'] ?? '',
        '_secret'        => $webhookSecret,
    ], JSON_THROW_ON_ERROR);

    $ch = curl_init($webhookUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode < 200 || $httpCode >= 300) {
        error_log(sprintf(
            '[Zapier Webhook] Erreur envoi: HTTP %d, cURL: %s',
            $httpCode,
            $error
        ));
        return ['success' => false, 'error' => $error ?: "HTTP {$httpCode}"];
    }

    return ['success' => true, 'error' => null];
}
