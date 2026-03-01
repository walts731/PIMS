<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get software data
$software_data = [];
$total_value = 0;
$total_count = 0;

$sql = "SELECT * FROM software ORDER BY software_name ASC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $software_data[] = $row;
    $total_value += $row['purchase_cost'];
    $total_count++;
}

// Get system settings for logo
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result_settings = $stmt->get_result();
    while ($row = $result_settings->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

$logo_path = !empty($system_settings['system_logo']) ? '../' . htmlspecialchars($system_settings['system_logo']) : '../img/trans_logo.png';
$system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Report - <?php echo $system_name; ?></title>
    <style>
        @page {
            size: landscape;
            margin: 0.5in;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin-right: 20px;
            object-fit: contain;
        }
        
        .header-info {
            flex: 1;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #888;
        }
        
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-number {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .status-active { background: #d4edda; color: #155724; padding: 2px 6px; border-radius: 3px; }
        .status-inactive { background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; }
        .status-expired { background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 3px; }
        .status-pending { background: #cce5ff; color: #004085; padding: 2px 6px; border-radius: 3px; }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" class="logo">
        <div class="header-info">
            <h1><?php echo $system_name; ?></h1>
            <h2>Software Management Report</h2>
            <p>Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>
    </div>
    
    <div class="summary">
        <div class="summary-item">
            <div class="summary-number"><?php echo $total_count; ?></div>
            <div class="summary-label">Total Software</div>
        </div>
        <div class="summary-item">
            <div class="summary-number">₱<?php echo number_format($total_value, 2); ?></div>
            <div class="summary-label">Total Purchase Cost</div>
        </div>
        <div class="summary-item">
            <div class="summary-number">₱<?php echo number_format($total_value / $total_count, 2); ?></div>
            <div class="summary-label">Average Cost</div>
        </div>
        <div class="summary-item">
            <div class="summary-number"><?php echo date('F j, Y'); ?></div>
            <div class="summary-label">Report Date</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Software Name</th>
                <th>Category</th>
                <th>Vendor</th>
                <th>Version</th>
                <th>License Type</th>
                <th>Purchase Cost</th>
                <th>Purchase Date</th>
                <th>Renewal Date</th>
                <th>Renewal Cost</th>
                <th>Status</th>
                <th>Assigned To</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($software_data)): ?>
                <tr>
                    <td colspan="11" class="text-center">No software items found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($software_data as $software): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($software['software_name']); ?></strong>
                            <?php if (!empty($software['license_key'])): ?>
                                <br><small><?php echo htmlspecialchars(substr($software['license_key'], 0, 15)) . '...'; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($software['category']); ?></td>
                        <td><?php echo htmlspecialchars($software['vendor']); ?></td>
                        <td><?php echo htmlspecialchars($software['version'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($software['license_type']); ?></td>
                        <td class="text-right">₱<?php echo number_format($software['purchase_cost'], 2); ?></td>
                        <td><?php echo date('m/d/Y', strtotime($software['purchase_date'])); ?></td>
                        <td><?php echo $software['renewal_date'] ? date('m/d/Y', strtotime($software['renewal_date'])) : ''; ?></td>
                        <td class="text-right">₱<?php echo number_format($software['renewal_cost'], 2); ?></td>
                        <td class="text-center">
                            <span class="status-<?php echo $software['status']; ?>">
                                <?php echo ucfirst($software['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($software['assigned_to'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">TOTAL:</th>
                <th class="text-right">₱<?php echo number_format($total_value, 2); ?></th>
                <th colspan="5"></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p>This report was generated by <?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?> on <?php echo date('F j, Y h:i A'); ?></p>
        <p><?php echo $system_name; ?> - Property Inventory and Management System</p>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
