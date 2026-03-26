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

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Set page title for topbar
$page_title = 'Requests Management';

// Get office ID from session
$office_id = $_SESSION['office_id'] ?? null;

// DEBUG: Log current session info
error_log("DEBUG: Session office_id = " . ($office_id ?? 'NULL'));
error_log("DEBUG: Session user_id = " . ($_SESSION['user_id'] ?? 'NULL'));
error_log("DEBUG: Session role = " . ($_SESSION['role'] ?? 'NULL'));

// Handle form submissions and AJAX requests
$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action === 'check_updates') {
    
    switch ($action) {
        case 'create_request':
            // Validate CSRF token first
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!validateCSRFToken($csrf_token)) {
                $_SESSION['error'] = "Invalid request. Please try again.";
                header('Location: requests.php');
                exit();
            }
            
            $asset_id = $_POST['asset_id'] ?? 0;
            $requested_to_office = $_POST['requested_to_office'] ?? 0;
            $quantity_requested = $_POST['quantity_requested'] ?? 1;
            $purpose = $_POST['purpose'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            
            // Validation
            if (empty($asset_id) || empty($requested_to_office) || empty($quantity_requested) || empty($purpose) || empty($start_date) || empty($end_date)) {
                $_SESSION['error'] = "All fields are required";
            } elseif ($start_date > $end_date) {
                $_SESSION['error'] = "Start date cannot be after end date";
            } elseif ($requested_to_office == $office_id) {
                $_SESSION['error'] = "Cannot request assets from your own office";
            } elseif ($quantity_requested < 1) {
                $_SESSION['error'] = "Quantity must be at least 1";
            } elseif (strtotime($start_date) < strtotime('today')) {
                $_SESSION['error'] = "Start date cannot be in the past";
            } elseif (strtotime($end_date) < strtotime($start_date)) {
                $_SESSION['error'] = "End date must be after start date";
            } else {
                try {
                    // Enhanced asset validation
                    $asset_check = "SELECT ai.id, ai.status, ai.description, ai.property_no,
                                   COALESCE(a.quantity, 1) as total_quantity,
                                   COALESCE(a.quantity, 1) as available_quantity,
                                   ai.office_id as asset_office_id
                                   FROM asset_items ai
                                   LEFT JOIN assets a ON ai.asset_id = a.id
                                   WHERE ai.id = ?";
                    $stmt = $conn->prepare($asset_check);
                    $stmt->bind_param("i", $asset_id);
                    $stmt->execute();
                    $asset_result = $stmt->get_result();
                    
                    if ($asset_result->num_rows === 0) {
                        $_SESSION['error'] = "Asset not found";
                    } else {
                        $asset_data = $asset_result->fetch_assoc();
                        
                        // Comprehensive asset validation
                        if ($asset_data['status'] !== 'serviceable') {
                            $_SESSION['error'] = "Asset '{$asset_data['description']}' is not available (Status: {$asset_data['status']})";
                        } elseif ($asset_data['asset_office_id'] == $office_id) {
                            $_SESSION['error'] = "Cannot request assets from your own office";
                        } elseif ($quantity_requested > $asset_data['available_quantity']) {
                            $_SESSION['error'] = "Only {$asset_data['available_quantity']} units available for '{$asset_data['description']}'. You requested {$quantity_requested}.";
                        } else {
                            // Check for overlapping requests for the same asset
                            $overlap_check = "SELECT COUNT(*) as overlapping_count
                                              FROM borrow_requests 
                                              WHERE asset_id = ? 
                                              AND status IN ('pending', 'approved', 'borrowed')
                                              AND (
                                                  (start_date <= ? AND end_date >= ?) OR
                                                  (start_date <= ? AND end_date >= ?) OR
                                                  (start_date >= ? AND end_date <= ?)
                                              )
                                              AND id != ?";
                            $stmt = $conn->prepare($overlap_check);
                            $exclude_id = 0;
                            $stmt->bind_param("issssssi", $asset_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date, $exclude_id);
                            $stmt->execute();
                            $overlap_result = $stmt->get_result();
                            $overlap_data = $overlap_result->fetch_assoc();
                            
                            if ($overlap_data['overlapping_count'] > 0) {
                                $_SESSION['error'] = "Asset '{$asset_data['description']}' is already requested for the selected dates. Please choose different dates.";
                            } else {
                                // Check borrowing limits (e.g., max 5 active requests per user)
                                $limit_check = "SELECT COUNT(*) as active_count
                                               FROM borrow_requests 
                                               WHERE requested_by = ? 
                                               AND status IN ('pending', 'approved', 'borrowed')";
                                $stmt = $conn->prepare($limit_check);
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $limit_result = $stmt->get_result();
                                $limit_data = $limit_result->fetch_assoc();
                                
                                if ($limit_data['active_count'] >= 5) {
                                    $_SESSION['error'] = "You have reached the maximum number of active requests (5). Please complete existing requests first.";
                                } else {
                                    // All validations passed - insert new borrow request
                                    // Generate unique ID for the request
                                    $request_id = time() + rand(1000, 9999); // Simple unique ID based on timestamp

                                    $insert_query = "INSERT INTO borrow_requests 
                                                     (id, requested_by, requested_by_office, requested_to_office, asset_id, quantity_requested, purpose, start_date, end_date) 
                                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                    $stmt = $conn->prepare($insert_query);
                                    $stmt->bind_param("iiisissss", $request_id, $_SESSION['user_id'], $office_id, $requested_to_office, $asset_id, $quantity_requested, $purpose, $start_date, $end_date);
                                    
                                    if ($stmt->execute()) {
                                        // Update asset status to pending when request is created
                                        $asset_update = "UPDATE asset_items SET status = 'pending' WHERE id = ?";
                                        $stmt2 = $conn->prepare($asset_update);
                                        $stmt2->bind_param("i", $asset_id);
                                        $stmt2->execute();
                                        
                                        $_SESSION['success'] = "Borrow request for {$quantity_requested} unit(s) created successfully";
                                        logSystemAction($_SESSION['user_id'], 'create', 'borrow_request', "Created borrow request for {$quantity_requested} unit(s) of asset #$asset_id");
                                    } else {
                                        $_SESSION['error'] = "Error creating borrow request";
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Database error: " . $e->getMessage();
                }
            }
            break;
            
        case 'approve_request':
            $request_id = $_POST['request_id'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            
            $update_query = "UPDATE borrow_requests SET 
                             status = 'approved', 
                             approved_by = ?, 
                             approved_at = NOW(), 
                             approval_notes = ? 
                             WHERE id = ? AND status = 'pending'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isi", $_SESSION['user_id'], $notes, $request_id);
            
            if ($stmt->execute()) {
                // Update asset status to pending when request is approved (awaiting pickup)
                $asset_update = "UPDATE asset_items SET status = 'pending' 
                                WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                $stmt2 = $conn->prepare($asset_update);
                $stmt2->bind_param("i", $request_id);
                $stmt2->execute();
                
                $_SESSION['success'] = "Request approved successfully";
                logSystemAction($_SESSION['user_id'], 'approve', 'borrow_request', "Approved borrow request #$request_id");
            } else {
                $_SESSION['error'] = "Error approving request";
            }
            break;
            
        // Bulk Actions Handlers
        case 'bulk_approve':
            $request_ids = $_POST['request_ids'] ?? '';
            if (empty($request_ids)) {
                echo json_encode(['success' => false, 'error' => 'No requests selected']);
                exit;
            }
            
            $id_array = explode(',', $request_ids);
            $approved_count = 0;
            
            foreach ($id_array as $request_id) {
                $request_id = (int)trim($request_id);
                if ($request_id <= 0) continue;
                
                $update_query = "UPDATE borrow_requests SET 
                                 status = 'approved', 
                                 approved_by = ?, 
                                 approved_at = NOW(), 
                                 approval_notes = 'Bulk approved' 
                                 WHERE id = ? AND status = 'pending' AND requested_to_office = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("iii", $_SESSION['user_id'], $request_id, $office_id);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    // Update asset status to pending when request is approved
                    $asset_update = "UPDATE asset_items SET status = 'pending' 
                                    WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                    $stmt2 = $conn->prepare($asset_update);
                    $stmt2->bind_param("i", $request_id);
                    $stmt2->execute();
                    
                    $approved_count++;
                    logSystemAction($_SESSION['user_id'], 'bulk_approve', 'borrow_request', "Bulk approved borrow request #$request_id");
                }
            }
            
            if ($approved_count > 0) {
                echo json_encode(['success' => true, 'message' => "$approved_count requests approved successfully"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No requests were approved']);
            }
            exit;
            
        case 'bulk_deny':
            $request_ids = $_POST['request_ids'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            if (empty($request_ids)) {
                echo json_encode(['success' => false, 'error' => 'No requests selected']);
                exit;
            }
            
            if (empty($reason)) {
                echo json_encode(['success' => false, 'error' => 'Denial reason is required']);
                exit;
            }
            
            $id_array = explode(',', $request_ids);
            $denied_count = 0;
            
            foreach ($id_array as $request_id) {
                $request_id = (int)trim($request_id);
                if ($request_id <= 0) continue;
                
                $update_query = "UPDATE borrow_requests SET 
                                 status = 'denied', 
                                 denied_by = ?, 
                                 denied_at = NOW(), 
                                 denial_reason = ? 
                                 WHERE id = ? AND status = 'pending' AND requested_to_office = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("isii", $_SESSION['user_id'], $reason, $request_id, $office_id);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    // Update asset status back to serviceable when request is denied
                    $asset_update = "UPDATE asset_items SET status = 'serviceable' 
                                    WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                    $stmt2 = $conn->prepare($asset_update);
                    $stmt2->bind_param("i", $request_id);
                    $stmt2->execute();
                    
                    $denied_count++;
                    logSystemAction($_SESSION['user_id'], 'bulk_deny', 'borrow_request', "Bulk denied borrow request #$request_id");
                }
            }
            
            if ($denied_count > 0) {
                echo json_encode(['success' => true, 'message' => "$denied_count requests denied"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No requests were denied']);
            }
            exit;
            
        case 'bulk_mark_borrowed':
            $request_ids = $_POST['request_ids'] ?? '';
            error_log("bulk_mark_borrowed called with request_ids: " . $request_ids);
            
            if (empty($request_ids)) {
                echo json_encode(['success' => false, 'error' => 'No requests selected']);
                exit;
            }
            
            $id_array = explode(',', $request_ids);
            $marked_count = 0;
            
            foreach ($id_array as $request_id) {
                $request_id = (int) trim($request_id);
                if ($request_id <= 0) continue;
                
                error_log("Processing request ID: " . $request_id);
                
                $update_query = "UPDATE borrow_requests SET 
                                 status = 'borrowed' 
                                 WHERE id = ? AND status = 'approved' AND requested_to_office = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ii", $request_id, $office_id);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    error_log("Successfully updated request ID: " . $request_id . " Affected rows: " . $stmt->affected_rows);
                    
                    // Update asset status to in_use when marked as borrowed
                    $asset_update = "UPDATE asset_items SET status = 'in_use' 
                                    WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                    $stmt2 = $conn->prepare($asset_update);
                    $stmt2->bind_param("i", $request_id);
                    $stmt2->execute();
                    
                    $marked_count++;
                    logSystemAction($_SESSION['user_id'], 'bulk_mark_borrowed', 'borrow_request', "Bulk marked borrow request #$request_id as borrowed");
                } else {
                    error_log("Failed to update request ID: " . $request_id . " Error: " . $stmt->error);
                }
            }
            
            error_log("Total marked count: " . $marked_count);
            
            if ($marked_count > 0) {
                echo json_encode(['success' => true, 'message' => "$marked_count requests marked as borrowed"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No requests were marked as borrowed']);
            }
            exit;
            
        // Real-time Updates Handler
        case 'check_updates':
            // Clean any previous output
            if (ob_get_level()) {
                ob_clean();
            }
            
            // Set proper content type first to prevent HTML output
            header('Content-Type: application/json');
            
            $last_update = $_GET['last_update'] ?? '';
            $has_updates = false;
            $new_requests = [];
            $changed_requests = [];
            $current_stats = [
                'pending_incoming' => 0,
                'approved_incoming' => 0,
                'borrowed_incoming' => 0,
                'pending_outgoing' => 0,
                'approved_outgoing' => 0,
                'borrowed_outgoing' => 0
            ];
            
            if ($office_id && $conn) {
                try {
                    // Get current requests with timestamps
                    $current_query = "SELECT br.*, 'incoming' as request_type,
                                     u.first_name, u.last_name, u.email, 
                                     o.office_name as requester_office, ai.description as asset_description,
                                     ai.property_no as asset_code, ac.category_name
                                     FROM borrow_requests br
                                     JOIN users u ON br.requested_by = u.id
                                     JOIN offices o ON br.requested_by_office = o.id
                                     JOIN asset_items ai ON br.asset_id = ai.id
                                     LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                                     WHERE br.requested_to_office = ? 
                                     UNION ALL
                                     SELECT br.*, 'outgoing' as request_type,
                                     u.first_name, u.last_name, u.email,
                                     o.office_name as approver_office, ai.description as asset_description,
                                     ai.property_no as asset_code, ac.category_name,
                                     oa.first_name as admin_first_name, oa.last_name as admin_last_name
                                     FROM borrow_requests br
                                     JOIN users u ON br.requested_by = u.id
                                     JOIN offices o ON br.requested_to_office = o.id
                                     JOIN asset_items ai ON br.asset_id = ai.id
                                     LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                                     LEFT JOIN users oa ON oa.office = br.requested_to_office AND oa.role = 'office_admin' AND oa.is_active = 1
                                     WHERE br.requested_by_office = ?
                                     ORDER BY br.updated_at DESC";
                    $stmt = $conn->prepare($current_query);
                    $stmt->bind_param("ii", $office_id, $office_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $request_time = new DateTime($row['updated_at']);
                        $last_update_time = new DateTime($last_update);
                        
                        // Check if request is newer than last update
                        if ($request_time > $last_update_time) {
                            $has_updates = true;
                            
                            // Check if it's a completely new request (created after last update)
                            $created_time = new DateTime($row['created_at']);
                            if ($created_time > $last_update_time) {
                                $new_requests[] = [
                                    'id' => $row['id'],
                                    'type' => $row['request_type'],
                                    'status' => $row['status']
                                ];
                            } else {
                                // It's an existing request that was updated
                                $changed_requests[] = [
                                    'id' => $row['id'],
                                    'type' => $row['request_type'],
                                    'status' => $row['status']
                                ];
                            }
                        }
                        
                        // Update current stats
                        if ($row['request_type'] === 'incoming') {
                            if ($row['status'] === 'pending') $current_stats['pending_incoming']++;
                            elseif ($row['status'] === 'approved') $current_stats['approved_incoming']++;
                            elseif ($row['status'] === 'borrowed') $current_stats['borrowed_incoming']++;
                        } else {
                            if ($row['status'] === 'pending') $current_stats['pending_outgoing']++;
                            elseif ($row['status'] === 'approved') $current_stats['approved_outgoing']++;
                            elseif ($row['status'] === 'borrowed') $current_stats['borrowed_outgoing']++;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error checking updates: " . $e->getMessage());
                    // Return error response
                    header('Content-Type: application/json');
                    echo json_encode([
                        'error' => 'Database error occurred',
                        'has_updates' => false,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    exit;
                }
            }
            
            $response = [
                'has_updates' => $has_updates,
                'new_requests' => $new_requests,
                'changed_requests' => $changed_requests,
                'stats' => $current_stats,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo json_encode($response);
            exit;
            
        case 'cancel_request':
            $request_id = $_POST['request_id'] ?? 0;
            
            // Verify this is an outgoing request from the current office
            $verify_query = "SELECT id FROM borrow_requests 
                           WHERE id = ? AND requested_by_office = ? AND status = 'pending'";
            $stmt = $conn->prepare($verify_query);
            $stmt->bind_param("ii", $request_id, $office_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update request status to cancelled
                $update_query = "UPDATE borrow_requests SET 
                                 status = 'cancelled' 
                                 WHERE id = ? AND requested_by_office = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ii", $request_id, $office_id);
                
                if ($stmt->execute()) {
                    // Update asset status back to serviceable when request is cancelled
                    $asset_update = "UPDATE asset_items SET status = 'serviceable' 
                                    WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                    $stmt2 = $conn->prepare($asset_update);
                    $stmt2->bind_param("i", $request_id);
                    $stmt2->execute();
                    
                    $_SESSION['success'] = "Request cancelled successfully";
                    logSystemAction($_SESSION['user_id'], 'cancel', 'borrow_request', "Cancelled borrow request #$request_id");
                } else {
                    $_SESSION['error'] = "Error cancelling request";
                }
            } else {
                $_SESSION['error'] = "Request not found or cannot be cancelled";
            }
            break;
            
        case 'bulk_cancel':
            $request_ids = $_POST['request_ids'] ?? '';
            if (empty($request_ids)) {
                echo json_encode(['success' => false, 'error' => 'No request IDs provided']);
                exit;
            }
            
            $request_id_array = explode(',', $request_ids);
            $cancelled_count = 0;
            
            foreach ($request_id_array as $request_id) {
                $request_id = trim($request_id);
                if (empty($request_id)) continue;
                
                // Verify this is an outgoing request from current office
                $verify_query = "SELECT id FROM borrow_requests 
                               WHERE id = ? AND requested_by_office = ? AND status = 'pending'";
                $stmt = $conn->prepare($verify_query);
                $stmt->bind_param("ii", $request_id, $office_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update request status to cancelled
                    $update_query = "UPDATE borrow_requests SET 
                                     status = 'cancelled' 
                                     WHERE id = ? AND requested_by_office = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ii", $request_id, $office_id);
                    
                    if ($stmt->execute()) {
                        // Update asset status back to serviceable when request is cancelled
                        $asset_update = "UPDATE asset_items SET status = 'serviceable' 
                                        WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                        $stmt2 = $conn->prepare($asset_update);
                        $stmt2->bind_param("i", $request_id);
                        $stmt2->execute();
                        
                        $cancelled_count++;
                        logSystemAction($_SESSION['user_id'], 'bulk_cancel', 'borrow_request', "Cancelled borrow request #$request_id");
                    }
                }
            }
            
            if ($cancelled_count > 0) {
                echo json_encode(['success' => true, 'message' => "$cancelled_count requests cancelled"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No requests were cancelled']);
            }
            exit;
            
        case 'deny_request':
            $request_id = $_POST['request_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            
            $update_query = "UPDATE borrow_requests SET 
                             status = 'denied', 
                             denied_by = ?, 
                             denied_at = NOW(), 
                             denial_reason = ? 
                             WHERE id = ? AND status = 'pending'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isi", $_SESSION['user_id'], $reason, $request_id);
            
            if ($stmt->execute()) {
                // Update asset status back to serviceable when request is denied
                $asset_update = "UPDATE asset_items SET status = 'serviceable' 
                                WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                $stmt2 = $conn->prepare($asset_update);
                $stmt2->bind_param("i", $request_id);
                $stmt2->execute();
                
                $_SESSION['success'] = "Request denied successfully";
                logSystemAction($_SESSION['user_id'], 'deny', 'borrow_request', "Denied borrow request #$request_id");
            } else {
                $_SESSION['error'] = "Error denying request";
            }
            break;
            
        case 'return_asset':
            $request_id = $_POST['request_id'] ?? 0;
            $condition = $_POST['return_condition'] ?? 'good';
            $notes = $_POST['return_notes'] ?? '';
            
            $update_query = "UPDATE borrow_requests SET 
                             status = 'returned', 
                             returned_at = NOW(), 
                             return_condition = ?, 
                             return_notes = ? 
                             WHERE id = ? AND status IN ('approved', 'borrowed')";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $condition, $notes, $request_id);
            
            if ($stmt->execute()) {
                // Update asset status back to serviceable when returned
                $asset_update = "UPDATE asset_items SET status = 'serviceable' 
                                WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                $stmt2 = $conn->prepare($asset_update);
                $stmt2->bind_param("i", $request_id);
                $stmt2->execute();
                
                $_SESSION['success'] = "Asset returned successfully";
                logSystemAction($_SESSION['user_id'], 'return', 'borrow_request', "Returned asset for request #$request_id");
            } else {
                $_SESSION['error'] = "Error returning asset";
            }
            break;
            
        case 'mark_borrowed':
            $request_id = $_POST['request_id'] ?? 0;
            
            $update_query = "UPDATE borrow_requests SET 
                             status = 'borrowed', 
                             borrowed_at = NOW() 
                             WHERE id = ? AND status = 'approved'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $request_id);
            
            if ($stmt->execute()) {
                // Update asset status to in_use when asset is borrowed
                $asset_update = "UPDATE asset_items SET status = 'in_use' 
                                WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                $stmt2 = $conn->prepare($asset_update);
                $stmt2->bind_param("i", $request_id);
                $stmt2->execute();
                
                $_SESSION['success'] = "Asset marked as borrowed successfully";
                logSystemAction($_SESSION['user_id'], 'borrow', 'borrow_request', "Marked asset as borrowed for request #$request_id");
            } else {
                $_SESSION['error'] = "Error marking asset as borrowed";
            }
            break;
    }
    
    header('Location: requests.php');
    exit();
}

// Fetch requests data
$incoming_requests = [];
$outgoing_requests = [];
$request_stats = [
    'pending_incoming' => 0,
    'approved_incoming' => 0,
    'borrowed_incoming' => 0,
    'denied_incoming' => 0,
    'pending_outgoing' => 0,
    'approved_outgoing' => 0,
    'borrowed_outgoing' => 0,
    'denied_outgoing' => 0
];

if ($office_id && $conn) {
    try {
        // DEBUG: Log the queries being executed
        error_log("DEBUG: Fetching requests for office_id = $office_id");
        
        $incoming_query = "SELECT br.*, u.first_name, u.last_name, u.email, 
                          o.office_name as requester_office, ai.description as asset_description,
                          ai.property_no as asset_code, ac.category_name
                          FROM borrow_requests br
                          JOIN users u ON br.requested_by = u.id
                          JOIN offices o ON br.requested_by_office = o.id
                          JOIN asset_items ai ON br.asset_id = ai.id
                          LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                          WHERE br.requested_to_office = ? 
                          ORDER BY br.created_at DESC";
        
        error_log("DEBUG: Incoming query = $incoming_query");
        $stmt = $conn->prepare($incoming_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $incoming_count = 0;
        while ($row = $result->fetch_assoc()) {
            $incoming_requests[] = $row;
            $incoming_count++;
            
            // Calculate stats
            if ($row['status'] === 'pending') {
                $request_stats['pending_incoming']++;
            } elseif ($row['status'] === 'approved') {
                $request_stats['approved_incoming']++;
            } elseif ($row['status'] === 'borrowed') {
                $request_stats['borrowed_incoming']++;
            } elseif ($row['status'] === 'denied') {
                $request_stats['denied_incoming']++;
            }
        }
        error_log("DEBUG: Found $incoming_count incoming requests");
        
        // Outgoing requests (this office requesting from other offices)
        $outgoing_query = "SELECT br.*, u.first_name, u.last_name, u.email,
                          o.office_name as approver_office, ai.description as asset_description,
                          ai.property_no as asset_code, ac.category_name,
                          oa.first_name as admin_first_name, oa.last_name as admin_last_name
                          FROM borrow_requests br
                          JOIN users u ON br.requested_by = u.id
                          JOIN offices o ON br.requested_to_office = o.id
                          JOIN asset_items ai ON br.asset_id = ai.id
                          LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                          LEFT JOIN users oa ON oa.office = br.requested_to_office AND oa.role = 'office_admin' AND oa.is_active = 1
                          WHERE br.requested_by_office = ?
                          ORDER BY br.created_at DESC";
        
        error_log("DEBUG: Outgoing query = $outgoing_query");
        $stmt = $conn->prepare($outgoing_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $outgoing_count = 0;
        while ($row = $result->fetch_assoc()) {
            $outgoing_requests[] = $row;
            $outgoing_count++;
            
            // Calculate stats
            if ($row['status'] === 'pending') {
                $request_stats['pending_outgoing']++;
            } elseif ($row['status'] === 'approved') {
                $request_stats['approved_outgoing']++;
            } elseif ($row['status'] === 'borrowed') {
                $request_stats['borrowed_outgoing']++;
            } elseif ($row['status'] === 'denied') {
                $request_stats['denied_outgoing']++;
            }
        }
        error_log("DEBUG: Found $outgoing_count outgoing requests");
        
    } catch (Exception $e) {
        error_log("Error fetching requests: " . $e->getMessage());
    }
    
    // DEBUG: Add sample data for testing if no requests found
    if (empty($incoming_requests) && $office_id) {
        error_log("DEBUG: No incoming requests found, creating sample data for office_id = $office_id");
        
        // Create a sample incoming request (someone requesting from this office)
        $sample_incoming = [
            'id' => 9999,
            'requested_by' => 1,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'requested_by_office' => 1,
            'requested_to_office' => $office_id,
            'asset_id' => 1,
            'purpose' => 'Sample incoming request for testing',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+3 days')),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'asset_description' => 'Sample Asset',
            'asset_code' => 'ASSET-001',
            'category_name' => 'Test Category',
            'requester_office' => 'Test Office'
        ];
        
        $incoming_requests[] = $sample_incoming;
        $request_stats['pending_incoming'] = 1;
        
        error_log("DEBUG: Created sample incoming request for testing");
    }
}

// Fetch available assets and offices for new request form
$available_assets = [];
$other_offices = [];
$asset_categories = [];

if ($office_id && $conn) {
    try {
        // Get asset categories
        $categories_query = "SELECT id, category_name, category_code, description 
                           FROM asset_categories 
                           WHERE status = 'active'
                           ORDER BY category_name";
        $stmt = $conn->prepare($categories_query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $asset_categories[] = $row;
        }
        
        // Get available assets from other offices
        $assets_query = "SELECT ai.id, ai.description, COALESCE(ai.property_no, ai.property_no) as asset_code, ac.category_name, o.office_name, o.id as office_id,
                         1 as total_quantity,
                         1 as available_quantity,
                         ac.id as category_id
                         FROM asset_items ai
                         LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                         LEFT JOIN assets a ON ai.asset_id = a.id
                         JOIN offices o ON ai.office_id = o.id
                         WHERE ai.office_id != ? AND ai.status = 'serviceable'
                         ORDER BY o.office_name, ai.description";
        $stmt = $conn->prepare($assets_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $available_assets[] = $row;
        }
        
        // Get other offices
        $offices_query = "SELECT id, office_name, office_code 
                         FROM offices 
                         WHERE status = 'active'
                         ORDER BY office_name";
        $stmt = $conn->prepare($offices_query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Exclude current office
            if ($row['id'] != $office_id) {
                $other_offices[] = $row;
            }
        }
        
    } catch (Exception $e) {
        error_log("Error fetching form data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests Management - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #5CC2F2;
        }
        
        .request-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.1);
            margin-bottom: 1rem;
        }
        
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .filter-tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            color: var(--dark-color);
            text-decoration: none;
            position: relative;
        }
        
        .filter-tab:hover {
            background: rgba(25, 27, 169, 0.05);
            color: var(--primary-color);
        }
        
        .filter-tab.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }
        
        .request-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .request-type-incoming {
            background: rgba(25, 27, 169, 0.1);
            color: var(--primary-color);
        }
        
        .request-type-outgoing {
            background: rgba(92, 194, 242, 0.1);
            color: var(--accent-color);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1e7dd; color: #0f5132; }
        .status-borrowed { background: #cff4fc; color: #055160; }
        .status-returned { background: #d1ecf1; color: #0c5460; }
        .status-denied { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #e2e3e5; color: #495057; }
        
        .quick-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.25rem;
        }
        
        .request-row[data-needs-action="true"] {
            background: linear-gradient(90deg, rgba(255, 193, 7, 0.05) 0%, transparent 100%);
            border-left: 3px solid #ffc107;
        }
        
        .request-row.hidden {
            display: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .bulk-actions-bar {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #5CC2F2;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .bulk-actions-bar .btn-group {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .request-checkbox {
            transform: scale(1.1);
            cursor: pointer;
        }
        
        .request-checkbox:checked {
            background-color: #5CC2F2;
            border-color: #5CC2F2;
        }
        
        #selectedCount {
            font-weight: 600;
            color: #191BA9;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-container .input-group {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 25px;
            overflow: hidden;
        }
        
        .search-container .input-group-text {
            background: transparent;
            border: none;
            color: #6c757d;
            padding-left: 1rem;
        }
        
        .search-container .form-control {
            border: none;
            border-radius: 25px;
            padding-left: 0.5rem;
            font-weight: 500;
        }
        
        .search-container .form-control:focus {
            box-shadow: none;
            border-color: #5CC2F2;
        }
        
        .search-container .btn {
            border: none;
            border-radius: 0 25px 25px 0;
            background: rgba(92, 194, 242, 0.1);
            color: #191BA9;
        }
        
        .search-container .btn:hover {
            background: rgba(92, 194, 242, 0.2);
        }
        
        .dropdown-menu .dropdown-item {
            padding: 0;
        }
        
        .dropdown-menu .dropdown-item:hover {
            background: transparent;
        }
        
        .highlight-search {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Page Footer Styles */
        .page-footer {
            margin-top: 3rem;
            padding: 2rem 0;
            border-top: 1px solid rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .footer-content {
            text-align: center;
        }
        
        .footer-spacer {
            height: 2rem;
        }
        
        .footer-info {
            opacity: 0.7;
        }
        
        /* Ensure main content has proper bottom spacing */
        .main-content {
            padding-bottom: 2rem;
        }
        
        /* Add some breathing room for the table */
        #requestsContainer {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'Requests Management';
?>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
        <?php require_once 'includes/notification_js.php'; ?>
        
        <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-send"></i> Requests Management
                    </h1>
                    <p class="text-muted mb-0">Manage asset borrow requests - incoming and outgoing</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="bi bi-plus-circle"></i> New Request
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Request Statistics -->
        <div class="row mb-4 justify-content-center">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number" data-stat="pending_incoming"><?php echo $request_stats['pending_incoming']; ?></div>
                            <div class="text-muted">Pending Incoming</div>
                            <small class="text-warning">Awaiting your action</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-inbox fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number" data-stat="borrowed_incoming"><?php echo $request_stats['borrowed_incoming']; ?></div>
                            <div class="text-muted">Borrowed Incoming</div>
                            <small class="text-warning">Currently borrowed</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-box-seam fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number" data-stat="pending_outgoing"><?php echo $request_stats['pending_outgoing']; ?></div>
                            <div class="text-muted">Pending Outgoing</div>
                            <small class="text-info">Awaiting approval</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-send fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number" data-stat="borrowed_outgoing"><?php echo $request_stats['borrowed_outgoing']; ?></div>
                            <div class="text-muted">Borrowed Outgoing</div>
                            <small class="text-info">Currently borrowed</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-check2-square fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Smart Filter Tabs -->
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-white border-bottom-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Request Management</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshRequests()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Search Bar -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="search-container">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="advancedSearchInput" 
                                       placeholder="Search requests by asset, requester, purpose, or status...">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-funnel"></i> Filters
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                                    <li><h6 class="dropdown-header">Search Filters</h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-item">
                                        <div class="mb-2">
                                            <label class="form-label small">Request Type</label>
                                            <select class="form-select form-select-sm" id="filterType">
                                                <option value="">All Types</option>
                                                <option value="incoming">Incoming</option>
                                                <option value="outgoing">Outgoing</option>
                                            </select>
                                        </div>
                                    </li>
                                    <li class="dropdown-item">
                                        <div class="mb-2">
                                            <label class="form-label small">Status</label>
                                            <select class="form-select form-select-sm" id="filterStatus">
                                                <option value="">All Statuses</option>
                                                <option value="pending">Pending</option>
                                                <option value="approved">Approved</option>
                                                <option value="borrowed">Borrowed</option>
                                                <option value="returned">Returned</option>
                                                <option value="denied">Denied</option>
                                            </select>
                                        </div>
                                    </li>
                                    <li class="dropdown-item">
                                        <div class="mb-2">
                                            <label class="form-label small">Date Range</label>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <input type="date" class="form-control form-control-sm" id="filterDateFrom" placeholder="From">
                                                </div>
                                                <div class="col-6">
                                                    <input type="date" class="form-control form-control-sm" id="filterDateTo" placeholder="To">
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item" onclick="applyAdvancedFilters()">
                                            <i class="bi bi-check-circle"></i> Apply Filters
                                        </button>
                                        <button class="dropdown-item" onclick="clearAdvancedFilters()">
                                            <i class="bi bi-x-circle"></i> Clear Filters
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2 align-items-center">
                            <small class="text-muted" id="searchResultsCount">Showing all requests</small>
                        </div>
                    </div>
                </div>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="needs_action">
                        <i class="bi bi-exclamation-circle"></i> Needs My Action
                        <?php if ($request_stats['pending_incoming'] > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?php echo $request_stats['pending_incoming']; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="filter-tab" data-filter="waiting">
                        <i class="bi bi-clock"></i> Waiting for Others
                        <?php if ($request_stats['pending_outgoing'] > 0): ?>
                            <span class="badge bg-info ms-1"><?php echo $request_stats['pending_outgoing']; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="filter-tab" data-filter="all">
                        <i class="bi bi-list-ul"></i> All Requests
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Bulk Actions Bar -->
                <div id="bulkActionsBar" class="bulk-actions-bar d-none">
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-muted">
                                <span id="selectedCount">0</span> requests selected
                            </span>
                            <div class="btn-group" role="group">
                                <!-- Buttons for "needs_action" filter (incoming requests) -->
                                <div id="incomingBulkActions" class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-success" id="bulkApproveBtn" disabled>
                                        <i class="bi bi-check-circle"></i> Approve Selected
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" id="bulkDenyBtn" disabled>
                                        <i class="bi bi-x-circle"></i> Deny Selected
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" id="bulkMarkBorrowedBtn" disabled>
                                        <i class="bi bi-hand-index"></i> Mark Borrowed
                                    </button>
                                </div>
                                
                                <!-- Buttons for "waiting" filter (outgoing requests) -->
                                <div id="outgoingBulkActions" class="btn-group d-none" role="group">
                                    <button class="btn btn-sm btn-outline-danger" id="bulkCancelBtn" disabled>
                                        <i class="bi bi-x-circle"></i> Cancel Selected
                                    </button>
                                </div>
                                
                                <!-- Buttons for "all" filter (both incoming and outgoing) -->
                                <div id="allBulkActions" class="btn-group d-none" role="group">
                                    <button class="btn btn-sm btn-outline-success" id="bulkApproveBtnAll" disabled>
                                        <i class="bi bi-check-circle"></i> Approve Selected
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" id="bulkDenyBtnAll" disabled>
                                        <i class="bi bi-x-circle"></i> Deny Selected
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" id="bulkMarkBorrowedBtnAll" disabled>
                                        <i class="bi bi-hand-index"></i> Mark Borrowed
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" id="bulkCancelBtnAll" disabled>
                                        <i class="bi bi-x-circle"></i> Cancel Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                            <i class="bi bi-x"></i> Clear Selection
                        </button>
                    </div>
                </div>
                
                <!-- Unified Request List -->
                <div id="requestsContainer">
                    <?php if (!empty($incoming_requests) || !empty($outgoing_requests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllRequests" class="form-check-input" title="Select all requests">
                                        </th>
                                        <th>Type</th>
                                        <th>Requester/Office</th>
                                        <th>Asset</th>
                                        <th>Purpose</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Merge and sort requests by created_at
                                    $all_requests = [];
                                    foreach ($incoming_requests as $req) {
                                        $req['request_type'] = 'incoming';
                                        $req['display_name'] = $req['first_name'] . ' ' . $req['last_name'];
                                        $req['display_office'] = $req['requester_office'];
                                        $all_requests[] = $req;
                                    }
                                    foreach ($outgoing_requests as $req) {
                                        $req['request_type'] = 'outgoing';
                                        $req['display_name'] = $req['approver_office'];
                                        // Show office admin if available, otherwise show the requester
                                        if (!empty($req['admin_first_name']) && !empty($req['admin_last_name'])) {
                                            $req['display_office'] = $req['admin_first_name'] . ' ' . $req['admin_last_name'] . ' (Admin)';
                                        } else {
                                            $req['display_office'] = 'No office admin assigned';
                                        }
                                        $all_requests[] = $req;
                                    }
                                    
                                    // Sort by created_at descending
                                    usort($all_requests, function($a, $b) {
                                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                                    });
                                    
                                    foreach ($all_requests as $request): 
                                    ?>
                                        <tr class="request-row" 
                                            data-type="<?php echo $request['request_type']; ?>" 
                                            data-status="<?php echo $request['status']; ?>"
                                            data-needs-action="<?php echo ($request['request_type'] === 'incoming' && $request['status'] === 'pending') ? 'true' : 'false'; ?>"
                                            data-request-id="<?php echo $request['id']; ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input request-checkbox" value="<?php echo $request['id']; ?>">
                                            </td>
                                            <td>
                                                <span class="request-type-badge request-type-<?php echo $request['request_type']; ?>">
                                                    <?php if ($request['request_type'] === 'incoming'): ?>
                                                        <i class="bi bi-inbox"></i> Incoming
                                                    <?php else: ?>
                                                        <i class="bi bi-send"></i> Outgoing
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($request['display_name']); ?></strong>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($request['display_office']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($request['asset_description']); ?></strong>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($request['asset_code']); ?></div>
                                                    <div class="small text-info"><?php echo htmlspecialchars($request['category_name'] ?? 'Uncategorized'); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                                            <td>
                                                <div class="small">
                                                    <div>From: <?php echo date('M j, Y', strtotime($request['start_date'])); ?></div>
                                                    <div>To: <?php echo date('M j, Y', strtotime($request['end_date'])); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                                <?php if ($request['request_type'] === 'incoming' && $request['status'] === 'pending'): ?>
                                                    <div class="small text-warning mt-1">
                                                        <i class="bi bi-exclamation-circle"></i> Your action needed
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($request['request_type'] === 'incoming' && $request['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success action-btn quick-action" 
                                                            onclick="quickApprove(<?php echo $request['id']; ?>)"
                                                            title="Approve request">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger action-btn quick-action" 
                                                            onclick="quickDeny(<?php echo $request['id']; ?>)"
                                                            title="Deny request">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php elseif ($request['request_type'] === 'incoming' && $request['status'] === 'approved'): ?>
                                                    <button class="btn btn-sm btn-warning action-btn quick-action" 
                                                            onclick="quickMarkBorrowed(<?php echo $request['id']; ?>)"
                                                            title="Mark as borrowed">
                                                        <i class="bi bi-hand-index"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-primary action-btn" 
                                                            onclick="returnAsset(<?php echo $request['id']; ?>)">
                                                        <i class="bi bi-arrow-return-left"></i> Return
                                                    </button>
                                                <?php elseif ($request['request_type'] === 'incoming' && $request['status'] === 'borrowed'): ?>
                                                    <button class="btn btn-sm btn-primary action-btn" 
                                                            onclick="returnAsset(<?php echo $request['id']; ?>)">
                                                        <i class="bi bi-arrow-return-left"></i> Return
                                                    </button>
                                                <?php elseif ($request['request_type'] === 'outgoing' && $request['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger action-btn" 
                                                            onclick="cancelRequest(<?php echo $request['id']; ?>)"
                                                            title="Cancel request">
                                                        <i class="bi bi-x-circle"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-info action-btn" 
                                                        onclick="viewDetails(<?php echo $request['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>No Requests Found</h5>
                            <p>No requests match the current filter criteria.</p>
                            <?php if (empty($office)): ?>
                                <p>Please select an office to view requests.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Approve Request Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Approve Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="approve_request">
                    <input type="hidden" name="request_id" id="approveRequestId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                            <textarea class="form-control" id="approval_notes" name="notes" rows="3" 
                                    placeholder="Add any notes or conditions for this approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Approve Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Deny Request Modal -->
    <div class="modal fade" id="denyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle"></i> Deny Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="deny_request">
                    <input type="hidden" name="request_id" id="denyRequestId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="denial_reason" class="form-label">Reason for Denial <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="denial_reason" name="reason" rows="3" 
                                    placeholder="Please provide a reason for denying this request..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Deny Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Return Asset Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-return-left"></i> Return Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="return_asset">
                    <input type="hidden" name="request_id" id="returnRequestId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="return_condition" class="form-label">Asset Condition</label>
                            <select class="form-control" id="return_condition" name="return_condition" required>
                                <option value="excellent">Excellent - Like new</option>
                                <option value="good" selected>Good - Minor wear</option>
                                <option value="fair">Fair - Noticeable wear</option>
                                <option value="poor">Poor - Significant damage</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="return_notes" class="form-label">Return Notes (Optional)</label>
                            <textarea class="form-control" id="return_notes" name="return_notes" rows="3" 
                                    placeholder="Add any notes about the asset condition..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-return-left"></i> Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle d-none d-md-inline"></i> 
                        <span class="d-md-none">New Request</span>
                        <span class="d-none d-md-inline">New Borrow Request</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_request">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                        <!-- Mobile-friendly layout with better spacing -->
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="requested_to_office" class="form-label fw-semibold">
                                        Request To Office 
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    <select class="form-select" id="requested_to_office" name="requested_to_office" 
                                            aria-describedby="officeHelp" aria-required="true" required>
                                        <option value="">Select Office</option>
                                        <?php if (!empty($other_offices)): ?>
                                            <?php foreach ($other_offices as $office): ?>
                                                <option value="<?php echo $office['id']; ?>">
                                                    <?php echo htmlspecialchars($office['office_name']); ?> (<?php echo htmlspecialchars($office['office_code']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>No offices available</option>
                                        <?php endif; ?>
                                    </select>
                                    <div id="officeHelp" class="form-text">
                                        Select the office you want to borrow assets from
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="asset_category" class="form-label fw-semibold">Asset Category</label>
                                    <select class="form-select" id="asset_category" name="asset_category">
                                        <option value="">All Categories</option>
                                        <?php if (!empty($asset_categories)): ?>
                                            <?php foreach ($asset_categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['category_name']); ?> (<?php echo htmlspecialchars($category['category_code']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <small class="text-muted">Filter assets by category</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        Asset to Borrow 
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    
                                    <!-- Assets Table -->
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-hover table-striped" id="assetsTable">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 50px;">Select</th>
                                                    <th>Description</th>
                                                    <th>Property No.</th>
                                                    <th>Category</th>
                                                    <th>Office</th>
                                                    <th class="text-center">Available</th>
                                                </tr>
                                            </thead>
                                            <tbody id="assetsTableBody">
                                                <?php if (empty($available_assets)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-4">
                                                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                                            No assets available for borrowing from other offices.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($available_assets as $asset): ?>
                                                        <tr class="asset-row" 
                                                            data-asset-id="<?php echo $asset['id']; ?>"
                                                            data-office-id="<?php echo $asset['office_id']; ?>"
                                                            data-category-id="<?php echo $asset['category_id']; ?>"
                                                            data-available="<?php echo $asset['available_quantity']; ?>"
                                                            data-total="<?php echo $asset['total_quantity']; ?>"
                                                            data-description="<?php echo htmlspecialchars($asset['description']); ?>"
                                                            data-asset-code="<?php echo htmlspecialchars($asset['asset_code']); ?>"
                                                            data-office-name="<?php echo htmlspecialchars($asset['office_name']); ?>">
                                                            <td class="text-center">
                                                                <input type="radio" name="selected_asset" 
                                                                       value="<?php echo $asset['id']; ?>"
                                                                       class="form-check-input asset-selector">
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($asset['description']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($asset['asset_code']); ?></code>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info">
                                                                    <?php echo htmlspecialchars($asset['category_name'] ?? 'Uncategorized'); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <i class="bi bi-building"></i>
                                                                <?php echo htmlspecialchars($asset['office_name']); ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-success">
                                                                    <?php echo $asset['available_quantity']; ?> of <?php echo $asset['total_quantity']; ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Hidden input to store selected asset -->
                                    <input type="hidden" id="asset_id" name="asset_id" required>
                                    
                                    <!-- Selected Asset Display -->
                                    <div id="selectedAssetDisplay" class="alert alert-info d-none">
                                        <h6 class="mb-2">Selected Asset:</h6>
                                        <div id="selectedAssetInfo"></div>
                                    </div>
                                    
                                    <div id="assetHelp" class="form-text">
                                        Only available assets from other offices are shown. Click on a row to select an asset.
                                    </div>
                                    <div id="assetAvailability" class="form-text fw-semibold"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="quantity_requested" class="form-label fw-semibold">
                                        Quantity 
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="quantity_requested" name="quantity_requested" min="1" value="1" required>
                                    <small class="text-muted" id="quantity_info">Select an asset to see available quantity</small>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label fw-semibold">
                                        Start Date 
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    <input type="date" class="form-control form-control-lg" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label fw-semibold">
                                        End Date 
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    <input type="date" class="form-control form-control-lg" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="purpose" class="form-label fw-semibold">
                                        Purpose of Borrowing 
                                        <span class="text-danger" aria-label="required">*</span>
                                    </label>
                                    <textarea class="form-control form-control-lg" id="purpose" name="purpose" rows="4" 
                                            placeholder="Please describe the purpose for borrowing this asset..." required
                                            style="min-height: 100px; resize: vertical;"></textarea>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Minimum 10 characters</small>
                                        <small class="text-muted" id="charCount">0 / 500</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progressive Disclosure: Additional Options -->
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" 
                                            data-bs-toggle="collapse" data-bs-target="#additionalOptions"
                                            aria-expanded="false" aria-controls="additionalOptions">
                                        <i class="bi bi-chevron-down"></i> 
                                        Additional Options
                                        <small class="text-muted">(Optional)</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="collapse" id="additionalOptions">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="urgency_level" class="form-label fw-semibold">Urgency Level</label>
                                        <select class="form-select" id="urgency_level" name="urgency_level">
                                            <option value="normal">Normal (3-5 business days)</option>
                                            <option value="urgent">Urgent (1-2 business days)</option>
                                            <option value="emergency">Emergency (Same day)</option>
                                        </select>
                                        <small class="text-muted">Select urgency level for processing priority</small>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="delivery_preference" class="form-label fw-semibold">Delivery Preference</label>
                                        <select class="form-select" id="delivery_preference" name="delivery_preference">
                                            <option value="pickup">Pickup from Office</option>
                                            <option value="delivery">Delivery to Location</option>
                                        </select>
                                        <small class="text-muted">How would you like to receive the asset?</small>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6" id="deliveryLocationGroup" style="display: none;">
                                    <div class="mb-3">
                                        <label for="delivery_location" class="form-label fw-semibold">Delivery Location</label>
                                        <input type="text" class="form-control" id="delivery_location" name="delivery_location" 
                                               placeholder="Enter delivery location...">
                                        <small class="text-muted">Specify where asset should be delivered</small>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6" id="emergencyReasonGroup" style="display: none;">
                                    <div class="mb-3">
                                        <label for="emergency_reason" class="form-label fw-semibold">
                                            Emergency Reason 
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="emergency_reason" name="emergency_reason" rows="2"
                                                  placeholder="Please explain emergency situation..."></textarea>
                                        <small class="text-muted">Required for emergency requests</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mobile-specific tips -->
                        <div class="d-md-none">
                            <div class="alert alert-info alert-sm">
                                <i class="bi bi-phone"></i> 
                                <strong>Mobile Tip:</strong> Scroll down to see all form fields. Use the info button to view asset details.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light sticky-bottom">
                        <div class="d-flex flex-column flex-md-row gap-2 w-100">
                            <button type="button" class="btn btn-secondary flex-fill flex-md-grow-0" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary flex-fill flex-md-grow-0">
                                <i class="bi bi-send"></i> Submit Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Request Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Request Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <!-- Content will be populated dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        

console.log('DEBUG: Script tag loaded!');

// ---------------------------------------------------------------------------
// Modal helpers
// ---------------------------------------------------------------------------

function approveRequest(requestId) {
    document.getElementById('approveRequestId').value = requestId;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function denyRequest(requestId) {
    console.log('denyRequest called with requestId:', requestId);
    document.getElementById('denyRequestId').value = requestId;
    new bootstrap.Modal(document.getElementById('denyModal')).show();
}

function returnAsset(requestId) {
    console.log('returnAsset called with requestId:', requestId);
    document.getElementById('returnRequestId').value = requestId;
    new bootstrap.Modal(document.getElementById('returnModal')).show();
}

// ---------------------------------------------------------------------------
// Quick-action helpers (create hidden form + submit)
// ---------------------------------------------------------------------------

function quickApprove(requestId) {
    console.log('quickApprove called with requestId:', requestId);
    if (confirm('Are you sure you want to approve this request?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve_request">
            <input type="hidden" name="request_id" value="${requestId}">
            <input type="hidden" name="notes" value="Approved via quick action">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function quickDeny(requestId) {
    console.log('quickDeny called with requestId:', requestId);
    const reason = prompt('Please enter the reason for denial:');
    if (reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="deny_request">
            <input type="hidden" name="request_id" value="${requestId}">
            <input type="hidden" name="reason" value="${reason}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function quickMarkBorrowed(requestId) {
    console.log('quickMarkBorrowed called with requestId:', requestId);
    if (confirm('Mark this asset as borrowed?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="mark_borrowed">
            <input type="hidden" name="request_id" value="${requestId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function markBorrowed(requestId) {
    console.log('markBorrowed called with requestId:', requestId);
    if (confirm('Are you sure you want to mark this asset as borrowed?')) {
        const formData = new FormData();
        formData.append('action', 'mark_borrowed');
        formData.append('request_id', requestId);
        fetch('requests.php', { method: 'POST', body: formData })
            .then(() => window.location.reload())
            .catch(err => alert('Error marking as borrowed: ' + err.message));
    }
}

function cancelRequest(requestId) {
    console.log('cancelRequest called with requestId:', requestId);
    if (confirm('Are you sure you want to cancel this request? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="cancel_request">
            <input type="hidden" name="request_id" value="${requestId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ---------------------------------------------------------------------------
// Refresh
// ---------------------------------------------------------------------------

function refreshRequests() {
    window.location.reload();
}

// ---------------------------------------------------------------------------
// Create test request for empty offices
// ---------------------------------------------------------------------------

function createTestRequest() {
    if (confirm('Create sample requests for testing? This will add 3 sample borrow requests for your office.')) {
        window.location.href = 'create_sample_requests.php';
    }
}

// ---------------------------------------------------------------------------
// View details
// ---------------------------------------------------------------------------

function viewDetails(requestId) {
    console.log('viewDetails called with requestId:', requestId);
    fetch(`../api/get_request_details_simple.php?request_id=${requestId}`)
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Expected JSON but got:', text.substring(0, 200));
                    throw new Error('Server returned non-JSON response.');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.error) { alert('Error: ' + data.error); return; }
            populateDetailsModal(data);
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        })
        .catch(err => alert('Error loading request details: ' + err.message));
}

// ---------------------------------------------------------------------------
// Details modal population helpers
// ---------------------------------------------------------------------------

function getEventIcon(type) {
    return { created:'bi-send', approved:'bi-check-circle', borrowed:'bi-hand-index',
              denied:'bi-x-circle', returned:'bi-arrow-return-left' }[type] || 'bi-circle';
}
function getEventColor(type) {
    return { created:'primary', approved:'success', borrowed:'warning',
              denied:'danger', returned:'info' }[type] || 'secondary';
}
function getStatusBadge(status) {
    return ({
        pending:   '<span class="badge bg-warning">Pending</span>',
        approved:  '<span class="badge bg-success">Approved</span>',
        borrowed:  '<span class="badge bg-warning">Borrowed</span>',
        denied:    '<span class="badge bg-danger">Denied</span>',
        returned:  '<span class="badge bg-info">Returned</span>',
        cancelled: '<span class="badge bg-secondary">Cancelled</span>'
    }[status]) || '<span class="badge bg-secondary">Unknown</span>';
}
function getStatusDescription(status) {
    return ({
        pending:   'Request is awaiting approval',
        approved:  'Request has been approved and ready for pickup',
        borrowed:  'Asset has been picked up and is in use',
        denied:    'Request has been denied',
        returned:  'Asset has been returned',
        cancelled: 'Request has been cancelled'
    }[status]) || 'Unknown status';
}
function getStatusColor(status) {
    return ({
        serviceable:'success', in_use:'primary', available:'info',
        maintenance:'warning', disposed:'secondary', unserviceable:'danger',
        no_tag:'secondary', pending_tag:'warning', red_tagged:'danger'
    }[status]) || 'secondary';
}
function ucfirst(str) {
    if (!str || typeof str !== 'string') return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function populateDetailsModal(data) {
    const modalBody = document.getElementById('detailsModalBody');

    let lifecycleHtml = '';
    if (data.lifecycle && data.lifecycle.events) {
        data.lifecycle.events.forEach((event, index) => {
            const icon = getEventIcon(event.type);
            const color = getEventColor(event.type);
            const formattedDate = new Date(event.timestamp).toLocaleDateString('en-US',
                { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
            lifecycleHtml += `
                <div class="timeline-item">
                    <div class="timeline-marker ${color}"><i class="bi ${icon}"></i></div>
                    <div class="timeline-content">
                        <h6 class="mb-1">${event.title}</h6>
                        <p class="mb-1 text-muted small">${event.description}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                ${event.user ? `<strong>${event.user}</strong><br>
                                    <small class="text-muted">${event.user_email}</small><br>
                                    <small class="text-info">${event.office}</small>` : ''}
                                ${event.notes ? `<br><small class="text-muted">Notes: ${event.notes}</small>` : ''}
                            </div>
                            <small class="text-muted">${formattedDate}</small>
                        </div>
                    </div>
                </div>
                ${index < data.lifecycle.events.length - 1 ? '<div class="timeline-connector"></div>' : ''}
            `;
        });
    }

    const currentStatus = (data.lifecycle && data.lifecycle.current_status)
        ? data.lifecycle.current_status
        : { status: data.request.status, title: ucfirst(data.request.status),
            description: getStatusDescription(data.request.status), timestamp: data.request.created_at };

    const formattedStatusDate = new Date(currentStatus.timestamp).toLocaleDateString('en-US',
        { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });

    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-info-circle"></i> Request Overview</h6></div>
                    <div class="card-body">
                        <div class="row mb-2"><div class="col-sm-4"><strong>Request ID:</strong></div><div class="col-sm-8">#${data.request.id}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Status:</strong></div><div class="col-sm-8">${getStatusBadge(currentStatus.status)}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Quantity:</strong></div><div class="col-sm-8">${data.request.quantity_requested} unit(s)</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Purpose:</strong></div><div class="col-sm-8">${data.request.purpose}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Duration:</strong></div>
                            <div class="col-sm-8">From: ${new Date(data.request.start_date).toLocaleDateString()}<br>To: ${new Date(data.request.end_date).toLocaleDateString()}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Created:</strong></div><div class="col-sm-8">${new Date(data.request.created_at).toLocaleDateString()}</div></div>
                        ${data.request.approval_notes ? `<div class="row mb-2"><div class="col-sm-4"><strong>Approval Notes:</strong></div><div class="col-sm-8">${data.request.approval_notes}</div></div>` : ''}
                        ${data.request.denial_reason   ? `<div class="row mb-2"><div class="col-sm-4"><strong>Denial Reason:</strong></div><div class="col-sm-8">${data.request.denial_reason}</div></div>` : ''}
                        ${data.request.return_condition? `<div class="row mb-2"><div class="col-sm-4"><strong>Return Condition:</strong></div><div class="col-sm-8">${data.request.return_condition}</div></div>` : ''}
                        ${data.request.return_notes    ? `<div class="row mb-2"><div class="col-sm-4"><strong>Return Notes:</strong></div><div class="col-sm-8">${data.request.return_notes}</div></div>` : ''}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-building"></i> Offices Involved</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-primary"><i class="bi bi-arrow-right"></i> Requested From</h6>
                            <strong>${data.requester.office.name}</strong> (${data.requester.office.code})<br>
                            <small class="text-muted">${data.requester.name}</small><br>
                            <small class="text-muted">${data.requester.email}</small>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-success"><i class="bi bi-arrow-left"></i> Requested To</h6>
                            <strong>${data.approver.office.name}</strong> (${data.approver.office.code})<br>
                            <small class="text-muted">${data.approver.name || 'N/A'}</small><br>
                            <small class="text-muted">${data.approver.email || 'N/A'}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-box-seam"></i> Asset Information</h6></div>
                    <div class="card-body">
                        ${data.asset ? `
                        <div class="row mb-2"><div class="col-sm-4"><strong>Description:</strong></div><div class="col-sm-8">${data.asset.description || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Property No:</strong></div><div class="col-sm-8">${data.asset.code || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Inventory Tag:</strong></div><div class="col-sm-8">${data.asset.inventory_tag || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Category:</strong></div><div class="col-sm-8">${data.asset.category.name || 'Uncategorized'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Model:</strong></div><div class="col-sm-8">${data.asset.model || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Serial Number:</strong></div><div class="col-sm-8">${data.asset.serial_number || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Unit:</strong></div><div class="col-sm-8">${data.asset.unit || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Current Status:</strong></div>
                            <div class="col-sm-8"><span class="badge bg-${getStatusColor(data.asset.status || 'unknown')}">${ucfirst((data.asset.status || 'unknown').replace('_',' '))}</span></div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Date Acquired:</strong></div><div class="col-sm-8">${data.asset.date_acquired ? new Date(data.asset.date_acquired).toLocaleDateString() : 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Assigned Office:</strong></div><div class="col-sm-8">${data.asset.office_name || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Current User:</strong></div><div class="col-sm-8">${data.asset.end_user || 'Unassigned'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Employee ID:</strong></div><div class="col-sm-8">${data.asset.employee_id || 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Last Counted:</strong></div><div class="col-sm-8">${data.asset.date_counted ? new Date(data.asset.date_counted).toLocaleDateString() : 'N/A'}</div></div>
                        <div class="row mb-2"><div class="col-sm-4"><strong>Requested Qty:</strong></div><div class="col-sm-8">${data.request.quantity_requested} unit(s)</div></div>
                        ${data.asset.image ? `<div class="row mb-2"><div class="col-sm-4"><strong>Asset Image:</strong></div><div class="col-sm-8"><button type="button" class="btn btn-sm btn-outline-primary" onclick="viewAssetImage(this)" data-image="${data.asset.image.replace(/"/g, '&quot;')}"><i class="bi bi-image"></i> View Image</button></div></div>` : ''}
                        ` : '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Asset information not available</div>'}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0"><i class="bi bi-clock-history"></i> Request Lifecycle</h6></div>
                    <div class="card-body">
                        <div class="timeline">${lifecycleHtml || '<p class="text-muted">No lifecycle events available</p>'}</div>
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted">
                                <strong>Current Status:</strong> ${currentStatus.title}<br>
                                ${currentStatus.description}<br>
                                <em>Last updated: ${formattedStatusDate}</em>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ---------------------------------------------------------------------------
// Toast / form feedback
// ---------------------------------------------------------------------------

function showFormFeedback(type, message) {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1050';
        document.body.appendChild(toastContainer);
    }

    const bgClass = type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'success';
    const toastId = 'toast_' + Date.now();
    toastContainer.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white bg-${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    // FIX: was `})));` — properly closed now
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// ---------------------------------------------------------------------------
// Field / form validation (module-scope so all callers can reach it)
// ---------------------------------------------------------------------------

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';

    if (field.hasAttribute('required') && !value) {
        isValid = false; message = 'This field is required';
    } else if (field.type === 'number') {
        const num = parseInt(value);
        const max = field.getAttribute('max');
        if (isNaN(num) || num < 1) { isValid = false; message = 'Please enter a valid number'; }
        else if (max && num > parseInt(max)) { isValid = false; message = `Maximum value is ${max}`; }
    } else if (field.type === 'date') {
        const date = new Date(value);
        const today = new Date(); today.setHours(0,0,0,0);
        if (date < today) { isValid = false; message = 'Date cannot be in the past'; }
    }

    if (isValid) {
        field.classList.remove('is-invalid'); field.classList.add('is-valid');
        removeFieldError(field);
    } else {
        field.classList.remove('is-valid'); field.classList.add('is-invalid');
        showFieldError(field, message);
    }
    return isValid;
}

function validateFormRealtime(form) {
    let isValid = true;
    form.querySelectorAll('[required]').forEach(field => {
        if (!validateField(field)) isValid = false;
    });
    const startDate = form.querySelector('#start_date');
    const endDate   = form.querySelector('#end_date');
    if (startDate && endDate && startDate.value && endDate.value &&
        new Date(startDate.value) > new Date(endDate.value)) {
        showFieldError(endDate, 'End date must be after start date');
        isValid = false;
    }
    return isValid;
}

function showFieldError(field, message) {
    removeFieldError(field);
    const div = document.createElement('div');
    div.className = 'invalid-feedback';
    div.textContent = message;
    div.setAttribute('data-error-for', field.id);
    field.parentNode.appendChild(div);
}

function removeFieldError(field) {
    const existing = field.parentNode.querySelector(`[data-error-for="${field.id}"]`);
    if (existing) existing.remove();
}

// ---------------------------------------------------------------------------
// Empty-state helper
// ---------------------------------------------------------------------------

function updateEmptyState() {
    const visibleRows = document.querySelectorAll('.request-row:not(.hidden)');
    const container   = document.getElementById('requestsContainer');
    if (!container) return;
    const emptyState  = container.querySelector('.empty-state');

    if (visibleRows.length === 0 && !emptyState) {
        const div = document.createElement('div');
        div.className = 'empty-state';
        div.innerHTML = `<i class="bi bi-inbox"></i><h5>No Requests Found</h5><p>No requests match the current filter criteria.</p>`;
        container.appendChild(div);
    } else if (visibleRows.length > 0 && emptyState) {
        emptyState.remove();
    }
}

// ---------------------------------------------------------------------------
// Bulk-actions helpers
// ---------------------------------------------------------------------------

let selectedRequests = new Set();

function updateBulkActionsBar() {
    const bar          = document.getElementById('bulkActionsBar');
    const countEl      = document.getElementById('selectedCount');
    const approveBtn   = document.getElementById('bulkApproveBtn');
    const denyBtn      = document.getElementById('bulkDenyBtn');
    const borrowedBtn  = document.getElementById('bulkMarkBorrowedBtn');

    const count = selectedRequests.size;
    if (countEl) countEl.textContent = count;
    if (bar) bar.classList.toggle('d-none', count === 0);

    const hasPendingIn  = Array.from(selectedRequests).some(id => {
        const r = document.querySelector(`tr[data-request-id="${id}"]`);
        return r && r.dataset.type === 'incoming' && r.dataset.status === 'pending';
    });
    const hasApprovedIn = Array.from(selectedRequests).some(id => {
        const r = document.querySelector(`tr[data-request-id="${id}"]`);
        return r && r.dataset.type === 'incoming' && r.dataset.status === 'approved';
    });
    const hasPendingOut = Array.from(selectedRequests).some(id => {
        const r = document.querySelector(`tr[data-request-id="${id}"]`);
        return r && r.dataset.type === 'outgoing' && r.dataset.status === 'pending';
    });

    if (approveBtn)  approveBtn.disabled  = !hasPendingIn;
    if (denyBtn)     denyBtn.disabled     = !hasPendingIn;
    if (borrowedBtn) borrowedBtn.disabled = !hasApprovedIn;

    ['bulkCancelBtn','bulkApproveBtnAll','bulkDenyBtnAll','bulkMarkBorrowedBtnAll','bulkCancelBtnAll'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (id.includes('Cancel'))   el.disabled = !hasPendingOut;
        if (id.includes('Approve'))  el.disabled = !hasPendingIn;
        if (id.includes('Deny'))     el.disabled = !hasPendingIn;
        if (id.includes('Borrowed')) el.disabled = !hasApprovedIn;
    });
}

function updateSelectAllCheckboxState() {
    const selectAll    = document.getElementById('selectAllRequests');
    if (!selectAll) return;
    const visible      = document.querySelectorAll('.request-row:not(.hidden) .request-checkbox');
    const checkedCount = document.querySelectorAll('.request-row:not(.hidden) .request-checkbox:checked').length;

    if (visible.length === 0 || checkedCount === 0) {
        selectAll.checked = false; selectAll.indeterminate = false;
    } else if (checkedCount === visible.length) {
        selectAll.checked = true;  selectAll.indeterminate = false;
    } else {
        selectAll.checked = false; selectAll.indeterminate = true;
    }
}

function selectAllRequests() {
    const selectAll = document.getElementById('selectAllRequests');
    document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = selectAll.checked);
    document.querySelectorAll('.request-row:not(.hidden) .request-checkbox').forEach(cb => {
        const id = parseInt(cb.value, 10);
        selectAll.checked ? selectedRequests.add(id) : selectedRequests.delete(id);
    });
    updateBulkActionsBar();
}

function clearSelection() {
    selectedRequests.clear();
    document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('selectAllRequests');
    if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
    updateBulkActionsBar();
}

function updateBulkActionsButtons(filter) {
    const incoming = document.getElementById('incomingBulkActions');
    const outgoing = document.getElementById('outgoingBulkActions');
    const all      = document.getElementById('allBulkActions');
    if (incoming) incoming.classList.add('d-none');
    if (outgoing) outgoing.classList.add('d-none');
    if (all)      all.classList.add('d-none');
    if (filter === 'needs_action' && incoming) incoming.classList.remove('d-none');
    if (filter === 'waiting'      && outgoing) outgoing.classList.remove('d-none');
    if (filter === 'all'          && all)      all.classList.remove('d-none');
}

// Bulk AJAX actions

function bulkApprove() {
    const ids = Array.from(selectedRequests).filter(id => {
        const r = document.querySelector(`tr[data-request-id="${id}"]`);
        return r && r.dataset.type === 'incoming' && r.dataset.status === 'pending';
    });
    if (!ids.length) { showNotification('No pending incoming requests selected', 'warning'); return; }
    if (!confirm(`Approve ${ids.length} request(s)?`)) return;
    fetch('requests.php', {
        method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ action:'bulk_approve', request_ids: ids.join(',') })
    }).then(r => r.json()).then(data => {
        if (data.success) { showNotification(data.message, 'success'); clearSelection(); setTimeout(() => location.reload(), 1500); }
        else showNotification(data.error || 'Error approving', 'error');
    }).catch(() => showNotification('Error approving requests', 'error'));
}

function bulkDeny() {
    const ids = Array.from(selectedRequests).filter(id => {
        const r = document.querySelector(`tr[data-request-id="${id}"]`);
        return r && r.dataset.type === 'incoming' && r.dataset.status === 'pending';
    });
    if (!ids.length) { showNotification('No pending incoming requests selected', 'warning'); return; }
    const reason = prompt('Enter denial reason:');
    if (!reason) return;
    if (!confirm(`Deny ${ids.length} request(s)?`)) return;
    fetch('requests.php', {
        method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ action:'bulk_deny', request_ids: ids.join(','), reason })
    }).then(r => r.json()).then(data => {
        if (data.success) { showNotification(data.message, 'success'); clearSelection(); setTimeout(() => location.reload(), 1500); }
        else showNotification(data.error || 'Error denying', 'error');
    }).catch(() => showNotification('Error denying requests', 'error'));
}

function bulkMarkBorrowed() {
    const ids = Array.from(selectedRequests).filter(id => {
        const r = document.querySelector(`tr[data-request-id="${id}"]`);
        return r && r.dataset.type === 'incoming' && r.dataset.status === 'approved';
    });
    if (!ids.length) { showNotification('No approved incoming requests selected', 'warning'); return; }
    if (!confirm(`Mark ${ids.length} request(s) as borrowed?`)) return;
    fetch('requests.php', {
        method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ action:'bulk_mark_borrowed', request_ids: ids.join(',') })
    }).then(r => r.json()).then(data => {
        if (data.success) { showNotification(data.message, 'success'); clearSelection(); setTimeout(() => location.reload(), 1500); }
        else showNotification(data.error || 'Error marking as borrowed', 'error');
    }).catch(() => showNotification('Error marking as borrowed', 'error'));
}

function bulkCancel() {
    const ids = Array.from(selectedRequests).filter(id => {
        const r = document.querySelector(`tr[data-request-id="${id}"]`);
        return r && r.dataset.type === 'outgoing' && r.dataset.status === 'pending';
    });
    if (!ids.length) { showNotification('No pending outgoing requests selected', 'warning'); return; }
    if (!confirm(`Cancel ${ids.length} request(s)?`)) return;
    fetch('requests.php', {
        method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ action:'bulk_cancel', request_ids: ids.join(',') })
    }).then(r => r.json()).then(data => {
        if (data.success) { showNotification(data.message, 'success'); clearSelection(); setTimeout(() => location.reload(), 1500); }
        else showNotification(data.error || 'Error cancelling', 'error');
    }).catch(() => showNotification('Error cancelling requests', 'error'));
}

// ---------------------------------------------------------------------------
// Advanced search
// ---------------------------------------------------------------------------

let currentFilters = { text:'', type:'', status:'', dateFrom:'', dateTo:'' };

function applyAdvancedFilters() {
    currentFilters.type     = document.getElementById('filterType')?.value     || '';
    currentFilters.status   = document.getElementById('filterStatus')?.value   || '';
    currentFilters.dateFrom = document.getElementById('filterDateFrom')?.value || '';
    currentFilters.dateTo   = document.getElementById('filterDateTo')?.value   || '';
    applyFilters();
}

function clearAdvancedFilters() {
    ['filterType','filterStatus','filterDateFrom','filterDateTo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const searchInput = document.getElementById('advancedSearchInput');
    if (searchInput) searchInput.value = '';
    currentFilters = { text:'', type:'', status:'', dateFrom:'', dateTo:'' };
    applyFilters();
}

function applyFilters() {
    let visibleCount = 0;
    document.querySelectorAll('.request-row').forEach(row => {
        let matches = true;

        if (currentFilters.text) {
            matches = row.textContent.toLowerCase().includes(currentFilters.text.toLowerCase());
        }
        if (matches && currentFilters.type)   matches = row.dataset.type   === currentFilters.type;
        if (matches && currentFilters.status) matches = row.dataset.status === currentFilters.status;

        if (matches && (currentFilters.dateFrom || currentFilters.dateTo)) {
            const cell = row.querySelector('td:nth-child(6)');
            if (cell) {
                const m = cell.textContent.match(/From:\s+(\w+ \d+, \d+)/);
                if (m) {
                    const d = new Date(m[1]);
                    if (currentFilters.dateFrom && d < new Date(currentFilters.dateFrom)) matches = false;
                    if (currentFilters.dateTo   && d > new Date(currentFilters.dateTo))   matches = false;
                }
            }
        }

        row.classList.toggle('hidden', !matches);
        if (matches) visibleCount++;
    });

    updateSearchResultsCount(visibleCount);
    updateEmptyState();
    updateSelectAllCheckboxState();
}

function updateSearchResultsCount(visibleCount) {
    const el = document.getElementById('searchResultsCount');
    if (!el) return;
    const total = document.querySelectorAll('.request-row').length;
    const hasFilter = Object.values(currentFilters).some(v => v !== '');
    el.textContent = hasFilter ? `Showing ${visibleCount} of ${total} requests` : 'Showing all requests';
}

// ---------------------------------------------------------------------------
// Real-time updates (polling)
// ---------------------------------------------------------------------------

let lastUpdateTime = new Date();
let pollingInterval;

function startRealTimeUpdates() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(checkForUpdates, 30000);
}

function checkForUpdates() {
    fetch('requests.php?action=check_updates&last_update=' + lastUpdateTime.toISOString(),
          { headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(data => {
            if (!data.has_updates) return;
            if (data.stats) updateStatsCards(data.stats);
            if (data.new_requests?.length || data.changed_requests?.length) {
                showNotification('Requests updated — refreshing…', 'info');
                setTimeout(() => location.reload(), 1500);
            }
            lastUpdateTime = new Date();
        })
        .catch(err => console.error('Real-time update error:', err));
}

function updateStatsCards(stats) {
    ['pending_incoming','approved_incoming','borrowed_incoming',
     'pending_outgoing','approved_outgoing','borrowed_outgoing'].forEach(key => {
        const el = document.querySelector(`[data-stat="${key}"]`);
        if (el && stats[key] !== undefined) el.textContent = stats[key];
    });
}

// ---------------------------------------------------------------------------
// Asset details popup (used by the info button in the New Request modal)
// ---------------------------------------------------------------------------

function showAssetDetails() {
    const assetSelect = document.getElementById('asset_id');
    if (!assetSelect || !assetSelect.value) {
        showFormFeedback('warning', 'Please select an asset first');
        return;
    }
    const opt = assetSelect.options[assetSelect.selectedIndex];
    const assetData = {
        description: opt.text.split('-')[0].trim(),
        code:        (opt.text.match(/\(([^)]+)\)/) || [])[1] || '',
        available:   opt.getAttribute('data-available') || '0',
        total:       opt.getAttribute('data-total') || '0',
        office:      (opt.text.split('-')[1] || '').trim()
    };

    const existing = document.getElementById('assetDetailsModal');
    if (existing) existing.remove();

    document.body.insertAdjacentHTML('beforeend', `
        <div class="modal fade" id="assetDetailsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Asset Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p><strong>Asset:</strong> ${assetData.description}</p>
                        <p><strong>Code:</strong> ${assetData.code}</p>
                        <p><strong>Available:</strong> ${assetData.available} units</p>
                        <p><strong>Total:</strong> ${assetData.total} units</p>
                        <p><strong>Office:</strong> ${assetData.office}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `);
    new bootstrap.Modal(document.getElementById('assetDetailsModal')).show();
}

// ---------------------------------------------------------------------------
// Keyboard shortcuts (global)
// ---------------------------------------------------------------------------

document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    if (e.ctrlKey && e.key === 'a') {
        e.preventDefault();
        const sa = document.getElementById('selectAllRequests');
        if (sa) { sa.checked = !sa.checked; selectAllRequests(); }
    }
    if (e.ctrlKey && e.key === 'r') { e.preventDefault(); refreshRequests(); }
    if (e.key === 'Escape') clearSelection();
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        const btn = document.getElementById('bulkApproveBtn');
        if (btn && !btn.disabled) bulkApprove();
    }
});

// ---------------------------------------------------------------------------
// DOMContentLoaded — wire everything up
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {
    console.log('DEBUG: DOMContentLoaded fired');

    // ── Filter tabs ────────────────────────────────────────────────────────
    const filterTabs = document.querySelectorAll('.filter-tab');
    console.log('DEBUG: Found filter tabs:', filterTabs.length);

    function applyTabFilter(filter) {
        document.querySelectorAll('.request-row').forEach(row => {
            let show = true;
            if (filter === 'needs_action') show = row.dataset.needsAction === 'true';
            if (filter === 'waiting')      show = row.dataset.type === 'outgoing';
            row.classList.toggle('hidden', !show);
        });
        updateEmptyState();
        updateSelectAllCheckboxState();
        updateBulkActionsButtons(filter);
        clearSelection();
    }

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            applyTabFilter(this.dataset.filter);
            console.log('DEBUG: Filter applied:', this.dataset.filter,
                '| Visible rows:', document.querySelectorAll('.request-row:not(.hidden)').length);
        });
    });

    // Apply default tab (needs_action) on load
    applyTabFilter('needs_action');

    // ── Checkbox wiring ────────────────────────────────────────────────────
    const selectAll = document.getElementById('selectAllRequests');
    if (selectAll) selectAll.addEventListener('change', selectAllRequests);

    document.querySelectorAll('.request-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            const id = parseInt(this.value, 10);
            this.checked ? selectedRequests.add(id) : selectedRequests.delete(id);
            updateBulkActionsBar();
            updateSelectAllCheckboxState();
        });
    });

    // ── Bulk action buttons ────────────────────────────────────────────────
    const btnMap = {
        bulkApproveBtn:        bulkApprove,
        bulkDenyBtn:           bulkDeny,
        bulkMarkBorrowedBtn:   bulkMarkBorrowed,
        bulkCancelBtn:         bulkCancel,
        bulkApproveBtnAll:     bulkApprove,
        bulkDenyBtnAll:        bulkDeny,
        bulkMarkBorrowedBtnAll:bulkMarkBorrowed,
        bulkCancelBtnAll:      bulkCancel,
    };
    Object.entries(btnMap).forEach(([id, fn]) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', fn);
    });

    // ── Advanced search ────────────────────────────────────────────────────
    const searchInput = document.getElementById('advancedSearchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            currentFilters.text = this.value.trim();
            searchTimeout = setTimeout(applyFilters, 250);
        });
    }

    // ── New-request form ───────────────────────────────────────────────────
    const today         = new Date().toISOString().split('T')[0];
    const startDateEl   = document.getElementById('start_date');
    const endDateEl     = document.getElementById('end_date');
    const quantityEl    = document.getElementById('quantity_requested');
    const quantityInfo  = document.getElementById('quantity_info');
    const assetSelect   = document.getElementById('asset_id');
    const officeSelect  = document.getElementById('requested_to_office');
    const categorySelect= document.getElementById('asset_category');

    if (startDateEl) startDateEl.min = today;
    if (endDateEl)   endDateEl.min   = today;

    if (startDateEl && endDateEl) {
        startDateEl.addEventListener('change', function () {
            endDateEl.min = this.value;
            if (endDateEl.value && endDateEl.value < this.value) endDateEl.value = this.value;
        });
    }

    if (assetSelect && quantityEl && quantityInfo) {
        assetSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const avail = opt.getAttribute('data-available');
            const total = opt.getAttribute('data-total');
            if (avail && total) {
                quantityInfo.textContent = `${avail} of ${total} units available`;
                quantityEl.max = avail;
                if (parseInt(quantityEl.value) > parseInt(avail)) quantityEl.value = avail;
            } else {
                quantityInfo.textContent = 'Select an asset to see available quantity';
                quantityEl.max = '';
            }
        });
        quantityEl.addEventListener('input', function () {
            if (this.max && parseInt(this.value) > parseInt(this.max)) this.value = this.max;
        });
    }

    function filterAssets() {
        if (!assetSelect) return;
        const officeId   = officeSelect?.value   || '';
        const categoryId = categorySelect?.value || '';
        assetSelect.querySelectorAll('option').forEach(opt => {
            if (!opt.value) { opt.style.display = ''; return; }
            const officeMatch    = !officeId   || opt.getAttribute('data-office-id')   === officeId;
            const categoryMatch  = !categoryId || opt.getAttribute('data-category-id') === categoryId;
            opt.style.display = (officeMatch && categoryMatch) ? '' : 'none';
        });
        if (assetSelect.value &&
            assetSelect.options[assetSelect.selectedIndex].style.display === 'none') {
            assetSelect.value = '';
            if (quantityInfo) quantityInfo.textContent = 'Select an asset to see available quantity';
            if (quantityEl)   quantityEl.max = '';
        }
    }

    if (officeSelect)   officeSelect.addEventListener('change',   filterAssets);
    if (categorySelect) categorySelect.addEventListener('change', filterAssets);

    // Character counter
    const purposeEl = document.getElementById('purpose');
    const charCount  = document.getElementById('charCount');
    if (purposeEl && charCount) {
        purposeEl.addEventListener('input', function () {
            const len = Math.min(this.value.length, 500);
            if (this.value.length > 500) this.value = this.value.substring(0, 500);
            charCount.textContent = `${len} / 500`;
            charCount.classList.toggle('text-danger',  len === 500);
            charCount.classList.toggle('text-warning', len < 10 && len > 0);
        });
    }

    // Form submit validation
    const newRequestForm = document.querySelector('#newRequestModal form');
    if (newRequestForm) {
        newRequestForm.addEventListener('submit', function (e) {
            if (!validateFormRealtime(this)) {
                e.preventDefault();
                showFormFeedback('error', 'Please fix the errors before submitting');
            }
        });
        newRequestForm.querySelectorAll('[required]').forEach(field => {
            field.addEventListener('blur',  () => validateField(field));
            field.addEventListener('input', () => {
                if (field.classList.contains('is-invalid')) validateField(field);
            });
        });
    }

    // Progressive disclosure (delivery + urgency)
    const deliveryPref     = document.getElementById('delivery_preference');
    const deliveryLocGroup = document.getElementById('deliveryLocationGroup');
    const urgencyLevel     = document.getElementById('urgency_level');
    const emergencyGroup   = document.getElementById('emergencyReasonGroup');
    const emergencyReason  = document.getElementById('emergency_reason');

    if (deliveryPref && deliveryLocGroup) {
        deliveryPref.addEventListener('change', function () {
            const show = this.value === 'delivery';
            deliveryLocGroup.style.display = show ? 'block' : 'none';
            const inp = deliveryLocGroup.querySelector('input');
            if (inp) show ? inp.setAttribute('required','') : inp.removeAttribute('required');
        });
    }
    if (urgencyLevel && emergencyGroup) {
        urgencyLevel.addEventListener('change', function () {
            const show = this.value === 'emergency';
            emergencyGroup.style.display = show ? 'block' : 'none';
            if (emergencyReason) show
                ? emergencyReason.setAttribute('required','')
                : emergencyReason.removeAttribute('required');
        });
    }

    // Additional-options collapse chevron
    const addOpts = document.getElementById('additionalOptions');
    const addBtn  = document.querySelector('[data-bs-target="#additionalOptions"]');
    if (addOpts && addBtn) {
        addOpts.addEventListener('show.bs.collapse', () => {
            addBtn.innerHTML = '<i class="bi bi-chevron-up"></i> Additional Options <small class="text-muted">(Expanded)</small>';
        });
        addOpts.addEventListener('hide.bs.collapse', () => {
            addBtn.innerHTML = '<i class="bi bi-chevron-down"></i> Additional Options <small class="text-muted">(Optional)</small>';
        });
    }

    // Start polling
    startRealTimeUpdates();

    // ---------------------------------------------------------------------------
    // Asset Table Functionality
    // ---------------------------------------------------------------------------

    // Asset table selection and filtering
    const requestedToOffice = document.getElementById('requested_to_office');
    const assetCategory = document.getElementById('asset_category');
    const assetsTableBody = document.getElementById('assetsTableBody');
    const assetIdHidden = document.getElementById('asset_id');
    const selectedAssetDisplay = document.getElementById('selectedAssetDisplay');
    const selectedAssetInfo = document.getElementById('selectedAssetInfo');
    const assetAvailability = document.getElementById('assetAvailability');

    // Filter assets based on office and category selection
    function filterAssetTable() {
        const officeId = requestedToOffice?.value || '';
        const categoryId = assetCategory?.value || '';
        
        console.log('DEBUG: Filtering assets - Office ID:', officeId, 'Category ID:', categoryId);
        
        const rows = document.querySelectorAll('.asset-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const rowOfficeId = row.dataset.officeId || '';
            const rowCategoryId = row.dataset.categoryId || '';
            
            const officeMatch = !officeId || rowOfficeId === officeId;
            const categoryMatch = !categoryId || rowCategoryId === categoryId;
            
            const isVisible = officeMatch && categoryMatch;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) visibleCount++;
            
            console.log('DEBUG: Asset row - Row Office:', rowOfficeId, 'Filter Office:', officeId, 'Match:', officeMatch, 'Visible:', isVisible);
        });
        
        console.log('DEBUG: Visible assets count:', visibleCount);
        
        // Show message if no results
        const noResultsRow = assetsTableBody.querySelector('.no-results-row');
        if (visibleCount === 0 && !noResultsRow) {
            const noRow = document.createElement('tr');
            noRow.className = 'no-results-row';
            noRow.innerHTML = `
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                    No assets found matching your criteria.
                </td>
            `;
            assetsTableBody.appendChild(noRow);
        } else if (visibleCount > 0 && noResultsRow) {
            noResultsRow.remove();
        }
        
        // Clear selection if no assets are visible
        if (visibleCount === 0) {
            assetIdHidden.value = '';
            selectedAssetDisplay.classList.add('d-none');
            if (quantityInfo) quantityInfo.textContent = 'Select an asset to see available quantity';
            if (quantityEl) quantityEl.max = '';
        }
    }

    // Handle asset row selection
    function selectAssetRow(row) {
        // Remove previous selection
        document.querySelectorAll('.asset-row').forEach(r => {
            r.classList.remove('table-primary');
        });
        
        // Add selection to clicked row
        row.classList.add('table-primary');
        
        // Get asset data
        const assetId = row.dataset.assetId;
        const description = row.dataset.description;
        const assetCode = row.dataset.assetCode;
        const officeName = row.dataset.officeName;
        const available = row.dataset.available;
        const total = row.dataset.total;
        
        // Update hidden input
        assetIdHidden.value = assetId;
        
        // Update selected asset display
        selectedAssetInfo.innerHTML = `
            <strong>${description}</strong> (${assetCode})<br>
            <small class="text-muted">
                <i class="bi bi-building"></i> ${officeName} | 
                <i class="bi bi-box"></i> ${available} of ${total} available
            </small>
        `;
        selectedAssetDisplay.classList.remove('d-none');
        
        // Update quantity info
        if (quantityInfo) {
            quantityInfo.textContent = `${available} of ${total} units available`;
        }
        if (quantityEl) {
            quantityEl.max = available;
            if (parseInt(quantityEl.value) > parseInt(available)) {
                quantityEl.value = available;
            }
        }
        
        // Check radio button
        const radio = row.querySelector('.asset-selector');
        if (radio) radio.checked = true;
    }

    // Add click handlers to asset rows
    document.querySelectorAll('.asset-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't select if clicking on radio button directly
            if (!e.target.classList.contains('asset-selector')) {
                selectAssetRow(this);
            }
        });
        
        // Handle radio button change
        const radio = row.querySelector('.asset-selector');
        if (radio) {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    selectAssetRow(row);
                }
            });
        }
    });

    // Add event listeners for filters
    if (requestedToOffice) requestedToOffice.addEventListener('change', filterAssetTable);
    if (assetCategory) assetCategory.addEventListener('change', filterAssetTable);

    // Clear selection when modal is hidden
    const newRequestModal = document.getElementById('newRequestModal');
    if (newRequestModal) {
        newRequestModal.addEventListener('hidden.bs.modal', function() {
            // Reset table selection
            document.querySelectorAll('.asset-row').forEach(row => {
                row.classList.remove('table-primary');
            });
            
            // Reset form elements
            if (requestedToOffice) requestedToOffice.value = '';
            if (assetCategory) assetCategory.value = '';
            if (assetIdHidden) assetIdHidden.value = '';
            if (selectedAssetDisplay) selectedAssetDisplay.classList.add('d-none');
            if (quantityInfo) quantityInfo.textContent = 'Select an asset to see available quantity';
            if (quantityEl) quantityEl.max = '';
            
            filterAssetTable();
        });
    }

    console.log('DEBUG: Init complete');
});

// ---------------------------------------------------------------------------
// Asset Image Viewer
// ---------------------------------------------------------------------------

function viewAssetImage(button) {
    try {
        // Get image data from data attribute
        const imageData = button.getAttribute('data-image');
        if (!imageData) {
            alert('No image data available');
            return;
        }
        
        let imageUrls = [];
        
        // Parse the image data (it might be a JSON array string or a single image path)
        if (typeof imageData === 'string') {
            try {
                imageUrls = JSON.parse(imageData);
                if (!Array.isArray(imageUrls)) {
                    imageUrls = [imageData];
                }
            } catch (e) {
                // If it's not valid JSON, treat it as a single image path
                imageUrls = [imageData];
            }
        } else if (Array.isArray(imageData)) {
            imageUrls = imageData;
        } else {
            imageUrls = [imageData];
        }
        
        if (imageUrls.length === 0) {
            alert('No images available for this asset');
            return;
        }
        
        // Create modal HTML
        let modalHtml = `
            <div class="modal fade" id="assetImageModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="bi bi-image"></i> Asset Images</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div id="imageCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
        `;
        
        // Add images to carousel
        imageUrls.forEach((imageUrl, index) => {
            const fullPath = imageUrl.startsWith('http') ? imageUrl : `../uploads/asset_images/${imageUrl}`;
            const isActive = index === 0 ? 'active' : '';
            
            modalHtml += `
                <div class="carousel-item ${isActive}">
                    <img src="${fullPath}" class="img-fluid rounded" style="max-height: 500px; object-fit: contain;" 
                         onerror="this.src='../img/no-image.png'; this.onerror=null;" 
                         alt="Asset Image ${index + 1}">
                    <div class="carousel-caption">
                        <h6>Image ${index + 1} of ${imageUrls.length}</h6>
                    </div>
                </div>
            `;
        });
        
        // Add carousel controls if multiple images
        if (imageUrls.length > 1) {
            modalHtml += `
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            `;
        } else {
            modalHtml += `
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            `;
        }
        
        // Remove existing modal if present
        const existingModal = document.getElementById('assetImageModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to page and show it
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('assetImageModal'));
        modal.show();
        
        // Clean up modal after it's hidden
        document.getElementById('assetImageModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
        
    } catch (error) {
        console.error('Error viewing asset image:', error);
        alert('Error loading asset image. Please try again.');
    }
}

    </script>
    
    <!-- Bootstrap-based Notification Script -->
    <?php require_once 'includes/notification_script_bootstrap.php'; ?>
    
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
    
    <!-- Page Footer -->
    <footer class="page-footer">
        <div class="footer-content">
            <div class="footer-spacer"></div>
            <div class="footer-info">
                <small class="text-muted">
                    © <?php echo date('Y'); ?> PIMS - Pilar Inventory Management System
                </small>
            </div>
        </div>
    </footer>
</body>
</html>