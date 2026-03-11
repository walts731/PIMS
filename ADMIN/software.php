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
        $expiry_date = $_POST['expiry_date'] ?? '';
        $status = trim($_POST['status'] ?? 'active');
        
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
                $sql = "INSERT INTO software (software_name, category, description, vendor, version, license_type, license_key, purchase_cost, purchase_date, expiry_date, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssdssss", $software_name, $category, $description, $vendor, $version, $license_type, $license_key, $purchase_cost, $purchase_date, $expiry_date, $status);
                
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSoftwareModal">
                        <i class="bi bi-plus-circle"></i> Add Software
                    </button>
                    <button class="btn btn-success btn-sm ms-2" onclick="exportSoftware()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_count; ?></div>
                    <div class="stats-label"><i class="bi bi-laptop"></i> Total Software</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number">₱<?php echo number_format($total_value, 2); ?></div>
                    <div class="stats-label"><i class="bi bi-cash"></i> Total Purchase Cost</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($categories); ?></div>
                    <div class="stats-label"><i class="bi bi-tags"></i> Categories</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $active_count; ?></div>
                    <div class="stats-label"><i class="bi bi-check-circle"></i> Active Licenses</div>
                </div>
            </div>
        </div>
        
        <!-- Software Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Software Records</h5>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="licenseFilter">
                                <option value="">All Licenses</option>
                                <?php foreach ($license_types as $license): ?>
                                    <option value="<?php echo htmlspecialchars($license); ?>" <?php echo $license_filter == $license ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($license); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search software..." value="<?php echo htmlspecialchars($search); ?>">
                                <span class="input-group-text" id="searchIndicator" style="display: none;">
                                    <i class="bi bi-search"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                                <i class="bi bi-x-circle"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="softwareTable">
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
                                <input type="text" name="category" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
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
                                <select name="license_type" class="form-select" required>
                                    <option value="">Select License Type</option>
                                    <option value="perpetual">Perpetual</option>
                                    <option value="annual">Annual</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="trial">Trial</option>
                                    <option value="freemium">Freemium</option>
                                    <option value="open-source">Open Source</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">License Key</label>
                                <input type="text" name="license_key" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Purchase Cost *</label>
                                <input type="number" name="purchase_cost" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="expired">Expired</option>
                                    <option value="pending">Pending</option>
                                </select>
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
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <script>
        // Filter functions - same as assets.php
        function applyFilters() {
            const category = document.getElementById('categoryFilter')?.value || '';
            const license = document.getElementById('licenseFilter')?.value || '';
            const status = document.getElementById('statusFilter')?.value || '';
            const search = document.getElementById('searchInput')?.value || '';
            
            console.log('Applying filters:', { category, license, status, search });
            
            // Build URL parameters like assets.php
            const currentUrl = new URL(window.location);
            
            // Update parameters
            if (category) {
                currentUrl.searchParams.set('category', category);
            } else {
                currentUrl.searchParams.delete('category');
            }
            if (license) {
                currentUrl.searchParams.set('license', license);
            } else {
                currentUrl.searchParams.delete('license');
            }
            if (status) {
                currentUrl.searchParams.set('status', status);
            } else {
                currentUrl.searchParams.delete('status');
            }
            if (search) {
                currentUrl.searchParams.set('search', search);
            } else {
                currentUrl.searchParams.delete('search');
            }
            
            // Remove page parameter if exists
            currentUrl.searchParams.delete('page');
            
            console.log('Navigating to:', currentUrl.toString());
            window.location.href = currentUrl.toString();
        }
        
        function clearFilters() {
            console.log('Clearing all filters');
            window.location.href = 'software.php';
        }
        
        // Export function
        function exportSoftware() {
            console.log('Exporting software data...');
            let csv = 'Software Name,Category,Description,Vendor,Version,License Type,License Key,Purchase Cost,Purchase Date,Expiry Date,Status\n';
            
            <?php if (!empty($software_data)): ?>
                <?php foreach ($software_data as $software): ?>
                    csv += <?php 
                        $data = [
                            'name' => $software['software_name'],
                            'category' => $software['category'],
                            'description' => $software['description'],
                            'vendor' => $software['vendor'],
                            'version' => $software['version'],
                            'license_type' => $software['license_type'],
                            'license_key' => $software['license_key'],
                            'purchase_cost' => $software['purchase_cost'],
                            'purchase_date' => $software['purchase_date'],
                            'expiry_date' => $software['expiry_date'],
                            'status' => $software['status']
                        ];
                        
                        // Build CSV string manually to avoid JSON issues
                        $name = str_replace('"', '""', htmlspecialchars($software['software_name']));
                        $category = str_replace('"', '""', htmlspecialchars($software['category']));
                        $description = str_replace('"', '""', htmlspecialchars($software['description']));
                        $vendor = str_replace('"', '""', htmlspecialchars($software['vendor']));
                        $version = str_replace('"', '""', htmlspecialchars($software['version']));
                        $license_type = str_replace('"', '""', htmlspecialchars($software['license_type']));
                        $license_key = str_replace('"', '""', htmlspecialchars($software['license_key']));
                        
                        $csv_line = "\"$name\",\"$category\",\"$description\",\"$vendor\",\"$version\",\"$license_type\",\"$license_key\"," . 
                                   $software['purchase_cost'] . "," . 
                                   ($software['purchase_date'] ? '"' . $software['purchase_date'] . '"' : '""') . "," .
                                   ($software['expiry_date'] ? '"' . $software['expiry_date'] . '"' : '""') . "," .
                                   '"' . $software['status'] . '"';
                        
                        echo "'" . addslashes($csv_line) . "'";
                    ?> + '\n';
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
        
        // View and Edit functions (placeholders)
        function viewSoftware(id) {
            console.log('Viewing software with ID:', id);
            alert('View functionality coming soon for ID: ' + id);
        }
        
        function editSoftware(id) {
            console.log('Editing software with ID:', id);
            alert('Edit functionality coming soon for ID: ' + id);
        }
        
        // Search on Enter key and auto-search
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchIndicator = document.getElementById('searchIndicator');
            const categoryFilter = document.getElementById('categoryFilter');
            const licenseFilter = document.getElementById('licenseFilter');
            const statusFilter = document.getElementById('statusFilter');
            let searchTimeout;
            
            // Add auto-filter for dropdown selects - same as assets.php
            if (categoryFilter) {
                categoryFilter.addEventListener('change', function() {
                    console.log('Category filter changed:', this.value);
                    applyFilters();
                });
            }
            
            if (licenseFilter) {
                licenseFilter.addEventListener('change', function() {
                    console.log('License filter changed:', this.value);
                    applyFilters();
                });
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    console.log('Status filter changed:', this.value);
                    applyFilters();
                });
            }
            
            if (searchInput) {
                // Auto-search as user types (with debouncing) - same as assets.php
                searchInput.addEventListener('input', function(e) {
                    console.log('Search input changed:', e.target.value);
                    
                    // Show loading indicator
                    if (searchIndicator) {
                        searchIndicator.style.display = 'block';
                        searchIndicator.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                    }
                    
                    // Clear previous timeout
                    clearTimeout(searchTimeout);
                    
                    // Set new timeout to search after user stops typing (500ms delay)
                    searchTimeout = setTimeout(function() {
                        console.log('Auto-search triggered after typing delay');
                        applyFilters();
                    }, 500);
                });
                
                // Keep Enter key support as well
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        console.log('Enter key pressed in search, applying filters immediately');
                        clearTimeout(searchTimeout); // Cancel auto-search
                        
                        // Show immediate loading indicator
                        if (searchIndicator) {
                            searchIndicator.style.display = 'block';
                            searchIndicator.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                        }
                        
                        applyFilters();
                    }
                });
                
                // Hide indicator when search loses focus (after a short delay)
                searchInput.addEventListener('blur', function() {
                    setTimeout(function() {
                        if (searchIndicator) {
                            searchIndicator.style.display = 'none';
                        }
                    }, 1000);
                });
            }
            
            // Log current filter values on page load
            console.log('Current filters:', {
                category: document.getElementById('categoryFilter')?.value || '',
                license: document.getElementById('licenseFilter')?.value || '',
                status: document.getElementById('statusFilter')?.value || '',
                search: document.getElementById('searchInput')?.value || ''
            });
        });
    </script>
</body>
</html>
