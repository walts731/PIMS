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

// Get PAR ID from URL
$par_id = $_GET['id'] ?? 0;
if (empty($par_id)) {
    header('Location: par_entries.php');
    exit();
}

// Get PAR form details
$par_form = null;
$stmt = $conn->prepare("SELECT * FROM par_forms WHERE id = ?");
$stmt->bind_param("i", $par_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $par_form = $result->fetch_assoc();
}
$stmt->close();

if (!$par_form) {
    header('Location: par_entries.php');
    exit();
}

// Get PAR items
$par_items = [];
$stmt = $conn->prepare("SELECT * FROM par_items WHERE form_id = ? ORDER BY id");
$stmt->bind_param("i", $par_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $par_items[] = $row;
}
$stmt->close();

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'PAR'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

logSystemAction($_SESSION['user_id'], 'Printed PAR Form', 'forms', "PAR ID: $par_id, PAR No: {$par_form['par_no']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROPERTY ACKNOWLEDGMENT RECEIPT</title>
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
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
        }
        
        .print-container {
            width: 100%;
            max-width: 8.27in;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            min-height: 100vh;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .entity-section {
            margin-bottom: 25px;
        }
        
        .entity-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .entity-label {
            width: 140px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .entity-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 20px;
            font-size: 12px;
            padding: 2px 5px;
            min-width: 150px;
            max-width: 200px;
        }
        
        .entity-row .entity-label:nth-child(3),
        .entity-row .entity-value:nth-child(4) {
            margin-left: 20px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 15px 6px;
            text-align: center;
            vertical-align: middle;
            font-size: 11px;
        }
        
        .items-table th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
        }
        
        .items-table .text-left {
            text-align: left;
        }
        
        .items-table .quantity-col {
            width: 8%;
        }
        
        .items-table .unit-col {
            width: 8%;
        }
        
        .items-table .description-col {
            width: 35%;
        }
        
        .items-table .property-number-col {
            width: 15%;
        }
        
        .items-table .date-col {
            width: 15%;
        }
        
        .items-table .amount-col {
            width: 21%;
        }
        
        .total-row td {
            font-weight: bold;
            background: #f0f0f0;
        }
        
        .total-row .total-label {
            text-align: right;
            padding-right: 10px;
        }
        
        .nothing-follows {
            text-align: center;
            font-style: italic;
            margin-bottom: 30px;
        }
        
        .signatures-section {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            margin-top: 0;
        }
        
        .signature-row {
            display: flex;
            margin-bottom: 30px;
        }
        
        .signature-column {
            flex: 1;
            padding: 0 20px;
        }
        
        .signature-column:first-child {
            border-right: 1px solid #ccc;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .signature-name {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 12px;
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 2px 5px;
            display: block;
        }
        
        .signature-position {
            font-style: italic;
            margin-bottom: 15px;
            font-size: 11px;
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 2px 5px;
            display: block;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            min-height: 40px;
            margin-bottom: 5px;
        }
        
        .signature-position-line {
            border-bottom: 1px solid #000;
            min-height: 20px;
            margin-bottom: 5px;
        }
        
        .date-line {
            font-size: 11px;
            text-align: left;
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 2px 5px;
            display: block;
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
            <div class="form-title"><?php echo htmlspecialchars($par_form['office_location']); ?></div>
            <div style="text-align: center; font-size: 12px; color: #666; margin-top: 5px;">Office/Location</div>
        </div>
        
        <!-- Entity Information -->
        <div class="entity-section">
            <div class="entity-row">
                <div class="entity-label">Entity Name:</div>
                <div class="entity-value"><?php echo htmlspecialchars($par_form['entity_name']); ?></div>
                <div class="entity-label">PAR No:</div>
                <div class="entity-value"><?php echo htmlspecialchars($par_form['par_no']); ?></div>
            </div>
            <div class="entity-row">
                <div class="entity-label">Fund Cluster:</div>
                <div class="entity-value"><?php echo htmlspecialchars($par_form['fund_cluster']); ?></div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="quantity-col">Quantity</th>
                    <th class="unit-col">Unit</th>
                    <th class="description-col text-left">Description</th>
                    <th class="property-number-col">Property Number</th>
                    <th class="date-col">Date Acquired</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($par_items as $item): ?>
                    <tr>
                        <td><?php echo number_format($item['quantity'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td class="text-left"><?php echo htmlspecialchars($item['description']); ?></td>
                        <td><?php echo htmlspecialchars($item['property_number'] ?? ''); ?></td>
                        <td><?php echo $item['date_acquired'] ? date('M d, Y', strtotime($item['date_acquired'])) : ''; ?></td>
                        <td><?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="total-label">Total:</td>
                    <td><?php echo number_format(array_sum(array_column($par_items, 'amount')), 2); ?></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="nothing-follows">Nothing follows</div>
        
        <!-- Signatures Section -->
        <div class="signatures-section">
            <div class="signature-row">
                <div class="signature-column">
                    <div class="signature-label">Received by:</div>
                    <div class="signature-name"><?php echo htmlspecialchars($par_form['received_by_name']); ?></div>
                    <div class="signature-position"><?php echo htmlspecialchars($par_form['received_by_position']); ?></div>
                    <div class="date-line">
                        <?php if (!empty($par_form['received_by_date']) && $par_form['received_by_date'] !== '0000-00-00'): ?>
                            Date: <?php echo date('F d, Y', strtotime($par_form['received_by_date'])); ?>
                        <?php else: ?>
                            Date: 
                        <?php endif; ?>
                    </div>
                </div>
                <div class="signature-column">
                    <div class="signature-label">Issued by:</div>
                    <div class="signature-name"><?php echo htmlspecialchars($par_form['issued_by_name']); ?></div>
                    <div class="signature-position"><?php echo htmlspecialchars($par_form['issued_by_position']); ?></div>
                    <div class="date-line">
                        <?php if (!empty($par_form['issued_by_date']) && $par_form['issued_by_date'] !== '0000-00-00'): ?>
                            Date: <?php echo date('F d, Y', strtotime($par_form['issued_by_date'])); ?>
                        <?php else: ?>
                            Date:
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
