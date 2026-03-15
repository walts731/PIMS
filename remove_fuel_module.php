<?php
// PIMS Fuel Module Removal Script

echo "<h1>PIMS Fuel Module Removal</h1>";

// List of fuel-related files to remove
$fuel_files = [
    'FUEL/dashboard.php',
    'FUEL/fuel_in.php', 
    'FUEL/fuel_out.php',
    'FUEL/fuel_tabs/fuel_in.php',
    'FUEL/fuel_tabs/fuel_out.php',
    'FUEL/fuel_tabs/inventory.php',
    'FUEL/fuel_tabs/reports.php',
    'FUEL/fuel_tabs/records.php',
    'FUEL/fuel_tabs/modal_content.php',
    'FUEL/fuel_tabs/process_fuel.php',
    'FUEL/fuel_tabs/export_fuel_report.php',
    'FUEL/add_image_column.php',
    'FUEL/add_image_column_ui.php',
    'FUEL/diagnostic.php',
    'FUEL/direct_fix.php',
    'FUEL/fuel_out_processor.php',
    'FUEL/fuel_out_processor_main.php',
    'FUEL/fuel_out_processor_simple.php',
    'FUEL/fuel_out_final.php',
    'FUEL/fuel_out_working.php',
    'FUEL/fuel_out_working_final.php',
    'FUEL/fuel_out_original.php',
    'FUEL/quick_fix.php',
    'FUEL/test_fuel_out.php',
    'FUEL/ultimate_fix.php',
    'FUEL/fix_404.php',
    'FUEL/fix_json_error.php',
    'FUEL/record_fuel_out.php',
    'FUEL/test_xampp.php',
    'FUEL/entry.php',
    'FUEL/admin.php'
];

echo "<h2>Fuel Files to Remove</h2>";
echo "<ul>";
foreach ($fuel_files as $file) {
    echo "<li>$file</li>";
}
echo "</ul>";

echo "<hr>";

// Check if files exist and remove them
$removed = 0;
$failed = 0;

foreach ($fuel_files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p style='color: green;'>✅ Removed: $file</p>";
            $removed++;
        } else {
            echo "<p style='color: red;'>❌ Failed to remove: $file</p>";
            $failed++;
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ File not found: $file</p>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p style='color: green;'>✅ Files removed: $removed</p>";
echo "<p style='color: red;'>❌ Failed to remove: $failed</p>";
echo "<p style='color: blue;'>ℹ️ Files not found: " . (count($fuel_files) - $removed - $failed) . "</p>";

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Remove fuel-related database tables (optional)</li>";
echo "<li>Update main dashboard to remove fuel menu items</li>";
echo "<li>Clear any fuel-related session data</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='../index.php' class='btn btn-primary'>Return to Main Dashboard</a></p>";
?>
