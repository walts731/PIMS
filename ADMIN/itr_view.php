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

logSystemAction($_SESSION['user_id'], 'Viewed ITR Form', 'forms', "ITR ID: $itr_id, ITR No: {$itr_form['itr_no']}");

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
    <title>ITR View - <?php echo htmlspecialchars($itr_form['itr_no']); ?> - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }

        .itr-container {
            background: white;
            border: 2px solid #000;
            padding: 0;
            margin: 20px auto;
            max-width: 900px;
            box-shadow: none;
            position: relative;
            box-sizing: border-box;
        }

        .itr-header {
            padding: 20px;
            display: flex;
            align-items: flex-start;
            border-bottom: none;
        }

        .logo-box {
            width: 80px;
        }

        .logo-box img {
            width: 70px;
            height: auto;
        }

        .header-central {
            flex-grow: 1;
            text-align: center;
        }

        .header-central p {
            margin: 0;
            font-size: 11px;
        }

        .header-central h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
            text-decoration: none;
        }

        .annex-box {
            width: 80px;
            text-align: right;
            font-style: italic;
            font-size: 11px;
            font-weight: bold;
        }

        .entity-info-bar {
            padding: 0 20px 10px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
        }

        .underline-input {
            border-bottom: 1px solid #000;
            padding: 0 5px;
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
            padding: 4px 8px;
            font-size: 10px;
            vertical-align: middle;
        }

        .excel-table {
            width: 100%;
            border-collapse: collapse;
            border-top: none;
        }

        .excel-table th, .excel-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 9px;
            height: 24px;
        }

        .excel-table th {
            text-align: center;
            font-weight: bold;
            background: #fff;
        }

        .check-box {
            display: inline-block;
            width: 40px;
            height: 18px;
            border: 1px solid #000;
            text-align: center;
            line-height: 18px;
            margin-right: 5px;
            font-weight: bold;
        }

        .reason-box {
            border: 1px solid #000;
            border-top: none;
            padding: 5px 10px;
            height: 80px;
        }

        .reason-title {
            font-size: 10px;
            margin-bottom: 5px;
        }

        .reason-content {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            margin-top: 10px;
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
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #000;
            font-size: 10px;
            font-weight: normal;
        }

        .sig-row {
            display: flex;
            border-bottom: 1px solid #000;
            height: 22px;
            align-items: center;
        }

        .sig-row:last-child {
            border-bottom: none;
        }

        .sig-label {
            width: 70px;
            padding-left: 5px;
            font-size: 9px;
            border-right: 1px solid #000;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .sig-value {
            flex-grow: 1;
            padding: 0 5px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        @media print {
            @page { size: A4 landscape; margin: 0.25in; }
            .no-print, .sidebar, .topbar { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .itr-container { margin: 0; border: 2px solid #000; width: 100%; max-width: none; box-sizing: border-box; }
            .main-content { padding: 0; margin: 0; }
            .main-wrapper { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>
    <?php $page_title = 'Property Transfer Report'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><i class="bi bi-arrow-left-right me-2 text-primary"></i>ITR View</h1>
                    <p class="text-muted mb-0">Record No: <strong><?php echo htmlspecialchars($itr_form['itr_no']); ?></strong></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown shadow-sm d-inline-block">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear-fill me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="print_itr.php?id=<?php echo $itr_id; ?>" target="_blank"><i class="bi bi-printer-fill me-2"></i> Print Report</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="itr_entries.php"><i class="bi bi-list-task me-2"></i> View Entries</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="itr-container">
            <div class="itr-header">
                <div class="logo-box">
                    <img src="../img/trans_logo.png" alt="Logo">
                </div>
                <div class="header-central">
                    <p>Republic of the Philippines</p>
                    <p><strong>Municipality of Pilar</strong></p>
                    <p>Province of Sorsogon</p>
                    <h2 style="margin-top: 15px;">PROPERTY TRANSFER REPORT</h2>
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
                            <div style="margin-right: 40px;">Transfer Type: (check only)</div>
                            <div>
                                <div style="display: flex; align-items: center; margin-bottom: 5px;">
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
                        <th width="70">Date Acquired</th>
                        <th width="45">Item No.</th>
                        <th width="100">ICS & PAR No./Date</th>
                        <th>Description</th>
                        <th width="85">Unit Price</th>
                        <th width="85">Total Amount</th>
                        <th width="120">Condition of Inventory</th>
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
                <!-- Column 1 -->
                <div class="sig-col">
                    <div class="sig-header">Approved by:</div>
                    <div class="sig-row"><div class="sig-label">Signature:</div><div class="sig-value"></div></div>
                    <div class="sig-row">
                        <div class="sig-label">Printed Name:</div>
                        <div class="sig-value"><?php echo htmlspecialchars($itr_form['approved_by']); ?></div>
                    </div>
                    <div class="sig-row">
                        <div class="sig-label">Designation:</div>
                        <div class="sig-value"><?php echo htmlspecialchars($itr_form['approved_by_position']); ?></div>
                    </div>
                    <div class="sig-row">
                        <div class="sig-label">Date:</div>
                        <div class="sig-value"><?php echo ($itr_form['approved_date'] && $itr_form['approved_date'] != '0000-00-00') ? date('m/d/Y', strtotime($itr_form['approved_date'])) : ''; ?></div>
                    </div>
                </div>
                <!-- Column 2 -->
                <div class="sig-col">
                    <div class="sig-header">Released/Issued by:</div>
                    <div class="sig-row"><div class="sig-label">Signature:</div><div class="sig-value"></div></div>
                    <div class="sig-row">
                        <div class="sig-label">Printed Name:</div>
                        <div class="sig-value"><?php echo htmlspecialchars($itr_form['released_by']); ?></div>
                    </div>
                    <div class="sig-row">
                        <div class="sig-label">Designation:</div>
                        <div class="sig-value"><?php echo htmlspecialchars($itr_form['released_by_position']); ?></div>
                    </div>
                    <div class="sig-row">
                        <div class="sig-label">Date:</div>
                        <div class="sig-value"><?php echo ($itr_form['released_date'] && $itr_form['released_date'] != '0000-00-00') ? date('m/d/Y', strtotime($itr_form['released_date'])) : date('m/d/Y'); ?></div>
                    </div>
                </div>
                <!-- Column 3 -->
                <div class="sig-col">
                    <div class="sig-header">Received by:</div>
                    <div class="sig-row"><div class="sig-label">Signature:</div><div class="sig-value"></div></div>
                    <div class="sig-row">
                        <div class="sig-label">Printed Name:</div>
                        <div class="sig-value"><?php echo htmlspecialchars($itr_form['received_by']); ?></div>
                    </div>
                    <div class="sig-row">
                        <div class="sig-label">Designation:</div>
                        <div class="sig-value"><?php echo htmlspecialchars($itr_form['received_by_position']); ?></div>
                    </div>
                    <div class="sig-row">
                        <div class="sig-label">Date:</div>
                        <div class="sig-value"><?php echo ($itr_form['received_date'] && $itr_form['received_date'] != '0000-00-00') ? date('m/d/Y', strtotime($itr_form['received_date'])) : ''; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
