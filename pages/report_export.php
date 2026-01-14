<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$type = $_GET['type'] ?? 'csv';
$anno = $_GET['anno'] ?? date('Y');
$mese = $_GET['mese'] ?? null;
$tipo_pratica = $_GET['tipo_pratica'] ?? null;
$metodo_pagamento = $_GET['metodo_pagamento'] ?? null;

$report = getReportEconomico($anno, $mese, $tipo_pratica ?: null, $metodo_pagamento ?: null);

$labelPeriodo = $mese
    ? (strftime('%B', mktime(0,0,0,$mese,1)) . ' ' . $anno)
    : ('Anno ' . $anno);

if ($type === 'excel') {
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        http_response_code(500);
        echo 'Dipendenze mancanti. Esegui: composer install';
        exit;
    }
    require __DIR__ . '/../vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Report');

    $sheet->setCellValue('A1', 'Report Economico');
    $sheet->setCellValue('A2', $labelPeriodo);
    $sheet->setCellValue('A4', 'Totale Entrate');
    $sheet->setCellValue('B4', $report['totale_entrate']);
    $sheet->setCellValue('A5', 'Totale Uscite');
    $sheet->setCellValue('B5', $report['totale_uscite']);
    $sheet->setCellValue('A6', 'Saldo');
    $sheet->setCellValue('B6', $report['saldo']);

    $sheet->setCellValue('A8', 'Entrate per Metodo');
    $row = 9;
    foreach ($report['entrate'] as $e) {
        $sheet->setCellValue('A' . $row, $e['metodo_pagamento']);
        $sheet->setCellValue('B' . $row, $e['numero_transazioni']);
        $sheet->setCellValue('C' . $row, $e['totale']);
        $row++;
    }

    $row += 1;
    $sheet->setCellValue('A' . $row, 'Uscite per Categoria');
    $row++;
    foreach ($report['uscite'] as $u) {
        $sheet->setCellValue('A' . $row, $u['categoria']);
        $sheet->setCellValue('B' . $row, $u['numero_spese']);
        $sheet->setCellValue('C' . $row, $u['totale']);
        $row++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="report_' . $anno . ($mese ? '_' . $mese : '') . '.xlsx"');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if ($type === 'pdf') {
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        http_response_code(500);
        echo 'Dipendenze mancanti. Esegui: composer install';
        exit;
    }
    require __DIR__ . '/../vendor/autoload.php';

    $html = '<h2>Report Economico</h2>';
    $html .= '<p>' . htmlspecialchars($labelPeriodo) . '</p>';
    $html .= '<p><strong>Totale Entrate:</strong> ' . formatMoney($report['totale_entrate']) . '</p>';
    $html .= '<p><strong>Totale Uscite:</strong> ' . formatMoney($report['totale_uscite']) . '</p>';
    $html .= '<p><strong>Saldo:</strong> ' . formatMoney($report['saldo']) . '</p>';
    $html .= '<h3>Entrate per Metodo</h3><ul>';
    foreach ($report['entrate'] as $e) {
        $html .= '<li>' . htmlspecialchars($e['metodo_pagamento']) . ': ' . formatMoney($e['totale']) . '</li>';
    }
    $html .= '</ul><h3>Uscite per Categoria</h3><ul>';
    foreach ($report['uscite'] as $u) {
        $html .= '<li>' . htmlspecialchars($u['categoria']) . ': ' . formatMoney($u['totale']) . '</li>';
    }
    $html .= '</ul>';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('report_' . $anno . ($mese ? '_' . $mese : '') . '.pdf');
    exit;
}

// CSV fallback
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_' . $anno . ($mese ? '_' . $mese : '') . '.csv"');

$out = fopen('php://output', 'w');

fputcsv($out, ['Report Economico', $labelPeriodo]);
fputcsv($out, ['Totale Entrate', $report['totale_entrate']]);
fputcsv($out, ['Totale Uscite', $report['totale_uscite']]);
fputcsv($out, ['Saldo', $report['saldo']]);
fputcsv($out, []);

fputcsv($out, ['Entrate per Metodo']);
fputcsv($out, ['Metodo', 'Transazioni', 'Totale']);
foreach ($report['entrate'] as $e) {
    fputcsv($out, [$e['metodo_pagamento'], $e['numero_transazioni'], $e['totale']]);
}

fputcsv($out, []);
fputcsv($out, ['Uscite per Categoria']);
fputcsv($out, ['Categoria', 'Numero', 'Totale']);
foreach ($report['uscite'] as $u) {
    fputcsv($out, [$u['categoria'], $u['numero_spese'], $u['totale']]);
}

fclose($out);
exit;
