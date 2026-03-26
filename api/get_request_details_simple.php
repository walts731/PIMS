<?php
// Start output buffering to catch any accidental output
ob_start();

session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

// Debug: Log session state
error_log("Session state in API: " . print_r($_SESSION, true));
error_log("Request ID: " . ($_GET['request_id'] ?? 'not set'));

// Disable error display to prevent HTML from corrupting JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    error_log("API: User not logged in");
    http_response_code(401);
    ob_clean();
    echo json_encode(['error' => 'Unauthorized', 'debug' => 'User not logged in']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    error_log("API: User role not office_admin: " . ($_SESSION['role'] ?? 'no role'));
    http_response_code(403);
    ob_clean();
    echo json_encode(['error' => 'Forbidden', 'debug' => 'Role: ' . ($_SESSION['role'] ?? 'no role')]);
    exit();
}

// Get request ID from query parameter
$request_id = $_GET['request_id'] ?? 0;

if (empty($request_id) || !is_numeric($request_id)) {
    error_log("API: Invalid request ID: " . $request_id);
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'Invalid request ID', 'debug' => 'Request ID: ' . $request_id]);
    exit();
}

$office_id = $_SESSION['office_id'] ?? 'not set';
error_log("API: Office ID: " . $office_id);

try {
    // Start with a basic query and build it up
    $query = "SELECT br.* FROM borrow_requests br WHERE br.id = ? AND (br.requested_by_office = ? OR br.requested_to_office = ?)";
    
    error_log("API: Query: " . $query);
    error_log("API: Params - request_id: $request_id, office_id: $office_id");
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("iii", $request_id, $office_id, $office_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("API: No request found for ID: $request_id, Office: $office_id");
        http_response_code(404);
        ob_clean();
        echo json_encode(['error' => 'Request not found', 'debug' => "No request found for ID: $request_id, Office: $office_id"]);
        exit();
    }
    
    $request_data = $result->fetch_assoc();
    error_log("API: Found request data: " . print_r($request_data, true));
    
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
        error_log('=== DEBUG: Asset Information Retrieval ===');
        error_log('DEBUG: Asset ID from request_data: ' . ($request_data['asset_id'] ?? 'NULL'));
        
        if ($request_data['asset_id']) {
            error_log('DEBUG: Asset ID exists, preparing query...');
            
            $asset_query = "SELECT ai.description, 
                          ai.property_no as asset_code, 
                          ai.serial_number, ai.model, 
                          ai.status, ai.asset_category_id,
                          ac.category_name, ac.category_code,
                          ai.value as unit_value, 
                          ai.acquisition_date as date_acquired,
                          ai.unit, ai.inventory_tag,
                          ai.end_user, ai.employee_id,
                          ai.office_id, ai.office_name,
                          ai.qr_code, ai.image,
                          ai.date_counted, ai.last_updated
                          FROM asset_items ai
                          LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                          WHERE ai.id = ?";
            
            error_log('DEBUG: Asset query: ' . $asset_query);
            error_log('DEBUG: Asset ID parameter: ' . $request_data['asset_id']);
            
            $asset_stmt = $conn->prepare($asset_query);
            if (!$asset_stmt) {
                error_log('DEBUG: Failed to prepare asset statement: ' . $conn->error);
                throw new Exception("Failed to prepare asset statement: " . $conn->error);
            }
            
            $asset_stmt->bind_param("i", $request_data['asset_id']);
            error_log('DEBUG: Asset statement prepared and bound');
            
            $asset_stmt->execute();
            error_log('DEBUG: Asset statement executed');
            
            $asset_result = $asset_stmt->get_result();
            error_log('DEBUG: Asset result rows: ' . $asset_result->num_rows);
            
            if ($asset_result->num_rows > 0) {
                $asset_data = $asset_result->fetch_assoc();
                error_log('DEBUG: Asset data fetched: ' . print_r($asset_data, true));
                
                $asset_info['description'] = $asset_data['description'] ?? 'Unknown';
                $asset_info['code'] = $asset_data['asset_code'] ?? 'Unknown';
                $asset_info['serial_number'] = $asset_data['serial_number'] ?? '';
                $asset_info['model'] = $asset_data['model'] ?? '';
                $asset_info['status'] = $asset_data['status'] ?? '';
                $asset_info['unit_value'] = $asset_data['unit_value'] ?? '';
                $asset_info['date_acquired'] = $asset_data['date_acquired'] ?? '';
                $asset_info['unit'] = $asset_data['unit'] ?? '';
                $asset_info['inventory_tag'] = $asset_data['inventory_tag'] ?? '';
                $asset_info['end_user'] = $asset_data['end_user'] ?? '';
                $asset_info['employee_id'] = $asset_data['employee_id'] ?? '';
                $asset_info['office_id'] = $asset_data['office_id'] ?? '';
                $asset_info['office_name'] = $asset_data['office_name'] ?? '';
                $asset_info['qr_code'] = $asset_data['qr_code'] ?? '';
                $asset_info['image'] = $asset_data['image'] ?? '';
                $asset_info['date_counted'] = $asset_data['date_counted'] ?? '';
                $asset_info['last_updated'] = $asset_data['last_updated'] ?? '';
                $asset_info['category'] = [
                    'name' => $asset_data['category_name'] ?? 'Uncategorized',
                    'code' => $asset_data['category_code'] ?? ''
                ];
                $asset_info['total_quantity'] = 1; // Default to 1 for individual items
                
                error_log('DEBUG: Asset info array built: ' . print_r($asset_info, true));
            } else {
                error_log('DEBUG: No asset data found for asset_id: ' . $request_data['asset_id']);
            }
        } else {
            error_log('DEBUG: No asset_id in request_data');
        }
    } catch (Exception $e) {
        error_log('DEBUG: Error getting asset info: ' . $e->getMessage());
        error_log('DEBUG: Exception trace: ' . $e->getTraceAsString());
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
    if ($request_data['status'] == 'borrowed') {
        $lifecycle_events[] = [
            'type' => 'borrowed',
            'title' => 'Asset Borrowed',
            'description' => 'Asset was borrowed and is currently in use',
            'user' => $requester_info['name'],
            'user_email' => $requester_info['email'],
            'office' => $requester_info['office']['name'],
            'timestamp' => $request_data['updated_at'], // Use updated_at as fallback
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
            'return_notes' => $request_data['return_notes'],
            'return_photo' => $request_data['return_photo']
        ],
        'requester' => $requester_info,
        'approver' => $approver_info,
        'asset' => $asset_info,
        'lifecycle' => [
            'events' => $lifecycle_events,
            'current_status' => $current_status
        ]
    ];
    
    error_log('=== DEBUG: Final Response ===');
    error_log('DEBUG: Asset info in response: ' . print_r($asset_info, true));
    error_log('DEBUG: Full response structure: ' . print_r($response, true));
    
    header('Content-Type: application/json');
    
    // Clean any output buffer to ensure clean JSON
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error fetching request details: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    http_response_code(500);
    
    // Clean any output buffer to ensure clean JSON
    ob_clean();
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage(), 'debug' => 'Exception caught']);
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
    if ($request_data['status'] == 'borrowed') {
        $timestamps[] = $request_data['updated_at']; // Use updated_at as fallback for borrowed status
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
