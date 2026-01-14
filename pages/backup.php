<?php
/**
 * Backup/Restore - solo admin
 */
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } else {
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
            try {
                restoreSqlBackup($sql);
                logAudit('restore', 'backup');
                $message = 'Restore completato con successo.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Errore restore: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}
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
                <h1 class="h3">Backup & Restore</h1>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Backup Database</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Scarica un dump SQL completo.</p>
                        <a href="backup_download.php" class="btn btn-primary">
                            <i class="bi bi-download"></i> Scarica Backup
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Restore Database</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">Attenzione: il restore sovrascrive lo stato attuale.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <?php echo csrf_input(); ?>
                            <div class="mb-3">
                                <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                            </div>
                            <button class="btn btn-danger" type="submit">
                                <i class="bi bi-upload"></i> Esegui Restore
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
