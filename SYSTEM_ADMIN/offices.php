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
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log offices page access
logSystemAction($_SESSION['user_id'], 'access', 'offices', 'System admin accessed offices page');

// Handle CRUD operations
$message = '';
$message_type = '';

// Check for message from URL parameters
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// TOGGLE STATUS - Update office status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
    $office_id = intval($_POST['office_id']);
    $current_status = $_POST['current_status'] ?? 'inactive';
    $new_status = $current_status == 'active' ? 'inactive' : 'active';
    
    try {
        // Get office info before update
        $stmt = $conn->prepare("SELECT office_name, office_code FROM offices WHERE id = ?");
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $office = $result->fetch_assoc();
        
        if ($office) {
            // Update office status
            $stmt = $conn->prepare("UPDATE offices SET status = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $_SESSION['user_id'], $office_id);
            $stmt->execute();
            
            $message = "Office status updated to {$new_status}!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'office_status_updated', 'office_management', 
                "Updated office status: " . addslashes($office['office_name']) . " (" . addslashes($office['office_code']) . ") to {$new_status}");
        } else {
            $message = "Office not found.";
            $message_type = "danger";
        }
    } catch (Exception $e) {
        $message = "Error updating office status: " . $e->getMessage();
        $message_type = "danger";
    }
}

// IMPORT - Import offices from CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'import') {
    if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['import_file']['tmp_name'];
        $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] == '1';
        
        $imported_count = 0;
        $skipped_count = 0;
        $error_count = 0;
        $errors = [];
        
        try {
            // Open and read CSV file
            if (($handle = fopen($file, 'r')) !== FALSE) {
                // Get headers
                $headers = fgetcsv($handle, 1000, ',');
                if ($headers === FALSE) {
                    throw new Exception('Cannot read CSV headers');
                }
                
                // Normalize headers to lowercase
                $headers = array_map('strtolower', $headers);
                
                // Validate required columns
                if (!in_array('office_name', $headers) || !in_array('office_code', $headers)) {
                    throw new Exception('CSV must contain office_name and office_code columns');
                }
                
                // Get column indexes
                $office_name_idx = array_search('office_name', $headers);
                $office_code_idx = array_search('office_code', $headers);
                $address_idx = array_search('address', $headers);
                $state_idx = array_search('state', $headers);
                $postal_code_idx = array_search('postal_code', $headers);
                $country_idx = array_search('country', $headers);
                $phone_idx = array_search('phone', $headers);
                $email_idx = array_search('email', $headers);
                $capacity_idx = array_search('capacity', $headers);
                
                // Process each row
                $row_num = 1;
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $row_num++;
                    
                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        continue;
                    }
                    
                    $office_name = trim($data[$office_name_idx] ?? '');
                    $office_code = trim($data[$office_code_idx] ?? '');
                    $address = trim($data[$address_idx] ?? '');
                    $state = trim($data[$state_idx] ?? '');
                    $postal_code = trim($data[$postal_code_idx] ?? '');
                    $country = trim($data[$country_idx] ?? 'Philippines');
                    $phone = trim($data[$phone_idx] ?? '');
                    $email = trim($data[$email_idx] ?? '');
                    $capacity = intval($data[$capacity_idx] ?? 0);
                    
                    // Validation
                    if (empty($office_name) || empty($office_code)) {
                        $errors[] = "Row {$row_num}: Office name and code are required";
                        $error_count++;
                        continue;
                    }
                    
                    if (!preg_match('/^[A-Z]{1,5}$/', strtoupper($office_code))) {
                        $errors[] = "Row {$row_num}: Office code must be 1-5 uppercase letters";
                        $error_count++;
                        continue;
                    }
                    
                    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Row {$row_num}: Invalid email format";
                        $error_count++;
                        continue;
                    }
                    
                    if ($capacity < 0) {
                        $errors[] = "Row {$row_num}: Capacity must be a positive number";
                        $error_count++;
                        continue;
                    }
                    
                    $office_code = strtoupper($office_code);
                    
                    try {
                        // Check for duplicates if skip option is enabled
                        if ($skip_duplicates) {
                            $check_stmt = $conn->prepare("SELECT id FROM offices WHERE office_name = ? OR office_code = ?");
                            $check_stmt->bind_param("ss", $office_name, $office_code);
                            $check_stmt->execute();
                            $check_result = $check_stmt->get_result();
                            
                            if ($check_result->num_rows > 0) {
                                $skipped_count++;
                                continue;
                            }
                        }
                        
                        // Insert office
                        $stmt = $conn->prepare("INSERT INTO offices (office_name, office_code, address, state, postal_code, country, phone, email, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssssii", $office_name, $office_code, $address, $state, $postal_code, $country, $phone, $email, $capacity, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        $imported_count++;
                        
                        logSystemAction($_SESSION['user_id'], 'office_imported', 'office_management', "Imported office: {$office_name} ({$office_code})");
                        
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            if ($skip_duplicates) {
                                $skipped_count++;
                            } else {
                                $errors[] = "Row {$row_num}: Office name or code already exists";
                                $error_count++;
                            }
                        } else {
                            $errors[] = "Row {$row_num}: " . $e->getMessage();
                            $error_count++;
                        }
                    }
                }
                
                fclose($handle);
                
                // Create summary message
                $message_parts = [];
                if ($imported_count > 0) {
                    $message_parts[] = "{$imported_count} offices imported successfully";
                }
                if ($skipped_count > 0) {
                    $message_parts[] = "{$skipped_count} offices skipped (duplicates)";
                }
                if ($error_count > 0) {
                    $message_parts[] = "{$error_count} offices had errors";
                }
                
                $message = implode(', ', $message_parts);
                
                if ($imported_count > 0) {
                    $message_type = "success";
                } elseif ($skipped_count > 0) {
                    $message_type = "warning";
                } else {
                    $message_type = "danger";
                }
                
                // Log the import operation
                logSystemAction($_SESSION['user_id'], 'offices_import_attempt', 'office_management', 
                    "Import attempt: {$imported_count} imported, {$skipped_count} skipped, {$error_count} errors");
                
            } else {
                throw new Exception('Cannot open CSV file');
            }
            
        } catch (Exception $e) {
            $message = "Import error: " . $e->getMessage();
            $message_type = "danger";
        }
        
    } else {
        $message = "Please select a CSV file to import.";
        $message_type = "danger";
    }
}

// CREATE - Add new office
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $office_name = trim($_POST['office_name'] ?? '');
    $office_code = trim($_POST['office_code'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'Philippines');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    
    // Validation
    if (empty($office_name) || empty($office_code)) {
        $message = "Office name and code are required.";
        $message_type = "danger";
    } elseif (!preg_match('/^[A-Z]{1,5}$/', $office_code)) {
        $message = "Office code must be 1-5 uppercase letters.";
        $message_type = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif ($capacity < 0) {
        $message = "Capacity must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO offices (office_name, office_code, address, state, postal_code, country, phone, email, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssii", $office_name, $office_code, $address, $state, $postal_code, $country, $phone, $email, $capacity, $_SESSION['user_id']);
            $stmt->execute();
            
            $message = "Office added successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'office_added', 'office_management', "Added office: {$office_name} ({$office_code})");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Office name or code already exists.";
            } else {
                $message = "Error adding office: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// UPDATE - Edit office
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $office_name = trim($_POST['office_name'] ?? '');
    $office_code = trim($_POST['office_code'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'Philippines');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    
    // Validation
    if (empty($office_name) || empty($office_code)) {
        $message = "Office name and code are required.";
        $message_type = "danger";
    } elseif (!preg_match('/^[A-Z]{1,5}$/', $office_code)) {
        $message = "Office code must be 1-5 uppercase letters.";
        $message_type = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif ($capacity < 0) {
        $message = "Capacity must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE offices SET office_name = ?, office_code = ?, address = ?, state = ?, postal_code = ?, country = ?, phone = ?, email = ?, capacity = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssssssiii", $office_name, $office_code, $address, $state, $postal_code, $country, $phone, $email, $capacity, $_SESSION['user_id'], $id);
            $stmt->execute();
            
            // Log the action before redirect
            logSystemAction($_SESSION['user_id'], 'office_updated', 'office_management', "Updated office: {$office_name} ({$office_code})");
            
            // Redirect to clear edit parameters and show success message
            header("Location: offices.php?message=" . urlencode("Office updated successfully!") . "&type=success");
            exit();
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Office name or code already exists.";
            } else {
                $message = "Error updating office: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// DELETE - Delete office
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Get office info before deletion
        $stmt = $conn->prepare("SELECT office_name, office_code FROM offices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $office = $result->fetch_assoc();
        
        if ($office) {
            $stmt = $conn->prepare("DELETE FROM offices WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Office deleted successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'office_deleted', 'office_management', "Deleted office: {$office['office_name']} ({$office['office_code']})");
        }
    } catch (Exception $e) {
        $message = "Error deleting office: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get all offices
$offices = [];
try {
    $stmt = $conn->prepare("SELECT o.*, u1.username as created_by_name, u2.username as updated_by_name FROM offices o LEFT JOIN users u1 ON o.created_by = u1.id LEFT JOIN users u2 ON o.updated_by = u2.id ORDER BY o.office_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
} catch (Exception $e) {
    $message = "Error fetching offices: " . $e->getMessage();
    $message_type = "danger";
}

// Get office for editing
$edit_office = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM offices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_office = $result->fetch_assoc();
    } catch (Exception $e) {
        $message = "Error fetching office: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get system settings for theme
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback to default
    $system_settings['system_name'] = 'PIMS';
}

// Set page title for topbar
$page_title = 'Offices';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offices - <?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        }
        
        /* Sidebar Toggle Styles */
        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1051;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar-toggle:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .sidebar-toggle.sidebar-active {
            left: 300px;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .main-wrapper.sidebar-active {
            margin-left: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle.sidebar-active {
                left: 20px;
            }
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
        }
    </style>
</head>
<body>
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
                        <i class="bi bi-building"></i> Offices
                    </h1>
                    <p class="text-muted mb-0">Manage office departments for the LGU</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#importOfficesModal">
                            <i class="bi bi-upload"></i> Import
                        </button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOfficeModal">
                            <i class="bi bi-plus-circle"></i> Add Office
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offices Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count($offices); ?></div>
                            <div class="text-muted">Total Offices</div>
                            <small class="text-success">
                                <i class="bi bi-building"></i> 
                                Branches
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-building fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_filter($offices, fn($o) => !empty($o['status']) && $o['status'] == 'active')); ?></div>
                            <div class="text-muted">Active Offices</div>
                            <small class="text-success">Operational</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_filter($offices, fn($o) => !empty($o['status']) && $o['status'] == 'inactive')); ?></div>
                            <div class="text-muted">Inactive Offices</div>
                            <small class="text-warning">Disabled</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-pause-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo array_sum(array_column($offices, 'capacity')); ?></div>
                            <div class="text-muted">Total Capacity</div>
                            <small class="text-info">Personnel</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Offices Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-building"></i> Offices Management</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="officesTable">
                                <thead>
                                    <tr>
                                        <th>Office Name</th>
                                        <th>Code</th>
                                        <th>State</th>
                                        <th>Capacity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($offices as $office): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($office['office_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($office['address'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($office['office_code']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($office['state'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo number_format($office['capacity']); ?></span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="office_id" value="<?php echo $office['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo !empty($office['status']) && $office['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="status_<?php echo $office['id']; ?>" 
                                                               onchange="this.form.submit()"
                                                               <?php echo (!empty($office['status']) && $office['status'] == 'active') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="status_<?php echo $office['id']; ?>">
                                                            <span class="badge bg-<?php echo !empty($office['status']) && $office['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo !empty($office['status']) && $office['status'] == 'active' ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="window.location.href='offices.php?action=edit&id=<?php echo $office['id']; ?>'">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteOffice(<?php echo $office['id']; ?>, '<?php echo htmlspecialchars($office['office_name']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Office Modal -->
    <div class="modal fade" id="addOfficeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Office</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="office_name" class="form-label">Office Name *</label>
                                <input type="text" class="form-control" id="office_name" name="office_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="office_code" class="form-label">Office Code *</label>
                                <input type="text" class="form-control" id="office_code" name="office_code" 
                                       pattern="[A-Z]{1,5}" placeholder="e.g., HO" required>
                                <small class="form-text text-muted">1-5 uppercase letters</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2">Caloñgay Pilar, Sorsogon</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" value="Albay">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" value="4714">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" value="Philippines">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" 
                                       min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Office</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Import Offices Modal -->
    <div class="modal fade" id="importOfficesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Offices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Import Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Upload a CSV file with office data</li>
                                <li>Required columns: office_name, office_code</li>
                                <li>Optional columns: address, state, postal_code, country, phone, email, capacity</li>
                                <li>First row should contain column headers</li>
                                <li>Office codes must be unique and 1-5 uppercase letters</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <label for="import_file" class="form-label">CSV File *</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" 
                                   accept=".csv" required>
                            <small class="form-text text-muted">Select a CSV file to import offices</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="skip_duplicates" name="skip_duplicates" checked>
                                <label class="form-check-label" for="skip_duplicates">
                                    Skip duplicate offices (same name or code)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-upload"></i> Import Offices
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Office Modal -->
    <div class="modal fade" id="editOfficeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Office</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_office['id'] ?? ''); ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_office_name" class="form-label">Office Name *</label>
                                <input type="text" class="form-control" id="edit_office_name" name="office_name" 
                                       value="<?php echo htmlspecialchars($edit_office['office_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_office_code" class="form-label">Office Code *</label>
                                <input type="text" class="form-control" id="edit_office_code" name="office_code" 
                                       pattern="[A-Z]{1,5}" placeholder="e.g., HO" 
                                       value="<?php echo htmlspecialchars($edit_office['office_code'] ?? ''); ?>" required>
                                <small class="form-text text-muted">1-5 uppercase letters</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2"><?php echo htmlspecialchars($edit_office['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="edit_state" name="state" 
                                       value="<?php echo htmlspecialchars($edit_office['state'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="edit_postal_code" name="postal_code" 
                                       value="<?php echo htmlspecialchars($edit_office['postal_code'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="edit_country" name="country" 
                                       value="<?php echo htmlspecialchars($edit_office['country'] ?? 'Philippines'); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" 
                                       value="<?php echo htmlspecialchars($edit_office['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" 
                                       value="<?php echo htmlspecialchars($edit_office['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="edit_capacity" name="capacity" 
                                       min="0" value="<?php echo htmlspecialchars($edit_office['capacity'] ?? 0); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Office</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the office "<span id="deleteOfficeName"></span>"?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteConfirmBtn" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
</div> <!-- Close main wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
<?php require_once 'includes/sidebar-scripts.php'; ?>

// Fix modal backdrop issues
document.addEventListener('DOMContentLoaded', function() {
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
        logoutModal.addEventListener('show.bs.modal', function () {
            // Ensure proper backdrop
            document.body.classList.add('modal-open');
        });
        
        logoutModal.addEventListener('hidden.bs.modal', function () {
            // Clean up backdrop
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        });
        
        // Ensure cancel button works properly
        const cancelButton = logoutModal.querySelector('[data-bs-dismiss="modal"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = bootstrap.Modal.getInstance(logoutModal);
                if (modal) {
                    modal.hide();
                }
            });
        }
    }
});

    // Initialize DataTables
    $(document).ready(function() {
        $('#officesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'asc']],
            language: {
                search: "Search offices:",
                lengthMenu: "Show _MENU_ offices",
                info: "Showing _START_ to _END_ of _TOTAL_ offices",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });

    function deleteOffice(id, name) {
        document.getElementById('deleteOfficeName').textContent = name;
        document.getElementById('deleteConfirmBtn').setAttribute('data-office-id', id);
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    
    function confirmDelete() {
        const officeId = document.getElementById('deleteConfirmBtn').getAttribute('data-office-id');
        if (officeId) {
            window.location.href = `offices.php?action=delete&id=${officeId}`;
        }
    }
    
    // Auto-uppercase office codes
    document.getElementById('office_code').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
    
    document.getElementById('edit_office_code').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
    
    // Show edit modal if editing
    <?php if ($edit_office): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editOfficeModal'));
            editModal.show();
        });
    <?php endif; ?>
    
    function showAlert(message, type) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at the top of main content
        const mainContent = document.querySelector('.main-content');
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
</script>
</body>
</html>
