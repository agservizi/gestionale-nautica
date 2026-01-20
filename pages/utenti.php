<?php
/**
 * Utenti - Gestione utenti e ruoli (solo admin)
 */
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $action = $_POST['action'] ?? '';
    $allowedRoles = ['operatore', 'admin', 'sviluppatore'];

    try {
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            throw new Exception('Sessione scaduta. Riprova.');
        }

        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $ruolo = $_POST['ruolo'] ?? 'operatore';
            $attivo = isset($_POST['attivo']) ? 1 : 0;

            if (!in_array($ruolo, $allowedRoles, true)) {
                $ruolo = 'operatore';
            }

            if ($username === '' || $password === '') {
                throw new Exception('Username e password sono obbligatori.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO utenti (username, password_hash, ruolo, attivo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $ruolo, $attivo]);
            logAudit('create', 'utente', $db->lastInsertId(), $username);

            $message = 'Utente creato con successo!';
            $message_type = 'success';
        }

        if ($action === 'update') {
            $id = (int)$_POST['id'];
            $ruolo = $_POST['ruolo'] ?? 'operatore';
            $attivo = isset($_POST['attivo']) ? 1 : 0;

            if (!in_array($ruolo, $allowedRoles, true)) {
                $ruolo = 'operatore';
            }

            $stmt = $db->prepare("UPDATE utenti SET ruolo = ?, attivo = ? WHERE id = ?");
            $stmt->execute([$ruolo, $attivo, $id]);
            logAudit('update', 'utente', $id);

            $message = 'Utente aggiornato con successo!';
            $message_type = 'success';
        }

        if ($action === 'reset_password') {
            $id = (int)$_POST['id'];
            $password = $_POST['password'] ?? '';

            if ($password === '') {
                throw new Exception('La password è obbligatoria.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE utenti SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            logAudit('reset_password', 'utente', $id);

            $message = 'Password aggiornata con successo!';
            $message_type = 'success';
        }

        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("DELETE FROM utenti WHERE id = ?");
            $stmt->execute([$id]);
            logAudit('delete', 'utente', $id);

            $message = 'Utente eliminato con successo!';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

$utenti = getDB()->query("SELECT id, username, ruolo, attivo, data_creazione FROM utenti ORDER BY id DESC")->fetchAll();
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
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1 class="h3">Utenti</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovoUtente">
                    <i class="bi bi-plus-lg"></i> Nuovo Utente
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Ruolo</th>
                                <th>Attivo</th>
                                <th>Creato</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($utenti)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Nessun utente presente</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($utenti as $utente): ?>
                                    <tr>
                                        <td><?php echo $utente['id']; ?></td>
                                        <td><?php echo htmlspecialchars($utente['username']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $utente['ruolo'] === 'admin' ? 'primary' : ($utente['ruolo'] === 'sviluppatore' ? 'info' : 'secondary'); ?>">
                                                <?php echo $utente['ruolo']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($utente['attivo']): ?>
                                                <span class="badge bg-success">Attivo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Disattivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($utente['data_creazione']); ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="Modifica utente" data-action="edit-user" data-utente="<?php echo htmlspecialchars(json_encode($utente), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="Reset password" data-action="reset-password" data-utente="<?php echo htmlspecialchars(json_encode($utente), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Elimina utente" data-action="delete-user" data-user-id="<?php echo (int)$utente['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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

<!-- Modal Nuovo Utente -->
<div class="modal fade" id="modalNuovoUtente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Nuovo Utente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ruolo</label>
                        <select name="ruolo" class="form-select">
                            <option value="operatore">Operatore</option>
                            <option value="admin">Admin</option>
                            <option value="sviluppatore">Sviluppatore</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="attivo" id="attivoNew" checked>
                        <label class="form-check-label" for="attivoNew">Attivo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Utente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Utente -->
<div class="modal fade" id="modalModificaUtente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-header">
                    <h5 class="modal-title">Modifica Utente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" id="editUsername" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ruolo</label>
                        <select name="ruolo" id="editRole" class="form-select">
                            <option value="operatore">Operatore</option>
                            <option value="admin">Admin</option>
                            <option value="sviluppatore">Sviluppatore</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="attivo" id="editActive">
                        <label class="form-check-label" for="editActive">Attivo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="resetUserId">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nuova Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-info">Aggiorna Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete -->
<div class="modal fade" id="modalDeleteUtente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteUserId">
                <div class="modal-header">
                    <h5 class="modal-title">Elimina Utente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Confermi l'eliminazione dell'utente?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">Elimina</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
document.querySelectorAll('[data-action="edit-user"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const utente = JSON.parse(btn.getAttribute('data-utente'));
        editUtente(utente);
    });
});

document.querySelectorAll('[data-action="reset-password"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const utente = JSON.parse(btn.getAttribute('data-utente'));
        resetPassword(utente);
    });
});

document.querySelectorAll('[data-action="delete-user"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = parseInt(btn.getAttribute('data-user-id'), 10);
        deleteUtente(id);
    });
});

function editUtente(utente) {
    document.getElementById('editUserId').value = utente.id;
    document.getElementById('editUsername').value = utente.username;
    document.getElementById('editRole').value = utente.ruolo;
    document.getElementById('editActive').checked = utente.attivo == 1;
    new bootstrap.Modal(document.getElementById('modalModificaUtente')).show();
}

function resetPassword(utente) {
    document.getElementById('resetUserId').value = utente.id;
    new bootstrap.Modal(document.getElementById('modalResetPassword')).show();
}

function deleteUtente(id) {
    document.getElementById('deleteUserId').value = id;
    new bootstrap.Modal(document.getElementById('modalDeleteUtente')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
