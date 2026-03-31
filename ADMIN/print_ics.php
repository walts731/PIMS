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
            line-height: 1.2;
            color: #000;
            background: white;
        }
        
        .print-container {
            width: 100%;
            padding: 0;
            position: relative;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .form-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        
        .entity-section {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }
        
        .entity-row {
            display: flex;
            margin-bottom: 5px;
            align-items: flex-end;
        }
        
        .entity-label {
            width: 100px;
            font-weight: bold;
            font-size: 11px;
        }
        
        .entity-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 18px;
            font-size: 11px;
            padding: 0 5px;
        }
        
        .ics-no-section {
            width: 250px;
            margin-left: 20px;
            display: flex;
            align-items: flex-end;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            border: 2px solid #000;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            vertical-align: top;
        }
        
        .items-table th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            background: #fff;
        }
        
        .items-table .text-left { text-align: left; }
        .items-table .text-right { text-align: right; }
        
        .quantity-col { width: 80px; }
        .unit-col { width: 70px; }
        .amount-col { width: 110px; }
        .item-no-col { width: 100px; }
        .useful-life-col { width: 110px; }
        
        .total-row td {
            font-weight: bold;
            text-align: right;
            padding-right: 10px;
        }
        
        .footer-section {
            margin-top: 30px;
            border: 1px solid #000;
        }
        
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .footer-table td {
            border: 1px solid #000;
            padding: 10px;
            width: 50%;
            vertical-align: top;
        }
        
        .label-row {
            font-weight: bold;
            margin-bottom: 30px;
        }
        
        .name-line {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            margin-bottom: 2px;
            font-size: 11px;
        }
        
        .sub-label {
            text-align: center;
            font-size: 10px;
            margin-bottom: 15px;
        }
        
        .signature-group {
            margin-top: 20px;
        }
        
        @media print {
            body { margin: 0; }
            .print-container { padding: 0.25in; }
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .print-container {
                padding: 0;
            }
            
            /* Hide browser print headers and footers */
            @page {
                size: A4;
                margin: 0.5in;
            }
            
            /* Ensure no extra headers appear */
            html {
                overflow: hidden;
            }
            
            /* Hide any potential header elements */
            header, nav, .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header Section -->
        <div class="header-section">
            <?php 
            if (!empty($header_image)) {
                echo '<div style="margin-bottom: 20px;">';
                echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 150px; object-fit: contain;">';
                echo '</div>';
            }
            ?>
            <div class="form-title">INVENTORY CUSTODIAN SLIP</div>
        </div>
        
        <!-- Entity Information -->
        <div class="entity-section">
            <div class="entity-row">
                <div class="entity-label">Entity Name:</div>
                <div class="entity-value"><?php echo htmlspecialchars($ics_form['entity_name']); ?></div>
            </div>
            <div class="entity-row">
                <div class="entity-label">Fund Cluster:</div>
                <div class="entity-value"><?php echo htmlspecialchars($ics_form['fund_cluster']); ?></div>
                <div class="ics-no-section">
                    <div class="entity-label" style="width: 60px;">ICS No:</div>
                    <div class="entity-value" style="font-weight: bold;"><?php echo htmlspecialchars($ics_form['ics_no']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="quantity-col">Quantity</th>
                    <th class="unit-col">Unit</th>
                    <th colspan="2">Amount</th>
                    <th class="description-col text-left">Description</th>
                    <th class="item-no-col">Inventory<br>Item No.</th>
                    <th class="useful-life-col">Estimated<br>Useful Life</th>
                </tr>
                <tr style="height: 15px;">
                    <th></th>
                    <th></th>
                    <th style="font-size: 8px;">Unit Cost</th>
                    <th style="font-size: 8px;">Total Cost</th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ics_items as $item): ?>
                    <tr>
                        <td><?php echo number_format($item['quantity'], 0); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td class="text-right"><?php echo number_format($item['unit_cost'], 2); ?></td>
                        <td class="text-right fw-bold"><?php echo number_format($item['total_cost'], 2); ?></td>
                        <td class="text-left"><?php echo htmlspecialchars($item['description']); ?></td>
                        <td><?php echo htmlspecialchars($item['item_no']); ?></td>
                        <td><?php echo htmlspecialchars($item['useful_life']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php 
                // Add empty rows to maintain form height if needed
                $total_items = count($ics_items);
                if ($total_items < 15) {
                    for ($i = 0; $i < (15 - $total_items); $i++) {
                        if ($i === 0) {
                            echo '<tr><td colspan="7" style="height: 20px; font-style: italic; border-bottom: none;">*** Nothing follows ***</td></tr>';
                        } else {
                            echo '<tr><td colspan="7" style="height: 20px; border-top: none; border-bottom: none;">&nbsp;</td></tr>';
                        }
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="border-right: none;">TOTAL:</td>
                    <td class="text-right" style="border-left: none;"><?php echo number_format(array_sum(array_column($ics_items, 'total_cost')), 2); ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Footer / Signatures Section -->
        <div class="footer-section">
            <table class="footer-table">
                <tr>
                    <td>
                        <div class="label-row">Received from:</div>
                        <div class="signature-group">
                            <div class="name-line"><?php echo htmlspecialchars($ics_form['received_from']); ?></div>
                            <div class="sub-label">Signature Over Printed Name</div>
                            <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($ics_form['received_from_position']); ?></div>
                            <div class="sub-label">Position / Office</div>
                            <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo (!empty($ics_form['received_from_date']) && $ics_form['received_from_date'] !== '0000-00-00') ? date('F d, Y', strtotime($ics_form['received_from_date'])) : ''; ?></div>
                            <div class="sub-label">Date</div>
                        </div>
                    </td>
                    <td>
                        <div class="label-row">Received by:</div>
                        <div class="signature-group">
                            <div class="name-line"><?php echo htmlspecialchars($ics_form['received_by']); ?></div>
                            <div class="sub-label">Signature Over Printed Name</div>
                            <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($ics_form['received_by_position']); ?></div>
                            <div class="sub-label">Position / Office</div>
                            <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo (!empty($ics_form['received_by_date']) && $ics_form['received_by_date'] !== '0000-00-00') ? date('F d, Y', strtotime($ics_form['received_by_date'])) : ''; ?></div>
                            <div class="sub-label">Date</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Close window after printing
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
