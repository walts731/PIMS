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

// Get ITR ID from URL
$itr_id = $_GET['id'] ?? 0;
if (empty($itr_id)) {
    header('Location: itr_entries.php');
    exit();
}

// Get ITR form details with employee and office information
$itr_form = null;
$stmt = $conn->prepare("
    SELECT i.*, 
           from_emp.firstname as from_firstname, 
           from_emp.lastname as from_lastname, 
           from_emp.position as from_position,
           from_office.office_name as from_office_name,
           to_emp.firstname as to_firstname, 
           to_emp.lastname as to_lastname, 
           to_emp.position as to_position,
           to_office.office_name as to_office_name
    FROM itr_forms i 
    LEFT JOIN employees from_emp ON i.from_office = from_emp.id
    LEFT JOIN offices from_office ON from_emp.office_id = from_office.id
    LEFT JOIN employees to_emp ON i.to_office = to_emp.id
    LEFT JOIN offices to_office ON to_emp.office_id = to_office.id
    WHERE i.id = ?
");
$stmt->bind_param("i", $itr_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $itr_form = $result->fetch_assoc();
}
$stmt->close();

if (!$itr_form) {
    header('Location: itr_entries.php');
    exit();
}

// Get ITR items with asset details
$itr_items = [];
$stmt = $conn->prepare("
    SELECT ii.*, ai.description as asset_description, 
           ai.property_no, ai.ics_par_no as asset_ics_par_no,
           ics.ics_no, par.par_no
    FROM itr_items ii 
    LEFT JOIN asset_items ai ON ii.description = ai.id 
    LEFT JOIN ics_forms ics ON ai.ics_id = ics.id
    LEFT JOIN par_forms par ON ai.par_id = par.id
    WHERE ii.form_id = ? 
    ORDER BY ii.id
");
$stmt->bind_param("i", $itr_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $itr_items[] = $row;
}
$stmt->close();

// Get system settings for logo and name
$system_settings = [];
$result = $conn->query("SELECT setting_name, setting_value FROM system_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
}

$logo_path = !empty($system_settings['system_logo']) ? '../' . htmlspecialchars($system_settings['system_logo']) : '../img/trans_logo.png';
$system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');

logSystemAction($_SESSION['user_id'], 'Printed ITR Form', 'forms', "ITR ID: $itr_id, ITR No: {$itr_form['itr_no']}");

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'ITR'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITR Print - <?php echo htmlspecialchars($itr_form['itr_no']); ?> - PIMS</title>
    <style>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.25in;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            font-size: 10px;
            color: #000;
        }

        .itr-document {
            width: 100%;
            border: 2px solid #000;
            position: relative;
            box-sizing: border-box;
        }

        .itr-header {
            padding: 10px 15px 5px;
            display: flex;
            align-items: flex-start;
        }

        .logo-box {
            width: 60px;
        }

        .logo-box img {
            width: 50px;
            height: auto;
        }

        .header-central {
            flex-grow: 1;
            text-align: center;
        }

        .header-central p {
            margin: 0;
            font-size: 9px;
            line-height: 1.2;
        }

        .header-central h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0 0;
            text-transform: uppercase;
        }

        .annex-box {
            width: 60px;
            text-align: right;
            font-style: italic;
            font-size: 9px;
            font-weight: bold;
        }

        .entity-info-bar {
            padding: 0 15px 5px;
            font-size: 10px;
            display: flex;
            justify-content: space-between;
        }

        .underline-input {
            border-bottom: 1px solid #000;
            padding: 0 4px;
            font-weight: bold;
            text-decoration: underline;
        }

        .technical-grid {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .technical-grid td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 8px;
            vertical-align: middle;
        }

        .excel-table {
            width: 100%;
            border-collapse: collapse;
            border-top: none;
        }

        .excel-table th, .excel-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 8px;
            height: 22px;
        }

        .excel-table th {
            text-align: center;
            font-weight: bold;
            background: #fff;
        }

        .check-box {
            display: inline-block;
            width: 30px;
            height: 14px;
            border: 1px solid #000;
            text-align: center;
            line-height: 14px;
            margin-right: 4px;
            font-weight: bold;
            font-size: 9px;
        }

        .reason-box {
            border: 1px solid #000;
            border-top: none;
            padding: 4px 6px;
            min-height: 50px;
        }

        .reason-title {
            font-size: 8px;
            margin-bottom: 2px;
        }

        .reason-content {
            text-align: center;
            font-weight: bold;
            font-size: 8px;
            margin-top: 4px;
            text-transform: uppercase;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border: 1px solid #000;
            border-top: none;
        }

        .sig-col {
            border-right: 1px solid #000;
            display: flex;
            flex-direction: column;
        }

        .sig-col:last-child {
            border-right: none;
        }

        .sig-header {
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #000;
            font-size: 8px;
            font-weight: normal;
        }

        .sig-row {
            display: flex;
            border-bottom: 1px solid #000;
            height: 18px;
            align-items: center;
        }

        .sig-row:last-child {
            border-bottom: none;
        }

        .sig-label {
            width: 50px;
            padding-left: 3px;
            font-size: 7.5px;
            border-right: 1px solid #000;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .sig-value {
            flex-grow: 1;
            padding: 0 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        @media screen {
            body { background: #525659; padding: 40px 0; }
            .itr-document { background: white; margin: 0 auto; width: 10.5in; }
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; background: white; }
            .itr-document { margin: 0; border: 2px solid #000; width: 100%; box-sizing: border-box; }
        }
    </style>
</head>
<body>
    <div class="itr-document">
        <div class="itr-header">
            <div class="logo-box">
                <img src="../img/trans_logo.png" alt="Logo">
            </div>
            <div class="header-central">
                <p>Republic of the Philippines</p>
                <p><strong>Municipality of Pilar</strong></p>
                <p>Province of Sorsogon</p>
                <h2>PROPERTY TRANSFER REPORT</h2>
            </div>
            <div class="annex-box">Annex A.3</div>
        </div>

        <div class="entity-info-bar">
            <div>Entity Name: <span class="underline-input"><?php echo htmlspecialchars($itr_form['entity_name']); ?></span></div>
            <div>Fund Cluster: <span class="underline-input"><?php echo htmlspecialchars($itr_form['fund_cluster']); ?></span></div>
        </div>

        <table class="technical-grid">
            <tr>
                <td width="60%">From Accountable Officer/Agency/Fund Cluster: <strong><?php echo htmlspecialchars(($itr_form['from_firstname'] . ' ' . $itr_form['from_lastname']) . ' / ' . $itr_form['from_office_name']); ?></strong></td>
                <td width="15%">ITR No. :</td>
                <td width="25%"><strong><?php echo htmlspecialchars($itr_form['itr_no']); ?></strong></td>
            </tr>
            <tr>
                <td>To Accountable Officer/Agency/Fund Cluster: <strong><?php echo htmlspecialchars(($itr_form['to_firstname'] . ' ' . $itr_form['to_lastname']) . ' / ' . $itr_form['to_office_name']); ?></strong></td>
                <td>Date :</td>
                <td><strong><?php echo date('m/d/Y', strtotime($itr_form['transfer_date'])); ?></strong></td>
            </tr>
            <tr>
                <td colspan="3">
                    <div style="display: flex; align-items: flex-start;">
                        <div style="margin-right: 30px;">Transfer Type: (check only)</div>
                        <div>
                            <div style="display: flex; align-items: center; margin-bottom: 3px;">
                                <div class="check-box"><?php echo (strtolower($itr_form['transfer_type']) == 'relocate') ? '√' : ''; ?></div> Relocate
                            </div>
                            <div style="display: flex; align-items: center;">
                                <div class="check-box"><?php echo (strtolower($itr_form['transfer_type']) != 'relocate') ? '√' : ''; ?></div> Others (Specify) <span style="text-decoration: underline; margin-left: 5px;"><?php echo strtolower($itr_form['transfer_type']) != 'relocate' ? htmlspecialchars($itr_form['transfer_type']) : '___________________'; ?></span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="excel-table">
            <thead>
                <tr>
                    <th width="65">Date Acquired</th>
                    <th width="40">Item No.</th>
                    <th width="90">ICS & PAR No./Date</th>
                    <th>Description</th>
                    <th width="80">Unit Price</th>
                    <th width="80">Total Amount</th>
                    <th width="110">Condition of Inventory</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_amount = 0;
                foreach ($itr_items as $index => $item): 
                    $total_amount += $item['total_amount'];
                ?>
                    <tr>
                        <td align="center"><?php echo !empty($item['date_acquired']) ? date('m/d/Y', strtotime($item['date_acquired'])) : ''; ?></td>
                        <td align="center"><?php echo $index + 1; ?></td>
                        <td align="center">
                            <?php 
                            if (!empty($item['par_no'])) {
                                echo htmlspecialchars($item['par_no']);
                            } elseif (!empty($item['ics_no'])) {
                                echo htmlspecialchars($item['ics_no']);
                            } elseif (!empty($item['asset_ics_par_no'])) {
                                echo htmlspecialchars($item['asset_ics_par_no']);
                            } else {
                                echo htmlspecialchars($item['ics_par_no']);
                            }
                            ?>
                        </td>
                        <td align="left">
                            <strong><?php echo htmlspecialchars($item['asset_description'] ?: $item['description']); ?></strong>
                            <?php if(!empty($item['property_no'])): ?>
                                <br><small>Property No: <?php echo htmlspecialchars($item['property_no']); ?></small>
                            <?php endif; ?>
                            <?php if(!empty($item['remarks'])): ?>
                                <br><small>SN: <?php echo htmlspecialchars($item['remarks']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td align="right"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td align="right"><?php echo number_format($item['total_amount'], 2); ?></td>
                        <td align="center"><?php echo htmlspecialchars($item['condition_of_inventory']); ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php 
                $remaining = 17 - count($itr_items);
                for($i=0; $i<$remaining; $i++): 
                ?>
                    <tr>
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="reason-box">
            <div class="reason-title">Reason/s for Transfer:</div>
            <div class="reason-content">
                <?php echo nl2br(htmlspecialchars($itr_form['purpose'])); ?>
            </div>
        </div>

        <div class="signature-grid">
            <div class="sig-col">
                <div class="sig-header">Approved by:</div>
                <div class="sig-row"><div class="sig-label">Signature:</div><div class="sig-value"></div></div>
                <div class="sig-row"><div class="sig-label">Printed Name:</div><div class="sig-value"><?php echo htmlspecialchars($itr_form['approved_by']); ?></div></div>
                <div class="sig-row"><div class="sig-label">Designation:</div><div class="sig-value"><?php echo htmlspecialchars($itr_form['approved_by_position']); ?></div></div>
                <div class="sig-row"><div class="sig-label">Date:</div><div class="sig-value"><?php echo ($itr_form['approved_date'] && $itr_form['approved_date'] != '0000-00-00') ? date('m/d/Y', strtotime($itr_form['approved_date'])) : ''; ?></div></div>
            </div>
            <div class="sig-col">
                <div class="sig-header">Released/Issued by:</div>
                <div class="sig-row"><div class="sig-label">Signature:</div><div class="sig-value"></div></div>
                <div class="sig-row"><div class="sig-label">Printed Name:</div><div class="sig-value"><?php echo htmlspecialchars($itr_form['released_by']); ?></div></div>
                <div class="sig-row"><div class="sig-label">Designation:</div><div class="sig-value"><?php echo htmlspecialchars($itr_form['released_by_position']); ?></div></div>
                <div class="sig-row"><div class="sig-label">Date:</div><div class="sig-value"><?php echo ($itr_form['released_date'] && $itr_form['released_date'] != '0000-00-00') ? date('m/d/Y', strtotime($itr_form['released_date'])) : date('m/d/Y'); ?></div></div>
            </div>
            <div class="sig-col">
                <div class="sig-header">Received by:</div>
                <div class="sig-row"><div class="sig-label">Signature:</div><div class="sig-value"></div></div>
                <div class="sig-row"><div class="sig-label">Printed Name:</div><div class="sig-value"><?php echo htmlspecialchars($itr_form['received_by']); ?></div></div>
                <div class="sig-row"><div class="sig-label">Designation:</div><div class="sig-value"><?php echo htmlspecialchars($itr_form['received_by_position']); ?></div></div>
                <div class="sig-row"><div class="sig-label">Date:</div><div class="sig-value"><?php echo ($itr_form['received_date'] && $itr_form['received_date'] != '0000-00-00') ? date('m/d/Y', strtotime($itr_form['received_date'])) : ''; ?></div></div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>
