<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';
require_once '../includes/asset_specific_manager.php';

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

// Log assets page access
logSystemAction($_SESSION['user_id'], 'access', 'assets', 'Admin accessed assets page');

// Initialize asset specific manager
$assetManager = new AssetSpecificManager($conn);

// Handle CRUD operations
$message = '';
$message_type = '';

// CREATE - Add new asset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $asset_categories_id = intval($_POST['asset_categories_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $unit_cost = floatval($_POST['unit_cost'] ?? 0);
    $office_id = intval($_POST['office_id'] ?? 0);
    
    // Get category code for specific data handling
    $category_code = '';
    $category_stmt = $conn->prepare("SELECT category_code FROM asset_categories WHERE id = ?");
    $category_stmt->bind_param("i", $asset_categories_id);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();
    if ($category_row = $category_result->fetch_assoc()) {
        $category_code = $category_row['category_code'];
    }
    $category_stmt->close();
    
    // Check if asset with same description already exists
    $existing_asset = null;
    $check_stmt = $conn->prepare("SELECT id, quantity, unit_cost FROM assets WHERE description = ? AND asset_categories_id = ? AND office_id = ?");
    $check_stmt->bind_param("sii", $description, $asset_categories_id, $office_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $existing_asset = $check_result->fetch_assoc();
    }
    $check_stmt->close();
    
    // Validation
    if (empty($description)) {
        $message = "Asset description is required.";
        $message_type = "danger";
    } elseif ($asset_categories_id <= 0) {
        $message = "Please select a category.";
        $message_type = "danger";
    } elseif ($office_id <= 0) {
        $message = "Please select an office.";
        $message_type = "danger";
    } elseif (empty($unit)) {
        $message = "Unit is required.";
        $message_type = "danger";
    } elseif ($quantity <= 0) {
        $message = "Quantity must be greater than 0.";
        $message_type = "danger";
    } elseif ($unit_cost < 0) {
        $message = "Unit cost cannot be negative.";
        $message_type = "danger";
    } else {
        error_log("DEBUG: Form validation passed - Description: '$description', Unit: '$unit', Category: $asset_categories_id, Office: $office_id, Quantity: $quantity, Cost: $unit_cost");
        try {
            if ($existing_asset) {
                // Update existing asset quantity using traditional SQL
                $new_quantity = $existing_asset['quantity'] + $quantity;
                $unit = mysqli_real_escape_string($conn, $unit);
                $unit_cost = floatval($unit_cost);
                $existing_asset_id = intval($existing_asset['id']);
                
                $update_sql = "UPDATE assets SET quantity = '$new_quantity', unit_cost = '$unit_cost', unit = '$unit' WHERE id = '$existing_asset_id'";
                error_log("DEBUG: Update SQL: " . $update_sql);
                
                if ($conn->query($update_sql)) {
                    $asset_id = $existing_asset['id'];
                    error_log("DEBUG: Asset updated with ID: $asset_id");
                    
                    // Create additional asset items for new quantity
                    for ($i = 1; $i <= $quantity; $i++) {
                        $item_description = mysqli_real_escape_string($conn, $description . ' - ' . $unit . ' ' . ($existing_asset['quantity'] + $i));
                        $item_status = 'available';
                        $acquisition_date = date('Y-m-d');
                        
                        $item_sql = "INSERT INTO asset_items (asset_id, description, status, value, acquisition_date, office_id) 
                                     VALUES ('$asset_id', '$item_description', '$item_status', '$unit_cost', '$acquisition_date', '$office_id')";
                        error_log("DEBUG: Item SQL: " . $item_sql);
                        
                        if ($conn->query($item_sql)) {
                            error_log("DEBUG: Successfully created item $i with ID: " . $conn->insert_id);
                        } else {
                            error_log("DEBUG: Failed to create item $i - Error: " . $conn->error);
                        }
                    }
                    
                    $message = "Asset quantity updated successfully! Added {$quantity} more items to existing asset.";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'asset_quantity_updated', 'asset_management', "Updated quantity for existing asset: {$description}");
                } else {
                    throw new Exception("Failed to update asset: " . $conn->error);
                }
            } else {
                // Insert new asset using traditional SQL
                $asset_categories_id = intval($asset_categories_id);
                $description = mysqli_real_escape_string($conn, $description);
                $unit = mysqli_real_escape_string($conn, $unit);
                $quantity = intval($quantity);
                $unit_cost = floatval($unit_cost);
                $office_id = intval($office_id);
                
                $sql = "INSERT INTO assets (asset_categories_id, description, unit, quantity, unit_cost, office_id, status) 
                        VALUES ('$asset_categories_id', '$description', '$unit', '$quantity', '$unit_cost', '$office_id', 'active')";
                
                error_log("DEBUG: SQL Query: " . $sql);
                
                if ($conn->query($sql)) {
                    $asset_id = $conn->insert_id;
                    error_log("DEBUG: Asset inserted with ID: $asset_id");
                    
                    // Handle specific asset data
                    if (!empty($category_code)) {
                        $specific_data = [];
                        $fields = $assetManager->getCategoryFormFields($category_code);
                        
                        foreach ($fields as $field_name => $field_config) {
                            if ($field_config['type'] === 'checkbox') {
                                $specific_data[$field_name] = isset($_POST[$field_name]) ? 1 : 0;
                            } else {
                                $specific_data[$field_name] = $_POST[$field_name] ?? '';
                            }
                        }
                        
                        // Remove empty values to avoid database issues
                        $specific_data = array_filter($specific_data, function($value) {
                            return $value !== '' && $value !== null;
                        });
                        
                        if (!empty($specific_data)) {
                            $assetManager->saveSpecificAssetData($asset_id, $category_code, $specific_data, $_SESSION['user_id']);
                        }
                    }
                    
                    // Create individual asset items for each unit
                    for ($i = 1; $i <= $quantity; $i++) {
                        $item_description = mysqli_real_escape_string($conn, $description . ' - ' . $unit . ' ' . $i);
                        $item_status = 'available';
                        $acquisition_date = date('Y-m-d');
                        
                        $item_sql = "INSERT INTO asset_items (asset_id, description, status, value, acquisition_date, office_id) 
                                     VALUES ('$asset_id', '$item_description', '$item_status', '$unit_cost', '$acquisition_date', '$office_id')";
                        error_log("DEBUG: New Item SQL: " . $item_sql);
                        
                        if ($conn->query($item_sql)) {
                            error_log("DEBUG: Successfully created new item $i with ID: " . $conn->insert_id);
                        } else {
                            error_log("DEBUG: Failed to create new item $i - Error: " . $conn->error);
                        }
                    }
                    
                    $message = "Asset added successfully!";
                    $message_type = "success";
                    
                    logSystemAction($_SESSION['user_id'], 'asset_added', 'asset_management', "Added asset: {$description}");
                } else {
                    throw new Exception("Failed to insert asset: " . $conn->error);
                }
            }
            
        } catch (Exception $e) {
            $message = "Error adding asset: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}


// AJAX handler to get asset items
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_items') {
    $asset_id = intval($_GET['asset_id'] ?? 0);
    
    if ($asset_id > 0) {
        try {
            $items_query = "SELECT ai.id, ai.description, ai.status, ai.value, ai.acquisition_date, a.description as asset_description 
                         FROM asset_items ai 
                         LEFT JOIN assets a ON ai.asset_id = a.id 
                         WHERE ai.asset_id = ? 
                         ORDER BY ai.id";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $asset_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $items = [];
            while ($row = $items_result->fetch_assoc()) {
                $items[] = $row;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'items' => $items]);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid asset ID']);
        exit;
    }
}

// Handle filter parameters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get assets with category and office information
$assets = [];
try {
    $sql = "SELECT a.*, ac.category_name, ac.category_code, o.office_name
            FROM assets a 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN offices o ON a.office_id = o.id 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($category_filter > 0) {
        $sql .= " AND a.asset_categories_id = ?";
        $params[] = $category_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $sql .= " AND (a.description LIKE ? OR ac.category_name LIKE ? OR o.office_name LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Get specific asset data if category code exists
            if (!empty($row['category_code'])) {
                $specific_data = $assetManager->getSpecificAssetData($row['id'], $row['category_code']);
                if ($specific_data) {
                    $row = array_merge($row, $specific_data);
                }
            }
            $assets[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    $message = "Error fetching assets: " . $e->getMessage();
    $message_type = "danger";
}

// Get asset categories for dropdown
$categories = [];
try {
    $result = $conn->query("SELECT id, category_code, category_name FROM asset_categories ORDER BY category_code");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
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

// Get asset statistics
$stats = [];
try {
    $sql = "SELECT 
                COUNT(*) as total_assets,
                SUM(quantity) as total_quantity,
                SUM(quantity * unit_cost) as total_value,
                COUNT(DISTINCT asset_categories_id) as total_categories,
                COUNT(DISTINCT office_id) as total_offices
            FROM assets";
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
    <title>Asset Management - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
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
        
        .category-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .text-value {
            font-weight: 600;
            color: #191BA9;
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
    $page_title = 'Asset Management';
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
                        <i class="bi bi-box"></i> Asset Management
                    </h1>
                    <p class="text-muted mb-0">Manage and track organizational assets</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="bi bi-plus-circle"></i> Add Asset
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportAssets()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_assets'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-box"></i> Total Assets</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_quantity'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-stack"></i> Total Quantity</div>
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
                    <div class="stats-number"><?php echo $stats['total_categories'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-tags"></i> Categories</div>
                </div>
            </div>
        </div>
        
        <!-- Assets Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Assets List</h5>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" id="categoryFilter" onchange="applyFilters()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search assets..." value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="assetsTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Total Value</th>
                            <th>Office</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assets)): ?>
                            <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td>
                                        <span class="category-badge">
                                            <?php echo htmlspecialchars($asset['category_code'] ?? 'N/A'); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($asset['description']); ?></td>
                                    <td><?php echo $asset['quantity']; ?></td>
                                    <td class="text-value"><?php echo number_format($asset['quantity'] * $asset['unit_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($asset['office_name'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo date('M j, Y', strtotime($asset['created_at'])); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info btn-action" onclick="viewAsset(<?php echo $asset['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No assets found. Click "Add Asset" to create your first asset.</p>
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
    
    <!-- Add Asset Modal -->
    <div class="modal fade" id="addAssetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="asset_categories_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <input type="text" class="form-control" name="description" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" name="quantity" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Unit *</label>
                                    <select class="form-select" name="unit" required>
                                        <option value="">Select Unit</option>
                                        <option value="pcs">Pieces (pcs)</option>
                                        <option value="units">Units</option>
                                        <option value="sets">Sets</option>
                                        <option value="boxes">Boxes</option>
                                        <option value="packages">Packages</option>
                                        <option value="liters">Liters</option>
                                        <option value="kilograms">Kilograms (kg)</option>
                                        <option value="meters">Meters (m)</option>
                                        <option value="square_meters">Square Meters (m²)</option>
                                        <option value="cubic_meters">Cubic Meters (m³)</option>
                                        <option value="pairs">Pairs</option>
                                        <option value="dozens">Dozens</option>
                                        <option value="rolls">Rolls</option>
                                        <option value="bottles">Bottles</option>
                                        <option value="bags">Bags</option>
                                        <option value="containers">Containers</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Unit Cost *</label>
                                    <input type="number" class="form-control" name="unit_cost" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
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
                        
                        <div id="categorySpecificFields"></div>
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
    
    
    <!-- View Asset Modal -->
    <div class="modal fade" id="viewAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Asset Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewAssetContent">
                    <!-- Content will be dynamically loaded -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Asset data for editing
        const assetData = <?php echo json_encode($assets); ?>;
        const categoriesData = <?php echo json_encode($categories); ?>;
        
        // Category field definitions
        const categoryFields = <?php echo json_encode([
            'FF' => [
                'furniture_type' => ['type' => 'select', 'label' => 'Furniture Type', 'options' => ['desk', 'chair', 'cabinet', 'shelf', 'table', 'sofa', 'bed', 'other']],
                'material' => ['type' => 'select', 'label' => 'Material', 'options' => ['wood', 'metal', 'plastic', 'glass', 'leather', 'fabric', 'composite']],
                'color' => ['type' => 'text', 'label' => 'Color'],
                'dimensions' => ['type' => 'text', 'label' => 'Dimensions (LxWxH)'],
                'weight_capacity' => ['type' => 'number', 'label' => 'Weight Capacity (kg)'],
                'manufacturer' => ['type' => 'text', 'label' => 'Manufacturer'],
                'model_number' => ['type' => 'text', 'label' => 'Model Number'],
                'purchase_date' => ['type' => 'date', 'label' => 'Purchase Date'],
                'warranty_expiry' => ['type' => 'date', 'label' => 'Warranty Expiry'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'location_building' => ['type' => 'text', 'label' => 'Building'],
                'location_floor' => ['type' => 'text', 'label' => 'Floor'],
                'location_room' => ['type' => 'text', 'label' => 'Room'],
                'assembly_required' => ['type' => 'checkbox', 'label' => 'Assembly Required'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'CE' => [
                'processor' => ['type' => 'text', 'label' => 'Processor'],
                'ram_capacity' => ['type' => 'text', 'label' => 'RAM Capacity'],
                'storage_type' => ['type' => 'select', 'label' => 'Storage Type', 'options' => ['hdd', 'ssd', 'hybrid']],
                'storage_capacity' => ['type' => 'text', 'label' => 'Storage Capacity'],
                'graphics_card' => ['type' => 'text', 'label' => 'Graphics Card'],
                'operating_system' => ['type' => 'text', 'label' => 'Operating System'],
                'mac_address' => ['type' => 'text', 'label' => 'MAC Address'],
                'ip_address' => ['type' => 'text', 'label' => 'IP Address'],
                'serial_number' => ['type' => 'text', 'label' => 'Serial Number'],
                'warranty_provider' => ['type' => 'text', 'label' => 'Warranty Provider'],
                'warranty_expiry' => ['type' => 'date', 'label' => 'Warranty Expiry'],
                'purchase_date' => ['type' => 'date', 'label' => 'Purchase Date'],
                'last_service_date' => ['type' => 'date', 'label' => 'Last Service Date'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'assigned_to' => ['type' => 'text', 'label' => 'Assigned To'],
                'department' => ['type' => 'text', 'label' => 'Department'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'VH' => [
                'plate_number' => ['type' => 'text', 'label' => 'Plate Number', 'required' => true],
                'engine_number' => ['type' => 'text', 'label' => 'Engine Number'],
                'chassis_number' => ['type' => 'text', 'label' => 'Chassis Number'],
                'color' => ['type' => 'text', 'label' => 'Color'],
                'model' => ['type' => 'text', 'label' => 'Model'],
                'brand' => ['type' => 'text', 'label' => 'Brand'],
                'year_manufactured' => ['type' => 'number', 'label' => 'Year Manufactured'],
                'fuel_type' => ['type' => 'select', 'label' => 'Fuel Type', 'options' => ['gasoline', 'diesel', 'electric', 'hybrid', 'lpg']],
                'transmission_type' => ['type' => 'select', 'label' => 'Transmission', 'options' => ['manual', 'automatic', 'cvt']],
                'registration_date' => ['type' => 'date', 'label' => 'Registration Date'],
                'registration_expiry' => ['type' => 'date', 'label' => 'Registration Expiry'],
                'insurance_provider' => ['type' => 'text', 'label' => 'Insurance Provider'],
                'insurance_policy_number' => ['type' => 'text', 'label' => 'Policy Number'],
                'insurance_expiry' => ['type' => 'date', 'label' => 'Insurance Expiry'],
                'odometer_reading' => ['type' => 'number', 'label' => 'Odometer Reading'],
                'last_maintenance_date' => ['type' => 'date', 'label' => 'Last Maintenance'],
                'next_maintenance_date' => ['type' => 'date', 'label' => 'Next Maintenance'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'ME' => [
                'machine_type' => ['type' => 'text', 'label' => 'Machine Type'],
                'manufacturer' => ['type' => 'text', 'label' => 'Manufacturer'],
                'model_number' => ['type' => 'text', 'label' => 'Model Number'],
                'serial_number' => ['type' => 'text', 'label' => 'Serial Number'],
                'capacity' => ['type' => 'text', 'label' => 'Capacity'],
                'power_requirements' => ['type' => 'text', 'label' => 'Power Requirements'],
                'voltage' => ['type' => 'number', 'label' => 'Voltage'],
                'operating_weight' => ['type' => 'number', 'label' => 'Operating Weight (kg)'],
                'dimensions' => ['type' => 'text', 'label' => 'Dimensions'],
                'installation_date' => ['type' => 'date', 'label' => 'Installation Date'],
                'last_maintenance_date' => ['type' => 'date', 'label' => 'Last Maintenance'],
                'next_maintenance_date' => ['type' => 'date', 'label' => 'Next Maintenance'],
                'maintenance_interval_days' => ['type' => 'number', 'label' => 'Maintenance Interval (days)'],
                'operator_required' => ['type' => 'checkbox', 'label' => 'Operator Required'],
                'safety_certification' => ['type' => 'text', 'label' => 'Safety Certification'],
                'certification_expiry' => ['type' => 'date', 'label' => 'Certification Expiry'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'location_building' => ['type' => 'text', 'label' => 'Building'],
                'location_area' => ['type' => 'text', 'label' => 'Area'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'BI' => [
                'building_type' => ['type' => 'select', 'label' => 'Building Type', 'options' => ['office', 'warehouse', 'factory', 'residential', 'commercial', 'other']],
                'address' => ['type' => 'textarea', 'label' => 'Address', 'required' => true],
                'city' => ['type' => 'text', 'label' => 'City'],
                'state' => ['type' => 'text', 'label' => 'State'],
                'postal_code' => ['type' => 'text', 'label' => 'Postal Code'],
                'total_floor_area' => ['type' => 'number', 'label' => 'Total Floor Area (sqm)'],
                'number_of_floors' => ['type' => 'number', 'label' => 'Number of Floors'],
                'year_built' => ['type' => 'number', 'label' => 'Year Built'],
                'year_renovated' => ['type' => 'number', 'label' => 'Year Renovated'],
                'construction_type' => ['type' => 'select', 'label' => 'Construction Type', 'options' => ['concrete', 'wood', 'steel', 'mixed']],
                'roof_type' => ['type' => 'text', 'label' => 'Roof Type'],
                'electrical_capacity' => ['type' => 'text', 'label' => 'Electrical Capacity'],
                'water_supply' => ['type' => 'select', 'label' => 'Water Supply', 'options' => ['municipal', 'well', 'mixed']],
                'sewage_system' => ['type' => 'select', 'label' => 'Sewage System', 'options' => ['municipal', 'septic_tank', 'mixed']],
                'fire_safety_system' => ['type' => 'checkbox', 'label' => 'Fire Safety System'],
                'security_system' => ['type' => 'checkbox', 'label' => 'Security System'],
                'air_conditioning' => ['type' => 'checkbox', 'label' => 'Air Conditioning'],
                'elevator_count' => ['type' => 'number', 'label' => 'Elevator Count'],
                'parking_spaces' => ['type' => 'number', 'label' => 'Parking Spaces'],
                'property_tax_number' => ['type' => 'text', 'label' => 'Property Tax Number'],
                'land_title_number' => ['type' => 'text', 'label' => 'Land Title Number'],
                'zoning_classification' => ['type' => 'text', 'label' => 'Zoning Classification'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'LD' => [
                'land_type' => ['type' => 'select', 'label' => 'Land Type', 'options' => ['commercial', 'residential', 'agricultural', 'industrial', 'mixed']],
                'address' => ['type' => 'textarea', 'label' => 'Address', 'required' => true],
                'city' => ['type' => 'text', 'label' => 'City'],
                'state' => ['type' => 'text', 'label' => 'State'],
                'postal_code' => ['type' => 'text', 'label' => 'Postal Code'],
                'lot_area' => ['type' => 'number', 'label' => 'Lot Area (sqm)'],
                'frontage' => ['type' => 'number', 'label' => 'Frontage (meters)'],
                'depth' => ['type' => 'number', 'label' => 'Depth (meters)'],
                'shape' => ['type' => 'select', 'label' => 'Shape', 'options' => ['regular', 'irregular']],
                'topography' => ['type' => 'select', 'label' => 'Topography', 'options' => ['flat', 'sloping', 'hilly', 'mountainous']],
                'zoning_classification' => ['type' => 'text', 'label' => 'Zoning Classification'],
                'land_classification' => ['type' => 'text', 'label' => 'Land Classification'],
                'tax_declaration_number' => ['type' => 'text', 'label' => 'Tax Declaration Number'],
                'land_title_number' => ['type' => 'text', 'label' => 'Land Title Number'],
                'survey_number' => ['type' => 'text', 'label' => 'Survey Number'],
                'corner_lot' => ['type' => 'checkbox', 'label' => 'Corner Lot'],
                'road_access' => ['type' => 'select', 'label' => 'Road Access', 'options' => ['paved', 'gravel', 'dirt', 'none']],
                'utilities_available' => ['type' => 'select', 'label' => 'Utilities Available', 'options' => ['full', 'partial', 'none']],
                'flood_prone' => ['type' => 'checkbox', 'label' => 'Flood Prone'],
                'encumbrances' => ['type' => 'textarea', 'label' => 'Encumbrances'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'SW' => [
                'software_name' => ['type' => 'text', 'label' => 'Software Name', 'required' => true],
                'version' => ['type' => 'text', 'label' => 'Version'],
                'license_type' => ['type' => 'select', 'label' => 'License Type', 'options' => ['perpetual', 'subscription', 'open_source', 'freemium']],
                'license_key' => ['type' => 'text', 'label' => 'License Key'],
                'number_of_licenses' => ['type' => 'number', 'label' => 'Number of Licenses'],
                'platform' => ['type' => 'select', 'label' => 'Platform', 'options' => ['windows', 'mac', 'linux', 'web', 'mobile', 'multi_platform']],
                'installation_date' => ['type' => 'date', 'label' => 'Installation Date'],
                'license_expiry' => ['type' => 'date', 'label' => 'License Expiry'],
                'renewal_cost' => ['type' => 'number', 'label' => 'Renewal Cost'],
                'vendor' => ['type' => 'text', 'label' => 'Vendor'],
                'support_contact' => ['type' => 'text', 'label' => 'Support Contact'],
                'activation_method' => ['type' => 'select', 'label' => 'Activation Method', 'options' => ['key', 'online', 'usb_dongle', 'account']],
                'server_based' => ['type' => 'checkbox', 'label' => 'Server Based'],
                'concurrent_users' => ['type' => 'number', 'label' => 'Concurrent Users'],
                'hardware_requirements' => ['type' => 'textarea', 'label' => 'Hardware Requirements'],
                'installation_path' => ['type' => 'text', 'label' => 'Installation Path'],
                'assigned_department' => ['type' => 'text', 'label' => 'Assigned Department'],
                'condition_status' => ['type' => 'select', 'label' => 'Status', 'options' => ['active', 'inactive', 'deprecated']],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ],
            'OE' => [
                'equipment_type' => ['type' => 'select', 'label' => 'Equipment Type', 'options' => ['printer', 'scanner', 'photocopier', 'fax', 'telephone', 'projector', 'shredder', 'other']],
                'brand' => ['type' => 'text', 'label' => 'Brand'],
                'model' => ['type' => 'text', 'label' => 'Model'],
                'serial_number' => ['type' => 'text', 'label' => 'Serial Number'],
                'connectivity' => ['type' => 'select', 'label' => 'Connectivity', 'options' => ['usb', 'network', 'wireless', 'bluetooth', 'multi']],
                'network_ip' => ['type' => 'text', 'label' => 'Network IP'],
                'functions' => ['type' => 'text', 'label' => 'Functions'],
                'paper_size' => ['type' => 'text', 'label' => 'Paper Size'],
                'print_speed_ppm' => ['type' => 'number', 'label' => 'Print Speed (PPM)'],
                'scan_resolution' => ['type' => 'text', 'label' => 'Scan Resolution'],
                'color_capability' => ['type' => 'checkbox', 'label' => 'Color Capability'],
                'power_consumption' => ['type' => 'text', 'label' => 'Power Consumption'],
                'warranty_provider' => ['type' => 'text', 'label' => 'Warranty Provider'],
                'warranty_expiry' => ['type' => 'date', 'label' => 'Warranty Expiry'],
                'last_service_date' => ['type' => 'date', 'label' => 'Last Service Date'],
                'next_service_date' => ['type' => 'date', 'label' => 'Next Service Date'],
                'condition_status' => ['type' => 'select', 'label' => 'Condition', 'options' => ['excellent', 'good', 'fair', 'poor']],
                'location_building' => ['type' => 'text', 'label' => 'Building'],
                'location_floor' => ['type' => 'text', 'label' => 'Floor'],
                'location_room' => ['type' => 'text', 'label' => 'Room'],
                'assigned_to' => ['type' => 'text', 'label' => 'Assigned To'],
                'notes' => ['type' => 'textarea', 'label' => 'Notes']
            ]
        ]); ?>;
        
        // Generate category-specific fields
        function generateCategoryFields(categoryCode, containerId, assetData = null) {
            const container = document.getElementById(containerId);
            const fields = categoryFields[categoryCode];
            
            if (!fields) {
                container.innerHTML = '';
                return;
            }
            
            let html = '<div class="category-specific-fields mt-3">';
            html += '<h6 class="mb-3"><i class="bi bi-gear"></i> Specific Details</h6>';
            
            for (const [fieldName, fieldConfig] of Object.entries(fields)) {
                const value = assetData ? (assetData[fieldName] || '') : '';
                const required = fieldConfig.required || false;
                const requiredAttr = required ? 'required' : '';
                
                html += '<div class="mb-3">';
                html += '<label class="form-label">' + fieldConfig.label;
                if (required) {
                    html += ' <span class="text-danger">*</span>';
                }
                html += '</label>';
                
                switch (fieldConfig.type) {
                    case 'select':
                        html += '<select class="form-select" name="' + fieldName + '" ' + requiredAttr + '>';
                        html += '<option value="">Select ' + fieldConfig.label + '</option>';
                        fieldConfig.options.forEach(option => {
                            const selected = value == option ? 'selected' : '';
                            const optionLabel = option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            html += '<option value="' + option + '" ' + selected + '>' + optionLabel + '</option>';
                        });
                        html += '</select>';
                        break;
                        
                    case 'textarea':
                        html += '<textarea class="form-control" name="' + fieldName + '" rows="3" ' + requiredAttr + '>' + value + '</textarea>';
                        break;
                        
                    case 'checkbox':
                        const checked = value ? 'checked' : '';
                        html += '<div class="form-check">';
                        html += '<input class="form-check-input" type="checkbox" name="' + fieldName + '" value="1" ' + checked + '>';
                        html += '<label class="form-check-label">' + fieldConfig.label + '</label>';
                        html += '</div>';
                        break;
                        
                    case 'number':
                        html += '<input type="number" class="form-control" name="' + fieldName + '" value="' + value + '" ' + requiredAttr + '>';
                        break;
                        
                    case 'date':
                        html += '<input type="date" class="form-control" name="' + fieldName + '" value="' + value + '" ' + requiredAttr + '>';
                        break;
                        
                    default:
                        html += '<input type="text" class="form-control" name="' + fieldName + '" value="' + value + '" ' + requiredAttr + '>';
                        break;
                }
                
                html += '</div>';
            }
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        // Get category code from category ID
        function getCategoryCode(categoryId) {
            const category = categoriesData.find(c => c.id == categoryId);
            return category ? category.category_code : null;
        }
        
        
        // Load asset items function
        function loadAssetItems(assetId) {
            fetch('assets.php?action=get_items&asset_id=' + assetId)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('assetItemsBody_' + assetId);
                    if (data.items && data.items.length > 0) {
                        let html = '';
                        data.items.forEach(item => {
                            const statusBadge = getStatusBadge(item.status);
                            html += '<tr>';
                            html += '<td>' + item.description + '</td>';
                            html += '<td>' + statusBadge + '</td>';
                            html += '<td>₱' + parseFloat(item.value).toFixed(2) + '</td>';
                            html += '<td>' + new Date(item.acquisition_date).toLocaleDateString() + '</td>';
                            html += '</tr>';
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No individual items found for this asset.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error loading asset items:', error);
                    const tbody = document.getElementById('assetItemsBody_' + assetId);
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading items.</td></tr>';
                });
        }
        
        // Get status badge HTML
        function getStatusBadge(status) {
            const badges = {
                'available': '<span class="badge bg-success">Available</span>',
                'in_use': '<span class="badge bg-primary">In Use</span>',
                'maintenance': '<span class="badge bg-warning">Maintenance</span>',
                'disposed': '<span class="badge bg-danger">Disposed</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }
        
        // View asset function
        function viewAsset(id) {
            const asset = assetData.find(a => a.id == id);
            if (asset) {
                let html = '<div class="row">';
                
                // Basic Information - First Div
                html += '<div class="col-md-6">';
                html += '<h6 class="text-primary mb-3"><i class="bi bi-info-circle"></i> Basic Information</h6>';
                html += '<table class="table table-sm table-borderless">';
                html += '<tr><td><strong>Category:</strong></td><td>' + (asset.category_code || 'N/A') + ' - ' + (asset.category_name || 'N/A') + '</td></tr>';
                html += '<tr><td><strong>Description:</strong></td><td>' + asset.description + '</td></tr>';
                html += '<tr><td><strong>Quantity:</strong></td><td>' + asset.quantity + '</td></tr>';
                html += '<tr><td><strong>Total Value:</strong></td><td>₱' + (asset.quantity * asset.unit_cost).toFixed(2) + '</td></tr>';
                html += '</table>';
                html += '</div>';
                
                // Additional Information - Second Div
                html += '<div class="col-md-6">';
                html += '<h6 class="text-primary mb-3"><i class="bi bi-building"></i> Additional Information</h6>';
                html += '<table class="table table-sm table-borderless">';
                html += '<tr><td><strong>Office:</strong></td><td>' + (asset.office_name || 'N/A') + '</td></tr>';
                html += '<tr><td><strong>Created:</strong></td><td>' + new Date(asset.created_at).toLocaleDateString() + '</td></tr>';
                html += '</table>';
                html += '</div>';
                
                // Asset Items Information
                html += '<div class="col-md-12">';
                html += '<h6 class="text-primary mb-3"><i class="bi bi-list-ul"></i> Individual Asset Items</h6>';
                html += '<div class="table-responsive">';
                html += '<table class="table table-sm table-bordered">';
                html += '<thead><tr>';
                html += '<th>Description</th>';
                html += '<th>Status</th>';
                html += '<th>Value</th>';
                html += '<th>Acquisition Date</th>';
                html += '</tr></thead>';
                html += '<tbody id="assetItemsBody_' + id + '">';
                html += '<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading items...</td></tr>';
                html += '</tbody>';
                html += '</table>';
                html += '</div>';
                html += '</div>';
                
                // Category-Specific Information
                const categoryCode = getCategoryCode(asset.asset_categories_id);
                const fields = categoryFields[categoryCode];
                
                if (fields) {
                    html += '<div class="col-md-6">';
                    html += '<h6 class="text-primary mb-3"><i class="bi bi-gear"></i> Specific Details</h6>';
                    html += '<table class="table table-sm table-borderless">';
                    
                    for (const [fieldName, fieldConfig] of Object.entries(fields)) {
                        const value = asset[fieldName];
                        if (value && value !== '' && value !== '0') {
                            let displayValue = value;
                            
                            // Format special fields
                            if (fieldConfig.type === 'checkbox') {
                                displayValue = value ? 'Yes' : 'No';
                            } else if (fieldConfig.type === 'date') {
                                displayValue = new Date(value).toLocaleDateString();
                            } else if (fieldConfig.type === 'select') {
                                displayValue = value.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            }
                            
                            html += '<tr><td><strong>' + fieldConfig.label + ':</strong></td><td>' + displayValue + '</td></tr>';
                        }
                    }
                    
                    html += '</table>';
                    html += '</div>';
                }
                
                html += '</div>';
                
                // Notes section if exists
                if (asset.notes && asset.notes !== '') {
                    html += '<div class="mt-3">';
                    html += '<h6 class="text-primary mb-2"><i class="bi bi-sticky-note"></i> Notes</h6>';
                    html += '<div class="alert alert-secondary">' + asset.notes + '</div>';
                    html += '</div>';
                }
                
                document.getElementById('viewAssetContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('viewAssetModal')).show();
                
                // Load asset items after modal is shown
                setTimeout(() => {
                    loadAssetItems(id);
                }, 500);
            }
        }
        
        // Export assets function
        function exportAssets() {
            const table = document.getElementById('assetsTable');
            let csv = 'ID,Category,Description,Quantity,Unit Cost,Total Value,Office,Created\n';
            
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                if (cells.length > 1) {
                    const rowData = [
                        cells[0].textContent,
                        cells[1].textContent.replace(/\n/g, ' '),
                        cells[2].textContent,
                        cells[3].textContent,
                        cells[4].textContent,
                        cells[5].textContent,
                        cells[6].textContent,
                        cells[7].textContent
                    ];
                    csv += rowData.map(cell => `"${cell.trim()}"`).join(',') + '\n';
                }
            }
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `assets_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Apply filters function
        function applyFilters() {
            const category = document.getElementById('categoryFilter').value;
            const search = document.getElementById('searchInput').value;
            
            const params = new URLSearchParams();
            if (category) params.append('category', category);
            if (search) params.append('search', search);
            
            const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = url;
        }
        
        // Search functionality with Enter key and debounce
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            clearTimeout(searchTimeout);
            if (e.key === 'Enter') {
                applyFilters();
            } else {
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 500);
            }
        });
        
        // Category filter change
        document.getElementById('categoryFilter').addEventListener('change', function() {
            applyFilters();
        });
        
        // Add event listeners for category selection in modals
        document.addEventListener('DOMContentLoaded', function() {
            // Add asset modal category change
            const addCategorySelect = document.querySelector('select[name="asset_categories_id"]');
            if (addCategorySelect) {
                addCategorySelect.addEventListener('change', function() {
                    const categoryCode = getCategoryCode(this.value);
                    generateCategoryFields(categoryCode, 'categorySpecificFields');
                });
            }
            
        });
    </script>
</body>
</html>
