<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Set content type
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'import') {
        throw new Exception('Invalid request method');
    }
    
    // Get import data
    $importData = json_decode($_POST['data'] ?? '[]', true);
    $skipDuplicates = isset($_POST['skipDuplicates']) && $_POST['skipDuplicates'] === 'true';
    $updateExisting = isset($_POST['updateExisting']) && $_POST['updateExisting'] === 'true';
    
    if (empty($importData)) {
        throw new Exception('No data to import');
    }
    
    // Log import attempt
    logSystemAction($_SESSION['user_id'], 'import', 'categories', 'Import started: ' . count($importData) . ' categories');
    
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        foreach ($importData as $index => $category) {
            $rowNumber = $category['row_number'] ?? ($index + 2); // +2 for header and 0-based index
            
            // Validate required fields
            if (empty($category['category_name']) || empty($category['category_code'])) {
                $errors[] = "Row $rowNumber: Category name and code are required";
                continue;
            }
            
            // Validate depreciation rate
            $depreciationRate = floatval($category['depreciation_rate'] ?? 0);
            if ($depreciationRate < 0 || $depreciationRate > 100) {
                $errors[] = "Row $rowNumber: Depreciation rate must be between 0 and 100";
                continue;
            }
            
            // Validate useful life
            $usefulLife = intval($category['useful_life_years'] ?? 0);
            if ($usefulLife < 0) {
                $errors[] = "Row $rowNumber: Useful life years must be positive";
                continue;
            }
            
            // Check if category code already exists
            $checkQuery = "SELECT id FROM asset_categories WHERE category_code = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("s", $category['category_code']);
            $checkStmt->execute();
            $existingCategory = $checkStmt->get_result()->fetch_assoc();
            
            if ($existingCategory) {
                if ($skipDuplicates && !$updateExisting) {
                    $skipped++;
                    continue;
                }
                
                if ($updateExisting) {
                    // Update existing category
                    $updateQuery = "UPDATE asset_categories SET 
                                   category_name = ?, 
                                   description = ?, 
                                   depreciation_rate = ?, 
                                   useful_life_years = ?, 
                                   status = ?,
                                   updated_by = ?,
                                   updated_at = NOW()
                                   WHERE id = ?";
                    
                    $status = in_array(strtolower($category['status'] ?? 'active'), ['active', '1', 'yes', 'true']) ? 'active' : 'inactive';
                    
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("ssdisii", 
                        $category['category_name'], 
                        $category['description'], 
                        $depreciationRate, 
                        $usefulLife, 
                        $status,
                        $_SESSION['user_id'],
                        $existingCategory['id']
                    );
                    
                    if ($updateStmt->execute()) {
                        $updated++;
                    } else {
                        $errors[] = "Row $rowNumber: Failed to update category";
                    }
                } else {
                    $errors[] = "Row $rowNumber: Category code already exists";
                    continue;
                }
            } else {
                // Insert new category
                $insertQuery = "INSERT INTO asset_categories 
                               (category_name, category_code, description, depreciation_rate, useful_life_years, status, created_by, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $status = in_array(strtolower($category['status'] ?? 'active'), ['active', '1', 'yes', 'true']) ? 'active' : 'inactive';
                
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("sssdssi", 
                    $category['category_name'], 
                    $category['category_code'], 
                    $category['description'], 
                    $depreciationRate, 
                    $usefulLife, 
                    $status,
                    $_SESSION['user_id']
                );
                
                if ($insertStmt->execute()) {
                    $imported++;
                } else {
                    $errors[] = "Row $rowNumber: Failed to insert category";
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log successful import
        $logMessage = "Import completed: $imported imported, $updated updated, $skipped skipped";
        if (!empty($errors)) {
            $logMessage .= ", " . count($errors) . " errors";
        }
        logSystemAction($_SESSION['user_id'], 'import', 'categories', $logMessage);
        
        // Prepare response message
        $message = "Import completed successfully! ";
        $message .= "$imported categories imported";
        if ($updated > 0) {
            $message .= ", $updated categories updated";
        }
        if ($skipped > 0) {
            $message .= ", $skipped categories skipped";
        }
        
        if (!empty($errors)) {
            $message .= ". " . count($errors) . " errors occurred.";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'stats' => [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => count($errors),
                'error_details' => $errors
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log error
    logSystemAction($_SESSION['user_id'], 'error', 'categories', 'Import failed: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Import failed: ' . $e->getMessage()
    ]);
}
?>
