<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
    exit();
}

// Get transaction details
$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$transaction_type = isset($_GET['type']) ? trim($_GET['type']) : '';

if ($transaction_id <= 0 || empty($transaction_type)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid transaction parameters']);
    exit();
}

$transaction_details = [];

try {
    if ($transaction_type === 'addition') {
        // Get addition details from consumables table
        $sql = "SELECT c.*, 
                       o.office_name as office_name,
                       fo.office_name as from_office_name,
                       to_off.office_name as to_office_name,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       c.created_at as transaction_date
                FROM consumables c
                LEFT JOIN offices o ON c.office_id = o.id
                LEFT JOIN offices fo ON c.office_id = fo.id
                LEFT JOIN offices to_off ON c.for_office_id = to_off.id
                LEFT JOIN users u ON 1=0 -- No user for additions, but we need column
                WHERE c.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $transaction_details = $result->fetch_assoc();
            $transaction_details['created_by_name'] = 'System';
            $transaction_details['transaction_type'] = 'addition';
            $transaction_details['transaction_date_formatted'] = date('M j, Y H:i', strtotime($transaction_details['transaction_date']));
        }
        
    } elseif ($transaction_type === 'release') {
        // Get release details from consumable_release_history table
        $sql = "SELECT h.*, 
                       c.units,
                       fo.office_name as from_office_name,
                       to_off.office_name as to_office_name,
                       CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                       CONCAT(u2.first_name, ' ', u2.last_name) as received_by_name,
                       h.release_date as transaction_date
                FROM consumable_release_history h
                LEFT JOIN consumables c ON h.consumable_id = c.id
                LEFT JOIN offices fo ON h.from_office_id = fo.id
                LEFT JOIN offices to_off ON h.to_office_id = to_off.id
                LEFT JOIN users u ON h.released_by = u.id
                LEFT JOIN users u2 ON h.received_by = u2.id
                WHERE h.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $transaction_details = $result->fetch_assoc();
            $transaction_details['transaction_type'] = 'release';
            $transaction_details['transaction_date_formatted'] = date('M j, Y H:i', strtotime($transaction_details['transaction_date']));
        }
        
    } elseif ($transaction_type === 'lend') {
        // Get lend details from lend_consumables table
        $sql = "SELECT l.*, 
                       c.units,
                       fo.office_name as from_office_name,
                       to_off.office_name as to_office_name,
                       CONCAT(u.first_name, ' ', u.last_name) as lent_by_name,
                       CONCAT(u2.first_name, ' ', u2.last_name) as received_by_name,
                       l.date_lent as transaction_date
                FROM lend_consumables l
                LEFT JOIN consumables c ON l.consumable_id = c.id
                LEFT JOIN offices fo ON l.from_office_id = fo.id
                LEFT JOIN offices to_off ON l.to_office_id = to_off.id
                LEFT JOIN users u ON l.lent_by = u.id
                LEFT JOIN users u2 ON l.received_by = u2.id
                WHERE l.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $transaction_details = $result->fetch_assoc();
            $transaction_details['transaction_type'] = 'lend';
            $transaction_details['transaction_date_formatted'] = date('M j, Y H:i', strtotime($transaction_details['transaction_date']));
            
            // Format dates
            if (!empty($transaction_details['expected_return_date'])) {
                $transaction_details['expected_return_date_formatted'] = date('M j, Y', strtotime($transaction_details['expected_return_date']));
            }
            if (!empty($transaction_details['actual_return_date'])) {
                $transaction_details['actual_return_date_formatted'] = date('M j, Y', strtotime($transaction_details['actual_return_date']));
            }
        }
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching transaction details: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

if (empty($transaction_details)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Transaction not found']);
    exit();
}

// Return success response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $transaction_details]);
?>
