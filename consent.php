<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$consent = $payload['consent'] ?? '';

$allowed = ['accepted', 'rejected', 'essential'];
if (!in_array($consent, $allowed, true)) {
    http_response_code(400);
    exit;
}

$user = currentUser();
setConsentCookie($consent);
try {
    saveConsent($consent, $user['id'] ?? null);
} catch (Exception $e) {
    // ignore db errors to avoid blocking UX
}

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
