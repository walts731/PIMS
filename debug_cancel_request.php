<?php
session_start();
require_once 'config.php';
require_once 'includes/system_functions.php';
require_once 'includes/logger.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Cancel Request</h2>";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<p style='color: red;'>User not logged in</p>";
    exit();
}

echo "<p style='color: green;'>User logged in: " . $_SESSION['user_id'] . "</p>";
echo "<p>Role: " . $_SESSION['role'] . "</p>";
echo "<p>Office ID: " . $_SESSION['office_id'] . "</p>";

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    echo "<p style='color: red;'>Wrong role. Expected: office_admin, Got: " . $_SESSION['role'] . "</p>";
    exit();
}

// Test database connection
if (!$conn) {
    echo "<p style='color: red;'>Database connection failed</p>";
    exit();
}

echo "<p style='color: green;'>Database connected</p>";

// Test if cancelled status exists in the table
$result = $conn->query("SHOW COLUMNS FROM borrow_requests LIKE 'status'");
$row = $result->fetch_assoc();
echo "<p>Status column type: " . $row['Type'] . "</p>";

if (strpos($row['Type'], 'cancelled') === false) {
    echo "<p style='color: red;'>CANCELLED STATUS NOT FOUND IN DATABASE!</p>";
    echo "<p>Please run the migration first.</p>";
} else {
    echo "<p style='color: green;'>Cancelled status exists in database</p>";
}

// Test query with a sample request ID
$test_request_id = 1; // Change this to a valid request ID
echo "<h3>Testing with request ID: $test_request_id</h3>";

$query = "SELECT br.* FROM borrow_requests br 
          WHERE br.id = ? AND (br.requested_by_office = ? OR br.requested_to_office = ?)
          AND br.status = 'pending'";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $test_request_id, $_SESSION['office_id'], $_SESSION['office_id']);
$stmt->execute();
$result = $stmt->get_result();

echo "<p>Query executed. Found " . $result->num_rows . " pending requests</p>";

if ($result->num_rows > 0) {
    $request_data = $result->fetch_assoc();
    echo "<p>Request found: ID=" . $request_data['id'] . ", Status=" . $request_data['status'] . "</p>";
    
    // Test the update query
    echo "<h3>Testing update query...</h3>";
    $update_query = "UPDATE borrow_requests 
                    SET status = 'cancelled', 
                        updated_at = NOW() 
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $test_request_id);
    
    if ($update_stmt->execute()) {
        echo "<p style='color: green;'>Update query successful!</p>";
        
        // Revert the change for testing
        $revert_query = "UPDATE borrow_requests SET status = 'pending' WHERE id = ?";
        $revert_stmt = $conn->prepare($revert_query);
        $revert_stmt->bind_param("i", $test_request_id);
        $revert_stmt->execute();
        echo "<p>Test completed - reverted changes</p>";
    } else {
        echo "<p style='color: red;'>Update query failed: " . $conn->error . "</p>";
    }
} else {
    echo "<p>No pending requests found for testing</p>";
}
?>
