<?php
require_once '../config.php';
require_once '../includes/logger.php';

// Function to create notifications for office events
function createOfficeNotification($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null, $priority = 'medium') {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, priority, related_id, related_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issssis', $user_id, $title, $message, $type, $priority, $related_id, $related_type);
    $stmt->execute();
    
    return $stmt->insert_id;
}

// Function to get office admin user ID
function getOfficeAdminId($office_id) {
    global $conn;
    
    $sql = "SELECT id FROM users WHERE role = 'office_admin' AND office = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }
    
    return null;
}

// Function to create notifications for low stock consumables
function createLowStockNotification($office_id, $consumable_id, $consumable_name, $current_stock, $reorder_level) {
    $office_admin_id = getOfficeAdminId($office_id);
    if (!$office_admin_id) return false;
    
    $title = "Low Stock Alert";
    $message = "Consumable '{$consumable_name}' is running low on stock. Current: {$current_stock}, Reorder at: {$reorder_level}";
    
    $notification_id = createOfficeNotification($office_admin_id, $title, $message, 'warning', $consumable_id, 'consumable', 'high');
    
    // Log the notification creation
    logSystemAction($office_admin_id, 'notification_created', 'consumable', "Low stock notification created for {$consumable_name}");
    
    return $notification_id;
}

// Function to create notifications for new requests
function createNewRequestNotification($office_id, $request_id, $request_type, $requester_name) {
    $office_admin_id = getOfficeAdminId($office_id);
    if (!$office_admin_id) return false;
    
    $title = "New {$request_type} Request";
    $message = "New {$request_type} request received from {$requester_name}";
    
    $notification_id = createOfficeNotification($office_admin_id, $title, $message, 'info', $request_id, 'request', 'medium');
    
    // Log the notification creation
    logSystemAction($office_admin_id, 'notification_created', 'request', "New request notification created for {$request_type}");
    
    return $notification_id;
}

// Function to create notifications for asset maintenance
function createMaintenanceNotification($office_id, $asset_id, $asset_name) {
    $office_admin_id = getOfficeAdminId($office_id);
    if (!$office_admin_id) return false;
    
    $title = "Asset Maintenance Due";
    $message = "Asset '{$asset_name}' is due for maintenance";
    
    $notification_id = createOfficeNotification($office_admin_id, $title, $message, 'warning', $asset_id, 'asset', 'high');
    
    // Log the notification creation
    logSystemAction($office_admin_id, 'notification_created', 'asset', "Maintenance notification created for {$asset_name}");
    
    return $notification_id;
}

// Function to create notifications for borrow requests
function createBorrowRequestNotification($office_id, $borrow_request_id, $requester_office, $asset_name) {
    $office_admin_id = getOfficeAdminId($office_id);
    if (!$office_admin_id) return false;
    
    $title = "New Borrow Request";
    $message = "Borrow request received from {$requester_office} for '{$asset_name}'";
    
    $notification_id = createOfficeNotification($office_admin_id, $title, $message, 'info', $borrow_request_id, 'request', 'medium');
    
    // Log the notification creation
    logSystemAction($office_admin_id, 'notification_created', 'borrow_request', "Borrow request notification created for {$asset_name}");
    
    return $notification_id;
}

// Function to create notifications for consumable consumption
function createConsumptionNotification($office_id, $consumable_id, $consumable_name, $quantity_consumed, $remaining_stock) {
    $office_admin_id = getOfficeAdminId($office_id);
    if (!$office_admin_id) return false;
    
    $title = "Consumable Used";
    $message = "{$quantity_consumed} units of '{$consumable_name}' consumed. Remaining stock: {$remaining_stock}";
    
    $notification_id = createOfficeNotification($office_admin_id, $title, $message, 'info', $consumable_id, 'consumable', 'low');
    
    // Log the notification creation
    logSystemAction($office_admin_id, 'notification_created', 'consumable', "Consumption notification created for {$consumable_name}");
    
    return $notification_id;
}

// Function to create notifications for asset status changes
function createAssetStatusNotification($office_id, $asset_id, $asset_name, $old_status, $new_status) {
    $office_admin_id = getOfficeAdminId($office_id);
    if (!$office_admin_id) return false;
    
    $title = "Asset Status Changed";
    $message = "Asset '{$asset_name}' status changed from '{$old_status}' to '{$new_status}'";
    
    $type = 'info';
    $priority = 'medium';
    
    if ($new_status === 'maintenance' || $new_status === 'unserviceable') {
        $type = 'warning';
        $priority = 'high';
    } elseif ($new_status === 'disposed') {
        $type = 'error';
        $priority = 'critical';
    }
    
    $notification_id = createOfficeNotification($office_admin_id, $title, $message, $type, $asset_id, 'asset', $priority);
    
    // Log the notification creation
    logSystemAction($office_admin_id, 'notification_created', 'asset', "Status change notification created for {$asset_name}");
    
    return $notification_id;
}

// Function to check and create low stock notifications for all consumables in an office
function checkLowStockNotifications($office_id) {
    global $conn;
    
    $sql = "SELECT id, description, quantity, reorder_level 
            FROM consumables 
            WHERE office_id = ? AND quantity <= reorder_level";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications_created = 0;
    while ($row = $result->fetch_assoc()) {
        // Check if we already created a notification for this low stock item recently (within last 24 hours)
        $check_sql = "SELECT id FROM notifications 
                     WHERE user_id = (SELECT id FROM users WHERE role = 'office_admin' AND office = ? LIMIT 1)
                     AND related_id = ? AND related_type = 'consumable' 
                     AND type = 'warning' 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('ii', $office_id, $row['id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Create new low stock notification
            if (createLowStockNotification($office_id, $row['id'], $row['description'], $row['quantity'], $row['reorder_level'])) {
                $notifications_created++;
            }
        }
    }
    
    return $notifications_created;
}

// Function to create system notifications for office admin
function createOfficeSystemNotification($office_id, $title, $message, $type = 'system', $priority = 'medium') {
    $office_admin_id = getOfficeAdminId($office_id);
    if (!$office_admin_id) return false;
    
    $notification_id = createOfficeNotification($office_admin_id, $title, $message, $type, null, null, $priority);
    
    // Log the notification creation
    logSystemAction($office_admin_id, 'notification_created', 'system', "System notification created: {$title}");
    
    return $notification_id;
}
?>
