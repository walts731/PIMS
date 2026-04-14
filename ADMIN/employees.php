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

// Log employees page access
logSystemAction($_SESSION['user_id'], 'access', 'employees', 'Admin accessed employees page');

// Handle CRUD operations
$message = '';
$message_type = '';

// UPDATE - Edit employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $firstname = trim($_POST['firstname'] ?? '');
    $middle_name = trim($_POST['middlename'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $employee_no = trim($_POST['employee_no'] ?? ''); // For display only, not updated
    $office_id = intval($_POST['office_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $designation = isset($_POST['designation']) ? array_filter($_POST['designation'], function($val) {
        return !empty(trim($val));
    }) : [];
    $designation = !empty($designation) ? json_encode(array_values($designation)) : null;
    $employment_status = trim($_POST['employment_status'] ?? 'permanent');
    
    // Handle profile photo upload
    $profile_photo = $_POST['current_photo'] ?? '';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['profile_photo']['type'], $allowed_types) && $_FILES['profile_photo']['size'] <= $max_size) {
            $upload_dir = '../uploads/employees/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $file_name = 'employee_' . $id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $profile_photo = 'uploads/employees/' . $file_name;
                
                // Delete old photo if exists
                if (!empty($_POST['current_photo']) && file_exists('../' . $_POST['current_photo'])) {
                    unlink('../' . $_POST['current_photo']);
                }
            }
        }
    }
    
    // Validation
    if (empty($firstname)) {
        $message = "First name is required.";
        $message_type = "danger";
    } elseif (empty($lastname)) {
        $message = "Last name is required.";
        $message_type = "danger";
    } elseif (empty($email)) {
        $message = "Email is required.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif ($office_id <= 0) {
        $message = "Please select an office.";
        $message_type = "danger";
    } else {
        try {
            // Check if employee exists
            $check_stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows == 0) {
                $message = "Employee not found.";
                $message_type = "danger";
            } else {
                $check_stmt->close();
                
                // Check if employee number conflicts with another employee
                $emp_no_check = $conn->prepare("SELECT id FROM employees WHERE employee_no = ? AND id != ?");
                $emp_no_check->bind_param("si", $employee_no, $id);
                $emp_no_check->execute();
                if ($emp_no_check->get_result()->num_rows > 0) {
                    $message = "Employee number already exists. Please use a different number.";
                    $message_type = "danger";
                    $emp_no_check->close();
                } else {
                    $emp_no_check->close();
                    
                    // Update employee
                    $update_sql = "UPDATE employees SET firstname = ?, middle_name = ?, lastname = ?, email = ?, phone = ?, employee_no = ?, office_id = ?, position = ?, designation = ?, employment_status = ?, profile_photo = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssssssissssi", $firstname, $middle_name, $lastname, $email, $phone, $employee_no, $office_id, $position, $designation, $employment_status, $profile_photo, $id);
                
                    if ($update_stmt->execute()) {
                        logSystemAction($_SESSION['user_id'], 'update', 'employees', "Updated employee: $firstname $lastname");
                        $_SESSION['message'] = "Employee updated successfully!";
                        $_SESSION['message_type'] = "success";
                        header("Location: employees.php");
                        exit();
                    } else {
                        $message = "Error updating employee.";
                        $message_type = "danger";
                    }
                    $update_stmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("Error updating employee: " . $e->getMessage());
            $message = "Database error occurred.";
            $message_type = "danger";
        }
    }
}

// ADD - Create new employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $firstname = trim($_POST['firstname'] ?? '');
    $middle_name = trim($_POST['middlename'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $employee_no = trim($_POST['employee_no'] ?? '');
    $office_id = intval($_POST['office_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $designation = isset($_POST['designation']) ? array_filter($_POST['designation'], function($val) {
        return !empty(trim($val));
    }) : [];
    $designation = !empty($designation) ? json_encode(array_values($designation)) : null;
    $employment_status = trim($_POST['employment_status'] ?? 'permanent');
    $clearance_status = trim($_POST['clearance_status'] ?? 'uncleared');
    
    // Handle profile photo upload
    $profile_photo = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['profile_photo']['type'], $allowed_types) && $_FILES['profile_photo']['size'] <= $max_size) {
            $upload_dir = '../uploads/employees/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // We'll get the employee ID after insertion, so use a temporary name first
            $temp_name = 'temp_' . time() . '.' . pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $upload_path = $upload_dir . $temp_name;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $profile_photo = 'uploads/employees/' . $temp_name;
            }
        }
    }
    
    // Validation
    if (empty($firstname)) {
        $message = "First name is required.";
        $message_type = "danger";
    } elseif (empty($lastname)) {
        $message = "Last name is required.";
        $message_type = "danger";
    } elseif (empty($email)) {
        $message = "Email is required.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif (empty($employee_no)) {
        $message = "Employee number is required.";
        $message_type = "danger";
    } elseif ($office_id <= 0) {
        $message = "Please select an office.";
        $message_type = "danger";
    } else {
        try {
            // Check if employee number already exists
            $check_stmt = $conn->prepare("SELECT id FROM employees WHERE employee_no = ?");
            $check_stmt->bind_param("s", $employee_no);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $message = "Employee number already exists. Please use a different number.";
                $message_type = "danger";
                $check_stmt->close();
            } else {
                $check_stmt->close();
                
                // Insert employee
                $insert_sql = "INSERT INTO employees (employee_no, firstname, middle_name, lastname, email, phone, office_id, position, designation, employment_status, clearance_status, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sssssisssssi", $employee_no, $firstname, $middle_name, $lastname, $email, $phone, $office_id, $position, $designation, $employment_status, $clearance_status, $profile_photo);
                
                if ($insert_stmt->execute()) {
                    $employee_id = $conn->insert_id;
                    
                    // Rename photo file with employee ID if photo was uploaded
                    if ($profile_photo && !empty($profile_photo)) {
                        $old_path = '../' . $profile_photo;
                        $file_extension = pathinfo($old_path, PATHINFO_EXTENSION);
                        $new_filename = 'employee_' . $employee_id . '_' . time() . '.' . $file_extension;
                        $new_path = '../uploads/employees/' . $new_filename;
                        
                        if (rename($old_path, $new_path)) {
                            // Update database with new filename
                            $update_photo_sql = "UPDATE employees SET profile_photo = ? WHERE id = ?";
                            $update_photo_stmt = $conn->prepare($update_photo_sql);
                            $new_photo_path = 'uploads/employees/' . $new_filename;
                            $update_photo_stmt->bind_param("si", $new_photo_path, $employee_id);
                            $update_photo_stmt->execute();
                            $update_photo_stmt->close();
                        }
                    }
                    
                    logSystemAction($_SESSION['user_id'], 'create', 'employees', "Added new employee: $firstname $lastname ($employee_no)");
                    $_SESSION['message'] = "Employee added successfully! Employee No: $employee_no";
                    $_SESSION['message_type'] = "success";
                    header("Location: employees.php");
                    exit();
                } else {
                    $message = "Error adding employee.";
                    $message_type = "danger";
                }
                $insert_stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error adding employee: " . $e->getMessage());
            $message = "Database error occurred.";
            $message_type = "danger";
        }
    }
}
// Handle edit_id parameter for editing
$edit_employee = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_employee = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        $edit_employee = null;
    }
}

$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$clearance_filter = isset($_GET['clearance']) ? trim($_GET['clearance']) : '';


// Get all employees (DataTables will handle pagination and filtering)
$employees = [];
try {
    $sql = "SELECT e.*, o.office_name,
                   CASE WHEN EXISTS (
                       SELECT 1 FROM asset_items ai WHERE ai.employee_id = e.id
                   ) THEN 'uncleared' ELSE 'cleared' END as computed_clearance_status
            FROM employees e 
            LEFT JOIN offices o ON e.office_id = o.id 
            ORDER BY e.lastname, e.firstname";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    $total_records = count($employees);
    
} catch (Exception $e) {
    error_log("Error fetching employees: " . $e->getMessage());
    $employees = [];
    $total_records = 0;
}

// Get offices for filter dropdown
$offices = [];
try {
    $result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
} catch (Exception $e) {
    $offices = [];
}

// Calculate statistics
$stats = [
    'total_employees' => $total_records,
    'permanent_employees' => 0,
    'cleared_employees' => 0,
    'uncleared_employees' => 0
];

// Calculate statistics based on filtered data
foreach ($employees as $emp) {
    if ($emp['employment_status'] === 'permanent') {
        $stats['permanent_employees']++;
    }
    if ($emp['clearance_status'] === 'cleared') {
        $stats['cleared_employees']++;
    } elseif ($emp['clearance_status'] === 'uncleared') {
        $stats['uncleared_employees']++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - PIMS</title>
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Employees';
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
                        <i class="bi bi-people"></i> Employees
                    </h1>
                    <p class="text-muted mb-0">Manage employee records and clearance status</p>
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-<?php echo ($_SESSION['message_type'] ?? 'info') == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($_SESSION['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="addEmployee()">
                                    <i class="bi bi-plus-circle"></i> Add Employee
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="exportEmployees()">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
                
        <!-- Employees Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Employee Records</h5>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="employeesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Photo</th>
                            <th>Employee No.</th>
                            <th>Name</th>
                            <th>Office</th>
                            <th>Employment Status</th>
                            <th>Clearance Status</th>
                            <th>Actions</th>
                            <th style="display:none;">Office ID</th>
                            <th style="display:none;">Status ID</th>
                            <th style="display:none;">Clearance ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($employee['profile_photo'])): ?>
                                            <img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), #6c63ff); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                <?php echo strtoupper(substr($employee['firstname'], 0, 1) . substr($employee['lastname'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['employee_no'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($employee['firstname'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . $employee['lastname']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['office_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status = $employee['employment_status'] ?? 'permanent';
                                        switch($status) {
                                            case 'permanent':
                                                $status_class = 'status-permanent';
                                                break;
                                            case 'contractual':
                                                $status_class = 'status-contractual';
                                                break;
                                            case 'job_order':
                                                $status_class = 'status-job_order';
                                                break;
                                            case 'resigned':
                                                $status_class = 'status-resigned';
                                                break;
                                            case 'retired':
                                                $status_class = 'status-retired';
                                                break;
                                            default:
                                                $status_class = 'status-permanent';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $clearance_class = '';
                                        $clearance = $employee['computed_clearance_status'] ?? 'uncleared';
                                        switch($clearance) {
                                            case 'cleared':
                                                $clearance_class = 'clearance-cleared';
                                                break;
                                            case 'uncleared':
                                                $clearance_class = 'clearance-uncleared';
                                                break;
                                            default:
                                                $clearance_class = 'clearance-uncleared';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $clearance_class; ?>">
                                            <?php echo ucfirst($clearance); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" onclick="viewEmployee(<?php echo $employee['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td style="display:none;"><?php echo htmlspecialchars($employee['office_id']); ?></td>
                                    <td style="display:none;"><?php echo htmlspecialchars($employee['employment_status']); ?></td>
                                    <td style="display:none;"><?php echo htmlspecialchars($employee['computed_clearance_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="bi bi-people fs-1"></i>
                                    <p class="mt-2">No employees found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main-wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">
                        <i class="bi bi-plus-circle"></i> Add Employee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addEmployeeForm" method="POST" action="employees.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="addFirstname" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="addFirstname" name="firstname" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="addMiddlename" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="addMiddlename" name="middlename">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="addLastname" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="addLastname" name="lastname" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addEmail" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="addEmail" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addPhone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="addPhone" name="phone">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addEmployeeNo" class="form-label">Employee No. *</label>
                                <input type="text" class="form-control" id="addEmployeeNo" name="employee_no" required placeholder="Enter employee number">
                                <small class="text-muted">Enter unique employee number</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <!-- Empty column for balance -->
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addPosition" class="form-label">Position</label>
                                <input type="text" class="form-control" id="addPosition" name="position">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designations</label>
                                <div id="designationContainer">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="designation[]" placeholder="Enter designation">
                                        <button class="btn btn-outline-success" type="button" onclick="addDesignation(this)">
                                            <i class="bi bi-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Click Add to include multiple designations</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addOffice" class="form-label">Office *</label>
                                <select class="form-select" id="addOffice" name="office_id" required>
                                    <option value="">Select Office</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>">
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addEmploymentStatus" class="form-label">Employment Status *</label>
                                <select class="form-select" id="addEmploymentStatus" name="employment_status" required>
                                    <option value="permanent">Permanent</option>
                                    <option value="contractual">Contractual</option>
                                    <option value="job_order">Job Order</option>
                                    <option value="resigned">Resigned</option>
                                    <option value="retired">Retired</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addPhoto" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="addPhoto" name="profile_photo" accept="image/*">
                                <small class="text-muted">Allowed: JPG, PNG, GIF (Max 5MB)</small>
                            </div>
                            <div class="col-md-6">
                                <!-- Empty column for balance -->
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addClearanceStatus" class="form-label">Clearance Status *</label>
                                <select class="form-select" id="addClearanceStatus" name="clearance_status" required>
                                    <option value="cleared">Cleared</option>
                                    <option value="uncleared">Uncleared</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <!-- Empty column for balance -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Add Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">
                        <i class="bi bi-pencil"></i> Edit Employee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editEmployeeForm" method="POST" action="employees.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editEmployeeId" value="<?php echo $edit_employee['id'] ?? ''; ?>">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="editFirstname" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="editFirstname" name="firstname" required value="<?php echo htmlspecialchars($edit_employee['firstname'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editMiddlename" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="editMiddlename" name="middlename" value="<?php echo htmlspecialchars($edit_employee['middlename'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editLastname" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="editLastname" name="lastname" required value="<?php echo htmlspecialchars($edit_employee['lastname'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required value="<?php echo htmlspecialchars($edit_employee['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPhone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="editPhone" name="phone" value="<?php echo htmlspecialchars($edit_employee['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEmployeeNo" class="form-label">Employee No. *</label>
                                <input type="text" class="form-control" id="editEmployeeNo" name="employee_no" required value="<?php echo htmlspecialchars($edit_employee['employee_no'] ?? ''); ?>" placeholder="Enter employee number">
                                <small class="text-muted">Enter unique employee number</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <!-- Empty column for balance -->
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editPosition" class="form-label">Position</label>
                                <input type="text" class="form-control" id="editPosition" name="position" value="<?php echo htmlspecialchars($edit_employee['position'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designations</label>
                                <div id="editDesignationContainer">
                                    <?php 
                                    if (!empty($edit_employee['designation'])) {
                                        $designations = json_decode($edit_employee['designation'], true);
                                        if (!empty($designations) && is_array($designations)) {
                                            foreach ($designations as $index => $designation) {
                                                echo '<div class="input-group mb-2">';
                                                echo '<input type="text" class="form-control" name="designation[]" value="' . htmlspecialchars($designation) . '" placeholder="Enter designation">';
                                                if ($index > 0) {
                                                    echo '<button class="btn btn-outline-danger" type="button" onclick="removeDesignation(this)"><i class="bi bi-dash"></i></button>';
                                                } else {
                                                    echo '<button class="btn btn-outline-success" type="button" onclick="addDesignation(this)"><i class="bi bi-plus"></i> Add</button>';
                                                }
                                                echo '</div>';
                                            }
                                        } else {
                                            echo '<div class="input-group mb-2">';
                                            echo '<input type="text" class="form-control" name="designation[]" placeholder="Enter designation">';
                                            echo '<button class="btn btn-outline-success" type="button" onclick="addDesignation(this)"><i class="bi bi-plus"></i> Add</button>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<div class="input-group mb-2">';
                                        echo '<input type="text" class="form-control" name="designation[]" placeholder="Enter designation">';
                                        echo '<button class="btn btn-outline-success" type="button" onclick="addDesignation(this)"><i class="bi bi-plus"></i> Add</button>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <small class="text-muted">Click Add to include multiple designations</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editOffice" class="form-label">Office *</label>
                                <select class="form-select" id="editOffice" name="office_id" required>
                                    <option value="">Select Office</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo (isset($edit_employee['office_id']) && $edit_employee['office_id'] == $office['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEmploymentStatus" class="form-label">Employment Status *</label>
                                <select class="form-select" id="editEmploymentStatus" name="employment_status" required>
                                    <option value="permanent" <?php echo (isset($edit_employee['employment_status']) && $edit_employee['employment_status'] == 'permanent') ? 'selected' : ''; ?>>Permanent</option>
                                    <option value="contractual" <?php echo (isset($edit_employee['employment_status']) && $edit_employee['employment_status'] == 'contractual') ? 'selected' : ''; ?>>Contractual</option>
                                    <option value="job_order" <?php echo (isset($edit_employee['employment_status']) && $edit_employee['employment_status'] == 'job_order') ? 'selected' : ''; ?>>Job Order</option>
                                    <option value="resigned" <?php echo (isset($edit_employee['employment_status']) && $edit_employee['employment_status'] == 'resigned') ? 'selected' : ''; ?>>Resigned</option>
                                    <option value="retired" <?php echo (isset($edit_employee['employment_status']) && $edit_employee['employment_status'] == 'retired') ? 'selected' : ''; ?>>Retired</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editPhoto" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="editPhoto" name="profile_photo" accept="image/*">
                                <input type="hidden" name="current_photo" value="<?php echo htmlspecialchars($edit_employee['profile_photo'] ?? ''); ?>">
                                <small class="text-muted">Allowed: JPG, PNG, GIF (Max 5MB)</small>
                                <?php if (!empty($edit_employee['profile_photo'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo htmlspecialchars($edit_employee['profile_photo']); ?>" alt="Current Photo" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <!-- Empty column for balance -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Show edit modal if edit_id is present
            <?php if ($edit_employee): ?>
                $('#editEmployeeModal').modal('show');
            <?php endif; ?>
            
            // Initialize DataTable
            let employeesTable;
            
            // Check if table has data rows before initializing DataTables
            const tableBody = $('#employeesTable tbody');
            const hasData = tableBody.find('tr').length > 0 && !tableBody.find('td[colspan]').length;
            
            console.log('Table has data:', hasData);
            console.log('Table rows found:', tableBody.find('tr').length);
            
            // Initialize DataTable with error handling
            try {
                if (hasData) {
                    // Only initialize DataTables if there's actual data
                    employeesTable = $('#employeesTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        dom: '<"row"<"col-md-2"l><"col-md-2 office-filter-container"><"col-md-2 status-filter-container"><"col-md-2 clearance-filter-container"><"col-md-4"f>>rtip',
                        language: {
                            search: "Search employees:",
                            lengthMenu: "Show _MENU_ employees per page",
                            info: "Showing _START_ to _END_ of _TOTAL_ employees",
                            infoEmpty: "Showing 0 to 0 of 0 employees",
                            infoFiltered: "(filtered from _MAX_ total employees)",
                            zeroRecords: "No matching employees found"
                        },
                        initComplete: function(settings, json) {
                            console.log('DataTables initialized successfully');
                            
                            // Add office filter to DataTables
                            $('.office-filter-container').html(`
                                <select id="officeFilter" class="form-select form-select-sm">
                                    <option value="">Office</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>"><?php echo htmlspecialchars($office['office_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            `);
                            
                            // Add status filter to DataTables
                            $('.status-filter-container').html(`
                                <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">Status</option>
                                    <option value="permanent">Permanent</option>
                                    <option value="contractual">Contractual</option>
                                    <option value="job_order">Job Order</option>
                                    <option value="resigned">Resigned</option>
                                    <option value="retired">Retired</option>
                                </select>
                            `);
                            
                            // Add clearance filter to DataTables
                            $('.clearance-filter-container').html(`
                                <select id="clearanceFilter" class="form-select form-select-sm">
                                    <option value="">Clearance</option>
                                    <option value="cleared">Cleared</option>
                                    <option value="uncleared">Uncleared</option>
                                </select>
                            `);
                            
                            // Set initial filter values if they exist in URL
                            const urlParams = new URLSearchParams(window.location.search);
                            if (urlParams.get('office')) $('#officeFilter').val(urlParams.get('office'));
                            if (urlParams.get('status')) $('#statusFilter').val(urlParams.get('status'));
                            if (urlParams.get('clearance')) $('#clearanceFilter').val(urlParams.get('clearance'));
                        }
                    });
                } else {
                    // No data - don't initialize DataTables, just add basic styling
                    $('#employeesTable').addClass('table-striped');
                    console.log('No data found - DataTables not initialized');
                }
            } catch (error) {
                console.error('DataTables initialization error:', error);
                // Fallback: make table work without DataTables
                $('#employeesTable').addClass('table-striped');
            }
            
            // DataTables filter functionality
            $(document).on('change', '#officeFilter, #statusFilter, #clearanceFilter', function() {
                if (!employeesTable) return;
                
                const officeValue = $('#officeFilter').val();
                const statusValue = $('#statusFilter').val();
                const clearanceValue = $('#clearanceFilter').val();
                
                // Apply filters to DataTables using hidden columns
                // Office filter (column 7 - hidden Office ID)
                if (officeValue) {
                    employeesTable.column(7).search(officeValue).draw();
                } else {
                    employeesTable.column(7).search('').draw();
                }
                
                // Status filter (column 8 - hidden Status ID)
                if (statusValue) {
                    employeesTable.column(8).search(statusValue).draw();
                } else {
                    employeesTable.column(8).search('').draw();
                }
                
                // Clearance filter (column 9 - hidden Clearance ID)
                if (clearanceValue) {
                    employeesTable.column(9).search(clearanceValue).draw();
                } else {
                    employeesTable.column(9).search('').draw();
                }
            });
        });

        // Designation management functions
        function addDesignation(button) {
            // Determine which container we're working with
            let container;
            if (button) {
                // If called from a button, find the container
                const modalBody = button.closest('.modal-body');
                if (modalBody) {
                    container = modalBody.querySelector('#editDesignationContainer') || 
                               modalBody.querySelector('#designationContainer');
                }
            } else {
                // Fallback to checking both containers
                container = document.getElementById('editDesignationContainer') || 
                          document.getElementById('designationContainer');
            }
            
            if (!container) {
                console.error('Container not found');
                return;
            }
            
            const newInput = document.createElement('div');
            newInput.className = 'input-group mb-2';
            newInput.innerHTML = `
                <input type="text" class="form-control" name="designation[]" placeholder="Enter designation">
                <button class="btn btn-outline-danger" type="button" onclick="removeDesignation(this)">
                    <i class="bi bi-dash"></i>
                </button>
            `;
            
            container.appendChild(newInput);
        }
        
        function removeDesignation(button) {
            const container = button.parentElement;
            const parentContainer = container.parentElement;
            
            // Don't remove if it's the last one
            if (parentContainer.children.length > 1) {
                container.remove();
            } else {
                // Clear the value instead of removing the last input
                container.querySelector('input').value = '';
            }
        }
        
        // Employee management functions
        function addEmployee() {
            // Clear form fields
            $('#addEmployeeForm')[0].reset();
            
            // Show modal
            $('#addEmployeeModal').modal('show');
        }
        
        function viewEmployee(id) {
            // Redirect to view employee page
            window.location.href = 'view_employee.php?id=' + id;
        }
        
        function editEmployee(id) {
            // Redirect to edit page with ID parameter
            window.location.href = 'employees.php?edit_id=' + id;
        }
        
        // Export employees function (manual export only)
        function exportEmployees() {
            console.log('Export function called');
            exportTableManually();
        }
        
        // Manual export function for when DataTables is not available
        function exportTableManually() {
            console.log('Using manual table export');
            let csv = 'Employee No,Name,Middle Name,Email,Office,Position,Designations,Employment Status,Clearance Status\n';
            
            $('#employeesTable tbody tr').each(function() {
                const $row = $(this);
                // Skip empty state rows
                if ($row.find('td[colspan]').length > 0) {
                    return;
                }
                
                const cells = $row.find('td');
                const employeeNo = $(cells[1]).text().trim();
                const name = $(cells[2]).text().trim();
                const office = $(cells[3]).text().trim();
                const status = $(cells[4]).text().trim();
                const clearance = $(cells[5]).text().trim();
                
                csv += '"' + employeeNo + '",' +
                       '"' + name + '",' +
                       '",' + // Middle name (not displayed in table)
                       '",' + // Email (not displayed in table)
                       '"' + office + '",' +
                       '",' + // Position (not displayed in table)
                       '",' + // Designations (not displayed in table)
                       '"' + status + '",' +
                       '"' + clearance + '"' + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'employees_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
