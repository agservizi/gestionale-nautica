<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

$to = getenv('RESEND_TO');
if (!$to) {
    echo "RESEND_TO mancante\n";
    exit(1);
}

$summary = getNotificationSummary();
$upcoming = getUpcomingGuide(1);

$html = '<h2>Promemoria Giornalieri</h2>';
$html .= '<p><strong>Guide oggi:</strong> ' . $summary['today_guides'] . '</p>';
$html .= '<p><strong>Guide domani:</strong> ' . $summary['tomorrow_guides'] . '</p>';
$html .= '<p><strong>Pratiche scoperte:</strong> ' . $summary['pratiche_scoperte'] . '</p>';

if (!empty($upcoming)) {
    $html .= '<h3>Guide di oggi</h3><ul>';
    foreach ($upcoming as $g) {
        $html .= '<li>' . htmlspecialchars($g['cliente_nome']) . ' - ' . $g['data_guida'] . ' ' . substr($g['orario_inizio'],0,5) . '</li>';
    }
    $html .= '</ul>';
}

try {
    sendResendEmail($to, 'Promemoria NautikaPro', $html);
    echo "Email inviata\n";
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
    exit(1);
}
