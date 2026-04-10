<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get ICS ID from URL
$ics_id = $_GET['id'] ?? 0;
if (empty($ics_id)) {
    header('Location: ics_entries.php');
    exit();
}

// Get ICS form details
$ics_form = null;
$stmt = $conn->prepare("SELECT * FROM ics_forms WHERE id = ?");
$stmt->bind_param("i", $ics_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $ics_form = $result->fetch_assoc();
}
$stmt->close();

if (!$ics_form) {
    header('Location: ics_entries.php');
    exit();
}

// Get ICS items
$ics_items = [];
$stmt = $conn->prepare("SELECT * FROM ics_items WHERE form_id = ? ORDER BY item_no");
$stmt->bind_param("i", $ics_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ics_items[] = $row;
}
$stmt->close();

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'ICS'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

logSystemAction($_SESSION['user_id'], 'Printed ICS Form', 'forms', "ICS ID: $ics_id, ICS No: {$ics_form['ics_no']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVENTORY CUSTODIAN SLIP</title>
    <style>
        @page {
            size: A4;
            margin: 0.5in;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: white;
        }
        
        .print-container {
            width: 100%;
            border: 2px solid #000;
            position: relative;
        }
        
        .ics-header {
            padding: 15px 20px 5px;
            display: flex;
            align-items: center;
            position: relative;
        }

        .ics-header .logo-container {
            margin-right: 20px;
        }

        .ics-header .logo-container img {
            max-height: 60px;
        }

        .ics-header .header-text {
            text-align: center;
            flex-grow: 1;
            margin-right: 80px;
            line-height: 1.3;
        }

        .ics-header .header-text p {
            margin: 0;
            font-size: 12px;
        }

        .ics-header .annex {
            position: absolute;
            right: 15px;
            top: 15px;
            font-style: italic;
            font-size: 11px;
        }

        .ics-title-section {
            text-align: center;
            padding: 5px 0;
        }

        .ics-title-section h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .meta-section {
            padding: 5px 15px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .meta-item {
            display: flex;
            align-items: center;
        }

        .meta-label {
            min-width: 80px;
        }

        .meta-value {
            border-bottom: 1px solid #000;
            min-width: 150px;
            padding: 0 5px;
        }

        .excel-grid-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .excel-grid-table th, .excel-grid-table td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 10px;
            height: 20px;
        }

        .excel-grid-table th {
            text-align: center;
            font-weight: bold;
        }

        .amount-header-row th {
            border-bottom: none;
        }

        .amount-sub-row th {
            font-size: 9px;
        }

        .signature-section {
            display: flex;
        }

        .sig-box {
            flex: 1;
            border: 1px solid #000;
            padding: 8px 12px 15px;
            display: flex;
            flex-direction: column;
            min-height: 140px;
        }

        .sig-box:first-child {
            border-left: none;
            border-bottom: none;
        }

        .sig-box:last-child {
            border-right: none;
            border-bottom: none;
        }

        .sig-label {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .sig-content {
            text-align: center;
            margin-top: auto;
        }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 1px;
            text-decoration: underline;
        }

        .sig-sub {
            font-size: 10px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .sig-line {
            width: 75%;
            margin: 0 auto 3px;
            border-bottom: 1px solid #000;
        }

        .sig-date-label {
            font-size: 9px;
            margin-top: 1px;
        }
        
        .preview-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media screen {
            body {
                background: #525659;
                padding: 60px 0;
            }
            .print-container {
                background: white;
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
                margin: 0 auto;
                width: 8.5in;
                min-height: 11in;
            }
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .print-container { 
                box-shadow: none; 
                margin: 0 auto; 
                border: 2px solid #000;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="preview-toolbar no-print">
        <div><i class="bi bi-printer-fill me-2"></i>Official ICS Print Preview</div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print Form</button>
            <button onclick="window.close()" class="btn btn-light btn-sm ms-2">Close</button>
        </div>
    </div>

    <div class="print-container">
        <!-- Header Section -->
        <div class="ics-header">
            <div class="logo-container" style="width: 100%; text-align: center; margin-right: 0;">
                <?php if ($header_image): ?>
                    <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Header Image" style="max-height: 120px; width: auto; max-width: 100%;">
                <?php else: ?>
                    <img src="../img/system_logo.png" alt="Logo" style="max-height: 80px;">
                <?php endif; ?>
            </div>
            <div class="annex">Annex A.3</div>
        </div>

        <div class="ics-title-section">
            <h2>INVENTORY CUSTODIAN SLIP</h2>
        </div>

        <!-- Metadata Section -->
        <div class="meta-section">
            <div class="meta-group">
                <div class="meta-item">
                    <span class="meta-label">Entity Name:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($ics_form['entity_name']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Fund Cluster:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($ics_form['fund_cluster']); ?></span>
                </div>
            </div>
            <div class="meta-group">
                <div class="meta-item">
                    <span class="meta-label">ICS No :</span>
                    <span class="meta-value"><?php echo htmlspecialchars($ics_form['ics_no']); ?></span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="excel-grid-table">
            <thead>
                <tr class="amount-header-row">
                    <th rowspan="2" width="50">Quantity</th>
                    <th rowspan="2" width="50">Unit</th>
                    <th colspan="2">Amount</th>
                    <th rowspan="2">Description</th>
                    <th rowspan="2" width="70">Item No.</th>
                    <th rowspan="2" width="90">Estimated Useful Life</th>
                </tr>
                <tr class="amount-sub-row">
                    <th width="70">Unit Cost</th>
                    <th width="80">Total Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_amount = 0;
                foreach ($ics_items as $item): 
                    $total_amount += $item['total_cost'];
                ?>
                    <tr>
                        <td align="center"><?php echo number_format($item['quantity'], 0); ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td align="right"><?php echo number_format($item['unit_cost'], 2); ?></td>
                        <td align="right"><?php echo number_format($item['total_cost'], 2); ?></td>
                        <td style="padding-left: 8px;"><?php echo htmlspecialchars($item['description']); ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['item_no']); ?></td>
                        <td align="center"><strong><?php echo htmlspecialchars($item['useful_life']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php 
                $min_rows = 24;
                $remaining = $min_rows - count($ics_items);
                for($i=0; $i<$remaining; $i++): 
                ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <?php if ($remaining > 0 && $i === ($remaining - 1)): ?>
                            <td align="right" style="color: red; font-weight: bold;"><?php echo number_format($total_amount, 2); ?></td>
                        <?php else: ?>
                            <td>&nbsp;</td>
                        <?php endif; ?>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="sig-box">
                <div class="sig-label">Received from:</div>
                <div class="sig-content">
                    <div class="sig-name"><?php echo htmlspecialchars($ics_form['received_from']); ?></div>
                    <div class="sig-sub"><?php echo htmlspecialchars($ics_form['received_from_position']); ?></div>
                    <div class="sig-line"></div>
                    <div class="sig-date-label">Date</div>
                </div>
            </div>
            <div class="sig-box">
                <div class="sig-label">Received by:</div>
                <div class="sig-content">
                    <div class="sig-name"><?php echo htmlspecialchars($ics_form['received_by']); ?></div>
                    <div class="sig-sub"><?php echo htmlspecialchars($ics_form['received_by_position']); ?></div>
                    <div class="sig-line"></div>
                    <div class="sig-date-label">Date</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Bootstrap CSS for Toolbar -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</body>
</html>
