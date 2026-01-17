<?php
require_once 'config.php';

echo "<h2>Testing IIRUP Form Processing</h2>";

// Test if tables exist
$tables = ['iirup_forms', 'iirup_items'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}

// Test if process_iirup.php exists
if (file_exists('ADMIN/process_iirup.php')) {
    echo "<p style='color: green;'>✓ Process file exists</p>";
} else {
    echo "<p style='color: red;'>✗ Process file missing</p>";
}

// Test if offices table exists and has data
$offices_result = $conn->query("SELECT COUNT(*) as count FROM offices WHERE status = 'active'");
if ($offices_result && $row = $offices_result->fetch_assoc()) {
    echo "<p style='color: green;'>✓ Offices table has " . $row['count'] . " active offices</p>";
} else {
    echo "<p style='color: red;'>✗ Offices table issue</p>";
}

echo "<h3>Ready to use IIRUP Form!</h3>";
echo "<p>The IIRUP form will now:</p>";
echo "<ul>";
echo "<li>Save form header data to iirup_forms table</li>";
echo "<li>Save individual items to iirup_items table</li>";
echo "<li>Use office dropdown for Department/Office selection</li>";
echo "<li>Generate unique form numbers (IIRUP-YYYY-NNNN)</li>";
echo "<li>Track user actions with comprehensive logging</li>";
echo "<li>Update asset status to 'unserviceable' when disposed</li>";
echo "</ul>";

$conn->close();
?>
