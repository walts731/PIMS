<?php
require_once 'config.php';

echo "<h2>Direct IIRUP Insert Test</h2>";

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Generate form number
    $form_number = 'IIRUP-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Direct insert test
    $sql = "INSERT INTO iirup_forms (
        form_number, as_of_year, accountable_officer, designation, department_office,
        accountable_officer_name, accountable_officer_designation, authorized_official_name,
        authorized_official_designation, inspection_officer_name, witness_name,
        status, total_items, created_by, updated_by, created_at, updated_at
    ) VALUES (
        '$form_number', 2024, 'Direct Test Officer', 'Direct Test Designation', 'Head Office',
        'Direct Test Name', 'Direct Test Designation', 'Direct Authorized',
        'Direct Auth Designation', 'Direct Inspector', 'Direct Witness',
        'draft', 1, 1, 1, NOW(), NOW()
    )";
    
    echo "<p>SQL: $sql</p>";
    
    $result = $conn->query($sql);
    if ($result) {
        $form_id = $conn->insert_id;
        echo "<p style='color: green;'>✓ Form insert successful! ID: $form_id</p>";
        
        // Insert test item
        $item_sql = "INSERT INTO iirup_items (
            form_id, date_acquired, particulars, property_no, quantity, unit_cost, total_cost,
            accumulated_depreciation, impairment_losses, carrying_amount, inventory_remarks,
            disposal_sale, disposal_transfer, disposal_destruction, disposal_others, disposal_total,
            appraised_value, total, or_no, amount, dept_office, control_no, date_received,
            item_order, created_at, updated_at
        ) VALUES (
            $form_id, '2024-01-01', 'Direct Test Item', 'PROP-001', 1, 100.00, 100.00,
            0.00, 0.00, 100.00, 'Direct test remarks',
            0.00, 0.00, 0.00, '', 0.00,
            0.00, 0.00, '', 0.00, 'Head Office', 'CTRL-001', '2024-01-01',
            1, NOW(), NOW()
        )";
        
        echo "<p>Item SQL: $item_sql</p>";
        
        $item_result = $conn->query($item_sql);
        if ($item_result) {
            $item_id = $conn->insert_id;
            echo "<p style='color: green;'>✓ Item insert successful! ID: $item_id</p>";
            
            // Commit transaction
            $conn->commit();
            echo "<p style='color: blue;'>✓ Transaction committed successfully!</p>";
        } else {
            echo "<p style='color: red;'>✗ Item insert failed: " . $conn->error . "</p>";
            $conn->rollback();
        }
    } else {
        echo "<p style='color: red;'>✗ Form insert failed: " . $conn->error . "</p>";
        $conn->rollback();
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
    $conn->rollback();
}

$conn->close();
?>
