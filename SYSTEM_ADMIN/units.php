<?php
ob_start();
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

// Check if user has correct role (system_admin only)
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log units page access
logSystemAction($_SESSION['user_id'], 'access', 'units', 'System Admin accessed units management page');

// Handle CRUD operations
$message = '';
$message_type = '';

// CREATE - Add new unit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $unit_name = trim($_POST['unit_name'] ?? '');
    $unit_code = trim($_POST['unit_code'] ?? '');
    $unit_type = $_POST['unit_type'] ?? 'other';
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($unit_name)) {
        $message = "Unit name is required.";
        $message_type = "danger";
    } elseif (empty($unit_code)) {
        $message = "Unit code is required.";
        $message_type = "danger";
    } elseif (!in_array($unit_type, ['count', 'weight', 'length', 'volume', 'area', 'time', 'other'])) {
        $message = "Invalid unit type.";
        $message_type = "danger";
    } else {
        try {
            // Check if unit code already exists
            $check_stmt = $conn->prepare("SELECT id FROM units WHERE unit_code = ?");
            $check_stmt->bind_param("s", $unit_code);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $message = "Unit code already exists.";
                $message_type = "danger";
            } else {
                $stmt = $conn->prepare("INSERT INTO units (unit_name, unit_code, unit_type, description, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $unit_name, $unit_code, $unit_type, $description, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $message = "Unit added successfully!";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'unit_added', 'units_management', "Added unit: {$unit_name} ({$unit_code})");
                } else {
                    throw new Exception("Failed to add unit: " . $stmt->error);
                }
                $stmt->close();
            }
            $check_stmt->close();
        } catch (Exception $e) {
            $message = "Error adding unit: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// UPDATE - Edit unit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $unit_name = trim($_POST['unit_name'] ?? '');
    $unit_code = trim($_POST['unit_code'] ?? '');
    $unit_type = $_POST['unit_type'] ?? 'other';
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    if ($id <= 0) {
        $message = "Invalid unit ID.";
        $message_type = "danger";
    } elseif (empty($unit_name)) {
        $message = "Unit name is required.";
        $message_type = "danger";
    } elseif (empty($unit_code)) {
        $message = "Unit code is required.";
        $message_type = "danger";
    } elseif (!in_array($unit_type, ['count', 'weight', 'length', 'volume', 'area', 'time', 'other'])) {
        $message = "Invalid unit type.";
        $message_type = "danger";
    } else {
        try {
            // Check if unit code already exists (excluding current record)
            $check_stmt = $conn->prepare("SELECT id FROM units WHERE unit_code = ? AND id != ?");
            $check_stmt->bind_param("si", $unit_code, $id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $message = "Unit code already exists.";
                $message_type = "danger";
            } else {
                $stmt = $conn->prepare("UPDATE units SET unit_name = ?, unit_code = ?, unit_type = ?, description = ?, status = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $unit_name, $unit_code, $unit_type, $description, $status, $_SESSION['user_id'], $id);
                
                if ($stmt->execute()) {
                    $message = "Unit updated successfully!";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'unit_updated', 'units_management', "Updated unit: {$unit_name} ({$unit_code})");
                } else {
                    throw new Exception("Failed to update unit: " . $stmt->error);
                }
                $stmt->close();
            }
            $check_stmt->close();
        } catch (Exception $e) {
            $message = "Error updating unit: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// DELETE - Delete unit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        $message = "Invalid unit ID.";
        $message_type = "danger";
    } else {
        try {
            // Check if unit is being used in assets or consumables
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM assets WHERE unit = ? UNION SELECT COUNT(*) as count FROM consumables WHERE unit = ?");
            $unit_code = '';
            $get_unit_stmt = $conn->prepare("SELECT unit_code FROM units WHERE id = ?");
            $get_unit_stmt->bind_param("i", $id);
            $get_unit_stmt->execute();
            $result = $get_unit_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $unit_code = $row['unit_code'];
            }
            $get_unit_stmt->close();
            
            $check_stmt->bind_param("ss", $unit_code, $unit_code);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $total_count = 0;
            while ($row = $result->fetch_assoc()) {
                $total_count += $row['count'];
            }
            $check_stmt->close();
            
            if ($total_count > 0) {
                $message = "Cannot delete unit. It is being used in {$total_count} asset(s) or consumable(s).";
                $message_type = "danger";
            } else {
                // Get unit info for logging
                $unit_info = $conn->prepare("SELECT unit_name, unit_code FROM units WHERE id = ?");
                $unit_info->bind_param("i", $id);
                $unit_info->execute();
                $unit_result = $unit_info->get_result();
                $unit_data = $unit_result->fetch_assoc();
                $unit_info->close();
                
                $stmt = $conn->prepare("DELETE FROM units WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Unit deleted successfully!";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'unit_deleted', 'units_management', "Deleted unit: {$unit_data['unit_name']} ({$unit_data['unit_code']})");
                } else {
                    throw new Exception("Failed to delete unit: " . $stmt->error);
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $message = "Error deleting unit: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// AJAX handler to get unit data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_unit') {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id > 0) {
        try {
            $stmt = $conn->prepare("SELECT * FROM units WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'unit' => $row]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Unit not found']);
            }
            $stmt->close();
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Get system settings for theme
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback to default
    $system_settings['system_name'] = 'PIMS';
}

// Get units
$units = [];
try {
    $sql = "SELECT * FROM units ORDER BY unit_type, unit_name";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
    }
} catch (Exception $e) {
    $message = "Error fetching units: " . $e->getMessage();
    $message_type = "danger";
}

// Get unit statistics
$stats = [];
try {
    $sql = "SELECT 
                COUNT(*) as total_units,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_units,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_units,
                SUM(CASE WHEN unit_type = 'count' THEN 1 ELSE 0 END) as count_units,
                SUM(CASE WHEN unit_type = 'weight' THEN 1 ELSE 0 END) as weight_units,
                SUM(CASE WHEN unit_type = 'length' THEN 1 ELSE 0 END) as length_units,
                SUM(CASE WHEN unit_type = 'volume' THEN 1 ELSE 0 END) as volume_units,
                SUM(CASE WHEN unit_type = 'area' THEN 1 ELSE 0 END) as area_units,
                SUM(CASE WHEN unit_type = 'time' THEN 1 ELSE 0 END) as time_units,
                SUM(CASE WHEN unit_type = 'other' THEN 1 ELSE 0 END) as other_units
            FROM units";
    $result = $conn->query($sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

// Helper function for unit type badge colors
function getUnitTypeBadgeColor($type) {
    $colors = [
        'count' => 'primary',
        'weight' => 'success',
        'length' => 'info',
        'volume' => 'warning',
        'area' => 'secondary',
        'time' => 'dark',
        'other' => 'light'
    ];
    return $colors[$type] ?? 'secondary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Units Management - <?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
        }
        
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

        .metric-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .metric-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Units Management';
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
                        <i class="bi bi-rulers"></i> Units Management
                    </h1>
                    <p class="text-muted mb-0">Manage standardized units of measurement for assets and consumables</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                        <i class="bi bi-plus-circle"></i> Add Unit
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportUnits()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo $stats['total_units'] ?? 0; ?></div>
                    <div class="metric-label"><i class="bi bi-rulers"></i> Total Units</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo $stats['active_units'] ?? 0; ?></div>
                    <div class="metric-label"><i class="bi bi-check-circle"></i> Active Units</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo $stats['inactive_units'] ?? 0; ?></div>
                    <div class="metric-label"><i class="bi bi-x-circle"></i> Inactive Units</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo ($stats['count_units'] ?? 0) + ($stats['weight_units'] ?? 0) + ($stats['length_units'] ?? 0); ?></div>
                    <div class="metric-label"><i class="bi bi-tags"></i> Common Types</div>
                </div>
            </div>
        </div>
        
        <!-- Units Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-rulers"></i> Units Management</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Units List</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select class="form-select form-select-sm" id="typeFilter">
                                            <option value="">All Types</option>
                                            <option value="count">Count</option>
                                            <option value="weight">Weight</option>
                                            <option value="length">Length</option>
                                            <option value="volume">Volume</option>
                                            <option value="area">Area</option>
                                            <option value="time">Time</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-select form-select-sm" id="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Search removed - using DataTables built-in search -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" id="unitsTable">
                    <thead>
                        <tr>
                            <th>Unit Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($units)): ?>
                            <?php foreach ($units as $unit): ?>
                                <tr data-type="<?php echo htmlspecialchars($unit['unit_type']); ?>" data-status="<?php echo htmlspecialchars($unit['status']); ?>">
                                    <td><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($unit['unit_code']); ?></code></td>
                                    <td>
                                        <span class="badge bg-<?php echo getUnitTypeBadgeColor($unit['unit_type']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($unit['unit_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($unit['description'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $unit['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($unit['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($unit['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editUnit(<?php echo $unit['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUnit(<?php echo $unit['id']; ?>, '<?php echo htmlspecialchars($unit['unit_name']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No units found. Click "Add Unit" to create your first unit.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
                </div>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Add Unit Modal -->
    <div class="modal fade" id="addUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Unit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Name *</label>
                                    <input type="text" class="form-control" name="unit_name" required placeholder="e.g., piece">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Code *</label>
                                    <input type="text" class="form-control" name="unit_code" required placeholder="e.g., pc" maxlength="20">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Type *</label>
                                    <select class="form-select" name="unit_type" required>
                                        <option value="">Select Type</option>
                                        <option value="count">Count</option>
                                        <option value="weight">Weight</option>
                                        <option value="length">Length</option>
                                        <option value="volume">Volume</option>
                                        <option value="area">Area</option>
                                        <option value="time">Time</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe when this unit is used"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Unit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Unit Modal -->
    <div class="modal fade" id="editUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Unit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editUnitId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Name *</label>
                                    <input type="text" class="form-control" name="unit_name" id="editUnitName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Code *</label>
                                    <input type="text" class="form-control" name="unit_code" id="editUnitCode" required maxlength="20">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Type *</label>
                                    <select class="form-select" name="unit_type" id="editUnitType" required>
                                        <option value="">Select Type</option>
                                        <option value="count">Count</option>
                                        <option value="weight">Weight</option>
                                        <option value="length">Length</option>
                                        <option value="volume">Volume</option>
                                        <option value="area">Area</option>
                                        <option value="time">Time</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" id="editUnitStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editUnitDescription" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Update Unit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the unit "<strong id="deleteUnitName"></strong>"?</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> This action cannot be undone.
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteUnitId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Unit
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
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>

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
        }

        // Initialize DataTable
        let unitsTable;
        
            // Check if table has data rows before initializing DataTables
            const tableBody = $('#unitsTable tbody');
            const hasData = tableBody.find('tr').length > 0 && !tableBody.find('td[colspan]').length;
            
            // Initialize DataTable with error handling
            try {
                if (hasData) {
                    // Only initialize DataTables if there's actual data
                    unitsTable = $('#unitsTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        order: [[0, 'asc']], // Sort by Unit Name by default
                        columnDefs: [
                            {
                                targets: 0, // Unit Name column
                                orderable: true,
                                render: function(data, type, row) {
                                    if (type === 'display') {
                                        return '<strong>' + data + '</strong>';
                                    }
                                    return data;
                                }
                            },
                            {
                                targets: 1, // Code column
                                orderable: true,
                                render: function(data, type, row) {
                                    if (type === 'display') {
                                        return '<code>' + data + '</code>';
                                    }
                                    return data;
                                }
                            },
                            {
                                targets: 2, // Type column
                                orderable: true,
                                render: function(data, type, row) {
                                    if (type === 'display') {
                                        const colors = {
                                            'count': 'primary',
                                            'weight': 'success',
                                            'length': 'info',
                                            'volume': 'warning',
                                            'area': 'secondary',
                                            'time': 'dark',
                                            'other': 'light'
                                        };
                                        return '<span class="badge bg-' + (colors[data] || 'secondary') + '">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                                    }
                                    return data;
                                }
                            },
                            {
                                targets: 3, // Description column
                                orderable: true
                            },
                            {
                                targets: 4, // Status column
                                orderable: true,
                                render: function(data, type, row) {
                                    if (type === 'display') {
                                        return '<span class="badge bg-' + (data === 'active' ? 'success' : 'secondary') + '">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                                    }
                                    return data;
                                }
                            },
                            {
                                targets: 5, // Created column
                                orderable: true
                            },
                            {
                                targets: 6, // Actions column
                                orderable: false,
                                className: 'text-center',
                                render: function(data, type, row) {
                                    return data;
                                }
                            }
                        ],
                        dom: 'Bfrtip',
                        buttons: [
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                                className: 'btn btn-sm btn-success',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5]
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                                className: 'btn btn-sm btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5]
                                }
                            },
                            {
                                extend: 'print',
                                text: '<i class="bi bi-printer"></i> Print',
                                className: 'btn btn-sm btn-info',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5]
                                }
                            }
                        ]
                    });
                } else {
                    // No data - don't initialize DataTables
                }
            } catch (error) {
                // Fallback: show basic table without DataTables
            }
        });
        
        // Filter functionality
        document.getElementById('typeFilter').addEventListener('change', function() {
            filterTable();
        });
        
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTable();
        });
        
        function filterTable() {
            const typeFilter = document.getElementById('typeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#unitsTable tbody tr');
            
            rows.forEach(row => {
                const type = row.getAttribute('data-type');
                const status = row.getAttribute('data-status');
                
                const typeMatch = !typeFilter || type === typeFilter;
                const statusMatch = !statusFilter || status === statusFilter;
                
                row.style.display = typeMatch && statusMatch ? '' : 'none';
            });
        }
        
        // Edit unit function
        function editUnit(id) {
            fetch('units.php?action=get_unit&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const unit = data.unit;
                        document.getElementById('editUnitId').value = unit.id;
                        document.getElementById('editUnitName').value = unit.unit_name;
                        document.getElementById('editUnitCode').value = unit.unit_code;
                        document.getElementById('editUnitType').value = unit.unit_type;
                        document.getElementById('editUnitStatus').value = unit.status;
                        document.getElementById('editUnitDescription').value = unit.description || '';
                        
                        const modal = new bootstrap.Modal(document.getElementById('editUnitModal'));
                        modal.show();
                    } else {
                        alert('Error loading unit data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading unit data');
                });
        }
        
        // Delete unit function
        function deleteUnit(id, name) {
            document.getElementById('deleteUnitId').value = id;
            document.getElementById('deleteUnitName').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteUnitModal'));
            modal.show();
        }
        
        // Export units function
        function exportUnits() {
            if (unitsTable) {
                unitsTable.button().add(0, {
                    extend: 'excel',
                    text: 'Export All Units',
                    className: 'btn btn-success'
                });
                unitsTable.button(0).trigger();
            }
        }
    </script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
