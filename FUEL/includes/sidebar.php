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
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-item {
    margin-bottom: 0.25rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
}

.nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    padding-left: 2rem;
}

.nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    border-left: 4px solid white;
}

.nav-link i {
    margin-right: 0.75rem;
    width: 1.25rem;
    text-align: center;
}

.nav-section {
    margin-bottom: 2rem;
}

.nav-section-title {
    padding: 0.5rem 1.5rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.05em;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        left: -100%;
    }
}
</style>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <?php 
                $logo_path = !empty($system_settings['system_logo']) ? '../img/trans_logo.png' : htmlspecialchars($system_settings['system_logo']);
                $system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');
                ?>
                <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" 
                     style="height: 40px; border-radius: 8px;">
            </div>
            <div class="flex-grow-1 ms-3">
                <h3><?php echo $system_name; ?></h3>
                <small class="text-white-50">Admin Panel</small>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <!-- Main Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Main Menu</div>
            
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </div>
            
            <div class="nav-item">
                <a href="?tab=inventory" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'inventory') || (!isset($_GET['tab'])) ? 'active' : ''; ?>">
                    <i class="bi bi-fuel-pump"></i>
                    Fuel Inventory
                </a>
            </div>
            
            <div class="nav-item">
                <a href="?tab=fuelin" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'fuelin') ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-down-circle"></i>
                    Fuel In
                </a>
            </div>
            
            <div class="nav-item">
                <a href="?tab=fuelout" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'fuelout') ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-up-circle"></i>
                    Fuel Out
                </a>
            </div>
            
            <div class="nav-item">
                <a href="?tab=reports" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'reports') ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    Reports
                </a>
            </div>
        </div>

        <!-- User Section -->
        <div class="nav-section">
            <div class="nav-section-title">User</div>
            
            <div class="nav-item">
                <a href="../logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>
</div>
