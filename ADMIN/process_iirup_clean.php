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
    // Debug: Log all POST data
    error_log("IIRUP POST data received: " . print_r($_POST, true));
    
    // Debug: Check specifically for component_type data
    if (isset($_POST['component_type'])) {
        error_log("IIRUP DEBUG: component_type POST data: " . print_r($_POST['component_type'], true));
    } else {
        error_log("IIRUP DEBUG: No component_type POST data found");
    }
    
    // Simple test: Check if we can update a peripheral directly
    error_log("=== SIMPLE PERIPHERAL TEST ===");
    $test_result = $conn->query("SELECT id, status FROM peripherals LIMIT 1");
    if ($test_result && $test_result->num_rows > 0) {
        $test_row = $test_result->fetch_assoc();
        $test_id = $test_row['id'];
        $test_status = $test_row['status'];
        error_log("Found test peripheral: ID=$test_id, Status=$test_status");
        
        $test_update = $conn->query("UPDATE peripherals SET status = 'unserviceable' WHERE id = $test_id");
        if ($test_update) {
            $test_affected = $conn->affected_rows;
            error_log("Test update result: Affected rows = $test_affected");
            
            // Reset to original status
            $reset_update = $conn->query("UPDATE peripherals SET status = '$test_status' WHERE id = $test_id");
            error_log("Reset peripheral to original status: $test_status");
        } else {
            error_log("Test update failed: " . $conn->error);
        }
    } else {
        error_log("No peripherals found for simple test");
    }
    error_log("=== END SIMPLE TEST ===");
    
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
        error_log("IIRUP DEBUG: All particulars submitted: " . print_r($particulars, true));
        $dates_acquired = $_POST['date_acquired'];
        $property_nos = $_POST['property_no'];
        error_log("IIRUP DEBUG: All property numbers submitted: " . print_r($property_nos, true));
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
        $component_updates = []; // Track component-specific updates
        $peripheral_update_count = 0; // Track peripheral updates
        
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
                
                // Extract asset ID and component type from the data
                $asset_id = null;
                $component_type = null;
                $property_no = $property_nos[$index] ?? '';
                
                // Check if this item came from auto-fill with component_type parameter
                $component_type_from_auto_fill = null;
                if (isset($_POST['component_type']) && is_array($_POST['component_type'])) {
                    $component_type_from_auto_fill = $_POST['component_type'][$index] ?? null;
                    error_log("IIRUP DEBUG: Component type from auto-fill: " . ($component_type_from_auto_fill ?: 'none'));
                }
                
                // Try to extract component type from description or detect from auto-fill data
                $component_type = null;
                error_log("IIRUP DEBUG: Analyzing particular: '$particular' for component type");
                
                // First check if component_type is explicitly set to 'peripheral' from auto-fill
                if ($component_type_from_auto_fill === 'peripheral') {
                    $component_type = 'peripheral';
                    error_log("IIRUP DEBUG: Detected peripheral from component_type parameter: '$particular'");
                }
                // Check for monitor patterns (case insensitive)
                elseif (preg_match('/monitor/i', $particular) || 
                    strpos($particular, 'Monitor - ') === 0 || 
                    strpos($particular, 'MONITOR - ') === 0 ||
                    preg_match('/^Monitor\s*-\s*/i', $particular)) {
                    $component_type = 'monitor';
                    error_log("IIRUP DEBUG: Detected monitor component from description: '$particular'");
                } 
                // Check for UPS patterns (case insensitive)
                elseif (preg_match('/ups/i', $particular) || 
                         strpos($particular, 'UPS - ') === 0 || 
                         strpos($particular, 'UPS-') === 0 ||
                         preg_match('/^UPS\s*-\s*/i', $particular)) {
                    $component_type = 'ups';
                    error_log("IIRUP DEBUG: Detected UPS component from description: '$particular'");
                } 
                // Check for other common component patterns
                elseif (preg_match('/keyboard|mouse|speaker|printer|scanner|webcam|microphone|headphone|camera/i', $particular)) {
                    $component_type = 'other';
                    error_log("IIRUP DEBUG: Detected other component from description: '$particular'");
                }
                else {
                    error_log("IIRUP DEBUG: No component type detected from description: '$particular' (treating as main asset)");
                }
                
                error_log("IIRUP DEBUG: Final component type determined: " . ($component_type ?: 'main_asset'));
                
                // First try to find asset by property number from the dedicated field
                if (!empty($property_no)) {
                    error_log("Trying to find asset by property number: '$property_no'");
                    $asset_id = getAssetIdByPropertyNo($property_no);
                    error_log("Asset ID from property number: " . ($asset_id ?: 'none'));
                }
                
                // If not found, try to extract from particulars description
                if (!$asset_id && !empty($particular)) {
                    error_log("Trying to extract asset ID from description: '$particular'");
                    $asset_id = extractAssetIdFromDescription($particular);
                    error_log("Asset ID from description: " . ($asset_id ?: 'none'));
                }
                
                // Debug logging
                error_log("IIRUP Processing - Particular: '$particular', Component Type: " . ($component_type ?: 'none') . ", Asset ID: " . ($asset_id ?: 'none'));
                
                if ($asset_id) {
                    if ($component_type) {
                        // This is a component, only add to component updates, not main asset updates
                        $component_updates[] = [
                            'asset_id' => $asset_id,
                            'component_type' => $component_type
                        ];
                        error_log("Added to component updates: Asset ID $asset_id, Type: $component_type");
                    } else {
                        // This is a main asset, add to main asset updates
                        $asset_ids_to_update[] = $asset_id;
                        error_log("Added to main asset updates: Asset ID $asset_id");
                    }
                } else {
                    error_log("No asset_id found for particular: '$particular'");
                }
            }
        }
        
        error_log("IIRUP Processing Summary - Main assets to update: " . count($asset_ids_to_update) . ", Component updates: " . count($component_updates));
        error_log("Main asset IDs: " . implode(', ', $asset_ids_to_update));
        
        // Update asset items status to unserviceable
        error_log("About to process status updates. Main assets: " . count($asset_ids_to_update) . ", Components: " . count($component_updates));
        
        // Process component-specific updates first (if any)
        if (!empty($component_updates)) {
            error_log("Processing component updates only");
            foreach ($component_updates as $component) {
                $asset_id = $component['asset_id'];
                $component_type = $component['component_type'];
                
                error_log("Processing component: Asset ID $asset_id, Type: $component_type");
                
                if ($component_type === 'monitor') {
                    // First check if peripheral record exists
                    $check_sql = "SELECT id, status, name FROM peripherals WHERE asset_item_id = $asset_id";
                    error_log("Monitor check SQL: $check_sql");
                    $check_result = $conn->query($check_sql);
                    
                    if ($check_result && $check_result->num_rows > 0) {
                        error_log("Found " . $check_result->num_rows . " peripheral records for asset_id: $asset_id");
                        $monitor_found = false;
                        while ($row = $check_result->fetch_assoc()) {
                            error_log("Peripheral record - ID: " . $row['id'] . ", Name: '" . $row['name'] . "', Status: " . $row['status']);
                            if (stripos($row['name'], 'Monitor') !== false || stripos($row['name'], 'monitor') !== false) {
                                $monitor_found = true;
                                $current_status = $row['status'];
                                error_log("Found monitor peripheral record. Current status: $current_status");
                                
                                // Update monitor status in peripherals table
                                $monitor_sql = "UPDATE peripherals SET status = 'unserviceable' 
                                              WHERE id = " . $row['id'];
                                error_log("Executing monitor update SQL: $monitor_sql");
                                
                                $result = $conn->query($monitor_sql);
                                if ($result) {
                                    $affected_rows = $conn->affected_rows;
                                    error_log("Monitor status update executed. Affected rows: $affected_rows");
                                    if ($affected_rows > 0) {
                                        error_log("✓ Monitor status successfully updated to unserviceable for peripheral ID: " . $row['id']);
                                        $peripheral_update_count++;
                                    } else {
                                        error_log("⚠ Monitor status update: No rows affected (may already be unserviceable)");
                                    }
                                } else {
                                    error_log("✗ Monitor status update failed: " . $conn->error);
                                }
                            }
                        }
                        
                        if (!$monitor_found) {
                            error_log("✗ No monitor peripheral found among " . $check_result->num_rows . " records for asset_id: $asset_id");
                        }
                    } else {
                        error_log("✗ No peripheral records found for asset_id: $asset_id");
                    }
                    
                } elseif ($component_type === 'ups') {
                    // First check if peripheral record exists
                    $check_sql = "SELECT id, status, name FROM peripherals WHERE asset_item_id = $asset_id";
                    error_log("UPS check SQL: $check_sql");
                    $check_result = $conn->query($check_sql);
                    
                    if ($check_result && $check_result->num_rows > 0) {
                        error_log("Found " . $check_result->num_rows . " peripheral records for asset_id: $asset_id");
                        $ups_found = false;
                        while ($row = $check_result->fetch_assoc()) {
                            error_log("Peripheral record - ID: " . $row['id'] . ", Name: '" . $row['name'] . "', Status: " . $row['status']);
                            if (stripos($row['name'], 'UPS') !== false || stripos($row['name'], 'ups') !== false) {
                                $ups_found = true;
                                $current_status = $row['status'];
                                error_log("Found UPS peripheral record. Current status: $current_status");
                                
                                // Update UPS status in peripherals table
                                $ups_sql = "UPDATE peripherals SET status = 'unserviceable' 
                                          WHERE id = " . $row['id'];
                                error_log("Executing UPS update SQL: $ups_sql");
                                
                                $result = $conn->query($ups_sql);
                                if ($result) {
                                    $affected_rows = $conn->affected_rows;
                                    error_log("UPS status update executed. Affected rows: $affected_rows");
                                    if ($affected_rows > 0) {
                                        error_log("✓ UPS status successfully updated to unserviceable for peripheral ID: " . $row['id']);
                                        $peripheral_update_count++;
                                    } else {
                                        error_log("⚠ UPS status update: No rows affected (may already be unserviceable)");
                                    }
                                } else {
                                    error_log("✗ UPS status update failed: " . $conn->error);
                                }
                            }
                        }
                        
                        if (!$ups_found) {
                            error_log("✗ No UPS peripheral found among " . $check_result->num_rows . " records for asset_id: $asset_id");
                        }
                    } else {
                        error_log("✗ No peripheral records found for asset_id: $asset_id");
                    }
                } elseif ($component_type === 'other') {
                    // Handle other peripheral types (keyboard, mouse, etc.)
                    $check_sql = "SELECT id, status, name FROM peripherals WHERE asset_item_id = $asset_id";
                    error_log("Other peripheral check SQL: $check_sql");
                    $check_result = $conn->query($check_sql);
                    
                    if ($check_result && $check_result->num_rows > 0) {
                        error_log("Found " . $check_result->num_rows . " peripheral records for asset_id: $asset_id");
                        
                        // Get the particular to determine the specific peripheral type
                        $particular_for_component = '';
                        foreach ($particulars as $idx => $part) {
                            if (!empty($part)) {
                                $comp_property_no = $property_nos[$idx] ?? '';
                                $comp_asset_id = null;
                                if (!empty($comp_property_no)) {
                                    $comp_asset_id = getAssetIdByPropertyNo($comp_property_no);
                                }
                                if ($comp_asset_id == $asset_id) {
                                    $particular_for_component = $part;
                                    break;
                                }
                            }
                        }
                        
                        $peripheral_updated = false;
                        while ($row = $check_result->fetch_assoc()) {
                            error_log("Peripheral record - ID: " . $row['id'] . ", Name: '" . $row['name'] . "', Status: " . $row['status']);
                            
                            // Check if this peripheral matches the particular description
                            $matched = false;
                            if (preg_match('/keyboard/i', $particular_for_component) && stripos($row['name'], 'keyboard') !== false) {
                                $matched = true;
                            } elseif (preg_match('/mouse/i', $particular_for_component) && stripos($row['name'], 'mouse') !== false) {
                                $matched = true;
                            } elseif (preg_match('/printer/i', $particular_for_component) && stripos($row['name'], 'printer') !== false) {
                                $matched = true;
                            } elseif (preg_match('/scanner/i', $particular_for_component) && stripos($row['name'], 'scanner') !== false) {
                                $matched = true;
                            }
                            
                            if ($matched) {
                                $current_status = $row['status'];
                                error_log("Found matching peripheral record. Current status: $current_status");
                                
                                // Update peripheral status
                                $update_sql = "UPDATE peripherals SET status = 'unserviceable' WHERE id = " . $row['id'];
                                error_log("Executing peripheral update SQL: $update_sql");
                                
                                $result = $conn->query($update_sql);
                                if ($result) {
                                    $affected_rows = $conn->affected_rows;
                                    error_log("Peripheral status update executed. Affected rows: $affected_rows");
                                    if ($affected_rows > 0) {
                                        error_log("✓ Peripheral status successfully updated to unserviceable for peripheral ID: " . $row['id']);
                                        $peripheral_update_count++;
                                        $peripheral_updated = true;
                                    } else {
                                        error_log("⚠ Peripheral status update: No rows affected (may already be unserviceable)");
                                    }
                                } else {
                                    error_log("✗ Peripheral status update failed: " . $conn->error);
                                }
                            }
                        }
                        
                        if (!$peripheral_updated) {
                            error_log("✗ No matching peripheral found for asset_id: $asset_id, particular: '$particular_for_component'");
                        }
                    } else {
                        error_log("✗ No peripheral records found for asset_id: $asset_id");
                    }
                } elseif ($component_type === 'peripheral') {
                    // Handle generic peripheral type from auto-fill
                    error_log("Processing generic peripheral for asset_id: $asset_id");
                    
                    // Update ALL peripherals for this asset to unserviceable
                    $update_sql = "UPDATE peripherals SET status = 'unserviceable', updated_at = NOW() 
                                 WHERE asset_item_id = $asset_id";
                    error_log("Executing generic peripheral update SQL: $update_sql");
                    
                    $result = $conn->query($update_sql);
                    if ($result) {
                        $affected_rows = $conn->affected_rows;
                        error_log("Generic peripheral update executed. Affected rows: $affected_rows");
                        if ($affected_rows > 0) {
                            error_log("✓ Successfully updated $affected_rows peripheral(s) to unserviceable for asset ID $asset_id");
                            $peripheral_update_count += $affected_rows;
                        } else {
                            error_log("⚠ Generic peripheral update: No rows affected (may already be unserviceable or no peripherals found)");
                        }
                    } else {
                        error_log("✗ Generic peripheral update failed: " . $conn->error);
                    }
                }
            }
        }
        
        // Also process ALL form items for peripheral updates (not just detected components)
        error_log("=== PERIPHERAL SCAN START ===");
        error_log("Processing ALL form items for potential peripheral updates");
        error_log("Total items to scan: " . count($particulars));
        foreach ($particulars as $index => $particular) {
            if (empty($particular)) continue;
            
            $property_no = $property_nos[$index] ?? '';
            error_log("PERIPHERAL SCAN: Processing item $index - '$particular' (Property No: '$property_no')");
            
            // Check if this is a peripheral component (comprehensive check)
            $is_peripheral = false;
            $peripheral_type = null;
            
            // Check if component_type is explicitly set to 'peripheral' from auto-fill
            $component_type_from_auto_fill = null;
            if (isset($_POST['component_type']) && is_array($_POST['component_type'])) {
                $component_type_from_auto_fill = $_POST['component_type'][$index] ?? null;
                if ($component_type_from_auto_fill === 'peripheral') {
                    $is_peripheral = true;
                    $peripheral_type = 'peripheral';
                    error_log("PERIPHERAL SCAN: Detected peripheral from component_type parameter");
                }
            }
            
            // Check for monitor patterns (case insensitive)
            if (!$is_peripheral && (preg_match('/monitor/i', $particular) || 
                strpos($particular, 'Monitor - ') === 0 || 
                strpos($particular, 'MONITOR - ') === 0 ||
                preg_match('/^Monitor\s*-\s*/i', $particular))) {
                $is_peripheral = true;
                $peripheral_type = 'monitor';
                error_log("PERIPHERAL SCAN: Detected monitor component");
            }
            // Check for UPS patterns (case insensitive)
            elseif (!$is_peripheral && (preg_match('/ups/i', $particular) || 
                     strpos($particular, 'UPS - ') === 0 || 
                     strpos($particular, 'UPS-') === 0 ||
                     preg_match('/^UPS\s*-\s*/i', $particular))) {
                $is_peripheral = true;
                $peripheral_type = 'ups';
                error_log("PERIPHERAL SCAN: Detected UPS component");
            }
            // Check for other peripherals
            elseif (!$is_peripheral && preg_match('/keyboard|mouse|speaker|printer|scanner|webcam|microphone|headphone|camera/i', $particular)) {
                $is_peripheral = true;
                $peripheral_type = 'other';
                error_log("PERIPHERAL SCAN: Detected other peripheral component");
            }
            
            if ($is_peripheral) {
                // Find asset ID
                $asset_id = null;
                
                // Try property number first
                if (!empty($property_no)) {
                    $asset_id = getAssetIdByPropertyNo($property_no);
                    if ($asset_id) {
                        error_log("PERIPHERAL SCAN: Found asset ID $asset_id from property number '$property_no'");
                    }
                }
                
                // Try to extract from description if no property number
                if (!$asset_id) {
                    if (preg_match('/Property\s*No\s*:\s*([A-Za-z0-9-]+)/i', $particular, $matches)) {
                        $extracted_property_no = $matches[1];
                        $asset_id = getAssetIdByPropertyNo($extracted_property_no);
                        if ($asset_id) {
                            error_log("PERIPHERAL SCAN: Found asset ID $asset_id from extracted property number '$extracted_property_no'");
                        }
                    }
                }
                
                if ($asset_id) {
                    // Update peripherals table
                    $updated = false;
                    
                    if ($peripheral_type === 'monitor') {
                        // Update monitor peripherals
                        $update_sql = "UPDATE peripherals SET status = 'unserviceable', updated_at = NOW() 
                                     WHERE asset_item_id = $asset_id AND (name LIKE '%Monitor%' OR name LIKE '%monitor%')";
                        error_log("PERIPHERAL SCAN: Executing monitor update SQL: $update_sql");
                        
                        $result = $conn->query($update_sql);
                        if ($result) {
                            $affected = $conn->affected_rows;
                            if ($affected > 0) {
                                $peripheral_update_count += $affected;
                                $updated = true;
                                error_log("PERIPHERAL SCAN: Updated $affected monitor(s) to unserviceable for asset ID $asset_id");
                            } else {
                                error_log("PERIPHERAL SCAN: No monitor records found to update for asset ID $asset_id");
                            }
                        } else {
                            error_log("PERIPHERAL SCAN: Monitor update failed: " . $conn->error);
                        }
                    }
                    elseif ($peripheral_type === 'ups') {
                        // Update UPS peripherals
                        $update_sql = "UPDATE peripherals SET status = 'unserviceable', updated_at = NOW() 
                                     WHERE asset_item_id = $asset_id AND (name LIKE '%UPS%' OR name LIKE '%ups%')";
                        error_log("PERIPHERAL SCAN: Executing UPS update SQL: $update_sql");
                        
                        $result = $conn->query($update_sql);
                        if ($result) {
                            $affected = $conn->affected_rows;
                            if ($affected > 0) {
                                $peripheral_update_count += $affected;
                                $updated = true;
                                error_log("PERIPHERAL SCAN: Updated $affected UPS unit(s) to unserviceable for asset ID $asset_id");
                            } else {
                                error_log("PERIPHERAL SCAN: No UPS records found to update for asset ID $asset_id");
                            }
                        } else {
                            error_log("PERIPHERAL SCAN: UPS update failed: " . $conn->error);
                        }
                    }
                    elseif ($peripheral_type === 'other') {
                        // Update other peripherals based on keywords
                        $keywords = ['keyboard', 'mouse', 'speaker', 'printer', 'scanner', 'webcam', 'microphone', 'headphone', 'camera'];
                        foreach ($keywords as $keyword) {
                            if (preg_match("/$keyword/i", $particular)) {
                                $update_sql = "UPDATE peripherals SET status = 'unserviceable', updated_at = NOW() 
                                             WHERE asset_item_id = $asset_id AND (name LIKE '%$keyword%' OR name LIKE '%" . ucfirst($keyword) . "%')";
                                error_log("PERIPHERAL SCAN: Executing $keyword update SQL: $update_sql");
                                
                                $result = $conn->query($update_sql);
                                if ($result) {
                                    $affected = $conn->affected_rows;
                                    if ($affected > 0) {
                                        $peripheral_update_count += $affected;
                                        $updated = true;
                                        error_log("PERIPHERAL SCAN: Updated $affected $keyword(s) to unserviceable for asset ID $asset_id");
                                    }
                                } else {
                                    error_log("PERIPHERAL SCAN: $keyword update failed: " . $conn->error);
                                }
                            }
                        }
                    }
                    elseif ($peripheral_type === 'peripheral') {
                        // Handle generic peripheral type from auto-fill - update ALL peripherals for this asset
                        $update_sql = "UPDATE peripherals SET status = 'unserviceable', updated_at = NOW() 
                                     WHERE asset_item_id = $asset_id";
                        error_log("PERIPHERAL SCAN: Executing generic peripheral update SQL: $update_sql");
                        
                        $result = $conn->query($update_sql);
                        if ($result) {
                            $affected = $conn->affected_rows;
                            if ($affected > 0) {
                                $peripheral_update_count += $affected;
                                $updated = true;
                                error_log("PERIPHERAL SCAN: Updated $affected peripheral(s) to unserviceable for asset ID $asset_id (generic peripheral)");
                            } else {
                                error_log("PERIPHERAL SCAN: No peripheral records found to update for asset ID $asset_id (generic peripheral)");
                            }
                        } else {
                            error_log("PERIPHERAL SCAN: Generic peripheral update failed: " . $conn->error);
                        }
                    }
                    
                    if (!$updated) {
                        error_log("PERIPHERAL SCAN: No peripheral records found for: $particular (Asset ID: $asset_id, Type: $peripheral_type)");
                    }
                } else {
                    error_log("PERIPHERAL SCAN: Could not find asset ID for: $particular");
                }
            } else {
                error_log("PERIPHERAL SCAN: Skipping non-peripheral item: $particular");
            }
        }
        error_log("=== PERIPHERAL SCAN END ===");
        error_log("Total peripheral updates performed: $peripheral_update_count");
        
        // Process main asset updates only if there are main assets to update
        if (!empty($asset_ids_to_update)) {
            error_log("Processing main asset updates");
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
                
                // Update only main assets that can be changed to unserviceable
                if (!empty($assets_to_update)) {
                    error_log("Processing " . count($assets_to_update) . " main asset updates");
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
                    
                    // Commit asset updates immediately to ensure they're saved
                    $conn->commit();
                    error_log("Asset updates committed successfully");
                    
                    // Start new transaction for remaining operations
                    $conn->begin_transaction();
                } else {
                    error_log("No main asset IDs to process after filtering");
                }
            } else {
                error_log("No main asset IDs to process after filtering");
            }
        } else {
            error_log("No main assets to update - only components processed");
        }
        
        // Log the action
        logSystemAction($_SESSION['user_id'], 'Created IIRUP Form', 'forms', 'form_id: ' . $form_id . ', form_number: ' . $form_number);
        
        // Clear form data session storage to reset data restoration
        unset($_SESSION['iirup_form_data']);
        
        // Create comprehensive success message
        $success_message = "IIRUP Form '$form_number' has been created successfully!";
        if ($peripheral_update_count > 0) {
            $success_message .= " Updated $peripheral_update_count peripheral(s) to unserviceable status.";
        }
        
        $_SESSION['success'] = $success_message;
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
        error_log("getAssetIdByPropertyNo: Empty property number provided");
        return null;
    }
    
    error_log("getAssetIdByPropertyNo: Looking up asset by property number: '$property_no'");
    
    $stmt = $conn->prepare("SELECT id, property_no, status FROM asset_items WHERE property_no = ? LIMIT 1");
    $stmt->bind_param("s", $property_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        error_log("getAssetIdByPropertyNo: Found asset ID: " . $row['id'] . " for property number: '$property_no' (Status: " . $row['status'] . ")");
        return (int)$row['id'];
    }
    
    error_log("getAssetIdByPropertyNo: No asset found for property number: '$property_no'");
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
