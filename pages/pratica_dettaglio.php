<?php
/**
 * Dettaglio Pratica con gestione completa
 */
require_once __DIR__ . '/../includes/header.php';

$pratica_id = $_GET['id'] ?? 0;
$pratica = getPraticaById($pratica_id);

if(!$pratica) {
    header('Location: pratiche.php');
    exit;
}

// Gestione POST per pagamenti e aggiornamenti
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = 'Sessione scaduta. Riprova.';
        $message_type = 'danger';
    } elseif(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_pagamento':
                if (empty($_POST['importo']) || empty($_POST['tipo_pagamento']) || empty($_POST['metodo_pagamento']) || empty($_POST['data_pagamento'])) {
                    $message = 'Importo, tipo, metodo e data sono obbligatori.';
                    $message_type = 'danger';
                    break;
                }
                createPagamento($_POST);
                logAudit('create', 'pagamento', null, 'pratica_id=' . $pratica_id);
                $message = 'Pagamento registrato con successo!';
                $message_type = 'success';
                // Ricarica pratica
                $pratica = getPraticaById($pratica_id);
                break;
            case 'update_pratica':
                updatePratica($pratica_id, $_POST);
                logAudit('update', 'pratica', $pratica_id);
                $message = 'Pratica aggiornata con successo!';
                $message_type = 'success';
                $pratica = getPraticaById($pratica_id);
                break;
            case 'upload_allegato':
                try {
                    $user = currentUser();
                    savePraticaAllegato($pratica_id, $_FILES['allegato'] ?? null, $user['id'] ?? null);
                    logAudit('upload', 'allegato', $pratica_id);
                    $message = 'Allegato caricato con successo!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = 'danger';
                }
                break;
            case 'delete_allegato':
                if (deletePraticaAllegato($_POST['allegato_id'], $pratica_id)) {
                    logAudit('delete', 'allegato', $_POST['allegato_id']);
                    $message = 'Allegato eliminato con successo!';
                    $message_type = 'success';
                } else {
                    $message = 'Impossibile eliminare l\'allegato.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Carica pagamenti della pratica
$pagamenti = getPagamenti(['pratica_id' => $pratica_id]);

// Allegati
$allegati = getPraticaAllegati($pratica_id);

// Carica cliente
$cliente = getClienteById($pratica['cliente_id']);
$altroSottocategorie = getAltroSottocategorie();
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Content -->
<div id="content" class="content">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="sidebarCollapseTop" class="btn btn-primary sidebar-toggle-btn">
                    <i class="bi bi-list"></i>
                </button>
                <a href="pratiche.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Torna alle Pratiche
                </a>
            </div>
            <div class="ms-auto">
                <form class="d-flex" id="searchForm">
                    <input class="form-control me-2" type="search" placeholder="Cerca cliente..." id="searchCliente">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>
    
    <!-- Page Content -->
    <div class="container-fluid py-4">
        
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Intestazione Pratica -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h1 class="h3 mb-2">Pratica #<?php echo $pratica['id']; ?></h1>
                                <h5 class="mb-2">
                                    <i class="bi bi-person"></i> 
                                    <a href="cliente_dettaglio.php?id=<?php echo $cliente['id']; ?>">
                                        <?php echo htmlspecialchars($pratica['cliente_nome']); ?>
                                    </a>
                                </h5>
                                <p class="mb-1">
                                    <?php if($pratica['cliente_telefono']): ?>
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($pratica['cliente_telefono']); ?>
                                    <?php endif; ?>
                                    <?php if($pratica['cliente_email']): ?>
                                        <span class="ms-3"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($pratica['cliente_email']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Tipo:</strong> <?php echo htmlspecialchars($pratica['tipo_pratica']); ?><br>
                                    <strong>Data Apertura:</strong> <?php echo formatDate($pratica['data_apertura']); ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="mb-3">
                                    <?php echo getStatoPraticaBadge($pratica['stato']); ?>
                                    <?php echo getStatoEconomicoBadge($pratica); ?>
                                </div>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Totale</small>
                                        <h5><?php echo formatMoney($pratica['totale_previsto']); ?></h5>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Pagato</small>
                                        <h5 class="text-success"><?php echo formatMoney($pratica['totale_pagato']); ?></h5>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Residuo</small>
                                        <h5 class="<?php echo $pratica['residuo'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                            <?php echo formatMoney($pratica['residuo']); ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dettagli">
                    <i class="bi bi-info-circle"></i> Dettagli
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pagamenti">
                    <i class="bi bi-credit-card"></i> Pagamenti (<?php echo count($pagamenti); ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#documenti">
                    <i class="bi bi-file-earmark"></i> Documenti
                </button>
            </li>
        </ul>
        
        <div class="tab-content">
            
            <!-- Tab Dettagli -->
            <div class="tab-pane fade show active" id="dettagli">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Informazioni Pratica</h5>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalModifica">
                            <i class="bi bi-pencil"></i> Modifica
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Dettagli Generali</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="150">Stato:</th>
                                        <td><?php echo getStatoPraticaBadge($pratica['stato']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tipo Pratica:</th>
                                        <td><?php echo htmlspecialchars($pratica['tipo_pratica']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Data Apertura:</th>
                                        <td><?php echo formatDate($pratica['data_apertura']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Dettagli Economici</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="150">Totale Previsto:</th>
                                        <td><?php echo formatMoney($pratica['totale_previsto']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Totale Pagato:</th>
                                        <td class="text-success"><?php echo formatMoney($pratica['totale_pagato']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Residuo:</th>
                                        <td class="<?php echo $pratica['residuo'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                            <strong><?php echo formatMoney($pratica['residuo']); ?></strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Dettagli specifici per tipo pratica -->
                        <?php if(strpos($pratica['tipo_pratica'], 'Patente') !== false && $pratica['tipo_pratica'] !== 'Rinnovo'): ?>
                            <hr>
                            <h6>Dettagli Conseguimento Patente</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="200">Data Esame:</th>
                                    <td><?php echo formatDate($pratica['data_esame']); ?></td>
                                </tr>
                                <tr>
                                    <th>Esito Esame:</th>
                                    <td>
                                        <?php if($pratica['esito_esame']): ?>
                                            <span class="badge bg-<?php echo $pratica['esito_esame'] == 'Superato' ? 'success' : 'danger'; ?>">
                                                <?php echo $pratica['esito_esame']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">In attesa</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Data Conseguimento:</th>
                                    <td><?php echo formatDate($pratica['data_conseguimento']); ?></td>
                                </tr>
                                <tr>
                                    <th>Numero Patente:</th>
                                    <td><?php echo htmlspecialchars($pratica['numero_patente'] ?? '-'); ?></td>
                                </tr>
                            </table>
                        <?php endif; ?>

                        <?php if($pratica['tipo_pratica'] === 'Rinnovo'): ?>
                            <hr>
                            <h6>Dettagli Rinnovo</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="200">Data Richiesta:</th>
                                    <td><?php echo formatDate($pratica['data_richiesta_rinnovo']); ?></td>
                                </tr>
                                <tr>
                                    <th>Data Completamento:</th>
                                    <td><?php echo formatDate($pratica['data_completamento_rinnovo']); ?></td>
                                </tr>
                                <tr>
                                    <th>Note Operative:</th>
                                    <td><?php echo htmlspecialchars($pratica['note_rinnovo'] ?? '-'); ?></td>
                                </tr>
                            </table>
                        <?php endif; ?>

                        <?php if($pratica['tipo_pratica'] === 'Duplicato'): ?>
                            <hr>
                            <h6>Dettagli Duplicato</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="200">Motivo:</th>
                                    <td><?php echo htmlspecialchars($pratica['motivo_duplicato'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Dettaglio:</th>
                                    <td><?php echo htmlspecialchars($pratica['motivo_duplicato_dettaglio'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Data Richiesta:</th>
                                    <td><?php echo formatDate($pratica['data_richiesta_duplicato']); ?></td>
                                </tr>
                                <tr>
                                    <th>Data Chiusura:</th>
                                    <td><?php echo formatDate($pratica['data_chiusura_duplicato']); ?></td>
                                </tr>
                            </table>
                        <?php endif; ?>

                        <?php if($pratica['tipo_pratica'] === 'Altro'): ?>
                            <hr>
                            <h6>Dettagli Altro</h6>
                            <p><?php echo htmlspecialchars($pratica['tipo_altro_dettaglio'] ?? '-'); ?></p>
                        <?php endif; ?>
                        
                        <?php if($pratica['note']): ?>
                            <hr>
                            <h6>Note</h6>
                            <p><?php echo nl2br(htmlspecialchars($pratica['note'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tab Pagamenti -->
            <div class="tab-pane fade" id="pagamenti">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Storico Pagamenti</h5>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPagamento">
                            <i class="bi bi-plus-lg"></i> Nuovo Pagamento
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if(empty($pagamenti)): ?>
                            <p class="text-muted">Nessun pagamento registrato per questa pratica</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Tipo</th>
                                            <th>Metodo</th>
                                            <th>Importo</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($pagamenti as $pag): ?>
                                            <tr>
                                                <td><?php echo formatDate($pag['data_pagamento']); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo $pag['tipo_pagamento']; ?></span></td>
                                                <td><span class="badge bg-info"><?php echo $pag['metodo_pagamento']; ?></span></td>
                                                <td class="text-success"><strong><?php echo formatMoney($pag['importo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($pag['note'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <td colspan="3" class="text-end"><strong>Totale Pagato:</strong></td>
                                            <td class="text-success"><strong><?php echo formatMoney($pratica['totale_pagato']); ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tab Documenti -->
            <div class="tab-pane fade" id="documenti">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Documenti e Allegati</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="mb-4">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="upload_allegato">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label">Carica allegato (PDF, JPG, PNG - max 5MB)</label>
                                    <input type="file" name="allegato" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-upload"></i> Carica
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if(empty($allegati)): ?>
                            <p class="text-muted">Nessun allegato presente</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Tipo</th>
                                            <th>Dimensione</th>
                                            <th>Caricato da</th>
                                            <th>Data</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($allegati as $allegato): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($allegato['filename_original']); ?></td>
                                                <td><?php echo htmlspecialchars($allegato['mime_type']); ?></td>
                                                <td><?php echo number_format($allegato['file_size'] / 1024, 1); ?> KB</td>
                                                <td><?php echo htmlspecialchars($allegato['uploaded_by_name'] ?? '-'); ?></td>
                                                <td><?php echo formatDate($allegato['data_upload']); ?></td>
                                                <td>
                                                    <a class="btn btn-sm btn-info" target="_blank" href="/uploads/<?php echo urlencode($allegato['filename_stored']); ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Eliminare questo allegato?')">
                                                        <?php echo csrf_input(); ?>
                                                        <input type="hidden" name="action" value="delete_allegato">
                                                        <input type="hidden" name="allegato_id" value="<?php echo $allegato['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
    
</div>

<!-- Modal Nuovo Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="add_pagamento">
                <input type="hidden" name="pratica_id" value="<?php echo $pratica_id; ?>">
                <input type="hidden" name="cliente_id" value="<?php echo $pratica['cliente_id']; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Registra Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Residuo da pagare:</strong> <?php echo formatMoney($pratica['residuo']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Importo *</label>
                        <input type="number" name="importo" class="form-control" 
                               step="0.01" min="0" value="<?php echo max(0, $pratica['residuo']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo Pagamento *</label>
                        <select name="tipo_pagamento" class="form-select" required>
                            <option value="Acconto">Acconto</option>
                            <option value="Rata">Rata</option>
                            <option value="Saldo" <?php echo $pratica['residuo'] > 0 ? 'selected' : ''; ?>>Saldo</option>
                            <option value="Pagamento unico">Pagamento unico</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Metodo Pagamento *</label>
                        <select name="metodo_pagamento" class="form-select" required>
                            <option value="Contanti">Contanti</option>
                            <option value="POS">POS</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data Pagamento *</label>
                        <input type="date" name="data_pagamento" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Registra Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Pratica -->
<div class="modal fade" id="modalModifica" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="update_pratica">
                <input type="hidden" name="id" value="<?php echo $pratica_id; ?>">
                <input type="hidden" name="cliente_id" value="<?php echo $pratica['cliente_id']; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Modifica Pratica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data Apertura *</label>
                            <input type="date" name="data_apertura" class="form-control" 
                                   value="<?php echo $pratica['data_apertura']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stato *</label>
                            <select name="stato" class="form-select" required>
                                <option value="Aperta" <?php echo $pratica['stato'] == 'Aperta' ? 'selected' : ''; ?>>Aperta</option>
                                <option value="In corso" <?php echo $pratica['stato'] == 'In corso' ? 'selected' : ''; ?>>In corso</option>
                                <option value="Completata" <?php echo $pratica['stato'] == 'Completata' ? 'selected' : ''; ?>>Completata</option>
                                <option value="Annullata" <?php echo $pratica['stato'] == 'Annullata' ? 'selected' : ''; ?>>Annullata</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Tipo Pratica *</label>
                            <select name="tipo_pratica" class="form-select" required id="tipoPratica">
                                <option value="Patente entro 12 miglia" <?php echo $pratica['tipo_pratica'] == 'Patente entro 12 miglia' ? 'selected' : ''; ?>>Patente entro 12 miglia</option>
                                <option value="Patente oltre 12 miglia" <?php echo $pratica['tipo_pratica'] == 'Patente oltre 12 miglia' ? 'selected' : ''; ?>>Patente oltre 12 miglia</option>
                                <option value="Patente D1" <?php echo $pratica['tipo_pratica'] == 'Patente D1' ? 'selected' : ''; ?>>Patente D1</option>
                                <option value="Rinnovo" <?php echo $pratica['tipo_pratica'] == 'Rinnovo' ? 'selected' : ''; ?>>Rinnovo</option>
                                <option value="Duplicato" <?php echo $pratica['tipo_pratica'] == 'Duplicato' ? 'selected' : ''; ?>>Duplicato</option>
                                <option value="Altro" <?php echo $pratica['tipo_pratica'] == 'Altro' ? 'selected' : ''; ?>>Altro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Totale Previsto</label>
                            <input type="number" name="totale_previsto" class="form-control" 
                                   step="0.01" min="0" value="<?php echo $pratica['totale_previsto']; ?>">
                        </div>
                    </div>

                    <!-- Campi dinamici: Conseguimento patente -->
                    <div id="campi_conseguimento" style="display:none;">
                        <hr>
                        <h6>Conseguimento Patente</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Data Esame</label>
                                <input type="date" name="data_esame" class="form-control" value="<?php echo $pratica['data_esame'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Esito</label>
                                <select name="esito_esame" class="form-select">
                                    <option value="In attesa" <?php echo ($pratica['esito_esame'] ?? '') == 'In attesa' ? 'selected' : ''; ?>>In attesa</option>
                                    <option value="Superato" <?php echo ($pratica['esito_esame'] ?? '') == 'Superato' ? 'selected' : ''; ?>>Superato</option>
                                    <option value="Non superato" <?php echo ($pratica['esito_esame'] ?? '') == 'Non superato' ? 'selected' : ''; ?>>Non superato</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data Conseguimento</label>
                                <input type="date" name="data_conseguimento" class="form-control" value="<?php echo $pratica['data_conseguimento'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Numero Patente</label>
                                <input type="text" name="numero_patente" class="form-control" value="<?php echo htmlspecialchars($pratica['numero_patente'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Note/Allegati</label>
                                <input type="text" name="allegati" class="form-control" value="<?php echo htmlspecialchars($pratica['allegati'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Campi dinamici: Rinnovo -->
                    <div id="campi_rinnovo" style="display:none;">
                        <hr>
                        <h6>Rinnovo</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Data Richiesta</label>
                                <input type="date" name="data_richiesta_rinnovo" class="form-control" value="<?php echo $pratica['data_richiesta_rinnovo'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data Completamento</label>
                                <input type="date" name="data_completamento_rinnovo" class="form-control" value="<?php echo $pratica['data_completamento_rinnovo'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note Operative</label>
                            <textarea name="note_rinnovo" class="form-control" rows="2"><?php echo htmlspecialchars($pratica['note_rinnovo'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Campi dinamici: Duplicato -->
                    <div id="campi_duplicato" style="display:none;">
                        <hr>
                        <h6>Duplicato</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Motivo</label>
                                <select name="motivo_duplicato" class="form-select">
                                    <option value="Smarrimento" <?php echo ($pratica['motivo_duplicato'] ?? '') == 'Smarrimento' ? 'selected' : ''; ?>>Smarrimento</option>
                                    <option value="Deterioramento" <?php echo ($pratica['motivo_duplicato'] ?? '') == 'Deterioramento' ? 'selected' : ''; ?>>Deterioramento</option>
                                    <option value="Altro" <?php echo ($pratica['motivo_duplicato'] ?? '') == 'Altro' ? 'selected' : ''; ?>>Altro</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Dettaglio</label>
                                <input type="text" name="motivo_duplicato_dettaglio" class="form-control" value="<?php echo htmlspecialchars($pratica['motivo_duplicato_dettaglio'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Data Richiesta</label>
                                <input type="date" name="data_richiesta_duplicato" class="form-control" value="<?php echo $pratica['data_richiesta_duplicato'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data Chiusura</label>
                                <input type="date" name="data_chiusura_duplicato" class="form-control" value="<?php echo $pratica['data_chiusura_duplicato'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Campi dinamici: Altro -->
                    <div id="campi_altro" style="display:none;">
                        <hr>
                        <h6>Altro</h6>
                        <div class="mb-3">
                            <label class="form-label">Descrizione / Sotto-categoria</label>
                            <input type="text" name="tipo_altro_dettaglio" class="form-control" list="altroSottocategorie" value="<?php echo htmlspecialchars($pratica['tipo_altro_dettaglio'] ?? ''); ?>">
                            <datalist id="altroSottocategorie">
                                <?php foreach($altroSottocategorie as $sottocat): ?>
                                    <option value="<?php echo htmlspecialchars($sottocat); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="3"><?php echo htmlspecialchars($pratica['note'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Campi aggiuntivi in base al tipo -->
                    <?php if(strpos($pratica['tipo_pratica'], 'Patente') !== false): ?>
                        <hr>
                        <h6>Dettagli Esame</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Data Esame</label>
                                <input type="date" name="data_esame" class="form-control" 
                                       value="<?php echo $pratica['data_esame'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Esito</label>
                                <select name="esito_esame" class="form-select">
                                    <option value="">In attesa</option>
                                    <option value="Superato" <?php echo $pratica['esito_esame'] == 'Superato' ? 'selected' : ''; ?>>Superato</option>
                                    <option value="Non superato" <?php echo $pratica['esito_esame'] == 'Non superato' ? 'selected' : ''; ?>>Non superato</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Data Conseguimento</label>
                                <input type="date" name="data_conseguimento" class="form-control" 
                                       value="<?php echo $pratica['data_conseguimento'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Numero Patente</label>
                                <input type="text" name="numero_patente" class="form-control" 
                                       value="<?php echo htmlspecialchars($pratica['numero_patente'] ?? ''); ?>">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
