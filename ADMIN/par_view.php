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

logSystemAction($_SESSION['user_id'], 'Viewed PAR Form', 'forms', "PAR ID: $par_id, PAR No: {$par_form['par_no']}");

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'PAR'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAR View - <?php echo htmlspecialchars($par_form['par_no']); ?> - PIMS</title>
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
            margin: 0;
            padding: 0;
            color: #333;
        }

        .excel-card {
            background: white;
            border: 2px solid #000;
            padding: 0;
            margin: 20px auto;
            max-width: 850px;
            box-shadow: none;
            position: relative;
        }

        .excel-header {
            padding: 20px 20px 10px;
            text-align: center;
            border-bottom: none;
            position: relative;
        }

        .excel-header .logo-container {
            margin-bottom: 15px;
        }

        .excel-header .logo-container img {
            max-height: 80px;
        }

        .excel-header h2 {
            font-size: 22px;
            font-weight: bold;
            margin: 0 0 5px;
            text-transform: uppercase;
        }

        .excel-header .sub-header {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .excel-header .office-name {
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 5px;
        }

        .excel-header .office-label {
            font-size: 13px;
            font-weight: bold;
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
            min-width: 100px;
        }

        .meta-value {
            border-bottom: 1px solid #000;
            min-width: 150px;
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
            text-transform: capitalize;
        }

        .total-row {
            font-weight: bold;
            background: #fff;
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
            font-style: italic;
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
        }

        .sig-line {
            width: 80%;
            margin: 0 auto 5px;
            border-bottom: 1px solid #000;
        }

        .sig-sub {
            font-size: 11px;
            margin-bottom: 15px;
        }

        .sig-date-line {
            width: 60%;
            margin: 15px auto 0;
            border-bottom: 1px solid #000;
            text-align: center;
            font-size: 12px;
            min-height: 18px;
        }

        .sig-date-label {
            font-size: 11px;
            margin-top: 2px;
        }

        @media print {
            .no-print, .sidebar, .topbar { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .excel-card { margin: 0; border: 2px solid #000; width: 100%; max-width: none; }
            .main-content { padding: 0; margin: 0; }
            .main-wrapper { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>
    <?php $page_title = 'Property Acknowledgment Receipt View'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php include 'includes/sidebar-toggle.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1 class="mb-1"><i class="bi bi-file-earmark-medical-fill me-2 text-primary"></i>PAR View</h1>
                    <p class="text-muted mb-0">Official Property Acknowledgment Receipt format</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="btn-group shadow-sm">
                        <a href="print_par.php?id=<?php echo $par_id; ?>" target="_blank" class="btn btn-primary">
                            <i class="bi bi-printer-fill me-1"></i> Print
                        </a>
                        <a href="par_form.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> New
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="excel-card">
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

            <table class="excel-grid-table">
                <thead>
                    <tr>
                        <th width="40">Qty.</th>
                        <th width="60">Unit</th>
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
                            <td style="padding-left: 10px;"><?php echo htmlspecialchars($item['description']); ?></td>
                            <td align="center"><?php echo htmlspecialchars($item['property_number']); ?></td>
                            <td align="center"><?php echo $item['date_acquired'] ? date('m/d/Y', strtotime($item['date_acquired'])) : ''; ?></td>
                            <td align="right">₱<?php echo number_format($item['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php 
                    // Fill remaining rows to maintain consistent height (approx 20 rows total)
                    $remaining = 20 - count($par_items);
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
                        <td colspan="1" style="border-right: none;">&nbsp;</td>
                        <td align="right">₱<?php echo number_format(array_sum(array_column($par_items, 'amount')), 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="signature-section">
                <div class="sig-box">
                    <div class="sig-label">Received by:</div>
                    <div class="sig-content">
                        <div class="sig-name"><?php echo htmlspecialchars($par_form['received_by_name']); ?></div>
                        <div class="sig-line"></div>
                        <div class="sig-sub">Signature over Printed Name</div>
                        
                        <div class="sig-name" style="margin-top: 15px; text-transform: none; font-weight: bold;"><?php echo htmlspecialchars($par_form['received_by_position']); ?></div>
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
                        
                        <div class="sig-name" style="margin-top: 15px; text-transform: none; font-weight: bold;"><?php echo htmlspecialchars($par_form['issued_by_position']); ?></div>
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
    </div>

    <?php include 'includes/sidebar-scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
