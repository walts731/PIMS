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

// Log branches page access
logSystemAction($_SESSION['user_id'], 'access', 'branches', 'System admin accessed branches page');

// Handle CRUD operations
$message = '';
$message_type = '';

// Check for message from URL parameters
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// TOGGLE STATUS - Update branch status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
    $branch_id = intval($_POST['branch_id']);
    $current_status = $_POST['current_status'] ?? 'inactive';
    $new_status = $current_status == 'active' ? 'inactive' : 'active';
    
    try {
        // Get branch info before update
        $stmt = $conn->prepare("SELECT b.branch_name, b.branch_code, o.office_name FROM branches b LEFT JOIN offices o ON b.office_id = o.id WHERE b.id = ?");
        $stmt->bind_param("i", $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $branch = $result->fetch_assoc();
        
        if ($branch) {
            // Update branch status
            $stmt = $conn->prepare("UPDATE branches SET status = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $_SESSION['user_id'], $branch_id);
            $stmt->execute();
            
            $message = "Branch status updated to {$new_status}!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'branch_status_updated', 'branch_management', 
                "Updated branch status: " . addslashes($branch['branch_name']) . " (" . addslashes($branch['branch_code']) . ") from " . addslashes($branch['office_name']) . " to {$new_status}");
        } else {
            $message = "Branch not found.";
            $message_type = "danger";
        }
    } catch (Exception $e) {
        $message = "Error updating branch status: " . $e->getMessage();
        $message_type = "danger";
    }
}

// CREATE - Add new branch
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $office_id = intval($_POST['office_id']);
    $branch_name = trim($_POST['branch_name'] ?? '');
    $branch_code = trim($_POST['branch_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $head_personnel = trim($_POST['head_personnel'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    // Validation
    if (empty($branch_name) || empty($branch_code) || empty($office_id)) {
        $message = "Branch name, code, and office are required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO branches (office_id, branch_name, branch_code, description, head_personnel, contact_number, location, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssi", $office_id, $branch_name, $branch_code, $description, $head_personnel, $contact_number, $location, $_SESSION['user_id']);
            $stmt->execute();
            
            $message = "Branch added successfully!";
            $message_type = "success";
            
            // Get office name for logging
            $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
            $office_stmt->bind_param("i", $office_id);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            $office = $office_result->fetch_assoc();
            
            logSystemAction($_SESSION['user_id'], 'branch_added', 'branch_management', "Added branch: {$branch_name} ({$branch_code}) to " . ($office['office_name'] ?? 'Unknown Office'));
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Branch code already exists.";
            } else {
                $message = "Error adding branch: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// UPDATE - Edit branch
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $office_id = intval($_POST['office_id']);
    $branch_name = trim($_POST['branch_name'] ?? '');
    $branch_code = trim($_POST['branch_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $head_personnel = trim($_POST['head_personnel'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    // Validation
    if (empty($branch_name) || empty($branch_code) || empty($office_id)) {
        $message = "Branch name, code, and office are required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE branches SET office_id = ?, branch_name = ?, branch_code = ?, description = ?, head_personnel = ?, contact_number = ?, location = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("issssssii", $office_id, $branch_name, $branch_code, $description, $head_personnel, $contact_number, $location, $_SESSION['user_id'], $id);
            $stmt->execute();
            
            // Get office name for logging
            $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
            $office_stmt->bind_param("i", $office_id);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            $office = $office_result->fetch_assoc();
            
            logSystemAction($_SESSION['user_id'], 'branch_updated', 'branch_management', "Updated branch: {$branch_name} ({$branch_code}) in " . ($office['office_name'] ?? 'Unknown Office'));
            
            // Redirect to clear edit parameters and show success message
            header("Location: branches.php?message=" . urlencode("Branch updated successfully!") . "&type=success");
            exit();
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Branch code already exists.";
            } else {
                $message = "Error updating branch: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// DELETE - Delete branch
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Get branch info before deletion
        $stmt = $conn->prepare("SELECT b.branch_name, b.branch_code, o.office_name FROM branches b LEFT JOIN offices o ON b.office_id = o.id WHERE b.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $branch = $result->fetch_assoc();
        
        if ($branch) {
            $stmt = $conn->prepare("DELETE FROM branches WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Branch deleted successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'branch_deleted', 'branch_management', "Deleted branch: {$branch['branch_name']} ({$branch['branch_code']}) from " . $branch['office_name']);
        }
    } catch (Exception $e) {
        $message = "Error deleting branch: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get all branches with office information
$branches = [];
try {
    $stmt = $conn->prepare("SELECT b.*, o.office_name, o.office_code, u1.username as created_by_name, u2.username as updated_by_name FROM branches b LEFT JOIN offices o ON b.office_id = o.id LEFT JOIN users u1 ON b.created_by = u1.id LEFT JOIN users u2 ON b.updated_by = u2.id ORDER BY o.office_name, b.branch_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
} catch (Exception $e) {
    $message = "Error fetching branches: " . $e->getMessage();
    $message_type = "danger";
}

// Get all offices for dropdown
$offices = [];
try {
    $stmt = $conn->prepare("SELECT id, office_name, office_code FROM offices WHERE status = 'active' ORDER BY office_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
} catch (Exception $e) {
    $message = "Error fetching offices: " . $e->getMessage();
    $message_type = "danger";
}

// Get branch for editing
$edit_branch = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_branch = $result->fetch_assoc();
    } catch (Exception $e) {
        $message = "Error fetching branch: " . $e->getMessage();
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
$page_title = 'Branches';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branches - <?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?></title>
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
                        <i class="bi bi-diagram-3"></i> Branches
                    </h1>
                    <p class="text-muted mb-0">Manage branches under different offices</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                        <i class="bi bi-plus-circle"></i> Add Branch
                    </button>
                </div>
            </div>
        </div>

        <!-- Branches Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count($branches); ?></div>
                            <div class="text-muted">Total Branches</div>
                            <small class="text-success">
                                <i class="bi bi-diagram-3"></i> 
                                All Offices
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-diagram-3 fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_filter($branches, fn($b) => !empty($b['status']) && $b['status'] == 'active')); ?></div>
                            <div class="text-muted">Active Branches</div>
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
                            <div class="stats-number"><?php echo count(array_filter($branches, fn($b) => !empty($b['status']) && $b['status'] == 'inactive')); ?></div>
                            <div class="text-muted">Inactive Branches</div>
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
                            <div class="stats-number"><?php echo count($offices); ?></div>
                            <div class="text-muted">Total Offices</div>
                            <small class="text-info">Parent Units</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-building fs-1"></i>
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
        
        <!-- Branches Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Branches Management</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="branchesTable">
                                <thead>
                                    <tr>
                                        <th>Branch Name</th>
                                        <th>Code</th>
                                        <th>Office</th>
                                        <th>Head Personnel</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($branches as $branch): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong>
                                                <?php if (!empty($branch['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($branch['description']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($branch['location'])): ?>
                                                    <br><small class="text-info"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($branch['location']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($branch['branch_code']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($branch['office_name'] ?? 'Unknown'); ?></span>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($branch['office_code'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <?php if (!empty($branch['head_personnel'])): ?>
                                                    <?php echo htmlspecialchars($branch['head_personnel']); ?>
                                                    <?php if (!empty($branch['contact_number'])): ?>
                                                        <br><small class="text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($branch['contact_number']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="branch_id" value="<?php echo $branch['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo !empty($branch['status']) && $branch['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="status_<?php echo $branch['id']; ?>" 
                                                               onchange="this.form.submit()"
                                                               <?php echo (!empty($branch['status']) && $branch['status'] == 'active') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="status_<?php echo $branch['id']; ?>">
                                                            <span class="badge bg-<?php echo !empty($branch['status']) && $branch['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo !empty($branch['status']) && $branch['status'] == 'active' ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="window.location.href='branches.php?action=edit&id=<?php echo $branch['id']; ?>'">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteBranch(<?php echo $branch['id']; ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')">
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
    
    <!-- Add Branch Modal -->
    <div class="modal fade" id="addBranchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="office_id" class="form-label">Office *</label>
                                <select class="form-select" id="office_id" name="office_id" required>
                                    <option value="">Select Office</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>"><?php echo htmlspecialchars($office['office_name']); ?> (<?php echo htmlspecialchars($office['office_code']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="branch_code" class="form-label">Branch Code *</label>
                                <input type="text" class="form-control" id="branch_code" name="branch_code" required>
                                <div class="form-text">Unique code for this branch</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="branch_name" class="form-label">Branch Name *</label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="head_personnel" class="form-label">Head Personnel</label>
                                <input type="text" class="form-control" id="head_personnel" name="head_personnel">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Ground Floor, Main Building">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Branch Modal -->
    <?php if ($edit_branch): ?>
    <div class="modal fade" id="editBranchModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $edit_branch['id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_office_id" class="form-label">Office *</label>
                                <select class="form-select" id="edit_office_id" name="office_id" required>
                                    <option value="">Select Office</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo $edit_branch['office_id'] == $office['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_name']); ?> (<?php echo htmlspecialchars($office['office_code']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_branch_code" class="form-label">Branch Code *</label>
                                <input type="text" class="form-control" id="edit_branch_code" name="branch_code" 
                                       value="<?php echo htmlspecialchars($edit_branch['branch_code']); ?>" required>
                                <div class="form-text">Unique code for this branch</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_branch_name" class="form-label">Branch Name *</label>
                                <input type="text" class="form-control" id="edit_branch_name" name="branch_name" 
                                       value="<?php echo htmlspecialchars($edit_branch['branch_name']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2"><?php echo htmlspecialchars($edit_branch['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_head_personnel" class="form-label">Head Personnel</label>
                                <input type="text" class="form-control" id="edit_head_personnel" name="head_personnel" 
                                       value="<?php echo htmlspecialchars($edit_branch['head_personnel'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="edit_contact_number" name="contact_number" 
                                       value="<?php echo htmlspecialchars($edit_branch['contact_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="edit_location" name="location" 
                                       value="<?php echo htmlspecialchars($edit_branch['location'] ?? ''); ?>" 
                                       placeholder="e.g., Ground Floor, Main Building">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-trash fs-1 text-danger"></i>
                    </div>
                    <h6 class="text-center mb-3">Are you sure you want to delete this branch?</h6>
                    <p class="text-muted text-center mb-0">
                        <strong id="deleteBranchName"></strong><br>
                        This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#branchesTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[2, 'asc'], [0, 'asc']], // Order by Office, then Branch Name
            language: {
                search: "Search branches:",
                lengthMenu: "Show _MENU_ branches per page",
                info: "Showing _START_ to _END_ of _TOTAL_ branches",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
        
        // Auto-uppercase branch codes
        $('#branch_code, #edit_branch_code').on('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Show edit modal if editing
        <?php if ($edit_branch): ?>
        $('#editBranchModal').modal('show');
        <?php endif; ?>
    });
    
    function deleteBranch(id, name) {
        $('#deleteBranchName').text(name);
        $('#confirmDeleteBtn').data('id', id);
        $('#deleteModal').modal('show');
    }
    
    $('#confirmDeleteBtn').click(function() {
        var id = $(this).data('id');
        window.location.href = 'branches.php?action=delete&id=' + id;
    });
    </script>
    
    <?php require_once 'includes/logout-modal.php'; ?>
</body>
</html>
