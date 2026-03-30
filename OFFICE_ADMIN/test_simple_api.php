<?php
// Simple test to check API functionality
echo "<h2>API Test Results</h2>";

// Test 1: Check if config.php loads
echo "<h3>Test 1: Config Loading</h3>";
try {
    require_once '../config.php';
    echo "✅ Config loaded successfully<br>";
    echo "Database connection: " . ($conn ? "✅ Connected" : "❌ Failed") . "<br>";
    if ($conn && $conn->connect_error) {
        echo "❌ Database error: " . $conn->connect_error . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 2: Check session
echo "<h3>Test 2: Session</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "Logged in: " . ($_SESSION['logged_in'] ?? 'false') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'none') . "<br>";
echo "Office ID: " . ($_SESSION['office_id'] ?? 'none') . "<br>";

// Test 3: Test search function directly
echo "<h3>Test 3: Direct Search Function</h3>";
try {
    // Simulate session
    $_SESSION['logged_in'] = true;
    $_SESSION['role'] = 'office_admin';
    $_SESSION['office_id'] = 5;
    
    echo "Session set to office_admin<br>";
    
    // Test a simple query
    if ($conn) {
        $sql = "SELECT COUNT(*) as count FROM asset_items LIMIT 1";
        $result = $conn->query($sql);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ Simple query successful: Found $count assets<br>";
        } else {
            echo "❌ Simple query failed: " . $conn->error . "<br>";
        }
        
        // Test search query
        $searchTerm = '%laptop%';
        $sql = "SELECT id, description, model FROM asset_items WHERE description LIKE ? OR model LIKE ? LIMIT 3";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            echo "✅ Search query executed<br>";
            echo "Results found: " . $result->num_rows . "<br>";
            
            while ($row = $result->fetch_assoc()) {
                echo "- " . $row['description'] . " (" . $row['model'] . ")<br>";
            }
            $stmt->close();
        } else {
            echo "❌ Search prepare failed: " . $conn->error . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Search error: " . $e->getMessage() . "<br>";
}

// Test 4: Test API file directly
echo "<h3>Test 4: API File Test</h3>";
echo "<a href='api/search.php?q=laptop&limit=3' target='_blank'>Click here to test API directly</a><br>";
echo "<a href='api/search_debug.php?q=laptop&limit=3' target='_blank'>Click here to test debug API</a><br>";

echo "<h3>Test 5: Manual API Call</h3>";
try {
    // Set up session
    $_SESSION['logged_in'] = true;
    $_SESSION['role'] = 'office_admin';
    $_SESSION['office_id'] = 5;
    
    // Include the search API file
    ob_start();
    include 'api/search.php';
    $output = ob_get_clean();
    
    echo "API Output:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ API include error: " . $e->getMessage() . "<br>";
}

echo "<h3>Test 6: Check File Permissions</h3>";
echo "API file exists: " . (file_exists('api/search.php') ? "✅ Yes" : "❌ No") . "<br>";
echo "API file readable: " . (is_readable('api/search.php') ? "✅ Yes" : "❌ No") . "<br>";
echo "API file size: " . filesize('api/search.php') . " bytes<br>";
?>
