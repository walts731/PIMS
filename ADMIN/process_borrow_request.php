<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once '../includes/logger.php';
require_once 'includes/check_permissions.php';

adminRequirePermission('borrowing.create', 'can_create', 'borrowing.php');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: borrow_request.php');
    exit();
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Validate and sanitize input
    $guest_name = mysqli_real_escape_string($conn, trim($_POST['guest_name']));
    $barangay = mysqli_real_escape_string($conn, trim($_POST['barangay']));
    $contact = mysqli_real_escape_string($conn, trim($_POST['contact']));
    $date_borrowed = mysqli_real_escape_string($conn, trim($_POST['date_borrowed']));
    $schedule_return = !empty($_POST['schedule_return']) ? mysqli_real_escape_string($conn, trim($_POST['schedule_return'])) : null;
    $releasing_officer = mysqli_real_escape_string($conn, trim($_POST['releasing_officer']));
    $approved_by = mysqli_real_escape_string($conn, trim($_POST['approved_by']));
    $borrower_signature = mysqli_real_escape_string($conn, trim($_POST['borrower_signature'] ?? ''));
    
    // Validate required fields
    if (empty($guest_name) || empty($barangay) || empty($contact) || 
        empty($date_borrowed) || empty($releasing_officer) || empty($approved_by)) {
        throw new Exception('All required fields must be filled out.');
    }
    
    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date_borrowed)) {
        throw new Exception('Invalid date format for date borrowed.');
    }
    
    // Validate return date format and logic only if provided
    if (!empty($schedule_return)) {
        if (!DateTime::createFromFormat('Y-m-d', $schedule_return)) {
            throw new Exception('Invalid date format for schedule of return.');
        }
        
        // Validate return date is after borrow date
        $borrow_date = new DateTime($date_borrowed);
        $return_date = new DateTime($schedule_return);
        if ($return_date <= $borrow_date) {
            throw new Exception('Schedule of return must be after the date borrowed.');
        }
    }
    
    // Process items
    $items = [];
    if (!isset($_POST['individual_items_json']) || empty($_POST['individual_items_json'])) {
        throw new Exception('At least one item must be selected.');
    }
    
    $individual_items = json_decode($_POST['individual_items_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($individual_items)) {
        throw new Exception('Invalid item data format.');
    }
    
    foreach ($individual_items as $item) {
        if (!empty($item['asset_item_id'])) {
            $asset_item_id = (int)$item['asset_item_id'];
            $remarks = mysqli_real_escape_string($conn, trim($item['remarks'] ?? ''));
            $description = mysqli_real_escape_string($conn, trim($item['description'] ?? ''));
            $category = mysqli_real_escape_string($conn, trim($item['category'] ?? ''));
            
            // Check if asset item exists and is available
            $check_query = "SELECT ai.id, ai.description, ai.property_no, ai.status 
                           FROM asset_items ai 
                           WHERE ai.id = ? AND ai.status = 'serviceable'";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, 'i', $asset_item_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
            
            if (!$result || mysqli_num_rows($result) === 0) {
                throw new Exception('Selected asset item is not available for borrowing.');
            }
            
            $asset_data = mysqli_fetch_assoc($result);
            
            $items[] = [
                'asset_item_id' => $asset_item_id,
                'description' => $asset_data['description'],
                'property_no' => $asset_data['property_no'],
                'remarks' => $remarks,
                'category' => $category
            ];
        }
    }
    
    if (empty($items)) {
        throw new Exception('At least one valid item must be selected.');
    }
    
    // Insert borrow request
    $items_json = json_encode($items);
    
    $insert_query = "INSERT INTO borrow_form_submissions 
                    (guest_name, barangay, contact, date_borrowed, schedule_return, 
                     releasing_officer, approved_by, items, status, submitted_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())";
    
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, 'ssssssss', 
        $guest_name, $barangay, $contact, $date_borrowed, $schedule_return, 
        $releasing_officer, $approved_by, $items_json);
    
    if (!mysqli_stmt_execute($insert_stmt)) {
        throw new Exception('Failed to save borrow request: ' . mysqli_error($conn));
    }
    
    $borrow_request_id = mysqli_insert_id($conn);
    
    // Update individual asset items status to 'borrowed'
    foreach ($items as $item) {
        $asset_item_id = $item['asset_item_id'];
        
        // Update individual asset item status to 'borrowed'
        $update_query = "UPDATE asset_items 
                        SET status = 'borrowed' 
                        WHERE id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'i', $asset_item_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception('Failed to update asset item status: ' . mysqli_error($conn));
        }
        
        // Log the asset status change
        logSystemAction($_SESSION['user_id'], 'asset_borrowed', 'borrowing', "Asset item {$asset_item_id} ({$item['description']}) marked as borrowed for borrow request {$borrow_request_id}");
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Log the borrow request submission
    logSystemAction($_SESSION['user_id'], 'borrow_request_submit', 'borrowing', "Borrow request submitted for {$guest_name} (ID: {$borrow_request_id})");
    
    $_SESSION['success'] = "Borrow request submitted successfully! Request ID: {$borrow_request_id}";
    header('Location: borrowing.php');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    // Log the error
    logSystemAction($_SESSION['user_id'], 'borrow_request_error', 'borrowing', "Error in borrow request submission: " . $e->getMessage());
    
    $_SESSION['error'] = $e->getMessage();
    header('Location: borrow_request.php');
    exit();
}
?>
