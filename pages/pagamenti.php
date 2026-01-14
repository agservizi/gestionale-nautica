<?php
/**
 * Pagamenti - Visualizzazione e gestione pagamenti
 */
require_once __DIR__ . '/../includes/header.php';

// Filtri
$filters = [];
if(!empty($_GET['anno'])) $filters['anno'] = $_GET['anno'];
if(!empty($_GET['mese'])) $filters['mese'] = $_GET['mese'];
if(!empty($_GET['metodo_pagamento'])) $filters['metodo_pagamento'] = $_GET['metodo_pagamento'];

$pagamenti = getPagamenti($filters);

// Calcola totali
$totale_contanti = 0;
$totale_pos = 0;
foreach($pagamenti as $pag) {
    if($pag['metodo_pagamento'] == 'Contanti') {
        $totale_contanti += $pag['importo'];
    } else {
        $totale_pos += $pag['importo'];
    }
}
$totale_generale = $totale_contanti + $totale_pos;

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
                <h1 class="h3">Pagamenti (<?php echo count($pagamenti); ?>)</h1>
            </div>
        </div>
        
        <!-- Totali -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card stat-card-success">
                    <div class="card-body">
                        <h6 class="text-muted">Totale Generale</h6>
                        <h3 class="mb-0"><?php echo formatMoney($totale_generale); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card stat-card-info">
                    <div class="card-body">
                        <h6 class="text-muted">Contanti</h6>
                        <h3 class="mb-0"><?php echo formatMoney($totale_contanti); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card stat-card-primary">
                    <div class="card-body">
                        <h6 class="text-muted">POS</h6>
                        <h3 class="mb-0"><?php echo formatMoney($totale_pos); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label class="form-label">Metodo</label>
                        <select name="metodo_pagamento" class="form-select">
                            <option value="">Tutti</option>
                            <option value="Contanti" <?php echo ($_GET['metodo_pagamento'] ?? '') == 'Contanti' ? 'selected' : ''; ?>>Contanti</option>
                            <option value="POS" <?php echo ($_GET['metodo_pagamento'] ?? '') == 'POS' ? 'selected' : ''; ?>>POS</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtra</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabella Pagamenti -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Pratica</th>
                                <th>Tipo Pagamento</th>
                                <th>Metodo</th>
                                <th>Importo</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pagamenti)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Nessun pagamento trovato</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($pagamenti as $pag): ?>
                                    <tr>
                                        <td><?php echo formatDate($pag['data_pagamento']); ?></td>
                                        <td>
                                            <a href="cliente_dettaglio.php?id=<?php echo $pag['cliente_id']; ?>">
                                                <?php echo htmlspecialchars($pag['cliente_nome']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="pratica_dettaglio.php?id=<?php echo $pag['pratica_id']; ?>">
                                                #<?php echo $pag['pratica_id']; ?>
                                            </a>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($pag['tipo_pratica']); ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo $pag['tipo_pagamento']; ?></span></td>
                                        <td><span class="badge bg-info"><?php echo $pag['metodo_pagamento']; ?></span></td>
                                        <td class="text-success"><strong><?php echo formatMoney($pag['importo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($pag['note'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
