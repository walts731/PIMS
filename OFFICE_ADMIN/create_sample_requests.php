<?php
require_once '../config.php';

echo "<h2>Create Sample Requests for OVM Office</h2>";

$office_id = 5; // OVM office
$user_id = 17; // Joshua's user ID

// Get some assets to create requests for
$asset_query = "SELECT ai.id, ai.description, ai.property_number 
               FROM asset_items ai 
               WHERE ai.office_id = ? OR ai.office_id IS NULL
               LIMIT 5";
$asset_stmt = $conn->prepare($asset_query);
$asset_stmt->bind_param("i", $office_id);
$asset_stmt->execute();
$asset_result = $asset_stmt->get_result();

$assets = [];
while ($row = $asset_result->fetch_assoc()) {
    $assets[] = $row;
}

if (empty($assets)) {
    echo "<div class='alert alert-warning'>No assets found to create requests for. Creating generic requests...</div>";
    
    // Create generic requests without specific assets
    $sample_requests = [
        [
            'asset_id' => null,
            'description' => 'Office Laptop',
            'purpose' => 'Remote work setup',
            'start_date' => date('Y-m-d', strtotime('+1 day')),
            'end_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'pending'
        ],
        [
            'asset_id' => null,
            'description' => 'Projector for presentation',
            'purpose' => 'Client meeting presentation',
            'start_date' => date('Y-m-d', strtotime('+3 days')),
            'end_date' => date('Y-m-d', strtotime('+4 days')),
            'status' => 'approved'
        ],
        [
            'asset_id' => null,
            'description' => 'Conference room equipment',
            'purpose' => 'Team training session',
            'start_date' => date('Y-m-d', strtotime('+2 days')),
            'end_date' => date('Y-m-d', strtotime('+2 days')),
            'status' => 'borrowed'
        ]
    ];
} else {
    echo "<div class='alert alert-info'>Found " . count($assets) . " assets for creating requests.</div>";
    
    $sample_requests = [];
    foreach ($assets as $index => $asset) {
        $sample_requests[] = [
            'asset_id' => $asset['id'],
            'description' => $asset['description'],
            'purpose' => 'Business need for ' . $asset['description'],
            'start_date' => date('Y-m-d', strtotime('+'.($index+1).' days')),
            'end_date' => date('Y-m-d', strtotime('+'.($index+3).' days')),
            'status' => ['pending', 'approved', 'borrowed'][$index % 3]
        ];
    }
}

// Insert sample requests
$insert_query = "INSERT INTO borrow_requests 
                (requested_by, requested_by_office, requested_to_office, asset_id, 
                 quantity_requested, purpose, start_date, end_date, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$insert_stmt = $conn->prepare($insert_query);

$created_requests = 0;
foreach ($sample_requests as $request) {
    // Insert request
    $insert_stmt->bind_param("iiiiissss", 
        $user_id,           // requested_by
        $office_id,         // requested_by_office (OVM)
        1,                  // requested_to_office (OMM - main office)
        $request['asset_id'], 
        1,                  // quantity_requested
        $request['purpose'],
        $request['start_date'],
        $request['end_date'],
        $request['status']
    );
    
    if ($insert_stmt->execute()) {
        $created_requests++;
        echo "<div class='alert alert-success'>✓ Created request: {$request['description']} (Status: {$request['status']})</div>";
    } else {
        echo "<div class='alert alert-danger'>✗ Error creating request: " . $insert_stmt->error . "</div>";
    }
}

$insert_stmt->close();

echo "<hr>";

if ($created_requests > 0) {
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Successfully created $created_requests sample requests!</h4>";
    echo "<p>You can now test the viewDetails functionality.</p>";
    echo "<p><a href='requests.php'>Go to Requests Page</a></p>";
    echo "<p><a href='check_office_requests.php'>Check Office Requests</a></p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ No requests were created</h4>";
    echo "<p>There might be an issue with the database or permissions.</p>";
    echo "</div>";
}
?>

<style>
body { padding: 20px; font-family: Arial, sans-serif; }
.alert { margin: 10px 0; padding: 15px; border-radius: 5px; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
h4 { margin-top: 0; }
</style>
