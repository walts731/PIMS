<?php
// File updated: 2025-02-05 12:53:00 - Fixed JavaScript syntax errors
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

// Force report type to summary (analytics)
$report_type = 'summary';

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

// Ensure data is always an array to prevent JavaScript errors
if (!isset($data) || !is_array($data)) {
    $data = [];
}

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
        SUM(CASE WHEN status = 'serviceable' THEN value ELSE 0 END) as serviceable_value,
        COUNT(CASE WHEN status = 'unserviceable' THEN 1 END) as unserviceable_count,
        SUM(CASE WHEN status = 'unserviceable' THEN value ELSE 0 END) as unserviceable_value,
        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_count,
        SUM(CASE WHEN status = 'maintenance' THEN value ELSE 0 END) as maintenance_value,
        COUNT(CASE WHEN status = 'red_tagged' THEN 1 END) as red_tagged_count,
        SUM(CASE WHEN status = 'red_tagged' THEN value ELSE 0 END) as red_tagged_value,
        COUNT(CASE WHEN status = 'disposed' THEN 1 END) as disposed_count,
        SUM(CASE WHEN status = 'disposed' THEN value ELSE 0 END) as disposed_value,
        COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed_count,
        SUM(CASE WHEN status = 'borrowed' THEN value ELSE 0 END) as borrowed_value,
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
        COUNT(CASE WHEN NOT EXISTS (SELECT 1 FROM asset_items ai WHERE ai.employee_id = e.id) THEN 1 END) as cleared_count,
        COUNT(CASE WHEN EXISTS (SELECT 1 FROM asset_items ai WHERE ai.employee_id = e.id) THEN 1 END) as uncleared_count,
        COUNT(CASE WHEN office_id IS NOT NULL THEN 1 END) as assigned_employees
        FROM employees e";
    
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

// Initialize variables to prevent undefined variable notices
$total_assets = $total_assets ?? 0;
$total_asset_value = $total_asset_value ?? 0;
$total_employees = $total_employees ?? 0;
$total_offices = $total_offices ?? 0;
$asset_stats = $asset_stats ?? [];
$employee_stats = $employee_stats ?? [];

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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Reports - PIMS</title>
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
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
                        <i class="bi bi-graph-up"></i> Analytics
                    </h1>
                    <p class="text-muted mb-0">View comprehensive system analytics and statistics</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="window.open('print_analytics.php', '_blank')">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="window.location.href='analytics.php'">
                                    <i class="bi bi-graph-up-arrow"></i> Advanced Analytics
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
        
                
        
        <!-- Analytics Content -->
        <div class="report-table">
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
                        <!-- Asset Overview -->
                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                            <td colspan="4" style="padding: 8px; border-bottom: 2px solid #dee2e6;">
                                <i class="bi bi-box"></i> Asset Overview
                            </td>
                        </tr>
                        <tr>
                            <td>Total Assets</td>
                            <td class="text-center"><?php echo $total_assets; ?></td>
                            <td>₱<?php echo number_format($total_asset_value, 2); ?></td>
                            <td>Overall asset count and value</td>
                        </tr>
                        <tr>
                            <td>Serviceable Assets</td>
                            <td class="text-center"><?php echo $asset_stats['serviceable_count'] ?? 0; ?></td>
                            <td>₱<?php echo number_format($asset_stats['serviceable_value'] ?? 0, 2); ?></td>
                            <td>Assets available for use</td>
                        </tr>
                        <tr>
                            <td>Unserviceable Assets</td>
                            <td class="text-center"><?php echo $asset_stats['unserviceable_count'] ?? 0; ?></td>
                            <td>₱<?php echo number_format($asset_stats['unserviceable_value'] ?? 0, 2); ?></td>
                            <td>Assets requiring maintenance</td>
                        </tr>
                        <tr>
                            <td>Maintenance Assets</td>
                            <td class="text-center"><?php echo $asset_stats['maintenance_count'] ?? 0; ?></td>
                            <td>₱<?php echo number_format($asset_stats['maintenance_value'] ?? 0, 2); ?></td>
                            <td>Assets under maintenance</td>
                        </tr>
                        <tr>
                            <td>Red Tagged Assets</td>
                            <td class="text-center"><?php echo $asset_stats['red_tagged_count'] ?? 0; ?></td>
                            <td>₱<?php echo number_format($asset_stats['red_tagged_value'] ?? 0, 2); ?></td>
                            <td>Assets marked for disposal</td>
                        </tr>
                        <tr>
                            <td>Total Disposed Assets</td>
                            <td class="text-center"><?php echo $asset_stats['disposed_count'] ?? 0; ?></td>
                            <td>₱<?php echo number_format($asset_stats['disposed_value'] ?? 0, 2); ?></td>
                            <td>Assets already disposed</td>
                        </tr>
                        <tr>
                            <td>Borrowed Assets</td>
                            <td class="text-center"><?php echo $asset_stats['borrowed_count'] ?? 0; ?></td>
                            <td>₱<?php echo number_format($asset_stats['borrowed_value'] ?? 0, 2); ?></td>
                            <td>Currently borrowed out</td>
                        </tr>
                        
                        <!-- Employee Overview -->
                        <tr style="background-color: #e3f2fd; font-weight: bold;">
                            <td colspan="4" style="padding: 8px; border-bottom: 2px solid #dee2e6;">
                                <i class="bi bi-people"></i> Employee Overview
                            </td>
                        </tr>
                        <tr>
                            <td>Total Employees</td>
                            <td class="text-center"><?php echo $total_employees; ?></td>
                            <td>N/A</td>
                            <td>Overall employee count</td>
                        </tr>
                        <tr>
                            <td>Cleared Employees</td>
                            <td class="text-center"><?php echo $employee_stats['cleared_count'] ?? 0; ?></td>
                            <td>N/A</td>
                            <td>Employees with completed clearance</td>
                        </tr>
                        <tr>
                            <td>Uncleared Employees</td>
                            <td class="text-center"><?php echo $employee_stats['uncleared_count'] ?? 0; ?></td>
                            <td>N/A</td>
                            <td>Employees without clearance</td>
                        </tr>
                        
                        <!-- Office Overview -->
                        <tr style="background-color: #d1ecf1; font-weight: bold;">
                            <td colspan="4" style="padding: 8px; border-bottom: 2px solid #dee2e6;">
                                <i class="bi bi-building"></i> Office Overview
                            </td>
                        </tr>
                        <tr>
                            <td>Total Offices</td>
                            <td class="text-center"><?php echo $total_offices; ?></td>
                            <td>N/A</td>
                            <td>Overall office count</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo time(); ?>"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <!-- Global Report Functions - Define immediately to ensure availability -->
    <script>
        // Force refresh - updated at <?php echo time(); ?>
        
        // Define functions in global scope
        window.printReport = function() {
            const reportType = <?php echo json_encode($report_type, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            console.log('Print report type:', reportType);
            
            // Build URL with all current parameters
            const currentUrl = new URL(window.location.href);
            const params = new URLSearchParams(currentUrl.search);
            
            // Set the print_reports.php as the target
            const printUrl = 'print_reports.php?' + params.toString();
            
            console.log('Print URL:', printUrl);
            
            // Open print window
            try {
                const printWindow = window.open(printUrl, '_blank', 'width=1000,height=800');
                if (!printWindow) {
                    alert('Please allow popups for this site to print reports.');
                }
            } catch (error) {
                console.error('Error opening print window:', error);
                alert('Error opening print window: ' + error.message);
            }
        };
        
        window.exportReport = function() {
            const reportType = <?php echo json_encode($report_type, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            let csvContent = '';
            let fileName = '';
            
            if (reportType === 'assets') {
                fileName = 'asset_report_' + new Date().toISOString().split('T')[0] + '.csv';
                // CSV header
                csvContent = 'Property No,Inventory Tag,Description,Category,Office,Status,Value,Acquisition Date,Assigned To\n';
                
                // CSV data
                const assetData = <?php 
                    if (empty($data)) {
                        echo '[]';
                    } else {
                        echo json_encode(array_map(function($item) {
                            return [
                                isset($item['property_no']) ? $item['property_no'] : 'N/A',
                                isset($item['inventory_tag']) ? $item['inventory_tag'] : 'N/A',
                                isset($item['description']) ? $item['description'] : '',
                                isset($item['category_code']) ? $item['category_code'] : '',
                                isset($item['office_name']) ? $item['office_name'] : 'N/A',
                                isset($item['status']) ? ucfirst(str_replace('_', ' ', $item['status'])) : '',
                                isset($item['value']) ? number_format($item['value'], 2) : '0.00',
                                isset($item['acquisition_date']) ? date('M j, Y', strtotime($item['acquisition_date'])) : 'N/A',
                                (isset($item['employee_no']) && $item['employee_no']) ? 
                                    $item['employee_no'] . ' - ' . (isset($item['firstname']) ? $item['firstname'] : '') . ' ' . (isset($item['lastname']) ? $item['lastname'] : '') : 'Unassigned'
                            ];
                        }, $data), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    }
                ?>;
                
                if (Array.isArray(assetData)) {
                    assetData.forEach(function(row) {
                        csvContent += row.map(function(field) {
                            return '"' + String(field).replace(/"/g, '""') + '"';
                        }).join(',') + '\n';
                    });
                }
                
            } else if (reportType === 'employees') {
                fileName = 'employee_report_' + new Date().toISOString().split('T')[0] + '.csv';
                // CSV header
                csvContent = 'Employee No,Name,Position,Office,Employment Status,Clearance Status,Date Added,Email,Phone\n';
                
                // CSV data
                const employeeData = <?php 
                    if (empty($data)) {
                        echo '[]';
                    } else {
                        echo json_encode(array_map(function($employee) {
                            return [
                                isset($employee['employee_no']) ? $employee['employee_no'] : '',
                                (isset($employee['firstname']) ? $employee['firstname'] : '') . ' ' . (isset($employee['lastname']) ? $employee['lastname'] : ''),
                                isset($employee['position']) ? $employee['position'] : 'N/A',
                                isset($employee['office_name']) ? $employee['office_name'] : 'N/A',
                                isset($employee['employment_status']) ? ucfirst(str_replace('_', ' ', $employee['employment_status'])) : '',
                                isset($employee['clearance_status']) ? ucfirst($employee['clearance_status']) : '',
                                isset($employee['created_at']) ? date('M j, Y', strtotime($employee['created_at'])) : 'N/A',
                                isset($employee['email']) ? $employee['email'] : '',
                                isset($employee['phone']) ? $employee['phone'] : ''
                            ];
                        }, $data), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    }
                ?>;
                
                if (Array.isArray(employeeData)) {
                    employeeData.forEach(function(row) {
                        csvContent += row.map(function(field) {
                            return '"' + String(field).replace(/"/g, '""') + '"';
                        }).join(',') + '\n';
                    });
                }
                
            } else if (reportType === 'summary') {
                fileName = 'summary_report_' + new Date().toISOString().split('T')[0] + '.csv';
                // CSV header
                csvContent = 'Report Type,Total Count,Total Value,Notes\n';
                
                // Summary data
                const summaryData = [
                    ['Total Assets', <?php echo (int)($total_assets ?? 0); ?>, '₱' + <?php echo (float)($total_asset_value ?? 0); ?>, 'Overall asset count and value'],
                    ['Total Employees', <?php echo (int)($total_employees ?? 0); ?>, 'N/A', 'Overall employee count'],
                    ['Total Offices', <?php echo (int)($total_offices ?? 0); ?>, 'N/A', 'Overall office count'],
                    ['Serviceable Assets', <?php echo (int)($asset_stats['serviceable_count'] ?? 0); ?>, 'N/A', 'Assets in serviceable condition'],
                    ['Unserviceable Assets', <?php echo (int)($asset_stats['unserviceable_count'] ?? 0); ?>, 'N/A', 'Assets in unserviceable condition'],
                    ['Red Tagged Assets', <?php echo (int)($asset_stats['red_tagged_count'] ?? 0); ?>, 'N/A', 'Assets with red tags'],
                    ['Permanent Employees', <?php echo (int)($employee_stats['permanent_count'] ?? 0); ?>, 'N/A', 'Permanent staff count'],
                    ['Contractual Employees', <?php echo (int)($employee_stats['contractual_count'] ?? 0); ?>, 'N/A', 'Contractual staff count'],
                    ['Cleared Employees', <?php echo (int)($employee_stats['cleared_count'] ?? 0); ?>, 'N/A', 'Employees with clearance']
                ];
                
                if (Array.isArray(summaryData)) {
                    summaryData.forEach(function(row) {
                        csvContent += row.map(function(field) {
                            return '"' + String(field).replace(/"/g, '""') + '"';
                        }).join(',') + '\n';
                    });
                }
            }
            
            // Create download link
            try {
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('hidden', '');
                a.setAttribute('href', url);
                a.setAttribute('download', fileName);
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error creating download:', error);
                alert('Error creating download: ' + error.message);
            }
        };
        
        // Debug: Log that functions are defined
        console.log('Report functions defined:', {
            printReport: typeof window.printReport,
            exportReport: typeof window.exportReport
        });
    </script>
</body>
</html>
