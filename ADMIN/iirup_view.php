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

logSystemAction($_SESSION['user_id'], 'Viewed IIRUP Form', 'forms', "IIRUP ID: $iirup_id, Form No: {$iirup_form['form_number']}");

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'IIRUP'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IIRUP View - <?php echo htmlspecialchars($iirup_form['form_number']); ?> - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .iirup-card {
            background: white;
            border: 2px solid #000;
            padding: 0;
            margin: 20px auto;
            max-width: 1100px;
            box-shadow: none;
            position: relative;
        }

        .iirup-header {
            padding: 20px 20px 10px;
            text-align: center;
        }

        .iirup-header .logo-container {
            margin-bottom: 15px;
        }

        .iirup-header img {
            max-height: 100px;
            width: auto;
        }

        .iirup-header h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0 5px;
            text-transform: uppercase;
        }

        .iirup-header p {
            margin: 0;
            font-size: 14px;
            font-style: italic;
        }

        .meta-top-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            padding: 20px;
            border-top: none;
        }

        .meta-item {
            text-align: center;
        }

        .meta-value {
            border-bottom: 1px solid #000;
            min-height: 25px;
            width: 100%;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .meta-label {
            font-style: italic;
            font-size: 12px;
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
            background: #f9f9f9;
        }

        .group-header {
            font-size: 12px;
            letter-spacing: 1px;
        }

        .sub-group-header {
            font-size: 9px;
            background: #fff !important;
        }

        .num-header {
            font-size: 8px;
            font-weight: normal;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            border-top: none;
        }

        .sig-box {
            border: 1px solid #000;
            padding: 15px;
            display: flex;
            flex-direction: column;
            min-height: 220px;
        }

        .sig-box:first-child { border-left: none; }
        .sig-box:last-child { border-right: none; }

        .cert-text {
            font-size: 11px;
            line-height: 1.4;
            margin-bottom: 25px;
        }

        .sig-content {
            margin-top: auto;
        }

        .sig-dual-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .sig-entry {
            text-align: center;
        }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
            margin-bottom: 2px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 80%;
        }

        .sig-sub {
            font-size: 10px;
            margin-top: 2px;
        }

        .sig-pos {
            font-size: 11px;
            margin-top: 5px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 80%;
        }

        @media print {
            .no-print, .sidebar, .topbar { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .iirup-card { margin: 0; border: 2px solid #000; width: 100%; max-width: none; }
            .main-content { padding: 0; margin: 0; }
            .main-wrapper { padding: 0; margin: 0; }
        }
    </style>
</head>

<body>
    <?php
    // Set page title for topbar
    $page_title = 'IIRUP View - ' . htmlspecialchars($iirup_form['form_number']);
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="mb-1"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>IIRUP View</h1>
                    <p class="text-muted mb-0">Inventory and Inspection Report layout</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="dropdown shadow-sm d-inline-block">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="iirupActions" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear-fill me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="iirupActions">
                            <li>
                                <a class="dropdown-item" href="print_iirup.php?id=<?php echo $iirup_id; ?>" target="_blank">
                                    <i class="bi bi-printer-fill me-2 text-primary"></i> Print IIRUP
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="iirup_form.php">
                                    <i class="bi bi-plus-circle-fill me-2 text-success"></i> New IIRUP
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="iirup_entries.php">
                                    <i class="bi bi-list-task me-2 text-info"></i> View All Entries
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="iirup-card">
            <div class="iirup-header">
                <div class="logo-container">
                    <?php if ($header_image): ?>
                        <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Banner">
                    <?php else: ?>
                        <img src="../img/system_logo.png" alt="Logo">
                    <?php endif; ?>
                </div>
                <h2>INVENTORY AND INSPECTION REPORT OF UNSERVICEABLE PROPERTY</h2>
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
                        <th rowspan="2" width="60">Date Acquired</th>
                        <th rowspan="2">Particulars/ Articles</th>
                        <th rowspan="2" width="60">Property No.</th>
                        <th rowspan="2" width="30">Qty</th>
                        <th rowspan="2" width="60">Unit Cost</th>
                        <th rowspan="2" width="70">Total Cost</th>
                        <th rowspan="2" width="70">Accumulated Depreciation</th>
                        <th rowspan="2" width="70">Accumulated Impairment</th>
                        <th rowspan="2" width="70">Carrying Amount</th>
                        <th rowspan="2" width="60">Remarks</th>
                        <th colspan="5" class="sub-group-header">DISPOSAL</th>
                        <th rowspan="2" width="60">Appraised Value</th>
                        <th colspan="2" class="sub-group-header">RECORD OF SALES</th>
                    </tr>
                    <tr>
                        <th width="40" class="sub-group-header">Sale</th>
                        <th width="40" class="sub-group-header">Transfer</th>
                        <th width="50" class="sub-group-header">Destruction</th>
                        <th width="60" class="sub-group-header">Others (Specify)</th>
                        <th width="50" class="sub-group-header">Total</th>
                        <th width="50" class="sub-group-header">OR No.</th>
                        <th width="60" class="sub-group-header">Amount</th>
                    </tr>
                    <tr class="num-header">
                        <th>(1)</th><th>(2)</th><th>(3)</th><th>(4)</th><th>(5)</th><th>(6)</th><th>(7)</th><th>(8)</th><th>(9)</th><th>(10)</th><th>(11)</th><th>(12)</th><th>(13)</th><th>(14)</th><th>(15)</th><th>(16)</th><th>(17)</th><th>(18)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_inventory = 0;
                    foreach ($iirup_items as $item): 
                        $total_inventory += $item['total_cost'];
                    ?>
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
                            <!-- Disposal Cols -->
                            <td align="right"><?php echo $item['disposal_sale'] > 0 ? number_format($item['disposal_sale'], 2) : ''; ?></td>
                            <td align="right"><?php echo $item['disposal_transfer'] > 0 ? number_format($item['disposal_transfer'], 2) : ''; ?></td>
                            <td align="right"><?php echo $item['disposal_destruction'] > 0 ? number_format($item['disposal_destruction'], 2) : ''; ?></td>
                            <td align="center"><?php echo htmlspecialchars($item['disposal_others']); ?></td>
                            <td align="right"><?php echo $item['disposal_total'] > 0 ? number_format($item['disposal_total'], 2) : ''; ?></td>
                            <td align="right"><?php echo $item['appraised_value'] > 0 ? number_format($item['appraised_value'], 2) : ''; ?></td>
                            <!-- Record of Sales -->
                            <td align="center"><?php echo htmlspecialchars($item['or_no']); ?></td>
                            <td align="right"><?php echo $item['amount'] > 0 ? number_format($item['amount'], 2) : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php 
                    $remaining = 15 - count($iirup_items);
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
                            <div class="sig-entry" style="margin-top: 20px;">
                                <div style="text-align: left; font-size: 11px; margin-bottom: 30px;">Requested by:</div>
                                <div class="sig-name"><?php echo htmlspecialchars($iirup_form['accountable_officer_name'] ?? $iirup_form['accountable_officer']); ?></div>
                                <div class="sig-sub">(Signature over Printed Name of Accountable Officer)</div>
                                <div class="sig-pos"><?php echo htmlspecialchars($iirup_form['accountable_officer_designation'] ?? $iirup_form['designation']); ?></div>
                                <div class="sig-sub">(Designation of Accountable Officer)</div>
                            </div>
                            <div class="sig-entry" style="margin-top: 20px;">
                                <div style="text-align: left; font-size: 11px; margin-bottom: 30px;">Approved by:</div>
                                <div class="sig-name"><?php echo htmlspecialchars($iirup_form['authorized_official_name']); ?></div>
                                <div class="sig-sub">(Signature over Printed Name of Authorized Official)</div>
                                <div class="sig-pos"><?php echo htmlspecialchars($iirup_form['authorized_official_designation']); ?></div>
                                <div class="sig-sub">(Designation of Authorized Official)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sig-box">
                    <div class="cert-text">I CERTIFY that I have inspected each and every article enumerated in this report, and that the disposition made thereof was, in my judgment, the best for the public interest.</div>
                    <div class="sig-content">
                        <div class="sig-entry">
                            <div class="sig-name" style="margin-top: 50px;"><?php echo htmlspecialchars($iirup_form['inspection_officer_name']); ?></div>
                            <div class="sig-sub">(Signature over Printed Name of Inspection Officer)</div>
                        </div>
                    </div>
                </div>
                <div class="sig-box">
                    <div class="cert-text">I CERTIFY that I have witnessed the disposition of the articles enumerated on this report this _____day of _____________, ________.</div>
                    <div class="sig-content">
                        <div class="sig-entry">
                            <div class="sig-name" style="margin-top: 50px;"><?php echo htmlspecialchars($iirup_form['witness_name']); ?></div>
                            <div class="sig-sub">(Signature over Printed Name of Witness)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>