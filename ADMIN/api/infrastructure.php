<?php
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            $id = intval($_GET['id']);
            
            $sql = "SELECT * FROM infrastructure WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Parse additional images if they exist
                $additional_images = [];
                if (!empty($row['additional_images'])) {
                    $additional_images = json_decode($row['additional_images'], true);
                    if (!is_array($additional_images)) {
                        $additional_images = [];
                    }
                }
                $row['additional_images'] = $additional_images;
                
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Infrastructure item not found']);
            }
            $stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    logSystemAction($_SESSION['user_id'], 'infrastructure_api_error', 'infrastructure', "API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>
