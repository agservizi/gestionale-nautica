<?php
/**
 * Automazioni - Scheduler interno (solo admin)
 */
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

initScheduledJobs();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        $jobKey = $_POST['job_key'] ?? '';

        if ($action === 'run' && $jobKey) {
            runJobByKey($jobKey);
            $message = 'Job eseguito: ' . $jobKey;
            $message_type = 'success';
        }

        if ($action === 'toggle' && $jobKey) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE scheduled_jobs SET enabled = IF(enabled=1,0,1) WHERE job_key = ?");
            $stmt->execute([$jobKey]);
            $message = 'Stato aggiornato per ' . $jobKey;
            $message_type = 'success';
        }
    }
}

$jobs = getDB()->query("SELECT * FROM scheduled_jobs ORDER BY job_key")->fetchAll();
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div id="content" class="content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Automazioni</h1>
                <p class="text-muted">Scheduler interno senza cron (esecuzione a richiesta).</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>Descrizione</th>
                                <th>Intervallo (min)</th>
                                <th>Abilitato</th>
                                <th>Ultima esecuzione</th>
                                <th>Esito</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($jobs as $job): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job['job_key']); ?></td>
                                    <td><?php echo htmlspecialchars($job['description']); ?></td>
                                    <td><?php echo (int)$job['interval_minutes']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $job['enabled'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $job['enabled'] ? 'ON' : 'OFF'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $job['last_run'] ? formatDate($job['last_run'], 'd/m/Y H:i') : '-'; ?></td>
                                    <td>
                                        <?php if($job['last_status']): ?>
                                            <span class="badge bg-<?php echo $job['last_status'] === 'ok' ? 'success' : 'danger'; ?>">
                                                <?php echo $job['last_status']; ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <form method="POST" class="d-inline">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="action" value="run">
                                                <input type="hidden" name="job_key" value="<?php echo htmlspecialchars($job['job_key']); ?>">
                                                <button class="btn btn-sm btn-primary" type="submit">Esegui</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="job_key" value="<?php echo htmlspecialchars($job['job_key']); ?>">
                                                <button class="btn btn-sm btn-outline-secondary" type="submit">On/Off</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>