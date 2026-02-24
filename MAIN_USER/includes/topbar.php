<nav class="navbar navbar-expand-lg navbar-dark" id="mainNavbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <?php echo ucfirst($page_title ?? 'Dashboard'); ?>
        </a>

        <div class="navbar-nav ms-auto align-items-center">
            <div class="nav-item me-3">
                <div class="search-container position-relative">
                    <form class="d-flex" action="search_handler.php" method="GET" id="searchForm">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q" id="searchInput"
                                   placeholder="Search..." autocomplete="off"
                                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                            <button class="btn btn-outline-light" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <div class="nav-item me-2">
                    <a href="scan_qr.php" class="nav-link text-white" title="QR Scanner">
                        <i class="bi bi-qr-code-scan"></i>
                    </a>
                </div>
                <div class="nav-item me-2">
                    <a href="#" class="nav-link text-white" title="Notifications">
                        <i class="bi bi-bell"></i>
                    </a>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-3">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></div>
                            <div class="user-role">
                                <span class="badge bg-primary">Main User</span>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.search-container .form-control {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: var(--border-radius);
    width: 250px;
    transition: width 0.3s ease;
}

.search-container .form-control:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    color: white;
    width: 300px;
}

.search-container .form-control::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.search-container .btn-outline-light {
    border-color: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
}

.search-container .btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
}

@media (max-width: 768px) {
    .search-container .form-control {
        width: 180px;
    }

    .search-container .form-control:focus {
        width: 200px;
    }
}

@media (max-width: 576px) {
    .search-container .form-control {
        width: 150px;
    }

    .search-container .form-control:focus {
        width: 170px;
    }

    .search-container .form-control::placeholder {
        font-size: 0.8rem;
    }
}
</style>
