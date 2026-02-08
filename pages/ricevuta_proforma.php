<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$praticaId = (int)($_GET['pratica_id'] ?? 0);
if ($praticaId <= 0) {
    http_response_code(400);
    echo 'Parametro non valido.';
    exit;
}

$pratica = getPraticaById($praticaId);
if (!$pratica) {
    http_response_code(404);
    echo 'Pratica non trovata.';
    exit;
}

$isCompletata = ($pratica['stato'] ?? '') === 'Completata';
$isSaldato = ($pratica['residuo'] ?? 0) <= 0 && ($pratica['totale_previsto'] ?? 0) > 0;
if (!$isCompletata || !$isSaldato) {
    http_response_code(403);
    echo 'Ricevuta non disponibile.';
    exit;
}

$pagamenti = getPagamenti(['pratica_id' => $praticaId]);
$dataRif = !empty($pagamenti) ? $pagamenti[0]['data_pagamento'] : $pratica['data_apertura'];
$ricevutaData = formatDate($dataRif);
$ricevutaNumero = date('Y', strtotime($dataRif)) . '-PR-' . $pratica['id'];

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    echo 'Dipendenze mancanti. Esegui: composer install';
    exit;
}
require __DIR__ . '/../vendor/autoload.php';

$rows = '';
if (empty($pagamenti)) {
    $rows = '<tr><td colspan="5" style="text-align:center; color:#6c757d;">Nessun pagamento registrato</td></tr>';
} else {
    foreach ($pagamenti as $pagamento) {
        $rows .= '<tr>';
        $rows .= '<td>' . htmlspecialchars(formatDate($pagamento['data_pagamento'])) . '</td>';
        $rows .= '<td>' . htmlspecialchars($pagamento['tipo_pagamento']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($pagamento['metodo_pagamento']) . '</td>';
        $rows .= '<td>' . htmlspecialchars($pagamento['note'] ?? '-') . '</td>';
        $rows .= '<td style="text-align:right;">' . htmlspecialchars(formatMoney($pagamento['importo'])) . '</td>';
        $rows .= '</tr>';
    }
}

$html = '
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Ricevuta proforma</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
    .header { display: table; width: 100%; margin-bottom: 16px; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    .title { font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
    .meta { color: #6c757d; font-size: 11px; }
    .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f8f9fa; text-transform: uppercase; font-size: 10px; letter-spacing: 0.04em; text-align: left; padding: 6px; }
    td { padding: 6px; border-top: 1px solid #e5e7eb; }
    .totals { width: 240px; margin-left: auto; }
    .totals td { border: 0; padding: 4px 0; }
    .totals .label { color: #6c757d; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <div class="title">Ricevuta proforma</div>
            <div class="meta">Numero: ' . htmlspecialchars($ricevutaNumero) . '</div>
            <div class="meta">Data: ' . htmlspecialchars($ricevutaData) . '</div>
        </div>
        <div class="right">
            <div><strong>Autoscuola Liana</strong></div>
            <div class="meta">Documento non fiscale</div>
        </div>
    </div>

    <div class="box">
        <strong>Cliente</strong><br>
        ' . htmlspecialchars($pratica['cliente_nome']) . '<br>
        <span class="meta">Tel: ' . htmlspecialchars($pratica['cliente_telefono'] ?? '-') . '</span><br>
        <span class="meta">Email: ' . htmlspecialchars($pratica['cliente_email'] ?? '-') . '</span>
    </div>

    <div class="box">
        <strong>Pratica</strong><br>
        ID: ' . htmlspecialchars($pratica['id']) . '<br>
        <span class="meta">Tipo: ' . htmlspecialchars($pratica['tipo_pratica']) . '</span><br>
        <span class="meta">Apertura: ' . htmlspecialchars(formatDate($pratica['data_apertura'])) . '</span><br>
        <span class="meta">Stato: ' . htmlspecialchars($pratica['stato']) . ' / Saldato</span>
    </div>

    <div class="box">
        <strong>Dettaglio pagamenti</strong>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Metodo</th>
                    <th>Note</th>
                    <th style="text-align:right;">Importo</th>
                </tr>
            </thead>
            <tbody>
                ' . $rows . '
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
            <td class="label">Totale previsto</td>
            <td style="text-align:right;"><strong>' . htmlspecialchars(formatMoney($pratica['totale_previsto'])) . '</strong></td>
        </tr>
        <tr>
            <td class="label">Totale pagato</td>
            <td style="text-align:right;"><strong>' . htmlspecialchars(formatMoney($pratica['totale_pagato'])) . '</strong></td>
        </tr>
        <tr>
            <td class="label">Residuo</td>
            <td style="text-align:right;"><strong>' . htmlspecialchars(formatMoney($pratica['residuo'])) . '</strong></td>
        </tr>
    </table>
</body>
</html>';

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('ricevuta_proforma_' . $pratica['id'] . '.pdf', ['Attachment' => false]);
exit;
