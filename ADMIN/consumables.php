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

// Log consumables page access
logSystemAction($_SESSION['user_id'], 'access', 'consumables', 'Admin accessed consumables page');

// Handle CRUD operations
$message = '';
$message_type = '';

// CREATE - Add new consumable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_cost = floatval($_POST['unit_cost'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 10);
    $office_id = intval($_POST['office_id'] ?? 0);
    
    // Check if consumable with same description already exists in the same office
    $existing_consumable = null;
    $check_stmt = $conn->prepare("SELECT id, quantity, unit_cost FROM consumables WHERE description = ? AND office_id = ?");
    $check_stmt->bind_param("si", $description, $office_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $existing_consumable = $check_result->fetch_assoc();
    }
    $check_stmt->close();
    
    // Validation
    if (empty($description)) {
        $message = "Consumable description is required.";
        $message_type = "danger";
    } elseif ($office_id <= 0) {
        $message = "Please select an office.";
        $message_type = "danger";
    } elseif ($quantity <= 0) {
        $message = "Quantity must be greater than 0.";
        $message_type = "danger";
    } elseif ($unit_cost < 0) {
        $message = "Unit cost cannot be negative.";
        $message_type = "danger";
    } elseif ($reorder_level < 0) {
        $message = "Reorder level cannot be negative.";
        $message_type = "danger";
    } else {
        try {
            if ($existing_consumable) {
                // Update existing consumable quantity
                $new_quantity = $existing_consumable['quantity'] + $quantity;
                $update_stmt = $conn->prepare("UPDATE consumables SET quantity = ?, unit_cost = ?, reorder_level = ? WHERE id = ?");
                $update_stmt->bind_param("iddi", $new_quantity, $unit_cost, $reorder_level, $existing_consumable['id']);
                
                if ($update_stmt->execute()) {
                    $message = "Consumable quantity updated successfully! Added {$quantity} more items to existing consumable.";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'consumable_quantity_updated', 'consumable_management', "Updated quantity for existing consumable: {$description}");
                } else {
                    throw new Exception("Failed to update consumable: " . $update_stmt->error);
                }
                $update_stmt->close();
            } else {
                // Insert new consumable
                $insert_stmt = $conn->prepare("INSERT INTO consumables (description, quantity, unit_cost, reorder_level, office_id) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("sidii", $description, $quantity, $unit_cost, $reorder_level, $office_id);
                
                if ($insert_stmt->execute()) {
                    $message = "Consumable added successfully!";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'consumable_added', 'consumable_management', "Added consumable: {$description}");
                } else {
                    throw new Exception("Failed to insert consumable: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            }
        } catch (Exception $e) {
            $message = "Error adding consumable: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// UPDATE - Update consumable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $consumable_id = intval($_POST['consumable_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_cost = floatval($_POST['unit_cost'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 10);
    $office_id = intval($_POST['office_id'] ?? 0);
    
    // Validation
    if (empty($description)) {
        $message = "Consumable description is required.";
        $message_type = "danger";
    } elseif ($office_id <= 0) {
        $message = "Please select an office.";
        $message_type = "danger";
    } elseif ($quantity < 0) {
        $message = "Quantity cannot be negative.";
        $message_type = "danger";
    } elseif ($unit_cost < 0) {
        $message = "Unit cost cannot be negative.";
        $message_type = "danger";
    } elseif ($reorder_level < 0) {
        $message = "Reorder level cannot be negative.";
        $message_type = "danger";
    } else {
        try {
            $update_stmt = $conn->prepare("UPDATE consumables SET description = ?, quantity = ?, unit_cost = ?, reorder_level = ?, office_id = ? WHERE id = ?");
            $update_stmt->bind_param("sidiii", $description, $quantity, $unit_cost, $reorder_level, $office_id, $consumable_id);
            
            if ($update_stmt->execute()) {
                $message = "Consumable updated successfully!";
                $message_type = "success";
                logSystemAction($_SESSION['user_id'], 'consumable_updated', 'consumable_management', "Updated consumable: {$description}");
            } else {
                throw new Exception("Failed to update consumable: " . $update_stmt->error);
            }
            $update_stmt->close();
        } catch (Exception $e) {
            $message = "Error updating consumable: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// UPDATE - Update consumable reorder level only
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_reorder') {
    $consumable_id = intval($_POST['consumable_id'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 10);
    
    // Validation
    if ($consumable_id <= 0) {
        $message = "Invalid consumable ID.";
        $message_type = "danger";
    } elseif ($reorder_level < 0) {
        $message = "Reorder level cannot be negative.";
        $message_type = "danger";
    } else {
        try {
            // Get consumable info for logging
            $info_stmt = $conn->prepare("SELECT description FROM consumables WHERE id = ?");
            $info_stmt->bind_param("i", $consumable_id);
            $info_stmt->execute();
            $info_result = $info_stmt->get_result();
            $consumable_info = $info_result->fetch_assoc();
            $info_stmt->close();
            
            $update_stmt = $conn->prepare("UPDATE consumables SET reorder_level = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $reorder_level, $consumable_id);
            
            if ($update_stmt->execute()) {
                $message = "Reorder level updated successfully!";
                $message_type = "success";
                logSystemAction($_SESSION['user_id'], 'consumable_reorder_updated', 'consumable_management', "Updated reorder level for consumable: " . ($consumable_info['description'] ?? 'Unknown') . " to {$reorder_level}");
            } else {
                throw new Exception("Failed to update reorder level: " . $update_stmt->error);
            }
            $update_stmt->close();
        } catch (Exception $e) {
            $message = "Error updating reorder level: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// AJAX handler to get consumable data for reorder level editing
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get') {
    $consumable_id = intval($_GET['id'] ?? 0);
    
    if ($consumable_id > 0) {
        try {
            $query = "SELECT id, description, quantity, reorder_level FROM consumables WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $consumable_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Consumable not found']);
            }
            $stmt->close();
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid consumable ID']);
        exit;
    }
}

// Handle filter parameters
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get consumables with office information
$consumables = [];
try {
    $sql = "SELECT c.*, o.office_name, fo.office_name as for_office_name
            FROM consumables c 
            LEFT JOIN offices o ON c.office_id = o.id 
            LEFT JOIN offices fo ON c.for_office_id = fo.id 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($office_filter > 0) {
        $sql .= " AND c.office_id = ?";
        $params[] = $office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $sql .= " AND (c.description LIKE ? OR o.office_name LIKE ? OR fo.office_name LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $consumables[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    $message = "Error fetching consumables: " . $e->getMessage();
    $message_type = "danger";
}

// Get offices for dropdown
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

// Get consumable statistics
$stats = [];
try {
    $sql = "SELECT 
                COUNT(*) as total_consumables,
                SUM(quantity) as total_quantity,
                SUM(quantity * unit_cost) as total_value,
                COUNT(CASE WHEN quantity <= reorder_level THEN 1 END) as low_stock_count,
                COUNT(DISTINCT office_id) as total_offices
            FROM consumables";
    $result = $conn->query($sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumable Management - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .stats-number {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            word-wrap: break-word;
            line-height: 1.2;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
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
        
        .text-value {
            font-weight: 600;
            color: #191BA9;
        }
        
        .low-stock {
            background-color: #fff3cd !important;
        }
        
        .low-stock-badge {
            background-color: #ffc107;
            color: #000;
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.75rem;
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
    $page_title = 'Consumable Management';
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
                        <i class="bi bi-box-seam"></i> Consumable Management
                    </h1>
                    <p class="text-muted mb-0">Manage and track organizational consumables</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConsumableModal">
                        <i class="bi bi-plus-circle"></i> Add Consumable
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportConsumables()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
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
        
        <!-- Consumables Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Consumables List</h5>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" id="officeFilter">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search consumables..." value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="consumablesTable">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Value</th>
                            <th>Reorder Level</th>
                            <th>Office</th>
                            <th>For Office</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($consumables)): ?>
                            <?php foreach ($consumables as $consumable): ?>
                                <tr <?php echo ($consumable['quantity'] <= $consumable['reorder_level']) ? 'class="low-stock"' : ''; ?>>
                                    <td>
                                        <?php echo htmlspecialchars($consumable['description']); ?>
                                        <?php if ($consumable['quantity'] <= $consumable['reorder_level']): ?>
                                            <span class="low-stock-badge ms-2">Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $consumable['quantity']; ?></td>
                                    <td><?php echo number_format($consumable['unit_cost'], 2); ?></td>
                                    <td class="text-value"><?php echo number_format($consumable['quantity'] * $consumable['unit_cost'], 2); ?></td>
                                    <td><?php echo $consumable['reorder_level']; ?></td>
                                    <td><?php echo htmlspecialchars($consumable['office_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($consumable['for_office_name'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo date('M j, Y', strtotime($consumable['created_at'])); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning" onclick="editReorderLevel(<?php echo $consumable['id']; ?>, '<?php echo htmlspecialchars($consumable['description']); ?>', <?php echo $consumable['quantity']; ?>)">
                                            <i class="bi bi-pencil"></i> Edit Reorder
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="openReleaseModal(<?php echo $consumable['id']; ?>)">
                                            <i class="bi bi-box-arrow-right"></i> Release
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No consumables found. Click "Add Consumable" to create your first consumable.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Add Consumable Modal -->
    <div class="modal fade" id="addConsumableModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Consumable</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <input type="text" class="form-control" name="description" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" name="quantity" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Cost *</label>
                                    <input type="number" class="form-control" name="unit_cost" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Reorder Level *</label>
                                    <input type="number" class="form-control" name="reorder_level" min="0" value="10" required>
                                    <small class="text-muted">Alert when quantity reaches this level</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Office *</label>
                                    <select class="form-select" name="office_id" required>
                                        <option value="">Select Office</option>
                                        <?php foreach ($offices as $office): ?>
                                            <option value="<?php echo $office['id']; ?>">
                                                <?php echo htmlspecialchars($office['office_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Consumable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Reorder Level Modal -->
    <div class="modal fade" id="editReorderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Reorder Level</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editReorderForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_reorder">
                        <input type="hidden" name="consumable_id" id="editReorderId">
                        
                        <div class="mb-3">
                            <label class="form-label">Consumable</label>
                            <input type="text" class="form-control" id="editReorderDescription" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Quantity</label>
                                    <input type="number" class="form-control" id="editReorderQuantity" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Reorder Level *</label>
                                    <input type="number" class="form-control" name="reorder_level" id="editReorderLevel" min="0" required>
                                    <small class="text-muted">Alert when quantity reaches this level</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> When the consumable quantity reaches or falls below the reorder level, it will be highlighted as "Low Stock" in the list.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Update Reorder Level
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Release Consumable Modal -->
    <div class="modal fade" id="releaseConsumableModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-right"></i> Release Consumable</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <iframe id="releaseModalFrame" src="" style="width: 100%; height: 600px; border: none;"></iframe>
                </div>
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
        // Consumable data for display
        const consumableData = <?php echo json_encode($consumables); ?>;
        
        // Edit reorder level function
        function editReorderLevel(consumableId, description, quantity) {
            fetch('consumables.php?action=get&id=' + consumableId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const consumable = data.data;
                        document.getElementById('editReorderId').value = consumable.id;
                        document.getElementById('editReorderDescription').value = consumable.description;
                        document.getElementById('editReorderQuantity').value = consumable.quantity;
                        document.getElementById('editReorderLevel').value = consumable.reorder_level;
                        
                        const modal = new bootstrap.Modal(document.getElementById('editReorderModal'));
                        modal.show();
                    } else {
                        alert('Error loading consumable data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading consumable data');
                });
        }
        
        // Open release modal function
        function openReleaseModal(consumableId) {
            const modal = new bootstrap.Modal(document.getElementById('releaseConsumableModal'));
            document.getElementById('releaseModalFrame').src = 'release_consumable_modal.php?id=' + consumableId;
            modal.show();
        }
        
        // Close release modal function (called from iframe)
        function closeReleaseModal() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('releaseConsumableModal'));
            if (modal) {
                modal.hide();
            }
        }
        
        // Show release success message (called from iframe)
        function showReleaseSuccess(message) {
            // Create success alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
        
        // Initialize DataTable
        let consumablesTable;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Since we're using backend filtering and pagination, 
            // we don't need to initialize DataTables
            // The table will be rendered by PHP with proper filtering
            consumablesTable = null;
            
            // Office filter
            $('#officeFilter').on('change', function() {
                const officeValue = this.value;
                const currentUrl = new URL(window.location);
                if (officeValue) {
                    currentUrl.searchParams.set('office', officeValue);
                } else {
                    currentUrl.searchParams.delete('office');
                }
                // Preserve search parameter if exists
                const searchValue = currentUrl.searchParams.get('search');
                if (!searchValue) {
                    currentUrl.searchParams.delete('search');
                }
                window.location.href = currentUrl.toString();
            });
            
            // Search functionality with debounce
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                const searchValue = this.value.trim();
                
                searchTimeout = setTimeout(() => {
                    const currentUrl = new URL(window.location);
                    if (searchValue) {
                        currentUrl.searchParams.set('search', searchValue);
                    } else {
                        currentUrl.searchParams.delete('search');
                    }
                    // Preserve office parameter if exists
                    const officeValue = currentUrl.searchParams.get('office');
                    if (!officeValue) {
                        currentUrl.searchParams.delete('office');
                    }
                    window.location.href = currentUrl.toString();
                }, 500); // Wait 500ms after user stops typing
            });
        });
        
        // Export consumables function
        function exportConsumables() {
            // Get current table data from DOM
            const table = document.getElementById('consumablesTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let csv = 'Description,Quantity,Unit Cost,Total Value,Reorder Level,Office,For Office,Created\n';
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length === 9) { // Skip empty message row
                    const rowData = [
                        cells[0].textContent.replace(/\s+/g, ' ').trim(), // Description
                        cells[1].textContent.trim(), // Quantity
                        cells[2].textContent.trim(), // Unit Cost
                        cells[3].textContent.replace(/[^0-9.-]+/g, '').trim(), // Total Value
                        cells[4].textContent.trim(), // Reorder Level
                        cells[5].textContent.trim(), // Office
                        cells[6].textContent.trim(), // For Office
                        cells[7].textContent.trim()  // Created
                    ];
                    csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
                }
            }
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `consumables_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
