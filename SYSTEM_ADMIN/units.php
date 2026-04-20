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


        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.125rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .unit-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .modal-header {
            background: var(--primary-gradient);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 27, 169, 0.05);
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
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="unitActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="unitActionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                                    <i class="bi bi-plus-circle text-primary"></i> Add Unit
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportUnits()">
                                    <i class="bi bi-download text-success"></i> Export Units
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="refreshUnits()">
                                    <i class="bi bi-arrow-clockwise text-warning"></i> Refresh Data
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="printUnits()">
                                    <i class="bi bi-printer text-secondary"></i> Print List
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        
        <!-- Units Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Units List</h5>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($units)): ?>
                            <?php foreach ($units as $unit): ?>
                                <tr data-type="<?php echo htmlspecialchars($unit['unit_type']); ?>" data-status="<?php echo htmlspecialchars($unit['status']); ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($unit['unit_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="unit-badge">
                                            <?php echo htmlspecialchars($unit['unit_code']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getUnitTypeBadgeColor($unit['unit_type']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($unit['unit_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($unit['description'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $unit['status']; ?>">
                                            <?php echo ucfirst(htmlspecialchars($unit['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action" onclick="editUnit(<?php echo $unit['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteUnit(<?php echo $unit['id']; ?>, '<?php echo htmlspecialchars($unit['unit_name']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
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
                                orderable: true
                            },
                            {
                                targets: 2, // Type column
                                orderable: true
                            },
                            {
                                targets: 3, // Description column
                                orderable: true
                            },
                            {
                                targets: 4, // Status column
                                orderable: true
                            },
                            {
                                targets: 5, // Actions column
                                orderable: false,
                                className: 'text-center',
                                render: function(data, type, row) {
                                    return data;
                                }
                            }
                        ],
                        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip'
                    });
                } else {
                    // No data - don't initialize DataTables
                }
            } catch (error) {
                // Fallback: show basic table without DataTables
            }
        });
        
        
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
            window.location.href = 'export_units.php';
        }
        
        // Refresh units function
        function refreshUnits() {
            // Show loading state
            showAlert('Refreshing units data...', 'info');
            
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        // Print units function
        function printUnits() {
            // Get all data from DataTable (not just current page)
            const table = $('#unitsTable').DataTable();
            const allData = table.data().toArray();
            
            if (allData.length === 0) {
                showAlert('No units data to print', 'warning');
                return;
            }
            
            // Create a print preview window
            const printWindow = window.open('', '_blank', 'width=1000,height=800');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Units - Print Preview</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 0.5in;
                        }
                        
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 12px;
                            color: #333;
                            background: white;
                        }
                        
                        .preview-toolbar {
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            z-index: 1000;
                            background: #191BA9;
                            color: white;
                            padding: 12px 20px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                        }
                        
                        .preview-toolbar .title {
                            font-weight: bold;
                            font-size: 14px;
                            display: flex;
                            align-items: center;
                        }
                        
                        .preview-toolbar .actions {
                            display: flex;
                            gap: 10px;
                        }
                        
                        .preview-toolbar button {
                            padding: 6px 12px;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 12px;
                            transition: all 0.3s ease;
                        }
                        
                        .preview-toolbar .btn-print {
                            background: #28a745;
                            color: white;
                        }
                        
                        .preview-toolbar .btn-print:hover {
                            background: #218838;
                            transform: translateY(-1px);
                        }
                        
                        .preview-toolbar .btn-close {
                            background: #6c757d;
                            color: white;
                        }
                        
                        .preview-toolbar .btn-close:hover {
                            background: #5a6268;
                            transform: translateY(-1px);
                        }
                        
                        .print-container {
                            width: 100%;
                            max-width: 8.5in;
                            margin: 0 auto;
                            padding: 60px 20px 20px;
                            background: white;
                            min-height: 11in;
                            position: relative;
                        }
                        
                        @media screen {
                            body {
                                background: #525659;
                                padding: 0;
                            }
                            .print-container {
                                background: white;
                                box-shadow: 0 0 20px rgba(0,0,0,0.5);
                                margin: 60px auto 20px;
                            }
                        }
                        
                        @media print {
                            .no-print { display: none !important; }
                            body { background: white; margin: 0; padding: 0; }
                            .print-container { 
                                box-shadow: none; 
                                margin: 0 auto; 
                                padding: 20px;
                                width: 100%;
                            }
                        }
                        
                        .report-header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 2px solid #191BA9;
                            padding-bottom: 15px;
                        }
                        
                        .report-header h1 {
                            color: #191BA9;
                            font-size: 24px;
                            font-weight: bold;
                            margin-bottom: 5px;
                            text-transform: uppercase;
                        }
                        
                        .report-header .subtitle {
                            color: #666;
                            font-size: 14px;
                            margin-bottom: 10px;
                        }
                        
                        .report-header .meta {
                            color: #333;
                            font-size: 12px;
                            font-weight: bold;
                        }
                        
                        .units-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 20px 0;
                        }
                        
                        .units-table th,
                        .units-table td {
                            border: 1px solid #333;
                            padding: 10px;
                            text-align: left;
                            vertical-align: top;
                        }
                        
                        .units-table th {
                            background-color: #f8f9fa;
                            font-weight: bold;
                            color: #333;
                            text-transform: uppercase;
                            font-size: 11px;
                        }
                        
                        .units-table .unit-name {
                            font-weight: bold;
                            min-width: 150px;
                        }
                        
                        .units-table .unit-code {
                            font-family: monospace;
                            background-color: #f8f9fa;
                            padding: 4px 8px;
                            border-radius: 3px;
                            font-size: 11px;
                            min-width: 100px;
                            text-align: center;
                        }
                        
                        .units-table .unit-type {
                            max-width: 100px;
                            word-wrap: break-word;
                            font-size: 11px;
                        }
                        
                        .units-table .description {
                            max-width: 200px;
                            word-wrap: break-word;
                            font-size: 11px;
                        }
                        
                        .units-table .status-active {
                            color: #28a745;
                            font-weight: bold;
                            text-align: center;
                            text-transform: uppercase;
                            font-size: 11px;
                        }
                        
                        .units-table .status-inactive {
                            color: #6c757d;
                            font-weight: bold;
                            text-align: center;
                            text-transform: uppercase;
                            font-size: 11px;
                        }
                        
                        .report-footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 1px solid #ddd;
                            font-size: 11px;
                            color: #666;
                            text-align: center;
                        }
                        
                        .report-footer .summary {
                            font-weight: bold;
                            margin-bottom: 5px;
                            color: #333;
                        }
                        
                        .report-footer .user-info {
                            font-style: italic;
                        }
                        
                        /* Alternating row colors */
                        .units-table tbody tr:nth-child(even) {
                            background-color: #f9f9f9;
                        }
                        
                        .units-table tbody tr:hover {
                            background-color: #f0f8ff;
                        }
                    </style>
                </head>
                <body>
                    <div class="preview-toolbar no-print">
                        <div class="title">
                            <i class="bi bi-printer-fill me-2"></i>Units Print Preview
                        </div>
                        <div class="actions">
                            <button onclick="window.print()" class="btn-print">
                                <i class="bi bi-printer me-1"></i>Print Report
                            </button>
                            <button onclick="window.close()" class="btn-close">
                                <i class="bi bi-x-lg me-1"></i>Close Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="print-container">
                        <div class="report-header">
                            <h1>Units Report</h1>
                            <div class="subtitle">Property and Inventory Management System</div>
                            <div class="meta">
                                Generated on: ${new Date().toLocaleString()} | Total Units: ${allData.length}
                            </div>
                        </div>
                        
                        <table class="units-table">
                            <thead>
                                <tr>
                                    <th>Unit Name</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${allData.map((row, index) => {
                                    // Extract data from DataTable row
                                    const unitName = row[0] || '';
                                    const unitCode = row[1] || '';
                                    const unitType = row[2] || '';
                                    const description = row[3] || '';
                                    const status = row[4] || 'inactive';
                                    
                                    // Clean text content
                                    const cleanName = unitName.replace(/<[^>]*>/g, '').trim();
                                    const cleanCode = unitCode.replace(/<[^>]*>/g, '').trim();
                                    const cleanType = unitType.replace(/<[^>]*>/g, '').trim();
                                    const cleanDescription = description.replace(/<[^>]*>/g, '').trim();
                                    const cleanStatus = status.replace(/<[^>]*>/g, '').trim().toLowerCase();
                                    
                                    const statusClass = cleanStatus === 'active' ? 'status-active' : 'status-inactive';
                                    
                                    return `
                                        <tr>
                                            <td class="unit-name">${cleanName}</td>
                                            <td><span class="unit-code">${cleanCode}</span></td>
                                            <td class="unit-type">${cleanType}</td>
                                            <td class="description">${cleanDescription}</td>
                                            <td class="${statusClass}">${cleanStatus.charAt(0).toUpperCase() + cleanStatus.slice(1)}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                        
                        <div class="report-footer">
                            <div class="summary">
                                Report Summary: ${allData.length} units exported from PIMS Asset Management System
                            </div>
                            <div class="user-info">
                                Printed by: System Administrator | 
                                Date: ${new Date().toLocaleDateString()} | 
                                Page: <span class="page-number"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bootstrap Icons -->
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
                </body>
                </html>
            `);
            printWindow.document.close();
            
            // Add page numbering
            printWindow.onload = function() {
                // Add page numbers
                const pageNumbers = printWindow.document.querySelectorAll('.page-number');
                pageNumbers.forEach((element, index) => {
                    element.textContent = index + 1;
                });
            };
        }
        
        // Show alert function
        function showAlert(message, type) {
            // Remove existing alerts
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            
            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert after page header
            const pageHeader = document.querySelector('.page-header');
            pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }
    </script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
