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

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log consumables page access
logSystemAction($_SESSION['user_id'], 'access', 'consumables', 'Admin accessed consumables page');

// Function to get singular form of unit name
function getSingularForm($unitName) {
    // Common plural to singular conversions
    $singularRules = [
        // Regular -s endings
        'pieces' => 'piece',
        'sets' => 'set',
        'units' => 'unit',
        'boxes' => 'box',
        'cartons' => 'carton',
        'packs' => 'pack',
        'packages' => 'package',
        'bags' => 'bag',
        'containers' => 'container',
        'bottles' => 'bottle',
        'reams' => 'ream',
        'pairs' => 'pair',
        'dozens' => 'dozen',
        'rolls' => 'roll',
        'sheets' => 'sheet',
        'feet' => 'foot',
        'inches' => 'inch',
        'meters' => 'meter',
        'centimeters' => 'centimeter',
        'kilometers' => 'kilometer',
        'liters' => 'liter',
        'milliliters' => 'milliliter',
        'kilograms' => 'kilogram',
        'grams' => 'gram',
        'tons' => 'ton',
        'hours' => 'hour',
        'days' => 'day',
        'months' => 'month',
        'years' => 'year',
        'hectares' => 'hectare',
        // Special cases
        'pcs' => 'pc',
        'kgs' => 'kg',
        'gs' => 'g',
        'ms' => 'm',
        'cms' => 'cm',
        'kms' => 'km',
        'mls' => 'ml',
        'm3s' => 'm3',
        'm2s' => 'm2',
        'has' => 'ha',
        'hrs' => 'hr',
        'mos' => 'mo',
        'yrs' => 'yr',
        'fts' => 'ft',
        'ins' => 'in',
        // Additional consumable-specific units
        'gallons' => 'gallon',
        'cans' => 'can',
        'tubes' => 'tube'
    ];
    
    $lowerUnitName = strtolower($unitName);
    return $singularRules[$lowerUnitName] ?? $unitName; // Return original if no rule found
}

// Get units from database
$units = [];
try {
    $result = $conn->query("SELECT unit_name, unit_code FROM units WHERE status = 'active' ORDER BY unit_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching units: " . $e->getMessage());
    // Fallback to common units if database fails
    $units = [];
    $common_units_fallback = [
        'pc', 'pcs', 'piece', 'pieces', 'set', 'sets', 'unit', 'units',
        'box', 'boxes', 'carton', 'cartons', 'pack', 'packs', 'package', 'packages',
        'liter', 'liters', 'kilogram', 'kilograms', 'meter', 'meters',
        'square_meter', 'square_meters', 'cubic_meter', 'cubic_meters',
        'pair', 'pairs', 'dozen', 'dozens', 'roll', 'rolls',
        'bottle', 'bottles', 'bag', 'bags', 'container', 'containers', 'ream', 'reams',
        'gallon', 'gallons', 'can', 'cans', 'tube', ' tubes'
    ];
    foreach ($common_units_fallback as $unit) {
        $units[] = ['unit_name' => ucfirst($unit), 'unit_code' => $unit];
    }
}

// Handle GET parameters for modal success messages
$message = '';
$message_type = '';
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['type'] === 'success' ? 'success' : 'danger';
}

// Handle CRUD operations

// CREATE - Add new consumable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $description = trim($_POST['description'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $units = trim($_POST['units'] ?? '');
    $unit_cost = floatval($_POST['unit_cost'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 10);
    $for_office_id = intval($_POST['office_id'] ?? 0); // This will be for_office_id
    $office_id = 3; // Always use Supply Office (ID = 3) for storage
    
    // Check if consumable with same description already exists in the same office
    $existing_consumable = null;
    if ($for_office_id > 0) {
        // Check for existing allocated record in Supply Office
        $sql = "SELECT id, quantity, unit_cost FROM consumables WHERE description = ? AND office_id = 3 AND for_office_id = ?";
        $check_stmt = $conn->prepare($sql);
        $check_stmt->bind_param("si", $description, $for_office_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $existing_consumable = $check_result->fetch_assoc();
        }
        $check_stmt->close();
    } else {
        // Check for existing regular record in target office
        $sql = "SELECT id, quantity, unit_cost FROM consumables WHERE description = ? AND office_id = ? AND for_office_id IS NULL";
        $check_stmt = $conn->prepare($sql);
        $check_stmt->bind_param("si", $description, $office_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $existing_consumable = $check_result->fetch_assoc();
        }
        $check_stmt->close();
    }
    
    // Validation
    if (empty($description)) {
        $message = "Consumable description is required.";
        $message_type = "danger";
    } elseif (empty($units)) {
        $message = "Units is required.";
        $message_type = "danger";
    } elseif ($for_office_id <= 0) {
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
                // Update existing consumable quantity and calculate WAC
                $current_quantity = $existing_consumable['quantity'];
                $current_unit_cost = $existing_consumable['unit_cost'];
                $added_quantity = $quantity;
                $added_unit_price = $unit_cost;
                
                // Calculate Weighted Average Cost (WAC)
                if ($current_quantity + $added_quantity > 0) {
                    if ($current_quantity == 0) {
                        // If no current stock, use the added price
                        $new_unit_cost = $added_unit_price;
                    } else {
                        // Calculate WAC: ((current_qty * current_cost) + (added_qty * added_price)) / total_qty
                        $new_unit_cost = (($current_quantity * $current_unit_cost) + ($added_quantity * $added_unit_price)) / ($current_quantity + $added_quantity);
                    }
                    // Format to 2 decimal places
                    $new_unit_cost = round($new_unit_cost, 2);
                } else {
                    $new_unit_cost = $added_unit_price;
                }
                
                $new_quantity = $current_quantity + $added_quantity;
                
                if ($for_office_id > 0) {
                    // Update allocated record in Supply Office - use prepared statement
                    $update_stmt = $conn->prepare("UPDATE consumables SET quantity = ?, unit_cost = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("idi", $new_quantity, $new_unit_cost, $existing_consumable['id']);
                } else {
                    // Update regular record in target office - use prepared statement
                    $update_stmt = $conn->prepare("UPDATE consumables SET quantity = ?, unit_cost = ?, for_office_id = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("idii", $new_quantity, $new_unit_cost, $for_office_id, $existing_consumable['id']);
                }
                
                if ($update_stmt->execute()) {
                    // Insert into consumable_add_history
                    $history_stmt = $conn->prepare("INSERT INTO consumable_add_history 
                        (consumable_id, description, supplier, quantity_added, units, unit_cost, total_value, office_id, to_office_id, added_by, add_date, source, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                    $total_value = $added_quantity * $added_unit_price;
                    $notes = "Stock added to existing consumable. New WAC: ₱" . number_format($new_unit_cost, 2);
                    $source = 'stock_addition';
                    $history_stmt->bind_param("issisddiiiss", 
                        $existing_consumable['id'], $description, $supplier, $added_quantity, $units, 
                        $added_unit_price, $total_value, $office_id, $existing_consumable['for_office_id'], $_SESSION['user_id'], $source, $notes);
                    $history_stmt->execute();
                    $history_stmt->close();
                    
                    $message = "Consumable stock updated successfully! Added {$quantity} more items to existing consumable. New WAC: ₱" . number_format($new_unit_cost, 2);
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'consumable_stock_added', 'consumable_management', "Added {$quantity} units to consumable: {$description}. New WAC: ₱{$new_unit_cost}");
                } else {
                    throw new Exception("Failed to update consumable: " . $update_stmt->error);
                }
                $update_stmt->close();
            } else {
                // Insert new consumable
                $insert_stmt = $conn->prepare("INSERT INTO consumables (description, supplier, quantity, units, unit_cost, reorder_level, office_id, for_office_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("ssisdiii", $description, $supplier, $quantity, $units, $unit_cost, $reorder_level, $office_id, $for_office_id);
                
                if ($insert_stmt->execute()) {
                    $new_consumable_id = $conn->insert_id;
                    
                    // Insert into consumable_add_history for new consumable
                    $history_stmt = $conn->prepare("INSERT INTO consumable_add_history 
                        (consumable_id, description, supplier, quantity_added, units, unit_cost, total_value, office_id, to_office_id, added_by, add_date, source, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                    $total_value = $quantity * $unit_cost;
                    $notes = "New consumable added to inventory";
                    $source = 'new_consumable';
                    $history_stmt->bind_param("issisddiiiss", 
                        $new_consumable_id, $description, $supplier, $quantity, $units, 
                        $unit_cost, $total_value, $office_id, $for_office_id, $_SESSION['user_id'], $source, $notes);
                    $history_stmt->execute();
                    $history_stmt->close();
                    
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
    $supplier = trim($_POST['supplier'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $units = trim($_POST['units'] ?? '');
    $unit_cost = floatval($_POST['unit_cost'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 10);
    $for_office_id = intval($_POST['office_id'] ?? 0); // This will be for_office_id
    $office_id = 3; // Always use Supply Office (ID = 3) for storage
    
    // Validation
    if (empty($description)) {
        $message = "Consumable description is required.";
        $message_type = "danger";
    } elseif (empty($units)) {
        $message = "Units is required.";
        $message_type = "danger";
    } elseif ($for_office_id <= 0) {
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
            $update_stmt = $conn->prepare("UPDATE consumables SET description = ?, supplier = ?, quantity = ?, units = ?, unit_cost = ?, reorder_level = ?, for_office_id = ? WHERE id = ?");
            $update_stmt->bind_param("ssisdiii", $description, $supplier, $quantity, $units, $unit_cost, $reorder_level, $for_office_id, $consumable_id);
            
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
            $query = "SELECT id, description, quantity, units, reorder_level FROM consumables WHERE id = ?";
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

// AJAX handler to get statistics based on office filter
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_stats') {
    $office_id = intval($_GET['office_id'] ?? 0);
    
    try {
        $sql = "SELECT 
                    COUNT(*) as total_consumables,
                    SUM(quantity) as total_quantity,
                    SUM(quantity * unit_cost) as total_value,
                    COUNT(CASE WHEN quantity <= reorder_level THEN 1 END) as low_stock_count,
                    COUNT(DISTINCT office_id) as total_offices
                FROM consumables
                WHERE quantity > 0";
        
        $params = [];
        $types = '';
        
        // Apply office filter to statistics
        if ($office_id > 0) {
            $sql .= " AND office_id = ?";
            $params[] = $office_id;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No statistics found']);
        }
        $stmt->close();
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle filter parameters
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 3; // Default to Supply Office (ID = 3)
$for_office_filter = isset($_GET['for_office']) ? intval($_GET['for_office']) : 0;

// Get consumables with office information
$consumables = [];
try {
    $sql = "SELECT c.*, o.office_name, fo.office_name as for_office_name
            FROM consumables c 
            LEFT JOIN offices o ON c.office_id = o.id 
            LEFT JOIN offices fo ON c.for_office_id = fo.id 
            WHERE c.quantity > 0";
    
    $params = [];
    $types = '';
    
    if ($office_filter > 0) {
        $sql .= " AND c.office_id = ?";
        $params[] = $office_filter;
        $types .= 'i';
    }
    
    if ($for_office_filter > 0) {
        $sql .= " AND c.for_office_id = ?";
        $params[] = $for_office_filter;
        $types .= 'i';
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
$descriptions = [];
try {
    $result = $conn->query("SELECT DISTINCT description FROM consumables ORDER BY description");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $descriptions[] = $row['description'];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching descriptions: " . $e->getMessage());
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
            FROM consumables
            WHERE quantity > 0";
    
    $params = [];
    $types = '';
    
    // Apply office filter to statistics
    if ($office_filter > 0) {
        $sql .= " AND office_id = ?";
        $params[] = $office_filter;
        $types .= 'i';
    }
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats = $result->fetch_assoc();
    }
    $stmt->close();
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
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
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
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
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
                            <?php if (isset($_SESSION['import_errors'])): ?>
                                <ul class="mb-0 mt-1 small">
                                    <?php foreach ($_SESSION['import_errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                    <?php unset($_SESSION['import_errors']); ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addConsumableModal">
                                    <i class="bi bi-plus-circle"></i> Add Consumable
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importConsumablesModal">
                                    <i class="bi bi-upload"></i> Import Consumables
                                </button>
                            </li>
                            <li>
                                <a class="dropdown-item" href="bulk_release_form.php">
                                    <i class="bi bi-box-seam"></i> Bulk Release
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="lend_consumables.php">
                                    <i class="bi bi-arrow-left-right"></i> Borrowing
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="release_history.php">
                                    <i class="bi bi-clock-history"></i> History
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportConsumables()">
                                    <i class="bi bi-download"></i> Export Consumables
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Consumables Table -->
        <div class="table-container">
            <div class="row mb-3 align-items-center">
                <div class="col-12">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Consumables List</h5>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="consumablesTable">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Units</th>
                            <th>Unit Cost</th>
                            <th>Total Value</th>
                            <th>Office</th>
                            <th>For Office</th>
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
                                    <td><?php echo htmlspecialchars($consumable['units'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($consumable['unit_cost'], 2); ?></td>
                                    <td class="text-value"><?php echo number_format($consumable['quantity'] * $consumable['unit_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($consumable['office_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($consumable['for_office_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-info" onclick="viewHistory(<?php echo $consumable['id']; ?>, '<?php echo htmlspecialchars($consumable['description']); ?>')" title="View History">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                            <?php if (empty($consumable['for_office_name'])): ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php elseif ($consumable['for_office_id'] == 3): ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editReorderLevel(<?php echo $consumable['id']; ?>, '<?php echo htmlspecialchars($consumable['description']); ?>', <?php echo $consumable['quantity']; ?>)" title="Edit Reorder Level">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick="openLendModal(<?php echo $consumable['id']; ?>)" title="Lend Consumable">
                                                    <i class="bi bi-arrow-up-right"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editReorderLevel(<?php echo $consumable['id']; ?>, '<?php echo htmlspecialchars($consumable['description']); ?>', <?php echo $consumable['quantity']; ?>)" title="Edit Reorder Level">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="openReleaseModal(<?php echo $consumable['id']; ?>)" title="Release Consumable">
                                                    <i class="bi bi-box-arrow-right"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
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
    <?php require_once 'includes/footer.php'; ?>
    
    <!-- Import Consumables Modal -->
    <div class="modal fade" id="importConsumablesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import Consumables</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="import_consumables.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i> CSV file should have headers like: <strong>Description, Quantity, Units, Unit Cost, Reorder Level, Office</strong>.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Choose CSV File</label>
                            <input type="file" class="form-control" name="import_file" accept=".csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
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
                            <input type="text" class="form-control" name="description" list="descriptionList" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" name="supplier" placeholder="Enter supplier name">
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
                                    <label class="form-label">Units *</label>
                                    <select class="form-select" name="units" id="consumableUnitSelect" required>
                                        <option value="">Select Unit</option>
                                        <?php foreach ($units as $unit): ?>
                                            <option value="<?php echo htmlspecialchars($unit['unit_code']); ?>" data-unit-name="<?php echo htmlspecialchars($unit['unit_name']); ?>" data-singular="<?php echo htmlspecialchars(getSingularForm($unit['unit_name'])); ?>"><?php echo htmlspecialchars($unit['unit_name']); ?></option>
                                        <?php endforeach; ?>
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
                                    <label class="form-label">For Office *</label>
                                    <select class="form-select" name="office_id" required>
                                        <option value="">Select Office</option>
                                        <?php foreach ($offices as $office): ?>
                                            <option value="<?php echo $office['id']; ?>">
                                                <?php echo htmlspecialchars($office['office_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Items will be stored in Supply Office and assigned to this office</small>
                                </div>
                            </div>
                        </div>
                        
                        <datalist id="descriptionList">
                            <?php foreach ($descriptions as $desc): ?>
                                <option value="<?php echo htmlspecialchars($desc); ?>">
                            <?php endforeach; ?>
                        </datalist>
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
    
    <!-- Lend Consumable Modal -->
    <div class="modal fade" id="lendConsumableModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-up-right"></i> Lend Consumable</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <iframe id="lendModalFrame" src="" style="width: 100%; height: 600px; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View History Modal -->
    <div class="modal fade" id="viewHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clock-history"></i> Consumable History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <iframe id="viewHistoryFrame" src="" style="width: 100%; height: 600px; border: none;"></iframe>
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
        
        // Open lend modal function
        function openLendModal(consumableId) {
            const modal = new bootstrap.Modal(document.getElementById('lendConsumableModal'));
            document.getElementById('lendModalFrame').src = 'lend_consumable_modal.php?id=' + consumableId;
            modal.show();
        }
        
        // View history modal function
        function viewHistory(consumableId, description) {
            const modal = new bootstrap.Modal(document.getElementById('viewHistoryModal'));
            document.getElementById('viewHistoryFrame').src = 'view_consumable_history.php?id=' + consumableId;
            modal.show();
        }
        
        // Close release modal function (called from iframe)
        function closeReleaseModal() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('releaseConsumableModal'));
            if (modal) {
                modal.hide();
            }
        }
        
        // Function to update unit display based on quantity
        function updateUnitDisplay() {
            const quantity = parseInt(document.querySelector('input[name="quantity"]').value) || 0;
            const unitSelect = document.getElementById('consumableUnitSelect');
            
            if (!unitSelect) return;
            
            // Remove any existing temporary options
            const tempOptions = unitSelect.querySelectorAll('option[data-temp-singular]');
            tempOptions.forEach(opt => opt.remove());
            
            // Show all original options
            const allOptions = unitSelect.querySelectorAll('option');
            allOptions.forEach(opt => {
                if (opt.style.display === 'none') {
                    opt.style.display = '';
                }
            });
            
            if (quantity === 1) {
                const selectedOption = unitSelect.options[unitSelect.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const singularName = selectedOption.getAttribute('data-singular');
                    const originalName = selectedOption.getAttribute('data-unit-name');
                    
                    if (singularName && singularName !== originalName) {
                        // Hide the original option
                        selectedOption.style.display = 'none';
                        
                        // Create and add singular option
                        const singularOption = document.createElement('option');
                        singularOption.value = selectedOption.value;
                        singularOption.textContent = singularName;
                        singularOption.setAttribute('data-temp-singular', 'true');
                        singularOption.selected = true;
                        
                        unitSelect.add(singularOption);
                        
                        console.log('Changed to singular:', originalName, '->', singularName);
                    }
                }
            }
        }
        
        // Add event listeners for unit display updates
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.querySelector('input[name="quantity"]');
            const unitSelect = document.getElementById('consumableUnitSelect');
            
            if (quantityInput) {
                quantityInput.addEventListener('input', updateUnitDisplay);
                quantityInput.addEventListener('change', updateUnitDisplay);
                
                // Set initial unit display if quantity has a value
                updateUnitDisplay();
            }
            if (unitSelect) {
                unitSelect.addEventListener('change', updateUnitDisplay);
            }
        });
        
        // Close lend modal function (called from iframe)
        function closeLendModal() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('lendConsumableModal'));
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
        
        // Update statistics cards dynamically
        function updateStatistics(officeId) {
            fetch(`consumables.php?action=get_stats&office_id=${officeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.data;
                        // Update statistics cards with animation
                        updateStatCard('.stats-number', 0, stats.total_quantity || 0);
                        updateStatCard('.stats-number', 1, stats.total_consumables || 0);
                        updateStatCard('.stats-number', 2, parseFloat(stats.total_value || 0).toFixed(2));
                        updateStatCard('.stats-number', 3, stats.low_stock_count || 0);
                    } else {
                        console.error('Error updating statistics:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Helper function to animate stat card updates
        function updateStatCard(selector, index, newValue) {
            const elements = document.querySelectorAll(selector);
            if (elements[index]) {
                const element = elements[index];
                const oldValue = element.textContent;
                
                // Add animation class
                element.style.transition = 'all 0.3s ease';
                element.style.transform = 'scale(1.1)';
                element.style.color = '#1E56A0';
                
                // Update value
                element.textContent = newValue;
                
                // Reset animation after delay
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                    element.style.color = '';
                }, 300);
            }
        }
        
        // Initialize DataTable
        let consumablesTable;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Check if table has data rows before initializing DataTables to avoid column count errors
            const tableBody = $('#consumablesTable tbody');
            const hasData = tableBody.find('tr').length > 0 && !tableBody.find('td[colspan]').length;
            
            console.log('Consumables table has data:', hasData);
            
            if (hasData) {
                // Initialize DataTable
                consumablesTable = $('#consumablesTable').DataTable({
                    "pageLength": 25,
                    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    "ordering": true,
                    "info": true,
                    "responsive": true,
                    "dom": "<'row mb-3 align-items-center'<'col-sm-12 col-md-3'l><'col-sm-12 col-md-3 office-filter-box'><'col-sm-12 col-md-3 for-office-filter-box'><'col-sm-12 col-md-3 text-end'f>>" +
                           "<'row'<'col-sm-12'tr>>" +
                           "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    "columnDefs": [
                        { "targets": 7, "orderable": false, "searchable": false } // Actions column
                    ],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "_MENU_",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next",
                            "previous": "Previous"
                        },
                        "emptyTable": "No consumables available",
                        "zeroRecords": "No matching consumables found"
                    },
                    "initComplete": function() {
                        // Inject Office Filter
                        $('.office-filter-box').html(`
                            <select class="form-select form-select-sm" id="officeFilter">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        `);
                        
                        // Inject For Office Filter
                        $('.for-office-filter-box').html(`
                            <select class="form-select form-select-sm" id="forOfficeFilter">
                                <option value="">All For Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $for_office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        `);
                        
                        // Bind events for the newly injected filters
                        $('#officeFilter').on('change', function() {
                            const officeValue = this.value;
                            const currentUrl = new URL(window.location);
                            if (officeValue) {
                                currentUrl.searchParams.set('office', officeValue);
                            } else {
                                currentUrl.searchParams.delete('office');
                            }
                            window.location.href = currentUrl.toString();
                        });
                        
                        $('#forOfficeFilter').on('change', function() {
                            const forOfficeValue = this.value;
                            const currentUrl = new URL(window.location);
                            if (forOfficeValue) {
                                currentUrl.searchParams.set('for_office', forOfficeValue);
                            } else {
                                currentUrl.searchParams.delete('for_office');
                            }
                            window.location.href = currentUrl.toString();
                        });
                    }
                });
            } else {
                console.log('No data found - DataTables not initialized to prevent warnings');
                $('#consumablesTable').addClass('table-striped');
            }
        });
        
        // Export consumables function
        function exportConsumables() {
            // Get current table data from DOM
            const table = document.getElementById('consumablesTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let csv = 'Description,Quantity,Units,Unit Cost,Total Value,Office,For Office\n';
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length === 8) { // Skip empty message row
                    const rowData = [
                        cells[0].textContent.replace(/\s+/g, ' ').trim(), // Description
                        cells[1].textContent.trim(), // Quantity
                        cells[2].textContent.trim(), // Units
                        cells[3].textContent.trim(), // Unit Cost
                        cells[4].textContent.replace(/[^0-9.-]+/g, '').trim(), // Total Value
                        cells[5].textContent.trim(), // Office
                        cells[6].textContent.trim()  // For Office
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
