<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$url = 'https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.xlsx';
$outFile = __DIR__ . '/../assets/data/istat_comuni.json';
$tmpFile = sys_get_temp_dir() . '/istat_comuni.xlsx';

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'header' => "User-Agent: NautikaPro/1.0\r\n"
    ]
]);

$data = @file_get_contents($url, false, $context);
if ($data === false && function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NautikaPro/1.0');
    $data = curl_exec($ch);
    curl_close($ch);
}

if ($data === false) {
    fwrite(STDERR, "Download ISTAT failed\n");
    exit(1);
}

file_put_contents($tmpFile, $data);

$reader = IOFactory::createReaderForFile($tmpFile);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($tmpFile);
$sheet = $spreadsheet->getActiveSheet();

$denCol = null;
$headerRow = $sheet->getRowIterator(1, 1)->current();
$cellIterator = $headerRow->getCellIterator();
$cellIterator->setIterateOnlyExistingCells(false);
foreach ($cellIterator as $cell) {
    $header = $cell->getValue();
    $headerLower = strtolower(trim((string)$header));
    if ($headerLower === '') {
        continue;
    }
    if (strpos($headerLower, 'denominazione') !== false) {
        $denCol = $cell->getColumn();
        if (strpos($headerLower, 'italiano') !== false || strpos($headerLower, 'comune') !== false) {
            break;
        }
    }
}

if ($denCol === null) {
    fwrite(STDERR, "Header not found\n");
    exit(1);
}

$comuni = [];
$rowIterator = $sheet->getRowIterator(2);
foreach ($rowIterator as $row) {
    $rowIndex = $row->getRowIndex();
    $cell = $sheet->getCell($denCol . $rowIndex);
    $name = trim((string)$cell->getValue());
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

if ($payload === false) {
    fwrite(STDERR, "JSON encode failed\n");
    exit(1);
}

file_put_contents($outFile, $payload);

fwrite(STDOUT, "OK\n");
