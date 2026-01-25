<?php
session_start();
require_once '../config.php';
require_once '../includes/qr_generator.php';
require_once '../includes/system_functions.php';

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

// Check if we should increment the property number counter
if (isset($_POST['increment_property_counter']) && $_POST['increment_property_counter'] == '1') {
    // Generate the actual property number (this increments the counter)
    $generated_property_no = generateNextTag('property_no');
    if ($generated_property_no !== null) {
        $property_no = $generated_property_no;
        logSystemAction($_SESSION['user_id'], 'Property number counter incremented', 'forms', "Generated property number: $property_no");
    }
}

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
                   category_id = ?,
                   status = 'serviceable',
                   last_updated = CURRENT_TIMESTAMP
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssiii", $property_no, $inventory_tag, $date_counted, $image_filename, $person_accountable, $category_id, $item_id);
    $update_stmt->execute();
    
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
        $update_assets_sql = "UPDATE assets SET 
                              asset_categories_id = ?,
                              updated_at = CURRENT_TIMESTAMP
                              WHERE id = ?";
        $update_assets_stmt = $conn->prepare($update_assets_sql);
        $update_assets_stmt->bind_param("ii", $category_id, $asset_id);
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
    
    // Handle category-specific fields
    if ($category && $category['category_code'] === 'ITS') {
        // Computer Equipment specific fields
        $processor = trim($_POST['processor'] ?? '');
        $ram = trim($_POST['ram'] ?? '');
        $storage = trim($_POST['storage'] ?? '');
        $operating_system = trim($_POST['operating_system'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        
        // Insert or update computer-specific information
        $computer_sql = "INSERT INTO asset_computers 
                       (asset_item_id, processor, ram_capacity, storage_capacity, operating_system, serial_number, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                       ON DUPLICATE KEY UPDATE
                       processor = VALUES(processor),
                       ram_capacity = VALUES(ram_capacity),
                       storage_capacity = VALUES(storage_capacity),
                       operating_system = VALUES(operating_system),
                       serial_number = VALUES(serial_number),
                       updated_by = VALUES(created_by),
                       updated_at = CURRENT_TIMESTAMP";
        
        $computer_stmt = $conn->prepare($computer_sql);
        $computer_stmt->bind_param("isssssi", $item_id, $processor, $ram, $storage, $operating_system, $serial_number, $_SESSION['user_id']);
        $computer_stmt->execute();
        
        // Log computer-specific field updates
        $computer_details = sprintf(
            "Computer Equipment specs saved - Processor: %s, RAM: %s, Storage: %s, OS: %s, Serial: %s",
            $processor ?: 'Not specified',
            $ram ?: 'Not specified', 
            $storage ?: 'Not specified',
            $operating_system ?: 'Not specified',
            $serial_number ?: 'Not specified'
        );
        
        $computer_history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'Computer Specs Updated', ?, ?, CURRENT_TIMESTAMP)";
        $computer_history_stmt = $conn->prepare($computer_history_sql);
        $computer_history_stmt->bind_param("isi", $item_id, $computer_details, $_SESSION['user_id']);
        $computer_history_stmt->execute();
    }
    elseif ($category && $category['category_code'] === 'VH') {
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
    elseif ($category && $category['category_code'] === 'FF') {
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
    elseif ($category && $category['category_code'] === 'ME') {
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
    elseif ($category && $category['category_code'] === 'OE') {
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
    elseif ($category && $category['category_code'] === 'SW') {
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
    elseif ($category && $category['category_code'] === 'LD') {
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
