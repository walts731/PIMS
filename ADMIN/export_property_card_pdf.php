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

// Log export action
logSystemAction($_SESSION['user_id'], 'export', 'property_card_pdf', 'User exported Property Card to PDF');

// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';

// Get asset items with PAR ID and filters
$asset_items = [];
if ($conn && !$conn->connect_error) {
    try {
        $query = "SELECT 
                    ai.id,
                    ai.created_at,
                    ai.property_no,
                    ai.description,
                    ai.value,
                    ai.par_id,
                    ai.employee_id,
                    ai.office_id,
                    COALESCE(ac.category_code, 'UNCAT') as asset_category,
                    COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                    COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code
                  FROM asset_items ai
                  LEFT JOIN asset_categories ac ON ai.category_id = ac.id
                  LEFT JOIN offices o1 ON ai.office_id = o1.id
                  LEFT JOIN employees e ON ai.employee_id = e.id
                  LEFT JOIN offices o2 ON e.office_id = o2.id
                  WHERE ai.par_id IS NOT NULL AND ai.par_id != ''";
        
        // Add category filter
        if (!empty($selected_category)) {
            $query .= " AND ac.category_code = '" . $conn->real_escape_string($selected_category) . "'";
        }
        
        // Add office filter
        if (!empty($selected_office)) {
            $query .= " AND (o1.office_code = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_code = '" . $conn->real_escape_string($selected_office) . "')";
        }
        
        $query .= " ORDER BY ai.created_at ASC";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Add employee and PAR info separately
                $row['employee_name'] = '';
                $row['employee_no'] = '';
                $row['par_no'] = '';
                
                // Get employee info
                if (!empty($row['employee_id'])) {
                    $emp_query = "SELECT CONCAT(firstname, ' ', lastname) as name, employee_no FROM employees WHERE id = " . intval($row['employee_id']);
                    $emp_result = $conn->query($emp_query);
                    if ($emp_result && $emp_data = $emp_result->fetch_assoc()) {
                        $row['employee_name'] = $emp_data['name'];
                        $row['employee_no'] = $emp_data['employee_no'];
                    }
                }
                
                // Get PAR info
                if (!empty($row['par_id'])) {
                    $par_query = "SELECT par_no, received_by_name FROM par_forms WHERE id = " . intval($row['par_id']);
                    $par_result = $conn->query($par_query);
                    if ($par_result && $par_data = $par_result->fetch_assoc()) {
                        $row['par_no'] = $par_data['par_no'];
                        $row['received_by'] = $par_data['received_by_name'];
                    }
                }
                
                $asset_items[] = $row;
            }
        }
    } catch (Exception $e) {
        // Error handling
        error_log("Error in property card PDF export: " . $e->getMessage());
    }
}

// Get system settings for header
$system_settings = [];
$system_name = 'Property Inventory Management System';
$logo_path = '../assets/images/logo.png';

if ($conn && !$conn->connect_error) {
    try {
        $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $system_settings[$row['setting_name']] = $row['setting_value'];
        }
        
        $system_name = $system_settings['system_name'] ?? $system_name;
        $logo_path = !empty($system_settings['system_logo']) ? '../' . $system_settings['system_logo'] : $logo_path;
        
        $stmt->close();
    } catch (Exception $e) {
        // Fallback to default if database fails
        $system_settings['system_logo'] = '';
        $system_settings['system_name'] = 'PIMS';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Card Report - PIMS</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5in;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #212529;
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .print-header img {
            max-width: 200px;
            height: auto;
            object-fit: contain;
        }
        
        .gov-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .gov-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .municipality {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .province {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .print-title {
            font-size: 18px;
            font-weight: bold;
            color: #191BA9;
            margin-bottom: 5px;
        }
        
        .print-subtitle {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            justify-content: space-between;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            flex: 1;
            text-align: center;
        }
        
        .stat-value {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .stat-label {
            font-size: 8px;
            opacity: 0.9;
        }
        
        .table-container {
            width: 100%;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        th {
            background-color: #191BA9;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 6px 4px;
            border: 1px solid #dee2e6;
            white-space: nowrap;
        }
        
        td {
            padding: 5px 4px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .property-no {
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        
        .category-badge {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 500;
            display: inline-block;
        }
        
        .office-code {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 500;
            font-family: 'Courier New', monospace;
            display: inline-block;
        }
        
        .quantity-badge {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
            text-align: center;
        }
        
        .value-cell {
            font-weight: bold;
            text-align: right;
        }
        
        .balance-qty {
            font-weight: bold;
            color: #fd7e14;
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 8px;
            color: #6c757d;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            font-size: 12px;
            color: #6c757d;
        }
        
        @media print {
            .no-print {
                display: none !important;
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
                    echo '<img src="' . htmlspecialchars($logo_path) . '" alt="' . htmlspecialchars($system_name) . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="municipality">Municipality of Pilar</div>
                    <div class="print-title"><?php echo htmlspecialchars($system_name); ?> - Property Card Report</div>
                    <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
                    <?php if (!empty($selected_category) || !empty($selected_office)): ?>
                        <div class="print-subtitle">
                            Filters: 
                            <?php if (!empty($selected_category)): ?>Category: <?php echo htmlspecialchars($selected_category); ?><?php endif; ?>
                            <?php if (!empty($selected_category) && !empty($selected_office)): ?> | <?php endif; ?>
                            <?php if (!empty($selected_office)): ?>Office: <?php echo htmlspecialchars($selected_office); ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($asset_items)): ?>
        <div class="no-data">
            <h4>No Property Items Found</h4>
            <p>There are no asset items with PAR references matching the current filters.</p>
        </div>
    <?php else: ?>
        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($asset_items); ?></div>
                <div class="stat-label">Total Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₱<?php echo number_format(array_sum(array_column($asset_items, 'value')), 2); ?></div>
                <div class="stat-label">Total Value</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count(array_unique(array_column($asset_items, 'par_id'))); ?></div>
                <div class="stat-label">PAR Forms</div>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Property No.</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Office</th>
                        <th>Employee Name</th>
                        <th>Employee No.</th>
                        <th>PAR No.</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Total Value</th>
                        <th class="text-center">Balance Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $item_counter = 1;
                    foreach ($asset_items as $item): 
                    ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                            <td><span class="property-no"><?php echo htmlspecialchars($item['property_no']); ?></span></td>
                            <td><span class="category-badge"><?php echo htmlspecialchars($item['asset_category']); ?></span></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><span class="office-code"><?php echo htmlspecialchars($item['office_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($item['employee_name'] ?: 'Not assigned'); ?></td>
                            <td><?php echo htmlspecialchars($item['employee_no']); ?></td>
                            <td><?php echo htmlspecialchars($item['par_no']); ?></td>
                            <td class="text-center"><span class="quantity-badge">1</span></td>
                            <td class="value-cell">₱<?php echo number_format($item['value'], 2); ?></td>
                            <td class="value-cell">₱<?php echo number_format($item['value'], 2); ?></td>
                            <td class="balance-qty"><?php echo $item_counter; ?></td>
                        </tr>
                    <?php 
                        $item_counter++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="footer">
        <p>Report generated by <?php echo htmlspecialchars($_SESSION['username'] ?? 'System'); ?> on <?php echo date('F d, Y h:i:s A'); ?></p>
        <p><?php echo htmlspecialchars($system_name); ?> - Property Inventory Management System</p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
