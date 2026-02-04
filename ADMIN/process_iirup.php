<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Debug: Log that file was accessed
error_log("IIRUP Process file accessed at " . date('Y-m-d H:i:s') . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

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
        
        // Insert items into iirup_items table
        $asset_ids_to_update = [];
        
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
                
                // Extract asset ID from property number field first, then from particulars
                $asset_id = null;
                $property_no = $property_nos[$index] ?? '';
                
                // First try to find asset by property number from the dedicated field
                if (!empty($property_no)) {
                    $asset_id = getAssetIdByPropertyNo($property_no);
                }
                
                // If not found, try to extract from particulars description
                if (!$asset_id && !empty($particular)) {
                    $asset_id = extractAssetIdFromDescription($particular);
                }
                
                if ($asset_id) {
                    $asset_ids_to_update[] = $asset_id;
                }
            }
        }
        
        // Update asset items status to unserviceable
        if (!empty($asset_ids_to_update)) {
            $unique_asset_ids = array_unique($asset_ids_to_update);
            
            if (!empty($unique_asset_ids)) {
                $ids_string = implode(',', array_map('intval', $unique_asset_ids));
                
                // First, check which assets exist and their current status
                $check_sql = "SELECT id, property_no, status FROM asset_items WHERE id IN ($ids_string)";
                $check_result = $conn->query($check_sql);
                
                $assets_to_update = [];
                $already_disposed = [];
                $already_unserviceable = [];
                
                while ($asset = $check_result->fetch_assoc()) {
                    if ($asset['status'] === 'disposed') {
                        $already_disposed[] = $asset['property_no'];
                    } elseif ($asset['status'] === 'unserviceable') {
                        $already_unserviceable[] = $asset['property_no'];
                    } else {
                        $assets_to_update[] = $asset['id'];
                    }
                }
                
                // Log asset status information
                if (!empty($already_disposed)) {
                    error_log("Assets already disposed, not updating: " . implode(', ', $already_disposed));
                }
                if (!empty($already_unserviceable)) {
                    error_log("Assets already unserviceable: " . implode(', ', $already_unserviceable));
                }
                
                    // Update only assets that can be changed to unserviceable
                    if (!empty($assets_to_update)) {
                        $update_ids_string = implode(',', $assets_to_update);
                        $update_sql = "UPDATE asset_items SET status = 'unserviceable', last_updated = NOW() 
                                      WHERE id IN ($update_ids_string)";
                        
                        $update_result = $conn->query($update_sql);
                        $updated_count = $conn->affected_rows;
                        
                        error_log("Updated $updated_count asset items to unserviceable. IDs: " . implode(', ', $assets_to_update));
                        
                        // Record history for each updated asset
                        foreach ($assets_to_update as $asset_id) {
                            $history_sql = "INSERT INTO asset_item_history (item_id, action, old_value, new_value, created_by, created_at, details) 
                                          VALUES (?, 'status_change', 'serviceable', 'unserviceable', ?, NOW(), 'Status changed via IIRUP Form: $form_number')";
                            $history_stmt = $conn->prepare($history_sql);
                            $history_stmt->bind_param("ii", $asset_id, $_SESSION['user_id']);
                            $history_stmt->execute();
                            $history_stmt->close();
                        }
                        
                        // Log the action for audit trail
                        logSystemAction($_SESSION['user_id'], 'Updated asset status to unserviceable', 'assets', 
                                      'asset_ids: ' . implode(',', $assets_to_update) . ', form_id: ' . $form_id);
                    }
            }
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

// Helper function to get asset ID by property number
function getAssetIdByPropertyNo($property_no) {
    global $conn;
    $property_no = trim($property_no);
    
    if (empty($property_no)) {
        return null;
    }
    
    error_log("Looking up asset by property number: $property_no");
    
    $stmt = $conn->prepare("SELECT id FROM asset_items WHERE property_no = ? LIMIT 1");
    $stmt->bind_param("s", $property_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        error_log("Found asset ID: " . $row['id'] . " for property number: $property_no");
        return (int)$row['id'];
    }
    
    error_log("No asset found for property number: $property_no");
    return null;
}

// Helper function to extract asset ID from description
function extractAssetIdFromDescription($description) {
    // Try to extract property number from description
    if (preg_match('/Property\s*No\s*:\s*([A-Za-z0-9-]+)/i', $description, $matches)) {
        $property_no = $matches[1];
        error_log("Extracted property number: $property_no from: $description");
        
        // Look up the asset ID by property number
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM asset_items WHERE property_no = ? LIMIT 1");
        $stmt->bind_param("s", $property_no);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            error_log("Found asset ID: " . $row['id'] . " for property number: $property_no");
            return (int)$row['id'];
        }
    }
    
    // If description starts with property number pattern (like "PROP-001")
    if (preg_match('/^([A-Za-z0-9-]+)/', $description, $matches)) {
        $property_no = $matches[1];
        error_log("Trying property number pattern: $property_no from: $description");
        
        // Look up the asset ID by property number
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM asset_items WHERE property_no = ? LIMIT 1");
        $stmt->bind_param("s", $property_no);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            error_log("Found asset ID: " . $row['id'] . " for property number: $property_no");
            return (int)$row['id'];
        }
    }
    
    // Fallback to ID if no property number found
    if (preg_match('/ID:\s*(\d+)/i', $description, $matches)) {
        $asset_id = (int)$matches[1];
        error_log("Extracted asset ID: $asset_id from: $description");
        return $asset_id;
    }
    
    // If description starts with a number, assume it's ID
    if (preg_match('/^(\d+)/', $description, $matches)) {
        $asset_id = (int)$matches[1];
        error_log("Extracted numeric ID: $asset_id from: $description");
        return $asset_id;
    }
    
    error_log("Could not extract asset ID from: $description");
    return null;
}

$conn->close();
?>
