<?php
/**
 * Impostazioni - Configurazione applicazione (solo admin)
 */
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$message = '';
$message_type = '';

$agendaWindow = getAgendaTimeWindow();
$appYearStart = getAppYearStart();
$defaultInstructors = ['Vincenzo Scibile', 'Vincenzo Lomiento', 'Luigi Visalli'];
$defaultLessonTypes = ['Guida pratica', 'Esame', 'Altro'];
$defaultExpenseCategories = ['Vincenzo', 'Luigi', 'Affitto barca', 'Benzina', 'Altro'];
$agendaInstructors = getSettingsList('agenda_instructors', $defaultInstructors);
$agendaLessonTypes = getSettingsList('agenda_lesson_types', $defaultLessonTypes);
$expenseCategories = getSettingsList('expense_categories', $defaultExpenseCategories);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } else {
        $agendaStart = trim($_POST['agenda_start_time'] ?? '');
        $agendaEnd = trim($_POST['agenda_end_time'] ?? '');
        $appYear = (int)($_POST['app_year_start'] ?? 0);
        $instructorsRaw = $_POST['agenda_instructors'] ?? '';
        $lessonTypesRaw = $_POST['agenda_lesson_types'] ?? '';
        $expenseCategoriesRaw = $_POST['expense_categories'] ?? '';

        $isTime = function($value) {
            return is_string($value) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value);
        };

        $parsedInstructors = parseSettingsList($instructorsRaw, $defaultInstructors);
        $parsedLessonTypes = parseSettingsList($lessonTypesRaw, $defaultLessonTypes);
        $parsedExpenseCategories = parseSettingsList($expenseCategoriesRaw, $defaultExpenseCategories);

        if (!$isTime($agendaStart) || !$isTime($agendaEnd) || $agendaEnd <= $agendaStart) {
            $message = 'Orari agenda non validi. Verifica inizio e fine.';
            $message_type = 'danger';
        } elseif (empty($parsedInstructors)) {
            $message = 'Inserisci almeno un istruttore.';
            $message_type = 'danger';
        } elseif (empty($parsedLessonTypes)) {
            $message = 'Inserisci almeno un tipo lezione.';
            $message_type = 'danger';
        } elseif (empty($parsedExpenseCategories)) {
            $message = 'Inserisci almeno una categoria spesa.';
            $message_type = 'danger';
        } elseif ($appYear < 2000 || $appYear > (int)date('Y') + 1) {
            $message = 'Anno iniziale non valido.';
            $message_type = 'danger';
        } else {
            setSetting('agenda_start_time', $agendaStart);
            setSetting('agenda_end_time', $agendaEnd);
            setSetting('app_year_start', (string)$appYear);
            setSettingsList('agenda_instructors', $parsedInstructors);
            setSettingsList('agenda_lesson_types', $parsedLessonTypes);
            setSettingsList('expense_categories', $parsedExpenseCategories);

            if (function_exists('logAudit')) {
                logAudit('update', 'settings', null, 'general');
            }

            $message = 'Impostazioni aggiornate con successo.';
            $message_type = 'success';
        }

        $agendaWindow = getAgendaTimeWindow();
        $appYearStart = getAppYearStart();
        $agendaInstructors = getSettingsList('agenda_instructors', $defaultInstructors);
        $agendaLessonTypes = getSettingsList('agenda_lesson_types', $defaultLessonTypes);
        $expenseCategories = getSettingsList('expense_categories', $defaultExpenseCategories);
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
                <h1 class="h3">Impostazioni</h1>
                <p class="text-muted">Configura i parametri principali dell’applicazione.</p>
            </div>
        </div>

        <form method="POST">
            <?php echo csrf_input(); ?>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-calendar3"></i> Agenda Guide</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Orario inizio</label>
                                    <input type="time" name="agenda_start_time" class="form-control" value="<?php echo htmlspecialchars($agendaWindow['start']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Orario fine</label>
                                    <input type="time" name="agenda_end_time" class="form-control" value="<?php echo htmlspecialchars($agendaWindow['end']); ?>" required>
                                </div>
                            </div>
                            <p class="text-muted mb-0">Gli orari vengono applicati alle nuove prenotazioni.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Report e Filtri</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Anno iniziale</label>
                                <input type="number" name="app_year_start" class="form-control" min="2000" max="<?php echo date('Y') + 1; ?>" value="<?php echo (int)$appYearStart; ?>" required>
                            </div>
                            <p class="text-muted mb-0">Definisce l’anno minimo per i filtri e i report.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-person-badge"></i> Istruttori</h5>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Elenco istruttori (uno per riga)</label>
                            <textarea name="agenda_instructors" class="form-control" rows="5" required><?php echo htmlspecialchars(implode("\n", $agendaInstructors)); ?></textarea>
                            <p class="text-muted mb-0 mt-2">Usato per la selezione in Agenda.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-journal-text"></i> Tipi Lezione</h5>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Tipi lezione (uno per riga)</label>
                            <textarea name="agenda_lesson_types" class="form-control" rows="5" required><?php echo htmlspecialchars(implode("\n", $agendaLessonTypes)); ?></textarea>
                            <p class="text-muted mb-0 mt-2">Disponibili nel menu di inserimento guida.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-wallet2"></i> Categorie Spese</h5>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Categorie (una per riga)</label>
                            <textarea name="expense_categories" class="form-control" rows="5" required><?php echo htmlspecialchars(implode("\n", $expenseCategories)); ?></textarea>
                            <p class="text-muted mb-0 mt-2">Usate nei filtri e nella creazione spese.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Salva impostazioni
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
