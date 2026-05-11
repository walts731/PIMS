<?php
session_start();
require_once 'c:/xampp/htdocs/PIMS/config.php';

// Simulate process_itr.php
$_SESSION['user_id'] = 5;
$itr_no = 'ITR-' . time();
$_SESSION['success_message'] = "ITR form saved successfully! ITR Number: $itr_no";

// Simulate itr_form.php including topbar.php
$success_msg = !empty($_SESSION['success']) ? $_SESSION['success'] : (!empty($_SESSION['success_message']) ? $_SESSION['success_message'] : '');

echo "Success msg: " . $success_msg . "\n";
if (!empty($success_msg) && (!isset($_SESSION['_notified_success']) || $_SESSION['_notified_success'] !== $success_msg)) {
    echo "Condition met. Inserting notification...\n";
    $notif_title = 'Success';
    $notif_type = 'success';
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('isss', $_SESSION['user_id'], $notif_title, $success_msg, $notif_type);
        $stmt->execute();
        $stmt->close();
        echo "Inserted successfully.\n";
    } else {
        echo "Prepare failed: " . $conn->error . "\n";
    }
    $_SESSION['_notified_success'] = $success_msg;
} else {
    echo "Condition NOT met. Notified success is: " . ($_SESSION['_notified_success'] ?? 'null') . "\n";
}
?>
