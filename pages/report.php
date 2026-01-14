<?php
/**
 * Report - Report economici e statistiche
 */
require_once __DIR__ . '/../includes/header.php';

// Filtri
$anno = $_GET['anno'] ?? date('Y');
$mese = $_GET['mese'] ?? null;
$tipo_pratica = $_GET['tipo_pratica'] ?? '';
$metodo_pagamento = $_GET['metodo_pagamento'] ?? '';

// Ottieni report economico
$report = getReportEconomico($anno, $mese, $tipo_pratica ?: null, $metodo_pagamento ?: null);

$entrateMensili = getEntrateMensili($anno, $metodo_pagamento ?: null, $tipo_pratica ?: null);
$usciteMensili = getUsciteMensili($anno);

// Ottieni dettagli pratiche per tipo
$db = getDB();
$sql_pratiche_tipo = "SELECT tipo_pratica, COUNT(*) as count FROM pratiche WHERE YEAR(data_apertura) = ?";
$params = [$anno];
if($mese) {
    $sql_pratiche_tipo .= " AND MONTH(data_apertura) = ?";
    $params[] = $mese;
}
if($tipo_pratica) {
    $sql_pratiche_tipo .= " AND tipo_pratica = ?";
    $params[] = $tipo_pratica;
}
$sql_pratiche_tipo .= " GROUP BY tipo_pratica";
$stmt = $db->prepare($sql_pratiche_tipo);
$stmt->execute($params);
$pratiche_per_tipo = $stmt->fetchAll();

$anni = range(APP_YEAR_START, date('Y') + 1);
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Content -->
<div id="content" class="content">
    
    <!-- Navbar -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="container-fluid py-4">
        
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Report Economico</h1>
                <p class="text-muted">
                    <?php 
                    if($mese) {
                        echo date('F', mktime(0, 0, 0, $mese, 1)) . ' ' . $anno;
                    } else {
                        echo 'Anno ' . $anno;
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Anno</label>
                        <select name="anno" class="form-select">
                            <?php foreach($anni as $a): ?>
                                <option value="<?php echo $a; ?>" <?php echo $anno == $a ? 'selected' : ''; ?>>
                                    <?php echo $a; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mese</label>
                        <select name="mese" class="form-select">
                            <option value="">Tutto l'anno</option>
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $mese == $m ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo Pratica</label>
                        <select name="tipo_pratica" class="form-select">
                            <option value="">Tutte</option>
                            <option value="Patente entro 12 miglia" <?php echo $tipo_pratica == 'Patente entro 12 miglia' ? 'selected' : ''; ?>>Patente entro 12 miglia</option>
                            <option value="Patente oltre 12 miglia" <?php echo $tipo_pratica == 'Patente oltre 12 miglia' ? 'selected' : ''; ?>>Patente oltre 12 miglia</option>
                            <option value="Patente D1" <?php echo $tipo_pratica == 'Patente D1' ? 'selected' : ''; ?>>Patente D1</option>
                            <option value="Rinnovo" <?php echo $tipo_pratica == 'Rinnovo' ? 'selected' : ''; ?>>Rinnovo</option>
                            <option value="Duplicato" <?php echo $tipo_pratica == 'Duplicato' ? 'selected' : ''; ?>>Duplicato</option>
                            <option value="Altro" <?php echo $tipo_pratica == 'Altro' ? 'selected' : ''; ?>>Altro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Metodo Pagamento</label>
                        <select name="metodo_pagamento" class="form-select">
                            <option value="">Tutti</option>
                            <option value="Contanti" <?php echo $metodo_pagamento == 'Contanti' ? 'selected' : ''; ?>>Contanti</option>
                            <option value="POS" <?php echo $metodo_pagamento == 'POS' ? 'selected' : ''; ?>>POS</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Aggiorna Report</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- KPI Principali -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card stat-card-success">
                    <div class="card-body">
                        <h6 class="text-muted">Totale Entrate</h6>
                        <h3 class="text-success mb-0"><?php echo formatMoney($report['totale_entrate']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card stat-card-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Totale Uscite</h6>
                        <h3 class="text-danger mb-0"><?php echo formatMoney($report['totale_uscite']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card stat-card-<?php echo $report['saldo'] >= 0 ? 'gold' : 'danger'; ?>">
                    <div class="card-body">
                        <h6 class="text-muted">Saldo</h6>
                        <h3 class="mb-0 <?php echo $report['saldo'] >= 0 ? 'text-gold' : 'text-danger'; ?>">
                            <?php echo formatMoney($report['saldo']); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dettaglio Entrate e Uscite -->
        <div class="row">
            
            <!-- Entrate -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-up-circle"></i> Entrate</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($report['entrate'])): ?>
                            <p class="text-muted">Nessuna entrata nel periodo selezionato</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Metodo</th>
                                        <th>Transazioni</th>
                                        <th class="text-end">Totale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($report['entrate'] as $entrata): ?>
                                        <tr>
                                            <td><span class="badge bg-info"><?php echo $entrata['metodo_pagamento']; ?></span></td>
                                            <td><?php echo $entrata['numero_transazioni']; ?></td>
                                            <td class="text-end text-success">
                                                <strong><?php echo formatMoney($entrata['totale']); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-active">
                                    <tr>
                                        <th colspan="2">Totale</th>
                                        <th class="text-end text-success">
                                            <?php echo formatMoney($report['totale_entrate']); ?>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Uscite -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-down-circle"></i> Uscite</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($report['uscite'])): ?>
                            <p class="text-muted">Nessuna uscita nel periodo selezionato</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Numero Spese</th>
                                        <th class="text-end">Totale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($report['uscite'] as $uscita): ?>
                                        <tr>
                                            <td><span class="badge bg-dark"><?php echo $uscita['categoria']; ?></span></td>
                                            <td><?php echo $uscita['numero_spese']; ?></td>
                                            <td class="text-end text-danger">
                                                <strong><?php echo formatMoney($uscita['totale']); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-active">
                                    <tr>
                                        <th colspan="2">Totale</th>
                                        <th class="text-end text-danger">
                                            <?php echo formatMoney($report['totale_uscite']); ?>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Pratiche per Tipo -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Pratiche per Tipo</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($pratiche_per_tipo)): ?>
                            <p class="text-muted">Nessuna pratica nel periodo selezionato</p>
                        <?php else: ?>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo Pratica</th>
                                        <th class="text-end">Numero</th>
                                        <th class="text-end">Percentuale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totale_pratiche = array_sum(array_column($pratiche_per_tipo, 'count'));
                                    foreach($pratiche_per_tipo as $tipo): 
                                        $percentuale = ($tipo['count'] / $totale_pratiche) * 100;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tipo['tipo_pratica']); ?></td>
                                            <td class="text-end"><strong><?php echo $tipo['count']; ?></strong></td>
                                            <td class="text-end">
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                         style="width: <?php echo $percentuale; ?>%">
                                                        <?php echo number_format($percentuale, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grafico Mensile -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Entrate vs Uscite (Mensile)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartMensile" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pulsanti Export -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Stampa Report
                </button>
                <a class="btn btn-outline-success" href="report_export.php?type=excel&anno=<?php echo $anno; ?><?php echo $mese ? '&mese=' . $mese : ''; ?><?php echo $tipo_pratica ? '&tipo_pratica=' . urlencode($tipo_pratica) : ''; ?><?php echo $metodo_pagamento ? '&metodo_pagamento=' . urlencode($metodo_pagamento) : ''; ?>">
                    <i class="bi bi-file-earmark-excel"></i> Esporta Excel
                </a>
                <a class="btn btn-outline-danger" href="report_export.php?type=pdf&anno=<?php echo $anno; ?><?php echo $mese ? '&mese=' . $mese : ''; ?><?php echo $tipo_pratica ? '&tipo_pratica=' . urlencode($tipo_pratica) : ''; ?><?php echo $metodo_pagamento ? '&metodo_pagamento=' . urlencode($metodo_pagamento) : ''; ?>">
                    <i class="bi bi-file-earmark-pdf"></i> Esporta PDF
                </a>
                <a class="btn btn-outline-secondary" href="report_export.php?type=csv&anno=<?php echo $anno; ?><?php echo $mese ? '&mese=' . $mese : ''; ?><?php echo $tipo_pratica ? '&tipo_pratica=' . urlencode($tipo_pratica) : ''; ?><?php echo $metodo_pagamento ? '&metodo_pagamento=' . urlencode($metodo_pagamento) : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i> Esporta CSV
                </a>
            </div>
        </div>
        
    </div>
    
</div>

<script>
const entrate = <?php echo json_encode(array_values($entrateMensili)); ?>;
const uscite = <?php echo json_encode(array_values($usciteMensili)); ?>;
const mesi = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];

const canvas = document.getElementById('chartMensile');
if (canvas) {
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.parentElement.clientWidth;
    const h = canvas.height;

    const maxVal = Math.max(...entrate, ...uscite, 1);
    const padding = 40;
    const barWidth = (w - padding * 2) / (mesi.length * 2);

    ctx.clearRect(0,0,w,h);
    ctx.font = '12px sans-serif';
    ctx.fillStyle = '#666';

    // assi
    ctx.beginPath();
    ctx.moveTo(padding, 10);
    ctx.lineTo(padding, h - 30);
    ctx.lineTo(w - 10, h - 30);
    ctx.strokeStyle = '#ccc';
    ctx.stroke();

    mesi.forEach((m, i) => {
        const x = padding + i * barWidth * 2;
        const e = entrate[i] || 0;
        const u = uscite[i] || 0;
        const eh = (e / maxVal) * (h - 60);
        const uh = (u / maxVal) * (h - 60);

        // entrate
        ctx.fillStyle = '#28a745';
        ctx.fillRect(x, (h - 30) - eh, barWidth - 4, eh);

        // uscite
        ctx.fillStyle = '#dc3545';
        ctx.fillRect(x + barWidth, (h - 30) - uh, barWidth - 4, uh);

        // label mese
        ctx.fillStyle = '#666';
        ctx.fillText(m, x + 4, h - 12);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
