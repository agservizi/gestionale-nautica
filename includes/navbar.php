<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom sticky-top app-navbar">
    <div class="container-fluid">
        <?php
        $isDeveloper = function_exists('isDeveloper') && isDeveloper();
        $pageLabels = [
            'dashboard' => 'Dashboard',
            'clienti' => 'Clienti',
            'cliente_form' => 'Nuovo cliente',
            'cliente_dettaglio' => 'Scheda cliente',
            'pratiche' => 'Pratiche',
            'pratica_form' => 'Nuova pratica',
            'pratica_dettaglio' => 'Dettaglio pratica',
            'pagamenti' => 'Pagamenti',
            'agenda' => 'Agenda',
            'agenda_form' => 'Nuova guida',
            'spese' => 'Spese',
            'report' => 'Report',
            'profilo' => 'Profilo',
            'utenti' => 'Utenti',
            'impostazioni' => 'Impostazioni',
            'audit' => 'Audit',
            'backup' => 'Backup',
            'automation' => 'Automazioni',
            'diagnostica' => 'Diagnostica',
        ];
        $currentPageLabel = $pageLabels[$current_page] ?? ucfirst(str_replace('_', ' ', $current_page));
        $todayGuides = (int)($notifications['today_guides'] ?? 0);
        $openAlerts = (int)($notifications['pratiche_scoperte'] ?? 0);
        $notificationItems = $uiNotifications ?? [];
        $notificationCount = count($notificationItems);
        $notificationGroups = [
            'urgent' => ['label' => 'Urgenti', 'items' => []],
            'today' => ['label' => 'Oggi', 'items' => []],
            'follow' => ['label' => 'Da seguire', 'items' => []],
        ];

        foreach ($notificationItems as $item) {
            $priority = (int)($item['priority'] ?? 3);
            if ($priority <= 1) {
                $notificationGroups['urgent']['items'][] = $item;
            } elseif ($priority === 2) {
                $notificationGroups['today']['items'][] = $item;
            } else {
                $notificationGroups['follow']['items'][] = $item;
            }
        }
        ?>

        <div class="topbar-shell">
            <div class="topbar-panel topbar-panel-brand">
                <button type="button" id="sidebarCollapseTop" class="btn btn-primary sidebar-toggle-btn" aria-label="Apri menu laterale">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-brand-copy">
                    <div class="topbar-kicker">Workspace</div>
                    <div class="topbar-page"><?php echo htmlspecialchars($currentPageLabel); ?></div>
                </div>
                <?php if(!$isDeveloper): ?>
                    <div class="topbar-glance d-none d-xl-flex">
                        <span class="topbar-pill">
                            <i class="bi bi-calendar3"></i>
                            Guide oggi <?php echo $todayGuides; ?>
                        </span>
                        <span class="topbar-pill topbar-pill-alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            Scoperte <?php echo $openAlerts; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="topbar-panel topbar-panel-actions">
                <?php if(!$isDeveloper): ?>
                    <?php $apiBase = rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>
                    <form class="d-flex position-relative topbar-search-wrap" id="searchForm" data-api-base="<?php echo htmlspecialchars($apiBase); ?>">
                        <span class="topbar-search-icon"><i class="bi bi-search"></i></span>
                        <input class="form-control" type="search" placeholder="Cerca cliente, pratica, pagamento o codice fiscale..." id="searchCliente" autocomplete="off">
                        <button class="btn btn-outline-primary topbar-search-btn" type="submit" aria-label="Avvia ricerca">
                            <i class="bi bi-search"></i>
                        </button>
                        <div id="searchResults" class="search-suggest-list list-group position-absolute"></div>
                    </form>
                    <div class="topbar-notification-wrap" data-ui-notifications>
                        <button type="button" class="btn btn-outline-secondary icon-btn topbar-notification-btn" data-ui-notifications-toggle aria-label="Apri notifiche">
                            <i class="bi bi-bell"></i>
                            <?php if($notificationCount > 0): ?>
                                <span class="topbar-notification-count"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="topbar-notification-panel" data-ui-notifications-panel>
                            <div class="topbar-notification-panel__header">
                                <div>
                                    <strong>Centro notifiche</strong>
                                    <small>Scadenze e azioni utili dentro l'app</small>
                                </div>
                                <a href="/pages/dashboard.php#alert-operativi" class="btn btn-outline-primary btn-sm topbar-notification-link-all">Leggi tutte</a>
                            </div>
                            <div class="topbar-notification-panel__body">
                                <?php if(empty($notificationItems)): ?>
                                    <div class="topbar-notification-empty">Nessuna notifica operativa in questo momento.</div>
                                <?php else: ?>
                                    <?php foreach($notificationGroups as $group): ?>
                                        <?php if(empty($group['items'])) continue; ?>
                                        <section class="topbar-notification-group">
                                            <div class="topbar-notification-group__title"><?php echo htmlspecialchars($group['label']); ?></div>
                                            <div class="topbar-notification-group__items">
                                                <?php foreach($group['items'] as $item): ?>
                                                    <a href="<?php echo htmlspecialchars($item['href']); ?>" class="topbar-notification-item topbar-notification-item--<?php echo htmlspecialchars($item['tone']); ?>">
                                                        <span class="topbar-notification-item__icon"><i class="bi bi-<?php echo htmlspecialchars($item['icon']); ?>"></i></span>
                                                        <span class="topbar-notification-item__body">
                                                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                            <small><?php echo htmlspecialchars($item['description']); ?></small>
                                                        </span>
                                                        <span class="topbar-notification-item__meta"><?php echo htmlspecialchars($item['meta']); ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <a href="/logout.php" class="btn btn-outline-danger icon-btn topbar-logout-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Logout" aria-label="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</nav>
