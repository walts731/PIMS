<?php
require_once 'config.php';

echo "<h2>Checking IIRUP Records</h2>";

// Check forms table
$result = $conn->query("SELECT * FROM iirup_forms ORDER BY id DESC LIMIT 5");
if ($result && $row = $result->fetch_assoc()) {
    echo "<h3>Latest Form Records:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Form Number</th><th>Officer</th><th>Office</th><th>Status</th><th>Created</th></tr>";
    do {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['form_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['accountable_officer']) . "</td>";
        echo "<td>" . htmlspecialchars($row['department_office']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    } while ($row = $result->fetch_assoc());
    echo "</table>";
} else {
    echo "<p>No form records found</p>";
}

// Check items table
$result = $conn->query("SELECT * FROM iirup_items ORDER BY id DESC LIMIT 5");
if ($result && $row = $result->fetch_assoc()) {
    echo "<h3>Latest Item Records:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Form ID</th><th>Particulars</th><th>Property No</th><th>Quantity</th></tr>";
    do {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['form_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['particulars']) . "</td>";
        echo "<td>" . htmlspecialchars($row['property_no']) . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "</tr>";
    } while ($row = $result->fetch_assoc());
    echo "</table>";
} else {
    echo "<p>No item records found</p>";
}

$conn->close();
?>
