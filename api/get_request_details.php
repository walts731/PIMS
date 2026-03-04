<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

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
    // Get detailed request information with all related data
    $query = "SELECT br.*, 
              requester.first_name as requester_first_name, requester.last_name as requester_last_name, requester.email as requester_email,
              approver.first_name as approver_first_name, approver.last_name as approver_last_name, approver.email as approver_email,
              denier.first_name as denier_first_name, denier.last_name as denier_last_name, denier.email as denier_email,
              requester_office.office_name as requester_office_name, requester_office.office_code as requester_office_code,
              approver_office.office_name as approver_office_name, approver_office.office_code as approver_office_code,
              ai.description as asset_description, 
              COALESCE(ai.property_number, ai.property_no) as asset_code,
              ai.serial_number, ai.model, ai.brand,
              ac.category_name, ac.category_code,
              a.quantity as total_quantity
              FROM borrow_requests br
              LEFT JOIN users requester ON br.requested_by = requester.id
              LEFT JOIN users approver ON br.approved_by = approver.id
              LEFT JOIN users denier ON br.denied_by = denier.id
              LEFT JOIN offices requester_office ON br.requested_by_office = requester_office.id
              LEFT JOIN offices approver_office ON br.requested_to_office = approver_office.id
              LEFT JOIN asset_items ai ON br.asset_id = ai.id
              LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
              LEFT JOIN assets a ON ai.asset_id = a.id
              WHERE br.id = ? AND (br.requested_by_office = ? OR br.requested_to_office = ?)";
    
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
    
    // Format the response with lifecycle events
    $lifecycle_events = [];
    
    // Creation event
    $lifecycle_events[] = [
        'type' => 'created',
        'title' => 'Request Created',
        'description' => 'Borrow request was initiated',
        'user' => $request_data['requester_first_name'] . ' ' . $request_data['requester_last_name'],
        'user_email' => $request_data['requester_email'],
        'office' => $request_data['requester_office_name'],
        'timestamp' => $request_data['created_at'],
        'status' => 'completed'
    ];
    
    // Approval event (if approved)
    if ($request_data['approved_at']) {
        $lifecycle_events[] = [
            'type' => 'approved',
            'title' => 'Request Approved',
            'description' => $request_data['approval_notes'] ?? 'Request was approved',
            'user' => $request_data['approver_first_name'] . ' ' . $request_data['approver_last_name'],
            'user_email' => $request_data['approver_email'],
            'office' => $request_data['approver_office_name'],
            'timestamp' => $request_data['approved_at'],
            'status' => 'completed'
        ];
    }
    
    // Denial event (if denied)
    if ($request_data['denied_at']) {
        $lifecycle_events[] = [
            'type' => 'denied',
            'title' => 'Request Denied',
            'description' => $request_data['denial_reason'] ?? 'Request was denied',
            'user' => $request_data['denier_first_name'] . ' ' . $request_data['denier_last_name'],
            'user_email' => $request_data['denier_email'],
            'office' => $request_data['approver_office_name'],
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
        'requester' => [
            'name' => $request_data['requester_first_name'] . ' ' . $request_data['requester_last_name'],
            'email' => $request_data['requester_email'],
            'office' => [
                'name' => $request_data['requester_office_name'],
                'code' => $request_data['requester_office_code']
            ]
        ],
        'approver' => [
            'name' => $request_data['approver_first_name'] . ' ' . $request_data['approver_last_name'],
            'email' => $request_data['approver_email'],
            'office' => [
                'name' => $request_data['approver_office_name'],
                'code' => $request_data['approver_office_code']
            ]
        ],
        'asset' => [
            'description' => $request_data['asset_description'],
            'code' => $request_data['asset_code'],
            'serial_number' => $request_data['serial_number'],
            'model' => $request_data['model'],
            'brand' => $request_data['brand'],
            'category' => [
                'name' => $request_data['category_name'],
                'code' => $request_data['category_code']
            ],
            'total_quantity' => $request_data['total_quantity']
        ],
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
    echo json_encode(['error' => 'Internal server error']);
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
        default:
            return 'Unknown status';
    }
}

function getLatestTimestamp($request_data) {
    $timestamps = [$request_data['created_at']];
    
    if ($request_data['approved_at']) {
        $timestamps[] = $request_data['approved_at'];
    }
    if ($request_data['denied_at']) {
        $timestamps[] = $request_data['denied_at'];
    }
    if ($request_data['returned_at']) {
        $timestamps[] = $request_data['returned_at'];
    }
    
    return max($timestamps);
}
?>
