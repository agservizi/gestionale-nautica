<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metodo non consentito']);
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'CSRF non valido']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$date = $_POST['date'] ?? '';

if ($id <= 0 || $date === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Dati non validi']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("UPDATE agenda_guide SET data_guida = ? WHERE id = ?");
$stmt->execute([$date, $id]);

logAudit('update', 'agenda', $id, 'data_guida=' . $date);

echo json_encode(['ok' => true]);
