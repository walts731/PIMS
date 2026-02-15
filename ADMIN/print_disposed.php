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

// Get filters from URL
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE conditions
$where_conditions = [];
$params = [];
$types = '';

// Office filter
if ($office_filter > 0) {
    $where_conditions[] = "(rt.office_id = ? OR ai.office_id = ?)";
    $params[] = $office_filter;
    $params[] = $office_filter;
    $types .= 'ii';
}

// Date filters
if (!empty($date_from)) {
    $where_conditions[] = "(COALESCE(rt.disposal_date, ai.disposal_date) >= ?)";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "(COALESCE(rt.disposal_date, ai.disposal_date) <= ?)";
    $params[] = $date_to;
    $types .= 's';
}

// Search filter
if (!empty($search_filter)) {
    $where_conditions[] = "(rt.item_description LIKE ? OR rt.control_no LIKE ? OR rt.red_tag_no LIKE ? OR ai.description LIKE ? OR ai.property_no LIKE ? OR o.office_name LIKE ?)";
    $search_term = '%' . $search_filter . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sssssss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get disposed items data
$data = [];
$total_value = 0;
$total_count = 0;

// Get disposed items from both red_tags and asset_items
$disposed_items = [];

// Get disposed items from red_tags
$red_tags_sql = "SELECT rt.*, ai.description as asset_description, ai.property_no, ai.inventory_tag, 
                        ai.value, ai.disposal_date as asset_disposal_date, ai.disposal_reason as asset_disposal_reason,
                        a.description as category_name, o.office_name,
                        CONCAT(u.first_name, ' ', u.last_name) as disposed_by_name
                 FROM red_tags rt
                 LEFT JOIN asset_items ai ON rt.asset_item_id = ai.id
                 LEFT JOIN assets a ON ai.asset_id = a.id
                 LEFT JOIN offices o ON rt.office_id = o.id
                 LEFT JOIN users u ON rt.updated_by = u.id
                 WHERE rt.action = 'disposed'";

if (!empty($where_conditions)) {
    $red_tags_sql .= ' AND ' . str_replace('rt.office_id', 'rt.office_id', str_replace('ai.office_id', 'ai.office_id', $where_conditions));
}

$red_tags_sql .= " ORDER BY COALESCE(rt.disposal_date, ai.disposal_date) DESC";

$stmt = $conn->prepare($red_tags_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $disposed_items[] = $row;
    $total_value += $row['value'];
    $total_count++;
}
$stmt->close();

// Also get disposed items from asset_items that don't have red tags
$asset_items_sql = "SELECT ai.*, 
                    a.description as category_name, o.office_name,
                    'System' as disposed_by_name,
                    'Direct Disposal' as control_no,
                    CONCAT('DISPOSAL-', ai.id) as red_tag_no,
                    ai.disposal_date,
                    ai.disposal_reason
             FROM asset_items ai
             LEFT JOIN assets a ON ai.asset_id = a.id
             LEFT JOIN offices o ON ai.office_id = o.id
             WHERE ai.status = 'disposed' 
             AND ai.id NOT IN (SELECT DISTINCT asset_item_id FROM red_tags WHERE action = 'disposed' AND asset_item_id IS NOT NULL)";

if (!empty($where_conditions)) {
    // Adjust conditions for asset_items query
    $asset_where_conditions = [];
    $asset_params = [];
    $asset_types = '';
    
    if ($office_filter > 0) {
        $asset_where_conditions[] = "ai.office_id = ?";
        $asset_params[] = $office_filter;
        $asset_types .= 'i';
    }
    
    if (!empty($date_from)) {
        $asset_where_conditions[] = "ai.disposal_date >= ?";
        $asset_params[] = $date_from;
        $asset_types .= 's';
    }
    
    if (!empty($date_to)) {
        $asset_where_conditions[] = "ai.disposal_date <= ?";
        $asset_params[] = $date_to;
        $asset_types .= 's';
    }
    
    if (!empty($search_filter)) {
        $asset_where_conditions[] = "(ai.description LIKE ? OR ai.property_no LIKE ? OR o.office_name LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $asset_params[] = $search_term;
        $asset_params[] = $search_term;
        $asset_params[] = $search_term;
        $asset_types .= 'sss';
    }
    
    if (!empty($asset_where_conditions)) {
        $asset_items_sql .= ' AND ' . implode(' AND ', $asset_where_conditions);
        $stmt = $conn->prepare($asset_items_sql);
        $stmt->bind_param($asset_types, ...$asset_params);
    } else {
        $stmt = $conn->prepare($asset_items_sql);
    }
} else {
    $stmt = $conn->prepare($asset_items_sql);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Map the fields to match red_tags structure
    $row['item_description'] = $row['description'];
    $row['tagged_by'] = $row['disposed_by_name'] ?? 'System';
    $row['item_location'] = $row['office_name'] ?? 'N/A';
    $row['removal_reason'] = $row['disposal_reason'] ?? 'N/A';
    $disposed_items[] = $row;
    $total_value += $row['value'];
    $total_count++;
}
$stmt->close();

// Get system settings
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
    <title>Disposed Items Report - PIMS</title>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .print-header {
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
        
        .report-table th {
            background: #f0f0f0 !important;
            color: #000 !important;
            border: 1px solid #000;
            font-weight: 600;
            padding: 8px;
            text-align: left;
        }
        
        .report-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        
        .stats-box {
            display: flex;
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
                    echo '<img src="../img/trans_logo.png" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="municipality">Municipality of Pilar</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name']); ?> - Disposed Items Report</div>
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
            
            <?php if (!empty($search_filter)): ?>
                Search: <?php echo htmlspecialchars($search_filter); ?><br>
            <?php endif; ?>
            
            <?php if (!empty($date_from)): ?>
                Date From: <?php echo date('F j, Y', strtotime($date_from)); ?><br>
            <?php endif; ?>
            
            <?php if (!empty($date_to)): ?>
                Date To: <?php echo date('F j, Y', strtotime($date_to)); ?><br>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-box">
        <div class="stat-box">
            <div class="stat-number"><?php echo number_format($total_count); ?></div>
            <div class="stat-label">Total Disposed Items</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">₱<?php echo number_format($total_value, 2); ?></div>
            <div class="stat-label">Total Value</div>
        </div>
    </div>
    
    <!-- Disposed Items Table -->
    <table class="report-table">
        <thead>
            <tr>
                <th>Control No.</th>
                <th>Red Tag No.</th>
                <th>Item Description</th>
                <th>Category</th>
                <th>Property No.</th>
                <th>Value</th>
                <th>Office</th>
                <th>Disposal Date</th>
                <th>Disposal Reason</th>
                <th>Disposed By</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($disposed_items)): ?>
                <tr>
                    <td colspan="11" style="text-align: center; font-style: italic;">No disposed items found matching the criteria.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($disposed_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['control_no']); ?></td>
                        <td><?php echo htmlspecialchars($item['red_tag_no']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['item_description']); ?>
                            <?php if (!empty($item['category_name'])): ?>
                                <br><small><?php echo htmlspecialchars($item['category_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['property_no'] ?? 'N/A'); ?></td>
                        <td>₱<?php echo number_format($item['value'] ?? 0, 2); ?></td>
                        <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            $disposal_date = $item['disposal_date'] ?? $item['asset_disposal_date'] ?? 'N/A';
                            if ($disposal_date !== 'N/A') {
                                echo date('M d, Y', strtotime($disposal_date));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $disposal_reason = $item['disposal_reason'] ?? $item['asset_disposal_reason'] ?? 'N/A';
                            echo htmlspecialchars($disposal_reason);
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['disposed_by_name'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
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
