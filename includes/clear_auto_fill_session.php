<?php
session_start();

// Clear the auto-fill session flag
if (isset($_SESSION['iirup_auto_fill_completed'])) {
    unset($_SESSION['iirup_auto_fill_completed']);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
