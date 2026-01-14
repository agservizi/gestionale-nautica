<?php
/**
 * Profilo Utente
 */
require_once __DIR__ . '/../includes/header.php';

$user = currentUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$message = '';
$message_type = 'success';

$db = getDB();
$stmt = $db->prepare("SELECT id, username, ruolo, password_hash FROM utenti WHERE id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$utente = $stmt->fetch();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            throw new Exception('Sessione scaduta. Riprova.');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $username = trim($_POST['username'] ?? '');
            if ($username === '') {
                throw new Exception('Username obbligatorio.');
            }

            $stmt = $db->prepare("SELECT id FROM utenti WHERE username = ? AND id <> ? LIMIT 1");
            $stmt->execute([$username, $utente['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Username già in uso.');
            }

            $stmt = $db->prepare("UPDATE utenti SET username = ? WHERE id = ?");
            $stmt->execute([$username, $utente['id']]);

            $_SESSION['username'] = $username;
            $utente['username'] = $username;

            if (function_exists('logAudit')) {
                logAudit('update', 'utente', $utente['id'], 'profilo:username');
            }

            $message = 'Profilo aggiornato con successo.';
            $message_type = 'success';
        }

        if ($action === 'change_password') {
            $current = $_POST['current_password'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if ($current === '' || $password === '' || $confirm === '') {
                throw new Exception('Completa tutti i campi password.');
            }

            if (!password_verify($current, $utente['password_hash'])) {
                throw new Exception('Password attuale non corretta.');
            }

            if ($password !== $confirm) {
                throw new Exception('Le nuove password non coincidono.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE utenti SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $utente['id']]);

            if (function_exists('logAudit')) {
                logAudit('reset_password', 'utente', $utente['id'], 'profilo:self');
            }

            $message = 'Password aggiornata con successo.';
            $message_type = 'success';
        }

        if ($action === 'gdpr_request') {
            $type = $_POST['request_type'] ?? '';
            $details = trim($_POST['details'] ?? '');

            $allowed = ['accesso', 'rettifica', 'cancellazione', 'limitazione', 'portabilita', 'opposizione'];
            if (!in_array($type, $allowed, true)) {
                throw new Exception('Tipo richiesta non valido.');
            }

            if (function_exists('createGdprRequest')) {
                createGdprRequest($utente['id'], $type, $details !== '' ? $details : null);
            }

            if (function_exists('logAudit')) {
                logAudit('create', 'gdpr_request', $utente['id'], $type);
            }

            $message = 'Richiesta GDPR registrata. Ti risponderemo entro i termini di legge.';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

$gdprRequests = function_exists('getGdprRequestsByUser') ? getGdprRequestsByUser($utente['id'], 5) : [];
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div id="content" class="content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Profilo</h1>
                <p class="text-muted">Gestisci il tuo account</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Dati profilo</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="update_profile">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($utente['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ruolo</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($utente['ruolo']); ?>" disabled>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Salva profilo
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-key"></i> Cambia password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label class="form-label">Password attuale</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nuova password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Conferma nuova password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-shield-lock"></i> Aggiorna password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Richieste GDPR</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Puoi inviare una richiesta di accesso, rettifica o cancellazione dei dati.</p>
                        <form method="POST" class="mb-3">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="gdpr_request">
                            <div class="mb-3">
                                <label class="form-label">Tipo richiesta</label>
                                <select name="request_type" class="form-select" required>
                                    <option value="">Seleziona</option>
                                    <option value="accesso">Accesso ai dati</option>
                                    <option value="rettifica">Rettifica</option>
                                    <option value="cancellazione">Cancellazione</option>
                                    <option value="limitazione">Limitazione del trattamento</option>
                                    <option value="portabilita">Portabilità</option>
                                    <option value="opposizione">Opposizione</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dettagli (opzionale)</label>
                                <textarea name="details" class="form-control" rows="3" placeholder="Inserisci dettagli utili"></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-send"></i> Invia richiesta
                            </button>
                        </form>

                        <?php if (!empty($gdprRequests)): ?>
                            <div class="mt-3">
                                <h6 class="mb-2">Richieste recenti</h6>
                                <ul class="list-group">
                                    <?php foreach ($gdprRequests as $req): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars(ucfirst($req['request_type'])); ?></strong>
                                                <div class="small text-muted"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($req['created_at']))); ?></div>
                                            </div>
                                            <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($req['status']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
