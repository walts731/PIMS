<?php
require_once '../config.php';

echo "<h2>Debug Borrow Requests</h2>";

// Test connection
if (!$conn) {
    die("Database connection failed");
}

// Check all borrow requests
echo "<h3>All Borrow Requests in Database:</h3>";
$all_requests = $conn->query("SELECT * FROM borrow_requests ORDER BY created_at DESC");
while ($row = $all_requests->fetch_assoc()) {
    echo "ID: {$row['id']}, By: {$row['requested_by']}, From Office: {$row['requested_by_office']}, To Office: {$row['requested_to_office']}, Status: {$row['status']}, Asset: {$row['asset_id']}<br>";
}

// Check offices
echo "<h3>All Offices:</h3>";
$offices = $conn->query("SELECT id, office_name FROM offices ORDER BY id");
while ($row = $offices->fetch_assoc()) {
    echo "ID: {$row['id']}, Name: {$row['office_name']}<br>";
}

// Test queries for different office IDs
$test_offices = [4, 5]; // From the sample data

foreach ($test_offices as $office_id) {
    echo "<h3>Testing for Office ID: $office_id</h3>";
    
    // Incoming requests
    $incoming = $conn->prepare("
        SELECT br.*, u.first_name, u.last_name, u.email, 
        o.office_name as requester_office, ai.description as asset_description,
        ai.property_no as asset_code, ac.category_name
        FROM borrow_requests br
        JOIN users u ON br.requested_by = u.id
        JOIN offices o ON br.requested_by_office = o.id
        JOIN asset_items ai ON br.asset_id = ai.id
        LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
        WHERE br.requested_to_office = ? 
        ORDER BY br.created_at DESC
    ");
    $incoming->bind_param("i", $office_id);
    $incoming->execute();
    $incoming_result = $incoming->get_result();
    
    echo "<strong>Incoming requests to office $office_id:</strong> " . $incoming_result->num_rows . " found<br>";
    while ($row = $incoming_result->fetch_assoc()) {
        echo "- Request ID: {$row['id']}, From: {$row['first_name']} {$row['last_name']} (Office {$row['requested_by_office']}), Status: {$row['status']}<br>";
    }
    
    // Outgoing requests
    $outgoing = $conn->prepare("
        SELECT br.*, u.first_name, u.last_name, u.email,
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
        ORDER BY br.created_at DESC
    ");
    $outgoing->bind_param("i", $office_id);
    $outgoing->execute();
    $outgoing_result = $outgoing->get_result();
    
    echo "<strong>Outgoing requests from office $office_id:</strong> " . $outgoing_result->num_rows . " found<br>";
    while ($row = $outgoing_result->fetch_assoc()) {
        $admin_info = '';
        if (!empty($row['admin_first_name']) && !empty($row['admin_last_name'])) {
            $admin_info = ", Admin: {$row['admin_first_name']} {$row['admin_last_name']}";
        } else {
            $admin_info = ', Admin: Not assigned';
        }
        echo "- Request ID: {$row['id']}, To: {$row['approver_office']} (Office {$row['requested_to_office']}){$admin_info}, Status: {$row['status']}<br>";
    }
    
    $incoming->close();
    $outgoing->close();
}

echo "<h3>Session Check:</h3>";
session_start();
echo "Session office_id: " . ($_SESSION['office_id'] ?? 'Not set') . "<br>";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Session role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";

// Check current user's office
if (isset($_SESSION['user_id'])) {
    $user_query = $conn->prepare("SELECT office, role, first_name, last_name FROM users WHERE id = ?");
    $user_query->bind_param("i", $_SESSION['user_id']);
    $user_query->execute();
    $user_result = $user_query->get_result();
    if ($user = $user_result->fetch_assoc()) {
        echo "Current user: {$user['first_name']} {$user['last_name']}<br>";
        echo "User's office: {$user['office']}<br>";
        echo "User role: {$user['role']}<br>";
    }
    $user_query->close();
}
?>
