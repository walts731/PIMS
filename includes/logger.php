<?php
// Logger functions for PIMS

function logSystemAction($user_id, $action, $module, $description) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    $sql = "INSERT INTO system_logs (user_id, action, module, description, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $action, $module, $description);
        return $stmt->execute();
    } catch (Exception $e) {
        // If system_logs table doesn't exist, just log to file
        error_log("System Log: User $user_id - $action - $module - $description");
        return true;
    }
}

function logError($message, $context = []) {
    $log_message = date('Y-m-d H:i:s') . " - ERROR: " . $message;
    if (!empty($context)) {
        $log_message .= " - Context: " . json_encode($context);
    }
    error_log($log_message);
}

function logInfo($message, $context = []) {
    $log_message = date('Y-m-d H:i:s') . " - INFO: " . $message;
    if (!empty($context)) {
        $log_message .= " - Context: " . json_encode($context);
    }
    error_log($log_message);
}
?>
