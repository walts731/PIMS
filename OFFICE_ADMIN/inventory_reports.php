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

// Check for export request
if (isset($_GET['export']) && $_GET['export'] == '1') {
    exportInventoryData();
    exit;
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

// Log inventory reports access
logSystemAction($_SESSION['user_id'], 'access', 'inventory_reports', 'Office admin accessed inventory reports');

// Get office-specific inventory data
$user_office_id = $_SESSION['office_id'] ?? null;
$inventory_data = [];
$filters = [
    'status' => $_GET['status'] ?? 'all',
    'category' => $_GET['category'] ?? 'all',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Check database connection
if (!$conn || $conn->connect_error) {
    $inventory_data['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // ===== ASSET INVENTORY =====
        $asset_query = "SELECT 
            ai.id,
            ai.description,
            ai.property_number,
            ai.inventory_tag,
            ai.status,
            ai.value,
            ai.acquisition_date,
            ai.last_updated,
            ai.end_user,
            ac.category_name,
            subcat.sub_category_name,
            o.office_name,
            CONCAT(e.firstname, ' ', e.lastname) as employee_name
            FROM asset_items ai
            LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
            LEFT JOIN asset_sub_categories subcat ON ai.asset_subcategory_id = subcat.id
            LEFT JOIN offices o ON ai.office_id = o.id
            LEFT JOIN employees e ON ai.employee_id = e.id
            WHERE ai.office_id = ?";
        
        $params = [$user_office_id];
        $types = "i";
        
        // Apply filters
        if ($filters['status'] !== 'all') {
            $asset_query .= " AND ai.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if ($filters['category'] !== 'all') {
            $asset_query .= " AND ai.asset_category_id = ?";
            $params[] = $filters['category'];
            $types .= "i";
        }
        
        if (!empty($filters['search'])) {
            $asset_query .= " AND (ai.description LIKE ? OR ai.property_number LIKE ? OR ai.inventory_tag LIKE ?)";
            $search_term = "%" . $filters['search'] . "%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "sss";
        }
        
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $asset_query .= " AND ai.acquisition_date BETWEEN ? AND ?";
            $params[] = $filters['date_from'];
            $params[] = $filters['date_to'];
            $types .= "ss";
        }
        
        $asset_query .= " ORDER BY ai.last_updated DESC";
        
        $stmt = $conn->prepare($asset_query);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $asset_result = $stmt->get_result();
            $inventory_data['assets'] = $asset_result->fetch_all(MYSQLI_ASSOC);
        }
        
        // ===== CONSUMABLE INVENTORY =====
        $consumable_query = "SELECT 
            c.id,
            c.description,
            c.quantity,
            c.unit,
            c.unit_cost,
            c.reorder_level,
            c.created_at,
            c.updated_at,
            o.office_name
            FROM consumables c
            LEFT JOIN offices o ON c.office_id = o.id
            WHERE c.office_id = ?";
        
        $consumable_params = [$user_office_id];
        $consumable_types = "i";
        
        // Apply consumable filters
        if (!empty($filters['search'])) {
            $consumable_query .= " AND c.description LIKE ?";
            $search_term = "%" . $filters['search'] . "%";
            $consumable_params[] = $search_term;
            $consumable_types .= "s";
        }
        
        $consumable_query .= " ORDER BY c.updated_at DESC";
        
        $stmt = $conn->prepare($consumable_query);
        if ($stmt) {
            $stmt->bind_param($consumable_types, ...$consumable_params);
            $stmt->execute();
            $consumable_result = $stmt->get_result();
            $inventory_data['consumables'] = $consumable_result->fetch_all(MYSQLI_ASSOC);
        }
        
        // ===== INVENTORY SUMMARY =====
        $summary_query = "SELECT 
            COUNT(DISTINCT ai.id) as total_assets,
            SUM(CASE WHEN ai.status IN ('serviceable', 'available', 'in_use') THEN 1 ELSE 0 END) as functional_assets,
            SUM(CASE WHEN ai.status IN ('unserviceable', 'maintenance', 'disposed') THEN 1 ELSE 0 END) as non_functional_assets,
            SUM(CASE WHEN ai.status = 'no_tag' THEN 1 ELSE 0 END) as untagged_assets,
            COALESCE(SUM(ai.value), 0) as total_asset_value,
            COUNT(DISTINCT c.id) as total_consumables,
            SUM(c.quantity) as total_consumable_quantity,
            SUM(CASE WHEN c.quantity <= c.reorder_level THEN 1 ELSE 0 END) as low_stock_count,
            COALESCE(SUM(c.quantity * c.unit_cost), 0) as total_consumable_value
            FROM asset_items ai
            LEFT JOIN consumables c ON ai.office_id = c.office_id
            WHERE ai.office_id = ? OR c.office_id = ?";
        
        $stmt = $conn->prepare($summary_query);
        if ($stmt) {
            $stmt->bind_param("ii", $user_office_id, $user_office_id);
            $stmt->execute();
            $summary_result = $stmt->get_result();
            $inventory_data['summary'] = $summary_result->fetch_assoc();
        }
        
        // ===== CATEGORIES FOR FILTER =====
        $categories_query = "SELECT DISTINCT ac.id, ac.category_name 
            FROM asset_categories ac
            INNER JOIN asset_items ai ON ac.id = ai.asset_category_id
            WHERE ai.office_id = ?
            ORDER BY ac.category_name";
        
        $stmt = $conn->prepare($categories_query);
        if ($stmt) {
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $categories_result = $stmt->get_result();
            $inventory_data['categories'] = $categories_result->fetch_all(MYSQLI_ASSOC);
        }
        
    } catch (Exception $e) {
        $inventory_data['error'] = "Error fetching inventory data: " . $e->getMessage();
        error_log("Inventory Reports Error: " . $e->getMessage());
    }
}

// Set default values if not set
$defaults = [
    'assets' => [],
    'consumables' => [],
    'summary' => [
        'total_assets' => 0,
        'functional_assets' => 0,
        'non_functional_assets' => 0,
        'untagged_assets' => 0,
        'total_asset_value' => 0,
        'total_consumables' => 0,
        'total_consumable_quantity' => 0,
        'low_stock_count' => 0,
        'total_consumable_value' => 0
    ],
    'categories' => []
];

foreach ($defaults as $key => $value) {
    if (!isset($inventory_data[$key])) {
        $inventory_data[$key] = is_array($value) ? $value : $value;
    }
}

// Export Inventory Data Function
function exportInventoryData() {
    global $conn, $user_office_id;
    
    // Get current filters
    $filters = [
        'status' => $_GET['status'] ?? 'all',
        'category' => $_GET['category'] ?? 'all',
        'search' => $_GET['search'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? ''
    ];
    
    // Get filtered data (reuse existing logic)
    $asset_query = "SELECT 
        ai.id,
        ai.description,
        ai.property_number,
        ai.inventory_tag,
        ai.status,
        ai.acquisition_date,
        ai.last_updated,
        ai.end_user,
        ac.category_name,
        subcat.sub_category_name,
        o.office_name,
        CONCAT(e.firstname, ' ', e.lastname) as employee_name
        FROM asset_items ai
        LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
        LEFT JOIN asset_sub_categories subcat ON ai.asset_subcategory_id = subcat.id
        LEFT JOIN offices o ON ai.office_id = o.id
        LEFT JOIN employees e ON ai.employee_id = e.id
        WHERE ai.office_id = ?";
    
    $params = [$user_office_id];
    $types = "i";
    
    // Apply filters
    if ($filters['status'] !== 'all') {
        $asset_query .= " AND ai.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if ($filters['category'] !== 'all') {
        $asset_query .= " AND ai.asset_category_id = ?";
        $params[] = $filters['category'];
        $types .= "i";
    }
    
    if (!empty($filters['search'])) {
        $asset_query .= " AND (ai.description LIKE ? OR ai.property_number LIKE ? OR ai.inventory_tag LIKE ?)";
        $search_term = "%" . $filters['search'] . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }
    
    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
        $asset_query .= " AND ai.acquisition_date BETWEEN ? AND ?";
        $params[] = $filters['date_from'];
        $params[] = $filters['date_to'];
        $types .= "ss";
    }
    
    $asset_query .= " ORDER BY ai.last_updated DESC";
    
    $stmt = $conn->prepare($asset_query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $asset_result = $stmt->get_result();
        $assets = $asset_result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inventory_report_' . date('Y-m-d') . '.csv');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // CSV headers
    fputcsv($output, [
        'ID',
        'Description',
        'Property Number',
        'Inventory Tag',
        'Status',
        'User',
        'Category',
        'Subcategory',
        'Office',
        'Acquisition Date',
        'Last Updated'
    ]);
    
    // CSV data
    foreach ($assets as $asset) {
        fputcsv($output, [
            $asset['id'],
            $asset['description'],
            $asset['property_number'] ?? 'N/A',
            $asset['inventory_tag'] ?? 'N/A',
            $asset['status'],
            $asset['end_user'] ?? $asset['employee_name'] ?? 'N/A',
            $asset['category_name'] ?? 'N/A',
            $asset['sub_category_name'] ?? 'N/A',
            $asset['office_name'] ?? 'N/A',
            $asset['acquisition_date'] ?? 'N/A',
            date('M d, Y', strtotime($asset['last_updated']))
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #5CC2F2;
        }
        
        .inventory-summary-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.3);
            height: 100%;
        }
        
        .inventory-summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .summary-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .summary-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .filter-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .inventory-table-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: var(--transition);
        }
        
        .inventory-table-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
            background: rgba(255, 255, 255, 0.35);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-serviceable { background: #d4edda; color: #155724; }
        .status-available { background: #cce5ff; color: #004085; }
        .status-in_use { background: #fff3cd; color: #856404; }
        .status-maintenance { background: #f8d7da; color: #721c24; }
        .status-disposed { background: #6c757d; color: white; }
        .status-unserviceable { background: #f8d7da; color: #721c24; }
        .status-no_tag { background: #e2e3e5; color: #383d41; }
        .status-pending_tag { background: #fff3cd; color: #856404; }
        .status-red_tagged { background: #dc3545; color: white; }
        
        .low-stock-badge {
            background: #dc3545;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 10px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .inventory-table-card h6 {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #191BA9;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link {
            color: #191BA9;
            font-weight: 500;
            border: none;
            background: transparent;
            border-radius: var(--border-radius);
            margin-right: 0.5rem;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(25, 27, 169, 0.3);
        }
        
        .nav-tabs .nav-link:hover {
            background: rgba(92, 194, 242, 0.1);
            color: #191BA9;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(92, 194, 242, 0.1);
        }
        
        .export-btn {
            background: linear-gradient(135deg, #191BA9, #5CC2F2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(25, 27, 169, 0.2);
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(25, 27, 169, 0.4);
            color: white;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .summary-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Inventory Reports';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-clipboard-data"></i> Inventory Reports
                    </h1>
                    <p class="text-muted mb-0">Monitor and analyze your office inventory status</p>
                    <?php if (isset($inventory_data['error'])): ?>
                        <div class="alert alert-warning mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Database Warning:</strong> <?php echo htmlspecialchars($inventory_data['error']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshReports()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-primary btn-sm ms-2" onclick="exportInventory()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Inventory Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="inventory-summary-card">
                    <div class="summary-number"><?php echo $inventory_data['summary']['total_assets']; ?></div>
                    <div class="summary-label"><i class="bi bi-box-seam"></i> Total Assets</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="inventory-summary-card">
                    <div class="summary-number"><?php echo $inventory_data['summary']['functional_assets']; ?></div>
                    <div class="summary-label"><i class="bi bi-check-circle"></i> Functional Assets</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="inventory-summary-card">
                    <div class="summary-number"><?php echo $inventory_data['summary']['total_consumables']; ?></div>
                    <div class="summary-label"><i class="bi bi-archive"></i> Consumables</div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-card">
            <h6 class="mb-3"><i class="bi bi-funnel"></i> Inventory Filters</h6>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search items...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Asset Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="serviceable" <?php echo $filters['status'] === 'serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                        <option value="available" <?php echo $filters['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="in_use" <?php echo $filters['status'] === 'in_use' ? 'selected' : ''; ?>>In Use</option>
                        <option value="maintenance" <?php echo $filters['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="unserviceable" <?php echo $filters['status'] === 'unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                        <option value="disposed" <?php echo $filters['status'] === 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                        <option value="no_tag" <?php echo $filters['status'] === 'no_tag' ? 'selected' : ''; ?>>No Tag</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="all" <?php echo $filters['category'] === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($inventory_data['categories'] as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $filters['category'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                    </div>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <a href="inventory_reports.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Inventory Tabs -->
        <div class="inventory-table-card">
            <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="assets-tab" data-bs-toggle="tab" data-bs-target="#assets" type="button" role="tab">
                        <i class="bi bi-box-seam"></i> Assets (<?php echo count($inventory_data['assets']); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="consumables-tab" data-bs-toggle="tab" data-bs-target="#consumables" type="button" role="tab">
                        <i class="bi bi-archive"></i> Consumables (<?php echo count($inventory_data['consumables']); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="charts-tab" data-bs-toggle="tab" data-bs-target="#charts" type="button" role="tab">
                        <i class="bi bi-graph-up"></i> Analytics
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="inventoryTabsContent">
                <!-- Assets Tab -->
                <div class="tab-pane fade show active" id="assets" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Description</th>
                                    <th>Property No.</th>
                                    <th>Inventory Tag</th>
                                    <th>Status</th>
                                    <th>User</th>
                                    <th>Category</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory_data['assets'])): ?>
                                    <?php foreach ($inventory_data['assets'] as $asset): ?>
                                        <tr>
                                            <td><?php echo $asset['id']; ?></td>
                                            <td><?php echo htmlspecialchars($asset['description']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['property_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['inventory_tag'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $asset['status']; ?>">
                                                    <?php echo htmlspecialchars($asset['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($asset['end_user'] ?? $asset['employee_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($asset['last_updated'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No assets found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Consumables Tab -->
                <div class="tab-pane fade" id="consumables" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Unit Cost</th>
                                    <th>Total Value</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory_data['consumables'])): ?>
                                    <?php foreach ($inventory_data['consumables'] as $consumable): ?>
                                        <tr>
                                            <td><?php echo $consumable['id']; ?></td>
                                            <td><?php echo htmlspecialchars($consumable['description']); ?></td>
                                            <td><?php echo $consumable['quantity']; ?></td>
                                            <td><?php echo htmlspecialchars($consumable['unit']); ?></td>
                                            <td>₱<?php echo number_format($consumable['unit_cost'], 2); ?></td>
                                            <td>₱<?php echo number_format($consumable['quantity'] * $consumable['unit_cost'], 2); ?></td>
                                            <td><?php echo $consumable['reorder_level']; ?></td>
                                            <td>
                                                <?php if ($consumable['quantity'] <= $consumable['reorder_level']): ?>
                                                    <span class="low-stock-badge">Low Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">In Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($consumable['updated_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No consumables found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Charts Tab -->
                <div class="tab-pane fade" id="charts" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Asset Status Distribution</h6>
                            <div class="chart-container">
                                <canvas id="assetStatusChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Inventory Value Breakdown</h6>
                            <div class="chart-container">
                                <canvas id="valueBreakdownChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h6 class="mb-3">Asset Categories</h6>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Charts
document.addEventListener('DOMContentLoaded', function() {
    // Asset Status Chart
    const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
    new Chart(assetStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Functional', 'Non-Functional', 'Untagged'],
            datasets: [{
                data: [
                    <?php echo $inventory_data['summary']['functional_assets']; ?>,
                    <?php echo $inventory_data['summary']['non_functional_assets']; ?>,
                    <?php echo $inventory_data['summary']['untagged_assets']; ?>
                ],
                backgroundColor: ['#28a745', '#dc3545', '#6c757d'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Value Breakdown Chart
    const valueBreakdownCtx = document.getElementById('valueBreakdownChart').getContext('2d');
    new Chart(valueBreakdownCtx, {
        type: 'pie',
        data: {
            labels: ['Asset Value', 'Consumable Value'],
            datasets: [{
                data: [
                    <?php echo $inventory_data['summary']['total_asset_value']; ?>,
                    <?php echo $inventory_data['summary']['total_consumable_value']; ?>
                ],
                backgroundColor: ['#5CC2F2', '#28a745'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    
    // Prepare category data
    const categoryData = {};
    <?php foreach ($inventory_data['assets'] as $asset): ?>
        <?php if ($asset['category_name']): ?>
            categoryData['<?php echo addslashes($asset['category_name']); ?>'] = 
                (categoryData['<?php echo addslashes($asset['category_name']); ?>'] || 0) + 1;
        <?php endif; ?>
    <?php endforeach; ?>
    
    const categoryLabels = Object.keys(categoryData);
    const categoryValues = Object.values(categoryData);
    
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: categoryLabels.length > 0 ? categoryLabels : ['No Data'],
            datasets: [{
                label: 'Number of Assets',
                data: categoryValues.length > 0 ? categoryValues : [0],
                backgroundColor: '#5CC2F2',
                borderColor: '#191BA9',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});

// Refresh Reports
function refreshReports() {
    location.reload();
}

// Export Inventory
function exportInventory() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', '1');
    window.open(currentUrl.toString(), '_blank');
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Sidebar Scripts -->
<script src="../assets/js/sidebar.js"></script>
</body>
</html>
