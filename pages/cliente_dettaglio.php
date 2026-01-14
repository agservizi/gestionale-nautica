<?php
/**
 * Dettaglio Cliente con storico pratiche e pagamenti
 */
require_once __DIR__ . '/../includes/header.php';

$cliente_id = $_GET['id'] ?? 0;
$cliente = getClienteById($cliente_id);

if(!$cliente) {
    header('Location: clienti.php');
    exit;
}

// Carica pratiche del cliente
$pratiche = getPratiche(['cliente_id' => $cliente_id]);

// Carica pagamenti del cliente
$pagamenti = getPagamenti(['cliente_id' => $cliente_id]);

// Carica agenda del cliente
$agenda = getAgendaGuide(['cliente_id' => $cliente_id]);

// Calcola totali
$totale_speso = array_sum(array_column($pagamenti, 'importo'));
$numero_pratiche = count($pratiche);
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Content -->
<div id="content" class="content">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="sidebarCollapseTop" class="btn btn-primary sidebar-toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <a href="clienti.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Torna ai Clienti
                </a>
            </div>
            <div class="ms-auto">
                <form class="d-flex" id="searchForm">
                    <input class="form-control me-2" type="search" placeholder="Cerca cliente..." id="searchCliente">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>
    
    <!-- Page Content -->
    <div class="container-fluid py-4">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="h3 mb-2">
                                    <?php echo htmlspecialchars($cliente['cognome'] . ' ' . $cliente['nome']); ?>
                                </h1>
                                <p class="mb-1">
                                    <?php if($cliente['telefono']): ?>
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($cliente['telefono']); ?>
                                    <?php endif; ?>
                                    <?php if($cliente['email']): ?>
                                        <span class="ms-3"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($cliente['email']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php if($cliente['note']): ?>
                                    <p class="text-muted mb-0"><small><?php echo nl2br(htmlspecialchars($cliente['note'])); ?></small></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="mb-2">
                                    <strong>Pratiche:</strong> <?php echo $numero_pratiche; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Totale Speso:</strong> <span class="text-success"><?php echo formatMoney($totale_speso); ?></span>
                                </div>
                                <small class="text-muted">Cliente dal: <?php echo formatDate($cliente['data_creazione']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="clienteTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pratiche-tab" data-bs-toggle="tab" 
                        data-bs-target="#pratiche" type="button">
                    <i class="bi bi-file-earmark-text"></i> Pratiche (<?php echo count($pratiche); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pagamenti-tab" data-bs-toggle="tab" 
                        data-bs-target="#pagamenti" type="button">
                    <i class="bi bi-credit-card"></i> Pagamenti (<?php echo count($pagamenti); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="agenda-tab" data-bs-toggle="tab" 
                        data-bs-target="#agenda" type="button">
                    <i class="bi bi-calendar3"></i> Guide (<?php echo count($agenda); ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="clienteTabsContent">
            
            <!-- Tab Pratiche -->
            <div class="tab-pane fade show active" id="pratiche">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Storico Pratiche</h5>
                        <a href="pratiche.php?cliente_id=<?php echo $cliente_id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> Nuova Pratica
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($pratiche)): ?>
                            <p class="text-muted">Nessuna pratica presente per questo cliente</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Data Apertura</th>
                                            <th>Tipo Pratica</th>
                                            <th>Stato</th>
                                            <th>Stato Economico</th>
                                            <th>Totale</th>
                                            <th>Pagato</th>
                                            <th>Residuo</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($pratiche as $pratica): ?>
                                            <tr>
                                                <td><?php echo $pratica['id']; ?></td>
                                                <td><?php echo formatDate($pratica['data_apertura']); ?></td>
                                                <td><?php echo htmlspecialchars($pratica['tipo_pratica']); ?></td>
                                                <td><?php echo getStatoPraticaBadge($pratica['stato']); ?></td>
                                                <td><?php echo getStatoEconomicoBadge($pratica); ?></td>
                                                <td><?php echo formatMoney($pratica['totale_previsto']); ?></td>
                                                <td class="text-success"><?php echo formatMoney($pratica['totale_pagato']); ?></td>
                                                <td class="<?php echo $pratica['residuo'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                                    <?php echo formatMoney($pratica['residuo']); ?>
                                                </td>
                                                <td>
                                                    <a href="pratica_dettaglio.php?id=<?php echo $pratica['id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tab Pagamenti -->
            <div class="tab-pane fade" id="pagamenti">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Storico Pagamenti</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($pagamenti)): ?>
                            <p class="text-muted">Nessun pagamento presente per questo cliente</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Pratica ID</th>
                                            <th>Tipo</th>
                                            <th>Metodo</th>
                                            <th>Importo</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($pagamenti as $pag): ?>
                                            <tr>
                                                <td><?php echo formatDate($pag['data_pagamento']); ?></td>
                                                <td>
                                                    <a href="pratica_dettaglio.php?id=<?php echo $pag['pratica_id']; ?>">
                                                        #<?php echo $pag['pratica_id']; ?>
                                                    </a>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo $pag['tipo_pagamento']; ?></span></td>
                                                <td><span class="badge bg-info"><?php echo $pag['metodo_pagamento']; ?></span></td>
                                                <td class="text-success"><strong><?php echo formatMoney($pag['importo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($pag['note'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <td colspan="4" class="text-end"><strong>Totale:</strong></td>
                                            <td class="text-success"><strong><?php echo formatMoney($totale_speso); ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tab Agenda -->
            <div class="tab-pane fade" id="agenda">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Storico Guide/Lezioni</h5>
                        <a href="agenda.php?cliente_id=<?php echo $cliente_id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> Nuova Guida
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if(empty($agenda)): ?>
                            <p class="text-muted">Nessuna guida presente per questo cliente</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Orario</th>
                                            <th>Tipo Lezione</th>
                                            <th>Pratica</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($agenda as $guida): ?>
                                            <tr>
                                                <td><?php echo formatDate($guida['data_guida']); ?></td>
                                                <td><?php echo substr($guida['orario_inizio'], 0, 5) . ' - ' . substr($guida['orario_fine'], 0, 5); ?></td>
                                                <td><?php echo htmlspecialchars($guida['tipo_lezione'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if($guida['pratica_id']): ?>
                                                        <a href="pratica_dettaglio.php?id=<?php echo $guida['pratica_id']; ?>">
                                                            #<?php echo $guida['pratica_id']; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($guida['note'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
    
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
