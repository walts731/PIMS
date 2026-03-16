<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* Enhanced Fuel Sidebar Styles */
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

.fuel-sidebar-header h6 {
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
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-weight: 500;
    border-radius: 0.5rem;
    margin: 0.25rem 0.5rem;
}

.fuel-sidebar-nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: white;
    text-decoration: none;
    transform: translateX(5px);
}

.fuel-sidebar-nav-item.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.fuel-sidebar-nav-item i {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.nav-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.nav-title {
    font-weight: 600;
    font-size: 0.95rem;
    line-height: 1.2;
}

.nav-desc {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 0.125rem;
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
    margin: 0 1.5rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

.stat-card.stat-in {
    border-left: 3px solid #28a745;
}

.stat-card.stat-out {
    border-left: 3px solid #dc3545;
}

.stat-card.stat-inventory {
    border-left: 3px solid #17a2b8;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    font-size: 1.1rem;
}

.stat-card.stat-in .stat-icon {
    color: #28a745;
}

.stat-card.stat-out .stat-icon {
    color: #dc3545;
}

.stat-card.stat-inventory .stat-icon {
    color: #17a2b8;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.7rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    margin: 0 1.5rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    color: white;
    text-decoration: none;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.quick-action-btn.action-in {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.quick-action-btn.action-out {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.quick-action-btn.action-inventory {
    background: linear-gradient(135deg, #17a2b8, #138496);
}

.quick-action-btn.action-reports {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

/* System Links */
.system-links {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 0 1.5rem;
}

.system-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    border-radius: 0.25rem;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.system-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
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
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        margin: 0 1rem;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        margin: 0 1rem;
    }
}
</style>

<!-- Sidebar Toggle Button -->
<button class="fuel-sidebar-toggle" id="fuelSidebarToggle" onclick="toggleFuelSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Overlay -->
<div class="fuel-sidebar-overlay" id="fuelSidebarOverlay" onclick="closeFuelSidebar()"></div>

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
        <div class="fuel-sidebar-section">
            <div class="fuel-sidebar-section-title">Navigation</div>
        </div>
        
        <a href="dashboard.php" class="fuel-sidebar-nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <div class="nav-content">
                <span class="nav-title">Dashboard</span>
                <small class="nav-desc">Overview & Statistics</small>
            </div>
        </a>
        
        <a href="fuel_in.php" class="fuel-sidebar-nav-item <?php echo $current_page === 'fuel_in.php' ? 'active' : ''; ?>">
            <i class="bi bi-arrow-down-circle text-success"></i>
            <div class="nav-content">
                <span class="nav-title">Fuel IN</span>
                <small class="nav-desc">Add fuel deliveries</small>
            </div>
        </a>
        
        <a href="fuel_out.php" class="fuel-sidebar-nav-item <?php echo $current_page === 'fuel_out.php' ? 'active' : ''; ?>">
            <i class="bi bi-arrow-up-circle text-danger"></i>
            <div class="nav-content">
                <span class="nav-title">Fuel OUT</span>
                <small class="nav-desc">Record fuel consumption</small>
            </div>
        </a>
        
        <a href="inventory.php" class="fuel-sidebar-nav-item <?php echo $current_page === 'inventory.php' ? 'active' : ''; ?>">
            <i class="bi bi-archive text-info"></i>
            <div class="nav-content">
                <span class="nav-title">Inventory</span>
                <small class="nav-desc">Tank management</small>
            </div>
        </a>
        
        <a href="reports.php" class="fuel-sidebar-nav-item <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-bar-graph text-primary"></i>
            <div class="nav-content">
                <span class="nav-title">Reports</span>
                <small class="nav-desc">Analytics & reports</small>
            </div>
        </a>

        <!-- Quick Stats -->
        <div class="fuel-sidebar-section">
            <div class="fuel-sidebar-section-title">Today's Summary</div>
        </div>
        
        <?php
        // Get quick stats from database
        try {
            // Total Fuel IN today
            $today_in_query = "SELECT SUM(quantity) as total FROM fuel_transactions WHERE transaction_type = 'IN' AND DATE(transaction_date) = CURDATE()";
            $today_in_result = $conn->query($today_in_query);
            $today_in = $today_in_result ? $today_in_result->fetch_assoc()['total'] : 0;

            // Total Fuel OUT today  
            $today_out_query = "SELECT SUM(quantity) as total FROM fuel_transactions WHERE transaction_type = 'OUT' AND DATE(transaction_date) = CURDATE()";
            $today_out_result = $conn->query($today_out_query);
            $today_out = $today_out_result ? $today_out_result->fetch_assoc()['total'] : 0;

            // Current inventory
            $inventory_query = "SELECT SUM(current_level) as total FROM fuel_inventory WHERE status = 'active'";
            $inventory_result = $conn->query($inventory_query);
            $current_inventory = $inventory_result ? $inventory_result->fetch_assoc()['total'] : 0;
        } catch (Exception $e) {
            $today_in = 0;
            $today_out = 0;
            $current_inventory = 0;
        }
        ?>
        
        <div class="stats-grid">
            <div class="stat-card stat-in">
                <div class="stat-icon">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($today_in, 0); ?></div>
                    <div class="stat-label">Fuel IN (L)</div>
                </div>
            </div>
            
            <div class="stat-card stat-out">
                <div class="stat-icon">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($today_out, 0); ?></div>
                    <div class="stat-label">Fuel OUT (L)</div>
                </div>
            </div>
            
            <div class="stat-card stat-inventory">
                <div class="stat-icon">
                    <i class="bi bi-droplet"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($current_inventory, 0); ?></div>
                    <div class="stat-label">Inventory (L)</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="fuel-sidebar-section">
            <div class="fuel-sidebar-section-title">Quick Actions</div>
        </div>
        
        <div class="quick-actions">
            <a href="fuel_in.php" class="quick-action-btn action-in">
                <i class="bi bi-plus-circle"></i>
                <span>Add Fuel IN</span>
            </a>
            <a href="fuel_out.php" class="quick-action-btn action-out">
                <i class="bi bi-dash-circle"></i>
                <span>Add Fuel OUT</span>
            </a>
            <a href="inventory.php" class="quick-action-btn action-inventory">
                <i class="bi bi-plus-square"></i>
                <span>Add Tank</span>
            </a>
            <a href="reports.php" class="quick-action-btn action-reports">
                <i class="bi bi-file-earmark"></i>
                <span>Generate Report</span>
            </a>
        </div>

        <!-- System -->
        <div class="fuel-sidebar-section">
            <div class="fuel-sidebar-section-title">System</div>
        </div>
        
        <div class="system-links">
            <a href="../MAIN_USER/dashboard.php" class="system-link">
                <i class="bi bi-house-door"></i>
                <span>Main Dashboard</span>
            </a>
            <a href="../logout.php" class="system-link">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>

<script>
function toggleFuelSidebar() {
    const sidebar = document.getElementById('fuelSidebar');
    const toggle = document.getElementById('fuelSidebarToggle');
    const overlay = document.getElementById('fuelSidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    
    sidebar.classList.toggle('active');
    toggle.classList.toggle('sidebar-active');
    overlay.classList.toggle('active');
    
    if (mainWrapper) {
        mainWrapper.classList.toggle('sidebar-active');
    }
}

function closeFuelSidebar() {
    const sidebar = document.getElementById('fuelSidebar');
    const toggle = document.getElementById('fuelSidebarToggle');
    const overlay = document.getElementById('fuelSidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    
    sidebar.classList.remove('active');
    toggle.classList.remove('sidebar-active');
    overlay.classList.remove('active');
    
    if (mainWrapper) {
        mainWrapper.classList.remove('sidebar-active');
    }
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
