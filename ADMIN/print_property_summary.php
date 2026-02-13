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

// Log print summary access
logSystemAction($_SESSION['user_id'], 'access', 'print_property_summary', 'User accessed Print Property Summary page');

// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';
$print_section = $_GET['section'] ?? 'all';

// Get system settings for print header
$system_settings = [];
if ($conn && !$conn->connect_error) {
    $result = $conn->query("SELECT * FROM system_settings LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $system_settings = $row;
    }
}

// Get summary data
$office_summary = [];
$category_summary = [];
$total_items = 0;
$total_value = 0;

if ($conn && !$conn->connect_error) {
    try {
        // Base query
        $base_query = "FROM asset_items ai
                       LEFT JOIN asset_categories ac ON ai.category_id = ac.id
                       LEFT JOIN offices o1 ON ai.office_id = o1.id
                       LEFT JOIN employees e ON ai.employee_id = e.id
                       LEFT JOIN offices o2 ON e.office_id = o2.id
                       LEFT JOIN par_forms pf ON ai.par_id = pf.id
                       WHERE ai.par_id IS NOT NULL AND ai.par_id != ''";
        
        // Add filters
        $where_conditions = [];
        if (!empty($selected_category)) {
            $where_conditions[] = "ac.category_code = '" . $conn->real_escape_string($selected_category) . "'";
        }
        if (!empty($selected_office)) {
            $where_conditions[] = "(o1.office_code = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_code = '" . $conn->real_escape_string($selected_office) . "')";
        }
        
        if (!empty($where_conditions)) {
            $base_query .= " AND " . implode(" AND ", $where_conditions);
        }
        
        // Get office summary
        $office_query = "SELECT 
                            COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                            COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code,
                            COUNT(ai.id) as item_count,
                            SUM(ai.value) as total_value
                          $base_query
                          GROUP BY COALESCE(o1.office_name, o2.office_name, 'Unassigned'), COALESCE(o1.office_code, o2.office_code, 'NONE')
                          ORDER BY total_value DESC";
        
        $result = $conn->query($office_query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $office_summary[] = $row;
                $total_items += $row['item_count'];
                $total_value += $row['total_value'];
            }
        }
        
        // Get category summary
        $category_query = "SELECT 
                              COALESCE(ac.category_code, 'UNCAT') as category_code,
                              COALESCE(ac.category_name, 'Uncategorized') as category_name,
                              COUNT(ai.id) as item_count,
                              SUM(ai.value) as total_value
                            $base_query
                            GROUP BY COALESCE(ac.category_code, 'UNCAT'), COALESCE(ac.category_name, 'Uncategorized')
                            ORDER BY total_value DESC";
        
        $result = $conn->query($category_query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $category_summary[] = $row;
            }
        }
        
    } catch (Exception $e) {
        error_log("Print Property Summary Query Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Summary Report - PIMS</title>
    <style>
        @page {
            size: landscape;
            margin: 0.5in;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: white;
        }
        
        .print-header {
            margin-bottom: 20px;
        }
        
        .gov-header {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
        }
        
        .gov-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
            line-height: 1.2;
        }
        
        .municipality {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #000;
        }
        
        .province {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #000;
        }
        
        .print-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #191BA9;
        }
        
        .print-subtitle {
            font-size: 11px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .filters-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .summary-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            border: 2px solid #191BA9;
            padding: 10px;
            text-align: center;
            min-width: 120px;
            background: #f8f9fa;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #191BA9;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        .summary-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #191BA9;
            text-align: center;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        
        .report-table th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .report-table td {
            font-size: 10px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .grand-total {
            border-top: 2px solid #000;
            font-weight: bold;
            background: #f8f9fa;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Print Header -->
    <div class="print-header">
        <div style="display: flex; align-items: flex-start; gap: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <?php 
                if (!empty($system_settings['system_logo'])) {
                    echo '<img src="../' . htmlspecialchars($system_settings['system_logo']) . '" alt="' . htmlspecialchars($system_settings['system_name'] ?? 'PIMS') . '" style="max-width: 250px; max-height: 100px;">';
                } else {
                    echo '<img src="../img/system_logo.png" alt="' . htmlspecialchars($system_settings['system_name'] ?? 'PIMS') . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="municipality">Municipality of Pilar</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?> - Property Summary Report</div>
                    <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($selected_category) || !empty($selected_office)): ?>
        <div class="filters-info">
            <strong>Active Filters:</strong>
            <?php if (!empty($selected_category)): ?>
                Category: <?php echo htmlspecialchars($selected_category); ?>
            <?php endif; ?>
            <?php if (!empty($selected_office)): ?>
                <?php if (!empty($selected_category)) echo ' | '; ?>
                Office: <?php echo htmlspecialchars($selected_office); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Overall Statistics -->
    <div class="summary-stats">
        <div class="stat-box">
            <div class="stat-number"><?php echo number_format($total_items); ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">₱<?php echo number_format($total_value, 2); ?></div>
            <div class="stat-label">Total Value</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">₱<?php echo number_format(array_sum(array_column($office_summary, 'total_value')), 2); ?></div>
            <div class="stat-label">Office Total Value</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">₱<?php echo number_format(array_sum(array_column($category_summary, 'total_value')), 2); ?></div>
            <div class="stat-label">Category Total Value</div>
        </div>
    </div>
    
    <?php if (empty($office_summary) && empty($category_summary)): ?>
        <div class="no-data">
            <p>No property items found matching the current filters.</p>
        </div>
    <?php else: ?>
        <?php if ($print_section === 'all' || $print_section === 'offices'): ?>
            <!-- Office Summary -->
            <div class="summary-section">
                <div class="section-title">Summary by Office</div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Office Name</th>
                            <th>Office Code</th>
                            <th>Number of Items</th>
                            <th>Total Value</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($office_summary as $office): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($office['office_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($office['office_code']); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($office['item_count']); ?>
                                </td>
                                <td class="text-right">
                                    ₱<?php echo number_format($office['total_value'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($total_value > 0 ? ($office['total_value'] / $total_value) * 100 : 0, 1); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!empty($office_summary)): ?>
                            <tr class="grand-total">
                                <td colspan="2">
                                    <strong>Grand Total</strong>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($total_items); ?>
                                </td>
                                <td class="text-right">
                                    ₱<?php echo number_format($total_value, 2); ?>
                                </td>
                                <td class="text-center">
                                    100.0%
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if ($print_section === 'all' || $print_section === 'categories'): ?>
            <!-- Category Summary -->
            <div class="summary-section">
                <div class="section-title">Summary by Category</div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Category Code</th>
                            <th>Number of Items</th>
                            <th>Total Value</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_summary as $category): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($category['category_code']); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($category['item_count']); ?>
                                </td>
                                <td class="text-right">
                                    ₱<?php echo number_format($category['total_value'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($total_value > 0 ? ($category['total_value'] / $total_value) * 100 : 0, 1); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!empty($category_summary)): ?>
                            <tr class="grand-total">
                                <td colspan="2">
                                    <strong>Grand Total</strong>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($total_items); ?>
                                </td>
                                <td class="text-right">
                                    ₱<?php echo number_format($total_value, 2); ?>
                                </td>
                                <td class="text-center">
                                    100.0%
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
