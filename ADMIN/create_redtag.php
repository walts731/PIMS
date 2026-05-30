<?php
ob_start();
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'includes/check_permissions.php';
adminRequirePermission('tags.create', 'can_create', 'dashboard.php');

// Log red tag creation page access
logSystemAction($_SESSION['user_id'], 'access', 'create_redtag', 'Admin accessed create red tag page');

// Get system settings for logo
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings WHERE setting_name IN ('system_logo', 'system_name')");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $system_settings[$row['setting_name']] = $row['setting_value'];
        }
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

// Get asset data from URL parameters if provided
$asset_item_id = isset($_GET['asset_id']) ? intval($_GET['asset_id']) : 0;
$description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '';
$property_no = isset($_GET['property_no']) ? htmlspecialchars($_GET['property_no']) : '';
$inventory_tag = isset($_GET['inventory_tag']) ? htmlspecialchars($_GET['inventory_tag']) : '';
$acquisition_date = isset($_GET['acquisition_date']) ? htmlspecialchars($_GET['acquisition_date']) : '';
$value = isset($_GET['value']) ? floatval($_GET['value']) : 0;
$office_name = isset($_GET['office_name']) ? htmlspecialchars($_GET['office_name']) : '';

// Get component information if provided
$component_type = isset($_GET['component_type']) ? htmlspecialchars($_GET['component_type']) : 'main_asset';
$component_description = isset($_GET['component_description']) ? htmlspecialchars($_GET['component_description']) : '';
$component_value = isset($_GET['component_value']) ? floatval($_GET['component_value']) : 0;

// Get peripheral-specific information if provided
$peripheral_id = isset($_GET['peripheral_id']) ? intval($_GET['peripheral_id']) : null;
$peripheral_name = isset($_GET['peripheral_name']) ? htmlspecialchars($_GET['peripheral_name']) : '';
$peripheral_model = isset($_GET['peripheral_model']) ? htmlspecialchars($_GET['peripheral_model']) : '';
$peripheral_serial = isset($_GET['peripheral_serial_number']) ? htmlspecialchars($_GET['peripheral_serial_number']) : '';
$peripheral_status = isset($_GET['peripheral_status']) ? htmlspecialchars($_GET['peripheral_status']) : '';

// Get asset item details including serial number if asset_id is provided
$serial_number = '';
if ($asset_item_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT serial_number FROM asset_items WHERE id = ?");
        $stmt->bind_param("i", $asset_item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $serial_number = $row['serial_number'] ?? '';
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching asset item details: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_redtag'])) {
    $control_no = trim($_POST['control_no'] ?? '');
    $date_received = trim($_POST['date_received'] ?? date('m/d/Y'));
    
    // Convert date from mm/dd/yyyy to yyyy-mm-dd for database
    if (!empty($date_received) && preg_match('/^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}$/', $date_received)) {
        $date_parts = explode('/', $date_received);
        $date_received_db = $date_parts[2] . '-' . $date_parts[0] . '-' . $date_parts[1];
    } else {
        $date_received_db = date('Y-m-d');
    }
    $tagged_by = trim($_POST['tagged_by'] ?? '');
    $item_location = trim($_POST['item_location'] ?? '');
    
    // Use component_description from GET for component types, or from POST for main assets
    $item_description = trim($_POST['item_description'] ?? '');
    
    // For component types, if POST item_description is empty, use the component_description from GET
    if ($component_type !== 'main_asset' && empty($item_description) && !empty($component_description)) {
        $item_description = $component_description;
    }
    
    // Check if serial number is already in the description to avoid duplication
    if (!empty($serial_number) && strpos($item_description, '(S/N:') === false) {
        if (!empty($item_description)) {
            $item_description .= " (S/N: {$serial_number})";
        } else {
            $item_description = "S/N: {$serial_number}";
        }
    }
    
    // Handle image uploads
    $uploaded_images = [];
    if (isset($_FILES['redtag_images']) && $_FILES['redtag_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $upload_dir = '../uploads/redtag_images/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['redtag_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['redtag_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['redtag_images']['name'][$key];
                $file_size = $_FILES['redtag_images']['size'][$key];
                $file_tmp = $_FILES['redtag_images']['tmp_name'][$key];
                $file_type = $_FILES['redtag_images']['type'][$key];
                
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_filename = 'redtag_' . time() . '_' . $key . '.' . $file_extension;
                $upload_path = $upload_dir . $unique_filename;
                
                // Validate file type (image only)
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($file_type, $allowed_types) && $file_size <= 5242880) { // 5MB limit
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $uploaded_images[] = 'uploads/redtag_images/' . $unique_filename;
                    }
                }
            }
        }
    }
    
    $removal_reason = trim($_POST['removal_reason'] ?? '');
    $action = trim($_POST['action'] ?? '');
    $other_action = trim($_POST['other_action'] ?? '');
    
    // If action is "other", use the custom action text
    if ($action === 'other' && !empty($other_action)) {
        $action = $other_action;
    }
    
    // Generate red_tag_no if not provided or empty
    if (empty($red_tag_no)) {
        $red_tag_no = generateNextTag('red_tag_no');
        if (empty($red_tag_no)) {
            // Fallback to manual generation if tag_formats not configured
            $red_tag_no = 'RTN-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    // Ensure control_no is not empty
    if (empty($control_no)) {
        $control_no = generateNextTag('red_tag_control');
        if (empty($control_no)) {
            // Fallback to manual generation if tag_formats not configured
            $control_no = 'RT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get office_id from office_name
        $office_id = null;
        if (!empty($item_location)) {
            $office_stmt = $conn->prepare("SELECT id FROM offices WHERE office_name = ? LIMIT 1");
            $office_stmt->bind_param("s", $item_location);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            if ($office_row = $office_result->fetch_assoc()) {
                $office_id = $office_row['id'];
            }
            $office_stmt->close();
        }
        
        // Debug logging
        error_log("Red Tag Debug - Data: control_no=$control_no, red_tag_no=$red_tag_no, asset_item_id=$asset_item_id, office_id=$office_id, peripheral_id=$peripheral_id, item_description=$item_description, disposal_reason=" . ($disposal_reason_null ?? 'NULL') . ", disposal_date=" . ($disposal_date_null ?? 'NULL'));
        
        // Insert into red_tags table using traditional SQL
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');
        
        // Escape all values for security
        $control_no_esc = $conn->real_escape_string($control_no);
        $red_tag_no_esc = $conn->real_escape_string($red_tag_no);
        $date_received_esc = $conn->real_escape_string($date_received_db);
        $tagged_by_esc = $conn->real_escape_string($tagged_by);
        $item_location_esc = $conn->real_escape_string($item_location);
        $item_description_esc = $conn->real_escape_string($item_description);
        $removal_reason_esc = $conn->real_escape_string($removal_reason);
        $action_esc = $conn->real_escape_string($action);
        $component_type_esc = $conn->real_escape_string($component_type);
        $component_description_esc = $conn->real_escape_string($component_description);
        
        $insert_sql = "INSERT INTO red_tags (control_no, red_tag_no, date_received, tagged_by, item_location, item_description, removal_reason, action, office_id, asset_item_id, created_by, component_type, component_description, peripheral_id, disposal_reason, disposal_date, created_at, updated_at, updated_by) VALUES (
            '$control_no_esc', 
            '$red_tag_no_esc', 
            '$date_received_esc', 
            '$tagged_by_esc', 
            '$item_location_esc', 
            '$item_description_esc', 
            '$removal_reason_esc', 
            '$action_esc', 
            " . ($office_id ? $office_id : 'NULL') . ", 
            " . ($asset_item_id ? $asset_item_id : 'NULL') . ", 
            " . $_SESSION['user_id'] . ", 
            '$component_type_esc', 
            '$component_description_esc', 
            " . ($peripheral_id ? $peripheral_id : 'NULL') . ", 
            " . (!empty($removal_reason_esc) ? "'$removal_reason_esc'" : 'NULL') . ", 
            NULL, 
            '$created_at', 
            '$updated_at', 
            " . $_SESSION['user_id'] . "
        )";
        
        $execute_result = $conn->query($insert_sql);
        if (!$execute_result) {
            throw new Exception("Insert failed: " . $conn->error);
        }
        $red_tag_id = $conn->insert_id;
        
        // Update status to 'red_tagged' if asset_item_id is provided
        if ($asset_item_id > 0) {
            // Check if this is a component or main asset
            if ($component_type === 'monitor') {
                // Update monitor status in asset_desktop_computers table
                $current_status_sql = "SELECT monitor_status FROM asset_desktop_computers WHERE asset_item_id = ?";
                $current_status_stmt = $conn->prepare($current_status_sql);
                $current_status_stmt->bind_param("i", $asset_item_id);
                $current_status_stmt->execute();
                $current_status_result = $current_status_stmt->get_result();
                $old_status = 'unknown';
                if ($current_status_row = $current_status_result->fetch_assoc()) {
                    $old_status = $current_status_row['monitor_status'];
                }
                $current_status_stmt->close();
                
                $update_sql = "UPDATE asset_desktop_computers SET monitor_status = 'red_tagged' WHERE asset_item_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $asset_item_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Log the component status change
                logSystemAction($_SESSION['user_id'], 'component_status_updated', 'inventory', "Monitor component for Asset ID {$asset_item_id} status changed from {$old_status} to red_tagged");
                
            } elseif ($component_type === 'ups') {
                // Update UPS status in asset_desktop_computers table
                $current_status_sql = "SELECT ups_status FROM asset_desktop_computers WHERE asset_item_id = ?";
                $current_status_stmt = $conn->prepare($current_status_sql);
                $current_status_stmt->bind_param("i", $asset_item_id);
                $current_status_stmt->execute();
                $current_status_result = $current_status_stmt->get_result();
                $old_status = 'unknown';
                if ($current_status_row = $current_status_result->fetch_assoc()) {
                    $old_status = $current_status_row['ups_status'];
                }
                $current_status_stmt->close();
                
                $update_sql = "UPDATE asset_desktop_computers SET ups_status = 'red_tagged' WHERE asset_item_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $asset_item_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Log the component status change
                logSystemAction($_SESSION['user_id'], 'component_status_updated', 'inventory', "UPS component for Asset ID {$asset_item_id} status changed from {$old_status} to red_tagged");
                
            } elseif ($component_type === 'peripheral') {
                // Update peripheral status in peripherals table
                $current_status_sql = "SELECT id, status FROM peripherals WHERE asset_item_id = ?";
                $current_status_stmt = $conn->prepare($current_status_sql);
                $current_status_stmt->bind_param("i", $asset_item_id);
                $current_status_stmt->execute();
                $current_status_result = $current_status_stmt->get_result();
                $old_status = 'unknown';
                $peripheral_id = null;
                if ($current_status_row = $current_status_result->fetch_assoc()) {
                    $old_status = $current_status_row['status'];
                    $peripheral_id = $current_status_row['id'];
                }
                $current_status_stmt->close();
                
                $update_sql = "UPDATE peripherals SET status = 'red_tagged' WHERE asset_item_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $asset_item_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Update asset_items with redtag images if main asset exists
                if ($asset_item_id && !empty($uploaded_images)) {
                    $images_json = json_encode($uploaded_images);
                    $update_asset_sql = "UPDATE asset_items SET redtag_image = ? WHERE id = ?";
                    $update_asset_stmt = $conn->prepare($update_asset_sql);
                    $update_asset_stmt->bind_param("si", $images_json, $asset_item_id);
                    $update_asset_stmt->execute();
                    $update_asset_stmt->close();
                }
                
                // Update red_tags table with peripheral_id
                if ($peripheral_id) {
                    $update_red_tag_sql = "UPDATE red_tags SET peripheral_id = ? WHERE id = ?";
                    $update_red_tag_stmt = $conn->prepare($update_red_tag_sql);
                    $update_red_tag_stmt->bind_param("ii", $peripheral_id, $red_tag_id);
                    $update_red_tag_stmt->execute();
                    $update_red_tag_stmt->close();
                }
                
                // Log the component status change
                logSystemAction($_SESSION['user_id'], 'component_status_updated', 'inventory', "Peripheral component for Asset ID {$asset_item_id} status changed from {$old_status} to red_tagged");
                
            } else {
                // Update main asset status (original logic)
                $current_status_sql = "SELECT status FROM asset_items WHERE id = ?";
                $current_status_stmt = $conn->prepare($current_status_sql);
                $current_status_stmt->bind_param("i", $asset_item_id);
                $current_status_stmt->execute();
                $current_status_result = $current_status_stmt->get_result();
                $old_status = 'unknown';
                if ($current_status_row = $current_status_result->fetch_assoc()) {
                    $old_status = $current_status_row['status'];
                }
                $current_status_stmt->close();
                
                $update_sql = "UPDATE asset_items SET status = 'red_tagged', last_updated = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $asset_item_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Update redtag images if uploaded
                if (!empty($uploaded_images)) {
                    $images_json = json_encode($uploaded_images);
                    $update_images_sql = "UPDATE asset_items SET redtag_image = ? WHERE id = ?";
                    $update_images_stmt = $conn->prepare($update_images_sql);
                    $update_images_stmt->bind_param("si", $images_json, $asset_item_id);
                    $update_images_stmt->execute();
                    $update_images_stmt->close();
                }
                
                // Record history for the asset status change
                $history_sql = "INSERT INTO asset_item_history (item_id, action, old_value, new_value, created_by, created_at, details) 
                              VALUES (?, 'status_change', ?, 'red_tagged', ?, NOW(), 'Status changed via Red Tag: $control_no')";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("iss", $asset_item_id, $old_status, $_SESSION['user_id']);
                $history_stmt->execute();
                $history_stmt->close();
                
                // Log the asset status change
                logSystemAction($_SESSION['user_id'], 'asset_status_updated', 'inventory', "Asset ID {$asset_item_id} status changed from {$old_status} to red_tagged");
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log red tag creation
        logSystemAction($_SESSION['user_id'], 'redtag_created', 'inventory', "Created red tag {$control_no} for: {$item_description}");
        
        $_SESSION['success'] = "Red tag created successfully! Control No: {$control_no}";
        
        // Create notifications for MAIN_USER
        createMainUserNotificationsForRedTag($control_no, $item_description, $tagged_by, $red_tag_id);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error creating red tag: " . $e->getMessage());
        error_log("Error details: " . print_r([
            'control_no' => $control_no,
            'red_tag_no' => $red_tag_no,
            'asset_item_id' => $asset_item_id,
            'user_id' => $_SESSION['user_id'] ?? 'none'
        ], true));
        $_SESSION['error'] = "Error creating red tag: " . $e->getMessage();
    }
}

// Generate control number using tag_formats system with fallback (without incrementing)
$control_no = $control_no ?? getNextTagPreview('red_tag_control');
if (empty($control_no)) {
    // Fallback to manual generation if tag_formats not configured
    $control_no = 'RT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
$tagged_by = $tagged_by ?? ($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '');
$date_received = $date_received ?? date('m/d/Y');
$item_location = $item_location ?? $office_name;

// Build item description with serial number
$item_description = $item_description ?? $description;
if (!empty($serial_number)) {
    if (!empty($item_description)) {
        $item_description .= " (S/N: {$serial_number})";
    } else {
        $item_description = "S/N: {$serial_number}";
    }
}

$action = $action ?? ''; // Initialize action variable

// Use existing red_tag_no if already generated, otherwise generate for display (without incrementing)
if (empty($red_tag_no)) {
    $red_tag_no = getNextTagPreview('red_tag_no');
    if (empty($red_tag_no)) {
        // Fallback to manual generation if tag_formats not configured
        $red_tag_no = 'RTN-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Red Tag - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        
        .red-tag-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
        }
        
        .red-tag {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border: 4px solid #dc3545;
            padding: 20px;
            background: white;
            page-break-inside: avoid;
        }
        
        .red-tag-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .red-tag-main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ddd;
        }
        
        .red-tag-logo {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .red-tag-logo .header-logo {
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }
        
        .red-tag-government {
            text-align: center;
            flex: 1;
        }
        
        .red-tag-government .republic {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .red-tag-government .province {
            font-size: 13px;
            margin-bottom: 2px;
        }
        
        .red-tag-government .municipality {
            font-size: 13px;
            font-weight: 600;
        }
        
        .red-tag-number {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            color: #dc3545;
        }
        
        .red-tag-title {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
            margin: 0;
        }
        
        .red-tag-subtitle {
            font-size: 14px;
            color: #666;
            margin: 5px 0 0 0;
        }
        
        .red-tag-section {
            margin-bottom: 15px;
        }
        
        .red-tag-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .red-tag-label {
            font-weight: bold;
            width: 120px;
            flex-shrink: 0;
        }
        
        .red-tag-value {
            flex: 1;
            border-bottom: 1px dotted #999;
            min-height: 20px;
        }
        
        .red-tag-checkboxes {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .red-tag-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .red-tag-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .btn-custom {
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: var(--border-radius);
            border: 1px solid #dee2e6;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .no-print {
            display: block;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .page-header, .form-container, .no-print {
                display: none !important;
            }
            
            .red-tag-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .red-tag {
                margin: 0;
                max-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .red-tag-checkboxes {
                flex-direction: column;
                gap: 10px;
            }
            
            .red-tag-row {
                flex-direction: column;
            }
            
            .red-tag-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .red-tag-main-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .red-tag-logo {
                margin: 0 auto;
            }
            
            .red-tag-number {
                text-align: center;
            }
        }
    </style>
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Create Red Tag';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-tag-fill text-danger"></i> Create Red Tag
                    </h1>
                    <p class="text-muted mb-0">Generate 5S Red Tag for unserviceable items</p>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="print_redtag.php?control_no=<?php echo urlencode($control_no); ?>" class="dropdown-item" target="_blank">
                                    <i class="bi bi-printer"></i> Print Red Tag
                                </a>
                            </li>
                            <li>
                                <a href="unserviceable_assets.php" class="dropdown-item">
                                    <i class="bi bi-arrow-left"></i> Back to Assets
                                </a>
                            </li>
                            <li>
                                <a href="red_tags.php" class="dropdown-item">
                                    <i class="bi bi-list"></i> View All Red Tags
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a href="#" onclick="window.location.reload()" class="dropdown-item">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Form Section -->
        <div class="table-container mb-4">
            <div class="d-flex align-items-center mb-4">
                <div class="flex-grow-1">
                    <h5 class="mb-1"><i class="bi bi-tag"></i> Red Tag Information</h5>
                    <p class="text-muted mb-0">Fill in the details to generate a red tag</p>
                </div>
                <div class="ms-3">
                    <span class="badge bg-danger"><?php echo $red_tag_no; ?></span>
                </div>
            </div>
                    
                    <form method="POST" class="row g-3" enctype="multipart/form-data">
                        <div class="col-md-6">
                            <label class="form-label">Control No. <span class="text-muted">(Auto-generated)</span></label>
                            <input type="text" class="form-control" name="control_no" value="<?php echo $control_no; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Received <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="date_received" value="<?php echo $date_received; ?>" placeholder="mm/dd/yyyy" pattern="(0[1-9]|1[0-2])/(0[1-9]|[12][0-9]|3[01])/\d{4}" title="Please enter date in mm/dd/yyyy format" required>
                            <small class="text-muted">Format: mm/dd/yyyy</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagged by <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tagged_by" value="<?php echo $tagged_by; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="item_location" value="<?php echo $item_location; ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Item Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="item_description" rows="2" required><?php echo $item_description; ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason for Removal <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="removal_reason" rows="3" placeholder="Specify reason for removal..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Action <span class="text-danger">*</span></label>
                            <select class="form-select" name="action" id="actionSelect" required>
                                <option value="">Select Action</option>
                                <option value="repair">Repair</option>
                                <option value="recondition">Recondition</option>
                                <option value="dispose">Dispose</option>
                                <option value="relocate">Relocate</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="otherActionDiv" style="display: none;">
                            <label class="form-label">Specify Other Action <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="other_action" id="otherActionInput" placeholder="Enter specific action...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Red Tag Images <span class="text-muted">(Optional - Upload multiple images)</span></label>
                            <input type="file" class="form-control" name="redtag_images[]" id="redtagImages" multiple accept="image/*">
                            <small class="text-muted">You can upload multiple image files (JPG, PNG, GIF, etc.)</small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end" id="generateButtonDiv">
                            <button type="submit" name="generate_redtag" class="btn btn-danger btn-custom">
                                <i class="bi bi-tag"></i> Generate Red Tag
                            </button>
                        </div>
                    </form>
                </div>

        <!-- Red Tag Preview -->
        <div class="table-container">
            <div class="text-center mb-3">
                <h6 class="text-muted">Red Tag Preview</h6>
                <small class="text-muted">This is how your red tag will appear when printed</small>
            </div>
                    
                    <div class="red-tag">
                        <div class="red-tag-main-header">
                            <div class="red-tag-logo">
                                <?php 
                                $logo_path = '../img/trans_logo.png'; // default
                                if (!empty($system_settings['system_logo'])) {
                                    if (file_exists('../' . $system_settings['system_logo'])) {
                                        $logo_path = '../' . $system_settings['system_logo'];
                                    } elseif (file_exists($system_settings['system_logo'])) {
                                        $logo_path = $system_settings['system_logo'];
                                    }
                                }
                                ?>
                                <img src="<?php echo $logo_path; ?>" alt="LGU Logo" class="header-logo">
                            </div>
                            <div class="red-tag-government">
                                <div class="republic">Republic of the Philippines</div>
                                <div class="province">Province of Sorsogon</div>
                                <div class="municipality">Municipality of Pilar</div>
                            </div>
                            <div class="red-tag-number">
                                Red Tag No:<br>
                                <?php echo $red_tag_no; ?>
                            </div>
                        </div>
                        
                        <div class="red-tag-header">
                            <div class="red-tag-title">5S RED TAG</div>
                        </div>
                        
                        <div class="red-tag-section">
                            <div class="red-tag-row">
                                <div class="red-tag-label">Control No.:</div>
                                <div class="red-tag-value"><?php echo $control_no; ?></div>
                            </div>
                            <div class="red-tag-row">
                                <div class="red-tag-label">Date Received:</div>
                                <div class="red-tag-value"><?php echo date('F j, Y', strtotime($date_received)); ?></div>
                            </div>
                            <div class="red-tag-row">
                                <div class="red-tag-label">Tagged by:</div>
                                <div class="red-tag-value"><?php echo $tagged_by; ?></div>
                            </div>
                        </div>
                        
                        <div class="red-tag-section">
                            <div class="red-tag-row">
                                <div class="red-tag-label">Item Location:</div>
                                <div class="red-tag-value"><?php echo $item_location; ?></div>
                            </div>
                            <div class="red-tag-row">
                                <div class="red-tag-label">Description:</div>
                                <div class="red-tag-value"><?php echo $item_description; ?></div>
                            </div>
                            <div class="red-tag-row">
                                <div class="red-tag-label">Reason for Removal:</div>
                                <div class="red-tag-value"><?php echo $removal_reason ?? ''; ?></div>
                            </div>
                        </div>
                        
                        <div class="red-tag-section">
                            <div class="red-tag-label">Action:</div>
                            <div class="red-tag-checkboxes">
                                <div class="red-tag-checkbox">
                                    <input type="checkbox" <?php echo ($action ?? '') === 'repair' ? 'checked' : ''; ?>>
                                    <label>Repair</label>
                                </div>
                                <div class="red-tag-checkbox">
                                    <input type="checkbox" <?php echo ($action ?? '') === 'recondition' ? 'checked' : ''; ?>>
                                    <label>Recondition</label>
                                </div>
                                <div class="red-tag-checkbox">
                                    <input type="checkbox" <?php echo ($action ?? '') === 'dispose' ? 'checked' : ''; ?>>
                                    <label>Dispose</label>
                                </div>
                                <div class="red-tag-checkbox">
                                    <input type="checkbox" <?php echo ($action ?? '') === 'relocate' ? 'checked' : ''; ?>>
                                    <label>Relocate</label>
                                </div>
                                <?php if ($action === 'repair' || $action === 'recondition' || $action === 'dispose' || $action === 'relocate'): ?>
                                    <div class="red-tag-checkbox">
                                        <input type="checkbox">
                                        <label>Other</label>
                                    </div>
                                <?php else: ?>
                                    <div class="red-tag-checkbox">
                                        <input type="checkbox" checked>
                                        <label>Other: <?php echo htmlspecialchars($action ?? ''); ?></label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <?php require_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle "Other" action field visibility and requirement
        document.addEventListener('DOMContentLoaded', function() {
            const actionSelect = document.getElementById('actionSelect');
            const otherActionDiv = document.getElementById('otherActionDiv');
            const otherActionInput = document.getElementById('otherActionInput');
            
            function toggleOtherField() {
                if (actionSelect.value === 'other') {
                    otherActionDiv.style.display = 'block';
                    otherActionInput.setAttribute('required', 'required');
                } else {
                    otherActionDiv.style.display = 'none';
                    otherActionInput.removeAttribute('required');
                }
            }
            
            // Handle change event
            actionSelect.addEventListener('change', toggleOtherField);
            
            // Handle image preview
            const imageInput = document.getElementById('redtagImages');
            const imagePreview = document.getElementById('imagePreview');
            
            imageInput.addEventListener('change', function(e) {
                imagePreview.innerHTML = '';
                const files = e.target.files;
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'col-md-3 mb-2';
                            preview.innerHTML = `
                                <img src="${e.target.result}" class="img-fluid rounded border" style="max-height: 100px; width: 100%; object-fit: cover;" alt="${file.name}">
                                <small class="text-muted d-block text-center">${file.name}</small>
                            `;
                            imagePreview.appendChild(preview);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
// Function to create notifications for MAIN_USER when Red Tags are created
function createMainUserNotificationsForRedTag($control_no, $item_description, $tagged_by, $red_tag_id) {
    global $conn;
    
    // Get all MAIN_USER users
    $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND is_active = 1";
    $main_users_result = $conn->query($main_users_query);
    
    if ($main_users_result && $main_users_result->num_rows > 0) {
        while ($main_user = $main_users_result->fetch_assoc()) {
            $user_id = $main_user['id'];
            
            $title = "New Red Tag Created";
            $message = "A new Red Tag ({$control_no}) has been created by {$tagged_by} for: {$item_description}";
            $type = "warning";
            $related_id = $red_tag_id;
            $related_type = "red_tag";
            
            // Insert notification
            $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
            $stmt->execute();
        }
    }
}
?>
