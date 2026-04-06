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
    SELECT ii.*, ai.description as asset_description 
    FROM itr_items ii 
    LEFT JOIN asset_items ai ON ii.description = ai.id 
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
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            background: white;
            font-size: 12px;
            line-height: 1.4;
        }
        .ptr-card {
            border: 2px solid #000;
            padding: 0;
            margin: 0;
            max-width: 100%;
        }
        .ptr-header {
            padding: 30px;
            border-bottom: 1px solid #000;
            background: #fff;
            position: relative;
            text-align: center;
        }
        .seal-img {
            width: 80px;
            height: 80px;
            position: absolute;
            top: 30px;
            left: 30px;
        }
        .header-content {
            display: inline-block;
            text-align: center;
        }
        .header-text {
            text-align: center;
        }
        @page {
            size: A4;
            margin: 0.3in;
        }
        .header-text p {
            margin: 0;
            font-size: 12px;
            color: #000;
            font-weight: 500;
        }
        .header-text h3 {
            margin: 5px 0;
            font-size: 16px;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-right {
            position: absolute;
            top: 30px;
            right: 30px;
            text-align: right;
        }
        .ptr-header .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
            margin-top: 15px;
        }
        .ptr-annex {
            font-size: 12px;
            color: #000;
            font-weight: bold;
            margin-bottom: 10px;
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
            color: #000;
        }
        .entity-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 18px;
            font-size: 11px;
            padding: 0 5px;
            color: #000;
        }
        .itr-no-section {
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
            font-size: 10px;
            color: #000;
        }
        .items-table th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            background: #fff;
            color: #000;
        }
        .items-table .text-left { text-align: left; }
        .items-table .text-right { text-align: right; }
        
        .item-no-col { width: 60px; }
        .date-col { width: 100px; }
        .ref-col { width: 120px; }
        .price-col { width: 100px; }
        .amount-col { width: 110px; }
        .condition-col { width: 100px; }
        
        .total-row td {
            font-weight: bold;
            text-align: right;
            padding-right: 10px;
        }
        .footer-section {
            margin-top: 5px;
            border: 1px solid #000;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .footer-table td {
            border: 1px solid #000;
            padding: 2px;
            width: 33.33%;
            vertical-align: top;
        }
        .label-row {
            font-weight: bold;
            margin-bottom: 2px;
            color: #000;
            font-size: 6px;
        }
        .name-line {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            margin-bottom: 0px;
            font-size: 6px;
            color: #000;
            line-height: 1.0;
            padding: 0px;
        }
        .sub-label {
            text-align: center;
            font-size: 6px;
            margin-bottom: 2px;
            color: #000;
            line-height: 1.0;
        }
        .signature-group {
            margin-top: 1px;
        }
        
        .main-content {
            padding: 0;
        }
        
        @media print {
            body { margin: 0; background: white; }
            .ptr-card { box-shadow: none; border: 2px solid #000; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="ptr-card">
        <!-- Header Section -->
        <div class="ptr-header">
            <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" class="seal-img">
            <div class="header-content">
                <div class="header-text">
                    <p>Republic of the Philippines</p>
                    <h3>Municipality of Pilar</h3>
                    <p>Province of Sorsogon</p>
                    <h2 class="title">PROPERTY TRANSFER REQUEST</h2>
                </div>
            </div>
            <div class="header-right">
                <div class="ptr-annex">
                    <p>Annex A.3</p>
                </div>
            </div>
        </div>

        <!-- Entity Information -->
        <div style="padding: 20px;">
            <div class="entity-section">
                <div class="entity-row">
                    <div class="entity-label">Entity Name:</div>
                    <div class="entity-value"><?php echo htmlspecialchars($itr_form['entity_name']); ?></div>
                    <div class="entity-label">Fund Cluster:</div>
                    <div class="entity-value"><?php echo htmlspecialchars($itr_form['fund_cluster']); ?></div>
                </div>
            </div>
            
            <!-- Transfer Information -->
            <div class="entity-section">
                <div class="entity-row">
                    <div class="entity-label">From Office:</div>
                    <div class="entity-value"><?php 
                        $from_name = trim($itr_form['from_firstname'] . ' ' . $itr_form['from_lastname']);
                        $from_office = $itr_form['from_office_name'];
                        echo $from_name && $from_office ? htmlspecialchars($from_name . '/' . $from_office) : 
                             ($from_name ? htmlspecialchars($from_name) : 'N/A');
                    ?></div>
                    <div class="entity-label">Transfer Date:</div>
                    <div class="entity-value"><?php echo date('F d, Y', strtotime($itr_form['transfer_date'])); ?></div>
                </div>
                <div class="entity-row">
                    <div class="entity-label">To Office:</div>
                    <div class="entity-value"><?php 
                        $to_name = trim($itr_form['to_firstname'] . ' ' . $itr_form['to_lastname']);
                        $to_office = $itr_form['to_office_name'];
                        echo $to_name && $to_office ? htmlspecialchars($to_name . '/' . $to_office) : 
                             ($to_name ? htmlspecialchars($to_name) : 'N/A');
                    ?></div>
                    <div class="entity-label">End User:</div>
                    <div class="entity-value"><?php echo htmlspecialchars($itr_form['end_user']); ?></div>
                </div>
                <div class="entity-row">
                    <div class="entity-label">Transfer Type:</div>
                    <div class="entity-value"><?php echo htmlspecialchars($itr_form['transfer_type']); ?></div>
                    <div class="entity-label">ITR No:</div>
                    <div class="entity-value" style="font-weight: bold;"><?php echo htmlspecialchars($itr_form['itr_no']); ?></div>
                </div>
                <?php if (!empty($itr_form['purpose'])): ?>
                <div class="entity-row">
                    <div class="entity-label">Purpose:</div>
                    <div class="entity-value"><?php echo htmlspecialchars($itr_form['purpose']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="item-no-col">Item No.</th>
                        <th class="date-col">Date Acquired</th>
                        <th class="ref-col">ICS/PAR No.</th>
                        <th class="text-left">Description</th>
                        <th class="price-col">Unit Price</th>
                        <th class="amount-col">Total Amount</th>
                        <th class="condition-col">Condition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_amount = 0;
                    foreach ($itr_items as $index => $item): 
                        $total_amount += $item['total_amount'];
                    ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo $item['date_acquired'] ? date('M d, Y', strtotime($item['date_acquired'])) : ''; ?></td>
                            <td><?php echo htmlspecialchars($item['ics_par_no']); ?></td>
                            <td class="text-left"><?php echo htmlspecialchars($item['asset_description'] ?: $item['description']); ?></td>
                            <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($item['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($item['condition_of_inventory']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php 
                    // Add empty rows to maintain form height
                    $total_items = count($itr_items);
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
                        <td colspan="5">TOTAL AMOUNT:</td>
                        <td class="text-right"><?php echo number_format($total_amount, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Footer / Signatures Section -->
            <div class="footer-section">
                <table class="footer-table">
                    <tr>
                        <td>
                            <div class="label-row">Approved By:</div>
                            <div class="signature-group">
                                <div class="name-line"><?php echo htmlspecialchars($itr_form['approved_by']); ?></div>
                                <div class="sub-label">Signature Over Printed Name</div>
                                <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($itr_form['approved_by_position']); ?></div>
                                <div class="sub-label">Position / Office</div>
                                <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo ($itr_form['approved_date'] && $itr_form['approved_date'] != '0000-00-00') ? date('F d, Y', strtotime($itr_form['approved_date'])) : ''; ?></div>
                                <div class="sub-label">Date</div>
                            </div>
                        </td>
                        <td>
                            <div class="label-row">Released By:</div>
                            <div class="signature-group">
                                <div class="name-line"><?php echo htmlspecialchars($itr_form['released_by']); ?></div>
                                <div class="sub-label">Signature Over Printed Name</div>
                                <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($itr_form['released_by_position']); ?></div>
                                <div class="sub-label">Position / Office</div>
                                <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo ($itr_form['released_date'] && $itr_form['released_date'] != '0000-00-00') ? date('F d, Y', strtotime($itr_form['released_date'])) : ''; ?></div>
                                <div class="sub-label">Date</div>
                            </div>
                        </td>
                        <td>
                            <div class="label-row">Received By:</div>
                            <div class="signature-group">
                                <div class="name-line"><?php echo htmlspecialchars($itr_form['received_by']); ?></div>
                                <div class="sub-label">Signature Over Printed Name</div>
                                <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($itr_form['received_by_position']); ?></div>
                                <div class="sub-label">Position / Office</div>
                                <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo ($itr_form['received_date'] && $itr_form['received_date'] != '0000-00-00') ? date('F d, Y', strtotime($itr_form['received_date'])) : ''; ?></div>
                                <div class="sub-label">Date</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="preview-toolbar no-print">
        <div class="d-flex justify-content-between align-items-center bg-dark text-white p-2">
            <div>
                <i class="bi bi-eye me-2"></i> Print Preview - ITR
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
            .ptr-card {
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
            .ptr-card { 
                box-shadow: none; 
                margin: 0; 
                padding: 0; 
                width: 100%;
            }
            @page { margin: 0.3in; }
        }
    </style>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Bootstrap CSS for Toolbar -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</body>
</html>
