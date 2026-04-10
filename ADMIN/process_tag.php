<?php
session_start();
require_once '../config.php';
require_once '../includes/qr_generator.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

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
$subcategory_id = intval($_POST['subcategory_id'] ?? 0);
$office_name = trim($_POST['office_name'] ?? '');
$property_no = trim($_POST['property_no']);
$ics_par_no = trim($_POST['ics_par_no'] ?? '');
$inventory_tag = trim($_POST['inventory_tag'] ?? '');
$person_accountable = intval($_POST['person_accountable']);
$end_user = trim($_POST['end_user'] ?? '');
$date_counted = trim($_POST['date_counted']);
$tag_format_id = intval($_POST['tag_format_id']);
$current_number = intval($_POST['current_number']);

// Collect model and serial number from form (available for all asset types)
$model = trim($_POST['model'] ?? '');
$serial_number = trim($_POST['serial_number'] ?? '');

// Debug: Log the form data we received
logSystemAction($_SESSION['user_id'], 'Tag Form Data Received', 'forms', "Item ID: {$item_id}, End User: '{$end_user}', Person Accountable: {$person_accountable}");

// Immediate check: If end_user is empty, log all POST data for debugging
if (empty($end_user)) {
    logSystemAction($_SESSION['user_id'], 'Tag Form - Empty End User', 'forms', "POST data: " . json_encode($_POST));
    error_log("Empty end_user in process_tag.php. POST data: " . print_r($_POST, true));
}

// Check if we should increment the property number counter
if (isset($_POST['increment_property_counter']) && $_POST['increment_property_counter'] == '1') {
    // Generate the actual property number (this increments the counter)
    $generated_property_no = generateNextTag('property_no');
    if ($generated_property_no !== null) {
        $property_no = $generated_property_no;
        logSystemAction($_SESSION['user_id'], 'Property number counter incremented', 'forms', "Generated property number: $property_no");
    }
}

// Handle multiple image uploads - append to existing images
$image_filenames = [];
$existing_images = [];

// First, get existing images for this asset item
$get_existing_images_sql = "SELECT image FROM asset_items WHERE id = ?";
$get_existing_images_stmt = $conn->prepare($get_existing_images_sql);
$get_existing_images_stmt->bind_param("i", $item_id);
$get_existing_images_stmt->execute();
$existing_images_result = $get_existing_images_stmt->get_result();
if ($existing_row = $existing_images_result->fetch_assoc()) {
    $existing_image_data = $existing_row['image'];
    if (!empty($existing_image_data) && $existing_image_data !== 'NULL') {
        $decoded_images = json_decode($existing_image_data, true);
        if (is_array($decoded_images)) {
            $existing_images = $decoded_images;
        } elseif (!empty($existing_image_data)) {
            // Handle case where it's a single filename (not JSON)
            $existing_images = [$existing_image_data];
        }
    }
}
$get_existing_images_stmt->close();

if (isset($_FILES['asset_images']) && is_array($_FILES['asset_images']['name'])) {
    $files = $_FILES['asset_images'];
    
    // Validate and process each file
    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $name,
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            ];
            
            // Validate file
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
            $max_size = 5 * 1024 * 1024; // 5MB per file
            
            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['error'] = 'Invalid file type: ' . $file['name'] . '. Only JPG, PNG, GIF, WebP, and AVIF files are allowed.';
                header('Location: create_tag.php?id=' . $item_id);
                exit();
            }
            
            if ($file['size'] > $max_size) {
                $_SESSION['error'] = 'File ' . $file['name'] . ' size must be less than 5MB.';
                header('Location: create_tag.php?id=' . $item_id);
                exit();
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $image_filename = 'asset_' . $item_id . '_' . $key . '_' . time() . '.' . $extension;
            $upload_path = '../uploads/asset_images/' . $image_filename;
            
            // Create directory if it doesn't exist
            if (!is_dir('../uploads/asset_images/')) {
                mkdir('../uploads/asset_images/', 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_filenames[] = $image_filename;
            } else {
                $_SESSION['error'] = 'Error uploading image file: ' . $file['name'];
                header('Location: create_tag.php?id=' . $item_id);
                exit();
            }
        }
    }
}

// Merge existing images with new ones
$all_images = array_merge($existing_images, $image_filenames);

// Remove duplicates and reindex array
$all_images = array_values(array_unique($all_images));

// If no images at all, keep empty array
if (empty($all_images)) {
    $all_images = [];
}

// Validate required fields
if (empty($item_id) || empty($category_id) || empty($office_name) || empty($property_no) || empty($date_counted)) {
    $_SESSION['error'] = 'Please fill in all required fields';
    header('Location: create_tag.php?id=' . $item_id);
    exit();
}

// Additional validation for Computer Equipment category - subcategory is required
$category_check_sql = "SELECT category_code FROM asset_categories WHERE id = ?";
$category_check_stmt = $conn->prepare($category_check_sql);
$category_check_stmt->bind_param("i", $category_id);
$category_check_stmt->execute();
$category_check_result = $category_check_stmt->get_result();
$category_check = $category_check_result->fetch_assoc();

// Debug: Log subcategory validation
logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Subcategory validation - Category: " . ($category_check['category_code'] ?? 'NULL') . ", Subcategory ID: '$subcategory_id'");

if ($category_check && $category_check['category_code'] === '05-030') {
    // For Computer Equipment, try to auto-detect subcategory if not provided
    if (empty($subcategory_id)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Subcategory ID empty, trying to auto-detect from property number");
        
        // Try to find subcategory by property number pattern
        if (!empty($property_no)) {
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Property number: $property_no");
            
            // Look for COMPUTER DESKTOP subcategory
            $auto_subcat_sql = "SELECT id FROM asset_sub_categories WHERE asset_categories_id = ? AND sub_category_name = 'COMPUTER DESKTOP' LIMIT 1";
            $auto_subcat_stmt = $conn->prepare($auto_subcat_sql);
            $auto_subcat_stmt->bind_param("i", $category_id);
            $auto_subcat_stmt->execute();
            $auto_subcat_result = $auto_subcat_stmt->get_result();
            $auto_subcat = $auto_subcat_result->fetch_assoc();
            
            if ($auto_subcat) {
                $subcategory_id = $auto_subcat['id'];
                logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Auto-detected subcategory ID: $subcategory_id");
            } else {
                logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Could not auto-detect COMPUTER DESKTOP subcategory");
                $_SESSION['error'] = 'Please select a subcategory for Computer Equipment assets';
                header('Location: create_tag.php?id=' . $item_id);
                exit();
            }
        } else {
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "No property number available for auto-detection");
            $_SESSION['error'] = 'Please select a subcategory for Computer Equipment assets';
            header('Location: create_tag.php?id=' . $item_id);
            exit();
        }
    }
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if office_name column exists in asset_items table, add if it doesn't
    $check_column_sql = "SHOW COLUMNS FROM `asset_items` LIKE 'office_name'";
    $column_result = $conn->query($check_column_sql);
    
    if ($column_result->num_rows == 0) {
        // Add office_name column
        $alter_sql = "ALTER TABLE `asset_items` ADD COLUMN `office_name` varchar(255) DEFAULT NULL AFTER `office_id`";
        $conn->query($alter_sql);
        
        // Update existing records to copy office name from offices table
        $update_sql = "UPDATE `asset_items` ai 
                      LEFT JOIN `offices` o ON ai.office_id = o.id 
                      SET ai.office_name = o.office_name 
                      WHERE ai.office_id IS NOT NULL AND ai.office_name IS NULL";
        $conn->query($update_sql);
    }
    
    // Update asset item with tag information using traditional SQL
    $property_no_safe = mysqli_real_escape_string($conn, $property_no);
    $ics_par_no_safe = !empty($ics_par_no) ? "'" . mysqli_real_escape_string($conn, $ics_par_no) . "'" : 'NULL';
    $inventory_tag_safe = !empty($inventory_tag) ? "'" . mysqli_real_escape_string($conn, $inventory_tag) . "'" : 'NULL';
    $date_counted_safe = mysqli_real_escape_string($conn, $date_counted);
    $model_safe = !empty($model) ? "'" . mysqli_real_escape_string($conn, $model) . "'" : 'NULL';
    $serial_number_safe = !empty($serial_number) ? "'" . mysqli_real_escape_string($conn, $serial_number) . "'" : 'NULL';
    // Convert image filenames array to JSON for storage
    $image_filename_json = !empty($all_images) ? json_encode($all_images) : 'NULL';
    $image_filename_safe = mysqli_real_escape_string($conn, $image_filename_json);
    $end_user_safe = mysqli_real_escape_string($conn, $end_user);
    $office_name_safe = mysqli_real_escape_string($conn, $office_name);
    
    $update_sql = "UPDATE asset_items SET 
                   property_no = '$property_no_safe', 
                   ics_par_no = $ics_par_no_safe,
                   inventory_tag = $inventory_tag_safe, 
                   date_counted = '$date_counted_safe',
                   model = $model_safe,
                   serial_number = $serial_number_safe,
                   image = '$image_filename_safe',
                   employee_id = $person_accountable, 
                   asset_category_id = $category_id,
                   asset_subcategory_id = " . ($subcategory_id > 0 ? $subcategory_id : 'NULL') . ",
                   office_name = '$office_name_safe',
                   end_user = '$end_user_safe',
                   status = 'serviceable',
                   last_updated = CURRENT_TIMESTAMP
                   WHERE id = $item_id";
    
    // Debug: Log the SQL and values before execution
    logSystemAction($_SESSION['user_id'], 'Tag Update SQL Debug', 'forms', "SQL: $update_sql");
    logSystemAction($_SESSION['user_id'], 'Tag Update Values Debug', 'forms', "Values: property_no='{$property_no}', ics_par_no='{$ics_par_no}', inventory_tag='{$inventory_tag}', date_counted='{$date_counted}', model='{$model}', serial_number='{$serial_number}', image='{$image_filename}', employee_id={$person_accountable}, asset_category_id={$category_id}, asset_subcategory_id={$subcategory_id}, end_user='{$end_user}' (length: " . strlen($end_user) . "), item_id={$item_id}");
    
    // Execute the traditional SQL
    $update_result = mysqli_query($conn, $update_sql);
    
    if (!$update_result) {
        throw new Exception('Failed to update asset item: ' . mysqli_error($conn));
    }
    
    // Log the update for debugging
    $affected_rows = mysqli_affected_rows($conn);
    if ($affected_rows > 0) {
        logSystemAction($_SESSION['user_id'], 'Asset item updated successfully', 'assets', "Item ID: {$item_id}, End User: {$end_user}, Rows affected: {$affected_rows}");
    } else {
        // Log if no items were updated for debugging
        logSystemAction($_SESSION['user_id'], 'Asset item update - no rows affected', 'assets', "Item ID: {$item_id}, End User: {$end_user}");
    }
    
    // Generate QR code for the asset item
    $qrGenerator = new QRCodeGenerator();
    
    // Get complete asset data for QR code
    $asset_data_sql = "SELECT ai.*, a.description as asset_description, ac.category_name, o.office_name,
                      e.employee_no, e.firstname, e.lastname
                      FROM asset_items ai 
                      LEFT JOIN assets a ON ai.asset_id = a.id 
                      LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
                      LEFT JOIN offices o ON ai.office_id = o.id 
                      LEFT JOIN employees e ON ai.employee_id = e.id 
                      WHERE ai.id = ?";
    $asset_data_stmt = $conn->prepare($asset_data_sql);
    $asset_data_stmt->bind_param("i", $item_id);
    $asset_data_stmt->execute();
    $asset_data_result = $asset_data_stmt->get_result();
    $asset_data = $asset_data_result->fetch_assoc();
    
    if ($asset_data) {
        // Generate QR code
        $qr_filename = $qrGenerator->generateAssetQRCode($asset_data);
        
        if ($qr_filename) {
            // Update asset item with QR code filename
            $update_qr_sql = "UPDATE asset_items SET qr_code = ? WHERE id = ?";
            $update_qr_stmt = $conn->prepare($update_qr_sql);
            $update_qr_stmt->bind_param("si", $qr_filename, $item_id);
            $update_qr_stmt->execute();
            
            // Log QR code generation
            $qr_details = "QR code generated for asset item: $qr_filename";
            $qr_history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'QR Code Generated', ?, ?, CURRENT_TIMESTAMP)";
            $qr_history_stmt = $conn->prepare($qr_history_sql);
            $qr_history_stmt->bind_param("isi", $item_id, $qr_details, $_SESSION['user_id']);
            $qr_history_stmt->execute();
        }
    }
    
    // Also update the assets table with the category_id
    $get_asset_sql = "SELECT asset_id FROM asset_items WHERE id = ?";
    $get_asset_stmt = $conn->prepare($get_asset_sql);
    $get_asset_stmt->bind_param("i", $item_id);
    $get_asset_stmt->execute();
    $asset_result = $get_asset_stmt->get_result();
    $asset_row = $asset_result->fetch_assoc();
    
    if ($asset_row && $asset_row['asset_id']) {
        $asset_id = $asset_row['asset_id'];
        
        // Try to find office_id from offices table using office_name, or set to NULL
        $office_id_for_assets = null;
        if (!empty($office_name)) {
            $find_office_sql = "SELECT id FROM offices WHERE office_name = ? LIMIT 1";
            $find_office_stmt = $conn->prepare($find_office_sql);
            $find_office_stmt->bind_param("s", $office_name);
            $find_office_stmt->execute();
            $office_result = $find_office_stmt->get_result();
            if ($office_row = $office_result->fetch_assoc()) {
                $office_id_for_assets = $office_row['id'];
            }
            $find_office_stmt->close();
        }
        
        // Validate subcategory_id exists before updating assets table
        $valid_subcategory_id = null;
        if ($subcategory_id > 0) {
            $check_subcategory_sql = "SELECT id FROM asset_sub_categories WHERE id = ? LIMIT 1";
            $check_subcategory_stmt = $conn->prepare($check_subcategory_sql);
            $check_subcategory_stmt->bind_param("i", $subcategory_id);
            $check_subcategory_stmt->execute();
            $check_subcategory_result = $check_subcategory_stmt->get_result();
            if ($check_subcategory_result->num_rows > 0) {
                $valid_subcategory_id = $subcategory_id;
            }
            $check_subcategory_stmt->close();
        }
        
        $update_assets_sql = "UPDATE assets SET 
                              asset_categories_id = ?,
                              asset_subcategory_id = ?,
                              office_id = ?,
                              updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
        $update_assets_stmt = $conn->prepare($update_assets_sql);
        $update_assets_stmt->bind_param("iiii", $category_id, $valid_subcategory_id, $office_id_for_assets, $asset_id);
        $update_assets_stmt->execute();
    }
    
    // Update tag format current number if tag format is used
    if ($tag_format_id > 0) {
        $new_number = $current_number + 1;
        $update_tag_sql = "UPDATE tag_formats SET current_number = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $update_tag_stmt = $conn->prepare($update_tag_sql);
        $update_tag_stmt->bind_param("iii", $new_number, $_SESSION['user_id'], $tag_format_id);
        $update_tag_stmt->execute();
    }
    
    // Get category information for specific field handling
    $category_sql = "SELECT category_name, category_code FROM asset_categories WHERE id = ?";
    $category_stmt = $conn->prepare($category_sql);
    $category_stmt->bind_param("i", $category_id);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();
    $category = $category_result->fetch_assoc();
    
    // Get subcategory information for specific field handling
    $subcategory_sql = "SELECT sub_category_name, sub_category_code FROM asset_sub_categories WHERE id = ?";
    $subcategory_stmt = $conn->prepare($subcategory_sql);
    $subcategory_stmt->bind_param("i", $subcategory_id);
    $subcategory_stmt->execute();
    $subcategory_result = $subcategory_stmt->get_result();
    $subcategory = $subcategory_result->fetch_assoc();
    
    // Debug: Log category and subcategory information
    logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Form Data - Category ID: $category_id, Subcategory ID: $subcategory_id");
    logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "POST Data: " . json_encode($_POST));
    logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Category Data - Code: " . ($category['category_code'] ?? 'NULL') . ", Name: " . ($category['category_name'] ?? 'NULL'));

    if ($category && ($category['category_code'] === '05-030' || $category['category_code'] === 'ITS' || $category['category_name'] === 'ITS' || stripos($category['category_name'], 'computer') !== false)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED COMPUTER/ITS BLOCK - Item ID: $item_id, Category Name: " . $category['category_name']);
        // Collect all form data
        $processor = trim($_POST['processor'] ?? '');
        $ram_capacity = trim($_POST['ram'] ?? '');
        $storage_capacity = trim($_POST['storage_capacity'] ?? '');
        $storage_type = trim($_POST['storage_type'] ?? 'ssd');
        $model = trim($_POST['model'] ?? '');
        $graphics_card = trim($_POST['graphics'] ?? '');
        $operating_system = trim($_POST['operating_system'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $warranty = trim($_POST['warranty'] ?? '');
        
        // Escape values for traditional SQL
        $processor_safe = mysqli_real_escape_string($conn, $processor);
        $ram_capacity_safe = mysqli_real_escape_string($conn, $ram_capacity);
        $storage_capacity_safe = mysqli_real_escape_string($conn, $storage_capacity);
        $storage_type_safe = mysqli_real_escape_string($conn, $storage_type);
        $model_safe = mysqli_real_escape_string($conn, $model);
        $graphics_card_safe = mysqli_real_escape_string($conn, $graphics_card);
        $operating_system_safe = mysqli_real_escape_string($conn, $operating_system);
        $serial_number_safe = mysqli_real_escape_string($conn, $serial_number);
        $brand_safe = mysqli_real_escape_string($conn, $brand);
        $warranty_safe = mysqli_real_escape_string($conn, $warranty);
        
        // Insert or update computer equipment-specific information using traditional SQL
        $computer_sql = "INSERT INTO asset_computers 
                       (asset_item_id, processor, ram_capacity, storage_type, storage_capacity, model, graphics_card, operating_system, serial_number, created_by, created_at)
                       VALUES ($item_id, '$processor_safe', '$ram_capacity_safe', '$storage_type_safe', '$storage_capacity_safe', '$model_safe', '$graphics_card_safe', '$operating_system_safe', '$serial_number_safe', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       processor = '$processor_safe',
                       ram_capacity = '$ram_capacity_safe',
                       storage_type = '$storage_type_safe',
                       storage_capacity = '$storage_capacity_safe',
                       model = '$model_safe',
                       graphics_card = '$graphics_card_safe',
                       operating_system = '$operating_system_safe',
                       serial_number = '$serial_number_safe',
                       updated_by = " . $_SESSION['user_id'] . ",
                       updated_at = CURRENT_TIMESTAMP";
        
        // Execute the traditional SQL
        $computer_result = mysqli_query($conn, $computer_sql);
        
        if (!$computer_result) {
            throw new Exception('Failed to save computer equipment details: ' . mysqli_error($conn));
        }
        
        // Log computer equipment-specific field updates
        $computer_details = sprintf(
            "Computer Equipment specs saved - Processor: %s, RAM: %s, Storage: %s %s, Model: %s, Graphics: %s, OS: %s, Serial: %s, Brand: %s, Warranty: %s",
            $processor ?: 'Not specified',
            $ram_capacity ?: 'Not specified',
            $storage_capacity ?: 'Not specified',
            $storage_type ?: 'Not specified',
            $model ?: 'Not specified',
            $graphics_card ?: 'Not specified',
            $operating_system ?: 'Not specified',
            $serial_number ?: 'Not specified',
            $brand ?: 'Not specified',
            $warranty ?: 'Not specified'
        );
        
        $computer_history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES ($item_id, 'Computer Specs Updated', '" . mysqli_real_escape_string($conn, $computer_details) . "', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)";
        mysqli_query($conn, $computer_history_sql);
        
        // Handle Desktop Computers subcategory-specific fields
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Checking desktop computer condition...");
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Subcategory name: " . ($subcategory['sub_category_name'] ?? 'NULL'));
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Condition result: " . ($subcategory && $subcategory['sub_category_name'] === 'COMPUTER DESKTOP' ? 'TRUE' : 'FALSE'));
        
        if ($subcategory && $subcategory['sub_category_name'] === 'COMPUTER DESKTOP') {
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Desktop computer condition MET - processing fields...");
            
            $monitor_name = trim($_POST['monitor_name'] ?? '');
            $monitor_model = trim($_POST['monitor_model'] ?? '');
            $monitor_serial_number = trim($_POST['monitor_serial_number'] ?? '');
            $ups_name = trim($_POST['ups_name'] ?? '');
            $ups_model = trim($_POST['ups_model'] ?? '');
            $ups_serial_number = trim($_POST['ups_serial_number'] ?? '');
            $monitor_status = trim($_POST['monitor_status'] ?? 'serviceable');
            $ups_status = trim($_POST['ups_status'] ?? 'serviceable');
            
            // Ensure status values are valid ENUM values, default to 'serviceable' if empty
            $monitor_status = empty($monitor_status) ? 'serviceable' : $monitor_status;
            $ups_status = empty($ups_status) ? 'serviceable' : $ups_status;
            
            // Validate that status values are allowed ENUM values
            $allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'no_tag', 'disposed'];
            if (!in_array($monitor_status, $allowed_statuses)) {
                $monitor_status = 'serviceable';
            }
            if (!in_array($ups_status, $allowed_statuses)) {
                $ups_status = 'serviceable';
            }
            
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Desktop fields collected - Monitor: '$monitor_name', UPS: '$ups_name', Monitor Status: '$monitor_status', UPS Status: '$ups_status'");
            
            // Convert empty strings to NULL for database
            $monitor_name_db = empty($monitor_name) ? 'NULL' : "'$monitor_name'";
            $monitor_model_db = empty($monitor_model) ? 'NULL' : "'$monitor_model'";
            $monitor_serial_number_db = empty($monitor_serial_number) ? 'NULL' : "'$monitor_serial_number'";
            $ups_name_db = empty($ups_name) ? 'NULL' : "'$ups_name'";
            $ups_model_db = empty($ups_model) ? 'NULL' : "'$ups_model'";
            $ups_serial_number_db = empty($ups_serial_number) ? 'NULL' : "'$ups_serial_number'";
            
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Desktop fields processed for DB - Monitor: $monitor_name_db, UPS: $ups_name_db");
            
            // Escape values for traditional SQL
            $monitor_name_safe = mysqli_real_escape_string($conn, $monitor_name);
            $monitor_model_safe = mysqli_real_escape_string($conn, $monitor_model);
            $monitor_serial_number_safe = mysqli_real_escape_string($conn, $monitor_serial_number);
            $ups_name_safe = mysqli_real_escape_string($conn, $ups_name);
            $ups_model_safe = mysqli_real_escape_string($conn, $ups_model);
            $ups_serial_number_safe = mysqli_real_escape_string($conn, $ups_serial_number);
            
            // Status values are already validated and guaranteed to be safe ENUM values
            $monitor_status_safe = $monitor_status; // No need to escape, already validated
            $ups_status_safe = $ups_status; // No need to escape, already validated
            
            // Insert or update desktop computer-specific information using traditional SQL
            // First check if record exists
            $check_sql = "SELECT id FROM asset_desktop_computers WHERE asset_item_id = $item_id";
            $check_result = mysqli_query($conn, $check_sql);
            
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Checking existing desktop record for item_id: $item_id");
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update existing record
                $desktop_sql = "UPDATE asset_desktop_computers SET 
                               monitor_name = " . (empty($monitor_name) ? 'NULL' : "'$monitor_name_safe'") . ",
                               monitor_model = " . (empty($monitor_model) ? 'NULL' : "'$monitor_model_safe'") . ",
                               monitor_serial_number = " . (empty($monitor_serial_number) ? 'NULL' : "'$monitor_serial_number_safe'") . ",
                               monitor_status = '$monitor_status_safe',
                               ups_name = " . (empty($ups_name) ? 'NULL' : "'$ups_name_safe'") . ",
                               ups_model = " . (empty($ups_model) ? 'NULL' : "'$ups_model_safe'") . ",
                               ups_serial_number = " . (empty($ups_serial_number) ? 'NULL' : "'$ups_serial_number_safe'") . ",
                               ups_status = '$ups_status_safe',
                               updated_by = " . $_SESSION['user_id'] . ",
                               updated_at = CURRENT_TIMESTAMP
                               WHERE asset_item_id = $item_id";
                
                logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Updating existing desktop record");
            } else {
                // Insert new record
                $desktop_sql = "INSERT INTO asset_desktop_computers 
                               (asset_item_id, monitor_name, monitor_model, monitor_serial_number, monitor_status, ups_name, ups_model, ups_serial_number, ups_status, created_by, created_at)
                               VALUES ($item_id, " . (empty($monitor_name) ? 'NULL' : "'$monitor_name_safe'") . ", " . (empty($monitor_model) ? 'NULL' : "'$monitor_model_safe'") . ", " . (empty($monitor_serial_number) ? 'NULL' : "'$monitor_serial_number_safe'") . ", '$monitor_status_safe', " . (empty($ups_name) ? 'NULL' : "'$ups_name_safe'") . ", " . (empty($ups_model) ? 'NULL' : "'$ups_model_safe'") . ", " . (empty($ups_serial_number) ? 'NULL' : "'$ups_serial_number_safe'") . ", '$ups_status_safe', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)";
                
                logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Inserting new desktop record");
            }
            
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Desktop SQL: $desktop_sql");
            
            // Execute the traditional SQL
            $desktop_result = mysqli_query($conn, $desktop_sql);
            
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Desktop SQL result: " . ($desktop_result ? 'SUCCESS' : 'FAILED'));
            if (!$desktop_result) {
                logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "SQL Error: " . mysqli_error($conn));
                throw new Exception('Failed to save desktop computer details: ' . mysqli_error($conn));
            }
            
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Desktop computer details saved successfully");
            
            // Log desktop computer-specific field updates
            $desktop_details = sprintf(
                "Desktop Computer specs saved - Monitor: %s %s (%s) - Status: %s, UPS: %s %s (%s) - Status: %s",
                $monitor_name ?: 'Not specified',
                $monitor_model ?: 'Not specified',
                $monitor_serial_number ?: 'No serial',
                $monitor_status ?: 'serviceable',
                $ups_name ?: 'Not specified',
                $ups_model ?: 'Not specified',
                $ups_serial_number ?: 'No serial',
                $ups_status ?: 'serviceable'
            );
            
            logSystemAction($_SESSION['user_id'], 'update', 'asset_desktop_computers', $desktop_details);
            
            $desktop_history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES ($item_id, 'Desktop Computer Specs Updated', '" . mysqli_real_escape_string($conn, $desktop_details) . "', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)";
            mysqli_query($conn, $desktop_history_sql);
        } else {
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Desktop computer condition NOT MET - skipping desktop fields");
        }
    }
    elseif ($category && ($category['category_code'] === '07' || $category['category_code'] === '06-010' || $category['category_code'] === 'MV' || $category['category_name'] === 'MV' || stripos($category['category_name'], 'vehicle') !== false)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED VEHICLE/MV BLOCK - Item ID: $item_id, Category Name: " . $category['category_name']);
        // Vehicles specific fields (including MV category 06-010)
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $plate_number = trim($_POST['plate_number'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $engine_number = trim($_POST['engine_number'] ?? '');
        $chassis_number = trim($_POST['chassis_number'] ?? '');
        $year_model = intval($_POST['year_model'] ?? 0);
        
        // Escape values for traditional SQL
        $brand_safe = mysqli_real_escape_string($conn, $brand);
        $model_safe = mysqli_real_escape_string($conn, $model);
        $plate_number_safe = mysqli_real_escape_string($conn, $plate_number);
        $color_safe = mysqli_real_escape_string($conn, $color);
        $engine_number_safe = mysqli_real_escape_string($conn, $engine_number);
        $chassis_number_safe = mysqli_real_escape_string($conn, $chassis_number);
        
        $vehicle_sql = "INSERT INTO asset_vehicles 
                       (asset_item_id, brand, model, plate_number, color, engine_number, chassis_number, year_manufactured, created_by, created_at)
                       VALUES ($item_id, '$brand_safe', '$model_safe', '$plate_number_safe', '$color_safe', '$engine_number_safe', '$chassis_number_safe', $year_model, " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       brand = '$brand_safe',
                       model = '$model_safe',
                       plate_number = '$plate_number_safe',
                       color = '$color_safe',
                       engine_number = '$engine_number_safe',
                       chassis_number = '$chassis_number_safe',
                       year_manufactured = $year_model,
                       updated_by = " . $_SESSION['user_id'] . ",
                       updated_at = CURRENT_TIMESTAMP";
        
        // Execute the traditional SQL
        $vehicle_result = mysqli_query($conn, $vehicle_sql);
        
        if (!$vehicle_result) {
            throw new Exception('Failed to save vehicle details: ' . mysqli_error($conn));
        }
        
        // Log vehicle specifications insertion
        $vehicle_details = sprintf(
            "Vehicle specs saved - Brand: %s, Model: %s, Plate: %s, Color: %s, Engine: %s, Chassis: %s, Year: %d",
            $brand ?: 'Not specified',
            $model ?: 'Not specified',
            $plate_number ?: 'Not specified',
            $color ?: 'Not specified',
            $engine_number ?: 'Not specified',
            $chassis_number ?: 'Not specified',
            $year_model ?: 0
        );
        
        logSystemAction($_SESSION['user_id'], 'update', 'asset_vehicles', $vehicle_details);
    }
    elseif ($category && ($category['category_code'] === '02' || $category['category_name'] === 'FUR' || stripos($category['category_name'], 'furniture') !== false)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED FURNITURE BLOCK - Item ID: $item_id, Category Name: " . $category['category_name']);
        // Furniture & Fixtures specific fields
        $material = trim($_POST['material'] ?? '');
        $dimensions = trim($_POST['dimensions'] ?? '');
        $furniture_color = trim($_POST['color'] ?? '');
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        
        // Escape values for traditional SQL
        $material_safe = mysqli_real_escape_string($conn, $material);
        $dimensions_safe = mysqli_real_escape_string($conn, $dimensions);
        $furniture_color_safe = mysqli_real_escape_string($conn, $furniture_color);
        $manufacturer_safe = mysqli_real_escape_string($conn, $manufacturer);
        
        $furniture_sql = "INSERT INTO asset_furniture 
                       (asset_item_id, material, dimensions, color, manufacturer, created_by, created_at)
                       VALUES ($item_id, '$material_safe', '$dimensions_safe', '$furniture_color_safe', '$manufacturer_safe', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       material = '$material_safe',
                       dimensions = '$dimensions_safe',
                       color = '$furniture_color_safe',
                       manufacturer = '$manufacturer_safe',
                       updated_by = " . $_SESSION['user_id'] . ",
                       updated_at = CURRENT_TIMESTAMP";
        
        // Execute the traditional SQL
        $furniture_result = mysqli_query($conn, $furniture_sql);
        
        if (!$furniture_result) {
            throw new Exception('Failed to save furniture details: ' . mysqli_error($conn));
        }
    }
    elseif ($category && ($category['category_code'] === '04' || $category['category_name'] === 'MACH' || stripos($category['category_name'], 'machinery') !== false)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED MACHINERY BLOCK - Item ID: $item_id, Category Name: " . $category['category_name']);
        // Machinery & Equipment specific fields
        $machine_type = trim($_POST['machine_type'] ?? '');
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $capacity = trim($_POST['capacity'] ?? '');
        $power_requirements = trim($_POST['power_rating'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        
        // Escape values for traditional SQL
        $machine_type_safe = mysqli_real_escape_string($conn, $machine_type);
        $manufacturer_safe = mysqli_real_escape_string($conn, $manufacturer);
        $model_safe = mysqli_real_escape_string($conn, $model);
        $capacity_safe = mysqli_real_escape_string($conn, $capacity);
        $power_requirements_safe = mysqli_real_escape_string($conn, $power_requirements);
        $serial_number_safe = mysqli_real_escape_string($conn, $serial_number);
        
        $machinery_sql = "INSERT INTO asset_machinery 
                       (asset_item_id, machine_type, manufacturer, model_number, capacity, power_requirements, serial_number, created_by, created_at)
                       VALUES ($item_id, '$machine_type_safe', '$manufacturer_safe', '$model_safe', '$capacity_safe', '$power_requirements_safe', '$serial_number_safe', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       machine_type = '$machine_type_safe',
                       manufacturer = '$manufacturer_safe',
                       model_number = '$model_safe',
                       capacity = '$capacity_safe',
                       power_requirements = '$power_requirements_safe',
                       serial_number = '$serial_number_safe',
                       updated_by = " . $_SESSION['user_id'] . ",
                       updated_at = CURRENT_TIMESTAMP";
        
        // Execute the traditional SQL
        $machinery_result = mysqli_query($conn, $machinery_sql);
        
        if (!$machinery_result) {
            throw new Exception('Failed to save machinery details: ' . mysqli_error($conn));
        }
    }
    elseif ($category && ($category['category_code'] === '05' || $category['category_name'] === 'OE' || stripos($category['category_name'], 'office') !== false)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED OFFICE EQUIP BLOCK - Item ID: $item_id, Category Name: " . $category['category_name']);
        // Office Equipment specific fields
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        
        // Escape values for traditional SQL
        $brand_safe = mysqli_real_escape_string($conn, $brand);
        $model_safe = mysqli_real_escape_string($conn, $model);
        $serial_number_safe = mysqli_real_escape_string($conn, $serial_number);
        
        $office_equipment_sql = "INSERT INTO asset_office_equipment 
                       (asset_item_id, brand, model, serial_number, created_by, created_at)
                       VALUES ($item_id, '$brand_safe', '$model_safe', '$serial_number_safe', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       brand = '$brand_safe',
                       model = '$model_safe',
                       serial_number = '$serial_number_safe',
                       updated_by = " . $_SESSION['user_id'] . ",
                       updated_at = CURRENT_TIMESTAMP";
        
        // Execute the traditional SQL
        $office_equipment_result = mysqli_query($conn, $office_equipment_sql);
        
        if (!$office_equipment_result) {
            throw new Exception('Failed to save office equipment details: ' . mysqli_error($conn));
        }
    }
    elseif ($category && ($category['category_code'] === '06' || $category['category_code'] === 'SW' || $category['category_name'] === 'SW' || stripos($category['category_name'], 'software') !== false)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED SOFTWARE/SW BLOCK - Item ID: $item_id, Category Name: " . $category['category_name']);
        // Software specific fields
        $software_name = trim($_POST['software_name'] ?? '');
        $version = trim($_POST['version'] ?? '');
        $license_key = trim($_POST['license_key'] ?? '');
        $expiry_date = !empty($_POST['expiry_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['expiry_date']) . "'" : 'NULL';
        
        // Escape values for traditional SQL
        $software_name_safe = mysqli_real_escape_string($conn, $software_name);
        $version_safe = mysqli_real_escape_string($conn, $version);
        $license_key_safe = mysqli_real_escape_string($conn, $license_key);
        
        $software_sql = "INSERT INTO asset_software 
                       (asset_item_id, software_name, version, license_key, license_expiry, created_by, created_at)
                       VALUES ($item_id, '$software_name_safe', '$version_safe', '$license_key_safe', $expiry_date, " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       software_name = '$software_name_safe',
                       version = '$version_safe',
                       license_key = '$license_key_safe',
                       license_expiry = $expiry_date,
                       updated_by = " . $_SESSION['user_id'] . ",
                       updated_at = CURRENT_TIMESTAMP";
        
        // Execute the traditional SQL
        $software_result = mysqli_query($conn, $software_sql);
        
        if (!$software_result) {
            throw new Exception('Failed to save software details: ' . mysqli_error($conn));
        }
    }
    elseif ($category && ($category['category_code'] === '03' || $category['category_code'] === 'LND' || $category['category_code'] === '01' || $category['category_name'] === 'LND' || stripos($category['category_name'], 'land') !== false)) {
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED LAND BLOCK - Item ID: $item_id, Category Name: " . $category['category_name']);
        // Land specific fields
        $lot_number = trim($_POST['lot_number'] ?? '');
        $area_size = trim($_POST['area_size'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $tax_declaration = trim($_POST['tax_declaration'] ?? '');
        
        logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "ENTERED LAND BLOCK - Item ID: $item_id, Lot: $lot_number, Area: $area_size, Loc: $location, Tax: $tax_declaration");

        // Escape values for traditional SQL
        $lot_number_safe = mysqli_real_escape_string($conn, $lot_number);
        $area_size_safe = mysqli_real_escape_string($conn, $area_size);
        $location_safe = mysqli_real_escape_string($conn, $location);
        $tax_declaration_safe = mysqli_real_escape_string($conn, $tax_declaration);
        
        $land_sql = "INSERT INTO asset_land_info 
                       (asset_item_id, lot_number, area_size, location, tax_declaration, created_by, created_at)
                       VALUES ($item_id, '$lot_number_safe', '$area_size_safe', '$location_safe', '$tax_declaration_safe', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       lot_number = '$lot_number_safe',
                       area_size = '$area_size_safe',
                       location = '$location_safe',
                       tax_declaration = '$tax_declaration_safe',
                       updated_by = " . $_SESSION['user_id'] . ",
                       updated_at = CURRENT_TIMESTAMP";
        
        // Execute the traditional SQL
        $land_result = mysqli_query($conn, $land_sql);
        
        if (!$land_result) {
            throw new Exception('Failed to save land details: ' . mysqli_error($conn));
        }
    }
    
    // Process Peripherals (for all asset types)
    if (isset($_POST['peripheral_name']) && is_array($_POST['peripheral_name'])) {
        $peripheral_names = $_POST['peripheral_name'];
        $peripheral_models = $_POST['peripheral_model'] ?? [];
        $peripheral_serial_numbers = $_POST['peripheral_serial_number'] ?? [];
        $peripheral_statuses = $_POST['peripheral_status'] ?? [];
        
        // First, delete existing peripherals for this asset item
        $delete_peripherals_sql = "DELETE FROM peripherals WHERE asset_item_id = $item_id";
        mysqli_query($conn, $delete_peripherals_sql);
        
        // Insert new peripherals
        $peripherals_added = 0;
        foreach ($peripheral_names as $index => $name) {
            $name = trim($name);
            
            // Only insert if name is not empty (peripherals are optional)
            if (!empty($name)) {
                $model = isset($peripheral_models[$index]) ? trim($peripheral_models[$index]) : '';
                $serial_number = isset($peripheral_serial_numbers[$index]) ? trim($peripheral_serial_numbers[$index]) : '';
                $status = isset($peripheral_statuses[$index]) ? trim($peripheral_statuses[$index]) : 'serviceable';
                
                // Validate status
                $allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'no_tag', 'disposed'];
                if (!in_array($status, $allowed_statuses)) {
                    $status = 'serviceable';
                }
                
                // Escape values for traditional SQL
                $name_safe = mysqli_real_escape_string($conn, $name);
                $model_safe = !empty($model) ? "'" . mysqli_real_escape_string($conn, $model) . "'" : 'NULL';
                $serial_number_safe = !empty($serial_number) ? "'" . mysqli_real_escape_string($conn, $serial_number) . "'" : 'NULL';
                $status_safe = mysqli_real_escape_string($conn, $status);
                
                $peripheral_sql = "INSERT INTO peripherals 
                           (asset_item_id, name, model, serial_number, status, created_by, created_at)
                           VALUES ($item_id, '$name_safe', $model_safe, $serial_number_safe, '$status_safe', " . $_SESSION['user_id'] . ", CURRENT_TIMESTAMP)";
                
                $peripheral_result = mysqli_query($conn, $peripheral_sql);
                
                if ($peripheral_result) {
                    $peripherals_added++;
                } else {
                    throw new Exception('Failed to save peripheral: ' . mysqli_error($conn));
                }
            }
        }
        
        if ($peripherals_added > 0) {
            logSystemAction($_SESSION['user_id'], 'debug', 'process_tag', "Added $peripherals_added peripherals for asset item ID: $item_id");
        }
    }
    
    // Get employee information for logging
    $employee_sql = "SELECT employee_no, firstname, lastname FROM employees WHERE id = ?";
    $employee_stmt = $conn->prepare($employee_sql);
    $employee_stmt->bind_param("i", $person_accountable);
    $employee_stmt->execute();
    $employee_result = $employee_stmt->get_result();
    $employee = $employee_result->fetch_assoc();
    
    // Log the tag creation action with multiple images
    $image_info = !empty($all_images) ? "Images: " . implode(', ', $all_images) : "No images";
    $log_details = sprintf(
        "Created tag for item ID %d: Property No: %s, Inventory Tag: %s, Date Counted: %s, Category: %s, Person Accountable: %s (%s), %s",
        $item_id,
        $property_no,
        $inventory_tag,
        $date_counted,
        $category ? $category['category_code'] . ' - ' . $category['category_name'] : 'Unknown',
        $employee ? $employee['employee_no'] : 'Unknown',
        $employee ? $employee['firstname'] . ' ' . $employee['lastname'] : 'Unknown',
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
    
    // Create notification for MAIN_USER
    createMainUserAssetTagNotification($item_id, $property_no);
    
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

// Function to create notifications for MAIN_USER when asset tag is created
function createMainUserAssetTagNotification($asset_item_id, $property_no) {
    global $conn;
    
    // Get asset item details
    $item_query = "SELECT ai.description, a.description as asset_description 
                   FROM asset_items ai 
                   LEFT JOIN assets a ON ai.asset_id = a.id 
                   WHERE ai.id = ?";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bind_param("i", $asset_item_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    
    if ($item_result && $item_row = $item_result->fetch_assoc()) {
        $item_description = $item_row['description'];
        $asset_description = $item_row['asset_description'];
        
        // Get all MAIN_USER users
        $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND is_active = 1";
        $main_users_result = $conn->query($main_users_query);
        
        if ($main_users_result && $main_users_result->num_rows > 0) {
            while ($main_user = $main_users_result->fetch_assoc()) {
                $user_id = $main_user['id'];
                $title = "Asset Tag Created";
                $message = "Asset '{$item_description}' has been tagged with property number: {$property_no}.";
                $type = "success";
                $related_id = $asset_item_id;
                $related_type = "asset_item";
                
                // Insert notification
                $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
                $stmt->execute();
            }
        }
    }
    
    $item_stmt->close();
}
?>
