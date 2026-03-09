<?php
session_start();

// Debug: Check session and working directory
error_log("DEBUG: Current working directory: " . __DIR__);
error_log("DEBUG: Session data at start: " . json_encode($_SESSION));

// Try multiple path resolutions for config file
$configPaths = [
    __DIR__ . '/../../config.php',                            // api → OFFICE_ADMIN → root
    dirname(__DIR__, 2) . '/config.php',                      // Go up two levels using dirname
    realpath(__DIR__ . '/../../config.php'),                  // Absolute path to root config
    __DIR__ . '/../../../includes/config.php',                // api → OFFICE_ADMIN → root → includes (if exists)
];

$configLoaded = false;
foreach ($configPaths as $index => $configPath) {
    error_log("DEBUG: Trying path $index: $configPath");
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        error_log("DEBUG: Config loaded from: $configPath");
        error_log("DEBUG: Database connection test: " . (isset($GLOBALS['conn']) ? 'SUCCESS' : 'FAILED'));
        break;
    } else {
        error_log("DEBUG: Path does not exist: $configPath");
    }
}

if (!$configLoaded) {
    error_log("ERROR: Could not load config file from any path");
    error_log("DEBUG: Session data at error: " . json_encode($_SESSION));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit();
}

// Load notification functions
require_once __DIR__ . '/../includes/notification_functions.php';

// Debug: Log that we're proceeding with the script
error_log("DEBUG: Proceeding with consumable API logic");

// Debug: Log all incoming data
error_log("DEBUG: Consumable API - Session data: " . json_encode($_SESSION));
error_log("DEBUG: Consumable API - POST data: " . json_encode($_POST));
error_log("DEBUG: Consumable API - Request method: " . $_SERVER['REQUEST_METHOD']);

// Check if user is logged in and has office admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['office_id']) || $_SESSION['role'] !== 'office_admin') {
    error_log("DEBUG: Consumable API - Authorization failed. User ID: " . ($_SESSION['user_id'] ?? 'NULL') . ", Role: " . ($_SESSION['role'] ?? 'NULL'));
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("DEBUG: Consumable API - Wrong method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$consumable_id = $_POST['consumable_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$notes = $_POST['notes'] ?? '';

error_log("DEBUG: Consumable API - Parsed data - ID: $consumable_id, Quantity: $quantity, Notes: $notes");

if (!$consumable_id || !$quantity || $quantity <= 0) {
    error_log("DEBUG: Consumable API - Input validation failed");
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    // Start transaction
    error_log("DEBUG: Consumable API - Starting transaction");
    $conn->begin_transaction();
    
    // Get current consumable details
    $stmt = $conn->prepare("SELECT quantity, description FROM consumables WHERE id = ? AND office_id = ?");
    error_log("DEBUG: Consumable API - Query: SELECT quantity, description FROM consumables WHERE id = $consumable_id AND office_id = " . $_SESSION['office_id']);
    $stmt->bind_param("ii", $consumable_id, $_SESSION['office_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("DEBUG: Consumable API - Query executed, rows found: " . $result->num_rows);
    
    if ($result->num_rows === 0) {
        error_log("DEBUG: Consumable API - Consumable not found");
        throw new Exception('Consumable not found');
    }
    
    $consumable = $result->fetch_assoc();
    error_log("DEBUG: Consumable API - Consumable data: " . json_encode($consumable));
    
    if ($consumable['quantity'] < $quantity) {
        error_log("DEBUG: Consumable API - Insufficient quantity. Available: " . $consumable['quantity'] . ", Requested: $quantity");
        throw new Exception('Insufficient quantity available. Current quantity: ' . $consumable['quantity']);
    }
    
    // Update consumable quantity
    $new_quantity = $consumable['quantity'] - $quantity;
    error_log("DEBUG: Consumable API - Calculated new quantity: $new_quantity");
    
    $stmt = $conn->prepare("UPDATE consumables SET quantity = ?, updated_at = NOW() WHERE id = ?");
    error_log("DEBUG: Consumable API - Update query: UPDATE consumables SET quantity = $new_quantity, updated_at = NOW() WHERE id = $consumable_id");
    $stmt->bind_param("ii", $new_quantity, $consumable_id);
    
    if (!$stmt->execute()) {
        error_log("DEBUG: Consumable API - Update failed: " . $stmt->error);
        throw new Exception('Failed to update consumable quantity');
    }
    error_log("DEBUG: Consumable API - Update successful, affected rows: " . $stmt->affected_rows);
    
    // Log consumption (using system logs for now)
    $log_details = "Consumed {$quantity} units of {$consumable['description']}. Remaining: {$new_quantity}. Notes: {$notes}";
    error_log("DEBUG: Consumable API - Log details: $log_details");
    
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, module, description, timestamp) VALUES (?, 'consumable_consumed', 'consumables', ?, NOW())");
    error_log("DEBUG: Consumable API - Insert log query prepared");
    $stmt->bind_param("is", $_SESSION['user_id'], $log_details);
    
    if (!$stmt->execute()) {
        error_log("DEBUG: Consumable API - Log insert failed: " . $stmt->error);
        throw new Exception('Failed to log consumption');
    }
    error_log("DEBUG: Consumable API - Log insert successful, ID: " . $conn->insert_id);
    
    // Commit transaction
    error_log("DEBUG: Consumable API - Committing transaction");
    $conn->commit();
    error_log("DEBUG: Consumable API - Transaction committed successfully");
    
    // Create notification for consumption
    createConsumptionNotification($_SESSION['office_id'], $consumable_id, $consumable['description'], $quantity, $new_quantity);
    
    // Check if this created a low stock situation
    if ($new_quantity <= 0) {
        // Get reorder level to check if we need a low stock alert
        $reorder_stmt = $conn->prepare("SELECT reorder_level FROM consumables WHERE id = ?");
        $reorder_stmt->bind_param("i", $consumable_id);
        $reorder_stmt->execute();
        $reorder_result = $reorder_stmt->get_result();
        $reorder_data = $reorder_result->fetch_assoc();
        
        if ($new_quantity <= $reorder_data['reorder_level']) {
            createLowStockNotification($_SESSION['office_id'], $consumable_id, $consumable['description'], $new_quantity, $reorder_data['reorder_level']);
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Consumable consumed successfully',
        'new_quantity' => $new_quantity,
        'consumed_quantity' => $quantity
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    error_log("DEBUG: Consumable API - Exception caught: " . $e->getMessage());
    $conn->rollback();
    error_log("DEBUG: Consumable API - Transaction rolled back");
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
