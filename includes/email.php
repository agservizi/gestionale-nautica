<?php
require_once __DIR__ . '/../config/config.php';

function sendResendEmail($to, $subject, $html) {
    $apiKey = getenv('RESEND_API_KEY');
    $from = getenv('RESEND_FROM') ?: 'no-reply@nautikapro.local';

    if (!$apiKey) {
        throw new Exception('RESEND_API_KEY mancante');
    }

    $payload = json_encode([
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $html
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => $payload
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status < 200 || $status >= 300) {
        throw new Exception('Errore Resend: ' . $response);
    }

    return true;
}
