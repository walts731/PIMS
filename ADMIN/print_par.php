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
$stmt = $conn->prepare("SELECT p.*, o.office_name FROM par_forms p LEFT JOIN offices o ON p.office_location = o.id WHERE p.id = ?");
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
            margin-bottom: 20px;
        }
        
        .form-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
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
        
        .par-no-section {
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
        
        .quantity-col { width: 70px; }
        .unit-col { width: 60px; }
        .property-number-col { width: 120px; }
        .date-col { width: 100px; }
        .amount-col { width: 110px; }
        
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
            .print-container { padding: 0.25in !important; }
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
            <div class="form-title"><?php echo htmlspecialchars($par_form['office_name'] ?? $par_form['office_location']); ?></div>
            <div style="text-align: center; font-size: 10px; color: #666; margin-top: 5px;">Office/Location</div>
        </div>
        
        <!-- Entity Information -->
        <div class="entity-section">
            <div class="entity-row">
                <div class="entity-label">Entity Name:</div>
                <div class="entity-value"><?php echo htmlspecialchars($par_form['entity_name']); ?></div>
            </div>
            <div class="entity-row">
                <div class="entity-label">Fund Cluster:</div>
                <div class="entity-value"><?php echo htmlspecialchars($par_form['fund_cluster']); ?></div>
                <div class="par-no-section">
                    <div class="entity-label" style="width: 60px;">PAR No:</div>
                    <div class="entity-value" style="font-weight: bold;"><?php echo htmlspecialchars($par_form['par_no']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="quantity-col">Quantity</th>
                    <th class="unit-col">Unit</th>
                    <th class="description-col text-left">Description</th>
                    <th class="property-number-col">Property No.</th>
                    <th class="date-col">Date Acquired</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($par_items as $item): ?>
                    <tr>
                        <td><?php echo number_format($item['quantity'], 0); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td class="text-left"><?php echo htmlspecialchars($item['description']); ?></td>
                        <td><?php echo htmlspecialchars($item['property_number'] ?? ''); ?></td>
                        <td><?php echo $item['date_acquired'] ? date('M d, Y', strtotime($item['date_acquired'])) : ''; ?></td>
                        <td class="text-right"><?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php 
                // Add empty rows to maintain form height
                $total_items = count($par_items);
                if ($total_items < 15) {
                    for ($i = 0; $i < (15 - $total_items); $i++) {
                        if ($i === 0) {
                            echo '<tr><td colspan="6" style="height: 20px; font-style: italic; border-bottom: none;">*** Nothing follows ***</td></tr>';
                        } else {
                            echo '<tr><td colspan="6" style="height: 20px; border-top: none; border-bottom: none;">&nbsp;</td></tr>';
                        }
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5">TOTAL:</td>
                    <td class="text-right"><?php echo number_format(array_sum(array_column($par_items, 'amount')), 2); ?></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Footer / Signatures Section -->
        <div class="footer-section">
            <table class="footer-table">
                <tr>
                    <td>
                        <div class="label-row">Received by:</div>
                        <div class="signature-group">
                            <div class="name-line"><?php echo htmlspecialchars($par_form['received_by_name']); ?></div>
                            <div class="sub-label">Signature Over Printed Name</div>
                            <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($par_form['received_by_position']); ?></div>
                            <div class="sub-label">Position / Office</div>
                            <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo (!empty($par_form['received_by_date']) && $par_form['received_by_date'] !== '0000-00-00') ? date('F d, Y', strtotime($par_form['received_by_date'])) : ''; ?></div>
                            <div class="sub-label">Date</div>
                        </div>
                    </td>
                    <td>
                        <div class="label-row">Issued by:</div>
                        <div class="signature-group">
                            <div class="name-line"><?php echo htmlspecialchars($par_form['issued_by_name']); ?></div>
                            <div class="sub-label">Signature Over Printed Name</div>
                            <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($par_form['issued_by_position']); ?></div>
                            <div class="sub-label">Position / Office</div>
                            <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo (!empty($par_form['issued_by_date']) && $par_form['issued_by_date'] !== '0000-00-00') ? date('F d, Y', strtotime($par_form['issued_by_date'])) : ''; ?></div>
                            <div class="sub-label">Date</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="preview-toolbar no-print">
        <div class="d-flex justify-content-between align-items-center bg-dark text-white p-2">
            <div>
                <i class="bi bi-eye me-2"></i> Print Preview - PAR
            </div>
            <div>
                <button class="btn btn-primary btn-sm me-2" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Form
                </button>
                <button class="btn btn-outline-light btn-sm" onclick="window.close()">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
        </div>
    </div>

    <style>
        @media screen {
            body {
                background: #525659;
                padding: 40px 0;
            }
            .print-container {
                background: white;
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
                margin: 0 auto;
                padding: 0.5in;
                width: 8.5in;
                min-height: 11in;
            }
            .preview-toolbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            }
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .print-container { 
                box-shadow: none; 
                margin: 0; 
                padding: 0; 
                width: 100%;
            }
            @page { margin: 0.5in; }
        }
    </style>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Bootstrap CSS for Toolbar -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</body>
</html>
