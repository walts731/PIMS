<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_consumables', 'Main user accessed consumables page');

$consumables = [];
$error = null;

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Check if consumables table exists (like admin)
        $table_check = $conn->query("SHOW TABLES LIKE 'consumables'");
        if ($table_check && $table_check->num_rows > 0) {
            // Get consumables exactly like admin - from consumables table
            $sql = "SELECT c.*, o.office_name, fo.office_name as for_office_name
                    FROM consumables c 
                    LEFT JOIN offices o ON c.office_id = o.id 
                    LEFT JOIN offices fo ON c.for_office_id = fo.id 
                    WHERE c.quantity > 0
                    ORDER BY c.created_at DESC";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $consumables[] = $row;
                }
            }
            
            // Calculate stats like admin
            $stats = [];
            $stats['total_quantity'] = array_sum(array_column($consumables, 'quantity'));
            $stats['total_consumables'] = count($consumables);
            $stats['total_value'] = array_sum(array_map(fn($c) => $c['quantity'] * $c['unit_cost'], $consumables));
            $stats['low_stock_count'] = count(array_filter($consumables, fn($c) => $c['quantity'] <= $c['reorder_level']));
            
            // Get offices for dropdown filters
            $offices = [];
            try {
                $result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $offices[] = $row;
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching offices: " . $e->getMessage());
            }
            
            if (empty($consumables)) {
                $error = 'No consumables found. Please add consumables through the admin panel.';
            }
        } else {
            $error = 'Consumables table not found. Please contact administrator to set up consumables management.';
        }
    } catch (Exception $e) {
        $error = 'Error fetching consumables: ' . $e->getMessage();
        error_log('Main User Consumables Error: ' . $e->getMessage());
    }
}

$page_title = 'Consumables Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumables Management - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
            border-radius: 0;
        }
        
        .header-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #28a745;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .consumables-table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .consumables-table thead {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .consumables-table th {
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        
        .consumables-table td {
            padding: 0.875rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .badge-status {
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #868e96;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        /* Office Card Styles */
        .office-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .office-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .office-card-link {
            display: block;
            color: inherit;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .office-card-link:hover {
            color: inherit;
            text-decoration: none;
            transform: scale(1.02);
        }
        
        .office-card-link:hover .office-card {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .office-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .office-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .office-stats {
            display: flex;
            justify-content: space-around;
            padding: 1rem;
            background: rgba(255,255,255,0.1);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item .number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .stat-item .label {
            font-size: 0.75rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .office-details {
            padding: 1.5rem;
        }
        
        .office-actions {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        
        /* Low stock row styling */
        .low-stock {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107 !important;
        }
        
        .low-stock:hover {
            background-color: #ffeaa7 !important;
        }
        
        .text-value {
            font-weight: 600;
            color: #28a745;
        }
        
        /* Admin-style statistics cards */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.04);
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .stats-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.25rem;
        }
        
        .stats-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .stats-label i {
            margin-right: 0.25rem;
        }
        
        /* Low stock badge */
        .low-stock-badge {
            background-color: #ffc107;
            color: #212529;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <!-- Header Section -->
            <div class="header-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-box-seam me-2"></i>
                            Consumables for Viewing
                        </h1>
                        <p class="mb-0 opacity-75">View available and released consumables</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <button class="btn btn-light btn-sm" onclick="window.location.reload()">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Notice:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Debug Info (remove in production) -->
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Debug Info:</strong> Found <?php echo count($consumables); ?> consumable records.
                <?php if (!empty($consumables)): ?>
                    First item: <?php echo htmlspecialchars($consumables[0]['description'] ?? 'N/A'); ?>
                <?php endif; ?>
            </div>

            <!-- Statistics Cards (Exact Admin Style) -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_quantity'] ?? 0; ?></div>
                        <div class="stats-label"><i class="bi bi-box-seam"></i> Total Consumables</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_consumables'] ?? 0; ?></div>
                        <div class="stats-label"><i class="bi bi-tags"></i> Consumable Types</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                        <div class="stats-label"><i class="bi bi-currency-dollar"></i> Total Value</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['low_stock_count'] ?? 0; ?></div>
                        <div class="stats-label"><i class="bi bi-exclamation-triangle"></i> Low Stock Items</div>
                    </div>
                </div>
            </div>

            <!-- Available Consumables Section -->
            <div class="table-container mb-4">
                <div class="row mb-3 align-items-center">
                    <div class="col-md-2">
                        <h5 class="mb-0"><i class="bi bi-check-circle text-success"></i> Available Consumables</h5>
                    </div>
                    <div class="col-md-10">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" id="availableOfficeFilter">
                                    <option value="">All Offices</option>
                                    <?php foreach ($offices ?? [] as $office): ?>
                                        <option value="<?php echo $office['id']; ?>">
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" id="availableSearchInput" placeholder="Search available consumables...">
                            </div>
                            <div class="col-md-3">
                                <span class="badge bg-success fs-6">
                                    <?php echo count(array_filter($consumables, fn($c) => empty($c['for_office_name']))); ?> Items
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="availableConsumablesTable">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Reorder Level</th>
                                <th>Office</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $available_consumables = array_filter($consumables, fn($c) => empty($c['for_office_name']));
                            if (!empty($available_consumables)): 
                            ?>
                                <?php foreach ($available_consumables as $consumable): ?>
                                    <tr <?php echo ($consumable['quantity'] <= $consumable['reorder_level']) ? 'class="low-stock"' : ''; ?>>
                                        <td>
                                            <?php echo htmlspecialchars($consumable['description']); ?>
                                            <?php if ($consumable['quantity'] <= $consumable['reorder_level']): ?>
                                                <span class="low-stock-badge ms-2">Low Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $consumable['quantity']; ?></td>
                                        <td><?php echo $consumable['reorder_level']; ?></td>
                                        <td><?php echo htmlspecialchars($consumable['office_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Available
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1"></i>
                                        <p class="mt-2">No available consumables found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Released Consumables Section -->
            <div class="table-container">
                <div class="row mb-3 align-items-center">
                    <div class="col-md-2">
                        <h5 class="mb-0"><i class="bi bi-box-arrow-right text-info"></i> Released Consumables</h5>
                    </div>
                    <div class="col-md-10">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" id="releasedOfficeFilter">
                                    <option value="">All Offices</option>
                                    <?php foreach ($offices ?? [] as $office): ?>
                                        <option value="<?php echo $office['id']; ?>">
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" id="releasedForOfficeFilter">
                                    <option value="">All For Offices</option>
                                    <?php foreach ($offices ?? [] as $office): ?>
                                        <option value="<?php echo $office['id']; ?>">
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control form-control-sm" id="releasedSearchInput" placeholder="Search released consumables...">
                            </div>
                            <div class="col-md-3">
                                <span class="badge bg-info fs-6">
                                    <?php echo count(array_filter($consumables, fn($c) => !empty($c['for_office_name']))); ?> Items
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="releasedConsumablesTable">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Reorder Level</th>
                                <th>Office</th>
                                <th>Released To</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $released_consumables = array_filter($consumables, fn($c) => !empty($c['for_office_name']));
                            if (!empty($released_consumables)): 
                            ?>
                                <?php foreach ($released_consumables as $consumable): ?>
                                    <tr <?php echo ($consumable['quantity'] <= $consumable['reorder_level']) ? 'class="low-stock"' : ''; ?>>
                                        <td>
                                            <?php echo htmlspecialchars($consumable['description']); ?>
                                            <?php if ($consumable['quantity'] <= $consumable['reorder_level']): ?>
                                                <span class="low-stock-badge ms-2">Low Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $consumable['quantity']; ?></td>
                                        <td><?php echo $consumable['reorder_level']; ?></td>
                                        <td><?php echo htmlspecialchars($consumable['office_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($consumable['for_office_name'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="bi bi-box-arrow-right"></i> Released
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1"></i>
                                        <p class="mt-2">No released consumables found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Custom sidebar functionality for consumables page
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                    
                    // Adjust main content margin on desktop
                    if (window.innerWidth > 768) {
                        if (sidebar.classList.contains('active')) {
                            mainContent.style.marginLeft = '280px';
                        } else {
                            mainContent.style.marginLeft = '0';
                        }
                    }
                });
                
                // Close sidebar when clicking overlay
                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        mainContent.style.marginLeft = '0';
                    });
                }
                
                // Close sidebar on mobile when clicking menu items
                const sidebarNavItems = document.querySelectorAll('.sidebar-nav-item');
                sidebarNavItems.forEach(item => {
                    item.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            sidebar.classList.remove('active');
                            sidebarOverlay.classList.remove('active');
                            mainContent.style.marginLeft = '0';
                        }
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    mainContent.style.marginLeft = '0';
                }
            });
            
            // Office Filter Functionality
            function setupFilters() {
                const availableOfficeFilter = document.getElementById('availableOfficeFilter');
                const releasedOfficeFilter = document.getElementById('releasedOfficeFilter');
                const releasedForOfficeFilter = document.getElementById('releasedForOfficeFilter');
                const availableSearchInput = document.getElementById('availableSearchInput');
                const releasedSearchInput = document.getElementById('releasedSearchInput');
                
                // Available consumables filtering
                if (availableOfficeFilter && availableSearchInput) {
                    availableOfficeFilter.addEventListener('change', filterAvailableConsumables);
                    availableSearchInput.addEventListener('input', filterAvailableConsumables);
                }
                
                // Released consumables filtering
                if (releasedOfficeFilter && releasedForOfficeFilter && releasedSearchInput) {
                    releasedOfficeFilter.addEventListener('change', filterReleasedConsumables);
                    releasedForOfficeFilter.addEventListener('change', filterReleasedConsumables);
                    releasedSearchInput.addEventListener('input', filterReleasedConsumables);
                }
            }
            
            function filterAvailableConsumables() {
                const officeFilter = document.getElementById('availableOfficeFilter').value;
                const searchTerm = document.getElementById('availableSearchInput').value.toLowerCase();
                const rows = document.querySelectorAll('#availableConsumablesTable tbody tr');
                
                console.log('Available filter - Office:', officeFilter, 'Search:', searchTerm); // Debug
                
                rows.forEach(row => {
                    if (row.querySelector('td[colspan]')) return; // Skip empty state row
                    
                    const office = row.cells[4].textContent.trim(); // Office column (index 4)
                    const description = row.cells[0].textContent.toLowerCase(); // Description column
                    
                    console.log('Row - Office:', office, 'Description:', description); // Debug
                    
                    const matchesOffice = !officeFilter || officeFilter === '' || office === officeFilter;
                    const matchesSearch = !searchTerm || description.includes(searchTerm);
                    
                    console.log('Matches - Office:', matchesOffice, 'Search:', matchesSearch); // Debug
                    
                    row.style.display = matchesOffice && matchesSearch ? '' : 'none';
                });
            }
            
            function filterReleasedConsumables() {
                const officeFilter = document.getElementById('releasedOfficeFilter').value;
                const forOfficeFilter = document.getElementById('releasedForOfficeFilter').value;
                const searchTerm = document.getElementById('releasedSearchInput').value.toLowerCase();
                const rows = document.querySelectorAll('#releasedConsumablesTable tbody tr');
                
                console.log('Released filter - Office:', officeFilter, 'ForOffice:', forOfficeFilter, 'Search:', searchTerm); // Debug
                
                rows.forEach(row => {
                    if (row.querySelector('td[colspan]')) return; // Skip empty state row
                    
                    const office = row.cells[3].textContent.trim(); // Office column (index 3)
                    const forOffice = row.cells[4].textContent.trim(); // Released To column (index 4)
                    const description = row.cells[0].textContent.toLowerCase(); // Description column
                    
                    console.log('Row - Office:', office, 'ForOffice:', forOffice, 'Description:', description); // Debug
                    
                    const matchesOffice = !officeFilter || officeFilter === '' || office === officeFilter;
                    const matchesForOffice = !forOfficeFilter || forOfficeFilter === '' || forOffice === forOfficeFilter;
                    const matchesSearch = !searchTerm || description.includes(searchTerm);
                    
                    console.log('Matches - Office:', matchesOffice, 'ForOffice:', matchesForOffice, 'Search:', matchesSearch); // Debug
                    
                    row.style.display = matchesOffice && matchesForOffice && matchesSearch ? '' : 'none';
                });
            }
            
            // Initialize filters
            setupFilters();
        });
    </script>
</body>
</html>
