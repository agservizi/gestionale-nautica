<?php
/**
 * Login - Accesso a NautikaPro
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Security Headers
$cspNonce = base64_encode(random_bytes(16));
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'nonce-$cspNonce'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'");

if (isLogged()) {
    header('Location: /pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $error = 'Sessione scaduta. Riprova.';
    } elseif ($username === '' || $password === '') {
        $error = 'Inserisci username e password.';
    } else {
        if (doLogin($username, $password)) {
            header('Location: /pages/dashboard.php');
            exit;
        }
        $error = 'Credenziali non valide.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header text-center">
                        <h4 class="mb-0 text-white">
                            <i class="bi bi-compass"></i> <?php echo APP_NAME; ?>
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <h5 class="text-center mb-4">Accesso</h5>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <?php echo csrf_input(); ?>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Entra
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center small text-muted">
                        Anno 2025+ • Versione <?php echo APP_VERSION; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
