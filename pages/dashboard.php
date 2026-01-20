<?php
/**
 * Dashboard - Pagina principale con statistiche
 */
require_once __DIR__ . '/../includes/header.php';

// Ottieni statistiche
$anno_corrente = date('Y');
$stats = getStatisticheDashboard($anno_corrente);
$currentUser = currentUser();

// Paginazione dashboard
$perPageDashboard = 5;
function dashboardPageLink($param, $page) {
    $params = $_GET;
    $params[$param] = $page;
    return '?' . http_build_query($params);
}
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
                <h1 class="h3">Dashboard</h1>
                <?php if ($currentUser): ?>
                    <div class="d-flex align-items-center gap-2 fw-semibold text-primary">
                        <i class="bi bi-hand-thumbs-up"></i>
                        <span>Benvenuto, <?php echo htmlspecialchars($currentUser['username']); ?>.</span>
                    </div>
                <?php endif; ?>
                <p class="text-muted">Anno: <?php echo $anno_corrente; ?></p>
            </div>
        </div>
        
        <!-- KPI Cards -->
        <div class="row g-4 mb-4">
            
            <!-- Clienti -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted mb-2">Totale Clienti</h6>
                                <h2 class="card-title mb-0"><?php echo $stats['totale_clienti']; ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pratiche Totali -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted mb-2">Pratiche <?php echo $anno_corrente; ?></h6>
                                <h2 class="card-title mb-0"><?php echo $stats['totale_pratiche']; ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Entrate -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted mb-2">Entrate <?php echo $anno_corrente; ?></h6>
                                <h2 class="card-title mb-0"><?php echo formatMoney($stats['entrate_anno']); ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Saldo -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card stat-card-<?php echo $stats['saldo_anno'] >= 0 ? 'gold' : 'danger'; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted mb-2">Saldo <?php echo $anno_corrente; ?></h6>
                                <h2 class="card-title mb-0"><?php echo formatMoney($stats['saldo_anno']); ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-currency-euro"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Stato Pratiche -->
        <div class="row g-4 mb-4">
            
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Aperte</h5>
                        <h3 class="text-info"><?php echo $stats['pratiche_aperte']; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">In Corso</h5>
                        <h3 class="text-primary"><?php echo $stats['pratiche_in_corso']; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Completate</h5>
                        <h3 class="text-success"><?php echo $stats['pratiche_completate']; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Uscite</h5>
                        <h3 class="text-danger"><?php echo formatMoney($stats['uscite_anno']); ?></h3>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Ultimi Movimenti -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Ultime Pratiche</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $ultime_pratiche_all = getPratiche(['anno' => $anno_corrente]);
                        $page_pratiche = max(1, (int)($_GET['p_pratiche'] ?? 1));
                        $total_pratiche = count($ultime_pratiche_all);
                        $pages_pratiche = (int)ceil($total_pratiche / $perPageDashboard);
                        $offset_pratiche = ($page_pratiche - 1) * $perPageDashboard;
                        $ultime_pratiche = array_slice($ultime_pratiche_all, $offset_pratiche, $perPageDashboard);
                        if(empty($ultime_pratiche)): ?>
                            <p class="text-muted">Nessuna pratica presente</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($ultime_pratiche as $pratica): ?>
                                    <a href="pratica_dettaglio.php?id=<?php echo $pratica['id']; ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($pratica['cliente_nome']); ?></h6>
                                            <?php echo getStatoPraticaBadge($pratica['stato']); ?>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($pratica['tipo_pratica']); ?></p>
                                        <small class="text-muted"><?php echo formatDate($pratica['data_apertura']); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <?php if($pages_pratiche > 1): ?>
                                <nav class="mt-3">
                                    <ul class="pagination pagination-sm justify-content-end mb-0">
                                        <li class="page-item <?php echo $page_pratiche <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_pratiche', $page_pratiche - 1)); ?>">&laquo;</a>
                                        </li>
                                        <?php for($p=1;$p<=$pages_pratiche;$p++): ?>
                                            <li class="page-item <?php echo $p === $page_pratiche ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_pratiche', $p)); ?>"><?php echo $p; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page_pratiche >= $pages_pratiche ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_pratiche', $page_pratiche + 1)); ?>">&raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Ultimi Pagamenti</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $ultimi_pagamenti_all = getPagamenti(['anno' => $anno_corrente]);
                        $page_pagamenti = max(1, (int)($_GET['p_pagamenti'] ?? 1));
                        $total_pagamenti = count($ultimi_pagamenti_all);
                        $pages_pagamenti = (int)ceil($total_pagamenti / $perPageDashboard);
                        $offset_pagamenti = ($page_pagamenti - 1) * $perPageDashboard;
                        $ultimi_pagamenti = array_slice($ultimi_pagamenti_all, $offset_pagamenti, $perPageDashboard);
                        if(empty($ultimi_pagamenti)): ?>
                            <p class="text-muted">Nessun pagamento presente</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($ultimi_pagamenti as $pag): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($pag['cliente_nome']); ?></h6>
                                            <strong class="text-success"><?php echo formatMoney($pag['importo']); ?></strong>
                                        </div>
                                        <p class="mb-1 small">
                                            <span class="badge bg-secondary"><?php echo $pag['tipo_pagamento']; ?></span>
                                            <span class="badge bg-info"><?php echo $pag['metodo_pagamento']; ?></span>
                                        </p>
                                        <small class="text-muted"><?php echo formatDate($pag['data_pagamento']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if($pages_pagamenti > 1): ?>
                                <nav class="mt-3">
                                    <ul class="pagination pagination-sm justify-content-end mb-0">
                                        <li class="page-item <?php echo $page_pagamenti <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_pagamenti', $page_pagamenti - 1)); ?>">&laquo;</a>
                                        </li>
                                        <?php for($p=1;$p<=$pages_pagamenti;$p++): ?>
                                            <li class="page-item <?php echo $p === $page_pagamenti ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_pagamenti', $p)); ?>"><?php echo $p; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page_pagamenti >= $pages_pagamenti ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_pagamenti', $page_pagamenti + 1)); ?>">&raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guide imminenti -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Guide imminenti (7 giorni)</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $upcoming_all = getUpcomingGuide(7);
                        $page_guide = max(1, (int)($_GET['p_guide'] ?? 1));
                        $total_guide = count($upcoming_all);
                        $pages_guide = (int)ceil($total_guide / $perPageDashboard);
                        $offset_guide = ($page_guide - 1) * $perPageDashboard;
                        $upcoming = array_slice($upcoming_all, $offset_guide, $perPageDashboard);
                        ?>
                        <?php if(empty($upcoming)): ?>
                            <p class="text-muted">Nessuna guida programmata</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Orario</th>
                                            <th>Cliente</th>
                                            <th>Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($upcoming as $g): ?>
                                            <tr>
                                                <td><?php echo formatDate($g['data_guida']); ?></td>
                                                <td><?php echo substr($g['orario_inizio'],0,5) . ' - ' . substr($g['orario_fine'],0,5); ?></td>
                                                <td><?php echo htmlspecialchars($g['cliente_nome']); ?></td>
                                                <td><?php echo htmlspecialchars($g['tipo_lezione'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if($pages_guide > 1): ?>
                                <nav class="mt-3">
                                    <ul class="pagination pagination-sm justify-content-end mb-0">
                                        <li class="page-item <?php echo $page_guide <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_guide', $page_guide - 1)); ?>">&laquo;</a>
                                        </li>
                                        <?php for($p=1;$p<=$pages_guide;$p++): ?>
                                            <li class="page-item <?php echo $p === $page_guide ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_guide', $p)); ?>"><?php echo $p; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page_guide >= $pages_guide ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(dashboardPageLink('p_guide', $page_guide + 1)); ?>">&raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
