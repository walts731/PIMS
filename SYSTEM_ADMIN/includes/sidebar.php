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

.sidebar-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
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
    padding-left: 80px; /* Space for toggle button when sidebar is closed */
}

.navbar.sidebar-active {
    padding-left: 20px; /* Reduce padding when sidebar is open */
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
        padding-left: 80px; /* Keep space for toggle button on mobile */
    }
    
    .sidebar-toggle.sidebar-active {
        left: 20px; /* Keep toggle button in same position on mobile */
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
                <small class="text-white-50">Inventory System</small>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
        <a href="user_management.php" class="sidebar-nav-item <?php echo $current_page == 'user_management.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            User Management
        </a>
        <a href="#" class="sidebar-nav-item">
            <i class="bi bi-box"></i>
            Inventory Management
        </a>
        <a href="#" class="sidebar-nav-item">
            <i class="bi bi-tags"></i>
            Categories
        </a>
        <a href="#" class="sidebar-nav-item">
            <i class="bi bi-arrow-left-right"></i>
            Transactions
        </a>
        <a href="#" class="sidebar-nav-item">
            <i class="bi bi-file-text"></i>
            Reports
        </a>
        <a href="#" class="sidebar-nav-item">
            <i class="bi bi-gear"></i>
            System Settings
        </a>
        <a href="security_audit.php" class="sidebar-nav-item <?php echo $current_page == 'security_audit.php' ? 'active' : ''; ?>">
            <i class="bi bi-shield-exclamation"></i>
            Security Audit
        </a>
        <a href="backup.php" class="sidebar-nav-item <?php echo $current_page == 'backup.php' ? 'active' : ''; ?>">
            <i class="bi bi-cloud-download"></i>
            Backup System
        </a>
        <a href="cloud_config.php" class="sidebar-nav-item <?php echo $current_page == 'cloud_config.php' ? 'active' : ''; ?>">
            <i class="bi bi-cloud"></i>
            Cloud Storage
        </a>
        <a href="logs.php" class="sidebar-nav-item <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            System Logs
        </a>
        <a href="profile.php" class="sidebar-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i>
            My Profile
        </a>
        <div class="sidebar-nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
            <i class="bi bi-box-arrow-right"></i>
            <a href="../logout.php" onclick="event.preventDefault(); confirmLogout();" style="color: inherit; text-decoration: none;">Logout</a>
        </div>
    </nav>
</aside>
