<?php
// Test config file loading
echo "Testing config.php loading...<br>";

try {
    require_once '../config.php';
    echo "✅ Config loaded successfully<br>";
    
    if ($conn) {
        echo "✅ Database connection established<br>";
        echo "Host: " . $conn->host_info . "<br>";
        
        // Test a simple query
        $result = $conn->query("SELECT COUNT(*) as count FROM asset_items");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ Test query successful - Found $count assets<br>";
        } else {
            echo "❌ Test query failed: " . $conn->error . "<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>Session info:<br>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "Logged in: " . ($_SESSION['logged_in'] ?? 'false') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'none') . "<br>";
echo "Office ID: " . ($_SESSION['office_id'] ?? 'none') . "<br>";
?>
