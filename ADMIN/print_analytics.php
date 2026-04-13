<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log print analytics access
logSystemAction($_SESSION['user_id'], 'print_analytics', 'analytics', 'Printed analytics dashboard');


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

// Get analytics data
$asset_stats = [];
$employee_stats = [];
$office_stats = [];
$category_data = [];
$consumables_data = [];
$office_asset_data = [];

// Asset Status Data
$asset_sql = "SELECT 
    COUNT(CASE WHEN ai.status = 'serviceable' THEN 1 END) as serviceable_count,
    COUNT(CASE WHEN ai.status = 'unserviceable' THEN 1 END) as unserviceable_count,
    COUNT(CASE WHEN ai.status = 'maintenance' THEN 1 END) as maintenance_count,
    COUNT(CASE WHEN ai.status = 'red_tagged' THEN 1 END) as red_tagged_count,
    COUNT(CASE WHEN ai.status = 'disposed' THEN 1 END) as disposed_count,
    COUNT(CASE WHEN ai.status = 'borrowed' THEN 1 END) as borrowed_count,
    SUM(CASE WHEN ai.status = 'serviceable' THEN ai.value ELSE 0 END) as serviceable_value,
    SUM(CASE WHEN ai.status = 'unserviceable' THEN ai.value ELSE 0 END) as unserviceable_value,
    SUM(CASE WHEN ai.status = 'maintenance' THEN ai.value ELSE 0 END) as maintenance_value,
    SUM(CASE WHEN ai.status = 'red_tagged' THEN ai.value ELSE 0 END) as red_tagged_value,
    SUM(CASE WHEN ai.status = 'disposed' THEN ai.value ELSE 0 END) as disposed_value,
    SUM(CASE WHEN ai.status = 'borrowed' THEN ai.value ELSE 0 END) as borrowed_value,
    COUNT(*) as total_items,
    SUM(ai.value) as total_value
    FROM asset_items ai";

$result = $conn->query($asset_sql);
if ($row = $result->fetch_assoc()) {
    $asset_stats = $row;
}

// Employee Data
$employee_sql = "SELECT 
    COUNT(*) as total_employees,
    COUNT(CASE WHEN clearance_status = 'cleared' THEN 1 END) as cleared_count,
    COUNT(CASE WHEN clearance_status = 'uncleared' THEN 1 END) as uncleared_count
    FROM employees WHERE employment_status = 'active'";

$result = $conn->query($employee_sql);
if ($row = $result->fetch_assoc()) {
    $employee_stats = $row;
}

// Office Data
$office_sql = "SELECT 
    COUNT(*) as total_offices,
    COUNT(*) as active_offices
    FROM offices";

$result = $conn->query($office_sql);
if ($row = $result->fetch_assoc()) {
    $office_stats = $row;
}

// Category Asset Data
$category_sql = "SELECT 
    ac.id, ac.category_code as code, ac.category_name as name,
    COUNT(ai.id) as item_count,
    COUNT(DISTINCT ai.asset_id) as asset_count,
    SUM(ai.value) as total_value
    FROM asset_categories ac
    LEFT JOIN assets a ON ac.id = a.asset_categories_id
    LEFT JOIN asset_items ai ON a.id = ai.asset_id
    GROUP BY ac.id, ac.category_code, ac.category_name
    ORDER BY total_value DESC
    LIMIT 10";

$result = $conn->query($category_sql);
while ($row = $result->fetch_assoc()) {
    $category_data[] = $row;
}

// Office Asset Data
$office_asset_sql = "SELECT 
    o.office_name,
    COUNT(ai.id) as asset_count,
    SUM(ai.value) as total_value
    FROM offices o
    LEFT JOIN asset_items ai ON o.id = ai.office_id
    WHERE o.status = 'active'
    GROUP BY o.id, o.office_name
    ORDER BY total_value DESC";

$result = $conn->query($office_asset_sql);
while ($row = $result->fetch_assoc()) {
    $office_asset_data[] = $row;
}

// Consumables Data
$consumables_sql = "SELECT 
    'Consumable Item' as consumable_name,
    COUNT(ai.id) as consumption_count,
    SUM(ai.value) as total_consumed_value
    FROM asset_items ai
    WHERE ai.status = 'disposed'
    GROUP BY ai.asset_id
    ORDER BY consumption_count DESC
    LIMIT 10";

$result = $conn->query($consumables_sql);
while ($row = $result->fetch_assoc()) {
    $consumables_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Print Analytics - PIMS</title>
    
    <style>
        @page {
            size: legal landscape;
            margin: 0.5in;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            color: #000;
        }
        
        @media screen {
            .print-header {
                margin: 0 auto;
                width: 13in;
            }
        }
        
        .preview-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .no-print {
            display: block !important;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .print-header {
                box-shadow: none;
                width: 100%;
            }
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .print-header img {
            max-width: 200px;
            object-fit: contain;
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
        
        .analytics-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }
        
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            font-size: 12px;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .value-cell {
            text-align: right;
            font-weight: bold;
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
            
            .analytics-section {
                page-break-inside: avoid;
            }
            
            .data-table {
                page-break-inside: auto;
            }
            
            /* Hide browser print headers and footers */
            @page {
                size: legal landscape;
                margin: 0.5in;
            }
        }
    </style>
</head>
<body>
    <div class="preview-toolbar no-print">
        <div><i class="bi bi-graph-up-arrow me-2"></i>Analytics Report Preview</div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print Report</button>
            <button onclick="window.close()" class="btn btn-light btn-sm ms-2">Close</button>
        </div>
    </div>
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
            
            <!-- Header info on the right -->
            <div style="flex-grow: 1;">
                <div class="gov-title">Republic of the Philippines</div>
                <div class="municipality">Municipality of Pilar</div>
                <div class="province">Province of Sorsogon</div>
                <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name']); ?> - Analytics Dashboard</div>
                <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Asset Status Overview -->
    <div class="analytics-section">
        <div class="section-title">Asset Status Overview</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Serviceable</td>
                    <td><?php echo number_format($asset_stats['serviceable_count'] ?? 0); ?></td>
                    <td class="value-cell">₱<?php echo number_format($asset_stats['serviceable_value'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Unserviceable</td>
                    <td><?php echo number_format($asset_stats['unserviceable_count'] ?? 0); ?></td>
                    <td class="value-cell">₱<?php echo number_format($asset_stats['unserviceable_value'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Maintenance</td>
                    <td><?php echo number_format($asset_stats['maintenance_count'] ?? 0); ?></td>
                    <td class="value-cell">₱<?php echo number_format($asset_stats['maintenance_value'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Red Tagged</td>
                    <td><?php echo number_format($asset_stats['red_tagged_count'] ?? 0); ?></td>
                    <td class="value-cell">₱<?php echo number_format($asset_stats['red_tagged_value'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Disposed</td>
                    <td><?php echo number_format($asset_stats['disposed_count'] ?? 0); ?></td>
                    <td class="value-cell">₱<?php echo number_format($asset_stats['disposed_value'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td>Borrowed</td>
                    <td><?php echo number_format($asset_stats['borrowed_count'] ?? 0); ?></td>
                    <td class="value-cell">₱<?php echo number_format($asset_stats['borrowed_value'] ?? 0, 2); ?></td>
                </tr>
                <tr style="background-color: #e3f2fd; font-weight: bold;">
                    <td>Total</td>
                    <td><?php echo number_format($asset_stats['total_items'] ?? 0); ?></td>
                    <td class="value-cell">₱<?php echo number_format($asset_stats['total_value'] ?? 0, 2); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Employee Overview -->
    <div class="analytics-section">
        <div class="section-title">Employee Overview</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Cleared Employees</td>
                    <td><?php echo number_format($employee_stats['cleared_count'] ?? 0); ?></td>
                </tr>
                <tr>
                    <td>Uncleared Employees</td>
                    <td><?php echo number_format($employee_stats['uncleared_count'] ?? 0); ?></td>
                </tr>
                <tr style="background-color: #e3f2fd; font-weight: bold;">
                    <td>Total Employees</td>
                    <td><?php echo number_format($employee_stats['total_employees'] ?? 0); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Office Overview -->
    <div class="analytics-section">
        <div class="section-title">Office Overview</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Offices</td>
                    <td><?php echo number_format($office_stats['total_offices'] ?? 0); ?></td>
                </tr>
                <tr>
                    <td>Active Offices</td>
                    <td><?php echo number_format($office_stats['active_offices'] ?? 0); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Top Categories by Value -->
    <div class="analytics-section">
        <div class="section-title">Top 10 Categories by Asset Value</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category Code</th>
                    <th>Category Name</th>
                    <th>Asset Count</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($category_data as $category): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['code']); ?></td>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td><?php echo number_format($category['asset_count']); ?></td>
                    <td class="value-cell">₱<?php echo number_format($category['total_value'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Top Offices by Value -->
    <div class="analytics-section">
        <div class="section-title">Top Offices by Asset Value</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Office Name</th>
                    <th>Asset Count</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($office_asset_data as $office): ?>
                <tr>
                    <td><?php echo htmlspecialchars($office['office_name']); ?></td>
                    <td><?php echo number_format($office['asset_count']); ?></td>
                    <td class="value-cell">₱<?php echo number_format($office['total_value'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Most Consumed Consumables -->
    <div class="analytics-section">
        <div class="section-title">Top 10 Most Consumed Consumables</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Consumable Name</th>
                    <th>Consumption Count</th>
                    <th>Total Consumed Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consumables_data as $consumable): ?>
                <tr>
                    <td><?php echo htmlspecialchars($consumable['consumable_name']); ?></td>
                    <td><?php echo number_format($consumable['consumption_count']); ?></td>
                    <td class="value-cell">₱<?php echo number_format($consumable['total_consumed_value'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        // Close window after printing
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
