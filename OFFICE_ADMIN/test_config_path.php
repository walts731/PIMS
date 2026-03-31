<?php
// Test config path from OFFICE_ADMIN directory
echo "Current working directory: " . getcwd() . "<br>";
echo "Script location: " . __FILE__ . "<br>";
echo "Script directory: " . __DIR__ . "<br>";

// Test different paths
$paths = [
    '../config.php',
    '../../config.php',
    '../../../config.php',
    dirname(__DIR__) . '/config.php'
];

foreach ($paths as $path) {
    echo "Testing path: $path - ";
    if (file_exists($path)) {
        echo "EXISTS<br>";
    } else {
        echo "NOT FOUND<br>";
    }
}

// Test absolute path
$abs_path = dirname(dirname(__DIR__)) . '/config.php';
echo "Absolute path: $abs_path - ";
if (file_exists($abs_path)) {
    echo "EXISTS<br>";
} else {
    echo "NOT FOUND<br>";
}
?>
