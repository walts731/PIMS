<?php
// Simple PIMS entry point
echo "<h1>PIMS System</h1>";
echo "<p>System is working!</p>";

// List available directories
$dirs = ['ADMIN', 'MAIN_USER', 'SYSTEM_ADMIN', 'FUEL'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p><a href='$dir/'>$dir/</a></p>";
    }
}

// Test config
if (file_exists('config.php')) {
    echo "<p style='color: green;'>✅ Config file found</p>";
    try {
        require_once 'config.php';
        echo "<p style='color: green;'>✅ Config loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Config error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Config file not found</p>";
}
?>
