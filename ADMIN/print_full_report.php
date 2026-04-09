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
logSystemAction($_SESSION['user_id'], 'print', 'full_property_report', 'User generated full property report');

// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';
$selected_tab = $_GET['tab'] ?? 'fixed';

// Get asset items with filters
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
                    ai.ics_id,
                    ai.employee_id,
                    ai.office_id,
                    COALESCE(ac.category_name, 'Uncategorized') as asset_category,
                    COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                    COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code,
                    ai.ics_par_no
                  FROM asset_items ai
                  LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                  LEFT JOIN offices o1 ON ai.office_id = o1.id
                  LEFT JOIN employees e ON ai.employee_id = e.id
                  LEFT JOIN offices o2 ON e.office_id = o2.id";
        
        if ($selected_tab == 'semi') {
            $query .= " WHERE (ai.ics_id IS NOT NULL AND ai.ics_id != '') OR (ai.ics_par_no IS NOT NULL AND ai.ics_par_no != '' AND ai.value < 50000)";
        } else {
            $query .= " WHERE (ai.par_id IS NOT NULL AND ai.par_id != '') OR (ai.ics_par_no IS NOT NULL AND ai.ics_par_no != '' AND ai.value >= 50000)";
        }
        
        // Add category filter
        if (!empty($selected_category)) {
            $query .= " AND ac.category_name = '" . $conn->real_escape_string($selected_category) . "'";
        }
        
        // Add office filter
        if (!empty($selected_office)) {
            $query .= " AND (o1.office_name = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_name = '" . $conn->real_escape_string($selected_office) . "')";
        }
        
        $query .= " ORDER BY ai.created_at ASC";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Add employee info
                $row['employee_name'] = '';
                if (!empty($row['employee_id'])) {
                    $emp_query = "SELECT CONCAT(firstname, ' ', lastname) as name FROM employees WHERE id = " . intval($row['employee_id']);
                    $emp_result = $conn->query($emp_query);
                    if ($emp_result && $emp_data = $emp_result->fetch_assoc()) {
                        $row['employee_name'] = $emp_data['name'];
                    }
                }
                
                // Get reference info (PAR or ICS)
                $row['ref_no'] = '';
                if ($selected_tab == 'semi') {
                    if (!empty($row['ics_id'])) {
                        $ics_query = "SELECT ics_no FROM ics_forms WHERE id = " . intval($row['ics_id']);
                        $ics_result = $conn->query($ics_query);
                        if ($ics_result && $ics_data = $ics_result->fetch_assoc()) {
                            $row['ref_no'] = $ics_data['ics_no'];
                        }
                    }
                } else {
                    if (!empty($row['par_id'])) {
                        $par_query = "SELECT par_no FROM par_forms WHERE id = " . intval($row['par_id']);
                        $par_result = $conn->query($par_query);
                        if ($par_result && $par_data = $par_result->fetch_assoc()) {
                            $row['ref_no'] = $par_data['par_no'];
                        }
                    }
                }
                if (empty($row['ref_no'])) {
                    $row['ref_no'] = $row['ics_par_no'] ?: '';
                }
                
                $asset_items[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error in full report generation: " . $e->getMessage());
    }
}

// Get system settings for header
$system_settings = [];
$system_name = 'Property Inventory Management System';
if ($conn && !$conn->connect_error) {
    try {
        $result = $conn->query("SELECT setting_name, setting_value FROM system_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $system_settings[$row['setting_name']] = $row['setting_value'];
            }
        }
        $system_name = $system_settings['system_name'] ?? $system_name;
    } catch (Exception $e) {
        error_log("Error fetching system settings: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Property Report - <?php echo date('Y-m-d'); ?></title>
    <style>
        @page {
            size: landscape;
            margin: 0.5in;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000000;
            margin: 0;
            padding: 0;
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 20px;
            padding: 10px;
        }
        
        .gov-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .gov-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .municipality, .province {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        
        .report-subtitle {
            font-size: 12px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .table-container {
            width: 100%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #000000;
            padding: 6px 4px;
            text-align: left;
        }
        
        th {
            background-color: transparent;
            font-weight: bold;
            text-align: center;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .footer {
            margin-top: 30px;
            border-top: 1px solid #000000;
            padding-top: 10px;
            font-size: 8px;
            text-align: center;
        }

        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <div style="display: flex; align-items: start; gap: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <?php 
                $print_logo_path = '../assets/images/logo.png';
                if (!empty($system_settings['system_logo'])) {
                    echo '<img src="../' . htmlspecialchars($system_settings['system_logo']) . '" alt="' . htmlspecialchars($system_name) . '" style="max-width: 250px; max-height: 100px;">';
                } else {
                    echo '<img src="' . htmlspecialchars($print_logo_path) . '" alt="' . htmlspecialchars($system_name) . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header centered -->
            <div style="flex: 1; text-align: center; margin-right: 170px;">
                <div class="gov-title">Republic of the Philippines</div>
                <div class="province">Province of Sorsogon</div>
                <div class="municipality">Municipality of Pilar</div>
                <div class="report-title"><?php echo htmlspecialchars($system_name); ?> - Full Property Report</div>
                <div class="report-subtitle">
                    <?php echo $selected_tab == 'semi' ? 'Semi-Expandable Items' : 'Property (PPE) Items'; ?>
                    &bull; Generated on <?php echo date('F j, Y g:i A'); ?>
                    <?php if ($selected_category || $selected_office): ?>
                        <br>
                        Filters: <?php echo $selected_category ? "Category: $selected_category" : ""; ?> 
                        <?php echo ($selected_category && $selected_office) ? " | " : ""; ?>
                        <?php echo $selected_office ? "Office: $selected_office" : ""; ?>
                    <?php endif; ?>
                </div>
            </div>
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
                    <th>Ref. No. (<?php echo $selected_tab == 'semi' ? 'ICS' : 'PAR'; ?>)</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Total Value</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($asset_items)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">No records found matching the criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($asset_items as $item): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                            <td style="font-family: monospace;"><?php echo htmlspecialchars($item['property_no']); ?></td>
                            <td><?php echo htmlspecialchars($item['asset_category']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo htmlspecialchars($item['office_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['employee_name'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['ref_no']); ?></td>
                            <td class="text-right">₱<?php echo number_format($item['value'], 2); ?></td>
                            <td class="text-right">₱<?php echo number_format($item['value'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Report generated by <?php echo htmlspecialchars($_SESSION['username'] ?? 'System'); ?> on <?php echo date('F d, Y h:i:s A'); ?></p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
