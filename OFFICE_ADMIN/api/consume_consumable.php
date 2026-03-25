<?php
session_start();

// Try multiple path resolutions for config file
$configPaths = [
    __DIR__ . '/../../config.php',                            // api → OFFICE_ADMIN → root
    dirname(__DIR__, 2) . '/config.php',                       // Go up two levels using dirname
    realpath(__DIR__ . '/../../config.php'),                  // Absolute path to root config
    __DIR__ . '/../../../config.php',                         // api → OFFICE_ADMIN → root → includes (if exists)
];

$configLoaded = false;
foreach ($configPaths as $index => $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit();
}

// Load notification functions
require_once __DIR__ . '/../includes/notification_functions.php';

// Check if user is logged in and has office admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['office_id']) || $_SESSION['role'] !== 'office_admin') {
    error_log("DEBUG: Consumable API - Authorization failed. User ID: " . ($_SESSION['user_id'] ?? 'NULL') . ", Role: " . ($_SESSION['role'] ?? 'NULL'));
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$consumable_id = $_POST['consumable_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$purpose = $_POST['purpose'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$consumable_id || !$quantity || $quantity <= 0 || !$purpose) {
    echo json_encode(['success' => false, 'message' => 'Invalid input - all fields including purpose are required']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get current consumable details with additional info
    $stmt = $conn->prepare("SELECT c.*, o.office_name FROM consumables c LEFT JOIN offices o ON c.office_id = o.id WHERE c.id = ? AND c.office_id = ?");
    $stmt->bind_param("ii", $consumable_id, $_SESSION['office_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Consumable not found');
    }
    
    $consumable = $result->fetch_assoc();
    
    if ($consumable['quantity'] < $quantity) {
        throw new Exception('Insufficient quantity available. Current quantity: ' . $consumable['quantity']);
    }
    
    // Update consumable quantity
    $new_quantity = $consumable['quantity'] - $quantity;
    
    $stmt = $conn->prepare("UPDATE consumables SET quantity = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $new_quantity, $consumable_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update consumable quantity');
    }
    
    // Log consumption
    $log_details = "Consumed {$quantity} units of {$consumable['description']}. Remaining: {$new_quantity}. Notes: {$notes}";
    
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, module, description, timestamp) VALUES (?, 'consumable_consumed', 'consumables', ?, NOW())");
    $stmt->bind_param("is", $_SESSION['user_id'], $log_details);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to log consumption');
    }
    
    // Insert consumption history record
    $user_id = $_SESSION['user_id'];
    $user_email = $_SESSION['email'] ?? '';
    
    // Get user details from database
    $user_stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_full_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
    
    $history_stmt = $conn->prepare("INSERT INTO consume_history (consumable_id, consumable_description, quantity_consumed, remaining_quantity, user_id, user_name, user_email, office_id, office_name, purpose, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    error_log("DEBUG: Consumable API - Insert history query prepared");
    
    $history_stmt->bind_param("isiiississs", 
        $consumable_id, 
        $consumable['description'], 
        $quantity, 
        $new_quantity, 
        $user_id, 
        $user_full_name, 
        $user_email, 
        $_SESSION['office_id'], 
        $consumable['office_name'], 
        $purpose, 
        $notes
    );
    
    if (!$history_stmt->execute()) {
        throw new Exception('Failed to record consumption history');
    }
    
    // Commit transaction
    $conn->commit();
    
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
    $conn->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
