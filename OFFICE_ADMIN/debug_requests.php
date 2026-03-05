<?php
session_start();
require_once '../config.php';

// Get office ID from session
$office_id = $_SESSION['office_id'] ?? null;

echo "<h2>Debug: Borrow Requests for Office ID: $office_id</h2>";

if ($office_id && $conn) {
    // Check all requests from this office (last 7 days)
    $query = "SELECT 
                id, requested_by, requested_by_office, requested_to_office, 
                asset_id, quantity_requested, status, purpose, 
                created_at, start_date, end_date
             FROM borrow_requests 
             WHERE requested_by_office = ? 
             ORDER BY created_at DESC 
             LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<h3>Requests sent by your office (last 10):</h3>";
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Asset ID</th><th>To Office</th><th>Quantity</th><th>Status</th><th>Created At</th><th>Within 7 days?</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $created_at = $row['created_at'];
            $within_7_days = (strtotime($created_at) >= strtotime('-7 days')) ? 'Yes' : 'No';
            $date_class = $within_7_days === 'Yes' ? 'style="background-color: #90EE90;"' : '';
            
            echo "<tr $date_class>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['asset_id']}</td>";
            echo "<td>{$row['requested_to_office']}</td>";
            echo "<td>{$row['quantity_requested']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$created_at}</td>";
            echo "<td>$within_7_days</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No requests found from your office.</p>";
    }
    
    // Check what the 7-day trends query would return
    echo "<h3>What 7-day trends query returns:</h3>";
    $trends_query = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as requests_count,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
                     FROM borrow_requests 
                     WHERE requested_by_office = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     GROUP BY DATE(created_at)
                     ORDER BY date";
    
    $stmt = $conn->prepare($trends_query);
    $stmt->bind_param("i", $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Date</th><th>Requests Count</th><th>Approved Count</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['date']}</td>";
            echo "<td>{$row['requests_count']}</td>";
            echo "<td>{$row['approved_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No requests found in the last 7 days.</p>";
    }
    
    // Check all requests in the system for debugging
    echo "<h3>All requests in system (last 5):</h3>";
    $all_query = "SELECT 
                    id, requested_by, requested_by_office, requested_to_office, 
                    asset_id, status, created_at
                 FROM borrow_requests 
                 ORDER BY created_at DESC 
                 LIMIT 5";
    
    $result = $conn->query($all_query);
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>By Office</th><th>To Office</th><th>Status</th><th>Created At</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['requested_by_office']}</td>";
            echo "<td>{$row['requested_to_office']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No requests found in the system.</p>";
    }
} else {
    echo "<p>Error: No office ID or database connection.</p>";
}
?>

<style>
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
