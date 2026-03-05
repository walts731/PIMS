<?php
session_start();
require_once 'config.php';

echo "<h2>Debug Session and User Data</h2>";

// Check session data
echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user_id exists in session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<h3>User ID from session: $user_id</h3>";
    
    // Check if this user exists in the database
    $check_query = "SELECT id, username, email, first_name, last_name, role, is_active FROM users WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<h3 style='color: green;'>User Found in Database:</h3>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "<h3 style='color: red;'>ERROR: User ID $user_id NOT FOUND in users table!</h3>";
        
        // Show all users for reference
        echo "<h4>All users in database:</h4>";
        $all_users_query = "SELECT id, username, email, first_name, last_name, role, is_active FROM users ORDER BY id";
        $all_result = $conn->query($all_users_query);
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Role</th><th>Active</th></tr>";
        while ($row = $all_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['first_name']} {$row['last_name']}</td>";
            echo "<td>{$row['role']}</td>";
            echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h3 style='color: red;'>ERROR: No user_id found in session!</h3>";
}

// Check office_id as well
if (isset($_SESSION['office_id'])) {
    $office_id = $_SESSION['office_id'];
    echo "<h3>Office ID from session: $office_id</h3>";
    
    // Check if this office exists
    $office_check = "SELECT id, office_name, office_code FROM offices WHERE id = ?";
    $stmt = $conn->prepare($office_check);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $office = $result->fetch_assoc();
        echo "<h3 style='color: green;'>Office Found:</h3>";
        echo "<pre>";
        print_r($office);
        echo "</pre>";
    } else {
        echo "<h3 style='color: red;'>ERROR: Office ID $office_id NOT FOUND!</h3>";
    }
}
?>
