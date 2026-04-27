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
        
        $imported_count = 0;
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
                if (!in_array('office_name', $headers) || !in_array('office_code', $headers) || !in_array('parent_office', $headers)) {
                    throw new Exception('CSV must contain office_name, office_code, and parent_office columns');
                }
                
                // Get column indexes
                $office_name_idx = array_search('office_name', $headers);
                $office_code_idx = array_search('office_code', $headers);
                $parent_office_idx = array_search('parent_office', $headers);
                
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
                    $parent_office_code = trim($data[$parent_office_idx] ?? '');
                    
                    // Validation
                    if (empty($office_name) || empty($office_code)) {
                        $errors[] = "Row {$row_num}: Office name and code are required";
                        $error_count++;
                        continue;
                    }
                    
                    // Validate office code format (numeric, 3-5 digits)
                    if (!preg_match('/^\d{3,5}$/', $office_code)) {
                        $errors[] = "Row {$row_num}: Office code must be 3-5 digits (e.g., 050, 123)";
                        $error_count++;
                        continue;
                    }
                    
                    // Validate parent office if provided
                    $branch_id = null;
                    if (!empty($parent_office_code)) {
                        // Check if parent office exists
                        $parent_stmt = $conn->prepare("SELECT id FROM offices WHERE office_code = ?");
                        $parent_stmt->bind_param("s", $parent_office_code);
                        $parent_stmt->execute();
                        $parent_result = $parent_stmt->get_result();
                        
                        if ($parent_result->num_rows === 0) {
                            $errors[] = "Row {$row_num}: Parent office code '{$parent_office_code}' not found";
                            $error_count++;
                            continue;
                        }
                        
                        $parent_row = $parent_result->fetch_assoc();
                        $branch_id = $parent_row['id'];
                    }
                    
                    
                    try {
                        // Insert office
                        $stmt = $conn->prepare("INSERT INTO offices (office_name, office_code, branch, created_by) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssii", $office_name, $office_code, $branch_id, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        $imported_count++;
                        
                        logSystemAction($_SESSION['user_id'], 'office_imported', 'office_management', "Imported office: {$office_name} ({$office_code})");
                        
                    } catch (Exception $e) {
                        $errors[] = "Row {$row_num}: " . $e->getMessage();
                        $error_count++;
                    }
                }
                
                fclose($handle);
                
                // Create summary message
                $message_parts = [];
                if ($imported_count > 0) {
                    $message_parts[] = "{$imported_count} offices imported successfully";
                }
                if ($error_count > 0) {
                    $message_parts[] = "{$error_count} offices had errors";
                }
                
                $message = implode(', ', $message_parts);
                
                if ($imported_count > 0) {
                    $message_type = "success";
                } else {
                    $message_type = "danger";
                }
                
                // Log the import operation
                logSystemAction($_SESSION['user_id'], 'offices_import_attempt', 'office_management', 
                    "Import attempt: {$imported_count} imported, {$error_count} errors");
                
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
    $branch = !empty($_POST['branch']) ? intval($_POST['branch']) : null;
    
    // Validation
    if (empty($office_name) || empty($office_code)) {
        $message = "Office name and code are required.";
        $message_type = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif ($capacity < 0) {
        $message = "Capacity must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO offices (office_name, office_code, address, state, postal_code, country, phone, email, capacity, branch, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssiii", $office_name, $office_code, $address, $state, $postal_code, $country, $phone, $email, $capacity, $branch, $_SESSION['user_id']);
            $stmt->execute();
            
            $message = "Office added successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'office_added', 'office_management', "Added office: {$office_name} ({$office_code})");
            
        } catch (Exception $e) {
            $message = "Error adding office: " . $e->getMessage();
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
    $branch = !empty($_POST['branch']) ? intval($_POST['branch']) : null;
    
    // Validation
    if (empty($office_name) || empty($office_code)) {
        $message = "Office name and code are required.";
        $message_type = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif ($capacity < 0) {
        $message = "Capacity must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE offices SET office_name = ?, office_code = ?, address = ?, state = ?, postal_code = ?, country = ?, phone = ?, email = ?, capacity = ?, branch = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssssssiiii", $office_name, $office_code, $address, $state, $postal_code, $country, $phone, $email, $capacity, $branch, $_SESSION['user_id'], $id);
            $stmt->execute();
            
            // Log the action before redirect
            logSystemAction($_SESSION['user_id'], 'office_updated', 'office_management', "Updated office: {$office_name} ({$office_code})");
            
            // Redirect to clear edit parameters and show success message
            header("Location: offices.php?message=" . urlencode("Office updated successfully!") . "&type=success");
            exit();
            
        } catch (Exception $e) {
            $message = "Error updating office: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}


// Get all offices with branch/parent info
$offices = [];
try {
    $stmt = $conn->prepare("SELECT o.*, u1.username as created_by_name, u2.username as updated_by_name, p.office_name as parent_office_name, p.office_code as parent_office_code, (SELECT COUNT(*) FROM offices WHERE branch = o.id) as child_count FROM offices o LEFT JOIN users u1 ON o.created_by = u1.id LEFT JOIN users u2 ON o.updated_by = u2.id LEFT JOIN offices p ON o.branch = p.id WHERE o.office_code NOT LIKE 'B%' AND o.office_code NOT LIKE 'L%' ORDER BY o.office_name");
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
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
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
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.125rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .office-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .modal-header {
            background: var(--primary-gradient);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 27, 169, 0.05);
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
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
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
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="officeActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="officeActionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addOfficeModal">
                                    <i class="bi bi-plus-circle text-primary"></i> Add Office
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importOfficesModal">
                                    <i class="bi bi-upload text-info"></i> Import Offices
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportOffices()">
                                    <i class="bi bi-download text-success"></i> Export Offices
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="refreshOffices()">
                                    <i class="bi bi-arrow-clockwise text-warning"></i> Refresh Data
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="printOffices()">
                                    <i class="bi bi-printer text-secondary"></i> Print List
                                </button>
                            </li>
                        </ul>
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
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Offices List</h5>
                </div>
            </div>
            
            <div class="table-responsive">
                            <table class="table table-hover" id="officesTable">
                                <thead>
                                    <tr>
                                        <th>Office Name</th>
                                        <th>Code</th>
                                        <th>Parent Office</th>
                                        <th>Sub-Offices</th>
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
                                                <span class="office-badge">
                                                    <?php echo htmlspecialchars($office['office_code']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($office['branch']): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-diagram-2"></i> <?php echo htmlspecialchars($office['parent_office_code'] . ' - ' . $office['parent_office_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">
                                                        <i class="bi bi-building"></i> Main Office
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-diagram-2"></i> <?php echo $office['child_count']; ?> sub-offices
                                                </span>
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
                                                            <span class="status-badge status-<?php echo !empty($office['status']) && $office['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                                                <?php echo !empty($office['status']) && $office['status'] == 'active' ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="window.location.href='offices.php?action=edit&id=<?php echo $office['id']; ?>'">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($office['child_count'] > 0): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            onclick="showSubOffices(<?php echo $office['id']; ?>, '<?php echo htmlspecialchars($office['office_name']); ?>')">
                                                        <i class="bi bi-diagram-2"></i>
                                                    </button>
                                                <?php endif; ?>
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
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Office</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                                       placeholder="e.g., HO" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="parent_office" class="form-label">Parent Office</label>
                            <select class="form-select" id="parent_office" name="branch">
                                <option value="">None (Main Office)</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>">
                                        <?php echo htmlspecialchars($office['office_code'] . ' - ' . $office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select a parent office if this is a sub-office</small>
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
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import Offices</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>CSV Format Preview:</strong>
                            <div class="table-responsive mt-2">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center bg-success text-white">office_name *</th>
                                            <th class="text-center bg-success text-white">office_code *</th>
                                            <th class="text-center bg-secondary text-white">parent_office</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Head Office</td>
                                            <td><code>HO</code></td>
                                            <td><em>empty</em></td>
                                        </tr>
                                        <tr>
                                            <td>North District</td>
                                            <td><code>ND</code></td>
                                            <td>HO</td>
                                        </tr>
                                        <tr>
                                            <td>South District</td>
                                            <td><code>SD</code></td>
                                            <td>HO</td>
                                        </tr>
                                        <tr>
                                            <td>East District</td>
                                            <td><code>ED</code></td>
                                            <td>HO</td>
                                        </tr>
                                        <tr>
                                            <td>West District</td>
                                            <td><code>WD</code></td>
                                            <td>HO</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> <strong>Notes:</strong> 
                                    Columns marked with * are required. Office codes must be unique and 1-5 uppercase letters.
                                    Parent office should contain the office_code of the parent office, or leave empty for main offices.
                                    First row should contain exact column headers as shown above.
                                </small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="import_file" class="form-label">CSV File *</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" 
                                   accept=".csv" required onchange="previewCSVFile()">
                            <small class="form-text text-muted">Select a CSV file to import offices</small>
                        </div>
                        
                        <!-- CSV Preview Section -->
                        <div id="csvPreview" class="mb-3" style="display: none;">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white py-2">
                                    <h6 class="mb-0"><i class="bi bi-eye"></i> CSV Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover" id="previewTable">
                                            <thead class="table-light">
                                                <tr id="previewHeaders"></tr>
                                            </thead>
                                            <tbody id="previewBody">
                                                <!-- Preview rows will be inserted here -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="alert alert-info mt-2" id="previewInfo">
                                        <i class="bi bi-info-circle"></i>
                                        <small id="previewMessage"></small>
                                    </div>
                                </div>
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
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Office</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                                       placeholder="e.g., HO" 
                                       value="<?php echo htmlspecialchars($edit_office['office_code'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_parent_office" class="form-label">Parent Office</label>
                            <select class="form-select" id="edit_parent_office" name="branch">
                                <option value="">None (Main Office)</option>
                                <?php foreach ($offices as $office): ?>
                                    <?php if (!isset($edit_office['id']) || $office['id'] != $edit_office['id']): // Prevent self-reference ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo (isset($edit_office['branch']) && $edit_office['branch'] == $office['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_code'] . ' - ' . $office['office_name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select a parent office if this is a sub-office</small>
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
    
    <!-- View Sub-Offices Modal -->
    <div class="modal fade" id="viewSubOfficesModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-diagram-2"></i> Sub-Offices for <span id="parentOfficeNameDisplay"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="subOfficesTable">
                            <thead>
                                <tr>
                                    <th>Office Name</th>
                                    <th>Office Code</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="subOfficesTableBody">
                                <!-- Sub-offices will be loaded here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
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
<?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>

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

        
        
    // Show edit modal if editing
    <?php if ($edit_office): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editOfficeModal'));
            editModal.show();
        });
    <?php endif; ?>
    
    // Office hierarchy functions
    const officesData = <?php echo json_encode($offices); ?>;
    
    // Show sub-offices for a parent office
    function showSubOffices(parentOfficeId, parentOfficeName) {
        document.getElementById('parentOfficeNameDisplay').textContent = parentOfficeName;
        
        // Filter sub-offices for this parent
        const subOffices = officesData.filter(office => office.branch == parentOfficeId);
        
        // Populate sub-offices table
        const tbody = document.getElementById('subOfficesTableBody');
        tbody.innerHTML = '';
        
        if (subOffices.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-building fs-1"></i>
                        <p class="mt-2">No sub-offices found for this office. Click "Add Office" to create a sub-office.</p>
                    </td>
                </tr>
            `;
        } else {
            subOffices.forEach(office => {
                const statusBadge = office.status === 'active' 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-secondary">Inactive</span>';
                
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${office.office_name}</strong></td>
                        <td><span class="badge bg-info">${office.office_code}</span></td>
                        <td>${office.address || '-'}</td>
                        <td>${office.phone || '-'}</td>
                        <td>${office.capacity || '0'}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="window.location.href='offices.php?action=edit&id=${office.id}'">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('viewSubOfficesModal'));
        modal.show();
    }
    
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
    
    // Export offices function
    function exportOffices() {
        // Get current offices data from PHP variable
        const officesData = <?php echo json_encode($offices); ?>;
        
        // Create CSV content with parent office
        let csvContent = "Office Name,Office Code,Parent Office\n";
        
        officesData.forEach(office => {
            const officeName = (office.office_name || '').replace(/"/g, '""');
            const officeCode = (office.office_code || '').replace(/"/g, '""');
            const parentOffice = office.parent_office_code && office.parent_office_name 
                ? `${office.parent_office_code} - ${office.parent_office_name}`.replace(/"/g, '""')
                : 'Main Office';
            csvContent += `"${officeName}","${officeCode}","${parentOffice}"\n`;
        });
        
        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'offices_export_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        // Show success message
        showAlert('Offices exported successfully!', 'success');
    }
    
    // Refresh offices function
    function refreshOffices() {
        // Show loading state
        showAlert('Refreshing offices data...', 'info');
        
        // Reload the page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    // Print offices function
    function printOffices() {
        // Get current offices data from PHP variable
        const officesData = <?php echo json_encode($offices); ?>;
        
        if (officesData.length === 0) {
            showAlert('No offices data to print', 'warning');
            return;
        }
        
        // Create a print preview window
        const printWindow = window.open('', '_blank', 'width=1000,height=800');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Offices - Print Preview</title>
                <style>
                    @page {
                        size: A4;
                        margin: 0.5in;
                    }
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        color: #333;
                        background: white;
                    }
                    
                    .preview-toolbar {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        z-index: 1000;
                        background: #191BA9;
                        color: white;
                        padding: 12px 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                    }
                    
                    .preview-toolbar .title {
                        font-weight: bold;
                        font-size: 14px;
                        display: flex;
                        align-items: center;
                    }
                    
                    .preview-toolbar .actions {
                        display: flex;
                        gap: 10px;
                    }
                    
                    .preview-toolbar button {
                        padding: 6px 12px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 12px;
                        transition: all 0.3s ease;
                    }
                    
                    .preview-toolbar .btn-print {
                        background: #28a745;
                        color: white;
                    }
                    
                    .preview-toolbar .btn-print:hover {
                        background: #218838;
                        transform: translateY(-1px);
                    }
                    
                    .preview-toolbar .btn-close {
                        background: #6c757d;
                        color: white;
                    }
                    
                    .preview-toolbar .btn-close:hover {
                        background: #5a6268;
                        transform: translateY(-1px);
                    }
                    
                    .print-container {
                        width: 100%;
                        max-width: 8.5in;
                        margin: 0 auto;
                        padding: 60px 20px 20px;
                        background: white;
                        min-height: 11in;
                        position: relative;
                    }
                    
                    @media screen {
                        body {
                            background: #525659;
                            padding: 0;
                        }
                        .print-container {
                            background: white;
                            box-shadow: 0 0 20px rgba(0,0,0,0.5);
                            margin: 60px auto 20px;
                        }
                    }
                    
                    @media print {
                        .no-print { display: none !important; }
                        body { background: white; margin: 0; padding: 0; }
                        .print-container { 
                            box-shadow: none; 
                            margin: 0 auto; 
                            padding: 20px;
                            width: 100%;
                        }
                    }
                    
                    .report-header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #191BA9;
                        padding-bottom: 15px;
                    }
                    
                    .report-header h1 {
                        color: #191BA9;
                        font-size: 24px;
                        font-weight: bold;
                        margin-bottom: 5px;
                        text-transform: uppercase;
                    }
                    
                    .report-header .subtitle {
                        color: #666;
                        font-size: 14px;
                        margin-bottom: 10px;
                    }
                    
                    .report-header .meta {
                        color: #333;
                        font-size: 12px;
                        font-weight: bold;
                    }
                    
                    .offices-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    
                    .offices-table th,
                    .offices-table td {
                        border: 1px solid #333;
                        padding: 10px;
                        text-align: left;
                        vertical-align: top;
                    }
                    
                    .offices-table th {
                        background-color: #f8f9fa;
                        font-weight: bold;
                        color: #333;
                        text-transform: uppercase;
                        font-size: 11px;
                    }
                    
                    .offices-table .office-name {
                        font-weight: bold;
                        min-width: 150px;
                    }
                    
                    .offices-table .office-code {
                        font-family: monospace;
                        background-color: #f8f9fa;
                        padding: 4px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        min-width: 100px;
                        text-align: center;
                    }
                    
                    .offices-table .address {
                        max-width: 200px;
                        word-wrap: break-word;
                        font-size: 11px;
                    }
                    
                    .offices-table .parent-office {
                        max-width: 200px;
                        word-wrap: break-word;
                        font-size: 11px;
                    }
                    
                    .offices-table .sub-offices {
                        text-align: center;
                        font-weight: bold;
                        min-width: 80px;
                    }
                    
                    .offices-table .status-active {
                        color: #28a745;
                        font-weight: bold;
                        text-align: center;
                        text-transform: uppercase;
                        font-size: 11px;
                    }
                    
                    .offices-table .status-inactive {
                        color: #6c757d;
                        font-weight: bold;
                        text-align: center;
                        text-transform: uppercase;
                        font-size: 11px;
                    }
                    
                    .report-footer {
                        margin-top: 40px;
                        padding-top: 20px;
                        border-top: 1px solid #ddd;
                        font-size: 11px;
                        color: #666;
                        text-align: center;
                    }
                    
                    .report-footer .summary {
                        font-weight: bold;
                        margin-bottom: 5px;
                        color: #333;
                    }
                    
                    .report-footer .user-info {
                        font-style: italic;
                    }
                    
                    /* Alternating row colors */
                    .offices-table tbody tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    
                    .offices-table tbody tr:hover {
                        background-color: #f0f8ff;
                    }
                </style>
            </head>
            <body>
                <div class="preview-toolbar no-print">
                    <div class="title">
                        <i class="bi bi-printer-fill me-2"></i>Offices Print Preview
                    </div>
                    <div class="actions">
                        <button onclick="window.print()" class="btn-print">
                            <i class="bi bi-printer me-1"></i>Print Report
                        </button>
                        <button onclick="window.close()" class="btn-close">
                            <i class="bi bi-x-lg me-1"></i>Close Preview
                        </button>
                    </div>
                </div>
                
                <div class="print-container">
                    <div class="report-header">
                        <h1>Offices Report</h1>
                        <div class="subtitle">Property and Inventory Management System</div>
                        <div class="meta">
                            Generated on: ${new Date().toLocaleString()} | Total Offices: ${officesData.length}
                        </div>
                    </div>
                    
                    <table class="offices-table">
                        <thead>
                            <tr>
                                <th>Office Name</th>
                                <th>Office Code</th>
                                <th>Parent Office</th>
                                <th>Sub-Offices</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${officesData.map((office, index) => {
                                const officeName = office.office_name || '';
                                const officeCode = office.office_code || '';
                                const parentOffice = office.branch ? 
                                    (office.parent_office_code + ' - ' + office.parent_office_name) : 
                                    'Main Office';
                                const subOffices = office.child_count || 0;
                                const status = office.status || 'inactive';
                                
                                const statusClass = status === 'active' ? 'status-active' : 'status-inactive';
                                
                                return `
                                    <tr>
                                        <td class="office-name">${officeName}</td>
                                        <td><span class="office-code">${officeCode}</span></td>
                                        <td class="parent-office">${parentOffice}</td>
                                        <td class="sub-offices">${subOffices}</td>
                                        <td class="${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                    
                    <div class="report-footer">
                        <div class="summary">
                            Report Summary: ${officesData.length} offices exported from PIMS Asset Management System
                        </div>
                        <div class="user-info">
                            Printed by: System Administrator | 
                            Date: ${new Date().toLocaleDateString()} | 
                            Page: <span class="page-number"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Bootstrap Icons -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
            </body>
            </html>
        `);
        printWindow.document.close();
        
        // Add page numbering
        printWindow.onload = function() {
            // Add page numbers
            const pageNumbers = printWindow.document.querySelectorAll('.page-number');
            pageNumbers.forEach((element, index) => {
                element.textContent = index + 1;
            });
        };
    }
    
    // CSV Preview function
    function previewCSVFile() {
        const fileInput = document.getElementById('import_file');
        const file = fileInput.files[0];
        
        if (!file) {
            hidePreview();
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const lines = text.split('\n').filter(line => line.trim() !== '');
            
            if (lines.length < 2) {
                showPreviewError('CSV file must have at least 2 rows (header + data)');
                return;
            }
            
            // Parse CSV
            const headers = parseCSVLine(lines[0]);
            const data = lines.slice(1, 6).map(line => parseCSVLine(line)); // Show first 5 rows
            
            // Validate required columns
            const requiredColumns = ['office_name', 'office_code'];
            const missingColumns = requiredColumns.filter(col => !headers.includes(col));
            
            if (missingColumns.length > 0) {
                showPreviewError(`Missing required columns: ${missingColumns.join(', ')}`);
                return;
            }
            
            // Validate parent_office column
            if (!headers.includes('parent_office')) {
                showPreviewError('Missing required column: parent_office');
                return;
            }
            
            // Show preview
            showPreview(headers, data, lines.length - 1);
        };
        
        reader.onerror = function() {
            showPreviewError('Error reading CSV file');
        };
        
        reader.readAsText(file);
    }
    
    function parseCSVLine(line) {
        const result = [];
        let current = '';
        let inQuotes = false;
        
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            
            if (char === '"') {
                inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
            } else {
                current += char;
            }
        }
        
        result.push(current.trim());
        return result;
    }
    
    function showPreview(headers, data, totalRows) {
        const previewDiv = document.getElementById('csvPreview');
        const headersRow = document.getElementById('previewHeaders');
        const previewBody = document.getElementById('previewBody');
        const previewMessage = document.getElementById('previewMessage');
        
        // Clear existing content
        headersRow.innerHTML = '';
        previewBody.innerHTML = '';
        
        // Add headers with proper styling
        headers.forEach(header => {
            const isRequired = ['office_name', 'office_code'].includes(header);
            const requiredIndicator = isRequired ? ' <span class="text-danger">*</span>' : '';
            headersRow.innerHTML += `<th class="text-center ${isRequired ? 'bg-light' : ''}">${header}${requiredIndicator}</th>`;
        });
        
        // Add data rows with better formatting
        data.forEach((row, rowIndex) => {
            const rowHtml = headers.map((header, index) => {
                const value = row[index] || '';
                const isRequired = ['office_name', 'office_code'].includes(header);
                const isMissing = isRequired && !value.trim();
                
                let cellClass = '';
                let cellContent = '';
                
                if (isMissing) {
                    cellClass = 'table-danger text-center';
                    cellContent = '<i class="bi bi-exclamation-triangle"></i> <em>Required field missing</em>';
                } else if (value.trim()) {
                    // Add appropriate styling based on column type
                    if (header === 'office_code') {
                        cellClass = 'text-center';
                        cellContent = `<code class="bg-light px-2 py-1 rounded">${value}</code>`;
                    } else if (header === 'parent_office') {
                        cellClass = 'text-center';
                        if (value.toLowerCase() === 'empty' || !value) {
                            cellContent = '<em>Main Office</em>';
                        } else {
                            cellContent = `<span class="badge bg-info">${value}</span>`;
                        }
                    } else {
                        cellContent = value;
                    }
                } else {
                    if (header === 'parent_office') {
                        cellClass = 'text-muted text-center';
                        cellContent = '<em>Main Office</em>';
                    } else {
                        cellClass = 'text-muted text-center';
                        cellContent = '<em>empty</em>';
                    }
                }
                
                return `<td class="${cellClass}">${cellContent}</td>`;
            }).join('');
            
            // Add row number
            const rowNumber = rowIndex + 1;
            previewBody.innerHTML += `<tr>
                <td class="text-muted text-center fw-bold">${rowNumber}</td>
                ${rowHtml}
            </tr>`;
        });
        
        // Add row number header
        headersRow.innerHTML = '<th class="text-center bg-dark text-white">#</th>' + headersRow.innerHTML;
        
        // Show preview info with better formatting
        const requiredCount = ['office_name', 'office_code'].length;
        const optionalCount = headers.length - requiredCount;
        previewMessage.innerHTML = `
            <strong>Preview Details:</strong><br>
            <i class="bi bi-table"></i> Showing ${data.length} of ${totalRows} total rows<br>
            <i class="bi bi-columns-gap"></i> ${headers.length} columns (${requiredCount} required, ${optionalCount} optional)<br>
            <i class="bi bi-exclamation-circle text-danger"></i> Required fields: office_name, office_code<br>
            <i class="bi bi-diagram-2 text-info"></i> Parent office: Use office_code of parent or leave empty for main office
        `;
        
        // Show preview section
        previewDiv.style.display = 'block';
    }
    
    function showPreviewError(message) {
        const previewDiv = document.getElementById('csvPreview');
        const previewMessage = document.getElementById('previewMessage');
        
        previewMessage.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ${message}`;
        previewMessage.className = 'text-danger';
        
        // Show preview section with error
        previewDiv.style.display = 'block';
        
        // Clear table
        document.getElementById('previewHeaders').innerHTML = '';
        document.getElementById('previewBody').innerHTML = '';
    }
    
    function hidePreview() {
        const previewDiv = document.getElementById('csvPreview');
        previewDiv.style.display = 'none';
    }
    
    // Show alert function
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
        
        // Insert after page header
        const pageHeader = document.querySelector('.page-header');
        pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
</script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
