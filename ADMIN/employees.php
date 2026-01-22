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
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $office_id = intval($_POST['office_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $employment_status = trim($_POST['employment_status'] ?? 'permanent');
    $clearance_status = trim($_POST['clearance_status'] ?? 'uncleared');
    
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
                // Update employee
                $update_sql = "UPDATE employees SET firstname = ?, lastname = ?, email = ?, phone = ?, office_id = ?, position = ?, employment_status = ?, clearance_status = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssissssi", $firstname, $lastname, $email, $phone, $office_id, $position, $employment_status, $clearance_status, $id);
                
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
            $check_stmt->close();
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
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $office_id = intval($_POST['office_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $employment_status = trim($_POST['employment_status'] ?? 'permanent');
    $clearance_status = trim($_POST['clearance_status'] ?? 'uncleared');
    
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
            // Generate employee number
            $year = date('Y');
            $prefix = 'EMP';
            
            // Get the last employee number for this year
            $last_stmt = $conn->prepare("SELECT employee_no FROM employees WHERE employee_no LIKE ? ORDER BY employee_no DESC LIMIT 1");
            $last_pattern = $prefix . $year . '%';
            $last_stmt->bind_param("s", $last_pattern);
            $last_stmt->execute();
            $last_result = $last_stmt->get_result();
            
            if ($last_row = $last_result->fetch_assoc()) {
                $last_number = intval(substr($last_row['employee_no'], -4));
                $new_number = $last_number + 1;
            } else {
                $new_number = 1;
            }
            $last_stmt->close();
            
            $employee_no = $prefix . $year . str_pad($new_number, 4, '0', STR_PAD_LEFT);
            
            // Insert employee
            $insert_sql = "INSERT INTO employees (employee_no, firstname, lastname, email, phone, office_id, position, employment_status, clearance_status, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssissssi", $employee_no, $firstname, $lastname, $email, $phone, $office_id, $position, $employment_status, $clearance_status, $_SESSION['user_id']);
            
            if ($insert_stmt->execute()) {
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

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10; // Number of records per page
$offset = ($page - 1) * $per_page;

// Get employees with filters
$employees = [];
$total_records = 0;
try {
    // First, get total count for pagination
    $count_sql = "SELECT COUNT(*) as total 
                  FROM employees e 
                  LEFT JOIN offices o ON e.office_id = o.id 
                  WHERE 1=1";
    
    $count_params = [];
    $count_types = '';
    
    if ($search_filter !== '') {
        $count_sql .= " AND (e.firstname LIKE ? OR e.lastname LIKE ? OR e.email LIKE ? OR e.employee_no LIKE ?)";
        $searchParam = "%{$search_filter}%";
        $count_params = array_merge($count_params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $count_types = $count_types . 'ssss';
    }
    
    if ($office_filter > 0) {
        $count_sql .= " AND e.office_id = ?";
        $count_params[] = $office_filter;
        $count_types = $count_types . 'i';
    }
    
    if ($status_filter !== '') {
        $count_sql .= " AND e.employment_status = ?";
        $count_params[] = $status_filter;
        $count_types = $count_types . 's';
    }
    
    if ($clearance_filter !== '') {
        $count_sql .= " AND e.clearance_status = ?";
        $count_params[] = $clearance_filter;
        $count_types = $count_types . 's';
    }
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Now get the paginated data
    $sql = "SELECT e.*, o.office_name 
            FROM employees e 
            LEFT JOIN offices o ON e.office_id = o.id 
            WHERE 1=1";
    
    $params = [];
    $types = '';

    if ($search_filter !== '') {
        $sql .= " AND (e.firstname LIKE ? OR e.lastname LIKE ? OR e.email LIKE ? OR e.employee_no LIKE ?)";
        $searchParam = "%{$search_filter}%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types = $types . 'ssss';
    }

    if ($office_filter > 0) {
        $sql .= " AND e.office_id = ?";
        $params[] = $office_filter;
        $types = $types . 'i';
    }

    if ($status_filter !== '') {
        $sql .= " AND e.employment_status = ?";
        $params[] = $status_filter;
        $types = $types . 's';
    }

    if ($clearance_filter !== '') {
        $sql .= " AND e.clearance_status = ?";
        $params[] = $clearance_filter;
        $types = $types . 's';
    }
    
    $sql .= " ORDER BY e.lastname, e.firstname LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types = $types . 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
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

// Pagination calculations
$total_pages = ceil($total_records / $per_page);
$showing_from = $total_records > 0 ? ($page - 1) * $per_page + 1 : 0;
$showing_to = min($page * $per_page, $total_records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - PIMS</title>
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
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-permanent { background: #d4edda; color: #155724; }
        .status-contractual { background: #cce5ff; color: #004085; }
        .status-job_order { background: #fff3cd; color: #856404; }
        .status-resigned { background: #f8d7da; color: #721c24; }
        .status-retired { background: #e2e3e5; color: #383d41; }
        .clearance-cleared { background: #d4edda; color: #155724; }
        .clearance-uncleared { background: #f8d7da; color: #721c24; }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
    </style>
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
                    <button class="btn btn-primary" onclick="addEmployee()">
                        <i class="bi bi-plus-circle"></i> Add Employee
                    </button>
                    <button class="btn btn-success btn-sm ms-2" onclick="exportEmployees()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_employees']; ?></div>
                    <div class="stats-label"><i class="bi bi-people"></i> Total Employees</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['permanent_employees']; ?></div>
                    <div class="stats-label"><i class="bi bi-person-badge"></i> Permanent Employees</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['cleared_employees']; ?></div>
                    <div class="stats-label"><i class="bi bi-shield-check"></i> Cleared Employees</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['uncleared_employees']; ?></div>
                    <div class="stats-label"><i class="bi bi-shield-x"></i> Uncleared Employees</div>
                </div>
            </div>
        </div>
        
        <!-- Employees Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Employee Records</h5>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="officeFilter">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="permanent" <?php echo $status_filter == 'permanent' ? 'selected' : ''; ?>>Permanent</option>
                                <option value="contractual" <?php echo $status_filter == 'contractual' ? 'selected' : ''; ?>>Contractual</option>
                                <option value="job_order" <?php echo $status_filter == 'job_order' ? 'selected' : ''; ?>>Job Order</option>
                                <option value="resigned" <?php echo $status_filter == 'resigned' ? 'selected' : ''; ?>>Resigned</option>
                                <option value="retired" <?php echo $status_filter == 'retired' ? 'selected' : ''; ?>>Retired</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="clearanceFilter">
                                <option value="">All Clearance</option>
                                <option value="cleared" <?php echo $clearance_filter == 'cleared' ? 'selected' : ''; ?>>Cleared</option>
                                <option value="uncleared" <?php echo $clearance_filter == 'uncleared' ? 'selected' : ''; ?>>Uncleared</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search employees..." value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="employeesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Employee No.</th>
                            <th>Name</th>
                            <th>Office</th>
                            <th>Employment Status</th>
                            <th>Clearance Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['employee_no'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                                        <?php if (!empty($employee['email'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($employee['email']); ?></small>
                                        <?php endif; ?>
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
                                        $clearance = $employee['clearance_status'] ?? 'uncleared';
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
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-people fs-1"></i>
                                    <p class="mt-2">No employees found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing <?php echo $showing_from; ?> to <?php echo $showing_to; ?> of <?php echo $total_records; ?> employees
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        // Build URL parameters for pagination links
                        $url_params = http_build_query([
                            'search' => $search_filter,
                            'office' => $office_filter,
                            'status' => $status_filter,
                            'clearance' => $clearance_filter
                        ]);
                        
                        // Previous button
                        if ($page > 1):
                            $prev_page = $page - 1;
                            $prev_url = "?page=$prev_page" . ($url_params ? "&$url_params" : "");
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $prev_url; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </span>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        // Page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1):
                            $first_url = "?page=1" . ($url_params ? "&$url_params" : "");
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $first_url; ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php
                            $page_url = "?page=$i" . ($url_params ? "&$url_params" : "");
                            $is_current = $i == $page;
                            ?>
                            <li class="page-item <?php echo $is_current ? 'active' : ''; ?>">
                                <?php if ($is_current): ?>
                                    <span class="page-link"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="<?php echo $page_url; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                        
                        <?php
                        if ($end_page < $total_pages):
                            $last_url = "?page=$total_pages" . ($url_params ? "&$url_params" : "");
                        ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $last_url; ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        // Next button
                        if ($page < $total_pages):
                            $next_page = $page + 1;
                            $next_url = "?page=$next_page" . ($url_params ? "&$url_params" : "");
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $next_url; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
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
                <form id="addEmployeeForm" method="POST" action="employees.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addFirstname" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="addFirstname" name="firstname" required>
                            </div>
                            <div class="col-md-6 mb-3">
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
                                <label for="addPosition" class="form-label">Position</label>
                                <input type="text" class="form-control" id="addPosition" name="position">
                            </div>
                        </div>
                        
                        <div class="row">
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
                            <div class="col-md-6 mb-3">
                                <label for="addClearanceStatus" class="form-label">Clearance Status *</label>
                                <select class="form-select" id="addClearanceStatus" name="clearance_status" required>
                                    <option value="cleared">Cleared</option>
                                    <option value="uncleared">Uncleared</option>
                                </select>
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
                <form id="editEmployeeForm" method="POST" action="employees.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editEmployeeId" value="<?php echo $edit_employee['id'] ?? ''; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editFirstname" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="editFirstname" name="firstname" required value="<?php echo htmlspecialchars($edit_employee['firstname'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
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
                                <label for="editPosition" class="form-label">Position</label>
                                <input type="text" class="form-control" id="editPosition" name="position" value="<?php echo htmlspecialchars($edit_employee['position'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
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
                            <div class="col-md-6 mb-3">
                                <label for="editClearanceStatus" class="form-label">Clearance Status *</label>
                                <select class="form-select" id="editClearanceStatus" name="clearance_status" required>
                                    <option value="cleared" <?php echo (isset($edit_employee['clearance_status']) && $edit_employee['clearance_status'] == 'cleared') ? 'selected' : ''; ?>>Cleared</option>
                                    <option value="uncleared" <?php echo (isset($edit_employee['clearance_status']) && $edit_employee['clearance_status'] == 'uncleared') ? 'selected' : ''; ?>>Uncleared</option>
                                </select>
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
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Show edit modal if edit_id is present
            <?php if ($edit_employee): ?>
                $('#editEmployeeModal').modal('show');
            <?php endif; ?>
            
            // Search functionality with debounce
            $('#searchInput').on('keyup change', function() {
                // Get current URL parameters
                var urlParams = new URLSearchParams(window.location.search);
                
                var search = $(this).val();
                
                // Update search parameter
                if (search) {
                    urlParams.set('search', search);
                } else {
                    urlParams.delete('search');
                }
                
                // Preserve other filter parameters
                var office = $('#officeFilter').val();
                var status = $('#statusFilter').val();
                var clearance = $('#clearanceFilter').val();
                
                if (office) urlParams.set('office', office);
                if (status) urlParams.set('status', status);
                if (clearance) urlParams.set('clearance', clearance);
                
                // Reset to page 1 when searching
                urlParams.delete('page');
                
                // Reload page with search parameter (with debounce)
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(function() {
                    window.location.href = window.location.pathname + '?' + urlParams.toString();
                }, 500);
            });
            
            // Filter functionality
            $('#officeFilter, #statusFilter, #clearanceFilter').on('change', function() {
                // Get current URL parameters
                var urlParams = new URLSearchParams(window.location.search);
                
                // Update filter values
                var office = $('#officeFilter').val();
                var status = $('#statusFilter').val();
                var clearance = $('#clearanceFilter').val();
                var search = $('#searchInput').val();
                
                // Set parameters
                if (office) {
                    urlParams.set('office', office);
                } else {
                    urlParams.delete('office');
                }
                
                if (status) {
                    urlParams.set('status', status);
                } else {
                    urlParams.delete('status');
                }
                
                if (clearance) {
                    urlParams.set('clearance', clearance);
                } else {
                    urlParams.delete('clearance');
                }
                
                if (search) {
                    urlParams.set('search', search);
                } else {
                    urlParams.delete('search');
                }
                
                // Reset to page 1 when filtering
                urlParams.delete('page');
                
                // Reload page with new parameters
                window.location.href = window.location.pathname + '?' + urlParams.toString();
            });
        });

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
        
        // Export employees function
        function exportEmployees() {
            let csv = 'Employee No,Name,Email,Office,Employment Status,Clearance Status\n';
            
            <?php if (!empty($employees)): ?>
                <?php foreach ($employees as $employee): ?>
                    csv += '<?php echo 
                        '"' . addslashes($employee['employee_no'] ?? 'N/A') . '",' .
                        '"' . addslashes(($employee['firstname'] ?? '') . ' ' . ($employee['lastname'] ?? '')) . '",' .
                        '"' . addslashes($employee['email'] ?? '') . '",' .
                        '"' . addslashes($employee['office_name'] ?? 'N/A') . '",' .
                        '"' . addslashes(ucfirst(str_replace('_', ' ', $employee['employment_status'] ?? 'permanent'))) . '",' .
                        '"' . addslashes(ucfirst($employee['clearance_status'] ?? 'uncleared')) . '"' 
                    ; ?>' + '\n';
                <?php endforeach; ?>
            <?php endif; ?>
            
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
