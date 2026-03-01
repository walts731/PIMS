<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log software page access
logSystemAction($_SESSION['user_id'], 'software_accessed', 'software', 'Accessed software page');

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$license_filter = isset($_GET['license']) ? $_GET['license'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

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

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $software_name = $_POST['software_name'];
        $category = $_POST['category'];
        $description = $_POST['description'];
        $vendor = $_POST['vendor'];
        $version = $_POST['version'];
        $license_type = $_POST['license_type'];
        $license_key = $_POST['license_key'];
        $purchase_date = $_POST['purchase_date'];
        $purchase_cost = floatval($_POST['purchase_cost']);
        $renewal_date = $_POST['renewal_date'];
        $renewal_cost = floatval($_POST['renewal_cost'] ?? 0);
        $status = $_POST['status'];
        $assigned_to = $_POST['assigned_to'] ?? '';
        $installation_date = $_POST['installation_date'];
        $notes = $_POST['notes'];
        
        // Handle file uploads (license documents, installation files)
        $license_doc = '';
        $installation_files = [];
        
        if (isset($_FILES['license_document']) && $_FILES['license_document']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/software/licenses/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = uniqid() . '_' . $_FILES['license_document']['name'];
            move_uploaded_file($_FILES['license_document']['tmp_name'], $upload_dir . $filename);
            $license_doc = $filename;
        }
        
        if (isset($_FILES['installation_files'])) {
            $upload_dir = '../uploads/software/installations/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['installation_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['installation_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . $_FILES['installation_files']['name'][$key];
                    move_uploaded_file($tmp_name, $upload_dir . $filename);
                    $installation_files[] = $filename;
                }
            }
        }
        
        $files_json = json_encode(['license_doc' => $license_doc, 'installation_files' => $installation_files]);
        
        $sql = "INSERT INTO software (software_name, category, description, vendor, version, license_type, license_key, purchase_date, purchase_cost, renewal_date, renewal_cost, status, assigned_to, installation_date, notes, files, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssssdssdsssssi', $software_name, $category, $description, $vendor, $version, $license_type, $license_key, $purchase_date, $purchase_cost, $renewal_date, $renewal_cost, $status, $assigned_to, $installation_date, $notes, $files_json, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Software added successfully!";
            logSystemAction($_SESSION['user_id'], 'software_added', 'software', "Added software: $software_name");
        } else {
            $_SESSION['error'] = "Error adding software.";
        }
        $stmt->close();
        
        header("Location: software.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Software Management - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="../assets/css/theme-custom.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
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
            background: linear-gradient(135deg, var(--primary-color) 0%, #4a5bf5 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .software-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .filter-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .table th {
            background: transparent;
            color: #333;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .table tbody tr:hover {
            background-color: rgba(25, 27, 169, 0.05);
        }
        
        .btn-software {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-software:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 27, 169, 0.3);
            color: white;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
            font-size: 0.875rem;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .btn-outline-secondary {
            border: 1px solid #6c757d;
            color: #6c757d;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
            font-size: 0.875rem;
            background: white;
        }
        
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: var(--border-radius-lg);
            border-bottom-left-radius: var(--border-radius-lg);
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: var(--border-radius-lg);
            border-bottom-right-radius: var(--border-radius-lg);
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-expired { background: #fff3cd; color: #856404; }
        .status-pending { background: #cce5ff; color: #004085; }
        
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            
            .page-header, .filter-card, .no-print {
                display: none !important;
            }
            
            .software-card {
                box-shadow: none;
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .table {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .table th {
                background: #f8f9fa !important;
                color: #000 !important;
                border: 1px solid #000;
            }
            
            .table td {
                border: 1px solid #000;
            }
            
            @page {
                size: landscape;
                margin: 0.5in;
            }
            
            html {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Software Management';
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
                <div class="col-md-6">
                    <h1 class="mb-2">
                        <i class="bi bi-code-slash"></i> Software Management
                    </h1>
                    <p class="text-muted mb-0">Manage software licenses and installations</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-secondary" onclick="importCSV()">
                            <i class="bi bi-upload"></i> Import CSV
                        </button>
                        <button class="btn btn-export" onclick="exportCSV()">
                            <i class="bi bi-file-earmark-csv"></i> Export CSV
                        </button>
                        <button class="btn btn-export" onclick="exportPDF()">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </button>
                    </div>
                    <button class="btn btn-software ms-2" data-bs-toggle="modal" data-bs-target="#addSoftwareModal">
                        <i class="bi bi-plus-circle"></i> Add Software
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_count; ?></div>
                    <div class="stats-label">Total Software</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">₱<?php echo number_format($total_value, 2); ?></div>
                    <div class="stats-label">Total Purchase Cost</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($categories); ?></div>
                    <div class="stats-label">Categories</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php 
                        $active_count = 0;
                        foreach ($software_data as $software) {
                            if ($software['status'] === 'active') $active_count++;
                        }
                        echo $active_count;
                    ?></div>
                    <div class="stats-label">Active Licenses</div>
                </div>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="filter-card no-print">
            <h5 class="mb-3"><i class="bi bi-funnel"></i> Filters</h5>
            <form method="GET" action="software.php">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search software, vendor, license key...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">License Type</label>
                        <select name="license" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($license_types as $license): ?>
                                <option value="<?php echo htmlspecialchars($license); ?>" <?php echo $license_filter === $license ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($license); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <a href="software.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Software Table -->
        <div class="software-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Software Name</th>
                            <th>Category</th>
                            <th>Vendor</th>
                            <th>Version</th>
                            <th>License Type</th>
                            <th>Purchase Cost</th>
                            <th>Renewal Date</th>
                            <th>Status</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($software_data)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No software items found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($software_data as $software): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($software['software_name']); ?></strong>
                                        <?php if (!empty($software['license_key'])): ?>
                                            <br><small class="text-muted">Key: <?php echo htmlspecialchars(substr($software['license_key'], 0, 10)) . '...'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($software['category']); ?></td>
                                    <td><?php echo htmlspecialchars($software['vendor']); ?></td>
                                    <td><?php echo htmlspecialchars($software['version']); ?></td>
                                    <td><?php echo htmlspecialchars($software['license_type']); ?></td>
                                    <td>₱<?php echo number_format($software['purchase_cost'], 2); ?></td>
                                    <td><?php echo $software['renewal_date'] ? date('M j, Y', strtotime($software['renewal_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $software['status']; ?>">
                                            <?php echo ucfirst($software['status']); ?>
                                        </span>
                                    </td>
                                    <td class="no-print">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewSoftware(<?php echo $software['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="editSoftware(<?php echo $software['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Add Software Modal -->
    <div class="modal fade" id="addSoftwareModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Software</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="software.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Basic Information</h6>
                                <div class="mb-3">
                                    <label class="form-label">Software Name *</label>
                                    <input type="text" name="software_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="Operating System">Operating System</option>
                                        <option value="Office Suite">Office Suite</option>
                                        <option value="Antivirus">Antivirus</option>
                                        <option value="Database">Database</option>
                                        <option value="Development Tools">Development Tools</option>
                                        <option value="Design Software">Design Software</option>
                                        <option value="Accounting">Accounting</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Vendor *</label>
                                    <input type="text" name="vendor" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Version</label>
                                    <input type="text" name="version" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">License Information</h6>
                                <div class="mb-3">
                                    <label class="form-label">License Type *</label>
                                    <select name="license_type" class="form-select" required>
                                        <option value="">Select License Type</option>
                                        <option value="Perpetual">Perpetual</option>
                                        <option value="Annual Subscription">Annual Subscription</option>
                                        <option value="Monthly Subscription">Monthly Subscription</option>
                                        <option value="Open Source">Open Source</option>
                                        <option value="Freeware">Freeware</option>
                                        <option value="Trial">Trial</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">License Key</label>
                                    <input type="text" name="license_key" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Purchase Date *</label>
                                    <input type="date" name="purchase_date" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Purchase Cost *</label>
                                    <input type="number" name="purchase_cost" class="form-control" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Renewal Date</label>
                                    <input type="date" name="renewal_date" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Renewal Cost</label>
                                    <input type="number" name="renewal_cost" class="form-control" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="expired">Expired</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Assigned To</label>
                                <input type="text" name="assigned_to" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Installation Date</label>
                                <input type="date" name="installation_date" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Documents</label>
                                <div class="mb-2">
                                    <label class="form-label">License Document</label>
                                    <input type="file" name="license_document" class="form-control" accept=".pdf,.doc,.docx">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Installation Files</label>
                                    <input type="file" name="installation_files[]" class="form-control" multiple accept=".exe,.msi,.zip,.rar">
                                </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <script>
        // Import CSV function
        function importCSV() {
            // Create a hidden file input
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.csv';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Create FormData for file upload
                    const formData = new FormData();
                    formData.append('csv_file', file);
                    
                    // Show loading state
                    const originalText = e.target.textContent;
                    e.target.textContent = 'Uploading...';
                    e.target.disabled = true;
                    
                    // Send file to server (you'll need to create process_software_import.php)
                    fetch('process_software_import.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('CSV imported successfully! ' + data.message);
                            location.reload();
                        } else {
                            alert('Error importing CSV: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error uploading file: ' + error.message);
                    })
                    .finally(() => {
                        // Restore button state
                        e.target.textContent = originalText;
                        e.target.disabled = false;
                    });
                }
            };
            input.click();
        }
        
        // Export CSV function
        function exportCSV() {
            window.location.href = 'export_software_csv.php';
        }
        
        // Export PDF function
        function exportPDF() {
            window.open('export_software_pdf.php', '_blank');
        }
        
        // View software item
        function viewSoftware(id) {
            // Load view modal via AJAX
            fetch('view_software_modal.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    // Remove existing modal if any
                    const existingModal = document.getElementById('viewSoftwareModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    // Add modal to body
                    document.body.insertAdjacentHTML('beforeend', html);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('viewSoftwareModal'));
                    modal.show();
                    
                    // Clean up modal after hidden
                    document.getElementById('viewSoftwareModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                })
                .catch(error => {
                    alert('Error loading software details: ' + error.message);
                });
        }
        
        // Edit software item
        function editSoftware(id) {
            loadEditModal(id);
        }
        
        // Load edit modal
        function loadEditModal(id) {
            // Load edit modal via AJAX
            fetch('edit_software_modal.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    // Remove existing modal if any
                    const existingModal = document.getElementById('editSoftwareModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    // Add modal to body
                    document.body.insertAdjacentHTML('beforeend', html);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editSoftwareModal'));
                    modal.show();
                    
                    // Clean up modal after hidden
                    document.getElementById('editSoftwareModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                })
                .catch(error => {
                    alert('Error loading edit form: ' + error.message);
                });
        }
    </script>
</body>
</html>
