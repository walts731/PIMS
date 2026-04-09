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
logSystemAction($_SESSION['user_id'], 'export', 'property_card_csv', 'User exported Property Card to CSV');

// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';

// Get tab parameter
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
                    COALESCE(ac.category_name, 'UNCAT') as asset_category,
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
                // Add employee and reference info separately
                $row['employee_name'] = '';
                $row['employee_no'] = '';
                $row['ref_no'] = '';
                
                // Get employee info
                if (!empty($row['employee_id'])) {
                    $emp_query = "SELECT CONCAT(firstname, ' ', lastname) as name, employee_no FROM employees WHERE id = " . intval($row['employee_id']);
                    $emp_result = $conn->query($emp_query);
                    if ($emp_result && $emp_data = $emp_result->fetch_assoc()) {
                        $row['employee_name'] = $emp_data['name'];
                        $row['employee_no'] = $emp_data['employee_no'];
                    }
                }
                
                // Get reference info (PAR or ICS)
                if ($selected_tab == 'semi') {
                    if (!empty($row['ics_id'])) {
                        $ics_query = "SELECT ics_no FROM ics_forms WHERE id = " . intval($row['ics_id']);
                        $ics_result = $conn->query($ics_query);
                        if ($ics_result && $ics_data = $ics_result->fetch_assoc()) {
                            $row['ref_no'] = $ics_data['ics_no'];
                        }
                    }
                    if (empty($row['ref_no'])) {
                        $row['ref_no'] = $row['ics_par_no'] ?: '';
                    }
                } else {
                    if (!empty($row['par_id'])) {
                        $par_query = "SELECT par_no FROM par_forms WHERE id = " . intval($row['par_id']);
                        $par_result = $conn->query($par_query);
                        if ($par_result && $par_data = $par_result->fetch_assoc()) {
                            $row['ref_no'] = $par_data['par_no'];
                        }
                    }
                    if (empty($row['ref_no'])) {
                        $row['ref_no'] = $row['ics_par_no'] ?: '';
                    }
                }
                
                $asset_items[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error in property card CSV export: " . $e->getMessage());
    }
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="property_card_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel display
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'Date',
    'Property No.',
    'Category',
    'Description',
    'Office',
    'Employee Name',
    'Employee No.',
    ($selected_tab == 'semi' ? 'ICS No.' : 'PAR No.'),
    'Receipt/Quantity',
    'Unit Cost',
    'Total Value',
    'Balance Qty'
];

fputcsv($output, $headers);

// Add data rows
$item_counter = 1;
foreach ($asset_items as $item) {
    $row = [
        date('M d, Y', strtotime($item['created_at'])),
        $item['property_no'],
        $item['asset_category'],
        $item['description'],
        $item['office_name'],
        $item['employee_name'],
        $item['employee_no'],
        $item['ref_no'],
        '1', // Quantity
        $item['value'],
        $item['value'], // Total Value (same as unit cost for single items)
        $item_counter++ // Balance Qty
    ];
    
    fputcsv($output, $row);
}

// Close output stream
fclose($output);
exit();
?>
