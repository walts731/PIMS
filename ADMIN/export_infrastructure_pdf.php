<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get infrastructure data
$infrastructure_data = [];
$total_value = 0;
$total_count = 0;

$sql = "SELECT * FROM infrastructure ORDER BY date_constructed DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $infrastructure_data[] = $row;
    $total_value += $row['acquisition_cost'];
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
    <title>Infrastructure Report - <?php echo $system_name; ?></title>
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
            <h2>Infrastructure Management Report</h2>
            <p>Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>
    </div>
    
    <div class="summary">
        <div class="summary-item">
            <div class="summary-number"><?php echo $total_count; ?></div>
            <div class="summary-label">Total Infrastructure</div>
        </div>
        <div class="summary-item">
            <div class="summary-number">₱<?php echo number_format($total_value, 2); ?></div>
            <div class="summary-label">Total Acquisition Cost</div>
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
                <th>Classification/Type</th>
                <th>Item Description</th>
                <th>Nature Occupancy</th>
                <th>Location</th>
                <th>Date Constructed</th>
                <th>Property No.</th>
                <th>Acquisition Cost</th>
                <th>Market Value</th>
                <th>Date of Appraisal</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($infrastructure_data)): ?>
                <tr>
                    <td colspan="10" class="text-center">No infrastructure items found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($infrastructure_data as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['classification']); ?></td>
                        <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                        <td><?php echo htmlspecialchars($item['nature_occupancy'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                        <td><?php echo date('m/d/Y', strtotime($item['date_constructed'])); ?></td>
                        <td><?php echo htmlspecialchars($item['property_no'] ?? ''); ?></td>
                        <td class="text-right">₱<?php echo number_format($item['acquisition_cost'], 2); ?></td>
                        <td class="text-right">₱<?php echo number_format($item['market_value'], 2); ?></td>
                        <td><?php echo $item['date_appraisal'] ? date('m/d/Y', strtotime($item['date_appraisal'])) : ''; ?></td>
                        <td><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">TOTAL:</th>
                <th class="text-right">₱<?php echo number_format($total_value, 2); ?></th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p>This report was generated by <?php echo htmlspecialchars(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '')); ?> on <?php echo date('F j, Y h:i A'); ?></p>
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
