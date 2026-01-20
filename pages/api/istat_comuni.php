<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

$cacheDir = __DIR__ . '/../../uploads/cache';
$cacheFile = $cacheDir . '/istat_comuni.json';
$cacheTtl = 60 * 60 * 24 * 7;

$query = trim($_GET['q'] ?? '');
$limit = (int)($_GET['limit'] ?? 20);
$limit = max(1, min(50, $limit));

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

function outputComuni($comuni, $query, $limit) {
    if ($query !== '') {
        $q = mb_strtolower($query, 'UTF-8');
        $filtered = array_values(array_filter($comuni, function($name) use ($q) {
            return mb_strpos(mb_strtolower($name, 'UTF-8'), $q) !== false;
        }));
        $comuni = array_slice($filtered, 0, $limit);
    }

    echo json_encode([
        'updated_at' => date('c'),
        'count' => count($comuni),
        'comuni' => $comuni
    ], JSON_UNESCAPED_UNICODE);
}

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    $raw = file_get_contents($cacheFile);
    $payload = json_decode($raw, true);
    if (is_array($payload) && isset($payload['comuni']) && is_array($payload['comuni'])) {
        outputComuni($payload['comuni'], $query, $limit);
        exit;
    }
}

$url = 'https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.xlsx';
$tmpFile = $cacheDir . '/istat_comuni.xlsx';

try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: NautikaPro/1.0\r\n"
        ]
    ]);

    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'NautikaPro/1.0');
            $data = curl_exec($ch);
            curl_close($ch);
        }
    }
    if ($data === false) {
        throw new Exception('Download failed');
    }
    file_put_contents($tmpFile, $data);

    $spreadsheet = IOFactory::load($tmpFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    if (empty($rows)) {
        throw new Exception('Empty dataset');
    }

    $headers = array_shift($rows);
    $denCol = null;
    foreach ($headers as $col => $header) {
        $headerLower = strtolower(trim((string)$header));
        if ($headerLower === '') {
            continue;
        }
        if (strpos($headerLower, 'denominazione') !== false) {
            $denCol = $col;
            if (strpos($headerLower, 'italiano') !== false || strpos($headerLower, 'comune') !== false) {
                break;
            }
        }
    }

    if ($denCol === null) {
        throw new Exception('Header not found');
    }

    $comuni = [];
    foreach ($rows as $row) {
        $name = trim((string)($row[$denCol] ?? ''));
        if ($name === '') {
            continue;
        }
        $comuni[$name] = true;
    }

    $list = array_keys($comuni);
    sort($list, SORT_LOCALE_STRING);

    $payload = json_encode([
        'updated_at' => date('c'),
        'count' => count($list),
        'comuni' => $list
    ], JSON_UNESCAPED_UNICODE);

    file_put_contents($cacheFile, $payload);
    outputComuni($list, $query, $limit);
} catch (Throwable $e) {
    if (file_exists($cacheFile)) {
        $raw = file_get_contents($cacheFile);
        $payload = json_decode($raw, true);
        if (is_array($payload) && isset($payload['comuni']) && is_array($payload['comuni'])) {
            outputComuni($payload['comuni'], $query, $limit);
            exit;
        }
    } else {
        echo json_encode(['updated_at' => date('c'), 'count' => 0, 'comuni' => []], JSON_UNESCAPED_UNICODE);
    }
}
