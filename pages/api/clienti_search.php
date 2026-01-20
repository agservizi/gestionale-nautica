<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$limit = (int)($_GET['limit'] ?? 8);
$limit = max(1, min(15, $limit));

if ($q === '') {
    echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$clienti = getClienti($q, $limit, 0);
$results = [];
foreach ($clienti as $cliente) {
    $results[] = [
        'id' => $cliente['id'],
        'nome' => $cliente['nome'],
        'cognome' => $cliente['cognome'],
        'email' => $cliente['email'] ?? '',
        'codice_fiscale' => $cliente['codice_fiscale'] ?? '',
    ];
}

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);