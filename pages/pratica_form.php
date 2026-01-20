<?php
/**
 * Pratica Form - Creazione pratica
 */
require_once __DIR__ . '/../includes/header.php';

$message = '';
$message_type = '';
$createdId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
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
    } elseif ($action === 'create_cliente_quick') {
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo_pratica = trim($_POST['tipo_pratica'] ?? '');
        $codice_fiscale = strtoupper(trim($_POST['codice_fiscale'] ?? ''));
        if ($nome === '' || $cognome === '') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Nome e cognome sono obbligatori.']);
            exit;
        }
        if ($tipo_pratica === '') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Tipo pratica è obbligatorio.']);
            exit;
        }
        if ($codice_fiscale !== '' && !preg_match('/^[A-Z0-9]{16}$/', $codice_fiscale)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Codice fiscale non valido.']);
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
        if (function_exists('logAudit')) {
            logAudit('create', 'cliente', $id, $cognome . ' ' . $nome);
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'id' => $id,
            'label' => $cognome . ' ' . $nome,
            'tipo_pratica' => $tipo_pratica
        ]);
        exit;
    } elseif ($action === 'create') {
        if (empty($_POST['cliente_id']) || empty($_POST['data_apertura'])) {
            $message = 'Cliente e data sono obbligatori.';
            $message_type = 'danger';
        } else {
            $createdId = createPratica($_POST);
            $tipoPraticaLog = $_POST['tipo_pratica'] ?? null;
            if ($tipoPraticaLog === null || $tipoPraticaLog === '') {
                $clienteLog = getClienteById($_POST['cliente_id']);
                $tipoPraticaLog = $clienteLog['tipo_pratica'] ?? null;
            }
            if (function_exists('logAudit')) {
                logAudit('create', 'pratica', $createdId, $tipoPraticaLog);
            }
            if (!empty($_POST['pagamento_importo']) && $_POST['pagamento_importo'] > 0) {
                createPagamento([
                    'pratica_id' => $createdId,
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
        }
    }
}

$clienti = getClienti();
$altroSottocategorie = getAltroSottocategorie();
$selectedClienteId = !empty($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : null;
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div id="content" class="content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Nuova Pratica</h1>
                    <p class="text-muted mb-0">Crea una nuova pratica cliente</p>
                </div>
                <a href="/pages/pratiche.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Torna alle Pratiche
                </a>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" id="formPratica">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="action" value="create">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente *</label>
                            <select name="cliente_id" class="form-select" required id="selectCliente">
                                <option value="">-- Seleziona Cliente --</option>
                                <?php foreach($clienti as $cli): ?>
                                    <option value="<?php echo $cli['id']; ?>" data-tipo-pratica="<?php echo htmlspecialchars($cli['tipo_pratica'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedClienteId === (int)$cli['id'] ? 'selected' : ''; ?>>
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
                            <input type="date" name="data_apertura" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
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
                            <label class="form-label">Tipo Pratica</label>
                            <input type="text" class="form-control" id="tipoPraticaDisplay" placeholder="Seleziona un cliente" readonly>
                            <input type="hidden" name="tipo_pratica" id="tipoPratica">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Totale Previsto</label>
                            <input type="number" name="totale_previsto" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>

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
                            <input type="number" name="pagamento_importo" class="form-control" step="0.01" min="0" value="0">
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

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Crea Pratica</button>
                        <a href="/pages/pratiche.php" class="btn btn-outline-secondary">Annulla</a>
                        <?php if($createdId): ?>
                            <a href="/pages/pratica_dettaglio.php?id=<?php echo $createdId; ?>" class="btn btn-success">Apri Dettaglio</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
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
                        <label class="form-label">Codice Fiscale</label>
                        <input type="text" class="form-control" name="codice_fiscale" id="clienteQuickCodiceFiscale" maxlength="16">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo Pratica *</label>
                        <select class="form-select" name="tipo_pratica" required>
                            <option value="">-- Seleziona --</option>
                            <option value="Patente entro 12 miglia">Patente entro 12 miglia</option>
                            <option value="Patente oltre 12 miglia">Patente oltre 12 miglia</option>
                            <option value="Patente D1">Patente D1</option>
                            <option value="Rinnovo">Rinnovo</option>
                            <option value="Duplicato">Duplicato</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Numero Patente</label>
                            <input type="text" class="form-control" name="numero_patente">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Conseguimento</label>
                            <input type="date" class="form-control" name="data_conseguimento_patente" id="clienteQuickDataConseguimento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Scadenza</label>
                            <input type="date" class="form-control" name="data_scadenza_patente" id="clienteQuickDataScadenza">
                        </div>
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
        const response = await fetch('pratica_form.php', {
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
        option.setAttribute('data-tipo-pratica', data.tipo_pratica || '');
        select.appendChild(option);
        select.value = data.id;
        select.dispatchEvent(new Event('change'));

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
