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

require_once 'includes/check_permissions.php';
adminRequirePermission('forms.create', 'can_create', 'ics_form.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $entity_name = $_POST['entity_name'];
        $fund_cluster = $_POST['fund_cluster'];
        $ics_no = $_POST['ics_no'];
        $received_from = $_POST['received_from'] ?? '';
        $received_from_position = $_POST['received_from_position'] ?? '';
        $received_from_date = !empty($_POST['received_from_date']) ? $_POST['received_from_date'] : null;
        $received_by = $_POST['received_by'] ?? '';
        $received_by_position = $_POST['received_by_position'] ?? '';
        $received_by_date = !empty($_POST['received_by_date']) ? $_POST['received_by_date'] : null;
        $items = $_POST['item_no'] ?? [];
        $property_numbers = $_POST['item_no'] ?? [];
        $category_codes = $_POST['category_code'] ?? [];
        $subcategory_codes = $_POST['subcategory_code'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $unit_costs = $_POST['unit_cost'] ?? [];
        $total_costs = $_POST['total_cost'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $useful_lives = $_POST['useful_life'] ?? [];
        $dates_acquired = $_POST['date_acquired'] ?? [];
        $asset_ids = []; // Track created asset IDs for notifications
        
        $date_col_check = $conn->query("SHOW COLUMNS FROM ics_items LIKE 'date_acquired'");
        if ($date_col_check && $date_col_check->num_rows === 0) {
            $conn->query("ALTER TABLE ics_items ADD COLUMN date_acquired DATE DEFAULT NULL AFTER item_no");
        }
        
        // Validate required fields (Removed)
        // if (empty($entity_name) || empty($fund_cluster)) {
        //     throw new Exception('All required fields must be filled');
        // }
        
        // Auto-generate ICS number to prevent duplicates
        $generated_ics_no = generateNextTag('ics_no');
        if ($generated_ics_no !== null) {
            $ics_no = $generated_ics_no;
            logSystemAction($_SESSION['user_id'], 'ICS number auto-generated', 'forms', "Generated ICS number: $ics_no");
        } else {
            // Fallback: generate simple ICS number with auto-increment
            $current_year = date('Y');
            $result = $conn->query("SELECT MAX(CAST(SUBSTRING(ics_no, -2, 2) AS UNSIGNED)) as max_series FROM ics_forms WHERE ics_no LIKE '%$current_year%' AND ics_no REGEXP '-[0-9]{2}$'");
            $next_series = '01';
            if ($result && $row = $result->fetch_assoc()) {
                $max_series = $row['max_series'];
                if ($max_series) {
                    $next_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
                }
            }
            $ics_no = "OMMI-$current_year-I-$next_series";
            logSystemAction($_SESSION['user_id'], 'ICS number generated (fallback)', 'forms', "Generated ICS number: $ics_no");
        }
        
        // Get office ID from entity name
        $office_id = null;
        $office_result = $conn->prepare("SELECT id FROM offices WHERE office_name = ?");
        $office_result->bind_param("s", $entity_name);
        $office_result->execute();
        $office_row = $office_result->get_result();
        if ($office_row && $office_row->num_rows > 0) {
            $office_data = $office_row->fetch_assoc();
            $office_id = $office_data['id'];
        }
        $office_result->close();
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert ICS form
        $stmt = $conn->prepare("INSERT INTO ics_forms (entity_name, fund_cluster, ics_no, received_from, received_from_position, received_from_date, received_by, received_by_position, received_by_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssi", $entity_name, $fund_cluster, $ics_no, $received_from, $received_from_position, $received_from_date, $received_by, $received_by_position, $received_by_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save ICS form: ' . $stmt->error);
        }
        
        $ics_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert ICS items
        $item_stmt = $conn->prepare("INSERT INTO ics_items (form_id, ics_id, item_no, quantity, unit, unit_cost, total_cost, description, useful_life, date_acquired) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($items); $i++) {
            if (!empty($items[$i]) && !empty($descriptions[$i])) {
                $quantity = floatval($quantities[$i]);
                $unit_cost = floatval($unit_costs[$i]);
                $total_cost = floatval($total_costs[$i]);
                $useful_life = intval($useful_lives[$i]);
                $date_acquired = !empty($dates_acquired[$i]) ? $dates_acquired[$i] : null;
                $acquisition_date = $date_acquired ?? date('Y-m-d');
                
                $item_stmt->bind_param("iisdsddsis", $ics_form_id, $ics_form_id, $items[$i], $quantity, $units[$i], $unit_cost, $total_cost, $descriptions[$i], $useful_life, $date_acquired);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save ICS item: ' . $item_stmt->error);
                }
                
                // Parse property numbers - handle both single and multiple property numbers
                $individual_property_numbers = [];
                $base_property_number = null;
                if (!empty($items[$i])) {
                    // Check if it's a textarea with multiple property numbers (newline-separated)
                    if (strpos($items[$i], "\n") !== false) {
                        $individual_property_numbers = array_filter(array_map('trim', explode("\n", $items[$i])));
                    } else {
                        // Single property number - we'll need to generate sequential numbers for each item
                        $base_property_number = $items[$i];
                    }
                }

                // Get category and subcategory IDs from hidden fields
                $category_id = null;
                $subcategory_id = null;
                
                $category_code = $category_codes[$i] ?? null;
                $subcategory_code = $subcategory_codes[$i] ?? null;
                
                // Look up category ID
                if (!empty($category_code)) {
                    $category_stmt = $conn->prepare("SELECT id FROM asset_categories WHERE category_code = ?");
                    if ($category_stmt) {
                        $category_stmt->bind_param("s", $category_code);
                        $category_stmt->execute();
                        $category_result = $category_stmt->get_result();
                        if ($category_result && $category_result->num_rows > 0) $category_id = $category_result->fetch_assoc()['id'];
                        $category_stmt->close();
                    }
                }
                
                // Look up subcategory ID
                if (!empty($subcategory_code)) {
                    $subcategory_stmt = $conn->prepare("SELECT id FROM asset_sub_categories WHERE sub_category_code = ?");
                    if ($subcategory_stmt) {
                        $subcategory_stmt->bind_param("s", $subcategory_code);
                        $subcategory_stmt->execute();
                        $subcategory_result = $subcategory_stmt->get_result();
                        if ($subcategory_result && $subcategory_result->num_rows > 0) $subcategory_id = $subcategory_result->fetch_assoc()['id'];
                        $subcategory_stmt->close();
                    }
                }

                // Fallback: Parse property numbers to extract category IDs
                $prop_for_cat = !empty($individual_property_numbers) ? $individual_property_numbers[0] : $base_property_number;
                
                if (is_null($category_id) && is_null($subcategory_id) && !empty($prop_for_cat)) {
                    $parts = explode('-', $prop_for_cat);
                    if (count($parts) >= 4) {
                        $cat_code = $parts[2];
                        $subcat_code = strlen($parts[3]) >= 4 ? substr($parts[3], 0, 2) : null;

                        // Fetch category ID
                        $stmt_c = $conn->prepare("SELECT id FROM asset_categories WHERE category_code = ? LIMIT 1");
                        if ($stmt_c) {
                            $stmt_c->bind_param('s', $cat_code);
                            $stmt_c->execute();
                            $res_c = $stmt_c->get_result();
                            if ($row_c = $res_c->fetch_assoc()) $category_id = $row_c['id'];
                            $stmt_c->close();
                        }

                        // Fetch subcategory ID
                        if ($subcat_code && $category_id) {
                            $stmt_s = $conn->prepare("SELECT id FROM asset_sub_categories WHERE sub_category_code = ? AND asset_categories_id = ? LIMIT 1");
                            if ($stmt_s) {
                                $stmt_s->bind_param('si', $subcat_code, $category_id);
                                $stmt_s->execute();
                                $res_s = $stmt_s->get_result();
                                if ($row_s = $res_s->fetch_assoc()) $subcategory_id = $row_s['id'];
                                $stmt_s->close();
                            }
                        }
                    }
                }

                // Also insert as asset and asset item
                $asset_stmt = $conn->prepare("INSERT INTO assets (asset_categories_id, asset_subcategory_id, description, unit, quantity, unit_cost, office_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $asset_stmt->bind_param("iissidi", $category_id, $subcategory_id, $descriptions[$i], $units[$i], $quantity, $unit_cost, $office_id);
                
                if (!$asset_stmt->execute()) {
                    throw new Exception('Failed to save asset: ' . $asset_stmt->error);
                }
                
                $asset_id = $asset_stmt->insert_id;
                $asset_stmt->close();
                
                // Track asset ID for notifications
                $asset_ids[] = $asset_id;
                
                // Insert multiple asset items based on quantity
                $asset_item_stmt = $conn->prepare("INSERT INTO asset_items (asset_id, ics_id, description, unit, status, value, acquisition_date, office_id, created_at, last_updated) VALUES (?, ?, ?, ?, 'no_tag', ?, ?, ?, NOW(), NOW())");
                // Create individual asset items for each quantity
                for ($item_num = 1; $item_num <= $quantity; $item_num++) {
                    // Debug: Log the values being inserted
                    logSystemAction($_SESSION['user_id'], 'ICS Asset Item Insert Debug', 'assets', "Item: {$descriptions[$i]}, Unit: '{$units[$i]}', Unit Cost: {$unit_cost}, Office ID: {$office_id}");
                    
                    $asset_item_stmt->bind_param("iissdsi", $asset_id, $ics_form_id, $descriptions[$i], $units[$i], $unit_cost, $acquisition_date, $office_id);
                    
                    if (!$asset_item_stmt->execute()) {
                        throw new Exception('Failed to save asset item ' . $item_num . ': ' . $asset_item_stmt->error);
                    }
                    
                    // Get the asset_item_id for history logging
                    $asset_item_id = $asset_item_stmt->insert_id;
                    
                    // Log asset item creation in history
                    $ics_details = "Created via ICS form $ics_no - Entity: $entity_name, Item No: {$items[$i]}, Quantity: 1, Unit: {$units[$i]}, Unit Cost: ₱" . number_format($unit_cost, 2);
                    $history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'ICS Created', ?, ?, CURRENT_TIMESTAMP)";
                    $history_stmt = $conn->prepare($history_sql);
                    $history_stmt->bind_param("isi", $asset_item_id, $ics_details, $_SESSION['user_id']);
                    $history_stmt->execute();
                    $history_stmt->close();
                    
                    // Assign property number to this asset item
                    $item_property_number = null;
                    if (!empty($individual_property_numbers)) {
                        // Use pre-generated property numbers from textarea
                        if (isset($individual_property_numbers[$item_num - 1])) {
                            $item_property_number = $individual_property_numbers[$item_num - 1];
                        }
                    } elseif (!empty($base_property_number)) {
                        // Generate sequential property numbers from base
                        // Parse the base property number to extract components
                        if (preg_match('/^(.*-)(\d+)(-[^-]+)$/', $base_property_number, $matches)) {
                            $prefix = $matches[1];
                            $series = intval($matches[2]);
                            $suffix = $matches[3];
                            $item_property_number = $prefix . str_pad($series + $item_num - 1, 4, '0', STR_PAD_LEFT) . $suffix;
                        } else {
                            // Fallback: just append item number
                            $item_property_number = $base_property_number . '-' . str_pad($item_num, 2, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Update the asset item with property number, category, and subcategory
                    if (!empty($item_property_number) || !is_null($category_id) || !is_null($subcategory_id)) {
                        // Use category_id as fallbacks if parsing misses
                        $parsed_category_id = $category_id;
                        $parsed_subcategory_id = $subcategory_id;
                        
                        // Just use the ones we already extracted for the asset table! We don't need to re-parse.
                        
                        $update_stmt = $conn->prepare("UPDATE asset_items SET property_no = ?, category_id = ?, asset_subcategory_id = ? WHERE id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param("siii", $item_property_number, $parsed_category_id, $parsed_subcategory_id, $asset_item_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                }
                $asset_item_stmt->close();
            }
        }
        $item_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Created ICS form', 'forms', "ICS No: $ics_no, Entity: $entity_name");
        
        // Set success message
        $_SESSION['success_message'] = "ICS form saved successfully! ICS Number: $ics_no";
        
        // Create notifications for MAIN_USER for each asset created
        createMainUserNotificationsForICS($descriptions, $asset_ids);
        
        // Redirect back to form
        header('Location: ics_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log error
        error_log("Error processing ICS form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to create ICS form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error saving ICS form: " . $e->getMessage();
        
        // Redirect back to form
        header('Location: ics_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: ics_form.php');
    exit();
}

// Function to create notifications for MAIN_USER when ICS assets are created
function createMainUserNotificationsForICS($descriptions, $asset_ids) {
    global $conn;
    
    // Get all MAIN_USER users
    $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND is_active = 1";
    $main_users_result = $conn->query($main_users_query);
    
    if ($main_users_result && $main_users_result->num_rows > 0) {
        while ($main_user = $main_users_result->fetch_assoc()) {
            $user_id = $main_user['id'];
            
            // Create notification for each asset
            foreach ($descriptions as $index => $description) {
                if (!empty($description) && isset($asset_ids[$index])) {
                    $asset_id = $asset_ids[$index];
                    $title = "New Asset Added via ICS";
                    $message = "A new asset '{$description}' has been added to the system via ICS form.";
                    $type = "success";
                    $related_id = $asset_id;
                    $related_type = "asset";
                    
                    // Insert notification
                    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
                    $stmt->execute();
                }
            }
        }
    }
}
?>
