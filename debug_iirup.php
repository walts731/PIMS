<?php
session_start();
require_once 'config.php';
require_once 'includes/system_functions.php';
require_once 'includes/logger.php';

echo "<h2>Debug IIRUP Form Submission</h2>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check required fields
    $required_fields = ['accountable_officer', 'designation', 'department_office', 'as_of_year'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo "<p style='color: red;'>Missing required fields: " . implode(', ', $missing_fields) . "</p>";
    } else {
        echo "<p style='color: green;'>All required fields present</p>";
    }
    
    // Check if items were submitted
    if (isset($_POST['particulars']) && is_array($_POST['particulars'])) {
        $item_count = 0;
        foreach ($_POST['particulars'] as $particular) {
            if (!empty($particular)) {
                $item_count++;
            }
        }
        echo "<p>Number of items with data: $item_count</p>";
    } else {
        echo "<p style='color: red;'>No items data found</p>";
    }
    
    // Test database connection
    if ($conn->ping()) {
        echo "<p style='color: green;'>Database connection OK</p>";
    } else {
        echo "<p style='color: red;'>Database connection failed</p>";
    }
    
    // Test if tables exist
    $tables = ['iirup_forms', 'iirup_items'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>Table '$table' does not exist</p>";
        }
    }
    
    // Test SQL insert manually
    echo "<h3>Testing Manual Insert:</h3>";
    $test_form_number = 'TEST-' . date('Y-m-d-H-i-s');
    $test_sql = "INSERT INTO iirup_forms (
        form_number, as_of_year, accountable_officer, designation, department_office,
        accountable_officer_name, accountable_officer_designation, authorized_official_name,
        authorized_official_designation, inspection_officer_name, witness_name,
        status, total_items, created_by, updated_by, created_at, updated_at
    ) VALUES (
        '$test_form_number', 2024, 'Test Officer', 'Test Designation', 'Test Office',
        'Test Name', 'Test Designation', 'Test Authorized',
        'Test Auth Designation', 'Test Inspector', 'Test Witness',
        'draft', 1, 1, 1, NOW(), NOW()
    )";
    
    echo "<p>SQL: $test_sql</p>";
    
    if ($conn->query($test_sql)) {
        echo "<p style='color: green;'>Test insert successful! Form ID: " . $conn->insert_id . "</p>";
        // Clean up test record
        $conn->query("DELETE FROM iirup_forms WHERE form_number = '$test_form_number'");
    } else {
        echo "<p style='color: red;'>Test insert failed: " . $conn->error . "</p>";
    }
    
} else {
    echo "<p style='color: orange;'>No POST data - form not submitted</p>";
    echo "<p>Current request method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
}

$conn->close();
?>
