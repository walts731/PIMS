<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'edit':
            $id = intval($_POST['id']);
            $classification = $_POST['classification'];
            $item_description = $_POST['item_description'];
            $nature_occupancy = $_POST['nature_occupancy'] ?? '';
            $location = $_POST['location'];
            $date_constructed = $_POST['date_constructed'];
            $property_no = $_POST['property_no'] ?? '';
            $acquisition_cost = floatval($_POST['acquisition_cost']);
            $market_value = floatval($_POST['market_value'] ?? 0);
            $date_appraisal = $_POST['date_appraisal'] ?? '';
            $remarks = $_POST['remarks'] ?? '';
            
            // Handle existing images
            $existing_images = [];
            if (isset($_POST['existing_images'])) {
                $existing_images = json_decode($_POST['existing_images'], true);
                if (!is_array($existing_images)) {
                    $existing_images = [];
                }
            }
            
            // Handle new image uploads
            if (isset($_FILES['additional_images'])) {
                $upload_dir = '../uploads/infrastructure/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                        if (count($existing_images) < 4) {
                            $filename = uniqid() . '_' . $_FILES['additional_images']['name'][$key];
                            if (move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                                $existing_images[] = $filename;
                            }
                        }
                    }
                }
            }
            
            $images_json = json_encode($existing_images);
            
            $sql = "UPDATE infrastructure SET classification = ?, item_description = ?, nature_occupancy = ?, location = ?, date_constructed = ?, property_no = ?, acquisition_cost = ?, market_value = ?, date_appraisal = ?, remarks = ?, additional_images = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssddsssii', $classification, $item_description, $nature_occupancy, $location, $date_constructed, $property_no, $acquisition_cost, $market_value, $date_appraisal, $remarks, $images_json, $_SESSION['user_id'], $id);
            
            if ($stmt->execute()) {
                logSystemAction($_SESSION['user_id'], 'infrastructure_updated', 'infrastructure', "Updated infrastructure: $item_description (ID: $id)");
                echo json_encode(['success' => true, 'message' => 'Infrastructure item updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update infrastructure item']);
            }
            $stmt->close();
            break;
            
        case 'delete':
            // This case is disabled as per user request
            echo json_encode(['success' => false, 'message' => 'Delete operation is not allowed']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    logSystemAction($_SESSION['user_id'], 'infrastructure_error', 'infrastructure', "Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>
