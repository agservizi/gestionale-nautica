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
                                            </div>
                                        </td>
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
