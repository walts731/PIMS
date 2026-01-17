<?php
require_once 'config.php';

echo "<h2>Setting up IIRUP Tables</h2>";

// Read the SQL file
$sql_file = 'database_iirup_setup.sql';
if (!file_exists($sql_file)) {
    die("Error: SQL file not found: $sql_file");
}

$sql = file_get_contents($sql_file);

// Remove comments and split SQL into individual statements
$sql = preg_replace('/--.*$/m', '', $sql); // Remove line comments
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove block comments

$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        try {
            if ($conn->query($statement)) {
                echo "<p style='color: green;'>✓ Successfully executed: " . substr($statement, 0, 100) . "...</p>";
                $success_count++;
            } else {
                echo "<p style='color: red;'>✗ Error executing: " . substr($statement, 0, 100) . "...</p>";
                echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
                $error_count++;
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
            $error_count++;
        }
    }
}

echo "<h3>Setup Summary:</h3>";
echo "<p style='color: green;'>Successful statements: $success_count</p>";
echo "<p style='color: red;'>Failed statements: $error_count</p>";

if ($error_count === 0) {
    echo "<p style='color: blue; font-weight: bold;'>IIRUP tables setup completed successfully!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Some errors occurred during setup. Please check the messages above.</p>";
}

// Verify tables were created
echo "<h3>Verifying Tables:</h3>";
$tables = ['iirup_forms', 'iirup_items'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        if ($structure) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            while ($row = $structure->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}

$conn->close();
?>
