<?php
declare(strict_types=1);

/**
 * Envoie la demande de visite à l'API BelEvent.
 * Fire-and-forget : ne bloque jamais le flow, ne lance jamais d'exception au caller.
 *
 * @return array{success: bool, requestId: ?string, error: ?string}
 */
function sendToBelEvent(array $data): array
{
    $apiUrl = defined('BELEVENT_API_URL') ? BELEVENT_API_URL : (getenv('BELEVENT_API_URL') ?: '');
    $apiKey = defined('BELEVENT_API_KEY') ? BELEVENT_API_KEY : (getenv('BELEVENT_API_KEY') ?: '');
    $venueSlug = defined('BELEVENT_VENUE_SLUG') ? BELEVENT_VENUE_SLUG : 'laubardemont';

    if (empty($apiKey) || empty($apiUrl)) {
        return ['success' => false, 'requestId' => null, 'error' => 'API not configured'];
    }

    $payload = json_encode([
        'lastName'      => $data['last_name'] ?? '',
        'firstName'     => $data['first_name'] ?? '',
        'email'         => $data['email'] ?? '',
        'phone'         => !empty($data['phone']) ? $data['phone'] : null,
        'preferredDate' => !empty($data['date']) ? $data['date'] : null,
        'eventType'     => !empty($data['reason']) ? $data['reason'] : null,
        'guestCount'    => !empty($data['guest_count']) ? (int) $data['guest_count'] : null,
        'message'       => $data['message'] ?? '',
        'source'        => 'form',
    ], JSON_THROW_ON_ERROR);

    $ch = curl_init("{$apiUrl}/api/v1/venues/{$venueSlug}/visit-requests");

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            "X-API-Key: {$apiKey}",
        ],
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode < 200 || $httpCode >= 300) {
        error_log(sprintf(
            '[BelEvent API] Erreur envoi visit-request: HTTP %d, cURL: %s, Response: %s',
            $httpCode,
            $error,
            substr((string) $response, 0, 500)
        ));
        return ['success' => false, 'requestId' => null, 'error' => $error ?: "HTTP {$httpCode}"];
    }

    $decoded = json_decode((string) $response, true);

    return [
        'success'   => true,
        'requestId' => $decoded['id'] ?? null,
        'error'     => null,
    ];
}
