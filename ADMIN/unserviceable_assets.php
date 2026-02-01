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

logSystemAction($_SESSION['user_id'], 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php');

// Handle search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';

// Build query
$where_conditions = ["ai.status = 'unserviceable'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(ai.description LIKE ? OR ai.property_number LIKE ? OR ai.inventory_tag LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($office_filter)) {
    $where_conditions[] = "ai.office_id = ?";
    $params[] = $office_filter;
    $types .= 'i';
}

if (!empty($category_filter)) {
    $where_conditions[] = "ai.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get unserviceable assets
$unserviceable_assets = [];
$sql = "SELECT ai.*, ac.category_name, 
               ac.category_code, o.office_name, e.firstname, e.lastname, e.position
        FROM asset_items ai 
        LEFT JOIN asset_categories ac ON ai.category_id = ac.id 
        LEFT JOIN offices o ON ai.office_id = o.id 
        LEFT JOIN employees e ON ai.employee_id = e.id 
        $where_clause 
        ORDER BY ai.last_updated DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unserviceable_assets[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $unserviceable_assets[] = $row;
        }
    }
}

// Get statistics
$total_unserviceable = count($unserviceable_assets);
$total_value = array_sum(array_column($unserviceable_assets, 'value'));

// Get offices for filter
$offices = [];
$offices_result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
if ($offices_result) {
    while ($office = $offices_result->fetch_assoc()) {
        $offices[] = $office;
    }
}

// Get categories for filter
$categories = [];
$categories_result = $conn->query("SELECT id, category_name, category_code FROM asset_categories WHERE status = 'active' ORDER BY category_name");
if ($categories_result) {
    while ($category = $categories_result->fetch_assoc()) {
        $categories[] = $category;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unserviceable Assets - PIMS</title>
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
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #dc3545;
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid #dc3545;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #dc3545;
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-maintenance { background-color: #fff3cd; color: #856404; }
        .status-disposed { background-color: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
            border-radius: var(--border-radius);
            font-size: 0.8rem;
        }
        
        .search-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        @media print {
            .no-print { display: none !important; }
            .table-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Unserviceable Assets';
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
                        <i class="bi bi-x-circle"></i> Unserviceable Assets
                    </h1>
                    <p class="text-muted mb-0">Manage and track unserviceable and disposed assets</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="no-print">
                        <button class="btn btn-outline-danger btn-sm" onclick="exportToCSV()">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Total Unserviceable</h6>
                            <div class="stats-number"><?php echo $total_unserviceable; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Total Value</h6>
                            <div class="stats-number">₱<?php echo number_format($total_value, 2); ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-currency-peso" style="font-size: 2rem; color: #28a745;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Average Value</h6>
                            <div class="stats-number">₱<?php echo $total_unserviceable > 0 ? number_format($total_value / $total_unserviceable, 2) : '0.00'; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-calculator" style="font-size: 2rem; color: #17a2b8;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section no-print">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Assets</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search description, property number, or inventory tag..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Office</label>
                    <select class="form-select" id="officeFilter" name="office">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" id="categoryFilter" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                                <?php if (!empty($category['category_code'])): ?>
                                    (<?php echo htmlspecialchars($category['category_code']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="unserviceable_assets.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </div>

        <!-- Assets Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Value</th>
                            <th>Office</th>
                            <th>Assigned To</th>
                            <th>Last Updated</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unserviceable_assets)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2">No unserviceable assets found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($unserviceable_assets as $asset): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($asset['description']); ?></strong>
                                        <?php if (!empty($asset['property_number'])): ?>
                                            <br><small class="text-muted">Property No: <?php echo htmlspecialchars($asset['property_number']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($asset['inventory_tag'])): ?>
                                            <br><small class="text-muted">Tag: <?php echo htmlspecialchars($asset['inventory_tag']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?>
                                        <?php if (!empty($asset['category_code'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($asset['category_code']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-maintenance">
                                            Unserviceable
                                        </span>
                                    </td>
                                    <td class="text-value">₱<?php echo number_format($asset['value'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($asset['office_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($asset['firstname'])): ?>
                                            <?php echo htmlspecialchars($asset['firstname'] . ' ' . $asset['lastname']); ?>
                                            <?php if (!empty($asset['position'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($asset['position']); ?></small>
                                            <?php endif; ?>
                                        <?php elseif (!empty($asset['end_user'])): ?>
                                            <?php echo htmlspecialchars($asset['end_user']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($asset['last_updated'])); ?></td>
                                    <td class="no-print">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_asset_item.php?id=<?php echo $asset['id']; ?>" class="btn btn-outline-primary btn-action" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="create_redtag.php?asset_id=<?php echo $asset['id']; ?>&description=<?php echo urlencode($asset['description']); ?>&property_no=<?php echo urlencode($asset['property_number'] ?? ''); ?>&inventory_tag=<?php echo urlencode($asset['inventory_tag'] ?? ''); ?>&acquisition_date=<?php echo $asset['acquisition_date']; ?>&value=<?php echo $asset['value']; ?>&office_name=<?php echo urlencode($asset['office_name'] ?? ''); ?>" class="btn btn-outline-danger btn-action" title="Create Red Tag">
                                                <i class="bi bi-exclamation-triangle"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Office filter
            $('#officeFilter').on('change', function() {
                const officeValue = this.value;
                const currentUrl = new URL(window.location);
                if (officeValue) {
                    currentUrl.searchParams.set('office', officeValue);
                } else {
                    currentUrl.searchParams.delete('office');
                }
                // Preserve search parameter if exists
                const searchValue = currentUrl.searchParams.get('search');
                if (!searchValue) {
                    currentUrl.searchParams.delete('search');
                }
                // Preserve category parameter if exists
                const categoryValue = currentUrl.searchParams.get('category');
                if (!categoryValue) {
                    currentUrl.searchParams.delete('category');
                }
                window.location.href = currentUrl.toString();
            });
            
            // Category filter
            $('#categoryFilter').on('change', function() {
                const categoryValue = this.value;
                const currentUrl = new URL(window.location);
                if (categoryValue) {
                    currentUrl.searchParams.set('category', categoryValue);
                } else {
                    currentUrl.searchParams.delete('category');
                }
                // Preserve search parameter if exists
                const searchValue = currentUrl.searchParams.get('search');
                if (!searchValue) {
                    currentUrl.searchParams.delete('search');
                }
                // Preserve office parameter if exists
                const officeValue = currentUrl.searchParams.get('office');
                if (!officeValue) {
                    currentUrl.searchParams.delete('office');
                }
                window.location.href = currentUrl.toString();
            });
            
            // Search functionality with debounce
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                const searchValue = this.value.trim();
                
                searchTimeout = setTimeout(() => {
                    const currentUrl = new URL(window.location);
                    if (searchValue) {
                        currentUrl.searchParams.set('search', searchValue);
                    } else {
                        currentUrl.searchParams.delete('search');
                    }
                    // Preserve office parameter if exists
                    const officeValue = currentUrl.searchParams.get('office');
                    if (!officeValue) {
                        currentUrl.searchParams.delete('office');
                    }
                    // Preserve category parameter if exists
                    const categoryValue = currentUrl.searchParams.get('category');
                    if (!categoryValue) {
                        currentUrl.searchParams.delete('category');
                    }
                    window.location.href = currentUrl.toString();
                }, 500); // Wait 500ms after user stops typing
            });
        });
        
        function exportToCSV() {
            let csv = 'Description,Category,Status,Value,Office,Assigned To,Last Updated\n';
            
            <?php foreach ($unserviceable_assets as $asset): ?>
            csv += '<?php echo addslashes($asset['description']); ?>,';
            csv += '<?php echo addslashes($asset['category_name'] ?? 'N/A'); ?>,';
            csv += 'Unserviceable,';
            csv += '<?php echo $asset['value']; ?>,';
            csv += '<?php echo addslashes($asset['office_name'] ?? 'N/A'); ?>,';
            csv += '<?php echo addslashes(!empty($asset['firstname']) ? $asset['firstname'] . ' ' . $asset['lastname'] : ($asset['end_user'] ?? 'Unassigned')); ?>,';
            csv += '<?php echo date('Y-m-d', strtotime($asset['last_updated'])); ?>\n';
            <?php endforeach; ?>
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'unserviceable_assets_<?php echo date('Y-m-d'); ?>.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        function markAsServiceable(assetId) {
            if (confirm('Are you sure you want to mark this asset as serviceable?')) {
                fetch('process_asset_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'asset_id=' + assetId + '&status=available'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the asset status.');
                });
            }
        }
    </script>
</body>
</html>
