<?php
// Simulate the exact IIRUP form submission process
session_start();
require_once 'config.php';

echo "=== IIRUP Form Submission Test ===\n\n";

// Simulate POST data that would come from IIRUP form
$_POST = [
    'as_of_year' => '2026',
    'accountable_officer' => 'Test Officer',
    'designation' => 'Test Designation',
    'department_office' => 'Test Office',
    'accountable_officer_name' => 'Test Name',
    'accountable_officer_designation' => 'Test Designation',
    'authorized_official_name' => 'Test Auth',
    'authorized_official_designation' => 'Test Auth Designation',
    'inspection_officer_name' => 'Test Inspector',
    'witness_name' => 'Test Witness',
    'particulars' => ['Monitor - COMPUTER DESKTOP i7'], // This is the key part
    'date_acquired' => ['2026-01-01'],
    'property_no' => ['2026-07-05-030-0307-01'],
    'qty' => ['1'],
    'unit_cost' => ['10000'],
    'total_cost' => ['10000'],
    'accumulated_depreciation' => ['0'],
    'impairment_losses' => ['0'],
    'carrying_amount' => ['10000'],
    'inventory_remarks' => ['Test remarks'],
    'disposal_sale' => ['0'],
    'disposal_transfer' => ['0'],
    'disposal_destruction' => ['0'],
    'disposal_others' => ['0'],
    'disposal_total' => ['0'],
    'appraised_value' => ['0'],
    'total' => ['10000'],
    'or_no' => [''],
    'amount' => ['0'],
    'dept_office' => ['Test Office'],
    'control_no' => [''],
    'date_received' => ['2026-01-01']
];

// Simulate session
$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'admin';

echo "Simulating IIRUP form submission with monitor component...\n\n";

// Get the posted data
$particulars = $_POST['particulars'];
$property_nos = $_POST['property_no'];

echo "Processing items:\n";
foreach ($particulars as $index => $particular) {
    echo "Item $index: '$particular'\n";
    echo "Property No: '{$property_nos[$index]}'\n";
    
    // Component detection logic
    $component_type = null;
    if (strpos($particular, 'Monitor - ') === 0) {
        $component_type = 'monitor';
        echo "✓ Detected as monitor component\n";
    } elseif (strpos($particular, 'UPS - ') === 0) {
        $component_type = 'ups';
        echo "✓ Detected as UPS component\n";
    } else {
        echo "✗ No component type detected\n";
    }
    
    // Asset ID lookup
    $asset_id = null;
    $property_no = $property_nos[$index] ?? '';
    
    if (!empty($property_no)) {
        $stmt = $conn->prepare("SELECT id FROM asset_items WHERE property_no = ? LIMIT 1");
        $stmt->bind_param("s", $property_no);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $asset_id = $row['id'];
            echo "✓ Found Asset ID: $asset_id\n";
        } else {
            echo "✗ No asset found for property number: $property_no\n";
        }
    }
    
    if ($asset_id) {
        if ($component_type) {
            echo "✓ Would add to component updates (NOT main asset updates)\n";
            
            // Test the actual component update
            if ($component_type === 'monitor') {
                echo "\n--- Testing Monitor Status Update ---\n";
                
                // Check current status
                $check_stmt = $conn->prepare("SELECT monitor_status FROM asset_desktop_computers WHERE asset_item_id = ?");
                $check_stmt->bind_param("i", $asset_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result && $check_result->num_rows > 0) {
                    $current_status = $check_result->fetch_assoc()['monitor_status'];
                    echo "Current monitor status: $current_status\n";
                    
                    // Perform update
                    $update_sql = "UPDATE asset_desktop_computers SET monitor_status = 'unserviceable' WHERE asset_item_id = $asset_id";
                    echo "Executing: $update_sql\n";
                    
                    $result = $conn->query($update_sql);
                    if ($result) {
                        $affected_rows = $conn->affected_rows;
                        echo "✓ Update executed. Affected rows: $affected_rows\n";
                        
                        if ($affected_rows > 0) {
                            echo "✓ Monitor status updated successfully\n";
                            
                            // Verify update
                            $verify_stmt = $conn->prepare("SELECT monitor_status FROM asset_desktop_computers WHERE asset_item_id = ?");
                            $verify_stmt->bind_param("i", $asset_id);
                            $verify_stmt->execute();
                            $verify_result = $verify_stmt->get_result();
                            $new_status = $verify_result->fetch_assoc()['monitor_status'];
                            echo "New monitor status: $new_status\n";
                        } else {
                            echo "⚠ No rows affected\n";
                        }
                    } else {
                        echo "✗ Update failed: " . $conn->error . "\n";
                    }
                } else {
                    echo "✗ No desktop computer record found\n";
                }
            }
        } else {
            echo "✓ Would add to main asset updates\n";
        }
    }
    
    echo "\n";
}

echo "=== Test Complete ===\n";
?>
