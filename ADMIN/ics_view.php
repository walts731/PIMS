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

logSystemAction($_SESSION['user_id'], 'Viewed ICS Form', 'forms', "ICS ID: $ics_id, ICS No: {$ics_form['ics_no']}");

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'ICS'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICS View - <?php echo htmlspecialchars($ics_form['ics_no']); ?> - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .ics-card {
            background: white;
            border: 2px solid #000;
            padding: 0;
            margin: 20px auto;
            max-width: 850px;
            box-shadow: none;
            position: relative;
        }

        .ics-header {
            padding: 20px 20px 10px;
            display: flex;
            align-items: center;
            border-bottom: none;
            position: relative;
        }

        .ics-header .logo-container {
            margin-right: 20px;
        }

        .ics-header .logo-container img {
            max-height: 80px;
        }

        .ics-header .header-text {
            text-align: center;
            flex-grow: 1;
            margin-right: 50px;
            line-height: 1.4;
        }

        .ics-header .header-text p {
            margin: 0;
            font-size: 14px;
        }

        .ics-header .annex {
            position: absolute;
            right: 20px;
            top: 20px;
            font-style: italic;
            font-size: 13px;
        }

        .ics-title-section {
            text-align: center;
            padding: 10px 0;
        }

        .ics-title-section h2 {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .meta-section {
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .meta-item {
            display: flex;
            align-items: center;
        }

        .meta-label {
            min-width: 90px;
        }

        .meta-value {
            border-bottom: 1px solid #000;
            min-width: 200px;
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
            padding: 5px 8px;
            font-size: 12px;
            height: 25px;
        }

        .excel-grid-table th {
            text-align: center;
            font-weight: bold;
        }

        .amount-header-row th {
            border-bottom: none;
        }

        .amount-sub-row th {
            font-size: 11px;
        }

        .signature-section {
            display: flex;
            border-top: none;
        }

        .sig-box {
            flex: 1;
            border: 1px solid #000;
            padding: 10px 15px 25px;
            display: flex;
            flex-direction: column;
            min-height: 180px;
        }

        .sig-box:first-child {
            border-left: none;
        }

        .sig-box:last-child {
            border-right: none;
        }

        .sig-label {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .sig-content {
            text-align: center;
            margin-top: auto;
        }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            margin-bottom: 2px;
            text-decoration: underline;
        }

        .sig-sub {
            font-size: 12px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .sig-line {
            width: 80%;
            margin: 0 auto 5px;
            border-bottom: 1px solid #000;
        }

        .sig-date-label {
            font-size: 11px;
            margin-top: 2px;
        }

        @media print {
            .no-print, .sidebar, .topbar { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .ics-card { margin: 0; border: 2px solid #000; width: 100%; max-width: none; }
            .main-content { padding: 0; margin: 0; }
            .main-wrapper { padding: 0; margin: 0; }
        }
    </style>
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'ICS View - ' . htmlspecialchars($ics_form['ics_no']);
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="mb-1"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>ICS View</h1>
                    <p class="text-muted mb-0">Official Inventory Custodian Slip format</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="btn-group shadow-sm">
                        <a href="print_ics.php?id=<?php echo $ics_id; ?>" target="_blank" class="btn btn-primary">
                            <i class="bi bi-printer-fill me-1"></i> Print
                        </a>
                        <a href="ics_form.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> New
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="ics-card">
            <div class="ics-header">
                <div class="logo-container" style="width: 100%; text-align: center; margin-right: 0;">
                    <?php if ($header_image): ?>
                        <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" alt="Header Image" style="max-height: 120px; width: auto; max-width: 100%;">
                    <?php else: ?>
                        <img src="../img/system_logo.png" alt="Logo" style="max-height: 80px;">
                    <?php endif; ?>
                </div>
                <div class="annex">Annex A.3</div>
            </div>

            <div class="ics-title-section">
                <h2>INVENTORY CUSTODIAN SLIP</h2>
            </div>

            <div class="meta-section">
                <div class="meta-group">
                    <div class="meta-item">
                        <span class="meta-label">Entity Name:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($ics_form['entity_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Fund Cluster:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($ics_form['fund_cluster']); ?></span>
                    </div>
                </div>
                <div class="meta-group">
                    <div class="meta-item">
                        <span class="meta-label">ICS No :</span>
                        <span class="meta-value"><?php echo htmlspecialchars($ics_form['ics_no']); ?></span>
                    </div>
                </div>
            </div>

            <table class="excel-grid-table">
                <thead>
                    <tr class="amount-header-row">
                        <th rowspan="2" width="60">Quantity</th>
                        <th rowspan="2" width="60">Unit</th>
                        <th colspan="2">Amount</th>
                        <th rowspan="2">Description</th>
                        <th rowspan="2" width="70">Item No.</th>
                        <th rowspan="2" width="100">Estimated Useful Life</th>
                    </tr>
                    <tr class="amount-sub-row">
                        <th width="80">Unit Cost</th>
                        <th width="90">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_amount = 0;
                    foreach ($ics_items as $item): 
                        $total_amount += $item['total_cost'];
                    ?>
                        <tr>
                            <td align="center"><?php echo number_format($item['quantity'], 0); ?></td>
                            <td align="center"><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td align="right"><?php echo number_format($item['unit_cost'], 2); ?></td>
                            <td align="right"><?php echo number_format($item['total_cost'], 2); ?></td>
                            <td style="padding-left: 10px;"><?php echo htmlspecialchars($item['description']); ?></td>
                            <td align="center"><?php echo htmlspecialchars($item['item_no']); ?></td>
                            <td align="center"><strong><?php echo htmlspecialchars($item['useful_life']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php 
                    $remaining = 18 - count($ics_items);
                    for($i=0; $i<$remaining; $i++): 
                    ?>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <?php if ($remaining > 0 && $i === ($remaining - 1)): ?>
                                <td align="right" style="color: red; font-weight: bold;"><?php echo number_format($total_amount, 2); ?></td>
                            <?php else: ?>
                                <td>&nbsp;</td>
                            <?php endif; ?>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <div class="signature-section">
                <div class="sig-box">
                    <div class="sig-label">Received from:</div>
                    <div class="sig-content">
                        <div class="sig-name"><?php echo htmlspecialchars($ics_form['received_from']); ?></div>
                        <div class="sig-sub"><?php echo htmlspecialchars($ics_form['received_from_position']); ?></div>
                        <div class="sig-line"></div>
                        <div class="sig-date-label">Date</div>
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-label">Received by:</div>
                    <div class="sig-content">
                        <div class="sig-name"><?php echo htmlspecialchars($ics_form['received_by']); ?></div>
                        <div class="sig-sub"><?php echo htmlspecialchars($ics_form['received_by_position']); ?></div>
                        <div class="sig-line"></div>
                        <div class="sig-date-label">Date</div>
                    </div>
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
