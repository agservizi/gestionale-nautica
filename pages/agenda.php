<?php
/**
 * Agenda Guide - Calendario lezioni e guide pratiche
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
                if (empty($_POST['cliente_id']) || empty($_POST['data_guida']) || empty($_POST['orario_inizio']) || empty($_POST['orario_fine']) || empty($_POST['istruttore'])) {
                    $message = 'Cliente, data, orari e istruttore sono obbligatori.';
                    $message_type = 'danger';
                    break;
                }
                $dataGuida = new DateTime($_POST['data_guida']);
                $weekday = (int)$dataGuida->format('N');
                if ($weekday === 7) {
                    $message = 'Le guide sono disponibili solo dal lunedì al sabato.';
                    $message_type = 'danger';
                    break;
                }
                $inizio = $_POST['orario_inizio'];
                $fine = $_POST['orario_fine'];
                if ($fine <= $inizio) {
                    $message = 'L\'orario di fine deve essere successivo a quello di inizio.';
                    $message_type = 'danger';
                    break;
                }
                if ($inizio < '08:00' || $fine > '18:00') {
                    $message = 'Le guide sono consentite solo tra le 08:00 e le 18:00.';
                    $message_type = 'danger';
                    break;
                }
                createAgenda($_POST);
                logAudit('create', 'agenda', null, $_POST['data_guida'] ?? null);
                $message = 'Guida registrata con successo!';
                $message_type = 'success';
                break;
            case 'delete':
                deleteAgenda($_POST['id']);
                logAudit('delete', 'agenda', $_POST['id']);
                $message = 'Guida eliminata con successo!';
                $message_type = 'success';
                break;
        }
    }
}

// Filtri data
$data_corrente = $_GET['data'] ?? date('Y-m-d');
$data_obj = new DateTime($data_corrente);
$mese_corrente = (int)($_GET['mese'] ?? date('m'));
$anno_corrente = (int)($_GET['anno'] ?? date('Y'));

// Ottieni guide del giorno
$guide_giorno = getAgendaGuide(['data' => $data_corrente]);

// Ottieni clienti per select
$clienti = getClienti();

// Ottieni pratiche attive per select
$pratiche_attive = getPratiche(['stato' => 'In corso']);

// Calendario mensile
$firstDay = new DateTime(sprintf('%04d-%02d-01', $anno_corrente, $mese_corrente));
$lastDay = (clone $firstDay)->modify('last day of this month');
$startMonth = $firstDay->format('Y-m-d');
$endMonth = $lastDay->format('Y-m-d');
$counts = getAgendaCountsByDateRange($startMonth, $endMonth);

$fmtMonth = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('it_IT', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'MMMM')
    : null;
$fmtFull = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('it_IT', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Rome', IntlDateFormatter::GREGORIAN, 'EEEE d MMMM y')
    : null;

$monthNameRaw = $fmtMonth ? $fmtMonth->format($firstDay) : date('F', $firstDay->getTimestamp());
$monthName = ucfirst($monthNameRaw);
$daysInMonth = (int)$lastDay->format('d');
$startWeekday = (int)$firstDay->format('N'); // 1 (Mon) - 7 (Sun)
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
                <h1 class="h3">Agenda Guide</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGuida">
                    <i class="bi bi-plus-lg"></i> Nuova Guida
                </button>
            </div>
        </div>
        
        <!-- Navigazione Data -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="btn-group" role="group">
                            <a href="?data=<?php echo $data_obj->modify('-1 day')->format('Y-m-d'); ?>" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-chevron-left"></i> Precedente
                            </a>
                            <a href="?data=<?php echo date('Y-m-d'); ?>" 
                               class="btn btn-outline-primary">
                                Oggi
                            </a>
                            <a href="?data=<?php echo (new DateTime($data_corrente))->modify('+1 day')->format('Y-m-d'); ?>" 
                               class="btn btn-outline-primary">
                                Successivo <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <h4 class="mb-0">
                            <?php 
                            $data_display = new DateTime($data_corrente);
                            $fullRaw = $fmtFull ? $fmtFull->format($data_display) : date('l d F Y', $data_display->getTimestamp());
                            echo ucfirst($fullRaw);
                            ?>
                        </h4>
                    </div>
                    <div class="col-md-4 text-end">
                        <input type="date" id="datePicker" class="form-control" 
                               value="<?php echo $data_corrente; ?>" 
                               onchange="window.location.href='?data=' + this.value">
                    </div>
                </div>
            </div>
        </div>
        
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#giornaliero">Giornaliero</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#mensile">Mensile</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#annuale">Annuale</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="giornaliero">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Guide del Giorno (<?php echo count($guide_giorno); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($guide_giorno)): ?>
                            <p class="text-muted text-center py-4">Nessuna guida programmata per questo giorno</p>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach($guide_giorno as $guida): ?>
                                    <div class="card mb-3 border-start border-primary border-4 agenda-item" draggable="true" data-id="<?php echo $guida['id']; ?>">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-2">
                                                    <h5 class="mb-0">
                                                        <i class="bi bi-clock"></i>
                                                        <?php echo substr($guida['orario_inizio'], 0, 5); ?> - 
                                                        <?php echo substr($guida['orario_fine'], 0, 5); ?>
                                                    </h5>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6 class="mb-0">
                                                        <i class="bi bi-person"></i>
                                                        <a href="cliente_dettaglio.php?id=<?php echo $guida['cliente_id']; ?>">
                                                            <?php echo htmlspecialchars($guida['cliente_nome']); ?>
                                                        </a>
                                                    </h6>
                                                    <?php if($guida['cliente_telefono']): ?>
                                                        <small class="text-muted">
                                                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($guida['cliente_telefono']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <?php if($guida['tipo_lezione']): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($guida['tipo_lezione']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if(!empty($guida['istruttore'])): ?>
                                                        <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($guida['istruttore']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if($guida['pratica_id']): ?>
                                                        <br>
                                                        <small>
                                                            <a href="pratica_dettaglio.php?id=<?php echo $guida['pratica_id']; ?>">
                                                                Pratica #<?php echo $guida['pratica_id']; ?>
                                                            </a>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <?php if($guida['note']): ?>
                                                        <small class="text-muted">
                                                            <i class="bi bi-chat-left-text"></i> <?php echo htmlspecialchars($guida['note']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-1 text-end">
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Eliminare questa guida?')">
                                                        <?php echo csrf_input(); ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $guida['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Elimina">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="mensile">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Calendario Mensile - <?php echo $monthName . ' ' . $anno_corrente; ?></h5>
                        <form class="d-flex gap-2" method="GET">
                            <input type="hidden" name="data" value="<?php echo $data_corrente; ?>">
                            <select name="mese" class="form-select">
                                <?php for($m=1;$m<=12;$m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $mese_corrente === $m ? 'selected' : ''; ?>>
                                        <?php
                                            $dateTmp = new DateTime(sprintf('%04d-%02d-01', $anno_corrente, $m));
                                            $mRaw = $fmtMonth ? $fmtMonth->format($dateTmp) : date('F', $dateTmp->getTimestamp());
                                            echo ucfirst($mRaw);
                                        ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="anno" class="form-select">
                                <?php for($y=APP_YEAR_START;$y<=date('Y')+1;$y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $anno_corrente === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                            <button class="btn btn-primary" type="submit">Vai</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Lun</th><th>Mar</th><th>Mer</th><th>Gio</th><th>Ven</th><th>Sab</th><th>Dom</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php
                                        $cell = 1;
                                        for ($i=1; $i<$startWeekday; $i++, $cell++) {
                                            echo '<td class="text-muted">&nbsp;</td>';
                                        }
                                        for ($day=1; $day<=$daysInMonth; $day++, $cell++) {
                                            $dateStr = sprintf('%04d-%02d-%02d', $anno_corrente, $mese_corrente, $day);
                                            $count = $counts[$dateStr] ?? 0;
                                            $badge = $count > 0 ? '<span class="badge bg-primary">' . $count . '</span>' : '';
                                            $link = '?data=' . $dateStr . '&mese=' . $mese_corrente . '&anno=' . $anno_corrente;
                                            echo '<td class="agenda-day" data-date="' . $dateStr . '"><a href="' . $link . '" class="d-block text-decoration-none">' . $day . '<br>' . $badge . '</a></td>';
                                            if ($cell % 7 === 0) {
                                                echo '</tr><tr>';
                                            }
                                        }
                                        while (($cell-1) % 7 !== 0) {
                                            echo '<td class="text-muted">&nbsp;</td>';
                                            $cell++;
                                        }
                                        ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="annuale">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Calendario Annuale - <?php echo $anno_corrente; ?></h5>
                        <form method="GET" class="d-flex gap-2">
                            <input type="hidden" name="data" value="<?php echo $data_corrente; ?>">
                            <select name="anno" class="form-select">
                                <?php for($y=APP_YEAR_START;$y<=date('Y')+1;$y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $anno_corrente === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                            <button class="btn btn-primary" type="submit">Vai</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php for($m=1;$m<=12;$m++): ?>
                                <div class="col-md-3">
                                    <a class="card text-decoration-none" href="?mese=<?php echo $m; ?>&anno=<?php echo $anno_corrente; ?>&data=<?php echo $data_corrente; ?>">
                                        <div class="card-body text-center">
                                            <h6 class="mb-0">
                                                <?php
                                                    $dateTmp = new DateTime(sprintf('%04d-%02d-01', $anno_corrente, $m));
                                                    $mRaw = $fmtMonth ? $fmtMonth->format($dateTmp) : date('F', $dateTmp->getTimestamp());
                                                    echo ucfirst($mRaw);
                                                ?>
                                            </h6>
                                        </div>
                                    </a>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<!-- Modal Nuova Guida -->
<div class="modal fade" id="modalGuida" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Nuova Guida/Lezione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="cliente_id" class="form-select" required>
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
                        <input type="date" name="data_guida" class="form-control" 
                               value="<?php echo $data_corrente; ?>" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Orario Inizio *</label>
                            <input type="time" name="orario_inizio" class="form-control" value="08:00" min="08:00" max="18:00" step="900" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Orario Fine *</label>
                            <input type="time" name="orario_fine" class="form-control" value="10:00" min="08:00" max="18:00" step="900" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Istruttore *</label>
                        <select name="istruttore" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <option value="Vincenzo Scibile">Vincenzo Scibile</option>
                            <option value="Vincenzo Lomiento">Vincenzo Lomiento</option>
                            <option value="Luigi Visalli">Luigi Visalli</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo Lezione</label>
                        <select name="tipo_lezione" class="form-select">
                            <option value="">-- Seleziona --</option>
                            <option value="Lezione teorica">Lezione teorica</option>
                            <option value="Guida pratica">Guida pratica</option>
                            <option value="Esame">Esame</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pratica Collegata (opzionale)</label>
                        <select name="pratica_id" class="form-select">
                            <option value="">-- Nessuna --</option>
                            <?php foreach($pratiche_attive as $prat): ?>
                                <option value="<?php echo $prat['id']; ?>">
                                    #<?php echo $prat['id']; ?> - <?php echo htmlspecialchars($prat['cliente_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Registra Guida</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.CSRF_TOKEN = "<?php echo csrf_token(); ?>";
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
