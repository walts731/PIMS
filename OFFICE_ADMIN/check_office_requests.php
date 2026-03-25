<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Not logged in");
}

$office_id = $_SESSION['office_id'];
echo "<h2>Available Requests for Office ID: $office_id ({$_SESSION['office']})</h2>";

// Get all requests for this office
$query = "SELECT br.*, 
          u1.first_name as requester_first, u1.last_name as requester_last,
          o1.office_name as requester_office,
          o2.office_name as target_office
          FROM borrow_requests br
          LEFT JOIN users u1 ON br.requested_by = u1.id
          LEFT JOIN offices o1 ON br.requested_by_office = o1.id
          LEFT JOIN offices o2 ON br.requested_to_office = o2.id
          WHERE br.requested_by_office = ? OR br.requested_to_office = ?
          ORDER BY br.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $office_id, $office_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-warning'>No requests found for your office.</div>";
} else {
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>ID</th><th>Requester</th><th>From Office</th><th>To Office</th><th>Status</th><th>Created</th><th>Test</th></tr></thead>";
    echo "<tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['requester_first'] . ' ' . $row['requester_last']) . "</td>";
        echo "<td>" . htmlspecialchars($row['requester_office']) . "</td>";
        echo "<td>" . htmlspecialchars($row['target_office']) . "</td>";
        echo "<td><span class='badge bg-" . getStatusBadgeClass($row['status']) . "'>" . strtoupper($row['status']) . "</span></td>";
        echo "<td>" . date('M j, Y H:i', strtotime($row['created_at'])) . "</td>";
        echo "<td><button class='btn btn-sm btn-primary' onclick='testRequest(" . $row['id'] . ")'>Test Details</button></td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
}

$stmt->close();

function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'warning',
        'approved' => 'success', 
        'denied' => 'danger',
        'returned' => 'info',
        'cancelled' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}
?>

<script>
function testRequest(requestId) {
    console.log('Testing request ID:', requestId);
    
    fetch(`../api/get_request_details_simple.php?request_id=${requestId}`)
        .then(response => response.text())
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                if (data.error) {
                    alert('Error: ' + data.error + '\nDebug: ' + (data.debug || 'No debug info'));
                } else {
                    alert('Success! Request found with status: ' + data.request?.status);
                }
            } catch (e) {
                alert('JSON Parse Error: ' + e.message + '\nRaw Response: ' + text.substring(0, 200));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Fetch Error: ' + error.message);
        });
}
</script>

<style>
body { padding: 20px; }
.table { margin-top: 20px; }
.alert { margin: 20px 0; }
</style>
