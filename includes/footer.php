    </div> <!-- /wrapper -->

    <footer class="py-3 border-top bg-white app-footer-layout">
        <div class="container-fluid">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between small text-muted">
                <div>
                    NAUTIKAPRO <?php echo date('Y'); ?> SVILUPPATO DA
                    <a href="https://agenziaplinio.it" target="_blank" rel="noopener noreferrer">AG SERVIZI</a>
                </div>
                <div class="mt-2 mt-md-0">AUTOSCUOLA LIANA</div>
            </div>
        </div>
    </footer>

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
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
</body>
</html>
