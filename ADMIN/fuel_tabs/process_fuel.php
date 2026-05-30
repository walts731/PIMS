<?php
// Enable error display temporarily for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any previous output
if (ob_get_level()) {
    ob_clean();
}

require_once '../../config.php';

// Check if user is logged in first
if (!isset($_SESSION['user_id'])) {
    $_SESSION['fuel_error'] = 'Unauthorized - please log in';
    header('Location: ../fuel.php?tab=fuelin');
    exit();
}

require_once '../includes/check_permissions.php';

$user_id = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

// For traditional form submissions (add-fuel-in), don't set JSON header
if ($action === 'add-fuel-in') {
    adminRequirePermission('fuel.manage', 'can_create', '../fuel.php?tab=fuelin');
    addFuelIn();
    exit();
}

// For AJAX requests, set JSON header
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'add-fuel-in':
            // Handled above before JSON header
            break;
        case 'quick-add-fuel-in':
            adminRequirePermission('fuel.manage', 'can_create', '../fuel.php');
            quickAddFuelIn();
            break;
        case 'add-fuel-out':
            adminRequirePermission('fuel.manage', 'can_create', '../fuel.php');
            addFuelOut();
            break;
        case 'quick-add-fuel-out':
            adminRequirePermission('fuel.manage', 'can_create', '../fuel.php');
            quickAddFuelOut();
            break;
        case 'add-stock':
            adminRequirePermission('fuel.manage', 'can_create', '../fuel.php');
            addStock();
            break;
        case 'edit-stock':
            adminRequirePermission('fuel.manage', 'can_update', '../fuel.php');
            editStock();
            break;
        case 'delete-fuel-in':
            adminRequirePermission('fuel.manage', 'can_delete', '../fuel.php');
            deleteFuelIn();
            break;
        case 'delete-fuel-out':
            adminRequirePermission('fuel.manage', 'can_delete', '../fuel.php');
            deleteFuelOut();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
}

function addFuelIn() {
    global $conn, $user_id;
    
    // Check required fields
    if (empty($_POST['fuel_type'])) {
        $_SESSION['fuel_error'] = 'Fuel type is required';
        header('Location: ../fuel.php?tab=fuelin');
        exit();
    }
    if (empty($_POST['quantity'])) {
        $_SESSION['fuel_error'] = 'Quantity is required';
        header('Location: ../fuel.php?tab=fuelin');
        exit();
    }
    
    // Get fuel type name from ID
    $fuel_type_id = intval($_POST['fuel_type']);
    $fuel_type_query = "SELECT name FROM fuel_types WHERE id = $fuel_type_id";
    $fuel_type_result = $conn->query($fuel_type_query);
    
    if (!$fuel_type_result) {
        $_SESSION['fuel_error'] = 'Database error: ' . $conn->error;
        header('Location: ../fuel.php?tab=fuelin');
        exit();
    }
    
    $fuel_type_row = $fuel_type_result->fetch_assoc();
    if (!$fuel_type_row) {
        $_SESSION['fuel_error'] = 'Invalid fuel type ID: ' . $fuel_type_id;
        header('Location: ../fuel.php?tab=fuelin');
        exit();
    }
    
    $fuel_type = $fuel_type_row['name'];
    $fuel_type_id_value = intval($_POST['fuel_type']);
    $quantity = floatval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $total_cost = $quantity * $unit_price;
    $storage_location = $conn->real_escape_string($_POST['storage_location'] ?? '');
    $delivery_receipt = $conn->real_escape_string($_POST['delivery_receipt'] ?? '');
    $supplier_name = $conn->real_escape_string($_POST['supplier_name'] ?? '');
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    
    // Insert into fuel_in table using fuel_type_id for foreign key
    $sql = "INSERT INTO fuel_in (date_time, fuel_type, quantity, unit_price, total_cost, storage_location, delivery_receipt, supplier_name, received_by, remarks, created_by, created_at) 
            VALUES (NOW(), $fuel_type_id_value, $quantity, $unit_price, $total_cost, '$storage_location', '$delivery_receipt', '$supplier_name', $user_id, '$remarks', $user_id, NOW())";
    
    // Debug: log the SQL
    error_log("Fuel in SQL: " . $sql);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        if ($conn->query($sql)) {
            $fuel_in_id = $conn->insert_id;
            
            // Also insert into fuel_transactions for unified reporting
            $tank_number = determineTankNumber($fuel_type, $storage_location);
            $transaction_sql = "INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, source, supplier, tank_number, user_id, notes) 
                    VALUES ('IN', '$fuel_type', $quantity, NOW(), 'Delivery', '$supplier_name', '$tank_number', $user_id, '$remarks')";
            $conn->query($transaction_sql);
            
            // Update fuel stock quantity
            $check_stock_sql = "SELECT quantity FROM fuel_stock WHERE fuel_type_id = $fuel_type_id_value";
            $stock_result = $conn->query($check_stock_sql);
            $existing_stock = $stock_result ? $stock_result->fetch_assoc() : null;
            
            if ($existing_stock) {
                // Update existing stock - add the new quantity
                $new_quantity = $existing_stock['quantity'] + $quantity;
                $update_sql = "UPDATE fuel_stock SET quantity = $new_quantity, updated_at = NOW() WHERE fuel_type_id = $fuel_type_id_value";
                $conn->query($update_sql);
            } else {
                // Insert new stock record
                $insert_sql = "INSERT INTO fuel_stock (fuel_type_id, quantity, updated_at) VALUES ($fuel_type_id_value, $quantity, NOW())";
                $conn->query($insert_sql);
            }
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['fuel_success'] = 'Fuel in transaction recorded successfully';
            header('Location: ../fuel.php?tab=fuelin');
            exit();
        } else {
            throw new Exception('Database error: ' . $conn->error);
        }
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['fuel_error'] = $e->getMessage() . ' (SQL: ' . $sql . ')';
        header('Location: ../fuel.php?tab=fuelin');
        exit();
    }
}

function quickAddFuelIn() {
    global $conn;
    
    // Get fuel type name from ID
    $fuel_type_id = intval($_POST['fuel_type']);
    $fuel_type_query = "SELECT name FROM fuel_types WHERE id = $fuel_type_id";
    $fuel_type_result = $conn->query($fuel_type_query)->fetch_assoc();
    
    if (!$fuel_type_result) {
        echo json_encode(['success' => false, 'message' => 'Invalid fuel type']);
        return;
    }
    
    $fuel_type = strtolower($fuel_type_result['name']);
    $quantity = floatval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $supplier_name = $conn->real_escape_string($_POST['supplier_name'] ?? '');
    $storage_location = $conn->real_escape_string($_POST['storage_location'] ?? '');
    global $user_id;
    
    // Determine tank number based on fuel type and storage location
    $tank_number = determineTankNumber($fuel_type, $storage_location);
    
    // Insert fuel transaction (trigger will automatically update inventory)
    $sql = "INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, source, supplier, tank_number, user_id, notes) 
            VALUES ('IN', '$fuel_type', $quantity, NOW(), 'Delivery', '$supplier_name', '$tank_number', $user_id, 'Quick add fuel in')";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Fuel in transaction recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record transaction: ' . $conn->error]);
    }
}

function addFuelOut() {
    global $conn;
    
    // Get fuel type name from ID
    $fo_fuel_type_id = intval($_POST['fo_fuel_type']);
    $fuel_type_query = "SELECT name FROM fuel_types WHERE id = $fo_fuel_type_id";
    $fuel_type_result = $conn->query($fuel_type_query)->fetch_assoc();
    
    if (!$fuel_type_result) {
        echo json_encode(['success' => false, 'message' => 'Invalid fuel type']);
        return;
    }
    
    $fuel_type = strtolower($fuel_type_result['name']);
    $fo_fuel_no = $conn->real_escape_string($_POST['fo_fuel_no'] ?? '');
    $fo_plate_no = $conn->real_escape_string($_POST['fo_plate_no'] ?? '');
    $fo_request = $conn->real_escape_string($_POST['fo_request']);
    $fo_liters = floatval($_POST['fo_liters']);
    $fo_vehicle_type = $conn->real_escape_string($_POST['fo_vehicle_type'] ?? '');
    $fo_receiver = $conn->real_escape_string($_POST['fo_receiver'] ?? '');
    $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : 'NULL';
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');
    global $user_id;
    
    // Determine tank number based on fuel type
    $tank_number = determineTankNumber($fuel_type);
    
    // Check if there's enough fuel in inventory
    $tank_number_escaped = $conn->real_escape_string($tank_number);
    $check_sql = "SELECT current_level FROM fuel_inventory WHERE fuel_type = '$fuel_type' AND tank_number = '$tank_number_escaped'";
    $current_level = $conn->query($check_sql)->fetch_assoc()['current_level'] ?? 0;
    
    if ($current_level < $fo_liters) {
        echo json_encode(['success' => false, 'message' => 'Insufficient fuel in inventory. Available: ' . $current_level . ' L']);
        return;
    }
    
    // Insert fuel transaction (trigger will automatically update inventory)
    $sql = "INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, employee_id, recipient_name, purpose, vehicle_equipment, tank_number, user_id, notes) 
            VALUES ('OUT', '$fuel_type', $fo_liters, NOW(), $employee_id, '$fo_receiver', '$fo_request', '$fo_vehicle_type', '$tank_number_escaped', $user_id, '$remarks')";
    
    if ($conn->query($sql)) {
        // Subtract from fuel stock based on fuel type ID
        $check_stock_sql = "SELECT quantity FROM fuel_stock WHERE fuel_type_id = $fo_fuel_type_id";
        $stock_result = $conn->query($check_stock_sql);
        $existing_stock = $stock_result ? $stock_result->fetch_assoc() : null;
        
        if ($existing_stock) {
            $new_quantity = $existing_stock['quantity'] - $fo_liters;
            $update_sql = "UPDATE fuel_stock SET quantity = $new_quantity, updated_at = NOW() WHERE fuel_type_id = $fo_fuel_type_id";
            $conn->query($update_sql);
        }
        
        echo json_encode(['success' => true, 'message' => 'Fuel out transaction recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record transaction: ' . $conn->error]);
    }
}

function quickAddFuelOut() {
    global $conn;
    
    // Get fuel type name from ID
    $fo_fuel_type_id = intval($_POST['fo_fuel_type']);
    $fuel_type_query = "SELECT name FROM fuel_types WHERE id = $fo_fuel_type_id";
    $fuel_type_result = $conn->query($fuel_type_query)->fetch_assoc();
    
    if (!$fuel_type_result) {
        echo json_encode(['success' => false, 'message' => 'Invalid fuel type']);
        return;
    }
    
    $fuel_type = strtolower($fuel_type_result['name']);
    $fo_fuel_no = $conn->real_escape_string($_POST['fo_fuel_no'] ?? '');
    $fo_plate_no = $conn->real_escape_string($_POST['fo_plate_no'] ?? '');
    $fo_request = $conn->real_escape_string($_POST['fo_request']);
    $fo_liters = floatval($_POST['fo_liters']);
    $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : 'NULL';
    global $user_id;
    
    // Determine tank number based on fuel type
    $tank_number = determineTankNumber($fuel_type);
    $tank_number_escaped = $conn->real_escape_string($tank_number);
    
    // Check if there's enough fuel in inventory
    $check_sql = "SELECT current_level FROM fuel_inventory WHERE fuel_type = '$fuel_type' AND tank_number = '$tank_number_escaped'";
    $current_level = $conn->query($check_sql)->fetch_assoc()['current_level'] ?? 0;
    
    if ($current_level < $fo_liters) {
        echo json_encode(['success' => false, 'message' => 'Insufficient fuel in inventory. Available: ' . $current_level . ' L']);
        return;
    }
    
    // Insert fuel transaction (trigger will automatically update inventory)
    $sql = "INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, employee_id, purpose, vehicle_equipment, tank_number, user_id, notes) 
            VALUES ('OUT', '$fuel_type', $fo_liters, NOW(), $employee_id, '$fo_request', '$fo_fuel_no', '$tank_number_escaped', $user_id, 'Quick add fuel out')";
    
    if ($conn->query($sql)) {
        // Subtract from fuel stock based on fuel type ID
        $check_stock_sql = "SELECT quantity FROM fuel_stock WHERE fuel_type_id = $fo_fuel_type_id";
        $stock_result = $conn->query($check_stock_sql);
        $existing_stock = $stock_result ? $stock_result->fetch_assoc() : null;
        
        if ($existing_stock) {
            $new_quantity = $existing_stock['quantity'] - $fo_liters;
            $update_sql = "UPDATE fuel_stock SET quantity = $new_quantity, updated_at = NOW() WHERE fuel_type_id = $fo_fuel_type_id";
            $conn->query($update_sql);
        }
        
        echo json_encode(['success' => true, 'message' => 'Fuel out transaction recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record transaction: ' . $conn->error]);
    }
}

function addStock() {
    global $conn;
    global $user_id;
    
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $quantity = floatval($_POST['quantity'] ?? 0);
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    
    // Check if stock record exists
    $check_sql = "SELECT id, quantity FROM fuel_stock WHERE fuel_type_id = $fuel_type_id";
    $check_result = $conn->query($check_sql);
    $existing = $check_result ? $check_result->fetch_assoc() : null;
    
    if ($existing) {
        // Update existing stock
        $new_quantity = $existing['quantity'] + $quantity;
        $sql = "UPDATE fuel_stock SET quantity = $new_quantity, updated_at = NOW() WHERE fuel_type_id = $fuel_type_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update stock: ' . $conn->error]);
        }
    } else {
        // Insert new stock record
        $sql = "INSERT INTO fuel_stock (fuel_type_id, quantity, updated_at, created_by) VALUES ($fuel_type_id, $quantity, NOW(), $user_id)";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Stock added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add stock: ' . $conn->error]);
        }
    }
}

function editStock() {
    global $conn;
    global $user_id;
    
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $quantity = floatval($_POST['quantity'] ?? 0);
    
    // Update stock record
    $sql = "UPDATE fuel_stock SET quantity = $quantity, updated_at = NOW(), updated_by = $user_id WHERE fuel_type_id = $fuel_type_id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update stock: ' . $conn->error]);
    }
}

function updateFuelStock($fuel_type_id, $quantity, $transaction_type) {
    global $conn;
    
    $fuel_type_id = intval($fuel_type_id);
    $quantity = floatval($quantity);
    
    // Get current stock for this fuel type
    $current_stock_sql = "SELECT quantity FROM fuel_stock WHERE fuel_type_id = $fuel_type_id";
    $result = $conn->query($current_stock_sql);
    $current_stock = $result ? $result->fetch_assoc()['quantity'] ?? 0 : 0;
    
    // Calculate new stock level
    if ($transaction_type === 'IN') {
        $new_stock = $current_stock + $quantity;
    } else {
        $new_stock = $current_stock - $quantity;
    }
    
    // Update stock
    $sql = "UPDATE fuel_stock SET quantity = $new_stock, updated_at = NOW() WHERE fuel_type_id = $fuel_type_id";
    
    return $conn->query($sql);
}

// Helper function to determine tank number based on fuel type and location
function determineTankNumber($fuel_type, $storage_location = '') {
    global $conn;
    
    // Escape the fuel type
    $fuel_type_escaped = $conn->real_escape_string($fuel_type);
    
    // Try to find an existing tank for this fuel type
    $sql = "SELECT tank_number FROM fuel_inventory WHERE fuel_type = '$fuel_type_escaped' AND status = 'active' ORDER BY current_level DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['tank_number'];
    }
    
    // If no tank exists, return a default tank number based on fuel type
    switch ($fuel_type) {
        case 'diesel':
            return 'TANK-D001';
        case 'gasoline':
            return 'TANK-G001';
        case 'premium':
            return 'TANK-P001';
        default:
            return 'TANK-DEFAULT';
    }
}

function deleteFuelIn() {
    global $conn;
    
    $transaction_id = intval($_POST['id'] ?? 0);
    
    // Get transaction details before deletion
    $sql = "SELECT fuel_type, quantity, tank_number FROM fuel_transactions WHERE id = $transaction_id AND transaction_type = 'IN'";
    $result = $conn->query($sql);
    $transaction = $result ? $result->fetch_assoc() : null;
    
    if ($transaction) {
        // Delete transaction (trigger will automatically reverse the inventory update)
        $delete_sql = "DELETE FROM fuel_transactions WHERE id = $transaction_id";
        
        if ($conn->query($delete_sql)) {
            echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete transaction: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    }
}

function deleteFuelOut() {
    global $conn;
    
    $transaction_id = intval($_POST['id'] ?? 0);
    
    // Get transaction details before deletion
    $sql = "SELECT fuel_type, quantity, tank_number FROM fuel_transactions WHERE id = $transaction_id AND transaction_type = 'OUT'";
    $result = $conn->query($sql);
    $transaction = $result ? $result->fetch_assoc() : null;
    
    if ($transaction) {
        // Delete transaction (trigger will automatically reverse the inventory update)
        $delete_sql = "DELETE FROM fuel_transactions WHERE id = $transaction_id";
        
        if ($conn->query($delete_sql)) {
            echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete transaction: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    }
}
?>
