<?php
require_once 'config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h3>Debug: Serviceable Assets Check</h3>";

// Check serviceable assets
$stmt = $conn->prepare("SELECT a.description, ai.description as item_description, COUNT(ai.id) as available_count, GROUP_CONCAT(ai.id) as asset_ids
                       FROM asset_items ai 
                       JOIN assets a ON ai.asset_id = a.id 
                       WHERE ai.status = 'serviceable' 
                       GROUP BY a.description, ai.description 
                       ORDER BY a.description, ai.description");
$stmt->execute();
$result = $stmt->get_result();

echo '<p><strong>Serviceable assets found: ' . $result->num_rows . '</strong></p>';

if ($result->num_rows > 0) {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Asset Description</th><th>Item Description</th><th>Available Count</th><th>Asset IDs</th></tr>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['description']) . '</td>';
        echo '<td>' . htmlspecialchars($row['item_description']) . '</td>';
        echo '<td>' . $row['available_count'] . '</td>';
        echo '<td>' . htmlspecialchars($row['asset_ids']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p style="color: red;">No serviceable assets found!</p>';
    
    // Check what asset statuses exist
    echo '<h4>All Asset Items Status:</h4>';
    $status_stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM asset_items GROUP BY status");
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Status</th><th>Count</th></tr>';
    while ($status_row = $status_result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($status_row['status']) . '</td>';
        echo '<td>' . $status_row['count'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

$stmt->close();
$conn->close();
?>
