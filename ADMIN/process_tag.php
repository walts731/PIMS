<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: asset_items.php');
    exit();
}

// Get form data
$item_id = intval($_POST['item_id']);
$category_id = intval($_POST['category_id']);
$property_no = trim($_POST['property_no']);
$inventory_tag = trim($_POST['inventory_tag']);
$person_accountable = intval($_POST['person_accountable']);
$end_user = trim($_POST['end_user']);
$date_counted = trim($_POST['date_counted']);
$tag_format_id = intval($_POST['tag_format_id']);
$current_number = intval($_POST['current_number']);

// Handle image upload
$image_filename = '';
if (isset($_FILES['asset_image']) && $_FILES['asset_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['asset_image'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, and GIF files are allowed.';
        header('Location: create_tag.php?id=' . $item_id);
        exit();
    }
    
    if ($file['size'] > $max_size) {
        $_SESSION['error'] = 'File size must be less than 5MB.';
        header('Location: create_tag.php?id=' . $item_id);
        exit();
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $image_filename = 'asset_' . $item_id . '_' . time() . '.' . $extension;
    $upload_path = '../uploads/asset_images/' . $image_filename;
    
    // Create directory if it doesn't exist
    if (!is_dir('../uploads/asset_images/')) {
        mkdir('../uploads/asset_images/', 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $_SESSION['error'] = 'Error uploading image file.';
        header('Location: create_tag.php?id=' . $item_id);
        exit();
    }
}

// Validate required fields
if (empty($item_id) || empty($category_id) || empty($property_no) || empty($inventory_tag) || empty($person_accountable) || empty($end_user) || empty($date_counted)) {
    $_SESSION['error'] = 'Please fill in all required fields';
    header('Location: create_tag.php?id=' . $item_id);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Update asset item with tag information
    $update_sql = "UPDATE asset_items SET 
                   property_no = ?, 
                   inventory_tag = ?, 
                   date_counted = ?,
                   image = ?,
                   employee_id = ?, 
                   status = 'available',
                   last_updated = CURRENT_TIMESTAMP
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssii", $property_no, $inventory_tag, $date_counted, $image_filename, $person_accountable, $item_id);
    $update_stmt->execute();
    
    // Update tag format current number if tag format is used
    if ($tag_format_id > 0) {
        $new_number = $current_number + 1;
        $update_tag_sql = "UPDATE tag_formats SET current_number = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $update_tag_stmt = $conn->prepare($update_tag_sql);
        $update_tag_stmt->bind_param("iii", $new_number, $_SESSION['user_id'], $tag_format_id);
        $update_tag_stmt->execute();
    }
    
    // Get category information for logging
    $category_sql = "SELECT category_name, category_code FROM asset_categories WHERE id = ?";
    $category_stmt = $conn->prepare($category_sql);
    $category_stmt->bind_param("i", $category_id);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();
    $category = $category_result->fetch_assoc();
    
    // Get employee information for logging
    $employee_sql = "SELECT employee_no, firstname, lastname FROM employees WHERE id = ?";
    $employee_stmt = $conn->prepare($employee_sql);
    $employee_stmt->bind_param("i", $person_accountable);
    $employee_stmt->execute();
    $employee_result = $employee_stmt->get_result();
    $employee = $employee_result->fetch_assoc();
    
    // Log the tag creation action
    $image_info = $image_filename ? " (Image: {$image_filename})" : " (No image)";
    $log_details = sprintf(
        "Created tag for item ID %d: Property No: %s, Inventory Tag: %s, Date Counted: %s, Category: %s, Person Accountable: %s (%s), End User: %s%s",
        $item_id,
        $property_no,
        $inventory_tag,
        $date_counted,
        $category ? $category['category_code'] . ' - ' . $category['category_name'] : 'Unknown',
        $employee ? $employee['employee_no'] : 'Unknown',
        $employee ? $employee['lastname'] . ', ' . $employee['firstname'] : 'Unknown',
        $end_user,
        $image_info
    );
    
    // Insert into asset_item_history
    $history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'Tag Created', ?, ?, CURRENT_TIMESTAMP)";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param("isi", $item_id, $log_details, $_SESSION['user_id']);
    $history_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = 'Asset tag created successfully!';
    
    // Redirect back to asset items page
    $redirect_sql = "SELECT asset_id FROM asset_items WHERE id = ?";
    $redirect_stmt = $conn->prepare($redirect_sql);
    $redirect_stmt->bind_param("i", $item_id);
    $redirect_stmt->execute();
    $redirect_result = $redirect_stmt->get_result();
    $redirect_row = $redirect_result->fetch_assoc();
    
    header('Location: asset_items.php?asset_id=' . $redirect_row['asset_id']);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $_SESSION['error'] = 'Error creating tag: ' . $e->getMessage();
    header('Location: create_tag.php?id=' . $item_id);
    exit();
}
?>
