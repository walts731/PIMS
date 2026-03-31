<?php
ob_start();
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "File upload failed with error code: " . $file['error'];
        $_SESSION['message_type'] = "danger";
        header('Location: consumables.php');
        exit();
    }
    
    // Check file extension
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'csv') {
        $_SESSION['message'] = "Please upload a valid CSV file.";
        $_SESSION['message_type'] = "danger";
        header('Location: consumables.php');
        exit();
    }
    
    // Open the file
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        // Get header row
        $header = fgetcsv($handle, 1000, ",");
        
        // Define expected columns (case insensitive mapping)
        $expected_cols = [
            'description' => -1,
            'quantity' => -1,
            'units' => -1,
            'unit_cost' => -1,
            'reorder_level' => -1,
            'office' => -1,
            'for_office' => -1
        ];
        
        foreach ($header as $index => $col) {
            $col_clean = strtolower(trim($col));
            if (strpos($col_clean, 'description') !== false) $expected_cols['description'] = $index;
            if (strpos($col_clean, 'quantity') !== false) $expected_cols['quantity'] = $index;
            if (strpos($col_clean, 'unit') !== false && strpos($col_clean, 'cost') === false) $expected_cols['units'] = $index;
            if (strpos($col_clean, 'cost') !== false) $expected_cols['unit_cost'] = $index;
            if (strpos($col_clean, 'reorder') !== false) $expected_cols['reorder_level'] = $index;
            if (strpos($col_clean, 'office') !== false && strpos($col_clean, 'for') === false) $expected_cols['office'] = $index;
            if (strpos($col_clean, 'for office') !== false || (strpos($col_clean, 'for') !== false && strpos($col_clean, 'office') !== false)) $expected_cols['for_office'] = $index;
        }
        
        // Basic validation
        if ($expected_cols['description'] === -1 || $expected_cols['quantity'] === -1) {
            $_SESSION['message'] = "CSV must at least contain 'Description' and 'Quantity' columns.";
            $_SESSION['message_type'] = "danger";
            fclose($handle);
            header('Location: consumables.php');
            exit();
        }
        
        // Load offices for mapping names to IDs
        $offices_map = [];
        $office_res = $conn->query("SELECT id, office_name FROM offices");
        while ($row = $office_res->fetch_assoc()) {
            $offices_map[strtolower(trim($row['office_name']))] = $row['id'];
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $description = trim($data[$expected_cols['description']] ?? '');
                if (empty($description)) continue;
                
                $quantity = intval($data[$expected_cols['quantity']] ?? 0);
                $units = $expected_cols['units'] !== -1 ? trim($data[$expected_cols['units']]) : 'pcs';
                $unit_cost = $expected_cols['unit_cost'] !== -1 ? floatval(str_replace(['$', '₱', ','], '', $data[$expected_cols['unit_cost']])) : 0.00;
                $reorder_level = $expected_cols['reorder_level'] !== -1 ? intval($data[$expected_cols['reorder_level']]) : 5;
                
                // Map Office
                $office_id = 3; // Default to Supply if not found
                if ($expected_cols['office'] !== -1) {
                    $office_name = strtolower(trim($data[$expected_cols['office']]));
                    if (isset($offices_map[$office_name])) {
                        $office_id = $offices_map[$office_name];
                    }
                }
                
                // Map For Office
                $for_office_id = 0; // Default to 0 (General) if not found
                if ($expected_cols['for_office'] !== -1) {
                    $for_office_name = strtolower(trim($data[$expected_cols['for_office']]));
                    if (isset($offices_map[$for_office_name])) {
                        $for_office_id = $offices_map[$for_office_name];
                    }
                }
                
                // Insert into database
                $sql = "INSERT INTO consumables (description, quantity, units, unit_cost, reorder_level, office_id, for_office_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisdi ii", $description, $quantity, $units, $unit_cost, $reorder_level, $office_id, $for_office_id);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "Row for '{$description}': " . $conn->error;
                }
                $stmt->close();
            }
            
            $conn->commit();
            logSystemAction($_SESSION['user_id'], 'consumables_imported', 'consumables', "Imported {$success_count} consumables from CSV.");
            
            $_SESSION['message'] = "Import completed! Successfully added: {$success_count}, Failed: {$error_count}.";
            $_SESSION['message_type'] = $error_count > 0 ? "warning" : "success";
            if ($error_count > 0) {
                $_SESSION['import_errors'] = array_slice($errors, 0, 5); // Store first 5 errors
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "A database error occurred: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
        
        fclose($handle);
    } else {
        $_SESSION['message'] = "Could not open the uploaded file.";
        $_SESSION['message_type'] = "danger";
    }
}

header('Location: consumables.php');
exit();
