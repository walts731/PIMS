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
$inventory_tag = trim($_POST['inventory_tag'] ?? '');
$person_accountable = intval($_POST['person_accountable']);
$end_user = trim($_POST['end_user'] ?? '');
$date_counted = trim($_POST['date_counted']);
$tag_format_id = intval($_POST['tag_format_id']);
$current_number = intval($_POST['current_number']);

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
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB per file
            
            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['error'] = 'Invalid file type: ' . $file['name'] . '. Only JPG, PNG, and GIF files are allowed.';
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
if (empty($item_id) || empty($category_id) || empty($office_name) || empty($property_no) || empty($person_accountable) || empty($end_user) || empty($date_counted)) {
    $_SESSION['error'] = 'Please fill in all required fields';
    header('Location: create_tag.php?id=' . $item_id);
    exit();
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
    $inventory_tag_safe = !empty($inventory_tag) ? "'" . mysqli_real_escape_string($conn, $inventory_tag) . "'" : 'NULL';
    $date_counted_safe = mysqli_real_escape_string($conn, $date_counted);
    // Convert image filenames array to JSON for storage
    $image_filename_json = !empty($all_images) ? json_encode($all_images) : 'NULL';
    $image_filename_safe = mysqli_real_escape_string($conn, $image_filename_json);
    $end_user_safe = mysqli_real_escape_string($conn, $end_user);
    $office_name_safe = mysqli_real_escape_string($conn, $office_name);
    
    $update_sql = "UPDATE asset_items SET 
                   property_no = '$property_no_safe', 
                   inventory_tag = $inventory_tag_safe, 
                   date_counted = '$date_counted_safe',
                   image = '$image_filename_safe',
                   employee_id = $person_accountable, 
                   category_id = $category_id,
                   asset_subcategory_id = " . ($subcategory_id > 0 ? $subcategory_id : 'NULL') . ",
                   office_name = '$office_name_safe',
                   end_user = '$end_user_safe',
                   status = 'serviceable',
                   last_updated = CURRENT_TIMESTAMP
                   WHERE id = $item_id";
    
    // Debug: Log the SQL and values before execution
    logSystemAction($_SESSION['user_id'], 'Tag Update SQL Debug', 'forms', "SQL: $update_sql");
    logSystemAction($_SESSION['user_id'], 'Tag Update Values Debug', 'forms', "Values: property_no='{$property_no}', inventory_tag='{$inventory_tag}', date_counted='{$date_counted}', image='{$image_filename}', employee_id={$person_accountable}, category_id={$category_id}, end_user='{$end_user}' (length: " . strlen($end_user) . "), item_id={$item_id}");
    
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
        
        $update_assets_sql = "UPDATE assets SET 
                              asset_categories_id = ?,
                              asset_subcategory_id = ?,
                              office_id = ?,
                              updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
        $update_assets_stmt = $conn->prepare($update_assets_sql);
        $update_assets_stmt->bind_param("iiii", $category_id, $subcategory_id, $office_id_for_assets, $asset_id);
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
    
    // Handle category-specific fields
    if ($category && $category['category_code'] === '030') {
        $processor = trim($_POST['processor'] ?? '');
        $ram = trim($_POST['ram'] ?? '');
        $storage = trim($_POST['storage'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $operating_system = trim($_POST['operating_system'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        
        // Insert or update computer equipment-specific information
        $computer_sql = "INSERT INTO asset_computers 
                       (asset_item_id, processor, ram, storage, model, operating_system, serial_number, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       processor = VALUES(processor),
                       ram = VALUES(ram),
                       storage = VALUES(storage),
                       model = VALUES(model),
                       operating_system = VALUES(operating_system),
                       serial_number = VALUES(serial_number),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $computer_stmt = $conn->prepare($computer_sql);
        $computer_stmt->bind_param("issssss", $item_id, $processor, $ram, $storage, $model, $operating_system, $serial_number, $_SESSION['user_id']);
        $computer_stmt->execute();
        
        // Log computer equipment-specific field updates
        $computer_details = sprintf(
            "Computer Equipment specs saved - Processor: %s, RAM: %s, Storage: %s, Model: %s, OS: %s, Serial: %s",
            $processor ?: 'Not specified',
            $ram ?: 'Not specified',
            $storage ?: 'Not specified',
            $model ?: 'Not specified',
            $operating_system ?: 'Not specified',
            $serial_number ?: 'Not specified'
        );
        
        $computer_history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'Computer Specs Updated', ?, ?, CURRENT_TIMESTAMP)";
        $computer_history_stmt = $conn->prepare($computer_history_sql);
        $computer_history_stmt->bind_param("isi", $item_id, $computer_details, $_SESSION['user_id']);
        $computer_history_stmt->execute();
        
        // Handle Desktop Computers subcategory-specific fields
        if ($subcategory && $subcategory['sub_category_code'] === '03') {
            $monitor_name = trim($_POST['monitor_name'] ?? '');
            $monitor_model = trim($_POST['monitor_model'] ?? '');
            $monitor_serial_number = trim($_POST['monitor_serial_number'] ?? '');
            $ups_name = trim($_POST['ups_name'] ?? '');
            $ups_model = trim($_POST['ups_model'] ?? '');
            $ups_serial_number = trim($_POST['ups_serial_number'] ?? '');
            
            // Insert or update desktop computer-specific information
            $desktop_sql = "INSERT INTO asset_desktop_computers 
                           (asset_item_id, monitor_name, monitor_model, monitor_serial_number, ups_name, ups_model, ups_serial_number, created_by, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                           ON DUPLICATE KEY UPDATE
                           monitor_name = VALUES(monitor_name),
                           monitor_model = VALUES(monitor_model),
                           monitor_serial_number = VALUES(monitor_serial_number),
                           ups_name = VALUES(ups_name),
                           ups_model = VALUES(ups_model),
                           ups_serial_number = VALUES(ups_serial_number),
                           updated_by = VALUES(created_by),
                           updated_at = CURRENT_TIMESTAMP";
            
            $desktop_stmt = $conn->prepare($desktop_sql);
            $desktop_stmt->bind_param("issssss", $item_id, $monitor_name, $monitor_model, $monitor_serial_number, $ups_name, $ups_model, $ups_serial_number, $_SESSION['user_id']);
            $desktop_stmt->execute();
            
            // Log desktop computer-specific field updates
            $desktop_details = sprintf(
                "Desktop Computer specs saved - Monitor: %s %s (%s), UPS: %s %s (%s)",
                $monitor_name ?: 'Not specified',
                $monitor_model ?: 'Not specified',
                $monitor_serial_number ?: 'No serial',
                $ups_name ?: 'Not specified',
                $ups_model ?: 'Not specified',
                $ups_serial_number ?: 'No serial'
            );
            
            $desktop_history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'Desktop Computer Specs Updated', ?, ?, CURRENT_TIMESTAMP)";
            $desktop_history_stmt = $conn->prepare($desktop_history_sql);
            $desktop_history_stmt->bind_param("isi", $item_id, $desktop_details, $_SESSION['user_id']);
            $desktop_history_stmt->execute();
        }
    }
    elseif ($category && $category['category_code'] === '07') {
        // Vehicles specific fields
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $plate_number = trim($_POST['plate_number'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $engine_number = trim($_POST['engine_number'] ?? '');
        $chassis_number = trim($_POST['chassis_number'] ?? '');
        $year_model = intval($_POST['year_model'] ?? 0);
        
        $vehicle_sql = "INSERT INTO asset_vehicles 
                       (asset_item_id, brand, model, plate_number, color, engine_number, chassis_number, year_manufactured, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       brand = VALUES(brand),
                       model = VALUES(model),
                       plate_number = VALUES(plate_number),
                       color = VALUES(color),
                       engine_number = VALUES(engine_number),
                       chassis_number = VALUES(chassis_number),
                       year_manufactured = VALUES(year_manufactured),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $vehicle_stmt = $conn->prepare($vehicle_sql);
        $vehicle_stmt->bind_param("isssssiii", $item_id, $brand, $model, $plate_number, $color, $engine_number, $chassis_number, $year_model, $_SESSION['user_id']);
        $vehicle_stmt->execute();
    }
    elseif ($category && $category['category_code'] === '02') {
        // Furniture & Fixtures specific fields
        $material = trim($_POST['material'] ?? '');
        $dimensions = trim($_POST['dimensions'] ?? '');
        $furniture_color = trim($_POST['color'] ?? '');
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        
        $furniture_sql = "INSERT INTO asset_furniture 
                       (asset_item_id, material, dimensions, color, manufacturer, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       material = VALUES(material),
                       dimensions = VALUES(dimensions),
                       color = VALUES(color),
                       manufacturer = VALUES(manufacturer),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $furniture_stmt = $conn->prepare($furniture_sql);
        $furniture_stmt->bind_param("issssi", $item_id, $material, $dimensions, $furniture_color, $manufacturer, $_SESSION['user_id']);
        $furniture_stmt->execute();
    }
    elseif ($category && $category['category_code'] === '04') {
        // Machinery & Equipment specific fields
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $capacity = trim($_POST['capacity'] ?? '');
        $power_rating = trim($_POST['power_rating'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        
        $machinery_sql = "INSERT INTO asset_machinery 
                       (asset_item_id, machine_type, manufacturer, model_number, capacity, power_requirements, serial_number, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       machine_type = VALUES(machine_type),
                       manufacturer = VALUES(manufacturer),
                       model_number = VALUES(model_number),
                       capacity = VALUES(capacity),
                       power_requirements = VALUES(power_requirements),
                       serial_number = VALUES(serial_number),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $machinery_stmt = $conn->prepare($machinery_sql);
        $machinery_stmt->bind_param("issssssi", $item_id, $manufacturer, $manufacturer, $model, $capacity, $power_rating, $serial_number, $_SESSION['user_id']);
        $machinery_stmt->execute();
    }
    elseif ($category && $category['category_code'] === '05') {
        // Office Equipment specific fields
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        
        $office_equipment_sql = "INSERT INTO asset_office_equipment 
                       (asset_item_id, brand, model, serial_number, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       brand = VALUES(brand),
                       model = VALUES(model),
                       serial_number = VALUES(serial_number),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $office_equipment_stmt = $conn->prepare($office_equipment_sql);
        $office_equipment_stmt->bind_param("isssi", $item_id, $brand, $model, $serial_number, $_SESSION['user_id']);
        $office_equipment_stmt->execute();
    }
    elseif ($category && $category['category_code'] === '06') {
        // Software specific fields
        $software_name = trim($_POST['software_name'] ?? '');
        $version = trim($_POST['version'] ?? '');
        $license_key = trim($_POST['license_key'] ?? '');
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        $software_sql = "INSERT INTO asset_software 
                       (asset_item_id, software_name, version, license_key, license_expiry, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       software_name = VALUES(software_name),
                       version = VALUES(version),
                       license_key = VALUES(license_key),
                       license_expiry = VALUES(license_expiry),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $software_stmt = $conn->prepare($software_sql);
        $software_stmt->bind_param("issssi", $item_id, $software_name, $version, $license_key, $expiry_date, $_SESSION['user_id']);
        $software_stmt->execute();
    }
    elseif ($category && $category['category_code'] === '03') {
        // Land specific fields
        $lot_number = trim($_POST['lot_number'] ?? '');
        $area_size = trim($_POST['area_size'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $tax_declaration = trim($_POST['tax_declaration'] ?? '');
        
        $land_sql = "INSERT INTO asset_land 
                       (asset_item_id, lot_area, address, tax_declaration_number, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       lot_area = VALUES(lot_area),
                       address = VALUES(address),
                       tax_declaration_number = VALUES(tax_declaration_number),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $land_stmt = $conn->prepare($land_sql);
        $land_stmt->bind_param("isssi", $item_id, $area_size, $location, $tax_declaration, $_SESSION['user_id']);
        $land_stmt->execute();
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
        "Created tag for item ID %d: Property No: %s, Inventory Tag: %s, Date Counted: %s, Category: %s, Person Accountable: %s (%s), End User: %s, %s",
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
        $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND status = 'active'";
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
