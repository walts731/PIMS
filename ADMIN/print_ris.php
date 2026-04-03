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

// Get RIS ID from URL
$ris_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ris_id <= 0) {
    header('Location: ris_entries.php?error=Invalid RIS ID');
    exit();
}

// Get RIS form details
$stmt = $conn->prepare("
    SELECT * FROM ris_forms WHERE id = ?
");
$stmt->bind_param("i", $ris_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header('Location: ris_entries.php?error=RIS not found');
    exit();
}

$ris = $result->fetch_assoc();
$stmt->close();

// Get RIS items
$ris_items = [];
$stmt = $conn->prepare("
    SELECT * FROM ris_items WHERE ris_form_id = ? ORDER BY stock_no
");
$stmt->bind_param("i", $ris_id);
$stmt->execute();
$items_result = $stmt->get_result();

while ($row = $items_result->fetch_assoc()) {
    $ris_items[] = $row;
}
$stmt->close();

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'RIS'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

logSystemAction($_SESSION['user_id'], 'Printed RIS entry', 'forms', "RIS ID: $ris_id, RIS No: {$ris['ris_no']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REQUISITION AND ISSUE SLIP</title>
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
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.2;
            color: #000;
            background: white;
        }
        
        .print-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            position: relative;
            background: white;
            border: none;
            min-height: 100vh;
        }
        
        .excel-header {
            padding: 15px 0;
            text-align: center;
            background: #fff;
            border: none;
        }
        
        .excel-header h4 {
            color: #000;
            font-weight: bold;
            margin-bottom: 0;
            font-size: 16px;
            font-family: 'DejaVu Sans', sans-serif;
        }
        
        .header-img {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .header-img img {
            max-width: 100%;
            height: auto;
        }
        
        .meta {
            margin: 10px 0;
            font-size: 12px;
        }
        
        .meta table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
        }
        
        .meta td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 11px;
            text-align: left;
        }
        
        .items-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
            height: 500px;
        }
        
        .items-table th, 
        .items-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 11px;
            font-family: 'DejaVu Sans', sans-serif;
            vertical-align: middle;
        }
        
        .items-table th {
            font-weight: bold;
            background: #f2f2f2;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        
        .items-table .text-left {
            text-align: left;
        }
        
        .grand-total {
            font-weight: bold;
            color: red;
            border-top: 1px solid #000;
        }
        
        .footer-table {
            border: 1px solid #000;
            border-collapse: collapse;
            margin-top: 10px;
            width: 100%;
        }
        
        .footer-table th, 
        .footer-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 11px;
            text-align: center;
            font-family: 'DejaVu Sans', sans-serif;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
                font-size: 12px;
            }
            
            .print-container {
                padding: 0;
                border: none;
                max-width: 100%;
                box-shadow: none;
            }
            
            @page {
                size: A4;
                margin: 0.5in;
            }
            
            html {
                overflow: hidden;
            }
            
            header, nav, .no-print {
                display: none !important;
            }
            
            .excel-header {
                padding: 10px 0;
            }
            
            .meta td {
                padding: 2px 3px;
            }
            
            .items-table th,
            .items-table td {
                padding: 3px 4px;
                font-size: 10px;
            }
            
            .footer-table th,
            .footer-table td {
                padding: 3px 4px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Form Header -->
        <div class="excel-header">
            <?php 
            if (!empty($header_image)) {
                echo '<div class="header-img">';
                echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image">';
                echo '</div>';
            }
            ?>
            <h4>REQUISITION AND ISSUE SLIP</h4>
        </div>
        
        <!-- Meta Information -->
        <div class="meta">
            <table>
                <tr>
                    <td><strong>DIVISION:</strong> <?php echo htmlspecialchars($ris['division']); ?></td>
                    <td><strong>Responsibility Center:</strong> <?php echo htmlspecialchars($ris['responsibility_center']); ?></td>
                    <td><strong>RIS NO:</strong> <?php echo htmlspecialchars($ris['ris_no']); ?></td>
                    <td><strong>DATE:</strong> 
                        <?php if (!empty($ris['date']) && $ris['date'] !== '0000-00-00' && $ris['date'] !== null): ?>
                            <?php echo date('F d, Y', strtotime($ris['date'])); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>OFFICE:</strong> <?php echo htmlspecialchars($ris['office']); ?></td>
                    <td><strong>Code:</strong> <?php echo htmlspecialchars($ris['code']); ?></td>
                    <td><strong>SAI NO:</strong> <?php echo htmlspecialchars($ris['sai_no']); ?></td>
                    <td></td>
                </tr>
            </table>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th colspan="4">REQUISITION</th>
                    <th colspan="3">ISSUANCE</th>
                </tr>
                <tr>
                    <th>Stock No</th>
                    <th>Unit</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Signature</th>
                    <th>Price</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ris_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['stock_no']); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td class="text-left"><?php echo htmlspecialchars($item['description']); ?></td>
                        <td><?php echo number_format($item['quantity'], 2); ?></td>
                        <td></td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo number_format($item['total_amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (!empty($ris_items)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; font-style: italic; padding: 6px 0; border-top: 1px solid #000;">— NOTHING FOLLOWS —</td>
                </tr>
                <?php endif; ?>
                
                <?php 
                // Fill blank rows to match generate_ris_pdf.php format
                $minRows = 15;
                $currentRows = count($ris_items);
                $emptyRows = max(0, $minRows - $currentRows);
                
                for ($i = 0; $i < $emptyRows; $i++) {
                    echo '<tr>';
                    echo '<td>&nbsp;</td>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '</tr>';
                }
                ?>
                
                <?php $grandTotal = array_sum(array_column($ris_items, 'total_amount')); ?>
                <tr>
                    <td colspan="6" style="text-align:right; border-top:1px solid #000;"><strong>Grand Total:</strong></td>
                    <td class="grand-total"><?php echo number_format($grandTotal, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="7" style="text-align:left; border-top:1px solid #000;"><strong>Purpose:</strong> <?php echo htmlspecialchars($ris['purpose']); ?></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Footer Signatures -->
        <table class="footer-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Requested By</th>
                    <th>Approved By</th>
                    <th>Issued By</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Signature</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Printed Name</td>
                    <td><?php echo htmlspecialchars($ris['requested_by']); ?></td>
                    <td><?php echo htmlspecialchars($ris['approved_by']); ?></td>
                    <td><?php echo htmlspecialchars($ris['issued_by']); ?></td>
                    <td><?php echo htmlspecialchars($ris['received_by']); ?></td>
                </tr>
                <tr>
                    <td>Designation</td>
                    <td><?php echo htmlspecialchars($ris['requested_by_position']); ?></td>
                    <td><?php echo htmlspecialchars($ris['approved_by_position']); ?></td>
                    <td><?php echo htmlspecialchars($ris['issued_by_position']); ?></td>
                    <td><?php echo htmlspecialchars($ris['received_by_position']); ?></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td><?php if (!empty($ris['requested_date']) && $ris['requested_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris['requested_date'])); ?></td>
                    <td><?php if (!empty($ris['approved_date']) && $ris['approved_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris['approved_date'])); ?></td>
                    <td><?php if (!empty($ris['issued_date']) && $ris['issued_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris['issued_date'])); ?></td>
                    <td><?php if (!empty($ris['received_date']) && $ris['received_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris['received_date'])); ?></td>
                </tr>
            </tbody>
        </table>
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
