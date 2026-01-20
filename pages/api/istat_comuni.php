<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

$cacheDir = __DIR__ . '/../../uploads/cache';
$cacheFile = $cacheDir . '/istat_comuni.json';
$cacheTtl = 60 * 60 * 24 * 7;

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    readfile($cacheFile);
    exit;
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

    $data = file_get_contents($url, false, $context);
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
    echo $payload;
} catch (Throwable $e) {
    if (file_exists($cacheFile)) {
        readfile($cacheFile);
    } else {
        echo json_encode(['updated_at' => date('c'), 'count' => 0, 'comuni' => []], JSON_UNESCAPED_UNICODE);
    }
}
