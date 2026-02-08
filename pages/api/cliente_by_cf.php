<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

if (function_exists('isDeveloper') && isDeveloper()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$cf = trim($_GET['cf'] ?? '');
$excludeId = $_GET['exclude_id'] ?? null;
$excludeId = $excludeId !== null ? (int)$excludeId : null;

if ($cf === '') {
    echo json_encode(['found' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$cliente = getClienteByCodiceFiscale($cf, $excludeId);
if (!$cliente) {
    echo json_encode(['found' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'found' => true,
    'cliente' => [
        'id' => $cliente['id'],
        'nome' => $cliente['nome'],
        'cognome' => $cliente['cognome'],
        'telefono' => $cliente['telefono'] ?? '',
        'email' => $cliente['email'] ?? '',
        'codice_fiscale' => $cliente['codice_fiscale'] ?? '',
        'data_nascita' => $cliente['data_nascita'] ?? '',
        'citta_nascita' => $cliente['citta_nascita'] ?? '',
        'indirizzo' => $cliente['indirizzo'] ?? '',
        'citta' => $cliente['citta'] ?? ''
    ]
], JSON_UNESCAPED_UNICODE);
