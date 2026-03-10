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

// Function to parse property number and extract category/subcategory codes
function parsePropertyNumber($property_number) {
    $parsed = [
        'category_code' => null,
        'subcategory_code' => null
    ];
    
    // Split property number by '-'
    $parts = explode('-', $property_number);
    
    // Expected format: YEAR-FORM-FUND-CATEGORY-SUBCATEGORY-SERIES
    // Positions: 0=year, 1=form, 2=fund, 3=category, 4=subcategory, 5=series
    if (count($parts) >= 6) {
        $parsed['category_code'] = $parts[3];      // Category code (e.g., 030)
        $parsed['subcategory_code'] = $parts[4];   // Subcategory code (e.g., 0307)
    }
    
    return $parsed;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $entity_name = $_POST['entity_name'];
        $fund_cluster = $_POST['fund_cluster'];
        $par_no = $_POST['par_no'];
        $office_location = $_POST['office_location'] ?? '';
        $received_by = $_POST['received_by'];
        $received_by_position = $_POST['received_by_position'] ?? '';
        $received_by_date = $_POST['received_by_date'] ?? null;
        $issued_by = $_POST['issued_by'];
        $issued_by_position = $_POST['issued_by_position'] ?? '';
        $issued_by_date = $_POST['issued_by_date'] ?? null;
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $property_numbers = $_POST['property_number'] ?? [];
        $dates_acquired = $_POST['date_acquired'] ?? [];
        $amounts = $_POST['amount'] ?? [];
        $asset_ids = []; // Track created asset IDs for notifications
        
        // Validate required fields
        if (empty($par_no) || empty($office_location) || empty($received_by) || empty($issued_by)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Validate amount fields - each item must be above 50,000
        foreach ($amounts as $index => $amount) {
            if (!empty($amount) && floatval($amount) <= 50000) {
                throw new Exception("Item " . ($index + 1) . ": Amount must be above ₱50,000. Current amount: ₱" . number_format(floatval($amount), 2));
            }
        }
        
        // Check if we should increment the PAR counter
        if (isset($_POST['increment_par_counter']) && $_POST['increment_par_counter'] == '1') {
            // Generate the actual PAR number (this increments the counter)
            $generated_par_no = generateNextTag('par_no');
            if ($generated_par_no !== null) {
                $par_no = $generated_par_no;
                logSystemAction($_SESSION['user_id'], 'PAR counter incremented', 'forms', "Generated PAR number: $par_no");
            }
        }
        
        // Get office ID from office location (form sends office_code)
        $office_id = null;
        $office_result = $conn->prepare("SELECT id FROM offices WHERE office_code = ?");
        $office_result->bind_param("s", $office_location);
        $office_result->execute();
        $office_row = $office_result->get_result();
        if ($office_row && $office_row->num_rows > 0) {
            $office_data = $office_row->fetch_assoc();
            $office_id = $office_data['id'];
        }
        $office_result->close();
        
        // Validate office_id
        if (!$office_id) {
            throw new Exception("Invalid office location: '$office_location'. Please select a valid office.");
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert PAR form
        $stmt = $conn->prepare("INSERT INTO par_forms (entity_name, fund_cluster, par_no, office_location, received_by_name, received_by_position, received_by_date, issued_by_name, issued_by_position, issued_by_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssii", $entity_name, $fund_cluster, $par_no, $office_location, $received_by, $received_by_position, $received_by_date, $issued_by, $issued_by_position, $issued_by_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save PAR form: ' . $stmt->error);
        }
        
        $par_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert PAR items
        $item_stmt = $conn->prepare("INSERT INTO par_items (form_id, asset_id, quantity, unit, description, property_number, date_acquired, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($descriptions); $i++) {
            if (!empty($descriptions[$i])) {
                $quantity = floatval($quantities[$i]);
                $property_number = $property_numbers[$i] ?? null;
                $date_acquired = !empty($dates_acquired[$i]) ? $dates_acquired[$i] : null;
                $amount = floatval($amounts[$i]);
                $unit_cost = $quantity > 0 ? $amount / $quantity : 0;
                
                // Check for property number duplication if property number is provided
                if (!empty($property_number)) {
                    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM par_items WHERE property_number = ? AND form_id != ?");
                    $check_stmt->bind_param("si", $property_number, $par_form_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $count = $check_result->fetch_assoc()['count'];
                    $check_stmt->close();
                    
                    if ($count > 0) {
                        throw new Exception("Property number '$property_number' already exists in the system. Please use a different property number.");
                    }
                }
                
                // Also insert as asset and asset item first to get asset_id
                $asset_stmt = $conn->prepare("INSERT INTO assets (description, unit, quantity, unit_cost, office_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $asset_stmt->bind_param("ssidi", $descriptions[$i], $units[$i], $quantity, $unit_cost, $office_id);
                
                if (!$asset_stmt->execute()) {
                    throw new Exception('Failed to save asset: ' . $asset_stmt->error);
                }
                
                $asset_id = $asset_stmt->insert_id;
                $asset_stmt->close();
                
                // Track asset ID for notifications
                $asset_ids[] = $asset_id;
                
                // Now insert PAR item with the correct asset_id
                $item_stmt->bind_param("iidssdsd", $par_form_id, $asset_id, $quantity, $units[$i], $descriptions[$i], $property_number, $date_acquired, $amount);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save PAR item: ' . $item_stmt->error);
                }
                
                // Parse property numbers - handle both single and multiple property numbers
                $individual_property_numbers = [];
                if (!empty($property_number)) {
                    // Check if it's a textarea with multiple property numbers (newline-separated)
                    if (strpos($property_number, "\n") !== false) {
                        $individual_property_numbers = array_filter(array_map('trim', explode("\n", $property_number)));
                    } else {
                        // Single property number - we'll need to generate sequential numbers for each item
                        $base_property_number = $property_number;
                    }
                }
                
                // Insert multiple asset items based on quantity
                for ($item_num = 1; $item_num <= $quantity; $item_num++) {
                    $description = $conn->real_escape_string($descriptions[$i]);
                    $unit = $conn->real_escape_string($units[$i]);
                    $status = 'no_tag';
                    $acquisition_date_sql = !empty($date_acquired) ? "'$date_acquired'" : 'NULL';
                    // Use the exact total amount for each individual item (not divided)
                    $value_sql = !empty($amount) ? $amount : 'NULL';
                    
                    $sql = "INSERT INTO asset_items (asset_id, par_id, description, unit, status, value, acquisition_date, office_id, created_at, last_updated) 
                           VALUES ($asset_id, $par_form_id, '$description', '$unit', '$status', $value_sql, $acquisition_date_sql, $office_id, NOW(), NOW())";
                    
                    if (!$conn->query($sql)) {
                        throw new Exception('Failed to save asset item ' . $item_num . ': ' . $conn->error);
                    }
                    
                    // Get the asset_item_id for property number assignment
                    $asset_item_id = $conn->insert_id;
                    
                    // Log asset item creation in history
                    $par_details = "Created via PAR form $par_no - Entity: $entity_name, Quantity: $quantity, Unit: {$units[$i]}, Amount: ₱" . number_format($amount, 2);
                    $history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'PAR Created', ?, ?, CURRENT_TIMESTAMP)";
                    $history_stmt = $conn->prepare($history_sql);
                    $history_stmt->bind_param("isi", $asset_item_id, $par_details, $_SESSION['user_id']);
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
                    
                    // Update the asset item with property number if assigned
                    if (!empty($item_property_number)) {
                        // Parse property number to extract category and subcategory codes
                        $parsed_data = parsePropertyNumber($item_property_number);
                        
                        // Look up category and subcategory IDs
                        $category_id = null;
                        $subcategory_id = null;
                        
                        if (!empty($parsed_data['category_code'])) {
                            $category_stmt = $conn->prepare("SELECT id FROM asset_categories WHERE category_code = ?");
                            $category_stmt->bind_param("s", $parsed_data['category_code']);
                            $category_stmt->execute();
                            $category_result = $category_stmt->get_result();
                            if ($category_result && $category_result->num_rows > 0) {
                                $category_id = $category_result->fetch_assoc()['id'];
                            }
                            $category_stmt->close();
                        }
                        
                        if (!empty($parsed_data['subcategory_code'])) {
                            $subcategory_stmt = $conn->prepare("SELECT id FROM asset_sub_categories WHERE sub_category_code = ?");
                            $subcategory_stmt->bind_param("s", $parsed_data['subcategory_code']);
                            $subcategory_stmt->execute();
                            $subcategory_result = $subcategory_stmt->get_result();
                            if ($subcategory_result && $subcategory_result->num_rows > 0) {
                                $subcategory_id = $subcategory_result->fetch_assoc()['id'];
                            }
                            $subcategory_stmt->close();
                        }
                        
                        // Update the asset item with property number, category, and subcategory
                        $update_stmt = $conn->prepare("UPDATE asset_items SET property_no = ?, category_id = ?, asset_subcategory_id = ? WHERE id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param("siii", $item_property_number, $category_id, $subcategory_id, $asset_item_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                }
            }
        }
        $item_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Update property number counter if property numbers were used
        $has_property_numbers = false;
        foreach ($property_numbers as $property_number) {
            if (!empty($property_number)) {
                $has_property_numbers = true;
                break;
            }
        }
        
        if ($has_property_numbers) {
            // Update the property number counter
            $update_counter = $conn->prepare("UPDATE tag_formats SET current_number = current_number + 1 WHERE tag_type = 'property_no' AND status = 'active'");
            if ($update_counter) {
                if (!$update_counter->execute()) {
                    error_log("Failed to update property number counter: " . $update_counter->error);
                    // Don't throw exception here, just log the error as it's not critical
                }
                $update_counter->close();
            } else {
                error_log("Failed to prepare property number counter update: " . $conn->error);
            }
        }
        
        // Log the action
        logSystemAction($_SESSION['user_id'], 'Created PAR form', 'forms', "PAR No: $par_no, Entity: $entity_name");
        
        // Set success message
        $_SESSION['success_message'] = "PAR form saved successfully! PAR Number: $par_no";
        
        // Create notifications for MAIN_USER for each asset created
        createMainUserNotificationsForPAR($descriptions, $asset_ids);
        
        // Redirect back to the form
        header('Location: par_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log error
        error_log("Error processing PAR form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to create PAR form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error saving PAR form: " . $e->getMessage();
        
        // Redirect back to the form
        header('Location: par_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: par_form.php');
    exit();
}

// Function to create notifications for MAIN_USER when PAR assets are created
function createMainUserNotificationsForPAR($descriptions, $asset_ids) {
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
                    $title = "New Asset Added via PAR";
                    $message = "A new asset '{$description}' has been added to the system via PAR form.";
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
