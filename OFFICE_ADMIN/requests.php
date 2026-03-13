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

// Set page title for topbar
$page_title = 'Requests Management';

// Get office ID from session
$office_id = $_SESSION['office_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_request':
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
            } else {
                try {
                    // Check if asset exists and get available quantity
                    $asset_check = "SELECT ai.status, COALESCE(a.quantity, 1) as total_quantity,
                                   COALESCE(a.quantity, 1) as available_quantity
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
                        
                        if ($asset_data['status'] !== 'serviceable') {
                            $_SESSION['error'] = "Asset is not available";
                        } elseif ($quantity_requested > $asset_data['available_quantity']) {
                            $_SESSION['error'] = "Only {$asset_data['available_quantity']} units available. You requested {$quantity_requested}.";
                        } else {
                            // Insert new borrow request
                            $insert_query = "INSERT INTO borrow_requests 
                                             (requested_by, requested_by_office, requested_to_office, asset_id, quantity_requested, purpose, start_date, end_date) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $conn->prepare($insert_query);
                            $stmt->bind_param("iiisisss", $_SESSION['user_id'], $office_id, $requested_to_office, $asset_id, $quantity_requested, $purpose, $start_date, $end_date);
                            
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
            
            // Set proper content type and output clean JSON
            header('Content-Type: application/json');
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
        $stmt = $conn->prepare($incoming_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $incoming_requests[] = $row;
            
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
        $stmt = $conn->prepare($outgoing_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $outgoing_requests[] = $row;
            
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
        
    } catch (Exception $e) {
        error_log("Error fetching requests: " . $e->getMessage());
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
        $assets_query = "SELECT ai.id, ai.description, COALESCE(ai.property_number, ai.property_no) as asset_code, ac.category_name, o.office_name, o.id as office_id,
                         COALESCE(a.quantity, 1) as total_quantity,
                         COALESCE(a.quantity, 1) as available_quantity,
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> New Borrow Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_request">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="requested_to_office" class="form-label">Request To Office <span class="text-danger">*</span></label>
                                    <select class="form-control" id="requested_to_office" name="requested_to_office" required>
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
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_category" class="form-label">Asset Category</label>
                                    <select class="form-control" id="asset_category" name="asset_category">
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
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asset_id" class="form-label">Asset to Borrow <span class="text-danger">*</span></label>
                                    <select class="form-control" id="asset_id" name="asset_id" required>
                                        <option value="">Select Asset</option>
                                        <?php foreach ($available_assets as $asset): ?>
                                            <option value="<?php echo $asset['id']; ?>" 
                                                    data-office-id="<?php echo $asset['office_id']; ?>" 
                                                    data-category-id="<?php echo $asset['category_id']; ?>"
                                                    data-available="<?php echo $asset['available_quantity']; ?>" 
                                                    data-total="<?php echo $asset['total_quantity']; ?>">
                                                <?php echo htmlspecialchars($asset['description']); ?> (<?php echo htmlspecialchars($asset['asset_code']); ?>)
                                                - <?php echo htmlspecialchars($asset['office_name']); ?>
                                                <small class="text-muted">(<?php echo $asset['available_quantity']; ?> of <?php echo $asset['total_quantity']; ?> available)</small>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Only available assets from other offices are shown</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="quantity_requested" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity_requested" name="quantity_requested" min="1" value="1" required>
                                    <small class="text-muted" id="quantity_info">Select an asset to see available quantity</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="purpose" class="form-label">Purpose of Borrowing <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="purpose" name="purpose" rows="3" 
                                            placeholder="Please describe the purpose for borrowing this asset..." required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Request
                        </button>
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
        // Approve Request
        function approveRequest(requestId) {
            document.getElementById('approveRequestId').value = requestId;
            new bootstrap.Modal(document.getElementById('approveModal')).show();
        }
        
        // Deny Request
        function denyRequest(requestId) {
            document.getElementById('denyRequestId').value = requestId;
            new bootstrap.Modal(document.getElementById('denyModal')).show();
        }
        
        // Return Asset
        function returnAsset(requestId) {
            document.getElementById('returnRequestId').value = requestId;
            new bootstrap.Modal(document.getElementById('returnModal')).show();
        }
        
        // Mark Borrowed
        function markBorrowed(requestId) {
            if (confirm('Are you sure you want to mark this asset as borrowed? This means the borrower has picked up the asset.')) {
                const formData = new FormData();
                formData.append('action', 'mark_borrowed');
                formData.append('request_id', requestId);
                
                fetch('requests.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Reload the page to show updated status
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error marking as borrowed:', error);
                    alert('Error marking as borrowed: ' + error.message);
                });
            }
        }
        
        // View Details
        function viewDetails(requestId) {
            fetch(`../api/get_request_details_simple.php?request_id=${requestId}`)
                .then(response => {
                    // Check if response is actually JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // Clone response to read it as text for debugging
                        return response.clone().text().then(text => {
                            console.error('Expected JSON but got:', text.substring(0, 200));
                            throw new Error('Server returned non-JSON response. Check console for details.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    populateDetailsModal(data);
                    new bootstrap.Modal(document.getElementById('detailsModal')).show();
                })
                .catch(error => {
                    console.error('Error fetching request details:', error);
                    alert('Error loading request details: ' + error.message);
                });
        }
        
        // Cancel Request
        function cancelRequest(requestId) {
            if (confirm('Are you sure you want to cancel this request?')) {
                const formData = new FormData();
                formData.append('request_id', requestId);
                
                fetch('../api/cancel_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Request cancelled successfully');
                        // Reload the page to show updated status
                        window.location.reload();
                    } else {
                        alert('Error cancelling request: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error cancelling request:', error);
                    alert('Error cancelling request: ' + error.message);
                });
            }
        }
        
        // Populate Details Modal
        function populateDetailsModal(data) {
            const modalBody = document.getElementById('detailsModalBody');
            
            // Build lifecycle HTML
            let lifecycleHtml = '';
            if (data.lifecycle && data.lifecycle.events) {
                data.lifecycle.events.forEach((event, index) => {
                    const icon = getEventIcon(event.type);
                    const color = getEventColor(event.type);
                    const date = new Date(event.timestamp);
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    
                    lifecycleHtml += `
                        <div class="timeline-item">
                            <div class="timeline-marker ${color}">
                                <i class="bi ${icon}"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">${event.title}</h6>
                                <p class="mb-1 text-muted small">${event.description}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        ${event.user ? `<strong>${event.user}</strong><br><small class="text-muted">${event.user_email}</small><br><small class="text-info">${event.office}</small>` : ''}
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
            
            // Current status indicator with fallback
            const currentStatus = (data.lifecycle && data.lifecycle.current_status) ? data.lifecycle.current_status : {
                status: data.request.status,
                title: ucfirst(data.request.status),
                description: getStatusDescription(data.request.status),
                timestamp: data.request.created_at
            };
            const statusBadge = getStatusBadge(currentStatus.status);
            const statusDate = new Date(currentStatus.timestamp);
            const formattedStatusDate = statusDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            modalBody.innerHTML = `
                <div class="row">
                    <!-- Request Overview -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Request Overview</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Request ID:</strong></div>
                                    <div class="col-sm-8">#${data.request.id}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Status:</strong></div>
                                    <div class="col-sm-8">${statusBadge}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Quantity:</strong></div>
                                    <div class="col-sm-8">${data.request.quantity_requested} unit(s)</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Purpose:</strong></div>
                                    <div class="col-sm-8">${data.request.purpose}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Duration:</strong></div>
                                    <div class="col-sm-8">
                                        From: ${new Date(data.request.start_date).toLocaleDateString()}<br>
                                        To: ${new Date(data.request.end_date).toLocaleDateString()}
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Created:</strong></div>
                                    <div class="col-sm-8">${new Date(data.request.created_at).toLocaleDateString()}</div>
                                </div>
                                ${data.request.approval_notes ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Approval Notes:</strong></div>
                                    <div class="col-sm-8">${data.request.approval_notes}</div>
                                </div>
                                ` : ''}
                                ${data.request.denial_reason ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Denial Reason:</strong></div>
                                    <div class="col-sm-8">${data.request.denial_reason}</div>
                                </div>
                                ` : ''}
                                ${data.request.return_condition ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Return Condition:</strong></div>
                                    <div class="col-sm-8">${data.request.return_condition}</div>
                                </div>
                                ` : ''}
                                ${data.request.return_notes ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Return Notes:</strong></div>
                                    <div class="col-sm-8">${data.request.return_notes}</div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Offices Information -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-building"></i> Offices Involved</h6>
                            </div>
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
                                    ${data.approver.office.users && data.approver.office.users.length > 0 ? `
                                        <div class="mt-2">
                                            <small class="text-info"><strong>Office Users:</strong></small>
                                            <div class="mt-1" style="max-height: 120px; overflow-y: auto;">
                                                ${data.approver.office.users.map(user => 
                                                    `<div class="small text-muted border-bottom pb-1 mb-1">
                                                        <strong>${user.name}</strong><br>
                                                        <small class="text-muted">${user.email}</small>
                                                    </div>`
                                                ).join('')}
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Asset Information -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-box-seam"></i> Asset Information</h6>
                            </div>
                            <div class="card-body">
                                ${data.asset ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Description:</strong></div>
                                    <div class="col-sm-8">${data.asset.description || 'N/A'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Property No:</strong></div>
                                    <div class="col-sm-8">${data.asset.code || 'N/A'}</div>
                                </div>
                                ${data.asset.serial_number ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Serial No:</strong></div>
                                    <div class="col-sm-8">${data.asset.serial_number}</div>
                                </div>
                                ` : ''}
                                ${data.asset.model ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Model:</strong></div>
                                    <div class="col-sm-8">${data.asset.model}</div>
                                </div>
                                ` : ''}
                                ${data.asset.brand ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Brand:</strong></div>
                                    <div class="col-sm-8">${data.asset.brand}</div>
                                </div>
                                ` : ''}
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Category:</strong></div>
                                    <div class="col-sm-8">${data.asset.category.name || 'Uncategorized'} (${data.asset.category.code || 'N/A'})</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Unit:</strong></div>
                                    <div class="col-sm-8">${data.asset.unit || 'N/A'}</div>
                                </div>
                                ${data.asset.inventory_tag ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Inventory Tag:</strong></div>
                                    <div class="col-sm-8">${data.asset.inventory_tag}</div>
                                </div>
                                ` : ''}
                                ${data.asset.unit_value && data.asset.unit_value > 0 ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Unit Value:</strong></div>
                                    <div class="col-sm-8">₱${parseFloat(data.asset.unit_value).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                </div>
                                ` : ''}
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Current Status:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="badge bg-${getStatusColor(data.asset.status || 'unknown')}">${ucfirst((data.asset.status || 'unknown').replace('_', ' '))}</span>
                                    </div>
                                </div>
                                ${data.asset.date_acquired ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Date Acquired:</strong></div>
                                    <div class="col-sm-8">${new Date(data.asset.date_acquired).toLocaleDateString()}</div>
                                </div>
                                ` : ''}
                                ${data.asset.end_user ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Current End User:</strong></div>
                                    <div class="col-sm-8">${data.asset.end_user}</div>
                                </div>
                                ` : ''}
                                ${data.asset.employee_id ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Employee ID:</strong></div>
                                    <div class="col-sm-8">${data.asset.employee_id}</div>
                                </div>
                                ` : ''}
                                ${data.asset.office_name ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Asset Office:</strong></div>
                                    <div class="col-sm-8">${data.asset.office_name}</div>
                                </div>
                                ` : ''}
                                ${data.asset.date_counted ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Last Counted:</strong></div>
                                    <div class="col-sm-8">${new Date(data.asset.date_counted).toLocaleDateString()}</div>
                                </div>
                                ` : ''}
                                ${data.asset.last_updated ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Last Updated:</strong></div>
                                    <div class="col-sm-8">${new Date(data.asset.last_updated).toLocaleDateString()}</div>
                                </div>
                                ` : ''}
                                ${data.asset.qr_code ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>QR Code:</strong></div>
                                    <div class="col-sm-8">
                                        <small class="text-muted">Generated</small>
                                        ${data.asset.qr_code && data.asset.qr_code.endsWith('.png') ? `<br><img src="../${data.asset.qr_code}" alt="QR Code" style="max-width: 100px; height: auto;">` : ''}
                                    </div>
                                </div>
                                ` : ''}
                                ${data.asset.image ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Asset Image:</strong></div>
                                    <div class="col-sm-8">
                                        <img src="../${data.asset.image}" alt="Asset Image" style="max-width: 150px; height: auto; border-radius: 4px;">
                                    </div>
                                </div>
                                ` : ''}
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Requested Quantity:</strong></div>
                                    <div class="col-sm-8">${data.request.quantity_requested} unit(s)</div>
                                </div>
                                ` : `
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Asset information not available
                                </div>
                                `}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Request Lifecycle -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Request Lifecycle</h6>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    ${lifecycleHtml || '<p class="text-muted">No lifecycle events available</p>'}
                                </div>
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
        
        // Helper functions
        function getEventIcon(type) {
            const icons = {
                'created': 'bi-send',
                'approved': 'bi-check-circle',
                'borrowed': 'bi-hand-index',
                'denied': 'bi-x-circle',
                'returned': 'bi-arrow-return-left'
            };
            return icons[type] || 'bi-circle';
        }
        
        function getEventColor(type) {
            const colors = {
                'created': 'primary',
                'approved': 'success',
                'borrowed': 'warning',
                'denied': 'danger',
                'returned': 'info'
            };
            return colors[type] || 'secondary';
        }
        
        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning">Pending</span>',
                'approved': '<span class="badge bg-success">Approved</span>',
                'borrowed': '<span class="badge bg-warning">Borrowed</span>',
                'denied': '<span class="badge bg-danger">Denied</span>',
                'returned': '<span class="badge bg-info">Returned</span>',
                'cancelled': '<span class="badge bg-secondary">Cancelled</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
        }
        
        function ucfirst(str) {
            if (!str || typeof str !== 'string') return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function getStatusDescription(status) {
            const descriptions = {
                'pending': 'Request is awaiting approval',
                'approved': 'Request has been approved and ready for pickup',
                'borrowed': 'Asset has been picked up and is in use',
                'denied': 'Request has been denied',
                'returned': 'Asset has been returned',
                'cancelled': 'Request has been cancelled'
            };
            return descriptions[status] || 'Unknown status';
        }
        
        function getStatusColor(status) {
            const colors = {
                'serviceable': 'success',
                'in_use': 'primary',
                'available': 'info',
                'maintenance': 'warning',
                'disposed': 'secondary',
                'unserviceable': 'danger',
                'no_tag': 'secondary',
                'pending_tag': 'warning',
                'red_tagged': 'danger'
            };
            return colors[status] || 'secondary';
        }
        
        // Set minimum date to today for date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const quantityInput = document.getElementById('quantity_requested');
            const quantityInfo = document.getElementById('quantity_info');
            const assetSelect = document.getElementById('asset_id');
            
            if (startDateInput) {
                startDateInput.min = today;
            }
            
            if (endDateInput) {
                endDateInput.min = today;
            }
            
            // Ensure end date is after start date
            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', function() {
                    endDateInput.min = this.value;
                    if (endDateInput.value && endDateInput.value < this.value) {
                        endDateInput.value = this.value;
                    }
                });
            }
            
            // Update quantity info when asset is selected
            if (assetSelect && quantityInput && quantityInfo) {
                assetSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const availableQuantity = selectedOption.getAttribute('data-available');
                    const totalQuantity = selectedOption.getAttribute('data-total');
                    
                    if (availableQuantity && totalQuantity) {
                        quantityInfo.textContent = `${availableQuantity} of ${totalQuantity} units available`;
                        quantityInput.max = availableQuantity;
                        
                        // Reset quantity if it exceeds available amount
                        if (quantityInput.value > parseInt(availableQuantity)) {
                            quantityInput.value = availableQuantity;
                        }
                    } else {
                        quantityInfo.textContent = 'Select an asset to see available quantity';
                        quantityInput.max = '';
                    }
                });
                
                // Validate quantity input
                quantityInput.addEventListener('input', function() {
                    const max = this.max;
                    if (max && parseInt(this.value) > parseInt(max)) {
                        this.value = max;
                    }
                });
            }
            
            // Filter assets based on selected office and category
            const officeSelect = document.getElementById('requested_to_office');
            const categorySelect = document.getElementById('asset_category');
            
            function filterAssets() {
                const selectedOfficeId = officeSelect.value;
                const selectedCategoryId = categorySelect.value;
                const options = assetSelect.querySelectorAll('option');
                
                options.forEach(option => {
                    if (option.value === '') {
                        option.style.display = 'block';
                        return;
                    }
                    
                    // Get office ID and category ID from asset option
                    const assetOfficeId = option.getAttribute('data-office-id');
                    const assetCategoryId = option.getAttribute('data-category-id');
                    
                    const officeMatch = selectedOfficeId === '' || assetOfficeId === selectedOfficeId;
                    const categoryMatch = selectedCategoryId === '' || assetCategoryId === selectedCategoryId;
                    
                    if (officeMatch && categoryMatch) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                // Reset asset selection if current selection is hidden
                if (assetSelect.value && assetSelect.options[assetSelect.selectedIndex].style.display === 'none') {
                    assetSelect.value = '';
                    quantityInfo.textContent = 'Select an asset to see available quantity';
                    quantityInput.max = '';
                }
            }
            
            if (officeSelect && assetSelect) {
                officeSelect.addEventListener('change', filterAssets);
            }
            
            if (categorySelect && assetSelect) {
                categorySelect.addEventListener('change', filterAssets);
            }
        });
        
        // Smart Filter Functionality
        function initSmartFilters() {
            const filterTabs = document.querySelectorAll('.filter-tab');
            const requestRows = document.querySelectorAll('.request-row');
            
            // Apply initial filter for "needs_action" on page load
            applyInitialFilter();
            
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    
                    // Update active tab
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter requests
                    requestRows.forEach(row => {
                        row.classList.remove('hidden');
                        
                        switch(filter) {
                            case 'needs_action':
                                // Show only incoming pending requests that need action
                                if (row.dataset.needsAction !== 'true') {
                                    row.classList.add('hidden');
                                }
                                break;
                            case 'waiting':
                                // Show only outgoing requests (waiting for others' action)
                                if (row.dataset.type === 'incoming') {
                                    row.classList.add('hidden');
                                }
                                break;
                            case 'all':
                                // Show all requests
                                break;
                        }
                    });
                    
                    updateEmptyState();
                    updateSelectAllCheckboxState();
                    updateBulkActionsButtons(filter);
                });
            });
        }
        
        function applyInitialFilter() {
            // Apply the initial "needs_action" filter when page loads
            const requestRows = document.querySelectorAll('.request-row');
            requestRows.forEach(row => {
                if (row.dataset.needsAction !== 'true') {
                    row.classList.add('hidden');
                }
            });
            updateEmptyState();
            updateSelectAllCheckboxState();
            updateBulkActionsButtons('needs_action');
        }
        
        // Quick Action Functions
        function quickApprove(requestId) {
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
            const reason = prompt('Please enter the reason for denial:');
            if (reason) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="deny_request">
                    <input type="hidden" name="request_id" value="${requestId}">
                    <input type="hidden" name="denial_reason" value="${reason}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function quickMarkBorrowed(requestId) {
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
        
        function cancelRequest(requestId) {
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
        
        function updateEmptyState() {
            const visibleRows = document.querySelectorAll('.request-row:not(.hidden)');
            const container = document.getElementById('requestsContainer');
            const emptyState = container.querySelector('.empty-state');
            
            if (visibleRows.length === 0 && !emptyState) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                emptyDiv.innerHTML = `
                    <i class="bi bi-inbox"></i>
                    <h5>No Requests Found</h5>
                    <p>No requests match the current filter criteria.</p>
                `;
                container.appendChild(emptyDiv);
            } else if (visibleRows.length > 0 && emptyState) {
                emptyState.remove();
            }
        }
        
        // Real-time Updates Functionality
        let lastUpdateTime = new Date();
        let pollingInterval;
        
        function startRealTimeUpdates() {
            // Clear any existing interval
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            
            // Poll every 30 seconds
            pollingInterval = setInterval(checkForUpdates, 30000);
        }
        
        function stopRealTimeUpdates() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        }
        
        function checkForUpdates() {
            fetch('requests.php?action=check_updates&last_update=' + lastUpdateTime.toISOString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Log the response status and text for debugging
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    // Try to parse JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text that failed to parse:', text);
                        throw e;
                    }
                });
            })
            .then(data => {
                if (data.has_updates) {
                    showNotification('New requests or status changes detected', 'info');
                    
                    // Update request counts in stats cards
                    if (data.stats) {
                        updateStatsCards(data.stats);
                    }
                    
                    // Update table with new data
                    if (data.new_requests || data.changed_requests) {
                        updateRequestTable(data);
                    }
                    
                    lastUpdateTime = new Date();
                }
            })
            .catch(error => {
                console.error('Real-time update error:', error);
            });
        }
        
        function updateStatsCards(newStats) {
            // Update incoming stats
            const pendingIncomingEl = document.querySelector('[data-stat="pending_incoming"]');
            const approvedIncomingEl = document.querySelector('[data-stat="approved_incoming"]');
            const borrowedIncomingEl = document.querySelector('[data-stat="borrowed_incoming"]');
            
            if (pendingIncomingEl && newStats.pending_incoming !== undefined) {
                pendingIncomingEl.textContent = newStats.pending_incoming;
            }
            if (approvedIncomingEl && newStats.approved_incoming !== undefined) {
                approvedIncomingEl.textContent = newStats.approved_incoming;
            }
            if (borrowedIncomingEl && newStats.borrowed_incoming !== undefined) {
                borrowedIncomingEl.textContent = newStats.borrowed_incoming;
            }
            
            // Update outgoing stats
            const pendingOutgoingEl = document.querySelector('[data-stat="pending_outgoing"]');
            const approvedOutgoingEl = document.querySelector('[data-stat="approved_outgoing"]');
            const borrowedOutgoingEl = document.querySelector('[data-stat="borrowed_outgoing"]');
            
            if (pendingOutgoingEl && newStats.pending_outgoing !== undefined) {
                pendingOutgoingEl.textContent = newStats.pending_outgoing;
            }
            if (approvedOutgoingEl && newStats.approved_outgoing !== undefined) {
                approvedOutgoingEl.textContent = newStats.approved_outgoing;
            }
            if (borrowedOutgoingEl && newStats.borrowed_outgoing !== undefined) {
                borrowedOutgoingEl.textContent = newStats.borrowed_outgoing;
            }
        }
        
        function updateRequestTable(data) {
            if (data.new_requests && data.new_requests.length > 0) {
                data.new_requests.forEach(request => {
                    addRequestRow(request);
                });
                showNotification(`${data.new_requests.length} new request(s) received`, 'success');
            }
            
            if (data.changed_requests && data.changed_requests.length > 0) {
                data.changed_requests.forEach(change => {
                    updateRequestRow(change);
                });
                showNotification(`${data.changed_requests.length} request(s) updated`, 'info');
            }
        }
        
        function addRequestRow(request) {
            const tbody = document.querySelector('#requestsTable tbody');
            if (!tbody) return;
            
            const row = createRequestRow(request);
            tbody.insertBefore(row, tbody.firstChild);
            
            // Animate new row
            row.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                row.style.transition = 'background-color 2s';
                row.style.backgroundColor = '';
            }, 100);
        }
        
        function updateRequestRow(change) {
            const row = document.querySelector(`tr[data-request-id="${change.id}"]`);
            if (!row) return;
            
            // Update status if changed
            if (change.status) {
                const statusCell = row.querySelector('.status-badge');
                if (statusCell) {
                    statusCell.className = `status-badge status-${change.status}`;
                    statusCell.textContent = change.status.charAt(0).toUpperCase() + change.status.slice(1);
                }
                
                // Update row data attributes
                row.dataset.status = change.status;
                row.dataset.needsAction = (change.type === 'incoming' && change.status === 'pending') ? 'true' : 'false';
            }
            
            // Animate updated row
            row.style.backgroundColor = '#fff3cd';
            setTimeout(() => {
                row.style.transition = 'background-color 2s';
                row.style.backgroundColor = '';
            }, 100);
        }
        
        function createRequestRow(request) {
            // This would need to generate the same HTML structure as existing rows
            // For now, we'll just reload the page to show new requests
            setTimeout(() => location.reload(), 2000);
            return null;
        }
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Only when not typing in input fields
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            // Ctrl+A to select all
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                const selectAll = document.getElementById('selectAllRequests');
                if (selectAll) {
                    selectAll.checked = !selectAll.checked;
                    selectAllRequests();
                }
            }
            
            // Ctrl+R to refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshRequests();
            }
            
            // Escape to clear selection
            if (e.key === 'Escape') {
                clearSelection();
            }
            
            // Ctrl+Enter to bulk approve selected pending requests
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                const bulkApproveBtn = document.getElementById('bulkApproveBtn');
                if (bulkApproveBtn && !bulkApproveBtn.disabled) {
                    bulkApprove();
                }
            }
        });
        
        // Advanced Search Functionality
        let searchTimeout;
        let currentFilters = {
            text: '',
            type: '',
            status: '',
            dateFrom: '',
            dateTo: ''
        };
        
        function initAdvancedSearch() {
            const searchInput = document.getElementById('advancedSearchInput');
            if (searchInput) {
                // Real-time search with debounce
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        currentFilters.text = this.value.toLowerCase();
                        performAdvancedSearch();
                    }, 300);
                });
                
                // Clear search on escape
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        currentFilters.text = '';
                        performAdvancedSearch();
                    }
                });
            }
        }
        
        function applyAdvancedFilters() {
            currentFilters.type = document.getElementById('filterType')?.value || '';
            currentFilters.status = document.getElementById('filterStatus')?.value || '';
            currentFilters.dateFrom = document.getElementById('filterDateFrom')?.value || '';
            currentFilters.dateTo = document.getElementById('filterDateTo')?.value || '';
            
            performAdvancedSearch();
            updateSearchResultsCount();
        }
        
        function clearAdvancedFilters() {
            currentFilters = {
                text: '',
                type: '',
                status: '',
                dateFrom: '',
                dateTo: ''
            };
            
            // Reset form fields
            document.getElementById('advancedSearchInput').value = '';
            document.getElementById('filterType').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            
            performAdvancedSearch();
            updateSearchResultsCount();
        }
        
        function performAdvancedSearch() {
            const rows = document.querySelectorAll('#requestsTable tbody tr.request-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                let matches = true;
                
                // Text search (asset, requester, purpose, status)
                if (currentFilters.text) {
                    const textContent = row.textContent.toLowerCase();
                    matches = textContent.includes(currentFilters.text);
                }
                
                // Type filter
                if (matches && currentFilters.type) {
                    matches = row.dataset.type === currentFilters.type;
                }
                
                // Status filter
                if (matches && currentFilters.status) {
                    matches = row.dataset.status === currentFilters.status;
                }
                
                // Date range filter
                if (matches && (currentFilters.dateFrom || currentFilters.dateTo)) {
                    const durationCell = row.querySelector('td:nth-child(5)');
                    if (durationCell) {
                        const dateText = durationCell.textContent;
                        const dateMatch = dateText.match(/From: (\w+ \d+, \d+)/);
                        if (dateMatch) {
                            const requestDate = new Date(dateMatch[1]);
                            const fromDate = currentFilters.dateFrom ? new Date(currentFilters.dateFrom) : null;
                            const toDate = currentFilters.dateTo ? new Date(currentFilters.dateTo) : null;
                            
                            if (fromDate && requestDate < fromDate) matches = false;
                            if (toDate && requestDate > toDate) matches = false;
                        }
                    }
                }
                
                // Show/hide row
                if (matches) {
                    row.classList.remove('hidden');
                    visibleCount++;
                    
                    // Highlight search text
                    if (currentFilters.text) {
                        highlightSearchText(row, currentFilters.text);
                    } else {
                        removeHighlight(row);
                    }
                } else {
                    row.classList.add('hidden');
                    removeHighlight(row);
                }
            });
            
            updateSearchResultsCount(visibleCount);
        }
        
        function highlightSearchText(row, searchText) {
            removeHighlight(row);
            
            if (!searchText) return;
            
            const cells = row.querySelectorAll('td');
            cells.forEach(cell => {
                const text = cell.textContent;
                const regex = new RegExp(`(${searchText})`, 'gi');
                
                // Skip cells with HTML we don't want to modify
                if (cell.querySelector('.btn, .badge, .status-badge, .request-type-badge')) return;
                
                const walker = document.createTreeWalker(
                    cell,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );
                
                const textNodes = [];
                let node;
                while (node = walker.nextNode()) {
                    if (node.nodeValue.trim()) {
                        textNodes.push(node);
                    }
                }
                
                textNodes.forEach(textNode => {
                    const text = textNode.nodeValue;
                    if (text.toLowerCase().includes(searchText)) {
                        const span = document.createElement('span');
                        span.innerHTML = text.replace(regex, '<span class="highlight-search">$1</span>');
                        textNode.parentNode.replaceChild(span, textNode);
                    }
                });
            });
        }
        
        function removeHighlight(row) {
            const highlights = row.querySelectorAll('.highlight-search');
            highlights.forEach(highlight => {
                const parent = highlight.parentNode;
                parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                parent.normalize();
            });
        }
        
        function updateSearchResultsCount(visibleCount = null) {
            const countElement = document.getElementById('searchResultsCount');
            if (!countElement) return;
            
            const totalRows = document.querySelectorAll('#requestsTable tbody tr.request-row').length;
            const visible = visibleCount !== null ? visibleCount : 
                          document.querySelectorAll('#requestsTable tbody tr.request-row:not(.hidden)').length;
            
            if (currentFilters.text || currentFilters.type || currentFilters.status || 
                currentFilters.dateFrom || currentFilters.dateTo) {
                countElement.textContent = `Showing ${visible} of ${totalRows} requests`;
            } else {
                countElement.textContent = 'Showing all requests';
            }
        }
        
        // Bulk Actions Functionality
        let selectedRequests = new Set();
        
        function updateBulkActionsBar() {
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const selectedCount = document.getElementById('selectedCount');
            const bulkApproveBtn = document.getElementById('bulkApproveBtn');
            const bulkDenyBtn = document.getElementById('bulkDenyBtn');
            const bulkMarkBorrowedBtn = document.getElementById('bulkMarkBorrowedBtn');
            
            const count = selectedRequests.size;
            selectedCount.textContent = count;
            
            // Show/hide bulk actions bar
            if (count > 0) {
                bulkActionsBar.classList.remove('d-none');
            } else {
                bulkActionsBar.classList.add('d-none');
            }
            
            // Enable/disable bulk action buttons based on selected requests
            const hasPendingIncoming = Array.from(selectedRequests).some(id => {
                const row = document.querySelector(`tr[data-request-id="${id}"]`);
                return row && row.dataset.type === 'incoming' && row.dataset.status === 'pending';
            });
            
            const hasApprovedIncoming = Array.from(selectedRequests).some(id => {
                const row = document.querySelector(`tr[data-request-id="${id}"]`);
                return row && row.dataset.type === 'incoming' && row.dataset.status === 'approved';
            });
            
            const hasPendingOutgoing = Array.from(selectedRequests).some(id => {
                const row = document.querySelector(`tr[data-request-id="${id}"]`);
                return row && row.dataset.type === 'outgoing' && row.dataset.status === 'pending';
            });
            
            // Update incoming buttons
            bulkApproveBtn.disabled = !hasPendingIncoming;
            bulkDenyBtn.disabled = !hasPendingIncoming;
            bulkMarkBorrowedBtn.disabled = !hasApprovedIncoming;
            
            // Update outgoing buttons
            const bulkCancelBtn = document.getElementById('bulkCancelBtn');
            if (bulkCancelBtn) {
                bulkCancelBtn.disabled = !hasPendingOutgoing;
            }
            
            // Update "all" buttons
            const bulkApproveBtnAll = document.getElementById('bulkApproveBtnAll');
            const bulkDenyBtnAll = document.getElementById('bulkDenyBtnAll');
            const bulkMarkBorrowedBtnAll = document.getElementById('bulkMarkBorrowedBtnAll');
            const bulkCancelBtnAll = document.getElementById('bulkCancelBtnAll');
            
            if (bulkApproveBtnAll) bulkApproveBtnAll.disabled = !hasPendingIncoming;
            if (bulkDenyBtnAll) bulkDenyBtnAll.disabled = !hasPendingIncoming;
            if (bulkMarkBorrowedBtnAll) bulkMarkBorrowedBtnAll.disabled = !hasApprovedIncoming;
            if (bulkCancelBtnAll) bulkCancelBtnAll.disabled = !hasPendingOutgoing;
        }
        
        function toggleRequestSelection(requestId) {
            if (selectedRequests.has(requestId)) {
                selectedRequests.delete(requestId);
            } else {
                selectedRequests.add(requestId);
            }
            updateBulkActionsBar();
            updateSelectAllCheckboxState();
        }
        
        function selectAllRequests() {
            const selectAll = document.getElementById('selectAllRequests');
            // Only select checkboxes that are in visible rows (not hidden by current filter)
            const visibleCheckboxes = document.querySelectorAll('.request-row:not(.hidden) .request-checkbox');
            const allCheckboxes = document.querySelectorAll('.request-checkbox');
            
            // Update all checkboxes to match the selectAll state
            allCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            // Only update the selectedRequests set with visible checkboxes
            visibleCheckboxes.forEach(checkbox => {
                const requestId = parseInt(checkbox.value);
                if (selectAll.checked) {
                    selectedRequests.add(requestId);
                } else {
                    selectedRequests.delete(requestId);
                }
            });
            
            updateBulkActionsBar();
        }
        
        function clearSelection() {
            selectedRequests.clear();
            document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllRequests').checked = false;
            updateBulkActionsBar();
        }
        
        function updateSelectAllCheckboxState() {
            const selectAll = document.getElementById('selectAllRequests');
            const visibleCheckboxes = document.querySelectorAll('.request-row:not(.hidden) .request-checkbox');
            const checkedVisibleCheckboxes = document.querySelectorAll('.request-row:not(.hidden) .request-checkbox:checked');
            
            // Update select all checkbox state based on visible checkboxes
            if (visibleCheckboxes.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checkedVisibleCheckboxes.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checkedVisibleCheckboxes.length === visibleCheckboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }
        
        function updateBulkActionsButtons(filter) {
            const incomingActions = document.getElementById('incomingBulkActions');
            const outgoingActions = document.getElementById('outgoingBulkActions');
            const allActions = document.getElementById('allBulkActions');
            
            // Hide all button groups first
            incomingActions.classList.add('d-none');
            outgoingActions.classList.add('d-none');
            allActions.classList.add('d-none');
            
            // Show appropriate button group based on filter
            switch(filter) {
                case 'needs_action':
                    incomingActions.classList.remove('d-none');
                    break;
                case 'waiting':
                    outgoingActions.classList.remove('d-none');
                    break;
                case 'all':
                    allActions.classList.remove('d-none');
                    break;
            }
        }
        
        function bulkApprove() {
            const pendingIncomingIds = Array.from(selectedRequests).filter(id => {
                const row = document.querySelector(`tr[data-request-id="${id}"]`);
                return row && row.dataset.type === 'incoming' && row.dataset.status === 'pending';
            });
            
            if (pendingIncomingIds.length === 0) {
                showNotification('No pending incoming requests selected for approval', 'warning');
                return;
            }
            
            if (confirm(`Approve ${pendingIncomingIds.length} pending request(s)?`)) {
                // Submit bulk approval via AJAX
                fetch('requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'bulk_approve',
                        request_ids: pendingIncomingIds.join(',')
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`${pendingIncomingIds.length} requests approved successfully`, 'success');
                        clearSelection();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'Error approving requests', 'error');
                    }
                })
                .catch(error => {
                    console.error('Bulk approval error:', error);
                    showNotification('Error approving requests', 'error');
                });
            }
        }
        
        function bulkDeny() {
            const pendingIncomingIds = Array.from(selectedRequests).filter(id => {
                const row = document.querySelector(`tr[data-request-id="${id}"]`);
                return row && row.dataset.type === 'incoming' && row.dataset.status === 'pending';
            });
            
            if (pendingIncomingIds.length === 0) {
                showNotification('No pending incoming requests selected for denial', 'warning');
                return;
            }
            
            const reason = prompt('Enter denial reason for all selected requests:');
            if (!reason) return;
            
            if (confirm(`Deny ${pendingIncomingIds.length} pending request(s)?`)) {
                // Submit bulk denial via AJAX
                fetch('requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'bulk_deny',
                        request_ids: pendingIncomingIds.join(','),
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`${pendingIncomingIds.length} requests denied`, 'success');
                        clearSelection();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'Error denying requests', 'error');
                    }
                })
                .catch(error => {
                    console.error('Bulk denial error:', error);
                    showNotification('Error denying requests', 'error');
                });
            }
        }
        
        function bulkMarkBorrowed() {
            console.log('bulkMarkBorrowed called');
            console.log('selectedRequests:', Array.from(selectedRequests));
            
            const approvedIncomingIds = Array.from(selectedRequests).filter(id => {
                const row = document.querySelector(`tr[data-request-id="${id}"]`);
                console.log(`Checking request ${id}:`, row ? `${row.dataset.type}/${row.dataset.status}` : 'not found');
                return row && row.dataset.type === 'incoming' && row.dataset.status === 'approved';
            });
            
            console.log('approvedIncomingIds:', approvedIncomingIds);
            
            if (approvedIncomingIds.length === 0) {
                showNotification('No approved incoming requests selected to mark as borrowed', 'warning');
                return;
            }
            
            if (confirm(`Mark ${approvedIncomingIds.length} approved request(s) as borrowed?`)) {
                // Submit bulk mark borrowed via AJAX
                fetch('requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'bulk_mark_borrowed',
                        request_ids: approvedIncomingIds.join(',')
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`${approvedIncomingIds.length} requests marked as borrowed`, 'success');
                        clearSelection();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'Error marking requests as borrowed', 'error');
                    }
                })
                .catch(error => {
                    console.error('Bulk mark borrowed error:', error);
                    showNotification('Error marking requests as borrowed', 'error');
                });
            }
        }
        
        function bulkCancel() {
            const pendingOutgoingIds = Array.from(selectedRequests).filter(id => {
                const row = document.querySelector(`tr[data-request-id="${id}"]`);
                return row && row.dataset.type === 'outgoing' && row.dataset.status === 'pending';
            });
            
            if (pendingOutgoingIds.length === 0) {
                showNotification('No pending outgoing requests selected to cancel', 'warning');
                return;
            }
            
            if (confirm(`Cancel ${pendingOutgoingIds.length} outgoing request(s)? This action cannot be undone.`)) {
                // Submit bulk cancel via AJAX
                fetch('requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'bulk_cancel',
                        request_ids: pendingOutgoingIds.join(',')
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`${pendingOutgoingIds.length} requests cancelled`, 'success');
                        clearSelection();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'Error cancelling requests', 'error');
                    }
                })
                .catch(error => {
                    console.error('Bulk cancel error:', error);
                    showNotification('Error cancelling requests', 'error');
                });
            }
        }
        
        // Event listeners for bulk actions
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize real-time updates
            startRealTimeUpdates();
            
            // Initialize advanced search
            initAdvancedSearch();
            
            // Select all checkbox
            document.getElementById('selectAllRequests')?.addEventListener('change', selectAllRequests);
            
            // Individual checkboxes
            document.querySelectorAll('.request-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleRequestSelection(parseInt(this.value));
                });
            });
            
            // Bulk action buttons
            const bulkApproveBtn = document.getElementById('bulkApproveBtn');
            const bulkDenyBtn = document.getElementById('bulkDenyBtn');
            const bulkMarkBorrowedBtn = document.getElementById('bulkMarkBorrowedBtn');
            
            console.log('Setting up event listeners:');
            console.log('bulkApproveBtn:', bulkApproveBtn);
            console.log('bulkDenyBtn:', bulkDenyBtn);
            console.log('bulkMarkBorrowedBtn:', bulkMarkBorrowedBtn);
            
            if (bulkApproveBtn) {
                bulkApproveBtn.addEventListener('click', bulkApprove);
                console.log('Added listener to bulkApproveBtn');
            }
            if (bulkDenyBtn) {
                bulkDenyBtn.addEventListener('click', bulkDeny);
                console.log('Added listener to bulkDenyBtn');
            }
            if (bulkMarkBorrowedBtn) {
                bulkMarkBorrowedBtn.addEventListener('click', bulkMarkBorrowed);
                console.log('Added listener to bulkMarkBorrowedBtn');
            }
            
            document.getElementById('bulkCancelBtn')?.addEventListener('click', bulkCancel);
            document.getElementById('bulkApproveBtnAll')?.addEventListener('click', bulkApprove);
            document.getElementById('bulkDenyBtnAll')?.addEventListener('click', bulkDeny);
            document.getElementById('bulkMarkBorrowedBtnAll')?.addEventListener('click', bulkMarkBorrowed);
            document.getElementById('bulkCancelBtnAll')?.addEventListener('click', bulkCancel);
            
            initSmartFilters();
        });
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
                    © <?php echo date('Y'); ?> PIMS - Property and Inventory Management System
                </small>
            </div>
        </div>
    </footer>
</body>
</html>
