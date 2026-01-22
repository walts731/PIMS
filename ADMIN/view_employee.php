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

// Get employee ID from URL parameter
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employee_id <= 0) {
    $_SESSION['message'] = "Invalid employee ID.";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit();
}

// Get employee details
$employee = null;
$office_name = 'N/A';
try {
    $stmt = $conn->prepare("SELECT e.*, o.office_name,
                                 CASE WHEN EXISTS (
                                     SELECT 1 FROM asset_items ai WHERE ai.employee_id = e.id
                                 ) THEN 'uncleared' ELSE 'cleared' END as computed_clearance_status
                          FROM employees e 
                          LEFT JOIN offices o ON e.office_id = o.id 
                          WHERE e.id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($employee = $result->fetch_assoc()) {
        $office_name = $employee['office_name'] ?? 'N/A';
    } else {
        $_SESSION['message'] = "Employee not found.";
        $_SESSION['message_type'] = "danger";
        header('Location: employees.php');
        exit();
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching employee: " . $e->getMessage());
    $_SESSION['message'] = "Database error occurred.";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit();
}

// Log view employee action
logSystemAction($_SESSION['user_id'], 'view', 'employees', "Viewed employee: {$employee['firstname']} {$employee['lastname']}");

// Get status badge classes
function getStatusBadgeClass($status, $type = 'employment') {
    if ($type === 'employment') {
        switch($status) {
            case 'permanent': return 'status-permanent';
            case 'contractual': return 'status-contractual';
            case 'job_order': return 'status-job_order';
            case 'resigned': return 'status-resigned';
            case 'retired': return 'status-retired';
            default: return 'status-permanent';
        }
    } else {
        switch($status) {
            case 'cleared': return 'clearance-cleared';
            case 'uncleared': return 'clearance-uncleared';
            default: return 'clearance-uncleared';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee - PIMS</title>
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
        
        .view-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 2rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-value {
            color: #212529;
            flex: 1;
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
        
        .employee-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #6c63ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'View Employee';
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
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="employees.php" class="text-decoration-none">Employees</a></li>
                            <li class="breadcrumb-item active">View Employee</li>
                        </ol>
                    </nav>
                    <h1 class="mb-2">
                        <i class="bi bi-person-circle"></i> View Employee
                    </h1>
                    <p class="text-muted mb-0">Employee details and information</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="employees.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Employees
                    </a>
                    <button class="btn btn-warning ms-2" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                        <i class="bi bi-pencil"></i> Edit Employee
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Employee Details -->
        <div class="row">
            <div class="col-md-4">
                <div class="view-card text-center">
                    <?php if (!empty($employee['profile_photo'])): ?>
                        <img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" alt="Profile Photo" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto 1.5rem;">
                    <?php else: ?>
                        <div class="employee-avatar">
                            <?php echo strtoupper(substr($employee['firstname'], 0, 1) . substr($employee['lastname'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <h4 class="mb-1"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></h4>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($employee['position'] ?? 'Not specified'); ?></p>
                    <div class="d-flex justify-content-center gap-2">
                        <span class="status-badge <?php echo getStatusBadgeClass($employee['employment_status'] ?? 'permanent', 'employment'); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $employee['employment_status'] ?? 'permanent')); ?>
                        </span>
                        <span class="status-badge <?php echo getStatusBadgeClass($employee['computed_clearance_status'] ?? 'uncleared', 'clearance'); ?>">
                            <?php echo ucfirst($employee['computed_clearance_status'] ?? 'uncleared'); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="view-card">
                    <h5 class="mb-4"><i class="bi bi-person-badge"></i> Employee Information</h5>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-hash"></i> Employee No.
                        </div>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($employee['employee_no'] ?? 'N/A'); ?></strong>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-person"></i> Full Name
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-envelope"></i> Email
                        </div>
                        <div class="info-value">
                            <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($employee['email']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <?php if (!empty($employee['phone'])): ?>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-telephone"></i> Phone
                        </div>
                        <div class="info-value">
                            <a href="tel:<?php echo htmlspecialchars($employee['phone']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($employee['phone']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-building"></i> Office
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($office_name); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-briefcase"></i> Position
                        </div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($employee['position'] ?? 'Not specified'); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-person-check"></i> Employment Status
                        </div>
                        <div class="info-value">
                            <span class="status-badge <?php echo getStatusBadgeClass($employee['employment_status'] ?? 'permanent', 'employment'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $employee['employment_status'] ?? 'permanent')); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-shield-check"></i> Clearance Status
                        </div>
                        <div class="info-value">
                            <span class="status-badge <?php echo getStatusBadgeClass($employee['computed_clearance_status'] ?? 'uncleared', 'clearance'); ?>">
                                <?php echo ucfirst($employee['computed_clearance_status'] ?? 'uncleared'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-calendar-plus"></i> Date Added
                        </div>
                        <div class="info-value">
                            <?php echo date('F d, Y', strtotime($employee['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main-wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        function editEmployee(id) {
            // Redirect to edit page with ID parameter
            window.location.href = 'employees.php?edit_id=' + id;
        }
    </script>
</body>
</html>
