<?php
// Get current page for active state
$current_page = $_GET['page'] ?? 'dashboard';
?>
<style>
/* Fuel Sidebar Styles */
.fuel-sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 2px 0 10px rgba(102, 126, 234, 0.1);
    transition: left 0.3s ease-in-out;
    z-index: 1040;
    overflow-y: auto;
}

.fuel-sidebar.active {
    left: 0;
}

.fuel-sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
}

.fuel-sidebar-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.fuel-sidebar-nav {
    padding: 1rem 0;
}

.fuel-sidebar-nav-item {
    display: block;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-weight: 500;
}

.fuel-sidebar-nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
    text-decoration: none;
}

.fuel-sidebar-nav-item.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.fuel-sidebar-nav-item i {
    width: 20px;
    margin-right: 0.75rem;
}

.fuel-sidebar-toggle {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1050;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    border-radius: 10px;
    color: white;
    padding: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.fuel-sidebar-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.fuel-sidebar-toggle.sidebar-active {
    left: 300px;
}

.fuel-sidebar-overlay {
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

.fuel-sidebar-overlay.active {
    display: block;
    opacity: 1;
}

/* Main content shift when sidebar is active */
.fuel-main-wrapper {
    transition: margin-left 0.3s ease-in-out;
}

.fuel-main-wrapper.sidebar-active {
    margin-left: 280px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .fuel-sidebar {
        width: 100%;
        left: -100%;
    }
    
    .fuel-main-wrapper.sidebar-active {
        margin-left: 0;
    }
    
    .fuel-sidebar-toggle.sidebar-active {
        left: 20px;
    }
}

/* Section divider */
.fuel-sidebar-section {
    padding: 1rem 1.5rem 0.5rem;
    margin-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.fuel-sidebar-section-title {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
    font-weight: 600;
}
</style>

<aside class="fuel-sidebar" id="fuelSidebar">
    <div class="fuel-sidebar-header">
        <div class="d-flex align-items-center">
            <div class="sidebar-logo">
                <i class="bi bi-fuel-pump" style="font-size: 2rem; color: white;"></i>
            </div>
            <div class="sidebar-title">
                <h6 class="mb-0 text-white">Fuel Management</h6>
                <small class="text-white-50">PIMS System</small>
            </div>
        </div>
    </div>

    <nav class="fuel-sidebar-nav">
        <!-- Main Navigation -->
        <a href="dashboard.php?page=dashboard" class="fuel-sidebar-nav-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard Overview
        </a>
        <a href="inventory.php" class="fuel-sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
            <i class="bi bi-fuel-pump"></i>
            Fuel Inventory
        </a>
        <a href="fuel_in.php" class="fuel-sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'fuel_in.php' ? 'active' : ''; ?>">
            <i class="bi bi-arrow-down-circle"></i>
            Fuel In
        </a>
        <a href="fuel_out.php" class="fuel-sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'fuel_out.php' ? 'active' : ''; ?>">
            <i class="bi bi-arrow-up-circle"></i>
            Fuel Out
        </a>
        <a href="reports.php" class="fuel-sidebar-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-bar-graph"></i>
            Reports
        </a>

        <!-- Quick Actions -->
        <div class="fuel-sidebar-section">
            <div class="fuel-sidebar-section-title">Quick Actions</div>
        </div>
        <a href="#" class="fuel-sidebar-nav-item" onclick="showFuelModal('add_tank'); return false;">
            <i class="bi bi-plus-circle"></i>
            Add Tank
        </a>
        <a href="#" class="fuel-sidebar-nav-item" onclick="showFuelModal('fuel_in'); return false;">
            <i class="bi bi-arrow-down-circle"></i>
            Quick Fuel In
        </a>
        <a href="#" class="fuel-sidebar-nav-item" onclick="showFuelModal('fuel_out'); return false;">
            <i class="bi bi-arrow-up-circle"></i>
            Quick Fuel Out
        </a>

        <!-- System -->
        <div class="fuel-sidebar-section">
            <div class="fuel-sidebar-section-title">System</div>
        </div>
        <a href="../MAIN_USER/dashboard.php" class="fuel-sidebar-nav-item">
            <i class="bi bi-house-door"></i>
            Main Dashboard
        </a>
        <a href="../index.php" class="fuel-sidebar-nav-item">
            <i class="bi bi-box-arrow-right"></i>
            Logout
        </a>
    </nav>
</aside>

<!-- Sidebar Toggle Button -->
<button class="fuel-sidebar-toggle" id="fuelSidebarToggle" onclick="toggleFuelSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Overlay -->
<div class="fuel-sidebar-overlay" id="fuelSidebarOverlay" onclick="closeFuelSidebar()"></div>

<script>
function toggleFuelSidebar() {
    const sidebar = document.getElementById('fuelSidebar');
    const toggle = document.getElementById('fuelSidebarToggle');
    const overlay = document.getElementById('fuelSidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    
    sidebar.classList.toggle('active');
    toggle.classList.toggle('sidebar-active');
    overlay.classList.toggle('active');
    mainWrapper.classList.toggle('sidebar-active');
}

function closeFuelSidebar() {
    const sidebar = document.getElementById('fuelSidebar');
    const toggle = document.getElementById('fuelSidebarToggle');
    const overlay = document.getElementById('fuelSidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    
    sidebar.classList.remove('active');
    toggle.classList.remove('sidebar-active');
    overlay.classList.remove('active');
    mainWrapper.classList.remove('sidebar-active');
}

// Close sidebar on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFuelSidebar();
    }
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('fuelSidebar');
    const toggle = document.getElementById('fuelSidebarToggle');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(e.target) && 
        !toggle.contains(e.target) &&
        sidebar.classList.contains('active')) {
        closeFuelSidebar();
    }
});
</script>
