<?php
ob_start();
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
        'ins' => 'in'
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
        'bottle', 'bottles', 'bag', 'bags', 'container', 'containers', 'ream', 'reams'
    ];
    foreach ($common_units_fallback as $unit) {
        $units[] = ['unit_name' => ucfirst($unit), 'unit_code' => $unit];
    }
}

// Handle CRUD operations
$message = '';
$message_type = '';

// Check for success parameter in URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Asset operation completed successfully!";
    $message_type = "success";
}

// CREATE - Add new asset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    // Debug: Log that we received the POST request
    error_log("DEBUG: POST request received for add asset action");
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    
    $asset_categories_id = intval($_POST['asset_categories_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $unit_cost = floatval($_POST['unit_cost'] ?? 0);
    $office_id = intval($_POST['office_id'] ?? 0);
    
    // Handle subcategory - convert code to ID if needed
    $asset_subcategory_code = trim($_POST['asset_subcategory_id'] ?? '');
    $asset_subcategory_id = null;
    
    // Debug: Log the raw POST value for subcategory
    error_log("DEBUG: Raw POST asset_subcategory_id value: '" . $_POST['asset_subcategory_id'] . "'");
    error_log("DEBUG: Trimmed asset_subcategory_code: '$asset_subcategory_code'");
    error_log("DEBUG: Is numeric check: " . (is_numeric($asset_subcategory_code) ? 'true' : 'false'));
    
    if (!empty($asset_subcategory_code)) {
        // Check if it's already an ID (numeric) or a code
        if (is_numeric($asset_subcategory_code)) {
            $asset_subcategory_id = intval($asset_subcategory_code);
            error_log("DEBUG: Using numeric ID: $asset_subcategory_id");
        } else {
            // It's a code, convert to ID
            error_log("DEBUG: Treating as code, looking up ID for: '$asset_subcategory_code'");
            $subcat_stmt = $conn->prepare("SELECT id FROM asset_sub_categories WHERE sub_category_code = ? AND status = 'active'");
            $subcat_stmt->bind_param("s", $asset_subcategory_code);
            $subcat_stmt->execute();
            $subcat_result = $subcat_stmt->get_result();
            if ($subcat_row = $subcat_result->fetch_assoc()) {
                $asset_subcategory_id = $subcat_row['id'];
                error_log("DEBUG: Found ID from code: $asset_subcategory_id");
            } else {
                error_log("DEBUG: No ID found for code: '$asset_subcategory_code'");
            }
            $subcat_stmt->close();
        }
    } else {
        error_log("DEBUG: asset_subcategory_code is empty");
    }
    $property_numbers = trim($_POST['property_numbers'] ?? '');
    
    // Debug: Log the extracted values
    error_log("DEBUG: Extracted values - Category: $asset_categories_id, Description: '$description', Quantity: $quantity, Unit: '$unit', Cost: $unit_cost, Office: $office_id, Subcategory Code: '$asset_subcategory_code', Subcategory ID: $asset_subcategory_id");
    
    // Convert date from mm/dd/yyyy to Y-m-d format for database
    $date_acquired_input = trim($_POST['date_acquired'] ?? date('m/d/Y'));
    $date_acquired = date('Y-m-d', strtotime($date_acquired_input));
    
    // Parse property numbers into array
    $property_numbers_array = [];
    if (!empty($property_numbers)) {
        $property_numbers_array = array_map('trim', explode("\n", $property_numbers));
        $property_numbers_array = array_filter($property_numbers_array, function($num) {
            return !empty($num);
        });
    }
    
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
        error_log("DEBUG: Validation failed - Empty description");
    } elseif ($asset_categories_id <= 0) {
        $message = "Please select a category.";
        $message_type = "danger";
        error_log("DEBUG: Validation failed - Invalid category: $asset_categories_id");
    } elseif ($office_id <= 0) {
        $message = "Please select an office.";
        $message_type = "danger";
        error_log("DEBUG: Validation failed - Invalid office: $office_id");
    } elseif (empty($unit)) {
        $message = "Unit is required.";
        $message_type = "danger";
        error_log("DEBUG: Validation failed - Empty unit");
    } elseif ($quantity <= 0) {
        $message = "Quantity must be greater than 0.";
        $message_type = "danger";
        error_log("DEBUG: Validation failed - Invalid quantity: $quantity");
    } elseif ($unit_cost < 0) {
        $message = "Unit cost cannot be negative.";
        $message_type = "danger";
        error_log("DEBUG: Validation failed - Negative unit cost: $unit_cost");
    } else {
        error_log("DEBUG: Form validation passed - Description: '$description', Unit: '$unit', Category: $asset_categories_id, Office: $office_id, Quantity: $quantity, Cost: $unit_cost");
        try {
            if ($existing_asset) {
                // Update existing asset quantity using traditional SQL
                $new_quantity = $existing_asset['quantity'] + $quantity;
                $unit = mysqli_real_escape_string($conn, $unit);
                $unit_cost = floatval($unit_cost);
                $existing_asset_id = intval($existing_asset['id']);
                
                $update_sql = "UPDATE assets SET quantity = '$new_quantity', unit_cost = '$unit_cost', unit = '$unit', asset_subcategory_id = " . ($asset_subcategory_id ? "'$asset_subcategory_id'" : "NULL") . " WHERE id = '$existing_asset_id'";
                error_log("DEBUG: Update SQL: " . $update_sql);
                
                if ($conn->query($update_sql)) {
                    $asset_id = $existing_asset['id'];
                    error_log("DEBUG: Asset updated with ID: $asset_id");
                    
                    // Create additional asset items for new quantity
                    for ($i = 1; $i <= $quantity; $i++) {
                        $item_description = mysqli_real_escape_string($conn, $description);
                        $item_status = 'no_tag';
                        $acquisition_date = mysqli_real_escape_string($conn, $date_acquired);
                        
                        // First create asset item without property number (like PAR)
                        $item_sql = "INSERT INTO asset_items (asset_id, description, status, value, acquisition_date, office_id) 
                                     VALUES ('$asset_id', '$item_description', '$item_status', '$unit_cost', '$acquisition_date', '$office_id')";
                        error_log("DEBUG: Item SQL: " . $item_sql);
                        
                        if ($conn->query($item_sql)) {
                            $asset_item_id = $conn->insert_id;
                            error_log("DEBUG: Successfully created item $i with ID: " . $asset_item_id);
                            
                            // Then assign property number if available (like PAR)
                            $item_property_number = '';
                            if (isset($property_numbers_array[$i - 1])) {
                                $item_property_number = mysqli_real_escape_string($conn, $property_numbers_array[$i - 1]);
                                
                                // Update asset item with property number only (keep no_tag status)
                                $update_sql = "UPDATE asset_items SET property_no = ? WHERE id = ?";
                                $update_stmt = $conn->prepare($update_sql);
                                if ($update_stmt) {
                                    $update_stmt->bind_param("si", $item_property_number, $asset_item_id);
                                    $update_stmt->execute();
                                    $update_stmt->close();
                                    error_log("DEBUG: Updated item $asset_item_id with property number: $item_property_number (status remains no_tag)");
                                }
                            }
                        } else {
                            error_log("DEBUG: Failed to create item $i - Error: " . $conn->error);
                        }
                    }
                    
                    $message = "Asset quantity updated successfully! Added {$quantity} more items to existing asset.";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'asset_quantity_updated', 'asset_management', "Updated quantity for existing asset: {$description}");
                    
                    // Redirect to refresh the page and show the updated asset
                    header('Location: assets.php?success=1');
                    exit();
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
                
                $sql = "INSERT INTO assets (asset_categories_id, asset_subcategory_id, description, unit, quantity, unit_cost, office_id) 
                        VALUES ('$asset_categories_id', " . ($asset_subcategory_id ? "'$asset_subcategory_id'" : "NULL") . ", '$description', '$unit', '$quantity', '$unit_cost', '$office_id')";
                
                error_log("DEBUG: Final SQL Query: " . $sql);
                error_log("DEBUG: asset_subcategory_id value being inserted: " . ($asset_subcategory_id ? "'$asset_subcategory_id'" : "NULL"));
                
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
                        $item_description = mysqli_real_escape_string($conn, $description);
                        $item_status = 'no_tag';
                        $acquisition_date = mysqli_real_escape_string($conn, $date_acquired);
                        
                        // First create asset item without property number (like PAR)
                        $item_sql = "INSERT INTO asset_items (asset_id, description, status, value, acquisition_date, office_id) 
                                     VALUES ('$asset_id', '$item_description', '$item_status', '$unit_cost', '$acquisition_date', '$office_id')";
                        error_log("DEBUG: New Item SQL: " . $item_sql);
                        
                        if ($conn->query($item_sql)) {
                            $asset_item_id = $conn->insert_id;
                            error_log("DEBUG: Successfully created new item $i with ID: " . $asset_item_id);
                            
                            // Then assign property number if available (like PAR)
                            $item_property_number = '';
                            if (isset($property_numbers_array[$i - 1])) {
                                $item_property_number = mysqli_real_escape_string($conn, $property_numbers_array[$i - 1]);
                                
                                // Update asset item with property number only (keep no_tag status)
                                $update_sql = "UPDATE asset_items SET property_no = ? WHERE id = ?";
                                $update_stmt = $conn->prepare($update_sql);
                                if ($update_stmt) {
                                    $update_stmt->bind_param("si", $item_property_number, $asset_item_id);
                                    $update_stmt->execute();
                                    $update_stmt->close();
                                    error_log("DEBUG: Updated new item $asset_item_id with property number: $item_property_number (status remains no_tag)");
                                }
                            }
                        } else {
                            error_log("DEBUG: Failed to create new item $i - Error: " . $conn->error);
                        }
                    }
                    
                    $message = "Asset added successfully!";
                    $message_type = "success";
                    
                    logSystemAction($_SESSION['user_id'], 'asset_added', 'asset_management', "Added asset: {$description}");
                    
                    // Create notification for MAIN_USER
                    createMainUserNotification($asset_id, $description);
                    
                    // Redirect to refresh the page and show the new asset
                    header('Location: assets.php?success=1');
                    exit();
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
$subcategory_filter = isset($_GET['subcategory']) ? intval($_GET['subcategory']) : 0;
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Debug logging for filters
error_log("DEBUG: Filter parameters - Category: $category_filter, Subcategory: $subcategory_filter, Office: $office_filter, Search: '$search_filter'");

// Get assets with category, subcategory, and office information
$assets = [];
try {
    $sql = "SELECT a.*, ac.category_name, ac.category_code, o.office_name,
                   sc.sub_category_name, sc.sub_category_code,
                   (SELECT ai.status FROM asset_items ai WHERE ai.asset_id = a.id GROUP BY ai.status ORDER BY COUNT(*) DESC LIMIT 1) as most_common_status
            FROM assets a 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN asset_sub_categories sc ON a.asset_subcategory_id = sc.id
            LEFT JOIN offices o ON a.office_id = o.id 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($category_filter > 0) {
        $sql .= " AND a.asset_categories_id = ?";
        $params[] = $category_filter;
        $types .= 'i';
    }
    
    if ($subcategory_filter > 0) {
        $sql .= " AND a.asset_subcategory_id = ?";
        $params[] = $subcategory_filter;
        $types .= 'i';
    }
    
    if ($office_filter > 0) {
        $sql .= " AND a.office_id = ?";
        $params[] = $office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $sql .= " AND (a.description LIKE ? OR ac.category_name LIKE ? OR ac.category_code LIKE ? OR sc.sub_category_name LIKE ? OR sc.sub_category_code LIKE ? OR o.office_name LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ssssss';
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

// Get subcategories for the selected category
$subcategories = [];
if ($category_filter > 0) {
    try {
        $stmt = $conn->prepare("SELECT id, sub_category_code, sub_category_name FROM asset_sub_categories WHERE asset_categories_id = ? AND status = 'active' ORDER BY sub_category_code");
        $stmt->bind_param("i", $category_filter);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $subcategories[] = $row;
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching subcategories: " . $e->getMessage());
    }
}

// Get offices for dropdown
$offices = [];
try {
    $result = $conn->query("SELECT id, office_name, office_code FROM offices WHERE status = 'active' ORDER BY office_name");
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
                COUNT(DISTINCT ai.asset_id) as total_assets,
                COUNT(ai.id) as total_quantity,
                SUM(ai.value) as total_value,
                COUNT(DISTINCT a.asset_categories_id) as total_categories,
                COUNT(DISTINCT a.office_id) as total_offices,
                SUM(CASE WHEN ai.status = 'available' THEN 1 ELSE 0 END) as serviceable_count,
                SUM(CASE WHEN ai.status = 'in_use' THEN 1 ELSE 0 END) as unserviceable_count,
                SUM(CASE WHEN ai.status = 'no_tag' THEN 1 ELSE 0 END) as no_tag_count,
                SUM(CASE WHEN ai.status = 'red_tagged' THEN 1 ELSE 0 END) as red_tagged_count,
                SUM(CASE WHEN ai.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count
            FROM asset_items ai
            LEFT JOIN assets a ON ai.asset_id = a.id";
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
    <!-- Bootstrap Date Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
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
                    <div class="no-print">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                                        <i class="bi bi-plus-circle"></i> Add Asset
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item" onclick="exportAssets()">
                                        <i class="bi bi-download"></i> Export Assets
                                    </button>
                                </li>
                                <li>
                                    <a href="inventory_tags.php" class="dropdown-item">
                                        <i class="bi bi-qr-code"></i> Inventory Tags
                                    </a>
                                </li>
                                <li>
                                    <a href="unserviceable_assets.php" class="dropdown-item">
                                        <i class="bi bi-x-circle"></i> Unserviceable Assets
                                    </a>
                                </li>
                                <li>
                                    <a href="no_inventory_tag.php" class="dropdown-item">
                                        <i class="bi bi-tag"></i> No Inventory Tag
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item" onclick="location.reload()">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_quantity'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-box-fill text-primary"></i> Total Assets</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['serviceable_count'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-check-circle-fill text-success"></i> Serviceable</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['unserviceable_count'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-x-circle-fill text-danger"></i> Unserviceable</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['no_tag_count'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-tag-fill text-secondary"></i> No Tag Assets</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['red_tagged_count'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-exclamation-triangle-fill text-danger"></i> Red-Tagged</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['maintenance_count'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-tools text-warning"></i> Maintenance</div>
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
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="subcategoryFilter" <?php echo $category_filter == 0 ? 'disabled' : ''; ?>>
                                <option value="">All Subcategories</option>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?php echo $subcategory['id']; ?>" <?php echo $subcategory_filter == $subcategory['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subcategory['sub_category_code'] . ' - ' . $subcategory['sub_category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="officeFilter">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <!-- Search removed - using DataTables built-in search -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="assetsTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Office</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assets)): ?>
                            <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($asset['sub_category_name'] ?? 'No subcategory'); ?></td>
                                    <td><?php echo htmlspecialchars($asset['description']); ?></td>
                                    <td><?php echo $asset['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($asset['office_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="asset_items.php?asset_id=<?php echo $asset['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i> View Items
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" onsubmit="console.log('Form submitted - Subcategory value:', document.getElementById('assetSubcategorySelect').value); return true;">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <!-- Asset Classification Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-tags"></i> Asset Classification</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Category *</label>
                                            <select class="form-select" name="asset_categories_id" id="assetCategorySelect" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" data-category-code="<?php echo htmlspecialchars($category['category_code']); ?>">
                                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Subcategory</label>
                                            <select class="form-select" name="asset_subcategory_id" id="assetSubcategorySelect">
                                                <option value="">Select Subcategory</option>
                                                <?php 
                                                // Get all subcategories for generator
                                                $all_subcategories_result = $conn->query("SELECT sc.id, sc.sub_category_code, sc.sub_category_name, ac.category_code, ac.id as category_id FROM asset_sub_categories sc JOIN asset_categories ac ON sc.asset_categories_id = ac.id WHERE sc.status = 'active' ORDER BY ac.category_code, sc.sub_category_code");
                                                if ($all_subcategories_result) {
                                                    while ($subcategory = $all_subcategories_result->fetch_assoc()) {
                                                        echo '<option value="' . $subcategory['id'] . '" data-category="' . htmlspecialchars($subcategory['category_code']) . '" data-category-id="' . $subcategory['category_id'] . '" data-subcategory-code="' . htmlspecialchars($subcategory['sub_category_code']) . '">' . htmlspecialchars($subcategory['sub_category_code'] . ' - ' . $subcategory['sub_category_name']) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <input type="text" class="form-control" name="description" required placeholder="Enter asset description">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Asset Details Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Asset Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Quantity *</label>
                                            <input type="number" class="form-control" name="quantity" min="1" required placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Unit *</label>
                                            <select class="form-select" name="unit" id="assetUnitSelect" required>
                                                <option value="">Select Unit</option>
                                                <?php foreach ($units as $unit): ?>
                                                    <option value="<?php echo htmlspecialchars($unit['unit_code']); ?>" data-unit-name="<?php echo htmlspecialchars($unit['unit_name']); ?>" data-singular="<?php echo htmlspecialchars(getSingularForm($unit['unit_name'])); ?>"><?php echo htmlspecialchars($unit['unit_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Unit Cost *</label>
                                            <input type="number" class="form-control" name="unit_cost" step="0.01" min="0" required placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Total Value</label>
                                            <input type="text" class="form-control" id="totalValue" readonly placeholder="0.00">
                                            <small class="text-muted">Auto-calculated</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Date Acquired</label>
                                            <input type="text" class="form-control" name="date_acquired" id="dateAcquired" value="<?php echo date('m/d/Y'); ?>" placeholder="mm/dd/yyyy" pattern="(0[1-9]|1[0-2])/(0[1-9]|[12][0-9]|3[01])/\d{4}" title="Please enter date in mm/dd/yyyy format">
                                            <small class="text-muted">When the asset was acquired (mm/dd/yyyy)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Assignment Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-building"></i> Assignment & Identification</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Office *</label>
                                    <select class="form-select" name="office_id" id="assetOfficeSelect" required>
                                        <option value="">Select Office</option>
                                        <?php foreach ($offices as $office): ?>
                                            <option value="<?php echo $office['id']; ?>" data-office-code="<?php echo htmlspecialchars($office['office_code']); ?>">
                                                <?php echo htmlspecialchars($office['office_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Property Numbers</label>
                                    <div class="property-number-field">
                                        <textarea class="form-control" name="property_numbers" id="assetPropertyNumbers" rows="3" readonly placeholder="Property numbers will be generated automatically"></textarea>
                                        <small class="text-muted">Property numbers will be generated automatically based on asset details</small>
                                    </div>
                                    <small class="text-muted">Format: YEAR-FORM-FUND-CATEGORY-SUBCATEGORY+SERIES-OFFICE</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="addAssetSubmitBtn">
                            <i class="bi bi-plus-circle"></i> Add Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            const addAssetForm = document.querySelector('#addAssetModal form');
            const submitBtn = document.getElementById('addAssetSubmitBtn');
            
            if (addAssetForm && submitBtn) {
                addAssetForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Clear previous error messages
                    const existingAlerts = addAssetForm.querySelectorAll('.alert-danger');
                    existingAlerts.forEach(alert => alert.remove());
                    
                    // Generate property numbers automatically before submission
                    const success = generatePropertyNumbersAutomatically();
                    
                    if (!success) {
                        // Show error message
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger mt-3';
                        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Failed to generate property numbers automatically. Please check your form data.';
                        
                        const modalBody = addAssetForm.querySelector('.modal-body');
                        modalBody.insertBefore(errorDiv, modalBody.firstChild);
                        
                        return false;
                    }
                    
                    // Validate form
                    const validation = validateAssetForm();
                    
                    if (!validation.valid) {
                        // Show error message
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger mt-3';
                        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + validation.message;
                        
                        const modalBody = addAssetForm.querySelector('.modal-body');
                        modalBody.insertBefore(errorDiv, modalBody.firstChild);
                        
                        return false;
                    }
                    
                    // Disable submit button to prevent double submission
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Adding...';
                    
                    // Submit the form using traditional POST (not AJAX)
                    addAssetForm.submit();
                });
            }
            
            // Function to generate property numbers automatically
            function generatePropertyNumbersAutomatically() {
                try {
                    const categorySelect = document.getElementById('assetCategorySelect');
                    const subcategorySelect = document.getElementById('assetSubcategorySelect');
                    const officeSelect = document.getElementById('assetOfficeSelect');
                    const quantityInput = document.querySelector('input[name="quantity"]');
                    const unitCostInput = document.querySelector('input[name="unit_cost"]');
                    const propertyNumbersField = document.getElementById('assetPropertyNumbers');
                    
                    if (!categorySelect.value || !officeSelect.value || !quantityInput.value || !unitCostInput.value) {
                        return false;
                    }
                    
                    const quantity = parseInt(quantityInput.value) || 1;
                    const unitCost = parseFloat(unitCostInput.value) || 0;
                    const year = new Date().getFullYear();
                    
                    // Get category code
                    const categoryOption = categorySelect.options[categorySelect.selectedIndex];
                    const categoryCode = categoryOption ? categoryOption.getAttribute('data-category-code') : '000';
                    
                    // Get subcategory code - FIXED: Extract actual subcategory code
                    let subcategoryCode = '00';
                    if (subcategorySelect.value) {
                        const subcategoryOption = subcategorySelect.options[subcategorySelect.selectedIndex];
                        // Try to get subcategory code from data-subcategory-code attribute first
                        subcategoryCode = subcategoryOption ? subcategoryOption.getAttribute('data-subcategory-code') || '00' : '00';
                        
                        // If no data-subcategory-code attribute, try to extract from option text
                        if (!subcategoryCode || subcategoryCode === '00') {
                            const optionText = subcategoryOption ? subcategoryOption.textContent : '';
                            // Extract code from text like "01 - LAPTOP" or "LAPTOP (01)"
                            const codeMatch = optionText.match(/^(\d{2})\s*-\s*|\((\d{2})\)/);
                            if (codeMatch) {
                                subcategoryCode = codeMatch[1] || codeMatch[2] || '00';
                            }
                        }
                    }
                    
                    // Get office code
                    const officeOption = officeSelect.options[officeSelect.selectedIndex];
                    const officeCode = officeOption ? officeOption.getAttribute('data-office-code') : '01';
                    
                    // Determine form type based on unit cost
                    const formType = unitCost < 50000 ? '04' : '07';
                    
                    // Generate property numbers
                    const propertyNumbers = [];
                    for (let i = 0; i < quantity; i++) {
                        const series = String(i + 1).padStart(2, '0');
                        const subcategorySeries = subcategoryCode + series;
                        const propertyNumber = `${year}-${formType}-${categoryCode}-${subcategorySeries}-${officeCode}`;
                        propertyNumbers.push(propertyNumber);
                    }
                    
                    // Set the property numbers in the field
                    propertyNumbersField.value = propertyNumbers.join('\n');
                    
                    console.log('Generated property numbers:', propertyNumbers);
                    console.log('Used subcategory code:', subcategoryCode);
                    
                    return true;
                } catch (error) {
                    console.error('Error generating property numbers:', error);
                    return false;
                }
            }
            
            // Auto-generate property numbers when form fields change
            const formFields = ['assetCategorySelect', 'assetSubcategorySelect', 'assetOfficeSelect', 'quantity', 'unit_cost'];
            formFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`) || document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('change', generatePropertyNumbersAutomatically);
                    field.addEventListener('input', generatePropertyNumbersAutomatically);
                }
            });
            
            // Form validation function
            function validateAssetForm() {
                const category = document.querySelector('select[name="asset_categories_id"]').value;
                const description = document.querySelector('input[name="description"]').value.trim();
                const quantity = parseInt(document.querySelector('input[name="quantity"]').value);
                const unit = document.querySelector('select[name="unit"]').value;
                const unitCost = parseFloat(document.querySelector('input[name="unit_cost"]').value);
                const office = document.querySelector('select[name="office_id"]').value;
                
                // Additional validation for date format
                const dateAcquired = document.querySelector('input[name="date_acquired"]').value.trim();
                const dateRegex = /^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}$/;
                
                if (dateAcquired && !dateRegex.test(dateAcquired)) {
                    return { valid: false, message: 'Date acquired must be in mm/dd/yyyy format.' };
                }
                
                if (!category) {
                    return { valid: false, message: 'Please select a category.' };
                }
                
                if (!description) {
                    return { valid: false, message: 'Asset description is required.' };
                }
                
                if (!quantity || quantity <= 0) {
                    return { valid: false, message: 'Quantity must be greater than 0.' };
                }
                
                if (!unit) {
                    return { valid: false, message: 'Please select a unit.' };
                }
                
                if (isNaN(unitCost) || unitCost < 0) {
                    return { valid: false, message: 'Unit cost must be a valid positive number.' };
                }
                
                if (!office) {
                    return { valid: false, message: 'Please select an office.' };
                }
                
                return { valid: true, message: '' };
            }
        });
    </script>
    
    
    
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap Date Picker JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
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
        // Asset data for editing
        const assetData = <?php echo json_encode($assets); ?>;
        const categoriesData = <?php echo json_encode($categories); ?>;
        
        // Function to update unit display based on quantity
        function updateUnitDisplay() {
            const quantity = parseInt(document.querySelector('input[name="quantity"]').value) || 0;
            const unitSelect = document.getElementById('assetUnitSelect');
            
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
            console.log('DEBUG: getStatusBadge called with status:', JSON.stringify(status), 'type:', typeof status, 'length:', status ? status.length : 'null');
            const badges = {
                'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                'available': '<span class="badge bg-success">Available</span>',
                'in_use': '<span class="badge bg-primary">In Use</span>',
                'maintenance': '<span class="badge bg-warning">Maintenance</span>',
                'disposed': '<span class="badge bg-danger">Disposed</span>'
            };
            const result = badges[status] || '<span class="badge bg-secondary">Unknown</span>';
            console.log('DEBUG: getStatusBadge result:', result);
            return result;
        }
        
        
        // Initialize DataTable
        let assetsTable;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date picker with mm/dd/yyyy format
            $('#dateAcquired').datepicker({
                format: 'mm/dd/yyyy',
                autoclose: true,
                todayHighlight: true,
                orientation: 'bottom auto'
            });
            
            // Check if table has data rows before initializing DataTables
            const tableBody = $('#assetsTable tbody');
            const hasData = tableBody.find('tr').length > 0 && !tableBody.find('td[colspan]').length;
            
            console.log('Table has data:', hasData);
            console.log('Table rows found:', tableBody.find('tr').length);
            
            // Initialize DataTable with error handling
            try {
                if (hasData) {
                    // Only initialize DataTables if there's actual data
                    assetsTable = $('#assetsTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        order: [[4, 'desc']], // Sort by Created date column (index 4) by default
                        columnDefs: [
                            {
                                targets: 0, // Category column
                                orderable: true,
                                render: function(data, type, row) {
                                    if (type === 'display') {
                                        return data;
                                    }
                                    return data.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
                                }
                            },
                            {
                                targets: 4, // Created date column
                                orderable: true,
                                render: function(data, type, row) {
                                    if (type === 'sort' || type === 'type') {
                                        // Convert date string to timestamp for sorting
                                        return new Date(data).getTime();
                                    }
                                    return data;
                                }
                            },
                            {
                                targets: -1, // Actions column (last column)
                                orderable: false,
                                searchable: false
                            }
                        ],
                        dom: '<"row"<"col-md-6"l><"col-md-6 text-end"f>>rtip',
                        language: {
                            search: "Search assets:",
                            lengthMenu: "Show _MENU_ assets per page",
                            info: "Showing _START_ to _END_ of _TOTAL_ assets",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            },
                            emptyTable: "No assets available",
                            zeroRecords: "No matching assets found"
                        },
                        initComplete: function(settings, json) {
                            console.log('DataTables initialized successfully');
                            // Initialize subcategory filter state after DataTables is ready
                            initializeSubcategoryFilter();
                        }
                    });
                } else {
                    // No data - don't initialize DataTables, just add basic styling
                    $('#assetsTable').addClass('table-striped');
                    console.log('No data found - DataTables not initialized');
                    // Still initialize subcategory filter
                    initializeSubcategoryFilter();
                }
            } catch (error) {
                console.error('DataTables initialization error:', error);
                // Fallback: make table work without DataTables
                $('#assetsTable').addClass('table-striped');
                // Initialize subcategory filter even if DataTables fails
                initializeSubcategoryFilter();
            }
            
            // Function to initialize subcategory filter based on current category selection
            function initializeSubcategoryFilter() {
                const categoryValue = $('#categoryFilter').val();
                const subcategorySelect = $('#subcategoryFilter');
                
                console.log('Initializing subcategory filter with category:', categoryValue);
                
                // Check if subcategories are already loaded via PHP
                const hasPhpSubcategories = subcategorySelect.find('option').length > 1;
                
                if (hasPhpSubcategories && categoryValue) {
                    console.log('Subcategories already loaded via PHP, skipping AJAX');
                    // Just make sure the subcategory filter is enabled
                    subcategorySelect.prop('disabled', false);
                    return;
                }
                
                if (categoryValue) {
                    // Enable subcategory filter and show loading
                    subcategorySelect.prop('disabled', false);
                    subcategorySelect.empty().append('<option value="">Loading subcategories...</option>');
                    
                    // Load subcategories for selected category - using simple API for testing
                    $.ajax({
                        url: './api/get_subcategories_simple.php',
                        method: 'GET',
                        data: { category_id: categoryValue },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Get current selected subcategory value
                                const currentSubcategory = subcategorySelect.val();
                                
                                // Clear current options
                                subcategorySelect.empty().append('<option value="">All Subcategories</option>');
                                
                                // Add new subcategory options
                                response.subcategories.forEach(function(subcat) {
                                    subcategorySelect.append(
                                        $('<option>', {
                                            value: subcat.id,
                                            text: subcat.code + ' - ' + subcat.name,
                                            selected: subcat.id == currentSubcategory
                                        })
                                    );
                                });
                                
                                console.log('Initialized subcategories:', response.subcategories);
                            } else {
                                console.error('Error loading subcategories:', response.error);
                                subcategorySelect.empty().append('<option value="">Error: ' + response.error + '</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error details:', {
                                status: status,
                                error: error,
                                responseText: xhr.responseText,
                                statusCode: xhr.status
                            });
                            
                            let errorMessage = 'Error loading subcategories';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.status === 404) {
                                errorMessage = 'API endpoint not found';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Server error - check logs';
                            }
                            
                            subcategorySelect.empty().append('<option value="">' + errorMessage + '</option>');
                        }
                    });
                } else {
                    // Disable subcategory filter if no category selected
                    subcategorySelect.prop('disabled', true);
                }
            }
            
            // Category filter - dynamically load subcategories and apply filter
            $('#categoryFilter').on('change', function() {
                const categoryValue = this.value;
                const subcategorySelect = $('#subcategoryFilter');
                
                console.log('Category filter changed:', categoryValue);
                
                if (categoryValue) {
                    // Enable subcategory filter and show loading
                    subcategorySelect.prop('disabled', false);
                    subcategorySelect.empty().append('<option value="">Loading subcategories...</option>');
                    
                    // Load subcategories for selected category - using simple API for testing
                    $.ajax({
                        url: './api/get_subcategories_simple.php',
                        method: 'GET',
                        data: { category_id: categoryValue },
                        dataType: 'json',
                        beforeSend: function(xhr) {
                            console.log('Sending AJAX request to: ./api/get_subcategories_simple.php?category_id=' + categoryValue);
                        },
                        success: function(response) {
                            console.log('AJAX response received:', response);
                            
                            if (response.success) {
                                // Clear current options
                                subcategorySelect.empty().append('<option value="">All Subcategories</option>');
                                
                                // Add new subcategory options
                                response.subcategories.forEach(function(subcat) {
                                    subcategorySelect.append(
                                        $('<option>', {
                                            value: subcat.id,
                                            text: subcat.code + ' - ' + subcat.name
                                        })
                                    );
                                });
                                
                                console.log('Loaded subcategories:', response.subcategories);
                            } else {
                                console.error('Error loading subcategories:', response.error);
                                subcategorySelect.empty().append('<option value="">Error: ' + response.error + '</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error details:', {
                                status: status,
                                error: error,
                                responseText: xhr.responseText,
                                statusCode: xhr.status
                            });
                            
                            let errorMessage = 'Error loading subcategories';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.status === 404) {
                                errorMessage = 'API endpoint not found';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Server error - check logs';
                            }
                            
                            subcategorySelect.empty().append('<option value="">' + errorMessage + '</option>');
                        }
                    });
                    
                    // Apply category filter
                    applyFilters();
                } else {
                    // Disable and clear subcategory filter
                    subcategorySelect.prop('disabled', true);
                    subcategorySelect.empty().append('<option value="">All Subcategories</option>');
                    
                    // Apply filters (will clear category filter)
                    applyFilters();
                }
            });
            
            // Subcategory filter - apply filter dynamically
            $('#subcategoryFilter').on('change', function() {
                const subcategoryValue = this.value;
                console.log('Subcategory filter changed:', subcategoryValue);
                applyFilters();
            });
            
            // Office filter - apply filter dynamically
            $('#officeFilter').on('change', function() {
                const officeValue = this.value;
                console.log('Office filter changed:', officeValue);
                applyFilters();
            });
            
            // Function to apply all filters dynamically
            function applyFilters() {
                const categoryValue = $('#categoryFilter').val();
                const subcategoryValue = $('#subcategoryFilter').val();
                const officeValue = $('#officeFilter').val();
                
                console.log('Applying filters:', {
                    category: categoryValue,
                    subcategory: subcategoryValue,
                    office: officeValue
                });
                
                // Build filter conditions for DataTables
                let searchTerms = [];
                
                if (categoryValue) {
                    // Filter by category - this will be handled by server-side reload
                    // For now, we'll reload the page to maintain consistency with existing logic
                    const currentUrl = new URL(window.location);
                    currentUrl.searchParams.set('category', categoryValue);
                    if (subcategoryValue) {
                        currentUrl.searchParams.set('subcategory', subcategoryValue);
                    } else {
                        currentUrl.searchParams.delete('subcategory');
                    }
                    if (officeValue) {
                        currentUrl.searchParams.set('office', officeValue);
                    } else {
                        currentUrl.searchParams.delete('office');
                    }
                    currentUrl.searchParams.delete('page'); // Reset pagination
                    window.location.href = currentUrl.toString();
                } else {
                    // No category filter - clear all filters and reload
                    const currentUrl = new URL(window.location);
                    currentUrl.searchParams.delete('category');
                    currentUrl.searchParams.delete('subcategory');
                    currentUrl.searchParams.delete('office');
                    currentUrl.searchParams.delete('page');
                    window.location.href = currentUrl.toString();
                }
            }
            
        });
        
        // Export assets function (updated for DataTables)
        function exportAssets() {
            console.log('Export function called');
            
            if (assetsTable) {
                // Use DataTables export functionality if DataTables is initialized
                try {
                    const data = assetsTable.data().toArray();
                    let csv = 'Category,Subcategory,Description,Quantity,Office\n';
                    
                    data.forEach(row => {
                        const rowData = [
                            row[0].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Category
                            row[1].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Subcategory
                            row[2], // Description
                            row[3], // Quantity
                            row[4].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()  // Office
                        ];
                        csv += rowData.map(cell => `"${cell.trim()}"`).join(',') + '\n';
                    });
                    
                    // Download CSV
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'assets_export.csv';
                    a.click();
                    window.URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('DataTables export error:', error);
                    // Fallback to manual table export
                    exportTableManually();
                }
            } else {
                // DataTables not initialized, use manual export
                exportTableManually();
            }
        }
        
        // Manual export function for when DataTables is not available
        function exportTableManually() {
            console.log('Using manual table export');
            let csv = 'Category,Subcategory,Description,Quantity,Office\n';
            
            $('#assetsTable tbody tr').each(function() {
                const $row = $(this);
                // Skip empty state rows
                if ($row.find('td[colspan]').length > 0) {
                    return;
                }
                
                const rowData = [];
                $row.find('td').each(function(index) {
                    let cellText = $(this).text().trim();
                    // Only include first 5 columns (exclude Actions column)
                    if (index < 5) {
                        rowData.push(cellText);
                    }
                });
                
                if (rowData.length > 0) {
                    csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
                }
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'assets_export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Category/subcategory filtering for the main form
        document.addEventListener('DOMContentLoaded', function() {
            const assetCategorySelect = document.getElementById('assetCategorySelect');
            const assetSubcategorySelect = document.getElementById('assetSubcategorySelect');
            
            if (assetCategorySelect && assetSubcategorySelect) {
                assetCategorySelect.addEventListener('change', function() {
                    const selectedCategoryCode = this.options[this.selectedIndex].getAttribute('data-category-code');
                    const options = assetSubcategorySelect.querySelectorAll('option');
                    
                    options.forEach(option => {
                        if (option.value === '') {
                            option.style.display = 'block';
                        } else {
                            const optionCategory = option.getAttribute('data-category');
                            const shouldShow = optionCategory === selectedCategoryCode || selectedCategoryCode === '';
                            option.style.display = shouldShow ? 'block' : 'none';
                        }
                    });
                    
                    // Reset subcategory if it doesn't match the new category
                    if (assetSubcategorySelect.value && assetSubcategorySelect.options[assetSubcategorySelect.selectedIndex].getAttribute('data-category') !== selectedCategoryCode) {
                        assetSubcategorySelect.value = '';
                    }
                });
            }
        });
        
        // Add event listener for quantity change
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.querySelector('input[name="quantity"]');
            if (quantityInput) {
                // Set initial unit display if quantity has a value
                updateUnitDisplay();
            }
        });
        
        // Total value calculation
        function calculateTotalValue() {
            const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
            const unitCost = parseFloat(document.querySelector('input[name="unit_cost"]').value) || 0;
            const totalValue = quantity * unitCost;
            document.getElementById('totalValue').value = totalValue.toFixed(2);
        }
        
        // Add event listeners for total value calculation
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.querySelector('input[name="quantity"]');
            const unitCostInput = document.querySelector('input[name="unit_cost"]');
            const unitSelect = document.getElementById('assetUnitSelect');
            
            if (quantityInput) {
                quantityInput.addEventListener('input', calculateTotalValue);
                quantityInput.addEventListener('input', updateUnitDisplay);
                quantityInput.addEventListener('change', updateUnitDisplay);
                
                // Set initial unit display if quantity has a value
                updateUnitDisplay();
            }
            if (unitCostInput) {
                unitCostInput.addEventListener('input', calculateTotalValue);
            }
            if (unitSelect) {
                unitSelect.addEventListener('change', updateUnitDisplay);
            }
        });
    </script>
</body>
</html>

<?php
// Function to create notification for MAIN_USER when asset is added
function createMainUserNotification($asset_id, $asset_description) {
    global $conn;
    
    // Get all MAIN_USER users
    $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND is_active = 1";
    $main_users_result = $conn->query($main_users_query);
    
    if ($main_users_result && $main_users_result->num_rows > 0) {
        while ($main_user = $main_users_result->fetch_assoc()) {
            $user_id = $main_user['id'];
            $title = "New Asset Added";
            $message = "A new asset '{$asset_description}' has been added to the system.";
            $type = "success";
            $related_id = $asset_id;
            $related_type = "asset";
            
            // Insert notification
            $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
            $stmt->execute();
        }
    }
}
?>
