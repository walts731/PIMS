<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Get request ID from query parameter
$request_id = $_GET['request_id'] ?? 0;

if (empty($request_id) || !is_numeric($request_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request ID']);
    exit();
}

$office_id = $_SESSION['office_id'];

try {
    // Start with a basic query and build it up
    $query = "SELECT br.* FROM borrow_requests br WHERE br.id = ? AND (br.requested_by_office = ? OR br.requested_to_office = ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $request_id, $office_id, $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found']);
        exit();
    }
    
    $request_data = $result->fetch_assoc();
    
    // Now try to get additional information with error handling
    $requester_info = ['name' => 'Unknown', 'email' => 'Unknown', 'office' => ['name' => 'Unknown', 'code' => 'Unknown']];
    $approver_info = ['name' => 'Unknown', 'email' => 'Unknown', 'office' => ['name' => 'Unknown', 'code' => 'Unknown']];
    $asset_info = ['description' => 'Unknown', 'code' => 'Unknown', 'serial_number' => '', 'model' => '', 'brand' => '', 'category' => ['name' => 'Unknown', 'code' => 'Unknown'], 'total_quantity' => 1];
    
    // Get requester information
    try {
        if ($request_data['requested_by']) {
            $user_query = "SELECT first_name, last_name, email FROM users WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $request_data['requested_by']);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $requester_info['name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                $requester_info['email'] = $user_data['email'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting requester info: " . $e->getMessage());
    }
    
    // Get requester office information
    try {
        if ($request_data['requested_by_office']) {
            $office_query = "SELECT office_name, office_code FROM offices WHERE id = ?";
            $office_stmt = $conn->prepare($office_query);
            $office_stmt->bind_param("i", $request_data['requested_by_office']);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            
            if ($office_result->num_rows > 0) {
                $office_data = $office_result->fetch_assoc();
                $requester_info['office']['name'] = $office_data['office_name'];
                $requester_info['office']['code'] = $office_data['office_code'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting requester office: " . $e->getMessage());
    }
    
    // Get approver office information
    try {
        if ($request_data['requested_to_office']) {
            $office_query = "SELECT office_name, office_code FROM offices WHERE id = ?";
            $office_stmt = $conn->prepare($office_query);
            $office_stmt->bind_param("i", $request_data['requested_to_office']);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            
            if ($office_result->num_rows > 0) {
                $office_data = $office_result->fetch_assoc();
                $approver_info['office']['name'] = $office_data['office_name'];
                $approver_info['office']['code'] = $office_data['office_code'];
                
                // If this is OMM office, get all OMM users
                if ($request_data['requested_to_office'] == 4) { // OMM office ID
                    $omm_users_query = "SELECT first_name, last_name, email FROM users WHERE office = 4 AND is_active = 1 ORDER BY first_name, last_name";
                    $omm_users_stmt = $conn->prepare($omm_users_query);
                    $omm_users_stmt->execute();
                    $omm_users_result = $omm_users_stmt->get_result();
                    
                    $omm_users = [];
                    while ($user_row = $omm_users_result->fetch_assoc()) {
                        $omm_users[] = [
                            'name' => $user_row['first_name'] . ' ' . $user_row['last_name'],
                            'email' => $user_row['email']
                        ];
                    }
                    $approver_info['office']['users'] = $omm_users;
                }
                
                // Get the office admin who performed the action (approved/denied)
                if ($request_data['approved_by']) {
                    $admin_query = "SELECT first_name, last_name, email FROM users WHERE id = ?";
                    $admin_stmt = $conn->prepare($admin_query);
                    $admin_stmt->bind_param("i", $request_data['approved_by']);
                    $admin_stmt->execute();
                    $admin_result = $admin_stmt->get_result();
                    
                    if ($admin_result->num_rows > 0) {
                        $admin_data = $admin_result->fetch_assoc();
                        $approver_info['name'] = $admin_data['first_name'] . ' ' . $admin_data['last_name'];
                        $approver_info['email'] = $admin_data['email'];
                    }
                } elseif ($request_data['denied_by']) {
                    $admin_query = "SELECT first_name, last_name, email FROM users WHERE id = ?";
                    $admin_stmt = $conn->prepare($admin_query);
                    $admin_stmt->bind_param("i", $request_data['denied_by']);
                    $admin_stmt->execute();
                    $admin_result = $admin_stmt->get_result();
                    
                    if ($admin_result->num_rows > 0) {
                        $admin_data = $admin_result->fetch_assoc();
                        $approver_info['name'] = $admin_data['first_name'] . ' ' . $admin_data['last_name'];
                        $approver_info['email'] = $admin_data['email'];
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error getting approver office: " . $e->getMessage());
    }
    
    // Get asset information
    try {
        if ($request_data['asset_id']) {
            $asset_query = "SELECT ai.description, 
                          COALESCE(ai.property_number, ai.property_no) as asset_code, 
                          ai.serial_number, ai.model, ai.brand, 
                          ai.status, ai.asset_category_id,
                          ac.category_name, ac.category_code,
                          ai.value as unit_value, 
                          ai.acquisition_date as date_acquired,
                          ai.unit, ai.inventory_tag,
                          ai.end_user, ai.employee_id
                          FROM asset_items ai
                          LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                          WHERE ai.id = ?";
            $asset_stmt = $conn->prepare($asset_query);
            $asset_stmt->bind_param("i", $request_data['asset_id']);
            $asset_stmt->execute();
            $asset_result = $asset_stmt->get_result();
            
            if ($asset_result->num_rows > 0) {
                $asset_data = $asset_result->fetch_assoc();
                $asset_info['description'] = $asset_data['description'];
                $asset_info['code'] = $asset_data['asset_code'];
                $asset_info['serial_number'] = $asset_data['serial_number'] ?? '';
                $asset_info['model'] = $asset_data['model'] ?? '';
                $asset_info['brand'] = $asset_data['brand'] ?? '';
                $asset_info['status'] = $asset_data['status'] ?? '';
                $asset_info['unit_value'] = $asset_data['unit_value'] ?? '';
                $asset_info['date_acquired'] = $asset_data['date_acquired'] ?? '';
                $asset_info['unit'] = $asset_data['unit'] ?? '';
                $asset_info['inventory_tag'] = $asset_data['inventory_tag'] ?? '';
                $asset_info['end_user'] = $asset_data['end_user'] ?? '';
                $asset_info['category'] = [
                    'name' => $asset_data['category_name'] ?? 'Uncategorized',
                    'code' => $asset_data['category_code'] ?? ''
                ];
                $asset_info['total_quantity'] = 1; // Default to 1 for individual items
            }
        }
    } catch (Exception $e) {
        error_log("Error getting asset info: " . $e->getMessage());
    }
    
    // Format the response with lifecycle events
    $lifecycle_events = [];
    
    // Creation event
    $lifecycle_events[] = [
        'type' => 'created',
        'title' => 'Request Created',
        'description' => 'Borrow request was initiated',
        'user' => $requester_info['name'],
        'user_email' => $requester_info['email'],
        'office' => $requester_info['office']['name'],
        'timestamp' => $request_data['created_at'],
        'status' => 'completed'
    ];
    
    // Approval event (if approved)
    if ($request_data['approved_at']) {
        $lifecycle_events[] = [
            'type' => 'approved',
            'title' => 'Request Approved',
            'description' => $request_data['approval_notes'] ?? 'Request was approved',
            'user' => $approver_info['name'],
            'user_email' => $approver_info['email'],
            'office' => $approver_info['office']['name'],
            'timestamp' => $request_data['approved_at'],
            'status' => 'completed'
        ];
    }
    
    // Borrowed event (if borrowed)
    if ($request_data['borrowed_at']) {
        $lifecycle_events[] = [
            'type' => 'borrowed',
            'title' => 'Asset Borrowed',
            'description' => 'Asset was picked up by the borrower',
            'user' => $requester_info['name'],
            'user_email' => $requester_info['email'],
            'office' => $requester_info['office']['name'],
            'timestamp' => $request_data['borrowed_at'],
            'status' => 'completed'
        ];
    }
    
    // Denial event (if denied)
    if ($request_data['denied_at']) {
        $lifecycle_events[] = [
            'type' => 'denied',
            'title' => 'Request Denied',
            'description' => $request_data['denial_reason'] ?? 'Request was denied',
            'user' => $approver_info['name'],
            'user_email' => $approver_info['email'],
            'office' => $approver_info['office']['name'],
            'timestamp' => $request_data['denied_at'],
            'status' => 'completed'
        ];
    }
    
    // Return event (if returned)
    if ($request_data['returned_at']) {
        $lifecycle_events[] = [
            'type' => 'returned',
            'title' => 'Asset Returned',
            'description' => 'Asset was returned with condition: ' . ucfirst($request_data['return_condition'] ?? 'good'),
            'notes' => $request_data['return_notes'],
            'timestamp' => $request_data['returned_at'],
            'status' => 'completed'
        ];
    }
    
    // Cancellation event (if cancelled)
    if ($request_data['status'] === 'cancelled') {
        $lifecycle_events[] = [
            'type' => 'cancelled',
            'title' => 'Request Cancelled',
            'description' => 'Request was cancelled',
            'timestamp' => $request_data['updated_at'],
            'status' => 'completed'
        ];
    }
    
    // Current status indicator
    $current_status = [
        'status' => $request_data['status'],
        'title' => ucfirst($request_data['status']),
        'description' => getStatusDescription($request_data['status']),
        'timestamp' => getLatestTimestamp($request_data)
    ];
    
    // Prepare response
    $response = [
        'request' => [
            'id' => $request_data['id'],
            'quantity_requested' => $request_data['quantity_requested'],
            'purpose' => $request_data['purpose'],
            'start_date' => $request_data['start_date'],
            'end_date' => $request_data['end_date'],
            'status' => $request_data['status'],
            'created_at' => $request_data['created_at'],
            'approval_notes' => $request_data['approval_notes'],
            'denial_reason' => $request_data['denial_reason'],
            'return_condition' => $request_data['return_condition'],
            'return_notes' => $request_data['return_notes']
        ],
        'requester' => $requester_info,
        'approver' => $approver_info,
        'asset' => $asset_info,
        'lifecycle' => [
            'events' => $lifecycle_events,
            'current_status' => $current_status
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error fetching request details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

function getStatusDescription($status) {
    switch ($status) {
        case 'pending':
            return 'Request is awaiting approval';
        case 'approved':
            return 'Request has been approved and asset is in use';
        case 'denied':
            return 'Request has been denied';
        case 'returned':
            return 'Asset has been returned';
        case 'cancelled':
            return 'Request has been cancelled';
        default:
            return 'Unknown status';
    }
}

function getLatestTimestamp($request_data) {
    $timestamps = [$request_data['created_at']];
    
    if ($request_data['approved_at']) {
        $timestamps[] = $request_data['approved_at'];
    }
    if ($request_data['borrowed_at']) {
        $timestamps[] = $request_data['borrowed_at'];
    }
    if ($request_data['denied_at']) {
        $timestamps[] = $request_data['denied_at'];
    }
    if ($request_data['returned_at']) {
        $timestamps[] = $request_data['returned_at'];
    }
    if ($request_data['status'] === 'cancelled' && $request_data['updated_at']) {
        $timestamps[] = $request_data['updated_at'];
    }
    
    return max($timestamps);
}
?>
