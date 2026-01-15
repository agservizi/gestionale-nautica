<?php
/**
 * Spese - Gestione uscite
 */
require_once __DIR__ . '/../includes/header.php';

// Gestione POST
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } elseif(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                if (empty($_POST['data_spesa']) || empty($_POST['categoria']) || empty($_POST['importo'])) {
                    $message = 'Data, categoria e importo sono obbligatori.';
                    $message_type = 'danger';
                    break;
                }
                createSpesa($_POST);
                logAudit('create', 'spesa', null, $_POST['categoria'] ?? null);
                $message = 'Spesa registrata con successo!';
                $message_type = 'success';
                break;
            case 'delete':
                deleteSpesa($_POST['id']);
                logAudit('delete', 'spesa', $_POST['id']);
                $message = 'Spesa eliminata con successo!';
                $message_type = 'success';
                break;
        }
    }
}

// Filtri
$filters = [];
if(!empty($_GET['anno'])) $filters['anno'] = $_GET['anno'];
if(!empty($_GET['mese'])) $filters['mese'] = $_GET['mese'];
if(!empty($_GET['categoria'])) $filters['categoria'] = $_GET['categoria'];

$spese = getSpese($filters);

// Calcola totali per categoria
$totali_categoria = [];
foreach($spese as $spesa) {
    $cat = $spesa['categoria'];
    if(!isset($totali_categoria[$cat])) {
        $totali_categoria[$cat] = 0;
    }
    $totali_categoria[$cat] += $spesa['importo'];
}
$totale_generale = array_sum($totali_categoria);

$anni = range(APP_YEAR_START, date('Y') + 1);
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
                <h1 class="h3">Spese (<?php echo count($spese); ?>)</h1>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalSpesa">
                    <i class="bi bi-plus-lg"></i> Nuova Spesa
                </button>
            </div>
        </div>
        
        <!-- Totali per Categoria -->
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="card stat-card stat-card-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Totale Spese</h6>
                        <h3 class="mb-0 text-danger"><?php echo formatMoney($totale_generale); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <?php foreach($totali_categoria as $cat => $tot): ?>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6 class="card-title"><?php echo $cat; ?></h6>
                            <h5 class="text-danger"><?php echo formatMoney($tot); ?></h5>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Categoria</label>
                        <select name="categoria" class="form-select">
                            <option value="">Tutte</option>
                            <option value="Vincenzo" <?php echo ($_GET['categoria'] ?? '') == 'Vincenzo' ? 'selected' : ''; ?>>Vincenzo</option>
                            <option value="Luigi" <?php echo ($_GET['categoria'] ?? '') == 'Luigi' ? 'selected' : ''; ?>>Luigi</option>
                            <option value="Affitto barca" <?php echo ($_GET['categoria'] ?? '') == 'Affitto barca' ? 'selected' : ''; ?>>Affitto barca</option>
                            <option value="Benzina" <?php echo ($_GET['categoria'] ?? '') == 'Benzina' ? 'selected' : ''; ?>>Benzina</option>
                            <option value="Altro" <?php echo ($_GET['categoria'] ?? '') == 'Altro' ? 'selected' : ''; ?>>Altro</option>
                        </select>
                    </div>
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
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtra</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabella Spese -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Categoria</th>
                                <th>Descrizione</th>
                                <th>Importo</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($spese)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nessuna spesa trovata</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($spese as $spesa): ?>
                                    <tr>
                                        <td><?php echo formatDate($spesa['data_spesa']); ?></td>
                                        <td>
                                            <span class="badge bg-dark">
                                                <?php echo $spesa['categoria']; ?>
                                                <?php if($spesa['categoria'] == 'Altro' && $spesa['categoria_altro']): ?>
                                                    - <?php echo htmlspecialchars($spesa['categoria_altro']); ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($spesa['descrizione'] ?? '-'); ?></td>
                                        <td class="text-danger"><strong><?php echo formatMoney($spesa['importo']); ?></strong></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminare questa spesa?')">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $spesa['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Elimina">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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

<!-- Modal Nuova Spesa -->
<div class="modal fade" id="modalSpesa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Nuova Spesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Data *</label>
                        <input type="date" name="data_spesa" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Categoria *</label>
                        <select name="categoria" class="form-select" required id="categoriaSpesa">
                            <option value="">-- Seleziona --</option>
                            <option value="Vincenzo">Vincenzo</option>
                            <option value="Luigi">Luigi</option>
                            <option value="Affitto barca">Affitto barca</option>
                            <option value="Benzina">Benzina</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="categoriaAltroDiv" style="display: none;">
                        <label class="form-label">Specifica Altra Categoria</label>
                        <input type="text" name="categoria_altro" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Importo *</label>
                        <input type="number" name="importo" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea name="descrizione" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">Registra Spesa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('categoriaSpesa').addEventListener('change', function() {
    const altroDiv = document.getElementById('categoriaAltroDiv');
    if(this.value === 'Altro') {
        altroDiv.style.display = 'block';
    } else {
        altroDiv.style.display = 'none';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
