<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if (!in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

try {
    // Get JSON input
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if ($data['action'] === 'fix_borrowed_status') {
        // Update all assets with approved borrow requests to have status='borrowed'
        $update_sql = "
            UPDATE asset_items ai 
            JOIN borrow_requests br ON br.asset_id = ai.id 
            SET ai.status = 'borrowed' 
            WHERE br.status = 'approved' AND ai.status != 'borrowed'
        ";
        
        $result = $conn->query($update_sql);
        
        if ($result) {
            $affected_rows = $conn->affected_rows;
            
            if ($affected_rows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Successfully updated {$affected_rows} assets to 'borrowed' status"
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => "No assets needed updating (all borrowed assets already have correct status)"
                ]);
            }
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $conn->error
            ]);
        }
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
