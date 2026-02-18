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

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Get user's office information from offices table
$user_office_id = null;
try {
    // Fixed: Use correct column name 'office_id' from users table
    $stmt = $conn->prepare("SELECT o.id FROM offices o WHERE o.id = (SELECT office_id FROM users WHERE id = ?)");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_office_id = $result->fetch_assoc()['id'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error getting user office: " . $e->getMessage());
}

// Debug: Let's see what's happening
error_log("DEBUG: office_assets.php - user_id: " . ($_SESSION['user_id'] ?? 'NULL'));
error_log("DEBUG: office_assets.php - user_office_id: " . ($user_office_id ?? 'NULL'));
error_log("DEBUG: office_assets.php - session data: " . print_r($_SESSION, true));

if (!$user_office_id) {
    $message = 'Office ID not found in session. Please log in again.';
    $message_type = 'danger';
} else {

// Handle form submissions
$message = '';
$message_type = '';

// Add new asset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_asset') {
    $asset_name = trim($_POST['asset_name']);
    $asset_type = $_POST['asset_type'];
    $description = trim($_POST['description']);
    $serial_number = trim($_POST['serial_number']);
    $purchase_date = $_POST['purchase_date'];
    $purchase_cost = $_POST['purchase_cost'];
    $current_value = $_POST['current_value'];
    $location = trim($_POST['location']);
    $status = $_POST['status'];
    $quantity = 1; // Default quantity for individual asset
    
    // Validation
    if (empty($asset_name) || empty($asset_type) || empty($purchase_date)) {
        $message = 'Asset name, type, and purchase date are required';
        $message_type = 'danger';
    } else {
        try {
            // Insert into assets table first to get the main asset record
            $stmt = $conn->prepare("INSERT INTO assets (asset_categories_id, description, quantity, unit_cost, office_id, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isdisi", $asset_type, $asset_name, 1, 0, $_SESSION['office_id'], $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $main_asset_id = $conn->insert_id;
                
                // Now insert into asset_items table
                $stmt2 = $conn->prepare("INSERT INTO asset_items (asset_id, description, quantity, unit, status, value, acquisition_date, office_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssdss", $main_asset_id, $description, $quantity, $unit, $status, $current_value, $purchase_date, $_SESSION['office_id']);
                $stmt2->execute();
                
                // Log asset creation
                logSystemAction($_SESSION['user_id'], 'create_asset', 'asset_items', "Created asset: {$asset_name} ({$serial_number})");
                
                $message = 'Asset added successfully';
                $message_type = 'success';
            } else {
                $message = 'Error adding asset';
                $message_type = 'danger';
            }
            $stmt->close();
            if (isset($stmt2)) $stmt2->close();
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Edit asset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_asset') {
    $asset_id = $_POST['asset_id'];
    $asset_name = trim($_POST['asset_name']);
    $asset_type = $_POST['asset_type'];
    $description = trim($_POST['description']);
    $serial_number = trim($_POST['serial_number']);
    $purchase_date = $_POST['purchase_date'];
    $purchase_cost = $_POST['purchase_cost'];
    $current_value = $_POST['current_value'];
    $location = trim($_POST['location']);
    $status = $_POST['status'];
    
    // Validation
    if (empty($asset_name) || empty($asset_type) || empty($purchase_date)) {
        $message = 'Asset name, type, and purchase date are required';
        $message_type = 'danger';
    } else {
        try {
            // Update asset
            $stmt = $conn->prepare("UPDATE assets SET asset_name = ?, asset_type = ?, description = ?, serial_number = ?, purchase_date = ?, purchase_cost = ?, current_value = ?, location = ?, status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssssdssisi", $asset_name, $asset_type, $description, $serial_number, $purchase_date, $purchase_cost, $current_value, $location, $status, $_SESSION['user_id'], $asset_id);
            
            if ($stmt->execute()) {
                // Log asset update
                logSystemAction($_SESSION['user_id'], 'update_asset', 'assets', "Updated asset {$asset_id}: {$asset_name}");
                
                $message = 'Asset updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Error updating asset';
                $message_type = 'danger';
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get asset data for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_asset') {
    $asset_id = $_GET['asset_id'];
    
    try {
        $stmt = $conn->prepare("SELECT ai.id, ai.description, ai.quantity, ai.unit, ai.status, ai.value, ai.acquisition_date, ac.name as category_name FROM asset_items ai LEFT JOIN assets a ON ai.asset_id = a.id LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id WHERE ai.id = ? AND ai.office_id = ?");
        $stmt->bind_param("ii", $asset_id, $_SESSION['office_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $asset = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode($asset);
            exit();
        } else {
            echo json_encode(['error' => 'Asset not found']);
            exit();
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Delete asset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_asset') {
    $asset_id = $_POST['asset_id'];
    
    try {
        // Get asset info before deletion for logging
        $stmt = $conn->prepare("SELECT description FROM asset_items WHERE id = ? AND office_id = ?");
        $stmt->bind_param("ii", $asset_id, $_SESSION['office_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $asset = $result->fetch_assoc();
        $stmt->close();
        
        if ($asset) {
            // Delete asset
            $stmt = $conn->prepare("DELETE FROM asset_items WHERE id = ? AND office_id = ?");
            $stmt->bind_param("ii", $asset_id, $_SESSION['office_id']);
            
            if ($stmt->execute()) {
                // Log asset deletion
                logSystemAction($_SESSION['user_id'], 'delete_asset', 'asset_items', "Deleted asset: {$asset['description']}");
                
                $message = 'Asset deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Error deleting asset';
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Asset not found';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Database error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
}

// Fetch assets for the current office
$assets = [];
try {
    $stmt = $conn->prepare("SELECT ai.id, ai.description, ai.quantity, ai.unit, ai.status, ai.value, ai.acquisition_date, ac.name as category_name FROM asset_items ai LEFT JOIN assets a ON ai.asset_id = a.id LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id WHERE ai.office_id = ? ORDER BY ai.acquisition_date DESC");
    $stmt->bind_param("i", $user_office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching assets: " . $e->getMessage());
}

// Get asset statistics
$stats = [];
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_assets, SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as active_assets, SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets, SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_assets FROM asset_items WHERE office_id = ?");
    $stmt->bind_param("i", $user_office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $asset_stats = $result->fetch_assoc();
    $stats['total_assets'] = $asset_stats['total_assets'];
    $stats['active_assets'] = $asset_stats['active_assets'];
    $stats['maintenance_assets'] = $asset_stats['maintenance_assets'];
    $stats['disposed_assets'] = $asset_stats['disposed_assets'];
    $stmt->close();
    
    // Asset type distribution
    $stmt = $conn->prepare("SELECT ac.name as category, COUNT(*) as count FROM asset_items ai LEFT JOIN assets a ON ai.asset_id = a.id LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id WHERE ai.office_id = ? GROUP BY ac.name");
    $stmt->bind_param("i", $user_office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['types'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['types'][$row['category']] = $row['count'];
    }
    $stmt->close();
    
    // Total value calculation
    $stmt = $conn->prepare("SELECT SUM(value) as total_value FROM asset_items WHERE office_id = ? AND status != 'disposed'");
    $stmt->bind_param("i", $user_office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $value_stats = $result->fetch_assoc();
    $stats['total_value'] = $value_stats['total_value'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching asset stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Assets - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
    <style>
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .asset-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.1);
            margin-bottom: 1rem;
        }
        
        .asset-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .type-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-electronics {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .type-furniture {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
        }
        
        .type-vehicle {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }
        
        .type-equipment {
            background: linear-gradient(135deg, #fd7e14 0%, #dc6502 100%);
            color: white;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-maintenance {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }
        
        .status-disposed {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .search-box {
            background: white;
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .form-control {
            background: var(--light-color);
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        /* Custom scrollbar for webkit browsers */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: rgba(25, 27, 169, 0.1);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
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
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
        }
    </link>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'Office Assets';
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
                        <i class="bi bi-box-seam"></i> Office Assets
                    </h1>
                    <p class="text-muted mb-0">Manage office assets, equipment, and inventory</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="bi bi-plus-circle"></i> Add Asset
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Message Display -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Asset Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['total_assets'] ?? 0; ?></div>
                            <div class="text-muted">Total Assets</div>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> 
                                <?php echo $stats['active_assets'] ?? 0; ?> active
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-box-seam fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                            <div class="text-muted">Total Value</div>
                            <small class="text-info">Current Assets</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-currency-dollar fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['maintenance_assets'] ?? 0; ?></div>
                            <div class="text-muted">In Maintenance</div>
                            <small class="text-warning">Under Repair</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-tools fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['disposed_assets'] ?? 0; ?></div>
                            <div class="text-muted">Disposed</div>
                            <small class="text-danger">Written Off</small>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-trash fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Office Assets Inventory</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="assetsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Asset</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Value</th>
                                        <th>Acquisition Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($assets)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <i class="bi bi-box-seam fs-1 text-muted"></i>
                                                <p class="text-muted mt-3">No assets found in the system</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($assets as $asset): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                            <i class="bi bi-box-seam fs-5"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($asset['description']); ?></div>
                                                            <small class="text-muted">ID: <?php echo htmlspecialchars($asset['id']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="type-badge type-<?php echo strtolower($asset['category_name']); ?>">
                                                        <?php echo htmlspecialchars($asset['category_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $asset['status']; ?>">
                                                        <?php echo ucfirst($asset['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>₱<?php echo number_format($asset['value'], 2); ?></strong>
                                                    <?php if ($asset['quantity'] > 1): ?>
                                                        <br><small class="text-muted">Qty: <?php echo $asset['quantity']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar"></i>
                                                        <?php echo date('M j, Y', strtotime($asset['acquisition_date'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-warning action-btn" onclick="editAsset(<?php echo $asset['id']; ?>)" title="Edit Asset">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger action-btn" onclick="deleteAsset(<?php echo $asset['id']; ?>)" title="Delete Asset">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Add Asset Modal -->
    <div class="modal fade" id="addAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_asset">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_name" class="form-label">Asset Name</label>
                                    <input type="text" class="form-control" id="asset_name" name="asset_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_type" class="form-label">Asset Type</label>
                                    <select class="form-control" id="asset_type" name="asset_type" required>
                                        <option value="">Select Type</option>
                                        <option value="electronics">Electronics</option>
                                        <option value="furniture">Furniture</option>
                                        <option value="vehicle">Vehicle</option>
                                        <option value="equipment">Equipment</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="purchase_cost" class="form-label">Purchase Cost (₱)</label>
                                    <input type="number" class="form-control" id="purchase_cost" name="purchase_cost" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_value" class="form-label">Current Value (₱)</label>
                                    <input type="number" class="form-control" id="current_value" name="current_value" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="active">Active</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="disposed">Disposed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Asset Modal -->
    <div class="modal fade" id="editAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editAssetForm">
                    <input type="hidden" name="action" value="edit_asset">
                    <input type="hidden" name="asset_id" id="editAssetId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_asset_name" class="form-label">Asset Name</label>
                                    <input type="text" class="form-control" id="edit_asset_name" name="asset_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_asset_type" class="form-label">Asset Type</label>
                                    <select class="form-control" id="edit_asset_type" name="asset_type" required>
                                        <option value="">Select Type</option>
                                        <option value="electronics">Electronics</option>
                                        <option value="furniture">Furniture</option>
                                        <option value="vehicle">Vehicle</option>
                                        <option value="equipment">Equipment</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="edit_serial_number" name="serial_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="edit_location" name="location" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="edit_purchase_date" name="purchase_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_purchase_cost" class="form-label">Purchase Cost (₱)</label>
                                    <input type="number" class="form-control" id="edit_purchase_cost" name="purchase_cost" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_current_value" class="form-label">Current Value (₱)</label>
                                    <input type="number" class="form-control" id="edit_current_value" name="current_value" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-control" id="edit_status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="active">Active</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="disposed">Disposed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Update Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
        });
        
        // Fix modal backdrop issues
        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('show.bs.modal', function () {
                    // Ensure proper backdrop
                    document.body.classList.add('modal-open');
                });
                
                logoutModal.addEventListener('hidden.bs.modal', function () {
                    // Clean up backdrop
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                });
                
                // Ensure cancel button works properly
                const cancelButton = logoutModal.querySelector('[data-bs-dismiss="modal"]');
                if (cancelButton) {
                    cancelButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        const modal = bootstrap.Modal.getInstance(logoutModal);
                        if (modal) {
                            modal.hide();
                        }
                    });
                }
            }
        });
        
        // Initialize DataTable
        $(document).ready(function() {
            $('#assetsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']], // Sort by asset name ascending
                language: {
                    search: "Search assets:",
                    lengthMenu: "Show _MENU_ assets",
                    info: "Showing _START_ to _END_ of _TOTAL_ assets",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
        
        // Edit asset function
        function editAsset(assetId) {
            // Fetch asset data
            $.ajax({
                url: 'office_assets.php?action=get_asset&asset_id=' + assetId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        alert('Error: ' + response.error);
                    } else {
                        // Populate form fields
                        $('#editAssetId').val(response.id);
                        $('#edit_asset_name').val(response.asset_name);
                        $('#edit_asset_type').val(response.asset_type);
                        $('#edit_description').val(response.description);
                        $('#edit_serial_number').val(response.serial_number);
                        $('#edit_location').val(response.location);
                        $('#edit_purchase_date').val(response.purchase_date);
                        $('#edit_purchase_cost').val(response.purchase_cost);
                        $('#edit_current_value').val(response.current_value);
                        $('#edit_status').val(response.status);
                        
                        // Show modal
                        $('#editAssetModal').modal('show');
                    }
                },
                error: function() {
                    alert('Error fetching asset data');
                }
            });
        }
        
        // Delete asset function
        function deleteAsset(assetId) {
            if (confirm('Are you sure you want to delete this asset? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_asset';
                
                const assetIdInput = document.createElement('input');
                assetIdInput.type = 'hidden';
                assetIdInput.name = 'asset_id';
                assetIdInput.value = assetId;
                
                form.appendChild(actionInput);
                form.appendChild(assetIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Clear form on add modal close
        document.getElementById('addAssetModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
        
        // Clear form on edit modal close
        document.getElementById('editAssetModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
        
        // Handle edit form submission
        document.getElementById('editAssetForm').addEventListener('submit', function (e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('office_assets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Reload page to show updated data
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating asset');
            });
        });
    </script>
</body>
</html>
