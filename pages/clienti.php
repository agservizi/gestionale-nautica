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

// Gestione ricerca
$search = $_GET['search'] ?? '';
$clienti = getClienti($search);
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
                <h1 class="h3">Clienti (<?php echo count($clienti); ?>)</h1>
                <a href="/pages/cliente_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Nuovo Cliente
                </a>
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
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($clienti)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Nessun cliente trovato</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($clienti as $cliente): ?>
                                    <tr>
                                        <td><?php echo $cliente['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cliente['cognome']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['telefono'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></td>
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
