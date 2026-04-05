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

// Get ITR form details with employee information
$itr_form = null;
$stmt = $conn->prepare("
    SELECT i.*, 
           from_emp.firstname as from_firstname, 
           from_emp.lastname as from_lastname, 
           from_emp.position as from_position,
           to_emp.firstname as to_firstname, 
           to_emp.lastname as to_lastname, 
           to_emp.position as to_position
    FROM itr_forms i 
    LEFT JOIN employees from_emp ON i.from_office = from_emp.id
    LEFT JOIN employees to_emp ON i.to_office = to_emp.id
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

// Get ITR items
$itr_items = [];
$stmt = $conn->prepare("SELECT * FROM itr_items WHERE form_id = ? ORDER BY id");
$stmt->bind_param("i", $itr_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $itr_items[] = $row;
}
$stmt->close();

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
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, var(--light-accent) 100%);
            min-height: 100vh;
        }
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        .excel-card {
            background: white;
            border: 2px solid #333;
            padding: 0;
            margin: 0 auto;
            max-width: 950px;
            box-shadow: var(--shadow-lg);
            border-radius: 4px;
            overflow: hidden;
        }
        .excel-header {
            padding: 30px;
            border-bottom: 1px solid #333;
            text-align: center;
            background: #fff;
        }
        .excel-header h4 {
            color: #1a1a1a;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .excel-header .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 0;
        }
        .excel-grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        .excel-grid-table th, .excel-grid-table td {
            border: 1px solid #333;
            padding: 12px 15px;
            font-size: 13px;
        }
        .excel-grid-table th {
            background: #f8f9fa;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            color: #333;
        }
        .info-row {
            display: flex;
            border-bottom: 1px solid #333;
            background: #fff;
        }
        .info-cell {
            flex: 1;
            padding: 15px 20px;
            border-right: 1px solid #333;
        }
        .info-cell:last-child {
            border-right: none;
        }
        .info-label {
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            color: var(--secondary-color);
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-weight: 600;
            font-size: 15px;
            color: #1a1a1a;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        .signature-table td {
            border: 1px solid #333;
            padding: 25px;
            width: 50%;
            vertical-align: top;
        }
        .sig-label {
            font-weight: 700;
            margin-bottom: 40px;
            font-size: 14px;
            color: #333;
        }
        .sig-name {
            text-align: center;
            font-weight: 800;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            margin-bottom: 3px;
            font-size: 14px;
            padding-bottom: 2px;
        }
        .sig-sub {
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .main-content {
            padding: 2rem;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media print {
            .no-print { display: none !important; }
            .excel-card { box-shadow: none; border: 2px solid #000; margin: 0; max-width: 100%; }
            body { background: white; }
            .main-content { padding: 0; }
        }
    </style>
</head>
<body>
    <?php $page_title = 'ITR View'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-arrow-left-right"></i> ITR View
                    </h1>
                    <p class="text-muted mb-0">
                        ITR Number: <strong><?php echo htmlspecialchars($itr_form['itr_no']); ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-md-end no-print">
                    <button class="btn btn-primary me-2" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="itr_entries.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="excel-card">
            <!-- Header Section -->
            <div class="excel-header">
                <?php if (!empty($header_image)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($header_image); ?>" alt="Header" style="max-height: 80px;" class="mb-3">
                <?php endif; ?>
                <h4 class="fw-bold mb-1">INVENTORY TRANSFER REQUEST</h4>
                <div class="text-uppercase" style="letter-spacing: 1px; font-size: 13px;">
                    <?php echo htmlspecialchars($itr_form['entity_name']); ?>
                </div>
            </div>

            <!-- Information Section -->
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Entity Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($itr_form['entity_name']); ?></div>
                </div>
                <div class="info-cell" style="max-width: 250px;">
                    <div class="info-label">ITR Number</div>
                    <div class="info-value text-primary"><?php echo htmlspecialchars($itr_form['itr_no']); ?></div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Fund Cluster</div>
                    <div class="info-value"><?php echo htmlspecialchars($itr_form['fund_cluster']); ?></div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">From (Accountable Officer)</div>
                    <div class="info-value"><?php echo htmlspecialchars($itr_form['from_firstname'] . ' ' . $itr_form['from_lastname']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">To (Accountable Officer)</div>
                    <div class="info-value"><?php echo htmlspecialchars($itr_form['to_firstname'] . ' ' . $itr_form['to_lastname']); ?></div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Transfer Date</div>
                    <div class="info-value"><?php echo date('F d, Y', strtotime($itr_form['transfer_date'])); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Transfer Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($itr_form['transfer_type']); ?></div>
                </div>
            </div>

            <?php if (!empty($itr_form['end_user'])): ?>
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">End User</div>
                    <div class="info-value"><?php echo htmlspecialchars($itr_form['end_user']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Purpose</div>
                    <div class="info-value"><?php echo htmlspecialchars($itr_form['purpose']); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Items Table -->
            <table class="excel-grid-table">
                <thead>
                    <tr>
                        <th width="80">Item No.</th>
                        <th width="120">Date Acquired</th>
                        <th width="120">ICS/PAR No.</th>
                        <th>Description</th>
                        <th width="120">Unit Price</th>
                        <th width="120">Total Amount</th>
                        <th width="100">Condition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_amount = 0;
                    foreach ($itr_items as $index => $item): 
                        $total_amount += $item['total_amount'];
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td class="text-center"><?php echo date('M d, Y', strtotime($item['date_acquired'])); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($item['ics_par_no']); ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td class="text-end">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end">₱<?php echo number_format($item['total_amount'], 2); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($item['condition_of_inventory']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($itr_items)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">No items found</td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (count($itr_items) < 5): ?>
                        <?php for($i=0; $i<(5-count($itr_items)); $i++): ?>
                            <tr>
                                <td colspan="7" style="height: 35px; background: #fff;">
                                    <?php if($i === 0) echo '<div class="text-center text-muted fst-italic">*** Nothing follows ***</div>'; ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end py-3">TOTAL AMOUNT</td>
                        <td class="text-end px-3">₱<?php echo number_format($total_amount, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Signature Section -->
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="sig-label">Requested By:</div>
                        <div class="mt-4">
                            <div class="sig-name"><?php echo htmlspecialchars($itr_form['requested_by']); ?></div>
                            <div class="sig-sub">Signature Over Printed Name</div>
                            <div class="sig-name mt-3" style="font-weight: 500; text-transform: none;"><?php echo htmlspecialchars($itr_form['requested_by_position']); ?></div>
                            <div class="sig-sub">Position / Office</div>
                            <div class="sig-name mt-3" style="font-weight: 500; font-size: 13px;">
                                <?php echo ($itr_form['requested_date'] && $itr_form['requested_date'] != '0000-00-00') ? date('F d, Y', strtotime($itr_form['requested_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="sig-sub">Date</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-label">Approved By:</div>
                        <div class="mt-4">
                            <div class="sig-name"><?php echo htmlspecialchars($itr_form['approved_by']); ?></div>
                            <div class="sig-sub">Signature Over Printed Name</div>
                            <div class="sig-name mt-3" style="font-weight: 500; text-transform: none;"><?php echo htmlspecialchars($itr_form['approved_by_position']); ?></div>
                            <div class="sig-sub">Position / Office</div>
                            <div class="sig-name mt-3" style="font-weight: 500; font-size: 13px;">
                                <?php echo ($itr_form['approved_date'] && $itr_form['approved_date'] != '0000-00-00') ? date('F d, Y', strtotime($itr_form['approved_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="sig-sub">Date</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="sig-label">Released By:</div>
                        <div class="mt-4">
                            <div class="sig-name"><?php echo htmlspecialchars($itr_form['released_by']); ?></div>
                            <div class="sig-sub">Signature Over Printed Name</div>
                            <div class="sig-name mt-3" style="font-weight: 500; text-transform: none;"><?php echo htmlspecialchars($itr_form['released_by_position']); ?></div>
                            <div class="sig-sub">Position / Office</div>
                            <div class="sig-name mt-3" style="font-weight: 500; font-size: 13px;">
                                <?php echo ($itr_form['released_date'] && $itr_form['released_date'] != '0000-00-00') ? date('F d, Y', strtotime($itr_form['released_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="sig-sub">Date</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-label">Received By:</div>
                        <div class="mt-4">
                            <div class="sig-name"><?php echo htmlspecialchars($itr_form['received_by']); ?></div>
                            <div class="sig-sub">Signature Over Printed Name</div>
                            <div class="sig-name mt-3" style="font-weight: 500; text-transform: none;"><?php echo htmlspecialchars($itr_form['received_by_position']); ?></div>
                            <div class="sig-sub">Position / Office</div>
                            <div class="sig-name mt-3" style="font-weight: 500; font-size: 13px;">
                                <?php echo ($itr_form['received_date'] && $itr_form['received_date'] != '0000-00-00') ? date('F d, Y', strtotime($itr_form['received_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="sig-sub">Date</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    </div>
    
    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
