<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log reports page access
logSystemAction($_SESSION['user_id'], 'reports_accessed', 'reports', 'Accessed reports page');

// Get report type from URL
$report_type = isset($_GET['type']) ? $_GET['type'] : 'assets';

// Get filter parameters
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$employee_status_filter = isset($_GET['employee_status']) ? $_GET['employee_status'] : '';
$clearance_status_filter = isset($_GET['clearance_status']) ? $_GET['clearance_status'] : '';

// Build WHERE conditions
$where_conditions = [];
$params = [];
$types = '';

// Asset report filters
if ($report_type === 'assets') {
    if ($office_filter > 0) {
        $where_conditions[] = "ai.office_id = ?";
        $params[] = $office_filter;
        $types .= 'i';
    }
    
    if ($category_filter > 0) {
        $where_conditions[] = "a.asset_categories_id = ?";
        $params[] = $category_filter;
        $types .= 'i';
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "ai.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "ai.acquisition_date >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "ai.acquisition_date <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
}

// Employee report filters
if ($report_type === 'employees') {
    if (!empty($employee_status_filter)) {
        $where_conditions[] = "e.employment_status = ?";
        $params[] = $employee_status_filter;
        $types .= 's';
    }
    
    if (!empty($clearance_status_filter)) {
        $where_conditions[] = "e.clearance_status = ?";
        $params[] = $clearance_status_filter;
        $types .= 's';
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get data based on report type
$data = [];
$total_value = 0;
$total_count = 0;

if ($report_type === 'assets') {
    // Asset report query
    $sql = "SELECT ai.id, ai.property_no, ai.inventory_tag, ai.description, ai.status, 
                   ai.value, ai.acquisition_date, ai.last_updated,
                   a.description as asset_description, ac.category_name, ac.category_code,
                   o.office_name,
                   e.employee_no, e.firstname, e.lastname
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN employees e ON ai.employee_id = e.id 
            $where_clause
            ORDER BY ai.acquisition_date DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_value += $row['value'];
        $total_count++;
    }
    $stmt->close();
    
} elseif ($report_type === 'employees') {
    // Employee report query
    $sql = "SELECT e.id, e.employee_no, e.firstname, e.lastname, e.position, 
                   e.employment_status, e.clearance_status, e.email, e.phone,
                   e.created_at, o.office_name
            FROM employees e 
            LEFT JOIN offices o ON e.office_id = o.id 
            $where_clause
            ORDER BY e.lastname, e.firstname";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_count++;
    }
    $stmt->close();
    
} elseif ($report_type === 'summary') {
    // Summary Report - Get overall system statistics
    
    // Asset Statistics
    $asset_stats = [];
    $total_assets = 0;
    $total_asset_value = 0;
    
    $asset_summary_sql = "SELECT 
        COUNT(*) as total_items,
        SUM(value) as total_value,
        COUNT(CASE WHEN status = 'serviceable' THEN 1 END) as serviceable_count,
        COUNT(CASE WHEN status = 'unserviceable' THEN 1 END) as unserviceable_count,
        COUNT(CASE WHEN status = 'red_tagged' THEN 1 END) as red_tagged_count,
        COUNT(CASE WHEN status = 'no_tag' THEN 1 END) as no_tag_count,
        COUNT(CASE WHEN office_id IS NOT NULL THEN 1 END) as assigned_count,
        COUNT(CASE WHEN office_id IS NULL THEN 1 END) as unassigned_count
        FROM asset_items";
    
    $result = $conn->query($asset_summary_sql);
    if ($row = $result->fetch_assoc()) {
        $asset_stats = $row;
        $total_assets = $row['total_items'];
        $total_asset_value = $row['total_value'] ?? 0;
    }
    
    // Employee Statistics
    $employee_stats = [];
    $total_employees = 0;
    
    $employee_summary_sql = "SELECT 
        COUNT(*) as total_employees,
        COUNT(CASE WHEN employment_status = 'permanent' THEN 1 END) as permanent_count,
        COUNT(CASE WHEN employment_status = 'contractual' THEN 1 END) as contractual_count,
        COUNT(CASE WHEN employment_status = 'job_order' THEN 1 END) as job_order_count,
        COUNT(CASE WHEN employment_status = 'resigned' THEN 1 END) as resigned_count,
        COUNT(CASE WHEN employment_status = 'retired' THEN 1 END) as retired_count,
        COUNT(CASE WHEN clearance_status = 'cleared' THEN 1 END) as cleared_count,
        COUNT(CASE WHEN clearance_status = 'uncleared' THEN 1 END) as uncleared_count,
        COUNT(CASE WHEN office_id IS NOT NULL THEN 1 END) as assigned_employees
        FROM employees";
    
    $result = $conn->query($employee_summary_sql);
    if ($row = $result->fetch_assoc()) {
        $employee_stats = $row;
        $total_employees = $row['total_employees'];
    }
    
    // Office Statistics
    $office_stats = [];
    $total_offices = 0;
    
    $office_summary_sql = "SELECT 
        COUNT(*) as total_offices,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_offices,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_offices,
        SUM(capacity) as total_capacity
        FROM offices";
    
    $result = $conn->query($office_summary_sql);
    if ($row = $result->fetch_assoc()) {
        $office_stats = $row;
        $total_offices = $row['total_offices'];
    }
    
    // Category Statistics
    $category_stats = [];
    $category_summary_sql = "SELECT 
        ac.category_name,
        ac.category_code,
        COUNT(ai.id) as item_count,
        COALESCE(SUM(ai.value), 0) as total_value
        FROM asset_categories ac
        LEFT JOIN assets a ON ac.id = a.asset_categories_id
        LEFT JOIN asset_items ai ON a.id = ai.asset_id
        GROUP BY ac.id, ac.category_name, ac.category_code
        ORDER BY item_count DESC";
    
    $result = $conn->query($category_summary_sql);
    while ($row = $result->fetch_assoc()) {
        $category_stats[] = $row;
    }
    
    // Office Asset Distribution
    $office_distribution = [];
    $distribution_sql = "SELECT 
        o.office_name,
        COUNT(ai.id) as asset_count,
        COALESCE(SUM(ai.value), 0) as total_value
        FROM offices o
        LEFT JOIN asset_items ai ON o.id = ai.office_id
        GROUP BY o.id, o.office_name
        ORDER BY asset_count DESC";
    
    $result = $conn->query($distribution_sql);
    while ($row = $result->fetch_assoc()) {
        $office_distribution[] = $row;
    }
    
    // Recent Activity (last 30 days)
    $recent_activity = [];
    $activity_sql = "SELECT 
        'asset_created' as activity_type,
        COUNT(*) as count
        FROM asset_items 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        
        UNION ALL
        
        SELECT 
        'employee_created' as activity_type,
        COUNT(*) as count
        FROM employees 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        
        UNION ALL
        
        SELECT 
        'red_tag_created' as activity_type,
        COUNT(*) as count
        FROM red_tags 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $result = $conn->query($activity_sql);
    while ($row = $result->fetch_assoc()) {
        $recent_activity[$row['activity_type']] = $row['count'];
    }
}

// Get filter options
$offices = [];
$office_sql = "SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name";
$office_result = $conn->query($office_sql);
while ($row = $office_result->fetch_assoc()) {
    $offices[] = $row;
}

$categories = [];
$category_sql = "SELECT id, category_name, category_code FROM asset_categories ORDER BY category_code";
$category_result = $conn->query($category_sql);
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}

// Status options for assets
$asset_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'no_tag'];

// Employment status options
$employment_statuses = ['permanent', 'contractual', 'job_order', 'resigned', 'retired'];

// Clearance status options
$clearance_statuses = ['cleared', 'uncleared'];

// Format status for display
function formatStatus($status) {
    $status_map = [
        'serviceable' => ['Serviceable', 'status-serviceable'],
        'unserviceable' => ['Unserviceable', 'status-unserviceable'],
        'red_tagged' => ['Red Tagged', 'status-red-tagged'],
        'no_tag' => ['No Tag', 'status-no-tag'],
        'permanent' => ['Permanent', 'bg-success'],
        'contractual' => ['Contractual', 'bg-info'],
        'job_order' => ['Job Order', 'bg-warning'],
        'resigned' => ['Resigned', 'bg-danger'],
        'retired' => ['Retired', 'bg-secondary'],
        'cleared' => ['Cleared', 'bg-success'],
        'uncleared' => ['Uncleared', 'bg-danger']
    ];
    return $status_map[$status] ?? [$status, 'bg-secondary'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - PIMS</title>
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
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .report-card {
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
        
        .report-table {
            background: white;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-serviceable { background: #d4edda; color: #155724; }
        .status-unserviceable { background: #f8d7da; color: #721c24; }
        .status-red-tagged { background: #fff3cd; color: #856404; }
        .status-no-tag { background: #e2e3e5; color: #383d41; }
        
        .btn-report {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 27, 169, 0.3);
            color: white;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .nav-tabs .nav-link {
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            transition: var(--transition);
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: var(--border-radius-md);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            
            .page-header, .filter-card, .no-print {
                display: none !important;
            }
            
            .report-card {
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
            
            /* Hide browser print headers and footers */
            @page {
                size: A4;
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
    $page_title = 'Reports';
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
                        <i class="bi bi-file-earmark-bar-graph"></i> Reports
                    </h1>
                    <p class="text-muted mb-0">Generate comprehensive reports for assets and employees</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-export me-2" onclick="exportReport()">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <button class="btn btn-report" onclick="printReport()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Report Type Tabs -->
        <div class="report-card">
            <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $report_type === 'assets' ? 'active' : ''; ?>" 
                            onclick="window.location.href='reports.php?type=assets'">
                        <i class="bi bi-box"></i> Assets Report
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $report_type === 'employees' ? 'active' : ''; ?>" 
                            onclick="window.location.href='reports.php?type=employees'">
                        <i class="bi bi-people"></i> Employees Report
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $report_type === 'summary' ? 'active' : ''; ?>" 
                            onclick="window.location.href='reports.php?type=summary'">
                        <i class="bi bi-graph-up"></i> Summary Report
                    </button>
                </li>
            </ul>
        </div>
        
        <!-- Filters Section -->
        <div class="filter-card no-print">
            <h5 class="mb-3"><i class="bi bi-funnel"></i> Filters</h5>
            <form method="GET" action="reports.php">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                
                <?php if ($report_type === 'assets'): ?>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Office</label>
                            <select name="office" class="form-select">
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
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach ($asset_statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                <?php elseif ($report_type === 'employees'): ?>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Employment Status</label>
                            <select name="employee_status" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach ($employment_statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $employee_status_filter === $status ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Clearance Status</label>
                            <select name="clearance_status" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach ($clearance_statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $clearance_status_filter === $status ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-report">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                        <a href="reports.php?type=<?php echo $report_type; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php if ($report_type === 'assets'): ?>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $total_count; ?></div>
                        <div class="stats-label">Total Assets</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">₱<?php echo number_format($total_value, 2); ?></div>
                        <div class="stats-label">Total Value</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">₱<?php echo $total_count > 0 ? number_format($total_value / $total_count, 2) : '0.00'; ?></div>
                        <div class="stats-label">Average Value</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($offices); ?></div>
                        <div class="stats-label">Offices</div>
                    </div>
                </div>
            <?php elseif ($report_type === 'employees'): ?>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $total_count; ?></div>
                        <div class="stats-label">Total Employees</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php 
                            $permanent_count = 0;
                            foreach ($data as $emp) {
                                if ($emp['employment_status'] === 'permanent') $permanent_count++;
                            }
                            echo $permanent_count;
                        ?></div>
                        <div class="stats-label">Permanent</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php 
                            $cleared_count = 0;
                            foreach ($data as $emp) {
                                if ($emp['clearance_status'] === 'cleared') $cleared_count++;
                            }
                            echo $cleared_count;
                        ?></div>
                        <div class="stats-label">Cleared</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php 
                            $uncleared_count = 0;
                            foreach ($data as $emp) {
                                if ($emp['clearance_status'] === 'uncleared') $uncleared_count++;
                            }
                            echo $uncleared_count;
                        ?></div>
                        <div class="stats-label">Uncleared</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Report Content -->
        <div class="report-table">
            <?php if ($report_type === 'assets'): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Property No</th>
                                <th>Inventory Tag</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th>Value</th>
                                <th>Acquisition Date</th>
                                <th>Assigned To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No assets found matching the criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['property_no'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['inventory_tag'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category_code'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            $status_display = formatStatus($item['status']);
                                            echo '<span class="status-badge ' . $status_display[1] . '">' . $status_display[0] . '</span>';
                                            ?>
                                        </td>
                                        <td>₱<?php echo number_format($item['value'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($item['acquisition_date'])); ?></td>
                                        <td>
                                            <?php 
                                            if ($item['employee_no']) {
                                                echo htmlspecialchars($item['employee_no'] . ' - ' . $item['firstname'] . ' ' . $item['lastname']);
                                            } else {
                                                echo '<span class="text-muted">Unassigned</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($report_type === 'employees'): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Employee No</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Office</th>
                                <th>Employment Status</th>
                                <th>Clearance Status</th>
                                <th>Date Added</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No employees found matching the criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data as $employee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['employee_no']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($employee['office_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            $status_display = formatStatus($employee['employment_status']);
                                            echo '<span class="status-badge ' . $status_display[1] . '">' . $status_display[0] . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_display = formatStatus($employee['clearance_status']);
                                            echo '<span class="status-badge ' . $status_display[1] . '">' . $status_display[0] . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo $employee['created_at'] ? date('M j, Y', strtotime($employee['created_at'])) : 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                            $contact = [];
                                            if ($employee['email']) $contact[] = htmlspecialchars($employee['email']);
                                            if ($employee['phone']) $contact[] = htmlspecialchars($employee['phone']);
                                            echo !empty($contact) ? implode('<br>', $contact) : '<span class="text-muted">N/A</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($report_type === 'summary'): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Count</th>
                                <th>Value</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Assets</td>
                                <td class="text-center"><?php echo $total_assets; ?></td>
                                <td>₱<?php echo number_format($total_asset_value, 2); ?></td>
                                <td>Overall asset count and value</td>
                            </tr>
                            <tr>
                                <td>Total Employees</td>
                                <td class="text-center"><?php echo $total_employees; ?></td>
                                <td>N/A</td>
                                <td>Overall employee count</td>
                            </tr>
                            <tr>
                                <td>Total Offices</td>
                                <td class="text-center"><?php echo $total_offices; ?></td>
                                <td>N/A</td>
                                <td>Overall office count</td>
                            </tr>
                            <tr>
                                <td>Serviceable Assets</td>
                                <td class="text-center"><?php echo $asset_stats['serviceable_count'] ?? 0; ?></td>
                                <td>N/A</td>
                                <td>Assets in serviceable condition</td>
                            </tr>
                            <tr>
                                <td>Unserviceable Assets</td>
                                <td class="text-center"><?php echo $asset_stats['unserviceable_count'] ?? 0; ?></td>
                                <td>N/A</td>
                                <td>Assets in unserviceable condition</td>
                            </tr>
                            <tr>
                                <td>Red Tagged Assets</td>
                                <td class="text-center"><?php echo $asset_stats['red_tagged_count'] ?? 0; ?></td>
                                <td>N/A</td>
                                <td>Assets with red tags</td>
                            </tr>
                            <tr>
                                <td>Permanent Employees</td>
                                <td class="text-center"><?php echo $employee_stats['permanent_count'] ?? 0; ?></td>
                                <td>N/A</td>
                                <td>Permanent staff count</td>
                            </tr>
                            <tr>
                                <td>Contractual Employees</td>
                                <td class="text-center"><?php echo $employee_stats['contractual_count'] ?? 0; ?></td>
                                <td>N/A</td>
                                <td>Contractual staff count</td>
                            </tr>
                            <tr>
                                <td>Cleared Employees</td>
                                <td class="text-center"><?php echo $employee_stats['cleared_count'] ?? 0; ?></td>
                                <td>N/A</td>
                                <td>Employees with clearance</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        function printReport() {
            const reportType = '<?php echo $report_type; ?>';
            
            // Build URL with current filters
            let printUrl = 'print_reports.php?type=' + reportType;
            
            // Add current filters to URL
            <?php if ($office_filter > 0): ?>
                printUrl += '&office=<?php echo $office_filter; ?>';
            <?php endif; ?>
            
            <?php if ($category_filter > 0): ?>
                printUrl += '&category=<?php echo $category_filter; ?>';
            <?php endif; ?>
            
            <?php if (!empty($status_filter)): ?>
                printUrl += '&status=<?php echo urlencode($status_filter); ?>';
            <?php endif; ?>
            
            <?php if (!empty($date_from)): ?>
                printUrl += '&date_from=<?php echo $date_from; ?>';
            <?php endif; ?>
            
            <?php if (!empty($date_to)): ?>
                printUrl += '&date_to=<?php echo $date_to; ?>';
            <?php endif; ?>
            
            <?php if (!empty($employee_status_filter)): ?>
                printUrl += '&employee_status=<?php echo urlencode($employee_status_filter); ?>';
            <?php endif; ?>
            
            <?php if (!empty($clearance_status_filter)): ?>
                printUrl += '&clearance_status=<?php echo urlencode($clearance_status_filter); ?>';
            <?php endif; ?>
            
            // Open print window
            const printWindow = window.open(printUrl, '_blank');
            printWindow.focus();
        }
        
        function exportReport() {
            const reportType = '<?php echo $report_type; ?>';
            let csvContent = '';
            let fileName = '';
            
            if (reportType === 'assets') {
                fileName = 'asset_report_' + new Date().toISOString().split('T')[0] + '.csv';
                // CSV header
                csvContent = 'Property No,Inventory Tag,Description,Category,Office,Status,Value,Acquisition Date,Assigned To\n';
                
                // CSV data
                <?php foreach ($data as $item): ?>
                    csvContent += '<?php 
                        echo '"' . addslashes($item['property_no'] ?? 'N/A') . '",';
                        echo '"' . addslashes($item['inventory_tag'] ?? 'N/A') . '",';
                        echo '"' . addslashes($item['description']) . '",';
                        echo '"' . addslashes($item['category_code'] ?? '') . '",';
                        echo '"' . addslashes($item['office_name'] ?? 'N/A') . '",';
                        echo '"' . addslashes(ucfirst(str_replace('_', ' ', $item['status']))) . '",';
                        echo '"' . number_format($item['value'], 2) . '",';
                        echo '"' . date('M j, Y', strtotime($item['acquisition_date'])) . '",';
                        echo '"' . addslashes(($item['employee_no'] ? $item['employee_no'] . ' - ' . $item['firstname'] . ' ' . $item['lastname'] : 'Unassigned')) . '"';
                    ?>\n';
                <?php endforeach; ?>
                
            } else if (reportType === 'employees') {
                fileName = 'employee_report_' + new Date().toISOString().split('T')[0] + '.csv';
                // CSV header
                csvContent = 'Employee No,Name,Position,Office,Employment Status,Clearance Status,Date Added,Email,Phone\n';
                
                // CSV data
                <?php foreach ($data as $employee): ?>
                    csvContent += '<?php 
                        echo '"' . addslashes($employee['employee_no']) . '",';
                        echo '"' . addslashes($employee['firstname'] . ' ' . $employee['lastname']) . '",';
                        echo '"' . addslashes($employee['position'] ?? 'N/A') . '",';
                        echo '"' . addslashes($employee['office_name'] ?? 'N/A') . '",';
                        echo '"' . addslashes(ucfirst(str_replace('_', ' ', $employee['employment_status']))) . '",';
                        echo '"' . addslashes(ucfirst($employee['clearance_status'])) . '",';
                        echo '"' . ($employee['created_at'] ? date('M j, Y', strtotime($employee['created_at'])) : 'N/A') . '",';
                        echo '"' . addslashes($employee['email'] ?? '') . '",';
                        echo '"' . addslashes($employee['phone'] ?? '') . '"';
                    ?>\n';
                <?php endforeach; ?>
                
            } else if (reportType === 'summary') {
                fileName = 'summary_report_' + new Date().toISOString().split('T')[0] + '.csv';
                // CSV header
                csvContent = 'Report Type,Total Count,Total Value,Notes\n';
                
                // Summary data
                csvContent += 'Total Assets,' + <?php echo $total_assets; ?> + ',₱' + <?php echo $total_asset_value; ?> + ',Overall asset count and value\n';
                csvContent += 'Total Employees,' + <?php echo $total_employees; ?> + ',N/A,Overall employee count\n';
                csvContent += 'Total Offices,' + <?php echo $total_offices; ?> + ',N/A,Overall office count\n';
                csvContent += 'Serviceable Assets,' + <?php echo $asset_stats['serviceable_count'] ?? 0; ?> + ',N/A,Assets in serviceable condition\n';
                csvContent += 'Unserviceable Assets,' + <?php echo $asset_stats['unserviceable_count'] ?? 0; ?> + ',N/A,Assets in unserviceable condition\n';
                csvContent += 'Red Tagged Assets,' + <?php echo $asset_stats['red_tagged_count'] ?? 0; ?> + ',N/A,Assets with red tags\n';
                csvContent += 'Permanent Employees,' + <?php echo $employee_stats['permanent_count'] ?? 0; ?> + ',N/A,Permanent staff count\n';
                csvContent += 'Contractual Employees,' + <?php echo $employee_stats['contractual_count'] ?? 0; ?> + ',N/A,Contractual staff count\n';
                csvContent += 'Cleared Employees,' + <?php echo $employee_stats['cleared_count'] ?? 0; ?> + ',N/A,Employees with clearance\n';
            }
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', fileName);
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>
