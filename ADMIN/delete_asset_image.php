<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get POST data
$item_id = intval($_POST['item_id'] ?? 0);
$image_filename = $_POST['image_filename'] ?? '';

if (empty($item_id) || empty($image_filename)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get current images for this asset item
    $get_images_sql = "SELECT image FROM asset_items WHERE id = ?";
    $get_images_stmt = $conn->prepare($get_images_sql);
    $get_images_stmt->bind_param("i", $item_id);
    $get_images_stmt->execute();
    $images_result = $get_images_stmt->get_result();
    
    if ($images_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Asset item not found']);
        exit();
    }
    
    $item_row = $images_result->fetch_assoc();
    $current_images = [];
    
    if (!empty($item_row['image']) && $item_row['image'] !== 'NULL') {
        $decoded_images = json_decode($item_row['image'], true);
        if (is_array($decoded_images)) {
            $current_images = $decoded_images;
        } elseif (!empty($item_row['image'])) {
            // Handle case where it's a single filename (not JSON)
            $current_images = [$item_row['image']];
        }
    }
    
    // Remove the specified image from the array
    $updated_images = array_filter($current_images, function($img) use ($image_filename) {
        return $img !== $image_filename;
    });
    
    // Re-index array
    $updated_images = array_values($updated_images);
    
    // Update database with new image list
    $new_image_json = !empty($updated_images) ? json_encode($updated_images) : NULL;
    $update_sql = "UPDATE asset_items SET image = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_image_json, $item_id);
    $update_result = $update_stmt->execute();
    
    if (!$update_result) {
        throw new Exception('Failed to update asset item');
    }
    
    // Delete the physical file
    $file_path = '../uploads/asset_images/' . $image_filename;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Log the action
    logSystemAction($_SESSION['user_id'], 'Asset Image Deleted', 'assets', "Item ID: {$item_id}, Image: {$image_filename}");
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Image deleted successfully',
        'remaining_images' => count($updated_images)
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode(['success' => false, 'error' => 'Error deleting image: ' . $e->getMessage()]);
}

$get_images_stmt->close();
if (isset($update_stmt)) {
    $update_stmt->close();
}
?>
