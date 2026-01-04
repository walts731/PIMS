<?php
// Backup System Test Script
// This script helps diagnose backup issues

echo "<h2>Backup System Diagnostics</h2>";

// Test 1: Check PHP extensions
echo "<h3>1. PHP Extensions Check</h3>";
echo "<ul>";
echo "<li>ZipArchive: " . (class_exists('ZipArchive') ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>') . "</li>";
echo "<li>mysqli: " . (extension_loaded('mysqli') ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>') . "</li>";
echo "</ul>";

// Test 2: Check mysqldump availability
echo "<h3>2. mysqldump Command Check</h3>";
$commands = [
    'mysqldump --version',
    'whereis mysqldump',
    'which mysqldump'
];

foreach ($commands as $cmd) {
    echo "<p>Testing: <code>$cmd</code><br>";
    $output = shell_exec($cmd . ' 2>&1');
    if ($output && !empty(trim($output))) {
        echo "<span style='color: green;'>✓ Output: " . htmlspecialchars(trim($output)) . "</span></p>";
    } else {
        echo "<span style='color: red;'>✗ No output</span></p>";
    }
}

// Test 3: Check directory permissions
echo "<h3>3. Directory Permissions</h3>";
$backup_dir = '../backups';
echo "<p>Backup directory: <code>$backup_dir</code><br>";
if (is_dir($backup_dir)) {
    echo "<span style='color: green;'>✓ Directory exists</span><br>";
    if (is_writable($backup_dir)) {
        echo "<span style='color: green;'>✓ Directory is writable</span></p>";
    } else {
        echo "<span style='color: red;'>✗ Directory is not writable</span></p>";
    }
} else {
    echo "<span style='color: orange;'>⚠ Directory does not exist</span><br>";
    if (mkdir($backup_dir, 0755, true)) {
        echo "<span style='color: green;'>✓ Created successfully</span></p>";
    } else {
        echo "<span style='color: red;'>✗ Failed to create</span></p>";
    }
}

// Test 4: Database connection
echo "<h3>4. Database Connection</h3>";
try {
    require_once '../config.php';
    echo "<span style='color: green;'>✓ Database connection successful</span><br>";
    echo "Host: " . $host . "<br>";
    echo "Database: " . $database . "<br>";
    echo "User: " . $username . "<br>";
    
    // Test table creation
    $table_check = $conn->prepare("DESCRIBE backups");
    if ($table_check->execute()) {
        echo "<span style='color: green;'>✓ Backups table exists</span>";
    } else {
        echo "<span style='color: orange;'>⚠ Backups table does not exist</span>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</span>";
}

// Test 5: File permissions test
echo "<h3>5. File Write Test</h3>";
$test_file = '../backups/test_' . time() . '.txt';
if (file_put_contents($test_file, 'test')) {
    echo "<span style='color: green;'>✓ Can write files to backup directory</span><br>";
    unlink($test_file);
    echo "<span style='color: green;'>✓ Can delete files from backup directory</span>";
} else {
    echo "<span style='color: red;'>✗ Cannot write files to backup directory</span>";
}

echo "<h3>Recommendations</h3>";
echo "<ul>";
if (!class_exists('ZipArchive')) {
    echo "<li><strong>Install ZipArchive extension:</strong> <code>apt-get install php-zip</code> (Ubuntu) or enable in php.ini</li>";
}
if (!shell_exec('mysqldump --version 2>&1')) {
    echo "<li><strong>Install mysqldump:</strong> Usually comes with MySQL/MariaDB installation</li>";
}
echo "<li><strong>Run database setup:</strong> Execute database_backups_setup.sql to create the backups table</li>";
echo "<li><strong>Check permissions:</strong> Ensure web server can write to the backups directory</li>";
echo "</ul>";

echo "<p><a href='backup.php'>← Back to Backup System</a></p>";
?>
