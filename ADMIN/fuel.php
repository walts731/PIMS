<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log fuel page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_inventory', 'User accessed fuel inventory page');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Inventory - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery - must load before any scripts that use it -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <style>
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .fuel-stats-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }
        
        .fuel-stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .fuel-stats-card .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .fuel-stats-card.diesel .stats-icon {
            background: var(--primary-gradient);
            color: white;
        }
        
        .fuel-stats-card.gasoline .stats-icon {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .fuel-stats-card.premium .stats-icon {
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
            color: white;
        }
        
        .fuel-stats-card .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .fuel-stats-card .stats-label {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .fuel-stats-card .stats-detail {
            font-size: 0.75rem;
            color: #adb5bd;
        }
        
        /* Compact card styles */
        .fuel-stats-card.compact {
            padding: 0.75rem;
            min-height: auto;
        }
        
        .fuel-stats-card.compact .stats-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }
        
        .fuel-stats-card.compact .stats-info {
            flex: 1;
        }
        
        .fuel-stats-card.compact .stats-value {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }
        
        .fuel-stats-card.compact .stats-label {
            font-size: 0.75rem;
            margin-bottom: 0;
            line-height: 1.2;
        }
        
        .fuel-stats-card.compact .stats-detail {
            font-size: 0.7rem;
            white-space: nowrap;
            text-align: right;
        }
        
        .fuel-stats-card.compact.total .stats-detail {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .fuel-level-indicator {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            overflow: hidden;
            position: relative;
        }
        
        .fuel-level-fill {
            height: 100%;
            background: var(--primary-gradient);
            transition: width 0.3s ease;
        }
        
        .fuel-level-fill.high {
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .fuel-level-fill.medium {
            background: linear-gradient(90deg, #ffc107, #fd7e14);
        }
        
        .fuel-level-fill.low {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        
        .fuel-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .fuel-type-diesel { 
            background: var(--primary-color); 
            color: white; 
        }
        
        .fuel-type-gasoline { 
            background: #007bff; 
            color: white; 
        }
        
        .fuel-type-premium { 
            background: #6f42c1; 
            color: white; 
        }
        
        .nav-tabs .nav-link {
            color: var(--primary-color);
            font-weight: 500;
            border: none;
            border-bottom: 2px solid transparent;
            background: none;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background: none;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-hover);
            border-bottom-color: var(--primary-hover);
        }
        
        .table-actions {
            display: flex;
            gap: 0.25rem;
        }
        
        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .btn-fuel-in {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .btn-fuel-out {
            background: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }
        
        .card-header h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-full { background: #d4edda; color: #155724; }
        .status-normal { background: #fff3cd; color: #856404; }
        .status-low { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Fuel Inventory';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-fuel-pump"></i> Fuel Inventory
                        </h1>
                        <p class="text-muted mb-0">Manage fuel tanks, transactions, and inventory levels</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshFuelData()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-outline-success btn-sm ms-2" onclick="exportFuelData()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Session Messages -->
            <?php if (isset($_SESSION['fuel_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['fuel_success']); unset($_SESSION['fuel_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['fuel_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['fuel_error']); unset($_SESSION['fuel_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Fuel Management Tabs -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Fuel Management</h5>
                </div>
                <div class="card-body">
                    <!-- Direct Navigation Links (Fallback) -->
                    <div class="mb-3">
                        <a href="?tab=inventory" class="btn btn-outline-primary <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'inventory') ? 'active' : ''; ?>">
                            <i class="bi bi-fuel-pump"></i> Main Inventory
                        </a>
                        <a href="?tab=fuelin" class="btn btn-outline-success <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'fuelin') ? 'active' : ''; ?>">
                            <i class="bi bi-arrow-down-circle"></i> Fuel In
                        </a>
                        <a href="?tab=fuelout" class="btn btn-outline-danger <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'fuelout') ? 'active' : ''; ?>">
                            <i class="bi bi-arrow-up-circle"></i> Fuel Out
                        </a>
                        <a href="?tab=reports" class="btn btn-outline-info <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'reports') ? 'active' : ''; ?>">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reports
                        </a>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="tab-content">
                        <?php
                        $current_tab = $_GET['tab'] ?? 'inventory';
                        
                        switch($current_tab) {
                            case 'fuelin':
                                include 'fuel_tabs/fuel_in.php';
                                break;
                            case 'fuelout':
                                include 'fuel_tabs/fuel_out.php';
                                break;
                            case 'reports':
                                include 'fuel_tabs/reports.php';
                                break;
                            default:
                                include 'fuel_tabs/inventory.php';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit Fuel -->
    <div class="modal fade" id="fuelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fuel Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    // Initialize Bootstrap tabs
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all tabs
        const triggerTabList = [].slice.call(document.querySelectorAll('#fuelTabs button'));
        triggerTabList.forEach(function(triggerEl) {
            new bootstrap.Tab(triggerEl);
        });
        
        // Auto-refresh inventory data every 30 seconds
        setInterval(function() {
            const activeTab = document.querySelector('#fuelTabs .nav-link.active');
            if (activeTab && activeTab.id === 'inventory-tab') {
                // Auto-refresh functionality can be added here
            }
        }, 30000);
    });

    // Refresh fuel data
    function refreshFuelData() {
        location.reload();
    }

    // Export fuel data
    function exportFuelData() {
        const activeTab = document.querySelector('.tab-pane.active');
        if (!activeTab) {
            alert('No active tab found');
            return;
        }
        
        const activeTabId = activeTab.id;
        console.log('Active tab:', activeTabId);
        
        switch(activeTabId) {
            case 'inventory':
                // Export inventory data
                alert('Inventory export functionality will be implemented');
                break;
            case 'fuelin':
                // Export fuel in data
                console.log('Exporting fuel in data...');
                window.open('fuel_tabs/export_fuel_report.php?export=1&type=fuel_in', '_blank');
                break;
            case 'fuelout':
                // Export fuel out data
                console.log('Exporting fuel out data...');
                window.open('fuel_tabs/export_fuel_report.php?export=1&type=fuel_out', '_blank');
                break;
            case 'reports':
                // Export reports
                console.log('Exporting reports...');
                window.open('fuel_tabs/export_fuel_report.php?export=1', '_blank');
                break;
            default:
                alert('Unknown tab: ' + activeTabId);
        }
    }

    // Modal functions
    function showFuelModal(type, id = null) {
        console.log('Opening modal:', type, id);
        
        const modal = new bootstrap.Modal(document.getElementById('fuelModal'));
        
        // Load modal content based on type
        fetch(`fuel_tabs/modal_content.php?type=${type}&id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                document.querySelector('#fuelModal .modal-body').innerHTML = html;
                modal.show();
            })
            .catch(error => {
                console.error('Error loading modal content:', error);
                alert('Error loading modal content: ' + error.message);
            });
    }
    </script>
    
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
