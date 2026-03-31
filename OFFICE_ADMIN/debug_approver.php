<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Not logged in");
}

echo "<h2>Debug: Approver Information in Borrow Requests</h2>";

$office_id = $_SESSION['office_id'];
echo "<p>Current Office ID: $office_id</p>";
echo "<p>Current User ID: " . $_SESSION['user_id'] . "</p>";

// Get recent requests with approver information
$query = "SELECT br.id, br.status, br.approved_by, br.approved_at, br.approval_notes,
          requester.first_name as requester_first, requester.last_name as requester_last,
          approver.first_name as approver_first, approver.last_name as approver_last,
          o1.office_name as requester_office, o2.office_name as target_office
          FROM borrow_requests br
          LEFT JOIN users requester ON br.requested_by = requester.id
          LEFT JOIN users approver ON br.approved_by = approver.id
          LEFT JOIN offices o1 ON br.requested_by_office = o1.id
          LEFT JOIN offices o2 ON br.requested_to_office = o2.id
          WHERE (br.requested_by_office = ? OR br.requested_to_office = ?)
          ORDER BY br.created_at DESC
          LIMIT 10";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $office_id, $office_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No requests found for your office.</p>";
} else {
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>ID</th><th>Requester</th><th>From</th><th>To</th><th>Status</th><th>Approved By</th><th>Approved At</th><th>Approval Notes</th></tr></thead>";
    echo "<tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['requester_first'] . ' ' . $row['requester_last']) . "</td>";
        echo "<td>" . htmlspecialchars($row['requester_office']) . "</td>";
        echo "<td>" . htmlspecialchars($row['target_office']) . "</td>";
        echo "<td><span class='badge bg-" . getStatusBadgeClass($row['status']) . "'>" . strtoupper($row['status']) . "</span></td>";
        echo "<td>" . ($row['approved_by'] ? htmlspecialchars($row['approver_first'] . ' ' . $row['approver_last']) . " (ID: {$row['approved_by']})" : "NULL") . "</td>";
        echo "<td>" . ($row['approved_at'] ? date('M j, Y H:i', strtotime($row['approved_at'])) : "NULL") . "</td>";
        echo "<td>" . htmlspecialchars($row['approval_notes'] ?? '') . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
}

// Test current session data
echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

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

<style>
body { padding: 20px; font-family: Arial, sans-serif; }
.table { margin-top: 20px; border-collapse: collapse; width: 100%; }
.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.table th { background-color: #f2f2f2; }
.badge { padding: 4px 8px; border-radius: 4px; color: white; font-size: 12px; }
.bg-warning { background-color: #ffc107; }
.bg-success { background-color: #28a745; }
.bg-danger { background-color: #dc3545; }
.bg-info { background-color: #17a2b8; }
.bg-secondary { background-color: #6c757d; }
pre { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
</style>
