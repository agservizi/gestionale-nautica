<?php
/**
 * Agenda Form - Nuova guida/lezione
 */
require_once __DIR__ . '/../includes/header.php';

$message = '';
$message_type = '';

$agendaWindow = getAgendaTimeWindow();
$agendaStart = $agendaWindow['start'];
$agendaEnd = $agendaWindow['end'];
$agendaDefaultEnd = date('H:i', strtotime($agendaStart . ' +2 hours'));
if ($agendaDefaultEnd > $agendaEnd) {
    $agendaDefaultEnd = $agendaEnd;
}
$agendaInstructors = getSettingsList('agenda_instructors', ['Vincenzo Scibile', 'Vincenzo Lomiento', 'Luigi Visalli']);
$agendaLessonTypes = getSettingsList('agenda_lesson_types', ['Guida pratica', 'Esame', 'Altro']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'create') {
        if (empty($_POST['cliente_id']) || empty($_POST['data_guida']) || empty($_POST['orario_inizio']) || empty($_POST['orario_fine']) || empty($_POST['istruttore']) || empty($_POST['tipo_lezione'])) {
            $message = 'Cliente, data, orari, istruttore e tipo lezione sono obbligatori.';
            $message_type = 'danger';
        } else {
            $dataGuida = new DateTime($_POST['data_guida']);
            $weekday = (int)$dataGuida->format('N');
            if ($weekday === 7) {
                $message = 'Le guide sono disponibili solo dal lunedì al sabato.';
                $message_type = 'danger';
            } else {
                $inizio = $_POST['orario_inizio'];
                $fine = $_POST['orario_fine'];
                if ($fine <= $inizio) {
                    $message = 'L\'orario di fine deve essere successivo a quello di inizio.';
                    $message_type = 'danger';
                } elseif ($inizio < $agendaStart || $fine > $agendaEnd) {
                    $message = 'Le guide sono consentite solo tra le ' . $agendaStart . ' e le ' . $agendaEnd . '.';
                    $message_type = 'danger';
                } else {
                    $id = createAgenda($_POST);
                    if (function_exists('logAudit')) {
                        logAudit('create', 'agenda', $id, $_POST['data_guida'] ?? null);
                    }
                    $redirectDate = $_POST['data_guida'] ?? date('Y-m-d');
                    header('Location: /pages/agenda.php?data=' . urlencode($redirectDate));
                    exit;
                }
            }
        }
    }
}

$data_corrente = $_GET['data'] ?? date('Y-m-d');
$clienti = getClienti();
$pratiche_attive = getPratiche();
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div id="content" class="content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Nuova Guida/Lezione</h1>
                    <p class="text-muted mb-0">Inserisci una nuova guida in agenda</p>
                </div>
                <a href="/pages/agenda.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Torna all’Agenda
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
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="cliente_id" class="form-select" required id="selectCliente">
                            <option value="">-- Seleziona Cliente --</option>
                            <?php foreach($clienti as $cli): ?>
                                <option value="<?php echo $cli['id']; ?>">
                                    <?php echo htmlspecialchars($cli['cognome'] . ' ' . $cli['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Data *</label>
                        <input type="date" name="data_guida" class="form-control" value="<?php echo $data_corrente; ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Orario Inizio *</label>
                            <input type="time" name="orario_inizio" class="form-control" value="<?php echo $agendaStart; ?>" min="<?php echo $agendaStart; ?>" max="<?php echo $agendaEnd; ?>" step="900" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Orario Fine *</label>
                            <input type="time" name="orario_fine" class="form-control" value="<?php echo $agendaDefaultEnd; ?>" min="<?php echo $agendaStart; ?>" max="<?php echo $agendaEnd; ?>" step="900" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Istruttore *</label>
                        <select name="istruttore" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach($agendaInstructors as $instr): ?>
                                <option value="<?php echo htmlspecialchars($instr); ?>"><?php echo htmlspecialchars($instr); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo Lezione *</label>
                        <select name="tipo_lezione" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach($agendaLessonTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pratica Collegata (opzionale)</label>
                        <select name="pratica_id" class="form-select" id="selectPratica">
                            <option value="">-- Nessuna --</option>
                            <?php foreach($pratiche_attive as $prat): ?>
                                <option value="<?php echo $prat['id']; ?>" data-cliente-id="<?php echo $prat['cliente_id']; ?>">
                                    #<?php echo $prat['id']; ?> - <?php echo htmlspecialchars($prat['cliente_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Registra Guida</button>
                        <a href="/pages/agenda.php" class="btn btn-outline-secondary">Annulla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
const selectCliente = document.getElementById('selectCliente');
const selectPratica = document.getElementById('selectPratica');

function filterPraticheByCliente() {
    if (!selectCliente || !selectPratica) return;
    const clienteId = selectCliente.value;
    Array.from(selectPratica.options).forEach(option => {
        if (!option.value) {
            option.hidden = false;
            option.disabled = false;
            return;
        }
        const optionCliente = option.getAttribute('data-cliente-id');
        const visible = clienteId && optionCliente === clienteId;
        option.hidden = !visible;
        option.disabled = !visible;
    });

    const selectedOption = selectPratica.options[selectPratica.selectedIndex];
    if (selectedOption && (selectedOption.disabled || selectedOption.hidden)) {
        selectPratica.value = '';
    }
}

if (selectCliente) {
    selectCliente.addEventListener('change', filterPraticheByCliente);
    filterPraticheByCliente();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
