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
    <title><?php echo APP_NAME; ?> - Informativa Privacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
    <link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0"><i class="bi bi-shield-check"></i> Informativa Privacy</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Ultimo aggiornamento: <?php echo date('d/m/Y'); ?></p>

                <h6>Titolare del trattamento</h6>
                <p>Inserire i dati del Titolare (ragione sociale, indirizzo, contatti).</p>

                <h6>Finalità e base giuridica</h6>
                <ul>
                    <li>Gestione operativa della scuola nautica (esecuzione di contratto).</li>
                    <li>Adempimenti amministrativi e fiscali (obbligo di legge).</li>
                    <li>Comunicazioni di servizio (legittimo interesse).</li>
                </ul>

                <h6>Categorie di dati trattati</h6>
                <p>Dati identificativi e di contatto, dati relativi alle pratiche, pagamenti e documentazione allegata.</p>

                <h6>Modalità del trattamento</h6>
                <p>Il trattamento avviene con strumenti informatici e misure di sicurezza adeguate per garantire riservatezza e integrità.</p>

                <h6>Conservazione</h6>
                <p>I dati sono conservati per il tempo necessario alle finalità indicate e per gli obblighi di legge applicabili.</p>

                <h6>Destinatari</h6>
                <p>I dati possono essere comunicati a fornitori di servizi tecnici e consulenti, nominati responsabili del trattamento ove necessario.</p>

                <h6>Diritti dell’interessato</h6>
                <p>È possibile esercitare i diritti previsti dagli artt. 15-22 GDPR (accesso, rettifica, cancellazione, limitazione, portabilità, opposizione) contattando il Titolare.</p>

                <h6>Contatti</h6>
                <p>Inserire un indirizzo email o PEC dedicata alle richieste privacy.</p>
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
