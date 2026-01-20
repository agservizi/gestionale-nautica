<?php
/**
 * Diagnostica - Monitoraggio problemi globali (admin/sviluppatore)
 */
require_once __DIR__ . '/../includes/header.php';
requireDeveloper();

$db = getDB();
$jobs = $db->query("SELECT * FROM scheduled_jobs ORDER BY job_key")->fetchAll();
$jobErrors = $db->query("SELECT * FROM scheduled_jobs WHERE last_status = 'error' ORDER BY last_run DESC")->fetchAll();
$locked = $db->query("SELECT username, ip_address, attempts, locked_until, last_attempt FROM auth_attempts WHERE locked_until IS NOT NULL AND locked_until > NOW() ORDER BY locked_until DESC")->fetchAll();
$recentAttempts = $db->query("SELECT username, ip_address, attempts, last_attempt FROM auth_attempts WHERE last_attempt >= (NOW() - INTERVAL 24 HOUR) ORDER BY last_attempt DESC")->fetchAll();

$agendaInvalid = $db->query("SELECT a.id, a.data_guida, a.orario_inizio, a.orario_fine, c.nome, c.cognome FROM agenda_guide a JOIN clienti c ON c.id = a.cliente_id WHERE a.orario_fine <= a.orario_inizio OR a.orario_inizio IS NULL OR a.orario_fine IS NULL ORDER BY a.data_guida DESC LIMIT 10")->fetchAll();
$agendaMissingLesson = $db->query("SELECT a.id, a.data_guida, c.nome, c.cognome FROM agenda_guide a JOIN clienti c ON c.id = a.cliente_id WHERE a.tipo_lezione IS NULL OR a.tipo_lezione = '' ORDER BY a.data_guida DESC LIMIT 10")->fetchAll();
$pagamentiInvalid = $db->query("SELECT id, importo, data_pagamento FROM pagamenti WHERE importo <= 0 ORDER BY data_pagamento DESC LIMIT 10")->fetchAll();
$speseInvalid = $db->query("SELECT id, importo, data_spesa FROM spese WHERE importo <= 0 ORDER BY data_spesa DESC LIMIT 10")->fetchAll();
$praticheOverpaid = $db->query("SELECT id, totale_previsto, totale_pagato FROM pratiche WHERE totale_pagato > (totale_previsto + 0.01) ORDER BY data_modifica DESC LIMIT 10")->fetchAll();

$uploadDir = __DIR__ . '/../uploads';
$missingFiles = [];
$allegatiRows = $db->query("SELECT id, pratica_id, filename_stored, data_upload FROM pratiche_allegati ORDER BY data_upload DESC LIMIT 200")->fetchAll();
foreach ($allegatiRows as $row) {
    $path = $uploadDir . '/' . $row['filename_stored'];
    if (!is_file($path)) {
        $missingFiles[] = $row;
    }
}

$recentAudit = $db->query("SELECT a.created_at, a.action, a.entity, a.details, u.username FROM audit_log a LEFT JOIN utenti u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 20")->fetchAll();

function tailFileLines($filePath, $maxLines = 50): array {
    if (!is_file($filePath) || !is_readable($filePath)) {
        return [];
    }
    $lines = @file($filePath, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return [];
    }
    if (count($lines) <= $maxLines) {
        return $lines;
    }
    return array_slice($lines, -$maxLines);
}

$phpErrorLogPath = ini_get('error_log');
$phpErrorLines = [];
$phpErrorStatus = ['label' => 'Non configurato', 'class' => 'secondary'];
if ($phpErrorLogPath) {
    $phpErrorLines = tailFileLines($phpErrorLogPath, 60);
    if (!empty($phpErrorLines)) {
        $phpErrorStatus = ['label' => 'Da verificare', 'class' => 'warning'];
    } elseif (is_file($phpErrorLogPath) && is_readable($phpErrorLogPath)) {
        $phpErrorStatus = ['label' => 'OK', 'class' => 'success'];
    } else {
        $phpErrorStatus = ['label' => 'Non accessibile', 'class' => 'danger'];
    }
}

$backupDir = __DIR__ . '/../backups';
$backupFiles = [];
if (is_dir($backupDir)) {
    $backupFiles = array_merge(
        glob($backupDir . '/*.sql') ?: [],
        glob($backupDir . '/*.zip') ?: []
    );
}

$lastBackupTime = null;
if (!empty($backupFiles)) {
    usort($backupFiles, function($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });
    $lastBackupTime = filemtime($backupFiles[0]);
}

$backupStatus = ['label' => 'Nessun backup', 'class' => 'danger'];
if ($lastBackupTime) {
    $days = floor((time() - $lastBackupTime) / 86400);
    if ($days <= 7) {
        $backupStatus = ['label' => 'OK', 'class' => 'success'];
    } elseif ($days <= 14) {
        $backupStatus = ['label' => 'Da verificare', 'class' => 'warning'];
    } else {
        $backupStatus = ['label' => 'Scaduto', 'class' => 'danger'];
    }
}

$uploadsDir = __DIR__ . '/../uploads';
$freeBytes = is_dir($uploadsDir) ? @disk_free_space($uploadsDir) : false;
$totalBytes = is_dir($uploadsDir) ? @disk_total_space($uploadsDir) : false;
$diskStatus = ['label' => 'N/D', 'class' => 'secondary'];
if ($freeBytes !== false) {
    $freeGb = $freeBytes / 1024 / 1024 / 1024;
    if ($freeGb < 1) {
        $diskStatus = ['label' => 'Critico', 'class' => 'danger'];
    } elseif ($freeGb < 5) {
        $diskStatus = ['label' => 'Basso', 'class' => 'warning'];
    } else {
        $diskStatus = ['label' => 'OK', 'class' => 'success'];
    }
}

$requiredSettings = ['agenda_start_time', 'agenda_end_time', 'app_year_start', 'theme_preset'];
$missingSettings = [];
foreach ($requiredSettings as $key) {
    $value = function_exists('getSetting') ? getSetting($key, '') : '';
    if ($value === null || trim((string)$value) === '') {
        $missingSettings[] = $key;
    }
}

function jobStatusInfo(array $job): array {
    $enabled = (int)($job['enabled'] ?? 0) === 1;
    $lastStatus = $job['last_status'] ?? null;
    $lastRun = $job['last_run'] ?? null;
    $interval = (int)($job['interval_minutes'] ?? 0);

    if (!$enabled) {
        return ['label' => 'Disabilitato', 'class' => 'secondary'];
    }

    if ($lastStatus === 'error') {
        return ['label' => 'Errore', 'class' => 'danger'];
    }

    if ($lastRun === null) {
        return ['label' => 'Mai eseguito', 'class' => 'warning'];
    }

    if ($interval > 0) {
        $lastRunTime = new DateTime($lastRun);
        $now = new DateTime();
        $minutes = ($now->getTimestamp() - $lastRunTime->getTimestamp()) / 60;
        if ($minutes > ($interval * 2)) {
            return ['label' => 'In ritardo', 'class' => 'warning'];
        }
    }

    return ['label' => 'OK', 'class' => 'success'];
}
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Content -->
<div id="content" class="content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Diagnostica</h1>
                <p class="text-muted mb-0">Panoramica su malfunzionamenti e criticità.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="row g-4">
                    <div class="col-12 col-lg-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-database-check"></i> Backup</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span>Stato</span>
                                    <span class="badge bg-<?php echo $backupStatus['class']; ?>"><?php echo $backupStatus['label']; ?></span>
                                </div>
                                <div class="text-muted small">
                                    Ultimo backup: <?php echo $lastBackupTime ? date('d/m/Y H:i', $lastBackupTime) : 'N/D'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-hdd"></i> Spazio disco</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span>Disponibile</span>
                                    <span class="badge bg-<?php echo $diskStatus['class']; ?>"><?php echo $diskStatus['label']; ?></span>
                                </div>
                                <?php if ($freeBytes !== false && $totalBytes !== false): ?>
                                    <div class="text-muted small">
                                        <?php echo number_format($freeBytes / 1024 / 1024 / 1024, 2, ',', '.'); ?> GB liberi su
                                        <?php echo number_format($totalBytes / 1024 / 1024 / 1024, 2, ',', '.'); ?> GB
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small">Impossibile leggere lo spazio disco.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-gear"></i> Impostazioni</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($missingSettings)): ?>
                                    <span class="badge bg-success">OK</span>
                                    <div class="text-muted small mt-2">Tutte le impostazioni principali sono presenti.</div>
                                <?php else: ?>
                                    <span class="badge bg-warning">Incomplete</span>
                                    <div class="text-muted small mt-2">Mancanti: <?php echo htmlspecialchars(implode(', ', $missingSettings)); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bug"></i> Scheduler</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobs)): ?>
                            <p class="text-muted mb-0">Nessun job configurato.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Job</th>
                                            <th>Intervallo</th>
                                            <th>Ultima esecuzione</th>
                                            <th>Stato</th>
                                            <th>Messaggio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $job): ?>
                                            <?php $status = jobStatusInfo($job); ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($job['job_key']); ?></td>
                                                <td><?php echo (int)$job['interval_minutes']; ?> min</td>
                                                <td><?php echo $job['last_run'] ? formatDate($job['last_run'], 'd/m/Y H:i') : '-'; ?></td>
                                                <td><span class="badge bg-<?php echo $status['class']; ?>"><?php echo $status['label']; ?></span></td>
                                                <td class="text-muted small">
                                                    <?php echo $job['last_message'] ? htmlspecialchars($job['last_message']) : '-'; ?>
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

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Errori job recenti</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobErrors)): ?>
                            <p class="text-muted mb-0">Nessun errore recente.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($jobErrors as $job): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($job['job_key']); ?></strong>
                                            <span class="text-muted small"><?php echo $job['last_run'] ? formatDate($job['last_run'], 'd/m/Y H:i') : '-'; ?></span>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo $job['last_message'] ? htmlspecialchars($job['last_message']) : 'Errore non specificato.'; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Accessi bloccati</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($locked)): ?>
                            <p class="text-muted mb-0">Nessun blocco attivo.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>IP</th>
                                            <th>Tentativi</th>
                                            <th>Blocco fino</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locked as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                                <td><?php echo (int)$row['attempts']; ?></td>
                                                <td><?php echo $row['locked_until'] ? formatDate($row['locked_until'], 'd/m/Y H:i') : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-shield-exclamation"></i> Tentativi sospetti (ultime 24h)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentAttempts)): ?>
                            <p class="text-muted mb-0">Nessun tentativo recente.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>IP</th>
                                            <th>Tentativi</th>
                                            <th>Ultimo tentativo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAttempts as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                                <td><?php echo (int)$row['attempts']; ?></td>
                                                <td><?php echo $row['last_attempt'] ? formatDate($row['last_attempt'], 'd/m/Y H:i') : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Anomalie dati</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Guide con orari invalidi</span>
                                    <span class="badge bg-<?php echo empty($agendaInvalid) ? 'success' : 'warning'; ?>"><?php echo count($agendaInvalid); ?></span>
                                </div>
                                <?php if (!empty($agendaInvalid)): ?>
                                    <div class="text-muted small">Ultima: <?php echo htmlspecialchars($agendaInvalid[0]['nome'] . ' ' . $agendaInvalid[0]['cognome']); ?> (<?php echo formatDate($agendaInvalid[0]['data_guida']); ?>)</div>
                                <?php endif; ?>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Guide senza tipo lezione</span>
                                    <span class="badge bg-<?php echo empty($agendaMissingLesson) ? 'success' : 'warning'; ?>"><?php echo count($agendaMissingLesson); ?></span>
                                </div>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Pagamenti con importo ≤ 0</span>
                                    <span class="badge bg-<?php echo empty($pagamentiInvalid) ? 'success' : 'warning'; ?>"><?php echo count($pagamentiInvalid); ?></span>
                                </div>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Spese con importo ≤ 0</span>
                                    <span class="badge bg-<?php echo empty($speseInvalid) ? 'success' : 'warning'; ?>"><?php echo count($speseInvalid); ?></span>
                                </div>
                            </div>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>Pratiche con pagato > previsto</span>
                                    <span class="badge bg-<?php echo empty($praticheOverpaid) ? 'success' : 'warning'; ?>"><?php echo count($praticheOverpaid); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-paperclip"></i> Allegati mancanti</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($missingFiles)): ?>
                            <p class="text-muted mb-0">Nessun allegato mancante.</p>
                        <?php else: ?>
                            <div class="text-muted small mb-2">Trovati <?php echo count($missingFiles); ?> allegati mancanti (ultimi 200).</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Pratica</th>
                                            <th>File</th>
                                            <th>Caricato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($missingFiles, 0, 10) as $row): ?>
                                            <tr>
                                                <td>#<?php echo (int)$row['pratica_id']; ?></td>
                                                <td class="text-muted small"><?php echo htmlspecialchars($row['filename_stored']); ?></td>
                                                <td><?php echo $row['data_upload'] ? formatDate($row['data_upload'], 'd/m/Y H:i') : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-terminal"></i> Errori PHP</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span>Stato</span>
                            <span class="badge bg-<?php echo $phpErrorStatus['class']; ?>"><?php echo $phpErrorStatus['label']; ?></span>
                        </div>
                        <div class="text-muted small mb-3">Log: <?php echo $phpErrorLogPath ? htmlspecialchars($phpErrorLogPath) : 'Non configurato'; ?></div>
                        <?php if (empty($phpErrorLines)): ?>
                            <p class="text-muted mb-0">Nessun errore recente (o log non accessibile).</p>
                        <?php else: ?>
                            <pre class="bg-light p-3 rounded small mb-0" style="max-height: 260px; overflow:auto;"><?php echo htmlspecialchars(implode("\n", $phpErrorLines)); ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-journal-text"></i> Log applicativo (audit)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentAudit)): ?>
                            <p class="text-muted mb-0">Nessuna attività registrata.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Quando</th>
                                            <th>Utente</th>
                                            <th>Azione</th>
                                            <th>Entità</th>
                                            <th>Dettagli</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAudit as $row): ?>
                                            <tr>
                                                <td><?php echo $row['created_at'] ? formatDate($row['created_at'], 'd/m/Y H:i') : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($row['username'] ?? 'N/D'); ?></td>
                                                <td><?php echo htmlspecialchars($row['action']); ?></td>
                                                <td><?php echo htmlspecialchars($row['entity']); ?></td>
                                                <td class="text-muted small"><?php echo $row['details'] ? htmlspecialchars($row['details']) : '-'; ?></td>
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
