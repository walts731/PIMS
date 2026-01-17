<?php
session_start();
require_once 'config.php';

// Simulate form submission
echo "<h2>Testing IIRUP Form Submit Simulation</h2>";

// Create test POST data
$_POST = [
    'as_of_year' => '2024',
    'accountable_officer' => 'Test Officer',
    'designation' => 'Test Designation', 
    'department_office' => 'Head Office',
    'accountable_officer_name' => 'Test Officer Name',
    'accountable_officer_designation' => 'Test Officer Designation',
    'authorized_official_name' => 'Test Authorized',
    'authorized_official_designation' => 'Test Auth Designation',
    'inspection_officer_name' => 'Test Inspector',
    'witness_name' => 'Test Witness',
    'particulars' => ['Test Item 1'],
    'date_acquired' => ['2024-01-01'],
    'property_no' => ['PROP-001'],
    'qty' => ['1'],
    'unit_cost' => ['100.00'],
    'total_cost' => ['100.00'],
    'accumulated_depreciation' => ['0.00'],
    'impairment_losses' => ['0.00'],
    'carrying_amount' => ['100.00'],
    'inventory_remarks' => ['Test remarks'],
    'disposal_sale' => ['0.00'],
    'disposal_transfer' => ['0.00'],
    'disposal_destruction' => ['0.00'],
    'disposal_others' => [''],
    'disposal_total' => ['0.00'],
    'appraised_value' => ['0.00'],
    'total' => ['0.00'],
    'or_no' => [''],
    'amount' => ['0.00'],
    'dept_office' => ['Head Office'],
    'control_no' => ['CTRL-001'],
    'date_received' => ['2024-01-01']
];

// Simulate session
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;

// Set REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<p>Simulated form data created</p>";

// Now test the processing
echo "<h3>Testing Process File:</h3>";

// Change to ADMIN directory first
$original_dir = getcwd();
chdir('ADMIN');

// Include the process file
include 'process_iirup.php';

// Change back to original directory
chdir($original_dir);

echo "<p>Test completed</p>";

// Check if data was inserted
$result = $conn->query("SELECT * FROM iirup_forms ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "<h3>Latest Form Record:</h3>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>No form records found</p>";
}

$conn->close();
?>
