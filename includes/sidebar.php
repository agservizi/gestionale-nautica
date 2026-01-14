        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo" aria-label="<?php echo APP_NAME; ?>">
                    <img src="/assets/icons/logo.svg" alt="<?php echo APP_NAME; ?>">
                </div>
                <h3 class="sidebar-title">NautikaPro</h3>
            </div>
            
            <ul class="list-unstyled components">
                <li class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <a href="/pages/dashboard.php" class="sidebar-link" data-tooltip="Dashboard">
                        <i class="bi bi-speedometer2"></i>
                        <span class="link-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'clienti' || $current_page == 'cliente_dettaglio' ? 'active' : ''; ?>">
                    <a href="/pages/clienti.php" class="sidebar-link" data-tooltip="Clienti">
                        <i class="bi bi-people"></i>
                        <span class="link-text">Clienti</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'pratiche' || $current_page == 'pratica_dettaglio' ? 'active' : ''; ?>">
                    <a href="/pages/pratiche.php" class="sidebar-link" data-tooltip="Pratiche">
                        <i class="bi bi-file-earmark-text"></i>
                        <span class="link-text">Pratiche</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'pagamenti' ? 'active' : ''; ?>">
                    <a href="/pages/pagamenti.php" class="sidebar-link" data-tooltip="Pagamenti">
                        <i class="bi bi-credit-card"></i>
                        <span class="link-text">Pagamenti</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'agenda' ? 'active' : ''; ?>">
                    <a href="/pages/agenda.php" class="sidebar-link" data-tooltip="Agenda Guide">
                        <i class="bi bi-calendar3"></i>
                        <span class="link-text">Agenda Guide</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'spese' ? 'active' : ''; ?>">
                    <a href="/pages/spese.php" class="sidebar-link" data-tooltip="Spese">
                        <i class="bi bi-wallet2"></i>
                        <span class="link-text">Spese</span>
                    </a>
                </li>
                
                <li class="<?php echo $current_page == 'report' ? 'active' : ''; ?>">
                    <a href="/pages/report.php" class="sidebar-link" data-tooltip="Report">
                        <i class="bi bi-graph-up"></i>
                        <span class="link-text">Report</span>
                    </a>
                </li>

                <li class="<?php echo $current_page == 'profilo' ? 'active' : ''; ?>">
                    <a href="/pages/profilo.php" class="sidebar-link" data-tooltip="Profilo">
                        <i class="bi bi-person-circle"></i>
                        <span class="link-text">Profilo</span>
                    </a>
                </li>

                <?php if(isAdmin()): ?>
                    <li class="<?php echo $current_page == 'utenti' ? 'active' : ''; ?>">
                        <a href="/pages/utenti.php" class="sidebar-link" data-tooltip="Utenti">
                            <i class="bi bi-person-gear"></i>
                            <span class="link-text">Utenti</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page == 'audit' ? 'active' : ''; ?>">
                        <a href="/pages/audit.php" class="sidebar-link" data-tooltip="Audit Log">
                            <i class="bi bi-shield-check"></i>
                            <span class="link-text">Audit Log</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page == 'backup' ? 'active' : ''; ?>">
                        <a href="/pages/backup.php" class="sidebar-link" data-tooltip="Backup/Restore">
                            <i class="bi bi-database-down"></i>
                            <span class="link-text">Backup/Restore</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page == 'automation' ? 'active' : ''; ?>">
                        <a href="/pages/automation.php" class="sidebar-link" data-tooltip="Automazioni">
                            <i class="bi bi-gear"></i>
                            <span class="link-text">Automazioni</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if(!empty($notifications)): ?>
                <div class="p-3">
                    <div class="card bg-dark text-white sidebar-reminders">
                        <div class="card-body p-3">
                            <small class="d-block">Promemoria</small>
                            <div class="sidebar-reminder-item">
                                <span>Guide oggi</span>
                                <span class="badge bg-gold"><?php echo $notifications['today_guides']; ?></span>
                            </div>
                            <div class="sidebar-reminder-item">
                                <span>Guide domani</span>
                                <span class="badge bg-gold"><?php echo $notifications['tomorrow_guides']; ?></span>
                            </div>
                            <div class="sidebar-reminder-item">
                                <span>Pratiche scoperte</span>
                                <span class="badge bg-danger"><?php echo $notifications['pratiche_scoperte']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="sidebar-footer">
                <div class="mb-2">
                    <small class="text-muted">v<?php echo APP_VERSION; ?></small>
                </div>
                <?php $currentUser = currentUser(); ?>
                <?php if($currentUser): ?>
                    <div class="d-flex flex-column gap-1">
                        <small class="text-light">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                        </small>
                        <small class="text-muted">Ruolo: <?php echo htmlspecialchars($currentUser['ruolo']); ?></small>
                        <a href="/logout.php" class="btn btn-sm btn-outline-light mt-1">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
