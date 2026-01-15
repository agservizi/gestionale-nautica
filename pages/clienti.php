<?php
/**
 * Clienti - Lista e gestione anagrafica clienti
 */
require_once __DIR__ . '/../includes/header.php';

// Gestione richieste POST (create/update/delete)
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } elseif(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                $nome = trim($_POST['nome'] ?? '');
                $cognome = trim($_POST['cognome'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $tipo_pratica = trim($_POST['tipo_pratica'] ?? '');
                if ($nome === '' || $cognome === '') {
                    $message = 'Nome e cognome sono obbligatori.';
                    $message_type = 'danger';
                    break;
                }
                if ($tipo_pratica === '') {
                    $message = 'Tipo pratica è obbligatorio.';
                    $message_type = 'danger';
                    break;
                }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Email non valida.';
                    $message_type = 'danger';
                    break;
                }
                $id = createCliente($_POST);
                logAudit('create', 'cliente', $id, $cognome . ' ' . $nome);
                $message = 'Cliente creato con successo!';
                $message_type = 'success';
                break;
            case 'update':
                $nome = trim($_POST['nome'] ?? '');
                $cognome = trim($_POST['cognome'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $tipo_pratica = trim($_POST['tipo_pratica'] ?? '');
                if ($nome === '' || $cognome === '') {
                    $message = 'Nome e cognome sono obbligatori.';
                    $message_type = 'danger';
                    break;
                }
                if ($tipo_pratica === '') {
                    $message = 'Tipo pratica è obbligatorio.';
                    $message_type = 'danger';
                    break;
                }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Email non valida.';
                    $message_type = 'danger';
                    break;
                }
                updateCliente($_POST['id'], $_POST);
                logAudit('update', 'cliente', $_POST['id']);
                $message = 'Cliente aggiornato con successo!';
                $message_type = 'success';
                break;
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
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                    <i class="bi bi-plus-lg"></i> Nuovo Cliente
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
                                                <button class="btn btn-sm btn-warning" 
                                                    onclick="editCliente(<?php echo htmlspecialchars(json_encode($cliente)); ?>)"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Modifica">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
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

<!-- Modal Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formCliente">
                <?php echo csrf_input(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalClienteTitle">Nuovo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="clienteAction" value="create">
                    <input type="hidden" name="id" id="clienteId">
                    
                    <div class="mb-3">
                        <label for="clienteNome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="clienteNome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clienteCognome" class="form-label">Cognome *</label>
                        <input type="text" class="form-control" id="clienteCognome" name="cognome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clienteTelefono" class="form-label">Telefono</label>
                        <input type="text" class="form-control" id="clienteTelefono" name="telefono">
                    </div>
                    
                    <div class="mb-3">
                        <label for="clienteEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="clienteEmail" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="clienteTipoPratica" class="form-label">Tipo Pratica *</label>
                        <select class="form-select" id="clienteTipoPratica" name="tipo_pratica" required>
                            <option value="">-- Seleziona --</option>
                            <option value="Patente entro 12 miglia">Patente entro 12 miglia</option>
                            <option value="Patente oltre 12 miglia">Patente oltre 12 miglia</option>
                            <option value="Patente D1">Patente D1</option>
                            <option value="Rinnovo">Rinnovo</option>
                            <option value="Duplicato">Duplicato</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="clienteNumeroPatente" class="form-label">Numero Patente</label>
                            <input type="text" class="form-control" id="clienteNumeroPatente" name="numero_patente">
                        </div>
                        <div class="col-md-4">
                            <label for="clienteDataConseguimento" class="form-label">Data Conseguimento</label>
                            <input type="date" class="form-control" id="clienteDataConseguimento" name="data_conseguimento_patente">
                        </div>
                        <div class="col-md-4">
                            <label for="clienteDataScadenza" class="form-label">Data Scadenza</label>
                            <input type="date" class="form-control" id="clienteDataScadenza" name="data_scadenza_patente">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clienteNote" class="form-label">Note</label>
                        <textarea class="form-control" id="clienteNote" name="note" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
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
// Funzione per modificare cliente
function editCliente(cliente) {
    document.getElementById('modalClienteTitle').textContent = 'Modifica Cliente';
    document.getElementById('clienteAction').value = 'update';
    document.getElementById('clienteId').value = cliente.id;
    document.getElementById('clienteNome').value = cliente.nome;
    document.getElementById('clienteCognome').value = cliente.cognome;
    document.getElementById('clienteTelefono').value = cliente.telefono || '';
    document.getElementById('clienteEmail').value = cliente.email || '';
    document.getElementById('clienteTipoPratica').value = cliente.tipo_pratica || '';
    document.getElementById('clienteNumeroPatente').value = cliente.numero_patente || '';
    document.getElementById('clienteDataConseguimento').value = cliente.data_conseguimento_patente || '';
    document.getElementById('clienteDataScadenza').value = cliente.data_scadenza_patente || '';
    document.getElementById('clienteNote').value = cliente.note || '';
    
    new bootstrap.Modal(document.getElementById('modalCliente')).show();
}

// Funzione per eliminare cliente
function deleteCliente(id) {
    document.getElementById('deleteClienteId').value = id;
    new bootstrap.Modal(document.getElementById('modalDeleteCliente')).show();
}

// Reset form quando il modal viene chiuso
document.getElementById('modalCliente').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formCliente').reset();
    document.getElementById('modalClienteTitle').textContent = 'Nuovo Cliente';
    document.getElementById('clienteAction').value = 'create';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
