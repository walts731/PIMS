<?php
// Direct test without any includes first
echo "<h2>Direct PHP Test</h2>";

// Test basic PHP functionality
echo "<p>✅ PHP is working</p>";

// Test database connection
try {
    require_once '../config.php';
    if ($conn) {
        echo "<p>✅ Database connection successful</p>";
        
        // Test the exact query we're using
        $category_id = 2;
        $sql = "SELECT id, sub_category_code, sub_category_name 
                FROM asset_sub_categories 
                WHERE asset_categories_id = ? AND status = 'active' 
                ORDER BY sub_category_code";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subcategories = [];
        while ($row = $result->fetch_assoc()) {
            $subcategories[] = [
                'id' => $row['id'],
                'code' => $row['sub_category_code'],
                'name' => $row['sub_category_name']
            ];
        }
        
        echo "<h3>Found " . count($subcategories) . " subcategories for category 2:</h3>";
        echo "<ul>";
        foreach ($subcategories as $sub) {
            echo "<li>{$sub['code']} - {$sub['name']}</li>";
        }
        echo "</ul>";
        
        // Test JSON output
        echo "<h3>JSON Output Test:</h3>";
        echo "<pre>";
        echo json_encode([
            'success' => true, 
            'subcategories' => $subcategories,
            'debug' => [
                'category_id' => $category_id,
                'count' => count($subcategories),
                'message' => count($subcategories) > 0 ? 'Subcategories found' : 'No active subcategories for this category'
            ]
        ], JSON_PRETTY_PRINT);
        echo "</pre>";
        
    } else {
        echo "<p>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
