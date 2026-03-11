<?php
// Simple test to verify database connection and data
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

echo "<h2>Database Connection Test</h2>";

if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

echo "<h2>Categories Test</h2>";
$cat_result = $conn->query("SELECT id, category_code, category_name FROM asset_categories WHERE status = 'active' ORDER BY category_code");
if ($cat_result && $cat_result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Code</th><th>Name</th></tr>";
    while ($cat = $cat_result->fetch_assoc()) {
        echo "<tr><td>{$cat['id']}</td><td>{$cat['category_code']}</td><td>{$cat['category_name']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No categories found</p>";
}

echo "<h2>Subcategories for Category 2 (ITS)</h2>";
$sub_result = $conn->query("SELECT id, sub_category_code, sub_category_name FROM asset_sub_categories WHERE asset_categories_id = 2 AND status = 'active' ORDER BY sub_category_code");
if ($sub_result && $sub_result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Code</th><th>Name</th></tr>";
    while ($sub = $sub_result->fetch_assoc()) {
        echo "<tr><td>{$sub['id']}</td><td>{$sub['sub_category_code']}</td><td>{$sub['sub_category_name']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No subcategories found for category 2</p>";
}

echo "<h2>API Test</h2>";
echo "<p>Try this URL: <a href='get_subcategories_simple.php?category_id=2'>get_subcategories_simple.php?category_id=2</a></p>";
?>
