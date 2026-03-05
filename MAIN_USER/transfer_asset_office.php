<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Access denied. Please log in.");
}

if (!in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    die("Access denied. Admin role required.");
}

header('Content-Type: application/json');

try {
    // Get JSON input
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if ($data['action'] === 'transfer_asset') {
        $property_no = $data['property_no'];
        $from_office = $data['from_office'];
        $to_office = $data['to_office'];
        
        // Get the office ID for the target office
        $office_query = "SELECT id FROM offices WHERE office_name = ?";
        $office_stmt = $conn->prepare($office_query);
        $office_stmt->bind_param('s', $to_office);
        $office_stmt->execute();
        $office_result = $office_stmt->get_result();
        
        if ($office_row = $office_result->fetch_assoc()) {
            $to_office_id = $office_row['id'];
            
            // Update the asset item's office
            $update_sql = "UPDATE asset_items SET office_id = ? WHERE property_no = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('is', $to_office_id, $property_no);
            
            if ($update_stmt->execute()) {
                $affected_rows = $update_stmt->affected_rows;
                
                if ($affected_rows > 0) {
                    echo json_encode([
                        'success' => true, 
                        'message' => "Asset {$property_no} successfully transferred from {$from_office} to {$to_office}"
                    ]);
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Asset not found or no changes needed'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Database error: ' . $conn->error
                ]);
            }
            $update_stmt->close();
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Target office not found: ' . $to_office
            ]);
        }
        $office_stmt->close();
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid action'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
