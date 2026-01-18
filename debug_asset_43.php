<?php
require_once 'config.php';

echo "<h2>Debug Asset ID 43</h2>";

// Check the specific asset
$stmt = $conn->prepare("SELECT * FROM asset_items WHERE id = ?");
$stmt->bind_param("i", $id);
$id = 43;
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "<h3>Asset Details:</h3>";
    echo "<table border='1'>";
    foreach ($row as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    // Check if it should appear in search
    echo "<h3>Search Criteria Check:</h3>";
    echo "<p><strong>Status:</strong> " . htmlspecialchars($row['status']) . " (should NOT be 'unserviceable')</p>";
    echo "<p><strong>Property No:</strong> '" . htmlspecialchars($row['property_no'] ?? 'NULL') . "' (should NOT be NULL or empty)</p>";
    
    $status_ok = $row['status'] !== 'unserviceable';
    $property_no_ok = $row['property_no'] !== null && $row['property_no'] !== '';
    
    echo "<p><strong>Passes Status Check:</strong> " . ($status_ok ? "✅ YES" : "❌ NO") . "</p>";
    echo "<p><strong>Passes Property No Check:</strong> " . ($property_no_ok ? "✅ YES" : "❌ NO") . "</p>";
    echo "<p><strong>Should Appear in Search:</strong> " . ($status_ok && $property_no_ok ? "✅ YES" : "❌ NO") . "</p>";
    
} else {
    echo "<p style='color: red;'>Asset ID 43 not found!</p>";
}

// Test the actual search query
echo "<h3>Test Search Query:</h3>";
$searchTerm = "%43%"; // Searching for ID 43

$sql = "SELECT ai.id, ai.description, ai.value, ai.acquisition_date, ai.status, 
               ai.property_no, o.office_name 
        FROM asset_items ai 
        LEFT JOIN offices o ON ai.office_id = o.id 
        WHERE (ai.description LIKE ? OR ai.id LIKE ? OR ai.property_no LIKE ?) 
        AND ai.status != 'unserviceable'
        AND ai.property_no IS NOT NULL 
        AND ai.property_no != ''
        ORDER BY ai.description
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

echo "<p><strong>Query:</strong><br><pre>" . htmlspecialchars($sql) . "</pre></p>";
echo "<p><strong>Search Term:</strong> '$searchTerm'</p>";

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo "<h4>Results (" . count($assets) . " found):</h4>";
if (empty($assets)) {
    echo "<p style='color: red;'>No results found!</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Description</th><th>Property No</th><th>Status</th><th>Office</th></tr>";
    foreach ($assets as $asset) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($asset['id']) . "</td>";
        echo "<td>" . htmlspecialchars($asset['description']) . "</td>";
        echo "<td>" . htmlspecialchars($asset['property_no']) . "</td>";
        echo "<td>" . htmlspecialchars($asset['status']) . "</td>";
        echo "<td>" . htmlspecialchars($asset['office_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
