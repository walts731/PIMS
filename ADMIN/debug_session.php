<?php
session_start();

echo "<h2>Session Debug Information</h2>";

echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "<br>";

echo "<h3>Session Variables:</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h3>Cookie Information:</h3>";
echo "<pre>";
var_dump($_COOKIE);
echo "</pre>";

echo "<h3>Current URL:</h3>";
echo "Current script: " . $_SERVER['PHP_SELF'] . "<br>";
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    echo "<h3>Authentication Status:</h3>";
    echo "✅ User is logged in<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User Role: " . $_SESSION['user_role'] . "<br>";
    
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'system_admin') {
        echo "✅ User has admin privileges<br>";
    } else {
        echo "❌ User does not have admin privileges<br>";
    }
} else {
    echo "<h3>Authentication Status:</h3>";
    echo "❌ User is not logged in<br>";
    echo "user_id isset: " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . "<br>";
    echo "user_role isset: " . (isset($_SESSION['user_role']) ? 'Yes' : 'No') . "<br>";
}

echo "<br><a href='../index.php'>Go to Login</a> | <a href='borrowing.php'>Try Borrowing Again</a>";
?>
