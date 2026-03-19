<?php
/**
 * Dashboard - Pagina principale con statistiche
 */
require_once __DIR__ . '/../includes/header.php';

// Ottieni statistiche
$anno_corrente = date('Y');
$anni = range(getAppYearStart(), date('Y') + 1);
$periodo = $_GET['periodo'] ?? 'auto';
$anno_selezionato = (int)($_GET['anno'] ?? $anno_corrente);
$stats = getStatisticheDashboard($anno_selezionato);
$useAllTime = false;

if ($periodo === 'tutti') {
    $stats = getStatisticheDashboardAllTime();
    $useAllTime = true;
} elseif ($periodo === 'auto') {
    if ((int)$stats['totale_pratiche'] === 0 && (float)$stats['entrate_anno'] == 0.0 && (float)$stats['uscite_anno'] == 0.0) {
        $statsAll = getStatisticheDashboardAllTime();
        if ((int)$statsAll['totale_pratiche'] > 0 || (float)$statsAll['entrate_anno'] != 0.0 || (float)$statsAll['uscite_anno'] != 0.0) {
            $stats = $statsAll;
            $useAllTime = true;
        }
    }
}

$periodoLabel = $useAllTime ? 'tutti gli anni' : $anno_selezionato;
$currentUser = currentUser();
$todayGuides = (int)($notifications['today_guides'] ?? 0);
$tomorrowGuides = (int)($notifications['tomorrow_guides'] ?? 0);
$scoperte = (int)($notifications['pratiche_scoperte'] ?? 0);
$documentiInScadenza = (int)($notifications['documenti_in_scadenza'] ?? 0);
$operationalAlerts = getOperationalAlerts(6);

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
        
        <section class="page-hero">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
                <div>
                    <div class="page-hero__eyebrow">Panoramica operativa</div>
                    <h1 class="page-hero__title">Dashboard</h1>
                    <?php if ($currentUser): ?>
                        <p class="page-hero__subtitle">Ciao <?php echo htmlspecialchars($currentUser['username']); ?>, qui trovi il quadro rapido dell'operativita e le prossime azioni da seguire.</p>
                    <?php else: ?>
                        <p class="page-hero__subtitle">Controlla numeri chiave, pratiche recenti e attivita imminenti in un solo colpo d'occhio.</p>
                    <?php endif; ?>
                    <div class="page-hero__meta">
                        <span class="page-meta-pill"><i class="bi bi-calendar3"></i> Periodo: <?php echo htmlspecialchars((string)$periodoLabel); ?></span>
                        <span class="page-meta-pill"><i class="bi bi-credit-card"></i> Saldo: <?php echo formatMoney($stats['saldo_anno']); ?></span>
                        <span class="page-meta-pill"><i class="bi bi-exclamation-triangle"></i> Scoperte: <?php echo $scoperte; ?></span>
                        <span class="page-meta-pill"><i class="bi bi-card-text"></i> Documenti in scadenza: <?php echo $documentiInScadenza; ?></span>
                    </div>
                </div>
                <div class="quick-actions align-self-start">
                    <a href="/pages/pratica_form.php" class="btn btn-light">
                        <i class="bi bi-plus-lg"></i> Nuova pratica
                    </a>
                    <a href="/pages/cliente_form.php" class="btn btn-outline-light">
                        <i class="bi bi-people"></i> Nuovo cliente
                    </a>
                    <a href="/pages/agenda.php" class="btn btn-outline-light">
                        <i class="bi bi-calendar3"></i> Agenda
                    </a>
                </div>
            </div>
        </section>

        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-strip">
                    <div class="stats-strip__item">
                        <span class="stats-strip__label">Guide oggi</span>
                        <span class="stats-strip__value"><?php echo $todayGuides; ?></span>
                    </div>
                    <div class="stats-strip__item">
                        <span class="stats-strip__label">Guide domani</span>
                        <span class="stats-strip__value"><?php echo $tomorrowGuides; ?></span>
                    </div>
                    <div class="stats-strip__item">
                        <span class="stats-strip__label">Pratiche aperte</span>
                        <span class="stats-strip__value"><?php echo (int)$stats['pratiche_aperte']; ?></span>
                    </div>
                    <div class="stats-strip__item">
                        <span class="stats-strip__label">Entrate periodo</span>
                        <span class="stats-strip__value"><?php echo formatMoney($stats['entrate_anno']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-4">
            <div class="col-xl-7" id="alert-operativi">
                <div class="card section-card h-100">
                    <div class="card-body">
                        <div class="section-card__header">
                            <div>
                                <div class="section-card__eyebrow">Filtro periodo</div>
                                <h2 class="section-card__title">Cambia rapidamente la lettura dei dati</h2>
                                <p class="section-card__hint">Usa il periodo automatico per lavorare senza dover cambiare anno quando non ci sono dati nel periodo selezionato.</p>
                            </div>
                        </div>
                        <form method="GET" class="row g-2 align-items-end mt-2">
                            <div class="col-sm-4 col-md-3">
                                <label class="form-label">Periodo</label>
                                <select name="periodo" class="form-select">
                                    <option value="auto" <?php echo $periodo === 'auto' ? 'selected' : ''; ?>>Automatico</option>
                                    <option value="anno" <?php echo $periodo === 'anno' ? 'selected' : ''; ?>>Anno</option>
                                    <option value="tutti" <?php echo $periodo === 'tutti' ? 'selected' : ''; ?>>Tutti gli anni</option>
                                </select>
                            </div>
                            <div class="col-sm-4 col-md-3">
                                <label class="form-label">Anno</label>
                                <select name="anno" class="form-select">
                                    <?php foreach ($anni as $anno): ?>
                                        <option value="<?php echo $anno; ?>" <?php echo $anno_selezionato == $anno ? 'selected' : ''; ?>>
                                            <?php echo $anno; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-4 col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Applica</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card section-card h-100">
                    <div class="card-body">
                        <div class="section-card__header">
                            <div>
                                <div class="section-card__eyebrow">Focus</div>
                                <h2 class="section-card__title">Da presidiare oggi</h2>
                                <p class="section-card__hint">Azioni rapide sulle aree che richiedono piu attenzione.</p>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="/pages/pratiche.php" class="btn btn-outline-secondary text-start">
                                <i class="bi bi-file-earmark-text"></i> Verifica pratiche e residui
                            </a>
                            <a href="/pages/agenda.php" class="btn btn-outline-secondary text-start">
                                <i class="bi bi-calendar3"></i> Controlla le guide in calendario
                            </a>
                            <a href="/pages/pagamenti.php" class="btn btn-outline-secondary text-start">
                                <i class="bi bi-credit-card"></i> Registra incassi e saldi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-4">
            <div class="col-xl-7">
                <div class="card section-card h-100">
                    <div class="card-body">
                        <div class="section-card__header">
                            <div>
                                <div class="section-card__eyebrow">Alert operativi</div>
                                <h2 class="section-card__title">Scadenze e priorita da presidiare</h2>
                                <p class="section-card__hint">Un punto unico per vedere guide vicine, residui aperti e documenti da controllare.</p>
                            </div>
                        </div>
                        <?php if (empty($operationalAlerts)): ?>
                            <p class="text-muted mb-0">Nessun alert aperto in questo momento.</p>
                        <?php else: ?>
                            <div class="insight-list">
                                <?php foreach ($operationalAlerts as $alert): ?>
                                    <a href="<?php echo htmlspecialchars($alert['href']); ?>" class="insight-item insight-item--<?php echo htmlspecialchars($alert['tone']); ?>">
                                        <span class="insight-item__icon"><i class="bi bi-<?php echo htmlspecialchars($alert['icon']); ?>"></i></span>
                                        <span class="insight-item__body">
                                            <strong><?php echo htmlspecialchars($alert['title']); ?></strong>
                                            <small><?php echo htmlspecialchars($alert['description']); ?></small>
                                        </span>
                                        <span class="insight-item__meta"><?php echo htmlspecialchars($alert['meta']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card section-card h-100">
                    <div class="card-body">
                        <div class="section-card__header">
                            <div>
                                <div class="section-card__eyebrow">Routine consigliata</div>
                                <h2 class="section-card__title">Ordine ideale di lavoro</h2>
                                <p class="section-card__hint">Un flusso semplice per trasformare dashboard e dati in azioni concrete.</p>
                            </div>
                        </div>
                        <div class="workflow-mini">
                            <div class="workflow-mini__step">
                                <span>1</span>
                                <div>
                                    <strong>Apri gli alert urgenti</strong>
                                    <small>Scadenze e guide vicine prima di tutto.</small>
                                </div>
                            </div>
                            <div class="workflow-mini__step">
                                <span>2</span>
                                <div>
                                    <strong>Chiudi i residui</strong>
                                    <small>Registra incassi o pianifica follow-up.</small>
                                </div>
                            </div>
                            <div class="workflow-mini__step">
                                <span>3</span>
                                <div>
                                    <strong>Completa le pratiche bloccate</strong>
                                    <small>Controlla allegati, scadenze e stato pratica.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                                <h6 class="card-subtitle text-muted mb-2">Pratiche <?php echo $periodoLabel; ?></h6>
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
                                <h6 class="card-subtitle text-muted mb-2">Entrate <?php echo $periodoLabel; ?></h6>
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
                                <h6 class="card-subtitle text-muted mb-2">Saldo <?php echo $periodoLabel; ?></h6>
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
                        $ultime_pratiche_all = $useAllTime ? getPratiche() : getPratiche(['anno' => $anno_selezionato]);
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
                        $ultimi_pagamenti_all = $useAllTime ? getPagamenti() : getPagamenti(['anno' => $anno_selezionato]);
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
