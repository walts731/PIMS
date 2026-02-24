<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: var(--primary-gradient);
    box-shadow: 2px 0 10px rgba(25, 27, 169, 0.1);
    transition: left 0.3s ease-in-out;
    z-index: 1040;
    overflow-y: auto;
}

.sidebar.active {
    left: 0;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
}

.sidebar-nav {
    padding: 1rem 0;
}

.sidebar-nav-item {
    display: block;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-weight: 500;
}

.sidebar-nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
}

.sidebar-nav-item.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.sidebar-nav-item i {
    width: 20px;
    margin-right: 0.75rem;
}

.sidebar-toggle {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1050;
    background: var(--primary-gradient);
    border: none;
    border-radius: var(--border-radius);
    color: white;
    padding: 0.75rem;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow);
}

.sidebar-toggle:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.sidebar-toggle.sidebar-active {
    left: 300px;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1035;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.sidebar-overlay.active {
    display: block;
    opacity: 1;
}

/* Main content shift when sidebar is active */
.main-wrapper {
    transition: margin-left 0.3s ease-in-out;
}

.main-wrapper.sidebar-active {
    margin-left: 280px;
}

.navbar {
    background: var(--primary-gradient);
    box-shadow: 0 2px 10px rgba(25, 27, 169, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: padding-left 0.3s ease-in-out;
    padding-left: 80px;
}

.navbar.sidebar-active {
    padding-left: 20px;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
}

.main-content {
    padding: 2rem;
    max-height: calc(100vh - 76px);
    overflow-y: auto;
    overflow-x: hidden;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
        max-height: calc(100vh - 60px);
    }

    .navbar-brand {
        font-size: 1.2rem;
    }

    .sidebar {
        width: 100%;
        left: -100%;
    }

    .main-wrapper.sidebar-active {
        margin-left: 0;
    }

    .navbar.sidebar-active {
        padding-left: 80px;
    }

    .sidebar-toggle.sidebar-active {
        left: 20px;
    }
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="sidebar-logo">
                <img src="../img/trans_logo.png" alt="PIMS Logo" class="img-fluid" style="max-height: 40px; border-radius: 8px;">
            </div>
            <div class="sidebar-title">
                <h6 class="mb-0 text-white">PIMS</h6>
                <small class="text-white-50">Main User Panel</small>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
        <a href="assets.php" class="sidebar-nav-item <?php echo $current_page == 'assets.php' ? 'active' : ''; ?>">
            <i class="bi bi-box-seam"></i>
            Assets
        </a>
        <a href="asset_items.php" class="sidebar-nav-item <?php echo $current_page == 'asset_items.php' ? 'active' : ''; ?>">
            <i class="bi bi-collection"></i>
            Asset Items
        </a>
        <a href="#" class="sidebar-nav-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="bi bi-key"></i>
            Change Password
        </a>
        <div class="sidebar-nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
            <i class="bi bi-box-arrow-right"></i>
            <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" style="color: inherit; text-decoration: none;">Logout</a>
        </div>
    </nav>
</aside>
