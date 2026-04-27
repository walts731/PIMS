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

// Log barangays page access
logSystemAction($_SESSION['user_id'], 'access', 'barangays', 'System admin accessed barangays page');

// Handle CRUD operations
$message = '';
$message_type = '';

// Check for message from URL parameters
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// TOGGLE STATUS - Update barangay status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
    $barangay_id = intval($_POST['barangay_id']);
    $current_status = $_POST['current_status'] ?? 'inactive';
    $new_status = $current_status == 'active' ? 'inactive' : 'active';
    
    try {
        // Get barangay info before update
        $stmt = $conn->prepare("SELECT office_name, office_code FROM offices WHERE id = ?");
        $stmt->bind_param("i", $barangay_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $barangay = $result->fetch_assoc();
        
        if ($barangay) {
            // Update barangay status
            $stmt = $conn->prepare("UPDATE offices SET status = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $_SESSION['user_id'], $barangay_id);
            $stmt->execute();
            
            $message = "Barangay status updated to {$new_status}!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'barangay_status_updated', 'barangay_management', 
                "Updated barangay status: " . addslashes($barangay['office_name']) . " (" . addslashes($barangay['office_code']) . ") to {$new_status}");
        } else {
            $message = "Barangay not found.";
            $message_type = "danger";
        }
    } catch (Exception $e) {
        $message = "Error updating barangay status: " . $e->getMessage();
        $message_type = "danger";
    }
}

// CREATE - Add new barangay
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $barangay_name = trim($_POST['barangay_name'] ?? '');
    $barangay_code = trim($_POST['barangay_code'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'Philippines');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    
    // Validation
    if (empty($barangay_name) || empty($barangay_code)) {
        $message = "Barangay name and code are required.";
        $message_type = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif ($capacity < 0) {
        $message = "Capacity must be a positive number.";
        $message_type = "danger";
    } elseif (!str_starts_with($barangay_code, 'B')) {
        $message = "Barangay code must start with 'B'.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO offices (office_name, office_code, address, state, postal_code, country, phone, email, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssii", $barangay_name, $barangay_code, $address, $state, $postal_code, $country, $phone, $email, $capacity, $_SESSION['user_id']);
            $stmt->execute();
            
            $message = "Barangay added successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'barangay_added', 'barangay_management', "Added barangay: {$barangay_name} ({$barangay_code})");
            
        } catch (Exception $e) {
            $message = "Error adding barangay: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// UPDATE - Edit barangay
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $barangay_name = trim($_POST['barangay_name'] ?? '');
    $barangay_code = trim($_POST['barangay_code'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'Philippines');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    
    // Validation
    if (empty($barangay_name) || empty($barangay_code)) {
        $message = "Barangay name and code are required.";
        $message_type = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif ($capacity < 0) {
        $message = "Capacity must be a positive number.";
        $message_type = "danger";
    } elseif (!str_starts_with($barangay_code, 'B')) {
        $message = "Barangay code must start with 'B'.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE offices SET office_name = ?, office_code = ?, address = ?, state = ?, postal_code = ?, country = ?, phone = ?, email = ?, capacity = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssssssii", $barangay_name, $barangay_code, $address, $state, $postal_code, $country, $phone, $email, $capacity, $_SESSION['user_id'], $id);
            $stmt->execute();
            
            // Log the action before redirect
            logSystemAction($_SESSION['user_id'], 'barangay_updated', 'barangay_management', "Updated barangay: {$barangay_name} ({$barangay_code})");
            
            // Redirect to clear edit parameters and show success message
            header("Location: barangays.php?message=" . urlencode("Barangay updated successfully!") . "&type=success");
            exit();
            
        } catch (Exception $e) {
            $message = "Error updating barangay: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Get all barangays (offices with codes starting with 'B')
$barangays = [];
try {
    $stmt = $conn->prepare("SELECT o.*, u1.username as created_by_name, u2.username as updated_by_name FROM offices o LEFT JOIN users u1 ON o.created_by = u1.id LEFT JOIN users u2 ON o.updated_by = u2.id WHERE o.office_code LIKE 'B%' ORDER BY o.office_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row;
    }
} catch (Exception $e) {
    $message = "Error fetching barangays: " . $e->getMessage();
    $message_type = "danger";
}

// Get barangay for editing
$edit_barangay = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM offices WHERE id = ? AND office_code LIKE 'B%'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_barangay = $result->fetch_assoc();
    } catch (Exception $e) {
        $message = "Error fetching barangay: " . $e->getMessage();
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
$page_title = 'Barangays';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangays - <?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?></title>
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
        
        .barangay-badge {
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
                        <i class="bi bi-geo-alt-fill"></i> Barangays
                    </h1>
                    <p class="text-muted mb-0">Manage barangay offices for the LGU</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="barangayActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="barangayActionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addBarangayModal">
                                    <i class="bi bi-plus-circle text-primary"></i> Add Barangay
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportBarangays()">
                                    <i class="bi bi-download text-success"></i> Export Barangays
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="refreshBarangays()">
                                    <i class="bi bi-arrow-clockwise text-warning"></i> Refresh Data
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="printBarangays()">
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
        
        <!-- Barangays Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Barangays List</h5>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="barangaysTable">
                    <thead>
                        <tr>
                            <th>Barangay Name</th>
                            <th>Code</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barangays as $barangay): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($barangay['office_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="barangay-badge">
                                        <?php echo htmlspecialchars($barangay['office_code']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($barangay['address'])): ?>
                                        <?php echo htmlspecialchars($barangay['address']); ?>
                                        <?php if (!empty($barangay['state'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($barangay['state']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No address</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($barangay['phone'])): ?>
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($barangay['phone']); ?>
                                        <?php if (!empty($barangay['email'])): ?>
                                            <br><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($barangay['email']); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No contact info</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="barangay_id" value="<?php echo $barangay['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo !empty($barangay['status']) && $barangay['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="status_<?php echo $barangay['id']; ?>" 
                                                   onchange="this.form.submit()"
                                                   <?php echo (!empty($barangay['status']) && $barangay['status'] == 'active') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="status_<?php echo $barangay['id']; ?>">
                                                <span class="status-badge status-<?php echo !empty($barangay['status']) && $barangay['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                                    <?php echo !empty($barangay['status']) && $barangay['status'] == 'active' ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </label>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="window.location.href='barangays.php?action=edit&id=<?php echo $barangay['id']; ?>'">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Barangay Modal -->
    <div class="modal fade" id="addBarangayModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Barangay</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="barangay_name" class="form-label">Barangay Name *</label>
                                <input type="text" class="form-control" id="barangay_name" name="barangay_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="barangay_code" class="form-label">Barangay Code *</label>
                                <input type="text" class="form-control" id="barangay_code" name="barangay_code" 
                                       placeholder="e.g., B001" required>
                                <small class="form-text text-muted">Must start with 'B'</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2">Pilar, Sorsogon</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" value="Sorsogon">
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
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g., (123) 456-7890">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="e.g., barangay@example.com">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" placeholder="Maximum capacity" min="0">
                            </div>
                            <div class="col-md-6">
                                <!-- Empty column for balance -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Add Barangay
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Barangay Modal -->
    <div class="modal fade" id="editBarangayModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Barangay</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_barangay['id'] ?? ''); ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_barangay_name" class="form-label">Barangay Name *</label>
                                <input type="text" class="form-control" id="edit_barangay_name" name="barangay_name" 
                                       value="<?php echo htmlspecialchars($edit_barangay['office_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_barangay_code" class="form-label">Barangay Code *</label>
                                <input type="text" class="form-control" id="edit_barangay_code" name="barangay_code" 
                                       placeholder="e.g., B001" 
                                       value="<?php echo htmlspecialchars($edit_barangay['office_code'] ?? ''); ?>" required>
                                <small class="form-text text-muted">Must start with 'B'</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2"><?php echo htmlspecialchars($edit_barangay['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="edit_state" name="state" 
                                       value="<?php echo htmlspecialchars($edit_barangay['state'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="edit_postal_code" name="postal_code" 
                                       value="<?php echo htmlspecialchars($edit_barangay['postal_code'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="edit_country" name="country" 
                                       value="<?php echo htmlspecialchars($edit_barangay['country'] ?? 'Philippines'); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" 
                                       value="<?php echo htmlspecialchars($edit_barangay['phone'] ?? ''); ?>" 
                                       placeholder="e.g., (123) 456-7890">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" 
                                       value="<?php echo htmlspecialchars($edit_barangay['email'] ?? ''); ?>" 
                                       placeholder="e.g., barangay@example.com">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="edit_capacity" name="capacity" 
                                       value="<?php echo htmlspecialchars($edit_barangay['capacity'] ?? ''); ?>" 
                                       placeholder="Maximum capacity" min="0">
                            </div>
                            <div class="col-md-6">
                                <!-- Empty column for balance -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Barangay
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Show edit modal if editing
            <?php if ($edit_barangay): ?>
                const editModal = new bootstrap.Modal(document.getElementById('editBarangayModal'));
                editModal.show();
            <?php endif; ?>
            
            // Initialize DataTable
            $('#barangaysTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: "Search barangays:",
                    lengthMenu: "Show _MENU_ barangays per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ barangays",
                    infoEmpty: "Showing 0 to 0 of 0 barangays",
                    infoFiltered: "(filtered from _MAX_ total barangays)",
                    zeroRecords: "No matching barangays found"
                }
            });
        });
        
        // Barangay management functions
        function refreshBarangays() {
            location.reload();
        }
        
        function exportBarangays() {
            let csv = 'Barangay Name,Code,Address,Phone,Email,Status\n';
            
            $('#barangaysTable tbody tr').each(function() {
                const $row = $(this);
                const cells = $row.find('td');
                const name = $(cells[0]).text().trim();
                const code = $(cells[1]).text().trim();
                const address = $(cells[2]).text().trim().replace(/\n/g, ' ');
                const contact = $(cells[3]).text().trim().replace(/\n/g, ' ');
                const status = $(cells[4]).text().trim();
                
                csv += '"' + name + '",' +
                       '"' + code + '",' +
                       '"' + address + '",' +
                       '"' + contact + '",' +
                       ',"' + status + '"\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'barangays_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function printBarangays() {
            window.print();
        }
    </script>
</body>
</html>
