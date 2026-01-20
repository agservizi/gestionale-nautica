<?php
/**
 * Cliente Form - Creazione/Modifica cliente
 */
require_once __DIR__ . '/../includes/header.php';

$message = '';
$message_type = '';

$cliente = null;
$isEdit = false;

if (!empty($_GET['id'])) {
    $cliente = getClienteById((int)$_GET['id']);
    if ($cliente) {
        $isEdit = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo_pratica = trim($_POST['tipo_pratica'] ?? '');
        $codice_fiscale = strtoupper(trim($_POST['codice_fiscale'] ?? ''));

        if ($nome === '' || $cognome === '') {
            $message = 'Nome e cognome sono obbligatori.';
            $message_type = 'danger';
        } elseif ($tipo_pratica === '') {
            $message = 'Tipo pratica è obbligatorio.';
            $message_type = 'danger';
        } elseif ($codice_fiscale !== '' && !preg_match('/^[A-Z0-9]{16}$/', $codice_fiscale)) {
            $message = 'Codice fiscale non valido.';
            $message_type = 'danger';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email non valida.';
            $message_type = 'danger';
        } else {
            if ($action === 'update' && !empty($_POST['cliente_id'])) {
                $id = (int)$_POST['cliente_id'];
                updateCliente($id, $_POST);
                if (function_exists('logAudit')) {
                    logAudit('update', 'cliente', $id);
                }
                $message = 'Cliente aggiornato con successo!';
                $message_type = 'success';
                $cliente = getClienteById($id);
                $isEdit = true;
            } else {
                $id = createCliente($_POST);
                if (function_exists('logAudit')) {
                    logAudit('create', 'cliente', $id, $cognome . ' ' . $nome);
                }
                $message = 'Cliente creato con successo!';
                $message_type = 'success';
                $cliente = getClienteById($id);
                $isEdit = true;
            }
        }
    }
}

$title = $isEdit ? 'Modifica Cliente' : 'Nuovo Cliente';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div id="content" class="content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0"><?php echo $title; ?></h1>
                    <p class="text-muted mb-0">Gestisci i dati del cliente</p>
                </div>
                <a href="/pages/clienti.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Torna ai Clienti
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
                <form method="POST" id="formCliente">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="action" value="<?php echo $isEdit ? 'update' : 'create'; ?>">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id'] ?? ''; ?>">

                    <div class="mb-4">
                        <h6 class="mb-3 text-uppercase text-muted">Anagrafica</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clienteNome" class="form-label">Nome *</label>
                                <input type="text" class="form-control" id="clienteNome" name="nome" required value="<?php echo htmlspecialchars($cliente['nome'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clienteCognome" class="form-label">Cognome *</label>
                                <input type="text" class="form-control" id="clienteCognome" name="cognome" required value="<?php echo htmlspecialchars($cliente['cognome'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clienteTelefono" class="form-label">Telefono</label>
                                <input type="text" class="form-control" id="clienteTelefono" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clienteEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="clienteEmail" name="email" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clienteCodiceFiscale" class="form-label">Codice Fiscale</label>
                                <input type="text" class="form-control" id="clienteCodiceFiscale" name="codice_fiscale" maxlength="16" value="<?php echo htmlspecialchars($cliente['codice_fiscale'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clienteDataNascita" class="form-label">Nato il</label>
                                <input type="date" class="form-control" id="clienteDataNascita" name="data_nascita" value="<?php echo htmlspecialchars($cliente['data_nascita'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-uppercase text-muted">Residenza</h6>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="clienteIndirizzo" class="form-label">Indirizzo</label>
                                <input type="text" class="form-control" id="clienteIndirizzo" name="indirizzo" value="<?php echo htmlspecialchars($cliente['indirizzo'] ?? ''); ?>" list="indirizzoSuggerimenti" autocomplete="off">
                                <datalist id="indirizzoSuggerimenti"></datalist>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="clienteCitta" class="form-label">Città</label>
                                <input type="text" class="form-control" id="clienteCitta" name="citta" value="<?php echo htmlspecialchars($cliente['citta'] ?? ''); ?>" list="cittaSuggerimenti" autocomplete="off">
                                <datalist id="cittaSuggerimenti"></datalist>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-uppercase text-muted">Tipo Pratica</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clienteTipoPratica" class="form-label">Tipo Pratica *</label>
                                <select class="form-select" id="clienteTipoPratica" name="tipo_pratica" required>
                                    <option value="">-- Seleziona --</option>
                                    <?php
                                    $tipoPratica = $cliente['tipo_pratica'] ?? '';
                                    $tipi = ['Patente entro 12 miglia', 'Patente oltre 12 miglia', 'Patente D1', 'Rinnovo', 'Duplicato', 'Altro'];
                                    foreach ($tipi as $t):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $tipoPratica === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-uppercase text-muted">Patente</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="clienteNumeroPatente" class="form-label">Numero Patente</label>
                                <input type="text" class="form-control" id="clienteNumeroPatente" name="numero_patente" value="<?php echo htmlspecialchars($cliente['numero_patente'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="clienteDataConseguimento" class="form-label">Data Conseguimento</label>
                                <input type="date" class="form-control" id="clienteDataConseguimento" name="data_conseguimento_patente" value="<?php echo htmlspecialchars($cliente['data_conseguimento_patente'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="clienteDataScadenza" class="form-label">Data Scadenza</label>
                                <input type="date" class="form-control" id="clienteDataScadenza" name="data_scadenza_patente" value="<?php echo htmlspecialchars($cliente['data_scadenza_patente'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-uppercase text-muted">Iscrizione</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clienteNumeroRegistro" class="form-label">Numero Registro Iscrizione</label>
                                <input type="text" class="form-control" id="clienteNumeroRegistro" name="numero_registro_iscrizione" value="<?php echo htmlspecialchars($cliente['numero_registro_iscrizione'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clienteDataIscrizione" class="form-label">Data Iscrizione</label>
                                <input type="date" class="form-control" id="clienteDataIscrizione" name="data_iscrizione" value="<?php echo htmlspecialchars($cliente['data_iscrizione'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-uppercase text-muted">Idoneità</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="clienteOcchiali" name="occhiali" value="1" <?php echo !empty($cliente['occhiali']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="clienteOcchiali">Uso occhiali</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-uppercase text-muted">Documento</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="clienteDocumentoTipo" class="form-label">Tipo Documento</label>
                                <select class="form-select" id="clienteDocumentoTipo" name="documento_tipo">
                                    <option value="">-- Seleziona --</option>
                                    <option value="Patente" <?php echo ($cliente['documento_tipo'] ?? '') === 'Patente' ? 'selected' : ''; ?>>Patente</option>
                                    <option value="Carta d'identità" <?php echo ($cliente['documento_tipo'] ?? '') === "Carta d'identità" ? 'selected' : ''; ?>>Carta d'identità</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="clienteDocumentoEmissione" class="form-label">Data Emissione</label>
                                <input type="date" class="form-control" id="clienteDocumentoEmissione" name="documento_data_emissione" value="<?php echo htmlspecialchars($cliente['documento_data_emissione'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="clienteDocumentoScadenza" class="form-label">Data Scadenza</label>
                                <input type="date" class="form-control" id="clienteDocumentoScadenza" name="documento_data_scadenza" value="<?php echo htmlspecialchars($cliente['documento_data_scadenza'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="mb-3 text-uppercase text-muted">Note</h6>
                        <textarea class="form-control" id="clienteNote" name="note" rows="3"><?php echo htmlspecialchars($cliente['note'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salva
                        </button>
                        <a href="/pages/clienti.php" class="btn btn-outline-secondary">Annulla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
const indirizzoInput = document.getElementById('clienteIndirizzo');
const cittaInput = document.getElementById('clienteCitta');
const indirizzoDatalist = document.getElementById('indirizzoSuggerimenti');
const cittaDatalist = document.getElementById('cittaSuggerimenti');

let comuniIstat = [];
let addressSuggestions = {};

async function loadComuniIstat() {
    try {
        const res = await fetch('/pages/api/istat_comuni.php', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (Array.isArray(data.comuni)) {
            comuniIstat = data.comuni;
            if (cittaDatalist) {
                cittaDatalist.innerHTML = comuniIstat.map(c => `<option value="${c}"></option>`).join('');
            }
        }
    } catch (e) {
        comuniIstat = [];
    }
}

function normalizeCityName(name) {
    if (!name) return '';
    if (!comuniIstat.length) return name;
    const lowered = name.toLowerCase();
    const match = comuniIstat.find(c => c.toLowerCase() === lowered);
    return match || name;
}

function buildAddressLabel(item) {
    const addr = item.address || {};
    const road = addr.road || addr.pedestrian || addr.footway || '';
    const house = addr.house_number || '';
    if (road && house) {
        return `${road} ${house}`.trim();
    }
    return item.display_name || '';
}

function extractCity(item) {
    const addr = item.address || {};
    return addr.city || addr.town || addr.village || addr.municipality || addr.county || '';
}

let debounceTimer;
function debounceAddressSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runAddressSearch, 350);
}

async function runAddressSearch() {
    if (!indirizzoInput) return;
    const query = indirizzoInput.value.trim();
    if (query.length < 4) {
        if (indirizzoDatalist) indirizzoDatalist.innerHTML = '';
        addressSuggestions = {};
        return;
    }

    const searchQuery = query.replace(/\s+\d+[a-zA-Z]?$/u, '').trim();
    const effectiveQuery = searchQuery !== '' ? searchQuery : query;

    const citta = cittaInput && cittaInput.value.trim() ? `, ${cittaInput.value.trim()}` : '';
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=6&countrycodes=it&q=${encodeURIComponent(effectiveQuery + citta)}`;

    try {
        const res = await fetch(url, { headers: { 'Accept-Language': 'it' } });
        const data = await res.json();
        addressSuggestions = {};
        if (indirizzoDatalist) {
            indirizzoDatalist.innerHTML = '';
            data.forEach(item => {
                const label = buildAddressLabel(item);
                const city = normalizeCityName(extractCity(item));
                if (label) {
                    addressSuggestions[label] = city;
                    const opt = document.createElement('option');
                    opt.value = label;
                    indirizzoDatalist.appendChild(opt);
                }
            });
        }
    } catch (e) {
        addressSuggestions = {};
    }
}

function applyCityFromAddress() {
    if (!indirizzoInput || !cittaInput) return;
    const label = indirizzoInput.value.trim();
    if (label && addressSuggestions[label]) {
        cittaInput.value = addressSuggestions[label];
        return;
    }

    const baseLabel = label.replace(/\s+\d+[a-zA-Z]?$/u, '').trim();
    if (baseLabel && addressSuggestions[baseLabel]) {
        cittaInput.value = addressSuggestions[baseLabel];
    }
}

if (indirizzoInput) {
    indirizzoInput.addEventListener('input', debounceAddressSearch);
    indirizzoInput.addEventListener('change', applyCityFromAddress);
    indirizzoInput.addEventListener('blur', applyCityFromAddress);
}

loadComuniIstat();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
