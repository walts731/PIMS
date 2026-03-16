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

// Log inventory page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_inventory_page', 'User accessed fuel inventory page');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_tank') {
        $tank_number = $_POST['tank_number'] ?? '';
        $fuel_type = $_POST['fuel_type'] ?? '';
        $capacity = $_POST['capacity'] ?? 0;
        $location = $_POST['location'] ?? '';
        
        if (!empty($tank_number) && !empty($fuel_type) && $capacity > 0) {
            $user_id = $_SESSION['user_id'];
            $insert_sql = "INSERT INTO fuel_inventory (tank_number, fuel_type, capacity, current_level, location, status, created_at, updated_by) 
                          VALUES (?, ?, ?, 0, ?, 'active', NOW(), ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('ssdsi', $tank_number, $fuel_type, $capacity, $location, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['fuel_success'] = 'Fuel tank added successfully!';
                logSystemAction($_SESSION['user_id'], 'create', 'fuel_inventory', "Added fuel tank: $tank_number");
            } else {
                $_SESSION['fuel_error'] = 'Error adding fuel tank: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['fuel_error'] = 'Please fill all required fields.';
        }
        
        header('Location: inventory.php');
        exit();
    }
    
    if ($action === 'update_tank') {
        $tank_id = $_POST['tank_id'] ?? 0;
        $current_level = $_POST['current_level'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($tank_id > 0) {
            $user_id = $_SESSION['user_id'];
            $update_sql = "UPDATE fuel_inventory SET current_level = ?, status = ?, last_updated = NOW(), updated_by = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('dsii', $current_level, $status, $user_id, $tank_id);
            
            if ($stmt->execute()) {
                $_SESSION['fuel_success'] = 'Tank updated successfully!';
                logSystemAction($_SESSION['user_id'], 'update', 'fuel_inventory', "Updated tank ID: $tank_id");
            } else {
                $_SESSION['fuel_error'] = 'Error updating tank: ' . $stmt->error;
            }
            $stmt->close();
        }
        
        header('Location: inventory.php');
        exit();
    }
}

// Get fuel inventory data
$fuel_inventory = [];
try {
    $inventory_sql = "SELECT 
                        id,
                        tank_number,
                        fuel_type,
                        capacity,
                        current_level,
                        location,
                        status,
                        last_updated,
                        created_at,
                        updated_by
                     FROM fuel_inventory 
                     ORDER BY fuel_type, tank_number";
    $inventory_result = $conn->query($inventory_sql);
    if ($inventory_result) {
        while ($row = $inventory_result->fetch_assoc()) {
            $fuel_inventory[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Inventory Error: ' . $e->getMessage());
}

// Calculate statistics
$total_tanks = count($fuel_inventory);
$active_tanks = count(array_filter($fuel_inventory, function($tank) {
    return $tank['status'] === 'active';
}));
$total_capacity = array_sum(array_column($fuel_inventory, 'capacity'));
$total_current_level = array_sum(array_column($fuel_inventory, 'current_level'));
$overall_fill_percentage = $total_capacity > 0 ? ($total_current_level / $total_capacity) * 100 : 0;
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
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <style>
        .inventory-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 1rem 1rem 1rem 5rem;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: calc(100vh - 76px);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin: -2rem -2rem 2rem -2rem;
            border-radius: 20px 20px 0 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card.primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #0056b3);
        }
        
        .stat-card.success::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .stat-card.warning::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #e0a800);
        }
        
        .stat-card.info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #17a2b8, #138496);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .stat-icon.primary { background: linear-gradient(135deg, #007bff, #0056b3); }
        .stat-icon.success { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-icon.warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #138496); }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .btn-inventory {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-inventory:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
            color: white;
        }
        
        .tank-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .tank-status.active { background: #28a745; }
        .tank-status.inactive { background: #dc3545; }
        .tank-status.maintenance { background: #ffc107; }
        
        .fuel-level-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .fuel-level-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin: 0.5rem;
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
        }
    </style>
</head>
<body class="inventory-page">
    <?php
    // Set page title for topbar
    $page_title = 'Fuel Inventory Management';
    ?>
    <!-- Main Content Wrapper -->
    <div class="fuel-main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once '../MAIN_USER/includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content animate-slide-up">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-fuel-pump me-3"></i>
                            Fuel Inventory Management
                        </h1>
                        <p class="mb-0 opacity-75">Manage fuel tanks and monitor stock levels</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-light btn-sm" onclick="refreshPage()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                        <button class="btn btn-outline-light btn-sm ms-2" onclick="exportInventoryData()">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                        <a href="dashboard.php" class="btn btn-light btn-sm ms-2">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['fuel_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['fuel_success']); unset($_SESSION['fuel_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['fuel_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['fuel_error']); unset($_SESSION['fuel_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card primary">
                        <div class="stat-icon primary">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total Tanks</h6>
                        <h3 class="mb-0 text-primary"><?php echo $total_tanks; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h6 class="text-muted mb-2">Active Tanks</h6>
                        <h3 class="mb-0 text-success"><?php echo $active_tanks; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="bi bi-rulers"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total Capacity</h6>
                        <h3 class="mb-0 text-warning"><?php echo number_format($total_capacity, 0); ?> L</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card info">
                        <div class="stat-icon info">
                            <i class="bi bi-droplet"></i>
                        </div>
                        <h6 class="text-muted mb-2">Current Level</h6>
                        <h3 class="mb-0 text-info"><?php echo number_format($total_current_level, 0); ?> L</h3>
                        <small class="text-muted"><?php echo number_format($overall_fill_percentage, 1); ?>% Full</small>
                    </div>
                </div>
            </div>

            <!-- Add Tank Form -->
            <div class="form-card">
                <h5 class="mb-4">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>
                    Add New Fuel Tank
                </h5>
                <form method="POST" action="inventory.php">
                    <input type="hidden" name="action" value="add_tank">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="tank_number" class="form-label">Tank Number *</label>
                            <input type="text" class="form-control" id="tank_number" name="tank_number" required>
                            <div class="form-text">Unique identifier for the tank (e.g., TANK-001)</div>
                        </div>
                        <div class="col-md-3">
                            <label for="fuel_type" class="form-label">Fuel Type *</label>
                            <select class="form-select" id="fuel_type" name="fuel_type" required>
                                <option value="">Select fuel type</option>
                                <option value="diesel">Diesel</option>
                                <option value="gasoline">Gasoline</option>
                                <option value="premium">Premium Gasoline</option>
                                <option value="kerosene">Kerosene</option>
                                <option value="lpg">LPG</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="capacity" class="form-label">Capacity (Liters) *</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" step="0.01" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="location" class="form-label">Location *</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                            <div class="form-text">Physical location of the tank</div>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-inventory w-100">
                                <i class="bi bi-plus-circle me-2"></i>Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tank Inventory Table -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-list me-2"></i>Fuel Tank Inventory
                        <span class="badge bg-primary text-white ms-2"><?php echo count($fuel_inventory); ?></span>
                    </h5>
                    <button class="btn btn-outline-primary btn-sm" onclick="printInventory()">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
                
                <?php if (!empty($fuel_inventory)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash me-1"></i>Tank #</th>
                                    <th><i class="bi bi-tag me-1"></i>Fuel Type</th>
                                    <th><i class="bi bi-rulers me-1"></i>Capacity</th>
                                    <th><i class="bi bi-droplet me-1"></i>Current Level</th>
                                    <th><i class="bi bi-speedometer2 me-1"></i>Fill %</th>
                                    <th><i class="bi bi-geo-alt me-1"></i>Location</th>
                                    <th><i class="bi bi-info-circle me-1"></i>Status</th>
                                    <th><i class="bi bi-clock me-1"></i>Last Updated</th>
                                    <th><i class="bi bi-gear me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuel_inventory as $tank): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($tank['tank_number']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                <?php echo htmlspecialchars(ucfirst($tank['fuel_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($tank['capacity'], 2); ?> L</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo number_format($tank['current_level'], 2); ?> L</span>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo min(100, ($tank['current_level'] / $tank['capacity']) * 100); ?>%"
                                                         aria-valuenow="<?php echo $tank['current_level']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="<?php echo $tank['capacity']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $percentage = ($tank['current_level'] / $tank['capacity']) * 100;
                                            $badge_class = 'success';
                                            if ($percentage < 20) $badge_class = 'danger';
                                            elseif ($percentage < 50) $badge_class = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?> text-white">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($tank['location']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = 'success';
                                            if ($tank['status'] === 'inactive') $status_class = 'danger';
                                            elseif ($tank['status'] === 'maintenance') $status_class = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?> text-white">
                                                <?php echo htmlspecialchars(ucfirst($tank['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($tank['last_updated'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-primary" onclick="editTank(<?php echo $tank['id']; ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-info" onclick="viewTankHistory(<?php echo $tank['id']; ?>)">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-fuel-pump text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">No Fuel Tanks Found</h5>
                        <p class="text-muted">No fuel tanks have been set up in the system yet.</p>
                        <button class="btn btn-inventory" data-bs-toggle="modal" data-bs-target="#addTankModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Your First Tank
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Tank Modal -->
    <div class="modal fade" id="editTankModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Tank</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="inventory.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_tank">
                        <input type="hidden" id="edit_tank_id" name="tank_id">
                        
                        <div class="mb-3">
                            <label for="edit_current_level" class="form-label">Current Level (Liters)</label>
                            <input type="number" class="form-control" id="edit_current_level" name="current_level" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Tank</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTables
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']]
        });
    });

    function refreshPage() {
        location.reload();
    }

    function exportInventoryData() {
        window.open('pages/export_fuel_report.php?export=1&type=inventory', '_blank');
    }

    function printInventory() {
        window.print();
    }

    function editTank(tankId) {
        // Fetch tank data and populate edit modal
        fetch('pages/get_tank_data.php?id=' + tankId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_tank_id').value = data.id;
                document.getElementById('edit_current_level').value = data.current_level;
                document.getElementById('edit_status').value = data.status;
                
                const modal = new bootstrap.Modal(document.getElementById('editTankModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error fetching tank data:', error);
                alert('Error loading tank data. Please try again.');
            });
    }

    function viewTankHistory(tankId) {
        // Open tank history in new window or modal
        window.open('pages/tank_history.php?id=' + tankId, '_blank');
    }
    </script>

    <style>
    @media print {
        .btn, .page-header, .modal {
            display: none !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        .table {
            font-size: 12px;
        }
    }
    </style>
</body>
</html>
