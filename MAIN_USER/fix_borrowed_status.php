<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Access denied. Please log in.");
}

if (!in_array($_SESSION['role'], ['system_admin', 'admin'], true)) {
    die("Access denied. Admin role required.");
}

echo "<h2>Fix Borrowed Asset Status</h2>";

try {
    // Update all assets with approved borrow requests to have status='borrowed'
    $update_sql = "
        UPDATE asset_items ai 
        JOIN borrow_requests br ON br.asset_id = ai.id 
        SET ai.status = 'borrowed' 
        WHERE br.status = 'approved' AND ai.status != 'borrowed'
    ";
    
    $result = $conn->query($update_sql);
    
    if ($result) {
        $affected_rows = $conn->affected_rows;
        echo "<p style='color: green;'>✅ Updated {$affected_rows} assets to 'borrowed' status.</p>";
        
        // Show what was updated
        $check_sql = "
            SELECT ai.id, ai.property_no, ai.status, o.office_name, 
                   br.id as borrow_id, br.status as borrow_status,
                   u.first_name, u.last_name
            FROM asset_items ai 
            JOIN offices o ON o.id = ai.office_id 
            JOIN borrow_requests br ON br.asset_id = ai.id 
            LEFT JOIN users u ON u.id = br.requested_by
            WHERE ai.status = 'borrowed'
            ORDER BY o.office_name, ai.property_no
        ";
        
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            echo "<h3>Assets Now Marked as Borrowed:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Property No</th><th>Office</th><th>Status</th><th>Borrower</th><th>Borrow Req ID</th></tr>";
            
            while ($row = $check_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['property_no']) . "</td>";
                echo "<td>" . htmlspecialchars($row['office_name']) . "</td>";
                echo "<td style='color: orange; font-weight: bold;'>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['borrow_id']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>No assets found with 'borrowed' status after update.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Error updating assets: " . $conn->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='assets.php'>← Back to Assets</a>";
?>
