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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get peripheral data from form
        $component_type = $_POST['component_type'] ?? 'main_asset';
        $id = $_POST['id'] ?? '';
        $asset_id = $_POST['asset_id'] ?? '';
        $peripheral_name = $_POST['peripheral_name'] ?? '';
        $peripheral_model = $_POST['peripheral_model'] ?? '';
        $peripheral_serial_number = $_POST['peripheral_serial_number'] ?? '';
        $peripheral_status = $_POST['peripheral_status'] ?? '';
        
        // Get form data for IIRUP processing
        $as_of_year = $_POST['as_of_year'];
        $accountable_officer = $_POST['accountable_officer'];
        $designation = $_POST['designation'];
        $department_office = $_POST['department_office'];
        $accountable_officer_name = $_POST['accountable_officer_name'];
        $accountable_officer_designation = $_POST['accountable_officer_designation'];
        $authorized_official_name = $_POST['authorized_official_name'];
        $authorized_official_designation = $_POST['authorized_official_designation'];
        $inspection_officer_name = $_POST['inspection_officer_name'];
        $witness_name = $_POST['witness_name'];
        
        // Generate form number
        $form_number = 'IIRUP-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Get total items count
        $total_items = count($_POST['particulars']);
        
        // Insert into iirup_forms table
        $form_number = $conn->real_escape_string($form_number);
        $as_of_year = (int)$as_of_year;
        $accountable_officer = $conn->real_escape_string($accountable_officer);
        $designation = $conn->real_escape_string($designation);
        $department_office = $conn->real_escape_string($department_office);
        $accountable_officer_name = $conn->real_escape_string($accountable_officer_name);
        $accountable_officer_designation = $conn->real_escape_string($accountable_officer_designation);
        $authorized_official_name = $conn->real_escape_string($authorized_official_name);
        $authorized_official_designation = $conn->real_escape_string($authorized_official_designation);
        $inspection_officer_name = $conn->real_escape_string($inspection_officer_name);
        $witness_name = $conn->real_escape_string($witness_name);
        $total_items = (int)$total_items;
        $created_by = (int)$_SESSION['user_id'];
        $updated_by = (int)$_SESSION['user_id'];
        
        $sql = "INSERT INTO iirup_forms (
            form_number, as_of_year, accountable_officer, designation, department_office,
            accountable_officer_name, accountable_officer_designation, authorized_official_name,
            authorized_official_designation, inspection_officer_name, witness_name,
            status, total_items, created_by, updated_by, created_at
        ) VALUES (
            '$form_number', $as_of_year, '$accountable_officer', '$designation', '$department_office',
            '$accountable_officer_name', '$accountable_officer_designation', '$authorized_official_name',
            '$authorized_official_designation', '$inspection_officer_name', '$witness_name',
            'draft', $total_items, $created_by, $updated_by, NOW()
        )";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("SQL Error: " . $conn->error);
        }
        
        $form_id = $conn->insert_id;
        
        // Get all posted item data
        $particulars = $_POST['particulars'];
        $dates_acquired = $_POST['date_acquired'];
        $property_nos = $_POST['property_no'];
        $quantities = $_POST['qty'];
        $unit_costs = $_POST['unit_cost'];
        $total_costs = $_POST['total_cost'];
        $accumulated_depreciations = $_POST['accumulated_depreciation'];
        $impairment_losses = $_POST['impairment_losses'];
        $carrying_amounts = $_POST['carrying_amount'];
        $inventory_remarks = $_POST['inventory_remarks'];
        $disposal_sales = $_POST['disposal_sale'];
        $disposal_transfers = $_POST['disposal_transfer'];
        $disposal_destructions = $_POST['disposal_destruction'];
        $disposal_others = $_POST['disposal_others'];
        $disposal_totals = $_POST['disposal_total'];
        $appraised_values = $_POST['appraised_value'];
        $totals = $_POST['total'];
        $or_nos = $_POST['or_no'];
        $amounts = $_POST['amount'];
        $dept_offices = $_POST['dept_office'];
        $control_nos = $_POST['control_no'];
        $dates_received = $_POST['date_received'];
        
        // Process peripheral status update first
        $peripheral_updated = false;
        if ($component_type === 'peripheral') {
            $peripheral_id = !empty($id) ? $id : $asset_id;
            
            if ($peripheral_id && is_numeric($peripheral_id)) {
                // Check if peripheral exists
                $check_sql = "SELECT id, status, name, model, serial_number FROM peripherals WHERE id = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("i", $peripheral_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $peripheral_record = $result->fetch_assoc();
                    $current_status = $peripheral_record['status'];
                    
                    // Update peripheral status to unserviceable
                    $update_sql = "UPDATE peripherals SET status = 'unserviceable', updated_at = NOW(), updated_by = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ii", $_SESSION['user_id'], $peripheral_id);
                    
                    if ($update_stmt->execute()) {
                        $affected_rows = $update_stmt->affected_rows;
                        if ($affected_rows > 0) {
                            $peripheral_updated = true;
                            logSystemAction($_SESSION['user_id'], "Updated peripheral status to unserviceable via IIRUP", 'peripherals', "Peripheral ID: $peripheral_id, Name: {$peripheral_record['name']}");
                        }
                    }
                    
                    $update_stmt->close();
                }
                
                $stmt->close();
            }
        }
        
        // Insert items into iirup_items table
        foreach ($particulars as $index => $particular) {
            if (!empty($particular)) {
                $item_order = $index + 1;
                
                // Escape and format values
                $safe_particulars = $conn->real_escape_string($particular);
                $safe_property_no = $conn->real_escape_string($property_nos[$index] ?? '');
                $safe_inventory_remarks = $conn->real_escape_string($inventory_remarks[$index] ?? '');
                $safe_disposal_others = $conn->real_escape_string($disposal_others[$index] ?? '');
                $safe_or_no = $conn->real_escape_string($or_nos[$index] ?? '');
                $safe_dept_office = $conn->real_escape_string($dept_offices[$index] ?? '');
                $safe_control_no = $conn->real_escape_string($control_nos[$index] ?? '');
                
                $date_acquired = !empty($dates_acquired[$index]) ? "'" . $conn->real_escape_string($dates_acquired[$index]) . "'" : 'NULL';
                $quantity = (float)($quantities[$index] ?? 0);
                $unit_cost = (float)($unit_costs[$index] ?? 0);
                $total_cost = (float)($total_costs[$index] ?? 0);
                $accumulated_depreciation = (float)($accumulated_depreciations[$index] ?? 0);
                $impairment_losses = (float)($impairment_losses[$index] ?? 0);
                $carrying_amount = (float)($carrying_amounts[$index] ?? 0);
                $disposal_sale = (float)($disposal_sales[$index] ?? 0);
                $disposal_transfer = (float)($disposal_transfers[$index] ?? 0);
                $disposal_destruction = (float)($disposal_destructions[$index] ?? 0);
                $disposal_total = (float)($disposal_totals[$index] ?? 0);
                $appraised_value = (float)($appraised_values[$index] ?? 0);
                $total = (float)($totals[$index] ?? 0);
                $amount = (float)($amounts[$index] ?? 0);
                $date_received = !empty($dates_received[$index]) ? "'" . $conn->real_escape_string($dates_received[$index]) . "'" : 'NULL';
                
                $item_sql = "INSERT INTO iirup_items (
                    form_id, date_acquired, particulars, property_no, quantity, unit_cost, total_cost,
                    accumulated_depreciation, impairment_losses, carrying_amount, inventory_remarks,
                    disposal_sale, disposal_transfer, disposal_destruction, disposal_others, disposal_total,
                    appraised_value, total, or_no, amount, dept_office, control_no, date_received,
                    item_order, created_at
                ) VALUES (
                    $form_id, $date_acquired, '$safe_particulars', '$safe_property_no', $quantity, $unit_cost, $total_cost,
                    $accumulated_depreciation, $impairment_losses, $carrying_amount, '$safe_inventory_remarks',
                    $disposal_sale, $disposal_transfer, $disposal_destruction, '$safe_disposal_others', $disposal_total,
                    $appraised_value, $total, '$safe_or_no', $amount, '$safe_dept_office', '$safe_control_no', $date_received,
                    $item_order, NOW()
                )";
                
                $conn->query($item_sql);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log the action
        logSystemAction($_SESSION['user_id'], 'Created IIRUP Form', 'forms', 'form_id: ' . $form_id . ', form_number: ' . $form_number);
        
        $_SESSION['success'] = "IIRUP Form '$form_number' has been created successfully!" . ($peripheral_updated ? " Peripheral status updated to unserviceable." : "");
        header('Location: iirup_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log error
        logSystemAction($_SESSION['user_id'], 'Error creating IIRUP Form: ' . $e->getMessage(), 'forms', 'error');
        
        $_SESSION['error'] = "Error creating IIRUP Form: " . $e->getMessage();
        header('Location: iirup_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: iirup_form.php');
    exit();
}

$conn->close();
?>
