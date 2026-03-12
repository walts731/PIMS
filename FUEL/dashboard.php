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

// Check if user has correct role (admin, system_admin, or fuel)
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel'])) {
    header('Location: ../index.php');
    exit();
}

// Log fuel page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_inventory', 'User accessed fuel dashboard');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Dashboard - PIMS</title>
    
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
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Fuel Dashboard';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-speedometer2"></i> Fuel Dashboard
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

            <!-- Quick Access Module Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="?tab=inventory" class="module-card">
                        <div class="module-icon blue">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <div class="module-title">Fuel Inventory</div>
                        <div class="module-desc">Stock levels</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?tab=fuelin" class="module-card">
                        <div class="module-icon success">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                        <div class="module-title">Fuel In</div>
                        <div class="module-desc">Add fuel</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?tab=fuelout" class="module-card">
                        <div class="module-icon danger">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <div class="module-title">Fuel Out</div>
                        <div class="module-desc">Dispense fuel</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="../ADMIN/employees.php" class="module-card">
                        <div class="module-icon purple">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="module-title">Employees</div>
                        <div class="module-desc">Staff management</div>
                    </a>
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
</body>
</html>
