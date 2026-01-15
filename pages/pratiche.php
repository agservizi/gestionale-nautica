<?php
/**
 * Pratiche - Gestione pratiche con calendario
 */
require_once __DIR__ . '/../includes/header.php';

// Gestione richieste POST
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if(!csrf_validate($_POST['csrf_token'] ?? '')) {
        if ($action === 'create_cliente_quick') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Sessione scaduta. Riprova.']);
            exit;
        }
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } elseif($action === 'create_cliente_quick') {
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($nome === '' || $cognome === '') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Nome e cognome sono obbligatori.']);
            exit;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Email non valida.']);
            exit;
        }
        $id = createCliente($_POST);
        logAudit('create', 'cliente', $id, $cognome . ' ' . $nome);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'id' => $id,
            'label' => $cognome . ' ' . $nome
        ]);
        exit;
    } elseif(isset($_POST['action'])) {
        switch($action) {
            case 'create':
                if (empty($_POST['cliente_id']) || empty($_POST['tipo_pratica']) || empty($_POST['data_apertura'])) {
                    $message = 'Cliente, tipo pratica e data sono obbligatori.';
                    $message_type = 'danger';
                    break;
                }
                $id = createPratica($_POST);
                logAudit('create', 'pratica', $id, $_POST['tipo_pratica'] ?? null);
                // Se c'è un pagamento immediato, registralo
                if(!empty($_POST['pagamento_importo']) && $_POST['pagamento_importo'] > 0) {
                    createPagamento([
                        'pratica_id' => $id,
                        'cliente_id' => $_POST['cliente_id'],
                        'tipo_pagamento' => $_POST['pagamento_tipo'],
                        'importo' => $_POST['pagamento_importo'],
                        'metodo_pagamento' => $_POST['pagamento_metodo'],
                        'data_pagamento' => $_POST['data_apertura'],
                        'note' => $_POST['pagamento_note'] ?? null
                    ]);
                }
                $message = 'Pratica creata con successo!';
                $message_type = 'success';
                break;
            case 'update':
                if (empty($_POST['tipo_pratica']) || empty($_POST['data_apertura'])) {
                    $message = 'Tipo pratica e data sono obbligatori.';
                    $message_type = 'danger';
                    break;
                }
                updatePratica($_POST['id'], $_POST);
                logAudit('update', 'pratica', $_POST['id']);
                $message = 'Pratica aggiornata con successo!';
                $message_type = 'success';
                break;
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
$clienti = getClienti();
$altroSottocategorie = getAltroSottocategorie();

// Anni disponibili
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
                <h1 class="h3">Pratiche (<?php echo count($pratiche); ?>)</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPratica">
                    <i class="bi bi-plus-lg"></i> Nuova Pratica
                </button>
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

<!-- Modal Nuova Pratica -->
<div class="modal fade" id="modalPratica" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formPratica">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Nuova Pratica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente *</label>
                            <select name="cliente_id" class="form-select" required id="selectCliente">
                                <option value="">-- Seleziona Cliente --</option>
                                <?php foreach($clienti as $cli): ?>
                                    <option value="<?php echo $cli['id']; ?>">
                                        <?php echo htmlspecialchars($cli['cognome'] . ' ' . $cli['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#modalClienteQuick">
                                    Crea nuovo cliente
                                </button>
                            </small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data Apertura *</label>
                            <input type="date" name="data_apertura" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stato *</label>
                            <select name="stato" class="form-select" required>
                                <option value="Aperta">Aperta</option>
                                <option value="In corso">In corso</option>
                                <option value="Completata">Completata</option>
                                <option value="Annullata">Annullata</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Tipo Pratica *</label>
                            <select name="tipo_pratica" class="form-select" required id="tipoPratica">
                                <option value="">-- Seleziona --</option>
                                <option value="Patente entro 12 miglia">Patente entro 12 miglia</option>
                                <option value="Patente oltre 12 miglia">Patente oltre 12 miglia</option>
                                <option value="Patente D1">Patente D1</option>
                                <option value="Rinnovo">Rinnovo</option>
                                <option value="Duplicato">Duplicato</option>
                                <option value="Altro">Altro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Totale Previsto</label>
                            <input type="number" name="totale_previsto" class="form-control" 
                                   step="0.01" min="0" value="0">
                        </div>
                    </div>

                    <!-- Campi dinamici: Conseguimento patente -->
                    <div id="campi_conseguimento" style="display:none;">
                        <hr>
                        <h6>Conseguimento Patente</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Data Esame</label>
                                <input type="date" name="data_esame" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Esito</label>
                                <select name="esito_esame" class="form-select">
                                    <option value="In attesa">In attesa</option>
                                    <option value="Superato">Superato</option>
                                    <option value="Non superato">Non superato</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data Conseguimento</label>
                                <input type="date" name="data_conseguimento" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Numero Patente</label>
                                <input type="text" name="numero_patente" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Note/Allegati</label>
                                <input type="text" name="allegati" class="form-control" placeholder="Es. allegati consegnati">
                            </div>
                        </div>
                    </div>

                    <!-- Campi dinamici: Rinnovo -->
                    <div id="campi_rinnovo" style="display:none;">
                        <hr>
                        <h6>Rinnovo</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Data Richiesta</label>
                                <input type="date" name="data_richiesta_rinnovo" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data Completamento</label>
                                <input type="date" name="data_completamento_rinnovo" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note Operative</label>
                            <textarea name="note_rinnovo" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- Campi dinamici: Duplicato -->
                    <div id="campi_duplicato" style="display:none;">
                        <hr>
                        <h6>Duplicato</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Motivo</label>
                                <select name="motivo_duplicato" class="form-select">
                                    <option value="Smarrimento">Smarrimento</option>
                                    <option value="Deterioramento">Deterioramento</option>
                                    <option value="Altro">Altro</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Dettaglio</label>
                                <input type="text" name="motivo_duplicato_dettaglio" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Data Richiesta</label>
                                <input type="date" name="data_richiesta_duplicato" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data Chiusura</label>
                                <input type="date" name="data_chiusura_duplicato" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Campi dinamici: Altro -->
                    <div id="campi_altro" style="display:none;">
                        <hr>
                        <h6>Altro</h6>
                        <div class="mb-3">
                            <label class="form-label">Descrizione / Sotto-categoria</label>
                            <input type="text" name="tipo_altro_dettaglio" class="form-control" list="altroSottocategorie" placeholder="Descrizione libera">
                            <datalist id="altroSottocategorie">
                                <?php foreach($altroSottocategorie as $sottocat): ?>
                                    <option value="<?php echo htmlspecialchars($sottocat); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <hr>
                    
                    <h6>Pagamento Immediato (opzionale)</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Importo</label>
                            <input type="number" name="pagamento_importo" class="form-control" 
                                   step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <select name="pagamento_tipo" class="form-select">
                                <option value="Acconto">Acconto</option>
                                <option value="Pagamento unico">Pagamento unico</option>
                                <option value="Rata">Rata</option>
                                <option value="Saldo">Saldo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Metodo</label>
                            <select name="pagamento_metodo" class="form-select">
                                <option value="Contanti">Contanti</option>
                                <option value="POS">POS</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note Pagamento</label>
                        <input type="text" name="pagamento_note" class="form-control">
                    </div>
                    
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Pratica</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nuovo Cliente (Quick) -->
<div class="modal fade" id="modalClienteQuick" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formClienteQuick">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="create_cliente_quick">
                <div class="modal-header">
                    <h5 class="modal-title">Nuovo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cognome *</label>
                        <input type="text" class="form-control" name="cognome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefono</label>
                        <input type="text" class="form-control" name="telefono">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" name="note" rows="2"></textarea>
                    </div>
                    <div class="alert alert-danger d-none" id="clienteQuickError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
document.getElementById('formClienteQuick').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const errorBox = document.getElementById('clienteQuickError');
    const submitBtn = form.querySelector('button[type="submit"]');
    errorBox.classList.add('d-none');
    submitBtn.disabled = true;

    try {
        const formData = new FormData(form);
        const response = await fetch('pratiche.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const text = await response.text();
        let data = null;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            data = null;
        }
        if (!data) {
            errorBox.textContent = text ? text.trim() : 'Risposta non valida dal server.';
            errorBox.classList.remove('d-none');
            return;
        }
        if (!data.ok) {
            errorBox.textContent = data.message || 'Errore durante il salvataggio.';
            errorBox.classList.remove('d-none');
            return;
        }

        const select = document.getElementById('selectCliente');
        const option = document.createElement('option');
        option.value = data.id;
        option.textContent = data.label;
        select.appendChild(option);
        select.value = data.id;

        const modalEl = document.getElementById('modalClienteQuick');
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.hide();
        form.reset();
    } catch (err) {
        errorBox.textContent = 'Errore di rete. Riprova.';
        errorBox.classList.remove('d-none');
    } finally {
        submitBtn.disabled = false;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
