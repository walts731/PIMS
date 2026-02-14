<?php
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

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Create tags table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `tag_formats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tag_type` varchar(50) NOT NULL UNIQUE,
    `format_components` text DEFAULT NULL,
    `auto_increment` tinyint(1) DEFAULT 0,
    `digits` int(2) DEFAULT 4,
    `separator` varchar(10) DEFAULT '-',
    `current_number` int(11) DEFAULT 1,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_by` int(11) DEFAULT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
)";

$conn->query($create_table_sql);

// Check if format_components column exists and add it if not
$column_check = $conn->query("SHOW COLUMNS FROM tag_formats LIKE 'format_components'");
if ($column_check->num_rows === 0) {
    // Add the new column
    $conn->query("ALTER TABLE tag_formats ADD COLUMN format_components text DEFAULT NULL AFTER tag_type");
    
    // Remove old columns that are no longer needed
    $old_columns = ['format_pattern', 'increment_element', 'prefix', 'suffix'];
    foreach ($old_columns as $column) {
        $check = $conn->query("SHOW COLUMNS FROM tag_formats LIKE '$column'");
        if ($check->num_rows > 0) {
            $conn->query("ALTER TABLE tag_formats DROP COLUMN $column");
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_tag') {
            $tag_type = $_POST['tag_type'];
            $format_components = json_encode($_POST['components'] ?? []);
            $digits = (int)$_POST['digits'];
            $separator = $_POST['separator'];
            $status = $_POST['status'];
            
            // Debug: Log what we're saving
            error_log("Saving tag format for: $tag_type");
            error_log("Components: " . $format_components);
            error_log("Digits: $digits, Separator: '$separator', Status: $status");
            
            // Check if tag exists
            $check_sql = "SELECT id FROM tag_formats WHERE tag_type = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $tag_type);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing - always set auto_increment to 1
                $sql = "UPDATE tag_formats SET format_components = ?, auto_increment = 1, digits = ?, `separator` = ?, status = ?, updated_by = ? WHERE tag_type = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sissis", $format_components, $digits, $separator, $status, $_SESSION['user_id'], $tag_type);
                error_log("Updating existing tag: $tag_type");
            } else {
                // Insert new - always set auto_increment to 1
                $sql = "INSERT INTO tag_formats (tag_type, format_components, auto_increment, digits, `separator`, status, created_by) VALUES (?, ?, 1, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $tag_type, $format_components, $digits, $separator, $status, $_SESSION['user_id']);
                error_log("Inserting new tag: $tag_type");
            }
            
            if ($stmt->execute()) {
                error_log("Successfully saved tag format for: $tag_type");
                error_log("Rows affected: " . $stmt->affected_rows);
                logSystemAction($_SESSION['user_id'], 'Updated tag format', 'tags', "Tag type: $tag_type");
                $success_message = "Tag format saved successfully!";
                
                // Redirect to prevent form resubmission and refresh the page
                header("Location: tags.php?success=" . urlencode($success_message));
                exit();
            } else {
                error_log("Failed to save tag format: " . $stmt->error);
            }
            
            $stmt->close();
            $check_stmt->close();
        }
        
        if ($_POST['action'] === 'reset_counter') {
            $tag_type = $_POST['tag_type'];
            error_log("Resetting counter for: $tag_type");
            $sql = "UPDATE tag_formats SET current_number = 1, updated_by = ? WHERE tag_type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $_SESSION['user_id'], $tag_type);
            
            if ($stmt->execute()) {
                error_log("Successfully reset counter for: $tag_type");
                logSystemAction($_SESSION['user_id'], 'Reset tag counter', 'tags', "Tag type: $tag_type");
                $success_message = "Counter reset successfully!";
                
                // Redirect to prevent form resubmission
                header("Location: tags.php?success=" . urlencode($success_message));
                exit();
            } else {
                error_log("Failed to reset counter: " . $stmt->error);
            }
            
            $stmt->close();
        }
        
        if ($_POST['action'] === 'generate_preview') {
            $tag_type = $_POST['tag_type'];
            $components = $_POST['components'] ?? [];
            
            // Debug: Log received data
            error_log("Generate preview for tag_type: " . $tag_type);
            error_log("Components received: " . print_r($components, true));
            
            // If components is a string, decode it
            if (is_string($components)) {
                $components = json_decode($components, true) ?: [];
            }
            
            $digits = (int)$_POST['digits'];
            $separator = $_POST['separator'];
            
            // Debug: Log processed data
            error_log("Processed components: " . print_r($components, true));
            error_log("Digits: " . $digits);
            error_log("Separator: " . $separator);
            
            // Always use auto_increment = true for preview
            $preview = generateTagPreview($tag_type, $components, true, $digits, $separator);
            
            error_log("Generated preview: " . $preview);
            
            echo json_encode(['preview' => $preview]);
            exit;
        }
    }
}

// Get all tag formats
$tag_formats = [];
$result = $conn->query("SELECT * FROM tag_formats ORDER BY tag_type");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tag_formats[$row['tag_type']] = $row;
    }
}

// Default tag types
$tag_types = [
    'ics_no' => 'ICS Number',
    'property_no' => 'Property Number',
    'itr_no' => 'ITR Number',
    'par_no' => 'PAR Number',
    'ris_no' => 'RIS Number',
    'sai_no' => 'SAI Number',
    'code' => 'General Code',
    'red_tag_control' => 'Red Tag Control No',
    'red_tag_no' => 'Red Tag No'
];

function getFormatPattern($components, $defaultSeparator) {
    $parts = [];
    
    foreach ($components as $index => $component) {
        $separator = $component['separator'] ?? $defaultSeparator;
        
        switch ($component['type']) {
            case 'text':
                $parts[] = ($separator && $index > 0 ? $separator : '') . ($component['value'] ?? 'TEXT');
                break;
            case 'digits':
                $digits = $component['digits'] ?? 4;
                $parts[] = ($separator && $index > 0 ? $separator : '') . str_repeat('0', $digits);
                break;
            case 'month':
                $parts[] = ($separator && $index > 0 ? $separator : '') . 'MM';
                break;
            case 'year':
                $parts[] = ($separator && $index > 0 ? $separator : '') . 'YYYY';
                break;
            case 'form_code':
                $parts[] = ($separator && $index > 0 ? $separator : '') . 'FC';
                break;
            case 'office_code':
                $parts[] = ($separator && $index > 0 ? $separator : '') . 'OF';
                break;
            case 'category_code':
                $parts[] = ($separator && $index > 0 ? $separator : '') . 'CAT';
                break;
            case 'sub_category_code':
                $parts[] = ($separator && $index > 0 ? $separator : '') . 'SUB';
                break;
        }
    }
    
    return implode('', $parts);
}

function getFormCodeByTagType($tag_type) {
    global $conn;
    
    // Map tag types to form codes
    $tag_to_form_mapping = [
        'par_no' => 'PAR',
        'ics_no' => 'ICS', 
        'ris_no' => 'RIS',
        'itr_no' => 'ITR',
        'sai_no' => 'SAI'
    ];
    
    $form_code = $tag_to_form_mapping[$tag_type] ?? null;
    
    if ($form_code) {
        // Get numeric code from forms table
        $stmt = $conn->prepare("SELECT code FROM forms WHERE form_code = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("s", $form_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['code'];
        }
    }
    
    return null;
}

function getDynamicCode($code_type, $tag_type = null) {
    global $conn;
    
    switch ($code_type) {
        case 'office_code':
            // Return first active office code as example
            $stmt = $conn->prepare("SELECT office_code FROM offices WHERE status = 'active' LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row['office_code'];
            }
            break;
            
        case 'category_code':
            // Return first active asset category code as example
            $stmt = $conn->prepare("SELECT category_code FROM asset_categories WHERE status = 'active' LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row['category_code'];
            }
            break;
            
        case 'sub_category_code':
            // Return first active asset sub-category code as example
            $stmt = $conn->prepare("SELECT sub_category_code FROM asset_sub_categories WHERE status = 'active' LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row['sub_category_code'];
            }
            break;
            
        case 'form_code':
            return getFormCodeByTagType($tag_type);
    }
    
    return null;
}

function generateTagPreview($tag_type, $components, $auto_increment, $digits, $defaultSeparator) {
    global $conn;
    
    // Get current number for this tag type (always auto-increment)
    $current_number = 1;
    $result = $conn->query("SELECT current_number FROM tag_formats WHERE tag_type = '$tag_type'");
    if ($result && $row = $result->fetch_assoc()) {
        $current_number = $row['current_number'];
    }
    
    // Build the tag from components
    $parts = [];
    
    foreach ($components as $index => $component) {
        $separator = $component['separator'] ?? $defaultSeparator;
        
        switch ($component['type']) {
            case 'text':
                $parts[] = ($separator && $index > 0 ? $separator : '') . ($component['value'] ?? '');
                break;
            case 'digits':
                $component_digits = $component['digits'] ?? $digits; // Use component-specific digits or fallback to global
                // Always auto-increment digits
                $number = str_pad($current_number, $component_digits, '0', STR_PAD_LEFT);
                $parts[] = ($separator && $index > 0 ? $separator : '') . $number;
                break;
            case 'month':
                $parts[] = ($separator && $index > 0 ? $separator : '') . date('m');
                break;
            case 'year':
                $parts[] = ($separator && $index > 0 ? $separator : '') . date('Y');
                break;
            case 'form_code':
                // Get form code based on tag type
                $form_code = getFormCodeByTagType($tag_type);
                $parts[] = ($separator && $index > 0 ? $separator : '') . ($form_code ?: 'FC');
                break;
            case 'office_code':
                // Get office code
                $office_code = getDynamicCode('office_code');
                $parts[] = ($separator && $index > 0 ? $separator : '') . ($office_code ?: 'OF');
                break;
            case 'category_code':
                // Get category code
                $category_code = getDynamicCode('category_code');
                $parts[] = ($separator && $index > 0 ? $separator : '') . ($category_code ?: 'CAT');
                break;
            case 'sub_category_code':
                // Get sub-category code
                $sub_category_code = getDynamicCode('sub_category_code');
                $parts[] = ($separator && $index > 0 ? $separator : '') . ($sub_category_code ?: 'SUB');
                break;
        }
    }
    
    return implode('', $parts);
}

// Log page access
logSystemAction($_SESSION['user_id'], 'Accessed Tags Management', 'tags', 'tags.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags Management - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .tag-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .tag-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .preview-box {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            text-align: center;
            margin-top: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1572C6 0%, #4AB8E8 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .border-success {
            border-left: 4px solid #28a745 !important;
        }
        
        .border-secondary {
            border-left: 4px solid #6c757d !important;
        }
        
        .min-height-60 {
            min-height: 60px;
        }
        
        /* Sidebar Toggle Button Styles */
        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-toggle:hover {
            background: var(--primary-color-dark);
            transform: scale(1.05);
        }
        
        .sidebar-toggle.sidebar-active {
            left: 280px;
        }
        
        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Ensure sidebar is properly positioned */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            z-index: 1002;
            transition: left 0.3s ease;
            overflow: hidden;
        }
        
        .sidebar.active {
            left: 0;
        }
        
        /* Remove scrollbar from sidebar content */
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        /* Main content adjustments */
        .main-wrapper.sidebar-active {
            margin-left: 280px;
        }
        
        /* Component styling */
        .component-item {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 6px;
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        
        .component-badge {
            font-size: 0.8rem;
            padding: 6px 10px;
            min-width: 120px;
            text-align: left;
        }
        
        .component-separator {
            min-width: 100px;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Tags Management';
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
                        <i class="bi bi-tags"></i> Tags Management
                    </h1>
                    <p class="text-muted mb-0">Configure document number formats and auto-increment settings</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="exportTagFormats()">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="importTagFormats()">
                        <i class="bi bi-upload"></i> Import
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tag Configuration Cards -->
        <div class="row">
            <?php foreach ($tag_types as $tag_key => $tag_name): ?>
                <?php 
                $tag_config = isset($tag_formats[$tag_key]) ? $tag_formats[$tag_key] : [
                    'format_components' => '[]',
                    'auto_increment' => 0,
                    'digits' => 4,
                    'separator' => '-',
                    'current_number' => 1,
                    'status' => 'active'
                ];
                
                $components = json_decode($tag_config['format_components'], true);
                // Handle double-encoded JSON
                if (is_string($components)) {
                    $components = json_decode($components, true);
                }
                if (!is_array($components)) {
                    $components = [];
                }
                ?>
                
                <div class="col-lg-6 col-xl-4">
                    <div class="tag-card <?php echo isset($tag_formats[$tag_key]) ? 'border-success' : 'border-secondary'; ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($tag_name); ?>
                                <?php if (isset($tag_formats[$tag_key])): ?>
                                    <small class="text-success ms-2">
                                        <i class="bi bi-check-circle-fill"></i> Configured
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted ms-2">
                                        <i class="bi bi-plus-circle"></i> Not configured
                                    </small>
                                <?php endif; ?>
                            </h5>
                            <span class="status-badge status-<?php echo $tag_config['status']; ?>">
                                <?php echo ucfirst($tag_config['status']); ?>
                            </span>
                        </div>
                        
                        <!-- Tag Info -->
                        <div class="alert alert-info py-2 mb-3">
                            <small class="mb-0">
                                <strong>Format Pattern:</strong> 
                                <span class="font-monospace"><?php echo getFormatPattern($components, $tag_config['separator']); ?></span>
                                <br>
                                <strong>Next Tag:</strong> 
                                <span class="font-monospace text-success"><?php echo generateTagPreview($tag_key, $components, true, $tag_config['digits'], $tag_config['separator']); ?></span>
                                <br>
                                <strong>Next Number:</strong> <?php echo $tag_config['current_number']; ?>
                            </small>
                        </div>
                        
                        <form method="POST" class="tag-form" data-tag-type="<?php echo $tag_key; ?>">
                            <input type="hidden" name="action" value="save_tag">
                            <input type="hidden" name="tag_type" value="<?php echo $tag_key; ?>">
                            
                            <!-- Format Builder -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <?php echo isset($tag_formats[$tag_key]) ? 'Edit Format' : 'Create Format'; ?>
                                </label>
                                <div class="btn-group d-flex flex-wrap gap-2 mb-2" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'text')">
                                        <i class="bi bi-fonts"></i> TEXT
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'digits')">
                                        <i class="bi bi-123"></i> DIGITS
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'month')">
                                        <i class="bi bi-calendar-month"></i> MONTH
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'year')">
                                        <i class="bi bi-calendar-year"></i> YEAR
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'form_code')">
                                        <i class="bi bi-file-earmark-code"></i> FORM CODE
                                    </button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'office_code')">
                                        <i class="bi bi-building"></i> OFFICE
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'category_code')">
                                        <i class="bi bi-tags"></i> CATEGORY
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="addComponent('<?php echo $tag_key; ?>', 'sub_category_code')">
                                        <i class="bi bi-tag"></i> SUBCATEGORY
                                    </button>
                                </div>
                                
                                <!-- Components Display -->
                                <div id="components_<?php echo $tag_key; ?>" class="border rounded p-2 bg-light min-height-60">
                                    <?php if (empty($components)): ?>
                                        <span class="text-muted">Click buttons above to add components</span>
                                    <?php else: ?>
                                        <?php foreach ($components as $index => $component): ?>
                                            <div class="component-item mb-2" data-index="<?php echo $index; ?>">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-secondary component-badge">
                                                        <?php 
                                                        switch ($component['type']) {
                                                            case 'text':
                                                                echo '<i class="bi bi-fonts"></i> ' . htmlspecialchars($component['value']);
                                                                break;
                                                            case 'digits':
                                                                echo '<i class="bi bi-123"></i> DIGITS (' . ($component['digits'] ?? 4) . ')';
                                                                break;
                                                            case 'month':
                                                                echo '<i class="bi bi-calendar-month"></i> MONTH';
                                                                break;
                                                            case 'year':
                                                                echo '<i class="bi bi-calendar-year"></i> YEAR';
                                                                break;
                                                            case 'form_code':
                                                                echo '<i class="bi bi-file-earmark-code"></i> FORM CODE';
                                                                break;
                                                            case 'office_code':
                                                                echo '<i class="bi bi-building"></i> OFFICE';
                                                                break;
                                                            case 'category_code':
                                                                echo '<i class="bi bi-tags"></i> CATEGORY';
                                                                break;
                                                            case 'sub_category_code':
                                                                echo '<i class="bi bi-tag"></i> SUBCATEGORY';
                                                                break;
                                                        }
                                                        ?>
                                                    </span>
                                                    <?php if ($index > 0): ?>
                                                        <select class="form-select form-select-sm component-separator" style="width: auto;" onchange="updateComponentSeparator('<?php echo $tag_key; ?>', <?php echo $index; ?>, this.value)">
                                                            <option value="-" <?php echo ($component['separator'] ?? '-') == '-' ? 'selected' : ''; ?>>Dash (-)</option>
                                                            <option value="/" <?php echo ($component['separator'] ?? '-') == '/' ? 'selected' : ''; ?>>Slash (/)</option>
                                                            <option value="_" <?php echo ($component['separator'] ?? '-') == '_' ? 'selected' : ''; ?>>Underscore (_)</option>
                                                            <option value=" " <?php echo ($component['separator'] ?? '-') == ' ' ? 'selected' : ''; ?>>Space</option>
                                                            <option value="" <?php echo ($component['separator'] ?? '-') == '' ? 'selected' : ''; ?>>None</option>
                                                        </select>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeComponent('<?php echo $tag_key; ?>', <?php echo $index; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Hidden field to store components -->
                                <input type="hidden" name="components" id="components_input_<?php echo $tag_key; ?>" value='<?php echo htmlspecialchars(json_encode($components)); ?>'>
                            </div>
                            
                            <!-- Digits Configuration -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Digits</label>
                                    <select class="form-select" name="digits" onchange="generatePreview('<?php echo $tag_key; ?>')">
                                        <?php for ($i = 2; $i <= 8; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $tag_config['digits'] == $i ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> digits
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Separator</label>
                                    <select class="form-select" name="separator" onchange="generatePreview('<?php echo $tag_key; ?>')">
                                        <option value="-" <?php echo $tag_config['separator'] == '-' ? 'selected' : ''; ?>>Dash (-)</option>
                                        <option value="/" <?php echo $tag_config['separator'] == '/' ? 'selected' : ''; ?>>Slash (/)</option>
                                        <option value="_" <?php echo $tag_config['separator'] == '_' ? 'selected' : ''; ?>>Underscore (_)</option>
                                        <option value=" " <?php echo $tag_config['separator'] == ' ' ? 'selected' : ''; ?>>Space</option>
                                        <option value="" <?php echo $tag_config['separator'] == '' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Current Number -->
                            <div class="mb-3">
                                <label class="form-label">Current Number: <span class="badge bg-info"><?php echo $tag_config['current_number']; ?></span></label>
                                <button type="button" class="btn btn-sm btn-outline-warning ms-2" onclick="resetCounter('<?php echo $tag_key; ?>')">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </button>
                            </div>
                            
                            <!-- Status -->
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo $tag_config['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $tag_config['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <!-- Preview -->
                            <div class="preview-box" id="preview_<?php echo $tag_key; ?>">
                                Preview will appear here
                            </div>
                            
                            <!-- Actions -->
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo isset($tag_formats[$tag_key]) ? 'Update' : 'Create'; ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="generatePreview('<?php echo $tag_key; ?>')">
                                    <i class="bi bi-eye"></i> Preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/logout-modal.php'; ?>
<?php include 'includes/change-password-modal.php'; ?>
<?php include 'includes/sidebar-scripts.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let tagComponents = {};

// Initialize components from PHP data
<?php foreach ($tag_types as $tag_key => $tag_name): ?>
    <?php 
    $tag_config = isset($tag_formats[$tag_key]) ? $tag_formats[$tag_key] : [
        'format_components' => '[]',
        'auto_increment' => 0,
        'digits' => 4,
        'separator' => '-',
        'current_number' => 1,
        'status' => 'active'
    ];
    
    $components_for_js = json_decode($tag_config['format_components'], true);
    // Handle double-encoded JSON
    if (is_string($components_for_js)) {
        $components_for_js = json_decode($components_for_js, true);
    }
    if (!is_array($components_for_js)) {
        $components_for_js = [];
    }
    ?>
    tagComponents['<?php echo $tag_key; ?>'] = <?php echo json_encode($components_for_js); ?>;
<?php endforeach; ?>

function addComponent(tagType, componentType) {
    if (!tagComponents[tagType]) {
        tagComponents[tagType] = [];
    }
    
    let component = { type: componentType };
    
    // Set default separator for new components (except first one)
    if (tagComponents[tagType].length > 0) {
        component.separator = '-'; // Default separator for new components
    }
    
    if (componentType === 'text') {
        const textValue = prompt('Enter text value:');
        if (textValue === null || textValue.trim() === '') {
            return;
        }
        component.value = textValue.trim();
    } else if (componentType === 'digits') {
        const digitOptions = ['2', '3', '4', '5', '6', '7', '8'];
        const digitCount = prompt('Select number of digits (2-8):', '4');
        
        if (digitCount === null || digitCount.trim() === '') {
            return;
        }
        
        const digits = parseInt(digitCount);
        if (isNaN(digits) || digits < 2 || digits > 8) {
            alert('Please enter a valid number between 2 and 8');
            return;
        }
        
        component.digits = digits;
        
        // Update the digits select field to match
        const digitsSelect = document.querySelector(`form[data-tag-type="${tagType}"] select[name="digits"]`);
        if (digitsSelect) {
            digitsSelect.value = digits;
        }
    }
    
    tagComponents[tagType].push(component);
    updateComponentsDisplay(tagType);
    
    // Auto-generate preview immediately after adding component
    setTimeout(() => {
        generatePreview(tagType);
    }, 100);
}

function removeComponent(tagType, index) {
    if (tagComponents[tagType]) {
        tagComponents[tagType].splice(index, 1);
        updateComponentsDisplay(tagType);
        
        // Auto-generate preview immediately after removing component
        setTimeout(() => {
            generatePreview(tagType);
        }, 100);
    }
}

function updateComponentsDisplay(tagType) {
    const container = document.getElementById('components_' + tagType);
    const input = document.getElementById('components_input_' + tagType);
    const components = tagComponents[tagType] || [];
    
    if (components.length === 0) {
        container.innerHTML = '<span class="text-muted">Click buttons above to add components</span>';
    } else {
        let html = '';
        components.forEach((component, index) => {
            let displayText = '';
            switch (component.type) {
                case 'text':
                    displayText = '<i class="bi bi-fonts"></i> ' + component.value;
                    break;
                case 'digits':
                    displayText = '<i class="bi bi-123"></i> DIGITS (' + (component.digits || 4) + ')';
                    break;
                case 'month':
                    displayText = '<i class="bi bi-calendar-month"></i> MONTH';
                    break;
                case 'year':
                    displayText = '<i class="bi bi-calendar-year"></i> YEAR';
                    break;
                case 'form_code':
                    displayText = '<i class="bi bi-file-earmark-code"></i> FORM CODE';
                    break;
                case 'office_code':
                    displayText = '<i class="bi bi-building"></i> OFFICE';
                    break;
                case 'category_code':
                    displayText = '<i class="bi bi-tags"></i> CATEGORY';
                    break;
                case 'sub_category_code':
                    displayText = '<i class="bi bi-tag"></i> SUBCATEGORY';
                    break;
            }
            
            html += '<div class="component-item mb-2" data-index="' + index + '">' +
                    '<div class="d-flex align-items-center gap-2">' +
                    '<span class="badge bg-secondary component-badge">' + displayText + '</span>';
            
            // Add separator dropdown for components after the first one
            if (index > 0) {
                const currentSeparator = component.separator || '-';
                html += '<select class="form-select form-select-sm component-separator" style="width: auto;" onchange="updateComponentSeparator(\'' + tagType + '\', ' + index + ', this.value)">' +
                        '<option value="-"' + (currentSeparator === '-' ? ' selected' : '') + '>Dash (-)</option>' +
                        '<option value="/"' + (currentSeparator === '/' ? ' selected' : '') + '>Slash (/)</option>' +
                        '<option value="_"' + (currentSeparator === '_' ? ' selected' : '') + '>Underscore (_)</option>' +
                        '<option value=" "' + (currentSeparator === ' ' ? ' selected' : '') + '>Space</option>' +
                        '<option value=""' + (currentSeparator === '' ? ' selected' : '') + '>None</option>' +
                        '</select>';
            }
            
            html += '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeComponent(\'' + tagType + '\', ' + index + ')">' +
                    '<i class="bi bi-trash"></i>' +
                    '</button>' +
                    '</div>' +
                    '</div>';
        });
        container.innerHTML = html;
    }
    
    // Update hidden input
    input.value = JSON.stringify(components);
}

function updateComponentSeparator(tagType, componentIndex, separator) {
    if (tagComponents[tagType] && tagComponents[tagType][componentIndex]) {
        tagComponents[tagType][componentIndex].separator = separator;
        updateComponentsDisplay(tagType);
        
        // Auto-generate preview immediately after updating separator
        setTimeout(() => {
            generatePreview(tagType);
        }, 100);
    }
}

function generatePreview(tagType) {
    console.log('Generating preview for:', tagType);
    
    const form = document.querySelector(`form[data-tag-type="${tagType}"]`);
    if (!form) {
        console.error('Form not found for tag type:', tagType);
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'generate_preview');
    
    // Debug: Log form data
    for (let pair of formData.entries()) {
        console.log(pair[0], pair[1]);
    }
    
    fetch('tags.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Preview data:', data);
        const previewElement = document.getElementById('preview_' + tagType);
        if (previewElement) {
            previewElement.textContent = data.preview;
        } else {
            console.error('Preview element not found:', 'preview_' + tagType);
        }
    })
    .catch(error => {
        console.error('Error generating preview:', error);
        
        // Fallback: Generate client-side preview
        const previewElement = document.getElementById('preview_' + tagType);
        if (previewElement) {
            const components = tagComponents[tagType] || [];
            const separator = form.querySelector('select[name="separator"]').value;
            const digits = parseInt(form.querySelector('select[name="digits"]').value) || 4;
            
            let preview = generateClientPreview(components, separator, digits);
            previewElement.textContent = preview;
        }
    });
}

function generateClientPreview(components, defaultSeparator, digits) {
    const parts = [];
    const currentNumber = 1; // Default to 1 for client-side preview
    
    components.forEach((component, index) => {
        const separator = component.separator || defaultSeparator;
        
        switch (component.type) {
            case 'text':
                parts.push((separator && index > 0 ? separator : '') + (component.value || ''));
                break;
            case 'digits':
                const componentDigits = component.digits || digits;
                const number = String(currentNumber).padStart(componentDigits, '0');
                parts.push((separator && index > 0 ? separator : '') + number);
                break;
            case 'month':
                parts.push((separator && index > 0 ? separator : '') + String(new Date().getMonth() + 1).padStart(2, '0'));
                break;
            case 'year':
                parts.push((separator && index > 0 ? separator : '') + new Date().getFullYear().toString());
                break;
            case 'form_code':
                // For client-side preview, use placeholder
                parts.push((separator && index > 0 ? separator : '') + 'FC');
                break;
            case 'office_code':
                // For client-side preview, use placeholder
                parts.push((separator && index > 0 ? separator : '') + 'OF');
                break;
            case 'category_code':
                // For client-side preview, use placeholder
                parts.push((separator && index > 0 ? separator : '') + 'CAT');
                break;
            case 'sub_category_code':
                // For client-side preview, use placeholder
                parts.push((separator && index > 0 ? separator : '') + 'SUB');
                break;
        }
    });
    
    return parts.join('');
}

function resetCounter(tagType) {
    if (confirm('Are you sure you want to reset the counter to 1? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'reset_counter');
        formData.append('tag_type', tagType);
        
        fetch('tags.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

function exportTagFormats() {
    // TODO: Implement export functionality
    alert('Export functionality will be implemented');
}

function importTagFormats() {
    // TODO: Implement import functionality
    alert('Import functionality will be implemented');
}

// Generate initial previews
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($tag_types as $tag_key => $tag_name): ?>
        generatePreview('<?php echo $tag_key; ?>');
    <?php endforeach; ?>
});

// Auto-generate preview on form changes
document.querySelectorAll('.tag-form input, .tag-form select').forEach(element => {
    element.addEventListener('change', function() {
        const form = this.closest('.tag-form');
        const tagType = form.dataset.tagType;
        generatePreview(tagType);
    });
});

// Prevent form submission from affecting other forms
document.querySelectorAll('.tag-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        // Let the form submit normally - no need to prevent default
        console.log('Submitting form for tag type:', this.dataset.tagType);
    });
});

// Sidebar Toggle Functionality
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const mainWrapper = document.getElementById('mainWrapper');

function toggleSidebar() {
    sidebar.classList.toggle('active');
    sidebarOverlay.classList.toggle('active');
    mainWrapper.classList.toggle('sidebar-active');
    sidebarToggle.classList.toggle('sidebar-active');
}

function closeSidebar() {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    mainWrapper.classList.remove('sidebar-active');
    sidebarToggle.classList.remove('sidebar-active');
}

// Initialize sidebar toggle
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', toggleSidebar);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
}

// Close sidebar on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
        closeSidebar();
    }
});
</script>
</body>
</html>
