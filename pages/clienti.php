<?php
/**
 * Clienti - Lista e gestione anagrafica clienti
 */
require_once __DIR__ . '/../includes/header.php';

// Gestione richieste POST (delete)
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } elseif(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'delete':
                deleteCliente($_POST['id']);
                logAudit('delete', 'cliente', $_POST['id']);
                $message = 'Cliente eliminato con successo!';
                $message_type = 'success';
                break;
        }
    }
}

// Gestione ricerca + paginazione
$search = $_GET['search'] ?? '';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$totalClienti = countClientiFiltered($search);
$totalPages = max(1, (int)ceil($totalClienti / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}
$clienti = getClienti($search, $perPage, $offset);
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Content -->
<div id="content" class="content">
    
    <!-- Navbar -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="container-fluid py-4">
        
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1 class="h3">Clienti (<?php echo $totalClienti; ?>)</h1>
                <a href="/pages/cliente_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Nuovo Cliente
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-9">
                        <label class="form-label">Ricerca</label>
                        <input type="search" name="search" class="form-control" placeholder="Nome, cognome, email o codice fiscale" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Cerca</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabella Clienti -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cognome</th>
                                <th>Nome</th>
                                <th>Telefono</th>
                                <th>Email</th>
                                <th>Codice Fiscale</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($clienti)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Nessun cliente trovato</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($clienti as $cliente): ?>
                                    <tr>
                                        <td><?php echo $cliente['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cliente['cognome']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['telefono'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['codice_fiscale'] ?? '-'); ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="cliente_dettaglio.php?id=<?php echo $cliente['id']; ?>" 
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" data-bs-placement="top" title="Dettaglio">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a class="btn btn-sm btn-warning" 
                                                    href="/pages/cliente_form.php?id=<?php echo $cliente['id']; ?>"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Modifica">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteCliente(<?php echo $cliente['id']; ?>)"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Elimina">
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

        <?php if($totalPages > 1): ?>
            <nav aria-label="Paginazione clienti" class="mt-3">
                <ul class="pagination justify-content-end">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(['search' => $search, 'page' => $page - 1]); ?>" aria-label="Precedente">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(['search' => $search, 'page' => $p]); ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(['search' => $search, 'page' => $page + 1]); ?>" aria-label="Successiva">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
        
    </div>
    
</div>

<!-- Modal Delete Confirm -->
<div class="modal fade" id="modalDeleteCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteClienteId">
                <div class="modal-header">
                    <h5 class="modal-title">Conferma Eliminazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Sei sicuro di voler eliminare questo cliente?</p>
                    <p class="text-danger"><small>Attenzione: verranno eliminate anche tutte le pratiche e i pagamenti associati.</small></p>
                    <div class="mb-3">
                        <label for="deleteClienteConfirm" class="form-label">Digita ELIMINA per confermare</label>
                        <input type="text" class="form-control" id="deleteClienteConfirm" placeholder="ELIMINA" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger" id="deleteClienteSubmit" disabled>Elimina</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
// Funzione per eliminare cliente
function deleteCliente(id) {
    document.getElementById('deleteClienteId').value = id;
    document.getElementById('deleteClienteConfirm').value = '';
    document.getElementById('deleteClienteSubmit').disabled = true;
    new bootstrap.Modal(document.getElementById('modalDeleteCliente')).show();
}

document.getElementById('deleteClienteConfirm').addEventListener('input', function() {
    document.getElementById('deleteClienteSubmit').disabled = this.value.trim().toUpperCase() !== 'ELIMINA';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
