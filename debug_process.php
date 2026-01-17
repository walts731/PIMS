<?php
session_start();
require_once 'config.php';
require_once 'includes/system_functions.php';
require_once 'includes/logger.php';

// Simulate session
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;

// Set REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'POST';

// Test POST data
$_POST = [
    'as_of_year' => '2024',
    'accountable_officer' => 'Debug Test Officer',
    'designation' => 'Debug Test Designation', 
    'department_office' => 'Head Office',
    'accountable_officer_name' => 'Debug Test Name',
    'accountable_officer_designation' => 'Debug Test Designation',
    'authorized_official_name' => 'Debug Authorized',
    'authorized_official_designation' => 'Debug Auth Designation',
    'inspection_officer_name' => 'Debug Inspector',
    'witness_name' => 'Debug Witness',
    'particulars' => ['Debug Test Item'],
    'date_acquired' => ['2024-01-01'],
    'property_no' => ['PROP-001'],
    'qty' => ['1'],
    'unit_cost' => ['100.00'],
    'total_cost' => ['100.00'],
    'accumulated_depreciation' => ['0.00'],
    'impairment_losses' => ['0.00'],
    'carrying_amount' => ['100.00'],
    'inventory_remarks' => ['Debug test remarks'],
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

echo "<h2>Debug Process File</h2>";

// Change to ADMIN directory
$original_dir = getcwd();
chdir('ADMIN');

// Test the exact same SQL as process file
try {
    $conn->begin_transaction();
    
    // Get form data
    $as_of_year = $_POST['as_of_year'];
    $accountable_officer = $_POST['accountable_officer'];
    $designation = $_POST['designation'];
    $department_office = $_POST['department_office'];
    $accountable_officer_name = $_POST['accountable_officer_name'];
    $accountable_officer_designation = $_POST['accountable_officer_designation'];
    $authorized_official_name = $_POST['authorized_official_name'];
    $authorized_official_designation = $_POST['authorized_official_designation'];
    $inspection_officer_name = $_POST['inspection_officer_name'];
    $witness_name = $_POST['witness_name'];
    
    // Generate form number
    $form_number = 'IIRUP-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Get total items count
    $total_items = count($_POST['particulars']);
    
    // Insert into iirup_forms table
    $form_number = $conn->real_escape_string($form_number);
    $as_of_year = (int)$as_of_year;
    $accountable_officer = $conn->real_escape_string($accountable_officer);
    $designation = $conn->real_escape_string($designation);
    $department_office = $conn->real_escape_string($department_office);
    $accountable_officer_name = $conn->real_escape_string($accountable_officer_name);
    $accountable_officer_designation = $conn->real_escape_string($accountable_officer_designation);
    $authorized_official_name = $conn->real_escape_string($authorized_official_name);
    $authorized_official_designation = $conn->real_escape_string($authorized_official_designation);
    $inspection_officer_name = $conn->real_escape_string($inspection_officer_name);
    $witness_name = $conn->real_escape_string($witness_name);
    $total_items = (int)$total_items;
    $created_by = (int)$_SESSION['user_id'];
    $updated_by = (int)$_SESSION['user_id'];
    
    $sql = "INSERT INTO iirup_forms (
        form_number, as_of_year, accountable_officer, designation, department_office,
        accountable_officer_name, accountable_officer_designation, authorized_official_name,
        authorized_official_designation, inspection_officer_name, witness_name,
        status, total_items, created_by, updated_by, created_at, updated_at
    ) VALUES (
        '$form_number', $as_of_year, '$accountable_officer', '$designation', '$department_office',
        '$accountable_officer_name', '$accountable_officer_designation', '$authorized_official_name',
        '$authorized_official_designation', '$inspection_officer_name', '$witness_name',
        'draft', $total_items, $created_by, $updated_by, NOW(), NOW()
    )";
    
    echo "<p><strong>SQL:</strong><br><pre>" . htmlspecialchars($sql) . "</pre></p>";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "<p style='color: red;'><strong>SQL Error:</strong> " . htmlspecialchars($conn->error) . "</p>";
        $conn->rollback();
    } else {
        $form_id = $conn->insert_id;
        echo "<p style='color: green;'><strong>Success!</strong> Form ID: $form_id</p>";
        $conn->commit();
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    $conn->rollback();
}

// Change back to original directory
chdir($original_dir);

$conn->close();
?>
