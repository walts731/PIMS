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

// Get IIRUP ID from URL
$iirup_id = $_GET['id'] ?? 0;
if (empty($iirup_id)) {
    header('Location: iirup_entries.php');
    exit();
}

// Get IIRUP form details
$iirup_form = null;
$stmt = $conn->prepare("SELECT * FROM iirup_forms WHERE id = ?");
$stmt->bind_param("i", $iirup_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $iirup_form = $result->fetch_assoc();
}
$stmt->close();

if (!$iirup_form) {
    header('Location: iirup_entries.php');
    exit();
}

// Get IIRUP items
$iirup_items = [];
$stmt = $conn->prepare("SELECT * FROM iirup_items WHERE form_id = ? ORDER BY item_order");
$stmt->bind_param("i", $iirup_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $iirup_items[] = $row;
}
$stmt->close();

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'IIRUP'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

logSystemAction($_SESSION['user_id'], 'Printed IIRUP Form', 'forms', "IIRUP ID: $iirup_id, Form No: {$iirup_form['form_number']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IIRUP PRINT - <?php echo htmlspecialchars($iirup_form['form_number']); ?> - PIMS</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.3in;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8.5px;
            color: #000;
            background: white;
        }

        .print-container {
            width: 100%;
            border: 2px solid #000;
            position: relative;
        }

        .iirup-header {
            padding: 10px 20px 5px;
            text-align: center;
        }

        .iirup-header .logo-container {
            margin-bottom: 5px;
        }

        .iirup-header img {
            max-height: 70px;
            width: auto;
        }

        .iirup-header h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 3px 0;
            text-transform: uppercase;
        }

        .iirup-header p {
            font-size: 10px;
            font-style: italic;
        }

        .meta-top-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            padding: 5px 20px;
        }

        .meta-item {
            text-align: center;
        }

        .meta-value {
            border-bottom: 1px solid #000;
            min-height: 18px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .meta-label {
            font-style: italic;
            font-size: 9px;
        }

        .excel-grid-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .excel-grid-table th, .excel-grid-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 8px;
            height: 16px;
        }

        .excel-grid-table th {
            text-align: center;
            font-weight: bold;
            background: #f0f0f0;
        }

        .group-header {
            font-size: 9px;
            background: #fff;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
        }

        .sig-box {
            border: 1px solid #000;
            padding: 8px;
            display: flex;
            flex-direction: column;
            min-height: 150px;
        }

        .sig-box:first-child { border-left: none; }
        .sig-box:last-child { border-right: none; }

        .cert-text {
            font-size: 8.5px;
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .sig-content {
            margin-top: auto;
        }

        .sig-dual-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .sig-entry {
            text-align: center;
        }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9.5px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 90%;
            margin-bottom: 1px;
        }

        .sig-sub {
            font-size: 7.5px;
            margin-bottom: 3px;
        }

        .sig-pos {
            font-size: 8.5px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 90%;
        }

        .no-print { display: none !important; }

        @media screen {
            body { background: #525659; padding: 40px 0; }
            .print-container { background: white; margin: 0 auto; width: 11in; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="iirup-header">
            <div class="logo-container">
                <?php if ($header_image): ?>
                    <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Banner">
                <?php else: ?>
                    <img src="../img/system_logo.png" alt="Logo">
                <?php endif; ?>
            </div>
            <h1>INVENTORY AND INSPECTION REPORT OF UNSERVICEABLE PROPERTY</h1>
            <p>As of <?php echo htmlspecialchars($iirup_form['as_of_year']); ?></p>
        </div>

        <div class="meta-top-grid">
            <div class="meta-item">
                <div class="meta-value"><?php echo htmlspecialchars($iirup_form['accountable_officer']); ?></div>
                <div class="meta-label">(Name of Accountable Officer)</div>
            </div>
            <div class="meta-item">
                <div class="meta-value"><?php echo htmlspecialchars($iirup_form['designation']); ?></div>
                <div class="meta-label">(Designation)</div>
            </div>
            <div class="meta-item">
                <div class="meta-value"><?php echo htmlspecialchars($iirup_form['department_office']); ?></div>
                <div class="meta-label">(Department/Office)</div>
            </div>
        </div>

        <table class="excel-grid-table">
            <thead>
                <tr>
                    <th colspan="10" class="group-header">INVENTORY</th>
                    <th colspan="8" class="group-header">INSPECTION and DISPOSAL</th>
                </tr>
                <tr>
                    <th rowspan="2" width="55">Date Acquired</th>
                    <th rowspan="2">Particulars/ Articles</th>
                    <th rowspan="2" width="60">Property No.</th>
                    <th rowspan="2" width="25">Qty</th>
                    <th rowspan="2" width="55">Unit Cost</th>
                    <th rowspan="2" width="60">Total Cost</th>
                    <th rowspan="2" width="60">Accum. Dep.</th>
                    <th rowspan="2" width="60">Accum. Imp.</th>
                    <th rowspan="2" width="60">Carrying Amount</th>
                    <th rowspan="2" width="50">Remarks</th>
                    <th colspan="5" class="sub-group-header">DISPOSAL</th>
                    <th rowspan="2" width="55">Appraised Value</th>
                    <th colspan="2" class="sub-group-header">RECORD OF SALES</th>
                </tr>
                <tr>
                    <th width="35">Sale</th>
                    <th width="35">Transfer</th>
                    <th width="45">Destruction</th>
                    <th width="50">Others</th>
                    <th width="40">Total</th>
                    <th width="45">OR No.</th>
                    <th width="50">Amount</th>
                </tr>
                <tr style="font-size: 7px; font-weight: normal;">
                    <th>(1)</th><th>(2)</th><th>(3)</th><th>(4)</th><th>(5)</th><th>(6)</th><th>(7)</th><th>(8)</th><th>(9)</th><th>(10)</th><th>(11)</th><th>(12)</th><th>(13)</th><th>(14)</th><th>(15)</th><th>(16)</th><th>(17)</th><th>(18)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($iirup_items as $item): ?>
                    <tr>
                        <td align="center"><?php echo !empty($item['date_acquired']) ? date('m/d/Y', strtotime($item['date_acquired'])) : ''; ?></td>
                        <td align="left"><?php echo htmlspecialchars($item['particulars']); ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['property_no']); ?></td>
                        <td align="center"><?php echo number_format($item['quantity'], 0); ?></td>
                        <td align="right"><?php echo number_format($item['unit_cost'], 2); ?></td>
                        <td align="right"><?php echo number_format($item['total_cost'], 2); ?></td>
                        <td align="right"><?php echo number_format($item['accumulated_depreciation'], 2); ?></td>
                        <td align="right"><?php echo number_format($item['impairment_losses'], 2); ?></td>
                        <td align="right"><?php echo number_format($item['carrying_amount'], 2); ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['inventory_remarks'] ?? 'Unserviceable'); ?></td>
                        <td align="right"><?php echo $item['disposal_sale'] > 0 ? number_format($item['disposal_sale'], 2) : ''; ?></td>
                        <td align="right"><?php echo $item['disposal_transfer'] > 0 ? number_format($item['disposal_transfer'], 2) : ''; ?></td>
                        <td align="right"><?php echo $item['disposal_destruction'] > 0 ? number_format($item['disposal_destruction'], 2) : ''; ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['disposal_others']); ?></td>
                        <td align="right"><?php echo $item['disposal_total'] > 0 ? number_format($item['disposal_total'], 2) : ''; ?></td>
                        <td align="right"><?php echo $item['appraised_value'] > 0 ? number_format($item['appraised_value'], 2) : ''; ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['or_no']); ?></td>
                        <td align="right"><?php echo $item['amount'] > 0 ? number_format($item['amount'], 2) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php 
                $remaining = 10 - count($iirup_items);
                for($i=0; $i<$remaining; $i++): 
                ?>
                    <tr>
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="signature-grid">
            <div class="sig-box">
                <div class="cert-text">I HEREBY request inspection and disposition, pursuant to Section 79 of PD 1445, of the property enumerated above.</div>
                <div class="sig-content">
                    <div class="sig-dual-row">
                        <div class="sig-entry">
                            <div style="text-align: left; font-size: 8px; margin-bottom: 20px;">Requested by:</div>
                            <div class="sig-name"><?php echo htmlspecialchars($iirup_form['accountable_officer_name'] ?? $iirup_form['accountable_officer']); ?></div>
                            <div class="sig-sub">(Signature of Accountable Officer)</div>
                            <div class="sig-pos"><?php echo htmlspecialchars($iirup_form['accountable_officer_designation'] ?? $iirup_form['designation']); ?></div>
                            <div class="sig-sub">(Designation)</div>
                        </div>
                        <div class="sig-entry">
                            <div style="text-align: left; font-size: 8px; margin-bottom: 20px;">Approved by:</div>
                            <div class="sig-name"><?php echo htmlspecialchars($iirup_form['authorized_official_name']); ?></div>
                            <div class="sig-sub">(Signature of Authorized Official)</div>
                            <div class="sig-pos"><?php echo htmlspecialchars($iirup_form['authorized_official_designation']); ?></div>
                            <div class="sig-sub">(Designation)</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sig-box">
                <div class="cert-text">I CERTIFY that I have inspected each and every article enumerated in this report, and that the disposition made thereof was, in my judgment, the best for the public interest.</div>
                <div class="sig-content">
                    <div class="sig-entry">
                        <div class="sig-name" style="margin-top: 30px;"><?php echo htmlspecialchars($iirup_form['inspection_officer_name']); ?></div>
                        <div class="sig-sub">(Signature over Printed Name of Inspection Officer)</div>
                    </div>
                </div>
            </div>
            <div class="sig-box">
                <div class="cert-text">I CERTIFY that I have witnessed the disposition of the articles enumerated on this report this _____day of _____________, ________.</div>
                <div class="sig-content">
                    <div class="sig-entry">
                        <div class="sig-name" style="margin-top: 30px;"><?php echo htmlspecialchars($iirup_form['witness_name']); ?></div>
                        <div class="sig-sub">(Signature over Printed Name of Witness)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
        window.onafterprint = function() { window.close(); };
    </script>
</body>
</html>
