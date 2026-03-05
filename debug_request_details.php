<?php
session_start();
require_once 'config.php';
require_once 'includes/system_functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "Not logged in";
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    echo "Wrong role: " . $_SESSION['role'];
    exit();
}

// Get request ID from query parameter
$request_id = $_GET['request_id'] ?? 0;

if (empty($request_id) || !is_numeric($request_id)) {
    echo "Invalid request ID: " . $request_id;
    exit();
}

$office_id = $_SESSION['office_id'];

echo "Debug Info:<br>";
echo "Request ID: " . $request_id . "<br>";
echo "Office ID: " . $office_id . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";

try {
    // First, let's check if the borrow_requests table exists and get its structure
    echo "<br><strong>Checking borrow_requests table structure:</strong><br>";
    $table_check = "DESCRIBE borrow_requests";
    $result = $conn->query($table_check);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "<br>";
        }
    } else {
        echo "Error checking borrow_requests table: " . $conn->error . "<br>";
    }
    
    // Now let's try a simpler query first
    echo "<br><strong>Testing simple query:</strong><br>";
    $simple_query = "SELECT * FROM borrow_requests WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($simple_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "No request found with ID: " . $request_id . "<br>";
    } else {
        $data = $result->fetch_assoc();
        echo "Found request. Sample data:<br>";
        echo "ID: " . $data['id'] . "<br>";
        echo "Requested By: " . $data['requested_by'] . "<br>";
        echo "Requested By Office: " . $data['requested_by_office'] . "<br>";
        echo "Requested To Office: " . $data['requested_to_office'] . "<br>";
        echo "Asset ID: " . $data['asset_id'] . "<br>";
        echo "Status: " . $data['status'] . "<br>";
    }
    
    // Check if related tables exist
    echo "<br><strong>Checking related tables:</strong><br>";
    
    $tables_to_check = ['users', 'offices', 'asset_items', 'asset_categories', 'assets'];
    
    foreach ($tables_to_check as $table) {
        $check = "SHOW TABLES LIKE '$table'";
        $result = $conn->query($check);
        if ($result && $result->num_rows > 0) {
            echo "✓ Table '$table' exists<br>";
        } else {
            echo "✗ Table '$table' does not exist<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
    echo "Error in database connection or query<br>";
}
?>
