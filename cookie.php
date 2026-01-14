<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$cspNonce = base64_encode(random_bytes(16));
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'nonce-$cspNonce'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Cookie Policy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
    <link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0"><i class="bi bi-cookie"></i> Cookie Policy</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Ultimo aggiornamento: <?php echo date('d/m/Y'); ?></p>

                <h6>Cosa sono i cookie</h6>
                <p>I cookie sono piccoli file di testo salvati sul dispositivo che aiutano il sito a funzionare correttamente.</p>

                <h6>Cookie tecnici (necessari)</h6>
                <ul>
                    <li>Sessione e sicurezza (autenticazione, CSRF, preferenze essenziali).</li>
                    <li>Cookie di consenso per memorizzare la scelta dell’utente.</li>
                </ul>

                <h6>Cookie facoltativi</h6>
                <p>Al momento non utilizziamo cookie di profilazione o marketing. Se in futuro venissero introdotti, saranno richiesti specifici consensi.</p>

                <h6>Gestione del consenso</h6>
                <p>Puoi accettare, rifiutare o limitare i cookie tramite il banner. La scelta è modificabile in qualsiasi momento.</p>

                <h6>Come disabilitare i cookie dal browser</h6>
                <p>È possibile gestire i cookie dalle impostazioni del browser. Disattivandoli, alcune funzionalità potrebbero non funzionare correttamente.</p>
            </div>
        </div>
    </div>

    <?php $consentValue = function_exists('getConsentValue') ? getConsentValue() : null; ?>
    <div id="cookieBanner" class="cookie-banner <?php echo $consentValue ? 'd-none' : ''; ?>" data-consent-name="<?php echo htmlspecialchars(getConsentCookieName(), ENT_QUOTES); ?>">
        <div class="cookie-banner__content">
            <div class="cookie-banner__text">
                <h6 class="mb-1">Privacy e Cookie</h6>
                <p class="mb-0">Usiamo cookie tecnici per il funzionamento del sistema e, solo se acconsenti, cookie non essenziali. Puoi cambiare scelta in qualsiasi momento.</p>
                <div class="small mt-1">
                    <a href="/privacy.php">Informativa Privacy</a> • <a href="/cookie.php">Cookie Policy</a>
                </div>
            </div>
            <div class="cookie-banner__actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-consent-action="essential">Solo necessari</button>
                <button type="button" class="btn btn-outline-danger btn-sm" data-consent-action="rejected">Rifiuta</button>
                <button type="button" class="btn btn-primary btn-sm" data-consent-action="accepted">Accetta tutti</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
