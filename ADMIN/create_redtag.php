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

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php');

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
$asset_id = isset($_GET['asset_id']) ? intval($_GET['asset_id']) : 0;
$description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '';
$property_no = isset($_GET['property_no']) ? htmlspecialchars($_GET['property_no']) : '';
$inventory_tag = isset($_GET['inventory_tag']) ? htmlspecialchars($_GET['inventory_tag']) : '';
$acquisition_date = isset($_GET['acquisition_date']) ? htmlspecialchars($_GET['acquisition_date']) : '';
$value = isset($_GET['value']) ? floatval($_GET['value']) : 0;
$office_name = isset($_GET['office_name']) ? htmlspecialchars($_GET['office_name']) : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_redtag'])) {
    $control_no = trim($_POST['control_no'] ?? '');
    $date_received = trim($_POST['date_received'] ?? date('Y-m-d'));
    $tagged_by = trim($_POST['tagged_by'] ?? '');
    $item_location = trim($_POST['item_location'] ?? '');
    $item_description = trim($_POST['item_description'] ?? '');
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
    
    // Create red_tags table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `red_tags` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `control_no` varchar(50) NOT NULL,
        `red_tag_no` varchar(50) NOT NULL,
        `date_received` date NOT NULL,
        `tagged_by` varchar(100) NOT NULL,
        `item_location` varchar(255) NOT NULL,
        `item_description` text NOT NULL,
        `removal_reason` text NOT NULL,
        `action` varchar(50) NOT NULL,
        `office_id` int(11) DEFAULT NULL,
        `asset_id` int(11) DEFAULT NULL,
        `status` enum('pending','processed','disposed') DEFAULT 'pending',
        `created_by` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `control_no` (`control_no`),
        UNIQUE KEY `red_tag_no` (`red_tag_no`),
        KEY `office_id` (`office_id`),
        KEY `asset_id` (`asset_id`),
        KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($create_table_sql);
    
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
        error_log("Red Tag Debug - Data: control_no=$control_no, red_tag_no=$red_tag_no, asset_id=$asset_id, office_id=$office_id");
        
        // Insert into red_tags table
        $insert_sql = "INSERT INTO red_tags (control_no, red_tag_no, date_received, tagged_by, item_location, item_description, removal_reason, action, office_id, asset_id, created_by) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $bind_result = $insert_stmt->bind_param("ssssssssiii", $control_no, $red_tag_no, $date_received, $tagged_by, $item_location, $item_description, $removal_reason, $action, $office_id, $asset_id, $_SESSION['user_id']);
        if (!$bind_result) {
            throw new Exception("Bind failed: " . $insert_stmt->error);
        }
        
        $execute_result = $insert_stmt->execute();
        if (!$execute_result) {
            throw new Exception("Execute failed: " . $insert_stmt->error);
        }
        
        $insert_stmt->close();
        
        // Update asset_item status to 'red_tagged' if asset_id is provided
        if ($asset_id > 0) {
            $update_sql = "UPDATE asset_items SET status = 'red_tagged', last_updated = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Update prepare failed: " . $conn->error);
            }
            
            $update_bind = $update_stmt->bind_param("i", $asset_id);
            if (!$update_bind) {
                throw new Exception("Update bind failed: " . $update_stmt->error);
            }
            
            $update_execute = $update_stmt->execute();
            if (!$update_execute) {
                throw new Exception("Update execute failed: " . $update_stmt->error);
            }
            
            $update_stmt->close();
            
            // Log the asset status change
            logSystemAction($_SESSION['user_id'], 'asset_status_updated', 'inventory', "Asset ID {$asset_id} status changed to red_tagged");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log red tag creation
        logSystemAction($_SESSION['user_id'], 'redtag_created', 'inventory', "Created red tag {$control_no} for: {$item_description}");
        
        $_SESSION['success'] = "Red tag created successfully! Control No: {$control_no}";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error creating red tag: " . $e->getMessage());
        error_log("Error details: " . print_r([
            'control_no' => $control_no,
            'red_tag_no' => $red_tag_no,
            'asset_id' => $asset_id,
            'user_id' => $_SESSION['user_id'] ?? 'none'
        ], true));
        $_SESSION['error'] = "Error creating red tag: " . $e->getMessage();
    }
}

// Generate control number using tag_formats system with fallback
$control_no = $control_no ?? generateNextTag('red_tag_control');
if (empty($control_no)) {
    // Fallback to manual generation if tag_formats not configured
    $control_no = 'RT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
$tagged_by = $tagged_by ?? ($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '');
$date_received = $date_received ?? date('Y-m-d');
$item_location = $item_location ?? $office_name;
$item_description = $item_description ?? $description;
$action = $action ?? ''; // Initialize action variable

// Generate separate red tag number for header with fallback
$red_tag_no = generateNextTag('red_tag_no');
if (empty($red_tag_no)) {
    // Fallback to manual generation if tag_formats not configured
    $red_tag_no = 'RTN-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
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
            border-left: 4px solid #dc3545;
        }
        
        .form-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
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
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/sidebar-toggle.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header no-print">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Create Red Tag</h1>
                            <p class="text-muted mb-0">Generate 5S Red Tag for unserviceable items</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-primary btn-custom">
                                <i class="bi bi-printer"></i> Print Red Tag
                            </button>
                            <a href="unserviceable_assets.php" class="btn btn-outline-secondary btn-custom">
                                <i class="bi bi-arrow-left"></i> Back to Assets
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Input Form Section -->
                <div class="form-container no-print">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">Red Tag Information</h5>
                            <p class="text-muted mb-0">Fill in the details to generate a red tag</p>
                        </div>
                        <div class="ms-3">
                            <span class="badge bg-danger">RT-<?php echo date('Y'); ?></span>
                        </div>
                    </div>
                    
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Control No.</label>
                            <input type="text" class="form-control" name="control_no" value="<?php echo $control_no; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Received</label>
                            <input type="date" class="form-control" name="date_received" value="<?php echo $date_received; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagged by</label>
                            <input type="text" class="form-control" name="tagged_by" value="<?php echo $tagged_by; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Location</label>
                            <input type="text" class="form-control" name="item_location" value="<?php echo $item_location; ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Item Description</label>
                            <textarea class="form-control" name="item_description" rows="2" required><?php echo $item_description; ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason for Removal</label>
                            <textarea class="form-control" name="removal_reason" rows="3" placeholder="Specify reason for removal..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Action</label>
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
                            <label class="form-label">Specify Other Action</label>
                            <input type="text" class="form-control" name="other_action" id="otherActionInput" placeholder="Enter specific action...">
                        </div>
                        <div class="col-md-6 d-flex align-items-end" id="generateButtonDiv">
                            <button type="submit" name="generate_redtag" class="btn btn-danger btn-custom">
                                <i class="bi bi-tag"></i> Generate Red Tag
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Red Tag Preview -->
                <div class="red-tag-container">
                    <div class="text-center mb-3 no-print">
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
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle "Other" action field visibility
        document.addEventListener('DOMContentLoaded', function() {
            const actionSelect = document.getElementById('actionSelect');
            const otherActionDiv = document.getElementById('otherActionDiv');
            const generateButtonDiv = document.getElementById('generateButtonDiv');
            
            function toggleOtherField() {
                if (actionSelect.value === 'other') {
                    otherActionDiv.style.display = 'block';
                    generateButtonDiv.className = 'col-12 d-flex align-items-end mt-3';
                } else {
                    otherActionDiv.style.display = 'none';
                    generateButtonDiv.className = 'col-md-6 d-flex align-items-end';
                }
            }
            
            // Initial check
            toggleOtherField();
            
            // Handle change event
            actionSelect.addEventListener('change', toggleOtherField);
        });
    </script>
</body>
</html>
