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

// Get assets assigned to employee
$employee_assets = [];
try {
    $stmt = $conn->prepare("SELECT ai.*, a.description as asset_description, ac.category_name, ac.category_code,
                                   o.office_name
                            FROM asset_items ai 
                            LEFT JOIN assets a ON ai.asset_id = a.id 
                            LEFT JOIN asset_categories ac ON COALESCE(ai.category_id, a.asset_categories_id) = ac.id 
                            LEFT JOIN offices o ON ai.office_id = o.id 
                            WHERE ai.employee_id = ? 
                            AND ai.status NOT IN ('unserviceable', 'red_tagged', 'disposed')
                            ORDER BY ai.created_at DESC");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee_assets = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching employee assets: " . $e->getMessage());
}

// Get status badge classes
function getStatusBadgeClass($status, $type = 'employment')
{
    if ($type === 'employment') {
        switch ($status) {
            case 'permanent':
                return 'status-permanent';
            case 'contractual':
                return 'status-contractual';
            case 'job_order':
                return 'status-job_order';
            case 'resigned':
                return 'status-resigned';
            case 'retired':
                return 'status-retired';
            default:
                return 'status-permanent';
        }
    } else {
        switch ($status) {
            case 'cleared':
                return 'clearance-cleared';
            case 'uncleared':
                return 'clearance-uncleared';
            default:
                return 'clearance-uncleared';
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
    <link href="assets/css/admin-unified.css" rel="stylesheet">
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
                                    <button class="dropdown-item" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                        <i class="bi bi-pencil"></i> Edit Employee
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="exportEmployeeData()">
                                        <i class="bi bi-download"></i> Export Employee Data
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a href="employees.php" class="dropdown-item">
                                        <i class="bi bi-arrow-left"></i> Back to Employees
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee Profile Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <?php if (!empty($employee['profile_photo'])): ?>
                                        <img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" alt="Profile Photo" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="avatar-placeholder rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; background: linear-gradient(135deg, var(--primary-color), #6c63ff); color: white; font-weight: 700; font-size: 3rem;">
                                            <?php echo strtoupper(substr($employee['firstname'], 0, 1) . substr($employee['lastname'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="card-title mb-1"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></h4>
                                    <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($employee['position'] ?? 'Not specified'); ?></p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge <?php echo getStatusBadgeClass($employee['employment_status'] ?? 'permanent', 'employment'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $employee['employment_status'] ?? 'permanent')); ?>
                                        </span>
                                        <span class="badge <?php echo getStatusBadgeClass($employee['computed_clearance_status'] ?? 'uncleared', 'clearance'); ?>">
                                            <?php echo ucfirst($employee['computed_clearance_status'] ?? 'uncleared'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <!-- Tabs Navigation -->
                                    <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                                <i class="bi bi-person-badge me-2"></i>Information
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="assets-tab" data-bs-toggle="tab" data-bs-target="#assets" type="button" role="tab" aria-controls="assets" aria-selected="false">
                                                <i class="bi bi-box-seam me-2"></i>Assets (<?php echo count($employee_assets); ?>)
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="statistics-tab" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab" aria-controls="statistics" aria-selected="false">
                                                <i class="bi bi-bar-chart me-2"></i>Statistics
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <!-- Tab Content -->
                                    <div class="tab-content" id="employeeTabsContent">
                                        <!-- Information Tab -->
                                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                            <div class="pt-3">
                                                <div class="row">
                                                    <div class="col-sm-6 mb-3">
                                                        <label class="text-muted small mb-1">Employee No.</label>
                                                        <div class="fw-bold">
                                                            <i class="bi bi-hash text-secondary me-1"></i>
                                                            <?php echo htmlspecialchars($employee['employee_no'] ?? 'N/A'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 mb-3">
                                                        <label class="text-muted small mb-1">Full Name</label>
                                                        <div class="fw-bold">
                                                            <i class="bi bi-person text-secondary me-1"></i>
                                                            <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 mb-3">
                                                        <label class="text-muted small mb-1">Email</label>
                                                        <div>
                                                            <i class="bi bi-envelope text-secondary me-1"></i>
                                                            <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($employee['email']); ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($employee['phone'])): ?>
                                                        <div class="col-sm-6 mb-3">
                                                            <label class="text-muted small mb-1">Phone</label>
                                                            <div>
                                                                <i class="bi bi-telephone text-secondary me-1"></i>
                                                                <a href="tel:<?php echo htmlspecialchars($employee['phone']); ?>" class="text-decoration-none">
                                                                    <?php echo htmlspecialchars($employee['phone']); ?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="col-sm-6 mb-3">
                                                        <label class="text-muted small mb-1">Office</label>
                                                        <div>
                                                            <i class="bi bi-building text-secondary me-1"></i>
                                                            <?php echo htmlspecialchars($office_name); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 mb-3">
                                                        <label class="text-muted small mb-1">Position</label>
                                                        <div>
                                                            <i class="bi bi-briefcase text-secondary me-1"></i>
                                                            <?php echo htmlspecialchars($employee['position'] ?? 'Not specified'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 mb-3">
                                                        <label class="text-muted small mb-1">Date Added</label>
                                                        <div>
                                                            <i class="bi bi-calendar-plus text-secondary me-1"></i>
                                                            <?php echo date('F d, Y', strtotime($employee['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Assets Tab -->
                                        <div class="tab-pane fade" id="assets" role="tabpanel" aria-labelledby="assets-tab">
                                            <div class="pt-3">
                                                <?php if (!empty($employee_assets)): ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Property No.</th>
                                                                    <th>Description</th>
                                                                    <th>Category</th>
                                                                    <th>Office</th>
                                                                    <th>Status</th>
                                                                    <th>Value</th>
                                                                    <th>Date Acquired</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($employee_assets as $asset): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($asset['property_no'] ?? 'N/A'); ?></td>
                                                                        <td>
                                                                            <?php echo htmlspecialchars($asset['description']); ?>
                                                                            <?php if (!empty($asset['asset_description'])): ?>
                                                                                <br><small class="text-muted"><?php echo htmlspecialchars($asset['asset_description']); ?></small>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge bg-secondary">
                                                                                <?php echo htmlspecialchars($asset['category_code'] ?? 'N/A'); ?>
                                                                            </span>
                                                                            <br><small class="text-muted"><?php echo htmlspecialchars($asset['category_name'] ?? ''); ?></small>
                                                                        </td>
                                                                        <td><?php echo htmlspecialchars($asset['office_name'] ?? 'N/A'); ?></td>
                                                                        <td>
                                                                            <?php
                                                                            $status_class = '';
                                                                            switch ($asset['status']) {
                                                                                case 'serviceable':
                                                                                    $status_class = 'bg-success';
                                                                                    break;
                                                                                case 'unserviceable':
                                                                                    $status_class = 'bg-danger';
                                                                                    break;
                                                                                case 'in_use':
                                                                                    $status_class = 'bg-primary';
                                                                                    break;
                                                                                case 'available':
                                                                                    $status_class = 'bg-secondary';
                                                                                    break;
                                                                                default:
                                                                                    $status_class = 'bg-warning';
                                                                            }
                                                                            ?>
                                                                            <span class="badge <?php echo $status_class; ?>">
                                                                                <?php echo ucfirst(htmlspecialchars($asset['status'])); ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                            if (isset($asset['unit_cost'])) {
                                                                                echo number_format($asset['unit_cost'], 2);
                                                                            } elseif (isset($asset['value'])) {
                                                                                echo number_format($asset['value'], 2);
                                                                            } else {
                                                                                echo '0.00';
                                                                            }
                                                                            ?>
                                                                        </td>
                                                                        <td><?php echo $asset['acquisition_date'] ? date('M d, Y', strtotime($asset['acquisition_date'])) : 'N/A'; ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center py-5">
                                                        <i class="bi bi-box-seam fs-1 text-muted mb-3"></i>
                                                        <h6 class="text-muted">No assets assigned to this employee</h6>
                                                        <p class="text-muted">This employee currently has no assets assigned to them.</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Statistics Tab -->
                                        <div class="tab-pane fade" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
                                            <div class="pt-3">
                                                <div class="row">
                                                    <div class="col-lg-3 col-md-6 mb-4">
                                                        <div class="card border-0 shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="mb-3">
                                                                    <i class="bi bi-box-seam text-primary" style="font-size: 2rem;"></i>
                                                                </div>
                                                                <h3 class="card-title mb-2"><?php echo count($employee_assets); ?></h3>
                                                                <p class="card-text text-muted">Total Assets</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 mb-4">
                                                        <div class="card border-0 shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="mb-3">
                                                                    <i class="bi bi-currency-philippine-peso text-success" style="font-size: 2rem;"></i>
                                                                </div>
                                                                <h3 class="card-title mb-2">
                                                                    <?php
                                                                    $total_value = 0;
                                                                    foreach ($employee_assets as $asset) {
                                                                        if (isset($asset['unit_cost'])) {
                                                                            $total_value += $asset['unit_cost'];
                                                                        } elseif (isset($asset['value'])) {
                                                                            $total_value += $asset['value'];
                                                                        }
                                                                    }
                                                                    echo number_format($total_value, 2);
                                                                    ?>
                                                                </h3>
                                                                <p class="card-text text-muted">Total Value</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 mb-4">
                                                        <div class="card border-0 shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="mb-3">
                                                                    <i class="bi bi-check-circle text-info" style="font-size: 2rem;"></i>
                                                                </div>
                                                                <h3 class="card-title mb-2">
                                                                    <?php
                                                                    $serviceable_count = count(array_filter($employee_assets, function ($asset) {
                                                                        return $asset['status'] === 'serviceable';
                                                                    }));
                                                                    echo $serviceable_count;
                                                                    ?>
                                                                </h3>
                                                                <p class="card-text text-muted">Serviceable</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 mb-4">
                                                        <div class="card border-0 shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="mb-3">
                                                                    <i class="bi bi-person-check text-warning" style="font-size: 2rem;"></i>
                                                                </div>
                                                                <h3 class="card-title mb-2">
                                                                    <?php
                                                                    $in_use_count = count(array_filter($employee_assets, function ($asset) {
                                                                        return $asset['status'] === 'in_use';
                                                                    }));
                                                                    echo $in_use_count;
                                                                    ?>
                                                                </h3>
                                                                <p class="card-text text-muted">In Use</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Additional Statistics -->
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <div class="card border-0 shadow-sm">
                                                            <div class="card-header bg-light border-0">
                                                                <h6 class="mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Asset Status Breakdown</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <?php
                                                                    $status_counts = [];
                                                                    foreach ($employee_assets as $asset) {
                                                                        $status = $asset['status'];
                                                                        if (!isset($status_counts[$status])) {
                                                                            $status_counts[$status] = 0;
                                                                        }
                                                                        $status_counts[$status]++;
                                                                    }
                                                                    
                                                                    $status_colors = [
                                                                        'serviceable' => 'success',
                                                                        'in_use' => 'primary', 
                                                                        'available' => 'secondary',
                                                                        'unserviceable' => 'danger',
                                                                        'maintenance' => 'warning'
                                                                    ];
                                                                    
                                                                    foreach ($status_counts as $status => $count): ?>
                                                                        <div class="col-md-3 col-sm-6 mb-3">
                                                                            <div class="d-flex align-items-center">
                                                                                <div class="me-3">
                                                                                    <i class="bi bi-circle-fill text-<?php echo $status_colors[$status] ?? 'secondary'; ?> me-2"></i>
                                                                                </div>
                                                                                <div>
                                                                                    <div class="fw-bold"><?php echo $count; ?></div>
                                                                                    <small class="text-muted"><?php echo ucfirst($status); ?></small>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                    
                                                                    <?php if (empty($status_counts)): ?>
                                                                        <div class="col-12 text-center text-muted">
                                                                            <i class="bi bi-info-circle me-2"></i>
                                                                            No asset data available
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        
        function exportEmployeeData() {
            let csv = 'Employee No,Name,Email,Office,Position,Employment Status,Clearance Status,Total Assets,Total Value\n';
            
            // Employee data
            csv += '<?php echo 
                '"' . addslashes($employee['employee_no'] ?? 'N/A') . '","' . 
                addslashes(($employee['firstname'] ?? '') . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['lastname'] ?? '')) . '","' . 
                addslashes($employee['email'] ?? '') . '","' . 
                addslashes($office_name) . '","' . 
                addslashes($employee['position'] ?? '') . '","' . 
                addslashes(ucfirst(str_replace('_', ' ', $employee['employment_status'] ?? 'permanent'))) . '","' . 
                addslashes(ucfirst($employee['computed_clearance_status'] ?? 'uncleared')) . '","' . 
                count($employee_assets) . '","' . 
                number_format(array_reduce($employee_assets, function($total, $asset) {
                    return $total + (isset($asset['unit_cost']) ? $asset['unit_cost'] : (isset($asset['value']) ? $asset['value'] : 0));
                }, 0), 2) . '"' 
            ; ?>' + '\n';
            
            // Add asset details
            csv += '\n\nAsset Details:\n';
            csv += 'Property No,Description,Category,Office,Status,Value,Date Acquired\n';
            
            <?php if (!empty($employee_assets)): ?>
                <?php foreach ($employee_assets as $asset): ?>
                    csv += '<?php echo 
                        '"' . addslashes($asset['property_no'] ?? 'N/A') . '","' . 
                        addslashes($asset['description']) . '","' . 
                        addslashes(($asset['category_code'] ?? 'N/A') . ' - ' . ($asset['category_name'] ?? '')) . '","' . 
                        addslashes($asset['office_name'] ?? 'N/A') . '","' . 
                        addslashes(ucfirst($asset['status'])) . '","' . 
                        (isset($asset['unit_cost']) ? number_format($asset['unit_cost'], 2) : (isset($asset['value']) ? number_format($asset['value'], 2) : '0.00')) . '","' . 
                        ($asset['acquisition_date'] ? date('M d, Y', strtotime($asset['acquisition_date'])) : 'N/A') . '"' 
                    ; ?>' + '\n';
                <?php endforeach; ?>
            <?php endif; ?>
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'employee_<?php echo $employee['employee_no']; ?>_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>

</html>