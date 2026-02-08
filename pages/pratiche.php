<?php
/**
 * Pratiche - Gestione pratiche con calendario
 */
require_once __DIR__ . '/../includes/header.php';

// Gestione richieste POST (delete)
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if(!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } elseif(isset($_POST['action'])) {
        switch($action) {
            case 'delete':
                deletePratica($_POST['id']);
                logAudit('delete', 'pratica', $_POST['id']);
                $message = 'Pratica eliminata con successo!';
                $message_type = 'success';
                break;
        }
    }
}

// Gestione filtri
$filters = [];
if(!empty($_GET['stato'])) $filters['stato'] = $_GET['stato'];
if(!empty($_GET['tipo_pratica'])) $filters['tipo_pratica'] = $_GET['tipo_pratica'];
if(!empty($_GET['anno'])) $filters['anno'] = $_GET['anno'];
if(!empty($_GET['mese'])) $filters['mese'] = $_GET['mese'];
if(!empty($_GET['cliente_id'])) $filters['cliente_id'] = $_GET['cliente_id'];

$pratiche = getPratiche($filters);

// Anni disponibili
$anni = range(getAppYearStart(), date('Y') + 1);
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Content -->
<div id="content" class="content">
    
    <!-- Navbar -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="container-fluid py-4">
        
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1 class="h3">Pratiche (<?php echo count($pratiche); ?>)</h1>
                <a class="btn btn-primary" href="/pages/pratica_form.php">
                    <i class="bi bi-plus-lg"></i> Nuova Pratica
                </a>
            </div>
        </div>
        
        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Stato</label>
                        <select name="stato" class="form-select">
                            <option value="">Tutti</option>
                            <option value="Aperta" <?php echo ($_GET['stato'] ?? '') == 'Aperta' ? 'selected' : ''; ?>>Aperta</option>
                            <option value="In corso" <?php echo ($_GET['stato'] ?? '') == 'In corso' ? 'selected' : ''; ?>>In corso</option>
                            <option value="Completata" <?php echo ($_GET['stato'] ?? '') == 'Completata' ? 'selected' : ''; ?>>Completata</option>
                            <option value="Annullata" <?php echo ($_GET['stato'] ?? '') == 'Annullata' ? 'selected' : ''; ?>>Annullata</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo Pratica</label>
                        <select name="tipo_pratica" class="form-select">
                            <option value="">Tutti</option>
                            <option value="Patente entro 12 miglia">Patente entro 12 miglia</option>
                            <option value="Patente oltre 12 miglia">Patente oltre 12 miglia</option>
                            <option value="Patente D1">Patente D1</option>
                            <option value="Rinnovo">Rinnovo</option>
                            <option value="Duplicato">Duplicato</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Anno</label>
                        <select name="anno" class="form-select">
                            <option value="">Tutti</option>
                            <?php foreach($anni as $anno): ?>
                                <option value="<?php echo $anno; ?>" <?php echo ($_GET['anno'] ?? '') == $anno ? 'selected' : ''; ?>>
                                    <?php echo $anno; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mese</label>
                        <select name="mese" class="form-select">
                            <option value="">Tutti</option>
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($_GET['mese'] ?? '') == $m ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtra</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabella Pratiche -->
        <div class="card">
            <div class="card-body">
                <?php $ricevutaModals = []; ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Telefono</th>
                                <th>Tipo Pratica</th>
                                <th>Stato</th>
                                <th>Stato Economico</th>
                                <th>Totale</th>
                                <th>Pagato</th>
                                <th>Residuo</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pratiche)): ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted">Nessuna pratica trovata</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($pratiche as $pratica): ?>
                                    <?php
                                    $isCompletata = ($pratica['stato'] ?? '') === 'Completata';
                                    $isSaldato = ($pratica['residuo'] ?? 0) <= 0 && ($pratica['totale_previsto'] ?? 0) > 0;
                                    $showRicevuta = $isCompletata && $isSaldato;
                                    $pagamentiRicevuta = [];
                                    $ricevutaData = formatDate($pratica['data_apertura']);
                                    $ricevutaNumero = '';
                                    if ($showRicevuta) {
                                        $pagamentiRicevuta = getPagamenti(['pratica_id' => $pratica['id']]);
                                        $dataRif = !empty($pagamentiRicevuta) ? $pagamentiRicevuta[0]['data_pagamento'] : $pratica['data_apertura'];
                                        $ricevutaData = formatDate($dataRif);
                                        $ricevutaNumero = date('Y', strtotime($dataRif)) . '-PR-' . $pratica['id'];
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $pratica['id']; ?></td>
                                        <td><?php echo formatDate($pratica['data_apertura']); ?></td>
                                        <td><?php echo htmlspecialchars($pratica['cliente_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($pratica['cliente_telefono'] ?? '-'); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($pratica['tipo_pratica']); ?></small>
                                        </td>
                                        <td><?php echo getStatoPraticaBadge($pratica['stato']); ?></td>
                                        <td><?php echo getStatoEconomicoBadge($pratica); ?></td>
                                        <td><?php echo formatMoney($pratica['totale_previsto']); ?></td>
                                        <td class="text-success"><?php echo formatMoney($pratica['totale_pagato']); ?></td>
                                        <td class="<?php echo $pratica['residuo'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                            <?php echo formatMoney($pratica['residuo']); ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                                <a href="pratica_dettaglio.php?id=<?php echo $pratica['id']; ?>" 
                                                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="Dettaglio">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($showRicevuta): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ricevutaModal-<?php echo $pratica['id']; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Ricevuta proforma">
                                                        <i class="bi bi-receipt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if ($showRicevuta): ?>
                                        <?php ob_start(); ?>
                                        <div class="modal fade" id="ricevutaModal-<?php echo $pratica['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content receipt-print">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Ricevuta Proforma</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                                                    </div>
                                                    <div class="modal-body receipt-proforma">
                                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                                                            <div>
                                                                <div class="receipt-title">Ricevuta proforma</div>
                                                                <div class="receipt-meta">Numero: <?php echo htmlspecialchars($ricevutaNumero); ?></div>
                                                                <div class="receipt-meta">Data: <?php echo htmlspecialchars($ricevutaData); ?></div>
                                                            </div>
                                                            <div class="text-md-end">
                                                                <div><strong>Autoscuola Liana</strong></div>
                                                                <div class="receipt-meta">Documento non fiscale</div>
                                                            </div>
                                                        </div>

                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <div class="receipt-section">
                                                                    <div class="fw-semibold mb-2">Cliente</div>
                                                                    <div><?php echo htmlspecialchars($pratica['cliente_nome']); ?></div>
                                                                    <div class="receipt-meta">Tel: <?php echo htmlspecialchars($pratica['cliente_telefono'] ?? '-'); ?></div>
                                                                    <div class="receipt-meta">Email: <?php echo htmlspecialchars($pratica['cliente_email'] ?? '-'); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="receipt-section">
                                                                    <div class="fw-semibold mb-2">Pratica</div>
                                                                    <div>ID: <?php echo $pratica['id']; ?></div>
                                                                    <div class="receipt-meta">Tipo: <?php echo htmlspecialchars($pratica['tipo_pratica']); ?></div>
                                                                    <div class="receipt-meta">Apertura: <?php echo formatDate($pratica['data_apertura']); ?></div>
                                                                    <div class="receipt-meta">Stato: <?php echo htmlspecialchars($pratica['stato']); ?> / Saldato</div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="receipt-section mt-3">
                                                            <div class="fw-semibold mb-2">Dettaglio pagamenti</div>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm receipt-table mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Data</th>
                                                                            <th>Tipo</th>
                                                                            <th>Metodo</th>
                                                                            <th>Note</th>
                                                                            <th class="text-end">Importo</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php if (empty($pagamentiRicevuta)): ?>
                                                                            <tr>
                                                                                <td colspan="5" class="text-center text-muted">Nessun pagamento registrato</td>
                                                                            </tr>
                                                                        <?php else: ?>
                                                                            <?php foreach ($pagamentiRicevuta as $pagamento): ?>
                                                                                <tr>
                                                                                    <td><?php echo formatDate($pagamento['data_pagamento']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($pagamento['tipo_pagamento']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($pagamento['metodo_pagamento']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($pagamento['note'] ?? '-'); ?></td>
                                                                                    <td class="text-end"><?php echo formatMoney($pagamento['importo']); ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        <div class="d-flex flex-column flex-md-row justify-content-end gap-3 mt-3">
                                                            <div class="receipt-section">
                                                                <div class="d-flex justify-content-between gap-4">
                                                                    <span>Totale previsto</span>
                                                                    <strong><?php echo formatMoney($pratica['totale_previsto']); ?></strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between gap-4">
                                                                    <span>Totale pagato</span>
                                                                    <strong><?php echo formatMoney($pratica['totale_pagato']); ?></strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between gap-4">
                                                                    <span>Residuo</span>
                                                                    <strong><?php echo formatMoney($pratica['residuo']); ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                                                        <button type="button" class="btn btn-primary receipt-print-btn">Stampa</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $ricevutaModals[] = ob_get_clean(); ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($ricevutaModals)): ?>
            <?php echo implode('', $ricevutaModals); ?>
        <?php endif; ?>
        
    </div>
    
</div>

<script nonce="<?php echo $cspNonce; ?>">
document.querySelectorAll('.receipt-print-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.body.classList.add('print-receipt');
        window.print();
        setTimeout(function() {
            document.body.classList.remove('print-receipt');
        }, 200);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
