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

require_once 'includes/check_permissions.php';

try {
    switch ($action) {
        case 'edit':
            adminRequirePermission('software.update', 'can_update', 'software.php');
            $id = intval($_POST['id']);
            $software_name = $_POST['software_name'];
            $category = $_POST['category'];
            $description = $_POST['description'] ?? '';
            $vendor = $_POST['vendor'];
            $version = $_POST['version'] ?? '';
            $license_type = $_POST['license_type'];
            $license_key = $_POST['license_key'] ?? '';
            $purchase_date = $_POST['purchase_date'];
            $purchase_cost = floatval($_POST['purchase_cost']);
            $renewal_date = $_POST['renewal_date'] ?? '';
            $renewal_cost = floatval($_POST['renewal_cost'] ?? 0);
            $status = $_POST['status'];
            $assigned_to = $_POST['assigned_to'] ?? '';
            $installation_date = $_POST['installation_date'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            // Handle existing files
            $existing_files = [];
            if (isset($_POST['existing_files'])) {
                $existing_files = json_decode($_POST['existing_files'], true);
                if (!is_array($existing_files)) {
                    $existing_files = ['license_doc' => '', 'installation_files' => []];
                }
            }
            
            // Handle new license document upload
            if (isset($_FILES['license_document']) && $_FILES['license_document']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/software/licenses/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = uniqid() . '_' . $_FILES['license_document']['name'];
                if (move_uploaded_file($_FILES['license_document']['tmp_name'], $upload_dir . $filename)) {
                    $existing_files['license_doc'] = $filename;
                }
            }
            
            // Handle new installation file uploads
            if (isset($_FILES['installation_files'])) {
                $upload_dir = '../uploads/software/installations/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['installation_files']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['installation_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $filename = uniqid() . '_' . $_FILES['installation_files']['name'][$key];
                        if (move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                            $existing_files['installation_files'][] = $filename;
                        }
                    }
                }
            }
            
            $files_json = json_encode($existing_files);
            
            $sql = "UPDATE software SET software_name = ?, category = ?, description = ?, vendor = ?, version = ?, license_type = ?, license_key = ?, purchase_date = ?, purchase_cost = ?, renewal_date = ?, renewal_cost = ?, status = ?, assigned_to = ?, installation_date = ?, notes = ?, files = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssdssdsssssi', $software_name, $category, $description, $vendor, $version, $license_type, $license_key, $purchase_date, $purchase_cost, $renewal_date, $renewal_cost, $status, $assigned_to, $installation_date, $notes, $files_json, $_SESSION['user_id'], $id);
            
            if ($stmt->execute()) {
                logSystemAction($_SESSION['user_id'], 'software_updated', 'software', "Updated software: $software_name (ID: $id)");
                echo json_encode(['success' => true, 'message' => 'Software updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update software']);
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
    logSystemAction($_SESSION['user_id'], 'software_error', 'software', "Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>
