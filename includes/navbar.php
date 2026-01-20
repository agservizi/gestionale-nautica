<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom sticky-top">
    <div class="container-fluid">
        <button type="button" id="sidebarCollapseTop" class="btn btn-primary sidebar-toggle-btn">
            <i class="bi bi-list"></i>
        </button>

        <div class="ms-auto d-flex align-items-center gap-2">
            <?php $isDeveloper = function_exists('isDeveloper') && isDeveloper(); ?>
            <?php if(!$isDeveloper): ?>
                <?php $apiBase = rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>
                <form class="d-flex position-relative" id="searchForm" data-api-base="<?php echo htmlspecialchars($apiBase); ?>">
                    <input class="form-control me-2" type="search" placeholder="Cerca cliente..." id="searchCliente" autocomplete="off">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    <div id="searchResults" class="search-suggest-list list-group position-absolute"></div>
                </form>
            <?php endif; ?>
            <a href="/logout.php" class="btn btn-outline-danger icon-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</nav>
