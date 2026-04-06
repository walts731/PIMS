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
                
                // Check if this is an AJAX request
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    echo json_encode(['success' => true, 'message' => 'Infrastructure item updated successfully', 'redirect' => 'infrastructure.php']);
                } else {
                    // Regular form submission - redirect directly
                    $_SESSION['success_message'] = 'Infrastructure item updated successfully';
                    header('Location: infrastructure.php');
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update infrastructure item']);
            }
            $stmt->close();
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid infrastructure ID.']);
                break;
            }
            
            // Get infrastructure data for logging and image cleanup
            $stmt = $conn->prepare("SELECT item_description, additional_images FROM infrastructure WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $infrastructure_data = $result->fetch_assoc();
            $stmt->close();
            
            if (!$infrastructure_data) {
                echo json_encode(['success' => false, 'message' => 'Infrastructure item not found.']);
                break;
            }
            
            // Delete associated image files
            if (!empty($infrastructure_data['additional_images'])) {
                $images = json_decode($infrastructure_data['additional_images'], true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        $file_path = '../uploads/infrastructure/' . $image;
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                }
            }
            
            // Delete the record
            $stmt = $conn->prepare("DELETE FROM infrastructure WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                logSystemAction($_SESSION['user_id'], 'infrastructure_deleted', 'infrastructure', "Deleted infrastructure: " . $infrastructure_data['item_description']);
                echo json_encode(['success' => true, 'message' => 'Infrastructure item deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete infrastructure item: ' . $stmt->error]);
            }
            $stmt->close();
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
