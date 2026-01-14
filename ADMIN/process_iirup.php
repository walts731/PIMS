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
        
        // Get form data
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
        
        // Insert into iirup_forms table
        $sql = "INSERT INTO iirup_forms (
            form_number, as_of_year, accountable_officer, designation, department_office,
            accountable_officer_name, accountable_officer_designation, authorized_official_name,
            authorized_official_designation, inspection_officer_name, witness_name,
            status, total_items, created_by, updated_by, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, NOW(), NOW())";
        
        $total_items = count($_POST['particulars']);
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sissssssssiiii",
            $form_number, $as_of_year, $accountable_officer, $designation, $department_office,
            $accountable_officer_name, $accountable_officer_designation, $authorized_official_name,
            $authorized_official_designation, $inspection_officer_name, $witness_name,
            $total_items, $_SESSION['user_id'], $_SESSION['user_id']
        );
        $stmt->execute();
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
        
        // Insert items into iirup_items table
        $item_sql = "INSERT INTO iirup_items (
            form_id, date_acquired, particulars, property_no, quantity, unit_cost, total_cost,
            accumulated_depreciation, impairment_losses, carrying_amount, inventory_remarks,
            disposal_sale, disposal_transfer, disposal_destruction, disposal_others, disposal_total,
            appraised_value, total, or_no, amount, dept_office, control_no, date_received,
            item_order, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $item_stmt = $conn->prepare($item_sql);
        
        $asset_ids_to_update = [];
        
        foreach ($particulars as $index => $particular) {
            if (!empty($particular)) {
                $item_order = $index + 1;
                
                $item_stmt->bind_param(
                    "isssddddddsdddddssdsssi",
                    $form_id,
                    $dates_acquired[$index] ?? null,
                    $particular,
                    $property_nos[$index] ?? null,
                    $quantities[$index] ?? 0,
                    $unit_costs[$index] ?? 0,
                    $total_costs[$index] ?? 0,
                    $accumulated_depreciations[$index] ?? 0,
                    $impairment_losses[$index] ?? 0,
                    $carrying_amounts[$index] ?? 0,
                    $inventory_remarks[$index] ?? null,
                    $disposal_sales[$index] ?? 0,
                    $disposal_transfers[$index] ?? 0,
                    $disposal_destructions[$index] ?? 0,
                    $disposal_others[$index] ?? null,
                    $disposal_totals[$index] ?? 0,
                    $appraised_values[$index] ?? 0,
                    $totals[$index] ?? 0,
                    $or_nos[$index] ?? null,
                    $amounts[$index] ?? 0,
                    $dept_offices[$index] ?? null,
                    $control_nos[$index] ?? null,
                    $dates_received[$index] ?? null,
                    $item_order
                );
                $item_stmt->execute();
                
                // Extract asset ID from particulars if it matches an asset description
                $asset_id = extractAssetIdFromDescription($particular);
                if ($asset_id) {
                    $asset_ids_to_update[] = $asset_id;
                }
            }
        }
        
        // Update asset items status to unserviceable
        if (!empty($asset_ids_to_update)) {
            $unique_asset_ids = array_unique($asset_ids_to_update);
            $placeholders = str_repeat('?,', count($unique_asset_ids) - 1) . '?';
            
            $update_sql = "UPDATE asset_items SET status = 'unserviceable', updated_at = NOW() 
                          WHERE id IN ($placeholders) AND status != 'disposed'";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param(str_repeat('i', count($unique_asset_ids)), ...$unique_asset_ids);
            $update_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log the action
        logSystemAction($_SESSION['user_id'], 'Created IIRUP Form', 'forms', 'form_id: ' . $form_id . ', form_number: ' . $form_number);
        
        $_SESSION['success'] = "IIRUP Form '$form_number' has been created successfully!";
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

// Helper function to extract asset ID from description
function extractAssetIdFromDescription($description) {
    // Try to extract property number from description
    if (preg_match('/Property\s*No\s*:\s*([A-Za-z0-9-]+)/i', $description, $matches)) {
        return $matches[1]; // Return property number
    }
    
    // If description starts with property number pattern (like "PROP-001")
    if (preg_match('/^([A-Za-z0-9-]+)/', $description, $matches)) {
        return $matches[1];
    }
    
    // Fallback to ID if no property number found
    if (preg_match('/ID:\s*(\d+)/i', $description, $matches)) {
        return (int)$matches[1];
    }
    
    // If description starts with a number, assume it's ID
    if (preg_match('/^(\d+)/', $description, $matches)) {
        return (int)$matches[1];
    }
    
    return null;
}

$conn->close();
?>
