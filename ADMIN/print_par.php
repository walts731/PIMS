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
            color: #000;
            background: white;
        }
        
        .print-container {
            width: 100%;
            border: 2px solid #000;
            position: relative;
        }
        
        .excel-header {
            padding: 20px 20px 10px;
            text-align: center;
            position: relative;
        }

        .excel-header .logo-container {
            margin-bottom: 15px;
        }

        .excel-header .logo-container img {
            max-height: 70px;
        }

        .excel-header h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 3px;
            text-transform: uppercase;
        }

        .excel-header .sub-header {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .excel-header .office-name {
            font-size: 12px;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 3px;
        }

        .excel-header .office-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .meta-section {
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
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
            min-width: 120px;
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
            padding: 4px 6px;
            font-size: 10px;
            height: 22px;
        }

        .excel-grid-table th {
            text-align: center;
            font-weight: bold;
            text-transform: capitalize;
        }

        .total-row {
            font-weight: bold;
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
            min-height: 150px;
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
            font-style: italic;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 25px;
        }

        .sig-content {
            text-align: center;
            margin-top: auto;
        }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .sig-line {
            width: 85%;
            margin: 0 auto 3px;
            border-bottom: 1px solid #000;
        }

        .sig-sub {
            font-size: 9px;
            margin-bottom: 10px;
        }

        .sig-date-line {
            width: 65%;
            margin: 10px auto 0;
            border-bottom: 1px solid #000;
            text-align: center;
            font-size: 10px;
            min-height: 15px;
        }

        .sig-date-label {
            font-size: 9px;
            margin-top: 2px;
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
        <div><i class="bi bi-printer-fill me-2"></i>Official PAR Print Preview</div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print Form</button>
            <button onclick="window.close()" class="btn btn-light btn-sm ms-2">Close</button>
        </div>
    </div>

    <div class="print-container">
        <!-- Header Section -->
        <div class="excel-header">
            <div class="logo-container">
                <?php if ($header_image): ?>
                    <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Logo">
                <?php else: ?>
                    <img src="../img/system_logo.png" alt="Logo">
                <?php endif; ?>
            </div>
            <div class="office-name"><?php echo htmlspecialchars($par_form['office_name'] ?: 'OMM'); ?></div>
            <div class="office-label">OFFICE/LOCATION</div>
        </div>

        <!-- Metadata Section -->
        <div class="meta-section">
            <div class="meta-group">
                <div class="meta-item">
                    <span class="meta-label">Entity Name:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($par_form['entity_name']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Fund Cluster:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($par_form['fund_cluster']); ?></span>
                </div>
            </div>
            <div class="meta-group">
                <div class="meta-item">
                    <span class="meta-label">PAR No.:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($par_form['par_no']); ?></span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="excel-grid-table">
            <thead>
                <tr>
                    <th width="40">Qty.</th>
                    <th width="50">Unit</th>
                    <th>Description</th>
                    <th width="140">Property Number</th>
                    <th width="100">Date Acquired</th>
                    <th width="100">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($par_items as $item): ?>
                    <tr>
                        <td align="center"><?php echo number_format($item['quantity'], 0); ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td style="padding-left: 8px;"><?php echo htmlspecialchars($item['description']); ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['property_number']); ?></td>
                        <td align="center"><?php echo $item['date_acquired'] ? date('m/d/Y', strtotime($item['date_acquired'])) : ''; ?></td>
                        <td align="right">₱<?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php 
                // Fill remaining rows to maintain consistent height
                $min_rows = 24; 
                $remaining = $min_rows - count($par_items);
                for($i=0; $i<$remaining; $i++): 
                ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
                
                <tr class="total-row">
                    <td colspan="3" style="border: none;">&nbsp;</td>
                    <td align="center">TOTAL</td>
                    <td style="border-right: none;">&nbsp;</td>
                    <td align="right">₱<?php echo number_format(array_sum(array_column($par_items, 'amount')), 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="sig-box">
                <div class="sig-label">Received by:</div>
                <div class="sig-content">
                    <div class="sig-name"><?php echo htmlspecialchars($par_form['received_by_name']); ?></div>
                    <div class="sig-line"></div>
                    <div class="sig-sub">Signature over Printed Name</div>
                    
                    <div class="sig-name" style="margin-top: 10px; text-transform: none; font-weight: bold;"><?php echo htmlspecialchars($par_form['received_by_position']); ?></div>
                    <div class="sig-line"></div>
                    <div class="sig-sub">Position / Office</div>
                    
                    <div class="sig-date-line">
                        <?php echo ($par_form['received_by_date'] && $par_form['received_by_date'] != '0000-00-00') ? date('m/d/Y', strtotime($par_form['received_by_date'])) : ''; ?>
                    </div>
                    <div class="sig-date-label">Date</div>
                </div>
            </div>
            <div class="sig-box">
                <div class="sig-label">Issued by:</div>
                <div class="sig-content">
                    <div class="sig-name"><?php echo htmlspecialchars($par_form['issued_by_name']); ?></div>
                    <div class="sig-line"></div>
                    <div class="sig-sub">Signature over Printed Name</div>
                    
                    <div class="sig-name" style="margin-top: 10px; text-transform: none; font-weight: bold;"><?php echo htmlspecialchars($par_form['issued_by_position']); ?></div>
                    <div class="sig-line"></div>
                    <div class="sig-sub">Position / Office</div>
                    
                    <div class="sig-date-line">
                        <?php echo ($par_form['issued_by_date'] && $par_form['issued_by_date'] != '0000-00-00') ? date('m/d/Y', strtotime($par_form['issued_by_date'])) : ''; ?>
                    </div>
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
