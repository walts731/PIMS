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

// Log software page access
logSystemAction($_SESSION['user_id'], 'access', 'software', 'Admin accessed software page');

// Handle CRUD operations
$message = '';
$message_type = '';

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $software_name = trim($_POST['software_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $vendor = trim($_POST['vendor'] ?? '');
        $version = trim($_POST['version'] ?? '');
        $license_type = trim($_POST['license_type'] ?? '');
        $license_key = trim($_POST['license_key'] ?? '');
        $purchase_cost = floatval($_POST['purchase_cost'] ?? 0);
        $purchase_date = $_POST['purchase_date'] ?? '';
        $renewal_date = $_POST['renewal_date'] ?? '';
        $renewal_cost = floatval($_POST['renewal_cost'] ?? 0);
        $assigned_to = trim($_POST['assigned_to'] ?? '');
        $installation_date = $_POST['installation_date'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        
        // Convert empty date strings to NULL for database
        $purchase_date = empty($purchase_date) ? NULL : $purchase_date;
        $renewal_date = empty($renewal_date) ? NULL : $renewal_date;
        $installation_date = empty($installation_date) ? NULL : $installation_date;
        
        // Validation
        if (empty($software_name)) {
            $message = "Software name is required.";
            $message_type = "danger";
        } elseif (empty($category)) {
            $message = "Category is required.";
            $message_type = "danger";
        } elseif (empty($vendor)) {
            $message = "Vendor is required.";
            $message_type = "danger";
        } elseif ($purchase_cost < 0) {
            $message = "Purchase cost cannot be negative.";
            $message_type = "danger";
        } else {
            try {
                $sql = "INSERT INTO software (software_name, category, description, vendor, version, license_type, license_key, purchase_cost, purchase_date, renewal_date, renewal_cost, assigned_to, installation_date, notes, status, created_at, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssdsssssssi", $software_name, $category, $description, $vendor, $version, $license_type, $license_key, $purchase_cost, $purchase_date, $renewal_date, $renewal_cost, $assigned_to, $installation_date, $notes, $status, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $message = "Software added successfully!";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'software_added', 'software', "Added software: $software_name");
                } else {
                    throw new Exception("Failed to add software: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = "Error adding software: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$license_filter = isset($_GET['license']) ? trim($_GET['license']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE conditions
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(software_name LIKE ? OR description LIKE ? OR vendor LIKE ? OR license_key LIKE ?)";
    $search_param = '%' . $search . '%';
    $params = array_fill(0, 4, $search_param);
    $types = 'ssss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($license_filter)) {
    $where_conditions[] = "license_type = ?";
    $params[] = $license_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get software data
$software_data = [];
$total_value = 0;
$total_count = 0;

$sql = "SELECT * FROM software $where_clause ORDER BY software_name ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $software_data[] = $row;
    $total_value += $row['purchase_cost'];
    $total_count++;
}
$stmt->close();

// Get unique categories and license types for filters
$categories = [];
$license_types = [];

$cat_sql = "SELECT DISTINCT category FROM software ORDER BY category";
$cat_result = $conn->query($cat_sql);
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

$license_sql = "SELECT DISTINCT license_type FROM software ORDER BY license_type";
$license_result = $conn->query($license_sql);
while ($row = $license_result->fetch_assoc()) {
    $license_types[] = $row['license_type'];
}

// Calculate active licenses count
$active_count = 0;
foreach ($software_data as $software) {
    if ($software['status'] === 'active') $active_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software - PIMS</title>
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
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
    
    <style>
        /* Ensure status column is always visible and prominent */
        #softwareTable th:nth-child(6),
        #softwareTable td:nth-child(6) {
            display: table-cell !important;
            visibility: visible !important;
            min-width: 120px;
            max-width: 120px;
            text-align: center;
        }
        
        /* Status badge styling - more prominent */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
            display: inline-block;
            white-space: nowrap;
            min-width: 80px;
        }
        
        /* Enhanced status colors */
        .bg-success.status-badge {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            border: 1px solid #28a745;
        }
        
        .bg-secondary.status-badge {
            background: linear-gradient(135deg, #6c757d, #868e96) !important;
            border: 1px solid #6c757d;
        }
        
        .bg-danger.status-badge {
            background: linear-gradient(135deg, #dc3545, #e74c3c) !important;
            border: 1px solid #dc3545;
        }
        
        .bg-warning.status-badge {
            background: linear-gradient(135deg, #ffc107, #ffb347) !important;
            border: 1px solid #ffc107;
            color: #212529 !important;
        }
        
        /* Ensure table is responsive but keeps all columns */
        .table-responsive {
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            #softwareTable th:nth-child(6),
            #softwareTable td:nth-child(6) {
                min-width: 100px;
                max-width: 100px;
                font-size: 0.75rem;
            }
            
            .status-badge {
                padding: 0.35rem 0.6rem;
                font-size: 0.75rem;
                min-width: 70px;
            }
        }
        
        @media (max-width: 576px) {
            #softwareTable th:nth-child(6),
            #softwareTable td:nth-child(6) {
                min-width: 90px;
                max-width: 90px;
                font-size: 0.7rem;
            }
            
            .status-badge {
                padding: 0.3rem 0.5rem;
                font-size: 0.7rem;
                min-width: 60px;
            }
        }
    </style>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Software';
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
                        <i class="bi bi-laptop"></i> Software
                    </h1>
                    <p class="text-muted mb-0">Manage software licenses and applications</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addSoftwareModal">
                                    <i class="bi bi-plus-circle"></i> Add Software
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="exportSoftware()">
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
        
        <!-- Software Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Software Records</h5>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="softwareTable" style="width: 100%">
                    <thead class="table-light">
                        <tr>
                            <th>Software Name</th>
                            <th>Category</th>
                            <th>Vendor</th>
                            <th>License Type</th>
                            <th>Purchase Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($software_data)): ?>
                            <?php foreach ($software_data as $software): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($software['software_name']); ?></strong>
                                        <?php if (!empty($software['version'])): ?>
                                            <br><small class="text-muted">v<?php echo htmlspecialchars($software['version']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($software['category']); ?></td>
                                    <td><?php echo htmlspecialchars($software['vendor']); ?></td>
                                    <td><?php echo htmlspecialchars($software['license_type']); ?></td>
                                    <td>₱<?php echo number_format($software['purchase_cost'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($software['status']) {
                                            case 'active': $status_class = 'bg-success'; break;
                                            case 'inactive': $status_class = 'bg-secondary'; break;
                                            case 'expired': $status_class = 'bg-danger'; break;
                                            case 'pending': $status_class = 'bg-warning'; break;
                                            default: $status_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?> status-badge">
                                            <?php echo ucfirst(htmlspecialchars($software['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewSoftware(<?php echo $software['id']; ?>)">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="editSoftware(<?php echo $software['id']; ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No software items found. Click "Add Software" to create your first item.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    
    <!-- Add Software Modal -->
    <div class="modal fade" id="addSoftwareModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Software</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="software.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Software Name *</label>
                                <input type="text" name="software_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="Operating System">Operating System</option>
                                    <option value="Office Suite">Office Suite</option>
                                    <option value="Antivirus">Antivirus</option>
                                    <option value="Database">Database</option>
                                    <option value="Development Tools">Development Tools</option>
                                    <option value="Design Software">Design Software</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="Communication">Communication</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor *</label>
                                <input type="text" name="vendor" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Version</label>
                                <input type="text" name="version" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">License Type *</label>
                                <select name="license_type" class="form-control" required>
                                    <option value="">Select License Type</option>
                                    <option value="Free">Free</option>
                                    <option value="Open Source">Open Source</option>
                                    <option value="Commercial">Commercial</option>
                                    <option value="Subscription">Subscription</option>
                                    <option value="Trial">Trial</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">License Key</label>
                                <input type="text" name="license_key" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Purchase Date *</label>
                                <input type="date" name="purchase_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purchase Cost *</label>
                                <input type="number" name="purchase_cost" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Renewal Date</label>
                                <input type="date" name="renewal_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Renewal Cost</label>
                                <input type="number" name="renewal_cost" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-control" required>
                                    <option value="">Select Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assigned To</label>
                                <input type="text" name="assigned_to" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Installation Date</label>
                                <input type="date" name="installation_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Software</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Software Modal -->
    <div class="modal fade" id="viewSoftwareModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Software Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewSoftwareContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Software Modal -->
    <div class="modal fade" id="editSoftwareModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Software Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editSoftwareForm" method="POST" action="process_software.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editSoftwareId">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Software Name *</label>
                                <input type="text" name="software_name" id="editSoftwareName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category" id="editCategory" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="Operating System">Operating System</option>
                                    <option value="Office Suite">Office Suite</option>
                                    <option value="Antivirus">Antivirus</option>
                                    <option value="Database">Database</option>
                                    <option value="Development Tools">Development Tools</option>
                                    <option value="Design Software">Design Software</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="Communication">Communication</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor *</label>
                                <input type="text" name="vendor" id="editVendor" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Version</label>
                                <input type="text" name="version" id="editVersion" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">License Type *</label>
                                <select name="license_type" id="editLicenseType" class="form-control" required>
                                    <option value="">Select License Type</option>
                                    <option value="Free">Free</option>
                                    <option value="Open Source">Open Source</option>
                                    <option value="Commercial">Commercial</option>
                                    <option value="Subscription">Subscription</option>
                                    <option value="Trial">Trial</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">License Key</label>
                                <input type="text" name="license_key" id="editLicenseKey" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Purchase Date *</label>
                                <input type="date" name="purchase_date" id="editPurchaseDate" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purchase Cost *</label>
                                <input type="number" name="purchase_cost" id="editPurchaseCost" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Renewal Date</label>
                                <input type="date" name="renewal_date" id="editRenewalDate" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Renewal Cost</label>
                                <input type="number" name="renewal_cost" id="editRenewalCost" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" id="editStatus" class="form-control" required>
                                    <option value="">Select Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assigned To</label>
                                <input type="text" name="assigned_to" id="editAssignedTo" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Installation Date</label>
                                <input type="date" name="installation_date" id="editInstallationDate" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" id="editNotes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Software</button>
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
        // Filter functions - same as infrastructure.php
        function applyFilters() {
            const category = document.getElementById('categoryFilter')?.value || '';
            const license = document.getElementById('licenseFilter')?.value || '';
            const status = document.getElementById('statusFilter')?.value || '';
            
            const params = new URLSearchParams();
            if (category) params.set('category', category);
            if (license) params.set('license', license);
            if (status) params.set('status', status);
            
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = newUrl;
        }
        
        function clearFilters() {
            window.location.href = 'software.php';
        }
        
        // DataTables custom filtering function
        function applyDataTablesFilters() {
            const table = $('#softwareTable').DataTable();
            const category = $('#categoryFilter').val();
            const license = $('#licenseFilter').val();
            const status = $('#statusFilter').val();
            
            // Clear all previous search functions
            $.fn.dataTable.ext.search = [];
            
            // Apply custom search function for each column
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const categoryColumn = data[1]; // Category column (index 1)
                const licenseColumn = data[3]; // License Type column (index 3)
                const statusColumn = data[5]; // Status column (index 5) - contains HTML
                
                // Category filter
                if (category && categoryColumn !== category) {
                    return false;
                }
                
                // License filter
                if (license && licenseColumn !== license) {
                    return false;
                }
                
                // Status filter - extract text from HTML
                if (status) {
                    // Create a temporary element to extract text from HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = statusColumn;
                    const statusText = tempDiv.textContent || tempDiv.innerText || '';
                    const normalizedStatus = statusText.toLowerCase().trim();
                    
                    if (normalizedStatus !== status) {
                        return false;
                    }
                }
                
                return true;
            });
            
            // Redraw the table
            table.draw();
        }
        
        // Export function
        function exportSoftware() {
            console.log('Exporting software data...');
            let csv = 'Software Name,Category,Description,Vendor,Version,License Type,License Key,Purchase Cost,Purchase Date,Renewal Date,Status\n';
            
            <?php if (!empty($software_data)): ?>
                <?php foreach ($software_data as $software): ?>
                    csv += `<?php echo htmlspecialchars($software['software_name']); ?>,<?php echo htmlspecialchars($software['category']); ?>,<?php echo htmlspecialchars($software['description']); ?>,<?php echo htmlspecialchars($software['vendor']); ?>,<?php echo htmlspecialchars($software['version']); ?>,<?php echo htmlspecialchars($software['license_type']); ?>,<?php echo htmlspecialchars($software['license_key']); ?>,<?php echo $software['purchase_cost']; ?>,<?php echo $software['purchase_date']; ?>,<?php echo $software['renewal_date']; ?>,<?php echo htmlspecialchars($software['status']); ?>\n`;
                <?php endforeach; ?>
            <?php endif; ?>
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'software_export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // View Software function
        function viewSoftware(id) {
            $.ajax({
                url: 'api/software.php?action=get&id=' + id,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Software Name</h6>
                                    <p>${data.software_name || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Category</h6>
                                    <p>${data.category || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Description</h6>
                                    <p>${data.description || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Vendor</h6>
                                    <p>${data.vendor || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Version</h6>
                                    <p>${data.version || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>License Type</h6>
                                    <p>${data.license_type || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>License Key</h6>
                                    <p>${data.license_key || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Purchase Date</h6>
                                    <p>${data.purchase_date ? new Date(data.purchase_date).toLocaleDateString() : 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Purchase Cost</h6>
                                    <p>₱${parseFloat(data.purchase_cost || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Renewal Date</h6>
                                    <p>${data.renewal_date ? new Date(data.renewal_date).toLocaleDateString() : 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Renewal Cost</h6>
                                    <p>₱${parseFloat(data.renewal_cost || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Status</h6>
                                    <p><span class="status-badge bg-${data.status === 'Active' ? 'success' : data.status === 'Expired' ? 'danger' : data.status === 'Pending' ? 'warning' : 'secondary'}">${data.status || 'N/A'}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Assigned To</h6>
                                    <p>${data.assigned_to || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6>Installation Date</h6>
                                    <p>${data.installation_date ? new Date(data.installation_date).toLocaleDateString() : 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Notes</h6>
                                    <p>${data.notes || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Record Information</h6>
                                    <p><small class="text-muted">
                                        Created: ${data.created_at ? new Date(data.created_at).toLocaleString() : 'N/A'}<br>
                                        Last Updated: ${data.updated_at ? new Date(data.updated_at).toLocaleString() : 'Never'}
                                    </small></p>
                                </div>
                            </div>
                        `;
                        
                        // Add files if they exist
                        if (data.files && (data.files.license_doc || (data.files.installation_files && data.files.installation_files.length > 0))) {
                            html += `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6>Files</h6>
                            `;
                            
                            if (data.files.license_doc) {
                                html += `
                                    <div class="mb-2">
                                        <i class="bi bi-file-earmark-text"></i> 
                                        <a href="../uploads/software/licenses/${data.files.license_doc}" target="_blank">License Document</a>
                                    </div>
                                `;
                            }
                            
                            if (data.files.installation_files && data.files.installation_files.length > 0) {
                                data.files.installation_files.forEach(function(file, index) {
                                    html += `
                                        <div class="mb-2">
                                            <i class="bi bi-file-earmark-zip"></i> 
                                            <a href="../uploads/software/installations/${file}" target="_blank">Installation File ${index + 1}</a>
                                        </div>
                                    `;
                                });
                            }
                            
                            html += `
                                    </div>
                                </div>
                            `;
                        }
                        
                        $('#viewSoftwareContent').html(html);
                        $('#viewSoftwareModal').modal('show');
                    } else {
                        alert('Error loading software details: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading software details. Please try again.');
                }
            });
        }
        
        // Edit Software function
        function editSoftware(id) {
            $.ajax({
                url: 'api/software.php?action=get&id=' + id,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Populate form fields
                        $('#editSoftwareId').val(data.id);
                        $('#editSoftwareName').val(data.software_name || '');
                        $('#editCategory').val(data.category || '');
                        $('#editDescription').val(data.description || '');
                        $('#editVendor').val(data.vendor || '');
                        $('#editVersion').val(data.version || '');
                        $('#editLicenseType').val(data.license_type || '');
                        $('#editLicenseKey').val(data.license_key || '');
                        $('#editPurchaseDate').val(data.purchase_date || '');
                        $('#editPurchaseCost').val(data.purchase_cost || '');
                        $('#editRenewalDate').val(data.renewal_date || '');
                        $('#editRenewalCost').val(data.renewal_cost || '');
                        $('#editStatus').val(data.status || '');
                        $('#editAssignedTo').val(data.assigned_to || '');
                        $('#editInstallationDate').val(data.installation_date || '');
                        $('#editNotes').val(data.notes || '');
                        
                        $('#editSoftwareModal').modal('show');
                    } else {
                        alert('Error loading software data: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading software data. Please try again.');
                }
            });
        }
        
        // Handle edit form submission via AJAX
        $('#editSoftwareForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Close modal and show success message
                        $('#editSoftwareModal').modal('hide');
                        
                        // Show success message
                        const successHtml = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        
                        // Insert success message after page header
                        $('.page-header .row').first().after(successHtml);
                        
                        // Reload page to show updated data
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    // Show error message
                    alert('Error updating software item. Please try again.');
                    console.error('AJAX Error:', error);
                }
            });
        });
        
        // Initialize DataTable
        document.addEventListener('DOMContentLoaded', function() {
            $('#softwareTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'asc']],
                language: {
                    search: "Search software:",
                    lengthMenu: "Show _MENU_ software per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ software",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"row"<"col-md-3"l><"col-md-2 category-filter-container"><"col-md-2 license-filter-container"><"col-md-2 status-filter-container"><"col-md-3"f>>rtip',
                initComplete: function(settings, json) {
                    // Add category filter to DataTables
                    $('.category-filter-container').html(`
                        <select id="categoryFilter" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    `);
                    
                    // Add license filter to DataTables
                    $('.license-filter-container').html(`
                        <select id="licenseFilter" class="form-select form-select-sm">
                            <option value="">All Licenses</option>
                            <?php foreach ($license_types as $license): ?>
                                <option value="<?php echo $license; ?>" <?php echo $license_filter == $license ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($license); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    `);
                    
                    // Add status filter to DataTables
                    $('.status-filter-container').html(`
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    `);
                    
                    // Apply current URL filters to DataTables
                    <?php if (!empty($category_filter)): ?>
                    $('#categoryFilter').val('<?php echo $category_filter; ?>');
                    <?php endif; ?>
                    <?php if (!empty($license_filter)): ?>
                    $('#licenseFilter').val('<?php echo $license_filter; ?>');
                    <?php endif; ?>
                    <?php if (!empty($status_filter)): ?>
                    $('#statusFilter').val('<?php echo $status_filter; ?>');
                    <?php endif; ?>
                    
                    // Apply filter events with DataTables API
                    $('#categoryFilter, #licenseFilter, #statusFilter').on('change', function() {
                        applyDataTablesFilters();
                    });
                    
                    // Initial filter application
                    applyDataTablesFilters();
                },
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Print',
                        className: 'btn btn-primary btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    }
                ]
            });
            
            var table = $('#softwareTable').DataTable();
        });
    </script>
    
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
</div> <!-- Close main-content -->
</div> <!-- Close main-wrapper -->

<?php require_once 'includes/logout-modal.php'; ?>
<?php require_once 'includes/change-password-modal.php'; ?>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
