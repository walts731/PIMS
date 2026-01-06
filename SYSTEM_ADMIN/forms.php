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

// Create forms table if not exists
$table_check = $conn->query("SHOW TABLES LIKE 'forms'");
if ($table_check->num_rows === 0) {
    $create_table_sql = "
        CREATE TABLE `forms` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `form_code` varchar(50) NOT NULL UNIQUE,
            `form_title` varchar(200) NOT NULL,
            `description` text DEFAULT NULL,
            `header_image` varchar(255) DEFAULT NULL,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_by` int(11) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_form_code` (`form_code`),
            KEY `idx_status` (`status`),
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->query($create_table_sql);
} else {
    // Check if header_image column exists and add it if not
    $column_check = $conn->query("SHOW COLUMNS FROM forms LIKE 'header_image'");
    if ($column_check->num_rows === 0) {
        $conn->query("ALTER TABLE forms ADD COLUMN header_image varchar(255) DEFAULT NULL AFTER description");
    }
}

// Create par_form table if not exists
$table_check = $conn->query("SHOW TABLES LIKE 'par_form'");
if ($table_check->num_rows === 0) {
    $create_table_sql = "
        CREATE TABLE `par_form` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `form_id` int(11) NOT NULL,
            `office_id` int(11) DEFAULT NULL,
            `received_by_name` varchar(200) DEFAULT NULL,
            `issued_by_name` varchar(200) DEFAULT NULL,
            `position_office_left` varchar(200) DEFAULT NULL,
            `position_office_right` varchar(200) DEFAULT NULL,
            `header_image` varchar(255) DEFAULT NULL,
            `entity_name` varchar(200) DEFAULT NULL,
            `fund_cluster` varchar(100) DEFAULT NULL,
            `par_no` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `date_received_left` date DEFAULT NULL,
            `date_received_right` date DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_form_id` (`form_id`),
            KEY `idx_office_id` (`office_id`),
            KEY `idx_par_no` (`par_no`),
            FOREIGN KEY (`form_id`) REFERENCES `forms`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->query($create_table_sql);
}

// Create par_items table if not exists
$table_check = $conn->query("SHOW TABLES LIKE 'par_items'");
if ($table_check->num_rows === 0) {
    $create_table_sql = "
        CREATE TABLE `par_items` (
            `item_id` int(11) NOT NULL AUTO_INCREMENT,
            `form_id` int(11) NOT NULL,
            `asset_id` int(11) DEFAULT NULL,
            `quantity` decimal(10,2) DEFAULT NULL,
            `unit` varchar(50) DEFAULT NULL,
            `description` text DEFAULT NULL,
            `property_no` varchar(100) DEFAULT NULL,
            `date_acquired` date DEFAULT NULL,
            `unit_price` decimal(10,2) DEFAULT NULL,
            `amount` decimal(10,2) DEFAULT NULL,
            PRIMARY KEY (`item_id`),
            KEY `idx_form_id` (`form_id`),
            KEY `idx_asset_id` (`asset_id`),
            FOREIGN KEY (`form_id`) REFERENCES `par_form`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->query($create_table_sql);
}

// Insert default forms if none exist
$result = $conn->query("SELECT COUNT(*) as count FROM forms");
if ($result->fetch_assoc()['count'] == 0) {
    $default_forms = [
        ['PAR', 'Property Acknowledgement Receipt', 'Form for acknowledging receipt of government property'],
        ['ICS', 'Inventory Custodian Slip', 'Form for transferring accountability of property'],
        ['RIS', 'Requisition and Issue Slip', 'Form for requesting and issuing supplies'],
        ['JO', 'Job Order', 'Form for requesting services or repairs'],
        ['PO', 'Purchase Order', 'Form for procuring items and services']
    ];
    
    foreach ($default_forms as $form) {
        $stmt = $conn->prepare("INSERT INTO forms (form_code, form_title, description, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $form[0], $form[1], $form[2], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_form'])) {
        $form_code = strtoupper($_POST['form_code']);
        $form_title = $_POST['form_title'];
        $description = $_POST['description'];
        
        // Handle header image upload
        $header_image = null;
        if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/forms/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['header_image']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['header_image']['tmp_name'], $target_path)) {
                $header_image = $file_name;
            }
        }
        
        // Handle user ID - validate against database
        $user_id = null;
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            // Check if user actually exists in database
            $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $user_check->bind_param("i", $_SESSION['user_id']);
            $user_check->execute();
            $user_result = $user_check->get_result();
            if ($user_result->num_rows > 0) {
                $user_id = $_SESSION['user_id'];
            }
            $user_check->close();
        }
        
        // Build query dynamically based on user ID
        if ($user_id === null) {
            $sql = "INSERT INTO forms (form_code, form_title, description, header_image, created_by) VALUES (?, ?, ?, ?, NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $form_code, $form_title, $description, $header_image);
        } else {
            $sql = "INSERT INTO forms (form_code, form_title, description, header_image, created_by) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $form_code, $form_title, $description, $header_image, $user_id);
        }
        
        if ($stmt->execute()) {
            if ($user_id !== null) {
                logSystemAction($user_id, 'create', 'forms', "Created new form: $form_code - $form_title");
            }
            header('Location: forms.php?message=Form added successfully');
        } else {
            header('Location: forms.php?error=Failed to add form');
        }
        $stmt->close();
        exit();
    }
    
    if (isset($_POST['update_form'])) {
        $form_id = $_POST['form_id'];
        $form_code = strtoupper($_POST['form_code']);
        $form_title = $_POST['form_title'];
        $description = $_POST['description'];
        $status = $_POST['status'];
        
        // Handle header image upload
        $header_image = null;
        if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/forms/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['header_image']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['header_image']['tmp_name'], $target_path)) {
                $header_image = $file_name;
            }
        }
        
        // Get current header image if no new image uploaded
        if ($header_image === null) {
            $current_result = $conn->query("SELECT header_image FROM forms WHERE id = $form_id");
            $current_row = $current_result->fetch_assoc();
            $header_image = $current_row['header_image'];
        }
        
        // Handle user ID - validate against database
        $user_id = null;
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            // Check if user actually exists in database
            $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $user_check->bind_param("i", $_SESSION['user_id']);
            $user_check->execute();
            $user_result = $user_check->get_result();
            if ($user_result->num_rows > 0) {
                $user_id = $_SESSION['user_id'];
            }
            $user_check->close();
        }
        
        // Build query dynamically based on user ID
        if ($user_id === null) {
            $sql = "UPDATE forms SET form_code = ?, form_title = ?, description = ?, header_image = ?, status = ?, updated_by = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $form_code, $form_title, $description, $header_image, $status, $form_id);
        } else {
            $sql = "UPDATE forms SET form_code = ?, form_title = ?, description = ?, header_image = ?, status = ?, updated_by = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssii", $form_code, $form_title, $description, $header_image, $status, $user_id, $form_id);
        }
        
        if ($stmt->execute()) {
            if ($user_id !== null) {
                logSystemAction($user_id, 'update', 'forms', "Updated form: $form_code - $form_title");
            }
            header('Location: forms.php?message=Form updated successfully');
        } else {
            header('Location: forms.php?error=Failed to update form');
        }
        $stmt->close();
        exit();
    }
    
    if (isset($_POST['delete_form'])) {
        $form_id = $_POST['form_id'];
        
        $stmt = $conn->prepare("DELETE FROM forms WHERE id = ?");
        $stmt->bind_param("i", $form_id);
        
        if ($stmt->execute()) {
            logSystemAction($_SESSION['user_id'], 'delete', 'forms', "Deleted form with ID: $form_id");
            header('Location: forms.php?message=Form deleted successfully');
        } else {
            header('Location: forms.php?error=Failed to delete form');
        }
        $stmt->close();
        exit();
    }
}

// Get all forms
$forms = [];
$result = $conn->query("SELECT f.*, u.first_name, u.last_name FROM forms f LEFT JOIN users u ON f.created_by = u.id ORDER BY f.form_code");
while ($row = $result->fetch_assoc()) {
    $forms[] = $row;
}

// Create forms data for JavaScript
$forms_data_json = json_encode($forms);

// Get form statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM forms");
$stats['total_forms'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as active FROM forms WHERE status = 'active'");
$stats['active_forms'] = $result->fetch_assoc()['active'];

$result = $conn->query("SELECT COUNT(*) as inactive FROM forms WHERE status = 'inactive'");
$stats['inactive_forms'] = $result->fetch_assoc()['inactive'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms Management - PIMS</title>
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
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .form-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--accent-color);
        }
        
        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .form-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .form-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Forms Management';
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
                            <i class="bi bi-file-earmark-text"></i> Forms Management
                        </h1>
                        <p class="text-muted mb-0">Manage system forms and their configurations</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFormModal">
                            <i class="bi bi-plus"></i> Add New Form
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Message Display -->
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Forms</h6>
                                <h3 class="mb-0 text-primary"><?php echo $stats['total_forms']; ?></h3>
                            </div>
                            <div class="text-primary fs-1">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Active Forms</h6>
                                <h3 class="mb-0 text-success"><?php echo $stats['active_forms']; ?></h3>
                            </div>
                            <div class="text-success fs-1">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Inactive Forms</h6>
                                <h3 class="mb-0 text-warning"><?php echo $stats['inactive_forms']; ?></h3>
                            </div>
                            <div class="text-warning fs-1">
                                <i class="bi bi-pause-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Forms List -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-lg rounded-4">
                        <div class="card-header bg-primary text-white rounded-top-4">
                            <h6 class="mb-0">
                                <i class="bi bi-file-earmark-text"></i> System Forms
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($forms)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                                    <p class="text-muted mt-3">No forms found</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="formsTable" class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Form Code</th>
                                                <th>Form Title</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($forms as $form): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($form['form_code']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($form['form_title']); ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo !empty($form['description']) ? htmlspecialchars(substr($form['description'], 0, 100)) . (strlen($form['description']) > 100 ? '...' : '') : 'No description'; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge bg-<?php echo $form['status'] === 'active' ? 'success' : 'secondary'; ?> text-white">
                                                            <?php echo htmlspecialchars($form['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($form['first_name'] . ' ' . $form['last_name']); ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y H:i:s', strtotime($form['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="form-actions">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="viewForm(<?php echo $form['id']; ?>)">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-warning" onclick="editForm(<?php echo $form['id']; ?>)">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteForm(<?php echo $form['id']; ?>)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Form Modal -->
    <div class="modal fade" id="addFormModal" tabindex="-1" aria-labelledby="addFormModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="forms.php" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addFormModalLabel">
                            <i class="bi bi-plus"></i> Add New Form
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="form_code" class="form-label">Form Code *</label>
                            <input type="text" class="form-control" id="form_code" name="form_code" required maxlength="50" style="text-transform: uppercase;">
                            <div class="form-text">Unique code for the form (e.g., PAR, ICS, RIS)</div>
                        </div>
                        <div class="mb-3">
                            <label for="form_title" class="form-label">Form Title *</label>
                            <input type="text" class="form-control" id="form_title" name="form_title" required maxlength="200">
                            <div class="form-text">Full title of the form</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="form-text">Brief description of the form's purpose</div>
                        </div>
                        <div class="mb-3">
                            <label for="header_image" class="form-label">Header Image</label>
                            <input type="file" class="form-control" id="header_image" name="header_image" accept="image/*">
                            <div class="form-text">Upload header image for the form (optional)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_form" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Add Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Form Modal -->
    <div class="modal fade" id="editFormModal" tabindex="-1" aria-labelledby="editFormModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="forms.php" enctype="multipart/form-data">
                    <input type="hidden" id="edit_form_id" name="form_id">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="editFormModalLabel">
                            <i class="bi bi-pencil"></i> Edit Form
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_form_code" class="form-label">Form Code *</label>
                            <input type="text" class="form-control" id="edit_form_code" name="form_code" required maxlength="50" style="text-transform: uppercase;">
                        </div>
                        <div class="mb-3">
                            <label for="edit_form_title" class="form-label">Form Title *</label>
                            <input type="text" class="form-control" id="edit_form_title" name="form_title" required maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_header_image" class="form-label">Header Image</label>
                            <input type="file" class="form-control" id="edit_header_image" name="header_image" accept="image/*">
                            <div class="form-text">Upload new header image for form (optional - leave empty to keep current)</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status *</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_form" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Update Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        // Forms data from PHP
        const formsData = <?php echo $forms_data_json; ?>;
        
        $(document).ready(function() {
            // Initialize DataTables
            $('#formsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 0, width: '120px' },
                    { targets: 1, width: '200px' },
                    { targets: 2, width: '300px' },
                    { targets: 3, width: '100px' },
                    { targets: 4, width: '150px' },
                    { targets: 5, width: '150px' },
                    { targets: 6, width: '120px', orderable: false }
                ]
            });
        });
        
        // View form details
        function viewForm(formId) {
            // Redirect to form details page
            window.location.href = 'form_details.php?id=' + formId;
        }
        
        // Edit form
        function editForm(formId) {
            // Find the form data from the pre-populated array
            const form = formsData.find(f => f.id == formId);
            
            if (form) {
                $('#edit_form_id').val(form.id);
                $('#edit_form_code').val(form.form_code);
                $('#edit_form_title').val(form.form_title);
                $('#edit_description').val(form.description || '');
                $('#edit_status').val(form.status);
                $('#editFormModal').modal('show');
            } else {
                alert('Error: Form not found');
            }
        }
        
        // Delete form
        function deleteForm(formId) {
            if (confirm('Are you sure you want to delete this form? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'forms.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_form';
                input.value = '1';
                
                const formIdInput = document.createElement('input');
                formIdInput.type = 'hidden';
                formIdInput.name = 'form_id';
                formIdInput.value = formId;
                
                form.appendChild(input);
                form.appendChild(formIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
