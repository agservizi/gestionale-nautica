<?php
/**
 * Audit Log - Visualizza attività (solo admin)
 */
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

function getFilterValue($value): string {
    if ($value === null) {
        return '';
    }
    if (is_array($value) || is_object($value)) {
        return '';
    }
    return trim((string) $value);
}

$action = getFilterValue($_GET['action'] ?? '');
$entity = getFilterValue($_GET['entity'] ?? '');
$user = getFilterValue($_GET['user'] ?? '');
$from = getFilterValue($_GET['from'] ?? '');
$to = getFilterValue($_GET['to'] ?? '');

$db = getDB();
$sql = "SELECT a.*, u.username FROM audit_log a LEFT JOIN utenti u ON a.user_id = u.id WHERE 1=1";
$params = [];

if ($action !== '') {
    $sql .= " AND a.action = ?";
    $params[] = $action;
}
if ($entity !== '') {
    $sql .= " AND a.entity = ?";
    $params[] = $entity;
}
if ($user !== '') {
    $sql .= " AND u.username LIKE ?";
    $params[] = '%' . $user . '%';
}
if ($from !== '') {
    $sql .= " AND DATE(a.created_at) >= ?";
    $params[] = $from;
}
if ($to !== '') {
    $sql .= " AND DATE(a.created_at) <= ?";
    $params[] = $to;
}

$sql .= " ORDER BY a.created_at DESC LIMIT 500";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$actions = ['login','logout','create','update','delete','upload','reset_password'];
$entities = ['cliente','pratica','pagamento','agenda','spesa','utente','allegato'];

function safeString($value, string $fallback = '-') : string {
    if ($value === null || $value === '') {
        return $fallback;
    }
    if (is_array($value) || is_object($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return (string) $value;
}

function formatAuditDetails($details): string {
    if ($details === null || $details === '') {
        return '-';
    }
    if (is_array($details) || is_object($details)) {
        return json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return (string) $details;
}
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div id="content" class="content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Audit Log</h1>
                <p class="text-muted">Ultimi 500 eventi</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Azione</label>
                        <select name="action" class="form-select">
                            <option value="">Tutte</option>
                            <?php foreach($actions as $a): ?>
                                <option value="<?php echo $a; ?>" <?php echo $action === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Entità</label>
                        <select name="entity" class="form-select">
                            <option value="">Tutte</option>
                            <?php foreach($entities as $e): ?>
                                <option value="<?php echo $e; ?>" <?php echo $entity === $e ? 'selected' : ''; ?>><?php echo $e; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Utente</label>
                        <input type="text" name="user" class="form-control" value="<?php echo htmlspecialchars(safeString($user, '')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dal</label>
                        <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars(safeString($from, '')); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Al</label>
                        <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars(safeString($to, '')); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Filtra</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Utente</th>
                                <th>Azione</th>
                                <th>Entità</th>
                                <th>ID</th>
                                <th>Dettagli</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($logs)): ?>
                                <tr><td colspan="7" class="text-center text-muted">Nessun evento</td></tr>
                            <?php else: ?>
                                <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td><?php echo formatDate($log['created_at'], 'd/m/Y H:i'); ?></td>
                                        <td><?php echo htmlspecialchars(safeString($log['username'] ?? '-')); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars(safeString($log['action'])); ?></span></td>
                                        <td><?php echo htmlspecialchars(safeString($log['entity'])); ?></td>
                                        <td><?php echo htmlspecialchars(safeString($log['entity_id'] ?? '-')); ?></td>
                                        <td><?php echo htmlspecialchars(formatAuditDetails($log['details'] ?? '-')); ?></td>
                                        <td><?php echo htmlspecialchars(safeString($log['ip_address'] ?? '-')); ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
