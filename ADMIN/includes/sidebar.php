<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get system settings for logo
require_once '../config.php';
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}
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

/* Dropdown Styles */
.sidebar-dropdown {
    position: relative;
}

.sidebar-dropdown-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-weight: 500;
    background: none;
    border: none;
    cursor: pointer;
}

.sidebar-dropdown-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown-toggle.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown-menu {
    background: rgba(0, 0, 0, 0.2);
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-dropdown-menu.collapse:not(.show) {
    display: none;
}

.sidebar-dropdown-menu.collapse.show {
    display: block;
}

.sidebar-dropdown-item {
    display: block;
    padding: 0.625rem 1.5rem 0.625rem 3rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    border-left: 3px solid transparent;
}

.sidebar-dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown-item.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.sidebar-dropdown i {
    width: 20px;
    margin-right: 0.75rem;
}

.sidebar-dropdown-toggle i:last-child {
    margin-right: 0;
    transition: transform 0.3s ease;
}

.sidebar-dropdown-toggle[aria-expanded="true"] i:last-child {
    transform: rotate(180deg);
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
                <?php 
                $logo_path = !empty($system_settings['system_logo']) ? '../' . htmlspecialchars($system_settings['system_logo']) : '../img/trans_logo.png';
                $system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');
                ?>
                <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" class="img-fluid" style="max-height: 40px; border-radius: 8px;">
            </div>
            <div class="sidebar-title">
                <h6 class="mb-0 text-white"><?php echo $system_name; ?></h6>
                <small class="text-white-50">Admin Panel</small>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
        <div class="sidebar-dropdown">
            <button class="sidebar-dropdown-toggle <?php echo (in_array($current_page, ['assets.php', 'consumables.php', 'no_inventory_tag.php'])) ? 'active' : ''; ?>" 
                    type="button" data-bs-toggle="collapse" data-bs-target="#inventoryDropdown" 
                    aria-expanded="<?php echo (in_array($current_page, ['assets.php', 'consumables.php', 'no_inventory_tag.php'])) ? 'true' : 'false'; ?>">
                <div>
                    <i class="bi bi-archive"></i>
                    Inventory
                </div>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse <?php echo (in_array($current_page, ['assets.php', 'consumables.php', 'no_inventory_tag.php'])) ? 'show' : ''; ?>" id="inventoryDropdown">
                <ul class="sidebar-dropdown-menu">
                    <li>
                        <a href="assets.php" class="sidebar-dropdown-item <?php echo $current_page == 'assets.php' ? 'active' : ''; ?>">
                            <i class="bi bi-box"></i>
                            Asset Management
                        </a>
                    </li>
                    <li>
                        <a href="no_inventory_tag.php" class="sidebar-dropdown-item <?php echo $current_page == 'no_inventory_tag.php' ? 'active' : ''; ?>">
                            <i class="bi bi-exclamation-triangle"></i>
                            No Inventory Tag
                        </a>
                    </li>
                    <li>
                        <a href="consumables.php" class="sidebar-dropdown-item <?php echo $current_page == 'consumables.php' ? 'active' : ''; ?>">
                            <i class="bi bi-archive"></i>
                            Consumables
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <a href="employees.php" class="sidebar-nav-item <?php echo $current_page == 'employees.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            Employees
        </a>
        <div class="sidebar-dropdown">
            <button class="sidebar-dropdown-toggle <?php echo (in_array($current_page, ['par_form.php', 'ics_form.php', 'ris_form.php', 'iirup_form.php', 'itr_form.php'])) ? 'active' : ''; ?>" 
                    type="button" data-bs-toggle="collapse" data-bs-target="#formsDropdown" 
                    aria-expanded="<?php echo (in_array($current_page, ['par_form.php', 'ics_form.php', 'ris_form.php', 'iirup_form.php', 'itr_form.php'])) ? 'true' : 'false'; ?>">
                <div>
                    <i class="bi bi-file-earmark-text"></i>
                    Forms
                </div>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse <?php echo (in_array($current_page, ['par_form.php', 'ics_form.php', 'ris_form.php', 'iirup_form.php', 'itr_form.php'])) ? 'show' : ''; ?>" id="formsDropdown">
                <ul class="sidebar-dropdown-menu">
                    <li>
                        <a href="par_form.php" class="sidebar-dropdown-item <?php echo $current_page == 'par_form.php' ? 'active' : ''; ?>">
                            Property Acknowledgment Receipt
                        </a>
                    </li>
                    <li>
                        <a href="ics_form.php" class="sidebar-dropdown-item <?php echo $current_page == 'ics_form.php' ? 'active' : ''; ?>">
                            Inventory Custodian Slip
                        </a>
                    </li>
                    <li>
                        <a href="ris_form.php" class="sidebar-dropdown-item <?php echo $current_page == 'ris_form.php' ? 'active' : ''; ?>">
                            Requisition and Issue Slip
                        </a>
                    </li>
                    <li>
                        <a href="iirup_form.php" class="sidebar-dropdown-item <?php echo $current_page == 'iirup_form.php' ? 'active' : ''; ?>">
                            Individual Item Request for User Property
                        </a>
                    </li>
                    <li>
                        <a href="itr_form.php" class="sidebar-dropdown-item <?php echo $current_page == 'itr_form.php' ? 'active' : ''; ?>">
                            Inventory Transfer Request
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <a href="reports.php" class="sidebar-nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-bar-graph"></i>
            Reports
        </a>
        <a href="profile.php" class="sidebar-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i>
            My Profile
        </a>
        <div class="sidebar-nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
            <i class="bi bi-box-arrow-right"></i>
            <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" style="color: inherit; text-decoration: none;">Logout</a>
        </div>
    </nav>
</aside>
