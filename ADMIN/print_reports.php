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

// Get report type and filters from URL
$report_type = $_GET['type'] ?? 'assets';
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
}

// Get system settings for logo
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($system_settings['system_name']); ?> - <?php echo ucfirst($report_type); ?> Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .print-header img {
            max-width: 200px;
            
            object-fit: contain;
            float: left;
            margin-right: 20px;
        }
        
        .print-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .print-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .gov-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            
        }
        
        .gov-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.2;
        }
        
        .municipality {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
        }
        
        .province {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #000;
        }
        
        .filters-info {
            font-size: 11px;
            color: #666;
            margin-bottom: 20px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        
        .report-table th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .report-table td {
            font-size: 11px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-serviceable { background: #d4edda; color: #155724; }
        .status-unserviceable { background: #f8d7da; color: #721c24; }
        .status-red-tagged { background: #fff3cd; color: #856404; }
        .status-no-tag { background: #e2e3e5; color: #383d41; }
        .status-permanent { background: #d4edda; color: #155724; }
        .status-contractual { background: #cce5ff; color: #004085; }
        .status-job-order { background: #fff3cd; color: #856404; }
        .status-resigned { background: #f8d7da; color: #721c24; }
        .status-retired { background: #e2e3e5; color: #383d41; }
        .status-cleared { background: #d4edda; color: #155724; }
        .status-uncleared { background: #f8d7da; color: #721c24; }
        
        .summary-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            border: 1px solid #000;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .print-header {
                padding: 10px;
            }
            
            .report-table th {
                background: #f0f0f0 !important;
                color: #000 !important;
                border: 1px solid #000;
            }
            
            /* Hide browser print headers and footers */
            @page {
                size: legal landscape;
                margin: 0.5in;
            }
            
            html {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <div style="display: flex; align-items: flex-start; gap: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <?php 
                if (!empty($system_settings['system_logo'])) {
                    echo '<img src="../' . htmlspecialchars($system_settings['system_logo']) . '" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">';
                } else {
                    echo '<img src="../img/system_logo.png" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="municipality">Municipality of Pilar</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name']); ?> - <?php echo ucfirst($report_type); ?> Report</div>
                    <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
   
    
    <?php if (!empty($where_conditions)): ?>
        <div class="filters-info">
            <strong>Filters Applied:</strong>
            <?php if ($office_filter > 0): ?>
                Office: <?php 
                $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
                $office_stmt->bind_param("i", $office_filter);
                $office_stmt->execute();
                $office_result = $office_stmt->get_result();
                if ($office_row = $office_result->fetch_assoc()) {
                    echo htmlspecialchars($office_row['office_name']);
                }
                $office_stmt->close();
                ?><br>
            <?php endif; ?>
            
            <?php if ($category_filter > 0): ?>
                Category: <?php 
                $cat_stmt = $conn->prepare("SELECT category_name FROM asset_categories WHERE id = ?");
                $cat_stmt->bind_param("i", $category_filter);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result();
                if ($cat_row = $cat_result->fetch_assoc()) {
                    echo htmlspecialchars($cat_row['category_name']);
                }
                $cat_stmt->close();
                ?><br>
            <?php endif; ?>
            
            <?php if (!empty($status_filter)): ?>
                Status: <?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?><br>
            <?php endif; ?>
            
            <?php if (!empty($date_from)): ?>
                From: <?php echo date('M j, Y', strtotime($date_from)); ?><br>
            <?php endif; ?>
            
            <?php if (!empty($date_to)): ?>
                To: <?php echo date('M j, Y', strtotime($date_to)); ?><br>
            <?php endif; ?>
            
            <?php if (!empty($employee_status_filter)): ?>
                Employment Status: <?php echo ucfirst(str_replace('_', ' ', $employee_status_filter)); ?><br>
            <?php endif; ?>
            
            <?php if (!empty($clearance_status_filter)): ?>
                Clearance Status: <?php echo ucfirst($clearance_status_filter); ?><br>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($report_type === 'summary'): ?>
        <!-- Summary Report -->
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-number"><?php 
                    $total_assets_sql = "SELECT COUNT(*) as count FROM asset_items";
                    $result = $conn->query($total_assets_sql);
                    $row = $result->fetch_assoc();
                    echo $row['count'];
                ?></div>
                <div class="stat-label">Total Assets</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php 
                    $total_employees_sql = "SELECT COUNT(*) as count FROM employees";
                    $result = $conn->query($total_employees_sql);
                    $row = $result->fetch_assoc();
                    echo $row['count'];
                ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php 
                    $total_offices_sql = "SELECT COUNT(*) as count FROM offices";
                    $result = $conn->query($total_offices_sql);
                    $row = $result->fetch_assoc();
                    echo $row['count'];
                ?></div>
                <div class="stat-label">Total Offices</div>
            </div>
        </div>
        
        <!-- Asset Status Breakdown -->
        <h3 style="margin-bottom: 15px;">Asset Status Breakdown</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $status_sql = "SELECT 
                    status,
                    COUNT(*) as count
                    FROM asset_items 
                    GROUP BY status";
                $result = $conn->query($status_sql);
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . ucfirst(str_replace('_', ' ', $row['status'])) . '</td>';
                    echo '<td class="text-center">' . $row['count'] . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        
        <!-- Employee Status Breakdown -->
        <h3 style="margin-bottom: 15px;">Employee Status Breakdown</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $emp_status_sql = "SELECT 
                    employment_status,
                    COUNT(*) as count
                    FROM employees 
                    GROUP BY employment_status";
                $result = $conn->query($emp_status_sql);
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . ucfirst(str_replace('_', ' ', $row['employment_status'])) . '</td>';
                    echo '<td class="text-center">' . $row['count'] . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        
    <?php else: ?>
        <!-- Detailed Report Table -->
        <table class="report-table">
            <?php if ($report_type === 'assets'): ?>
                <thead>
                    <tr>
                        <th>Property No</th>
                        <th>Inventory Tag</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th class="text-right">Value</th>
                        <th>Acquisition Date</th>
                        <th>Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No assets found matching the criteria</td>
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
                                    $status_class = '';
                                    $status = $item['status'];
                                    switch($status) {
                                        case 'serviceable': $status_class = 'status-serviceable'; break;
                                        case 'unserviceable': $status_class = 'status-unserviceable'; break;
                                        case 'red_tagged': $status_class = 'status-red-tagged'; break;
                                        case 'no_tag': $status_class = 'status-no-tag'; break;
                                        default: $status_class = 'bg-secondary'; break;
                                    }
                                    echo '<span class="status-badge ' . $status_class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
                                    ?>
                                </td>
                                <td class="text-right">₱<?php echo number_format($item['value'], 2); ?></td>
                                <td><?php echo date('M j, Y', strtotime($item['acquisition_date'])); ?></td>
                                <td>
                                    <?php 
                                    if ($item['employee_no']) {
                                        echo htmlspecialchars($item['employee_no'] . ' - ' . $item['firstname'] . ' ' . $item['lastname']);
                                    } else {
                                        echo 'Unassigned';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                
                <?php if (!empty($data)): ?>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">Total:</th>
                            <th class="text-right"><?php echo $total_count; ?></th>
                            <th class="text-right">₱<?php echo number_format($total_value, 2); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                <?php endif; ?>
                
            <?php elseif ($report_type === 'employees'): ?>
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
                            <td colspan="8" class="text-center">No employees found matching the criteria</td>
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
                                    $status_class = '';
                                    $status = $employee['employment_status'];
                                    switch($status) {
                                        case 'permanent': $status_class = 'status-permanent'; break;
                                        case 'contractual': $status_class = 'status-contractual'; break;
                                        case 'job_order': $status_class = 'status-job-order'; break;
                                        case 'resigned': $status_class = 'status-resigned'; break;
                                        case 'retired': $status_class = 'status-retired'; break;
                                        default: $status_class = 'bg-secondary'; break;
                                    }
                                    echo '<span class="status-badge ' . $status_class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $clearance_class = '';
                                    $clearance = $employee['clearance_status'];
                                    switch($clearance) {
                                        case 'cleared': $clearance_class = 'status-cleared'; break;
                                        case 'uncleared': $clearance_class = 'status-uncleared'; break;
                                        default: $clearance_class = 'bg-secondary'; break;
                                    }
                                    echo '<span class="status-badge ' . $clearance_class . '">' . ucfirst($clearance) . '</span>';
                                    ?>
                                </td>
                                <td><?php echo $employee['created_at'] ? date('M j, Y', strtotime($employee['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <?php 
                                    $contact = [];
                                    if ($employee['email']) $contact[] = htmlspecialchars($employee['email']);
                                    if ($employee['phone']) $contact[] = htmlspecialchars($employee['phone']);
                                    echo !empty($contact) ? implode('<br>', $contact) : 'N/A';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                
                <?php if (!empty($data)): ?>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-right">Total:</th>
                            <th class="text-right"><?php echo $total_count; ?></th>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            <?php endif; ?>
        </table>
    <?php endif; ?>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Close window after printing
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
