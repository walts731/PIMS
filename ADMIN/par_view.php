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
                    <p class="text-muted mb-0">Detailed Property Acknowledgment Receipt record</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="dropdown d-inline-block shadow-sm">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear-fill me-1"></i> Form Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <a class="dropdown-item py-2" href="print_par.php?id=<?php echo $par_id; ?>" target="_blank">
                                    <i class="bi bi-printer-fill me-2 text-primary"></i> Print PAR Form
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2" href="par_form.php">
                                    <i class="bi bi-plus-circle-fill me-2 text-success"></i> Create New PAR
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="excel-card">
            <div class="excel-header">
                <?php if ($header_image): ?>
                    <img src="../uploads/forms/<?php echo htmlspecialchars($header_image); ?>" style="max-height: 80px;" class="mb-3">
                <?php endif; ?>
                <h4 class="fw-bold mb-1">PROPERTY ACKNOWLEDGMENT RECEIPT</h4>
                <div class="text-uppercase" style="letter-spacing: 1px; font-size: 13px;">
                    <?php echo htmlspecialchars($par_form['office_name'] ?: $par_form['office_location']); ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Entity Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($par_form['entity_name']); ?></div>
                </div>
                <div class="info-cell" style="max-width: 250px;">
                    <div class="info-label">PAR Number</div>
                    <div class="info-value text-primary"><?php echo htmlspecialchars($par_form['par_no']); ?></div>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Fund Cluster</div>
                    <div class="info-value"><?php echo htmlspecialchars($par_form['fund_cluster']); ?></div>
                </div>
            </div>

            <table class="excel-grid-table">
                <thead>
                    <tr>
                        <th width="100">Quantity</th>
                        <th width="100">Unit</th>
                        <th>Description</th>
                        <th width="150">Property No.</th>
                        <th width="120">Date Acquired</th>
                        <th width="120">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($par_items as $item): ?>
                        <tr>
                            <td class="text-center"><?php echo number_format($item['quantity'], 0); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($item['property_number']); ?></td>
                            <td class="text-center"><?php echo $item['date_acquired'] ? date('M d, Y', strtotime($item['date_acquired'])) : '-'; ?></td>
                            <td class="text-end">₱<?php echo number_format($item['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($par_items) < 5): ?>
                        <?php for($i=0; $i<(5-count($par_items)); $i++): ?>
                            <tr>
                                <td colspan="6" style="height: 35px; background: #fff;">
                                    <?php if($i === 0) echo '<div class="text-center text-muted fst-italic">*** Nothing follows ***</div>'; ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end py-3">TOTAL AMOUNT</td>
                        <td class="text-end px-3">₱<?php echo number_format(array_sum(array_column($par_items, 'amount')), 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <table class="signature-table">
                <tr>
                    <td>
                        <div class="sig-label">Received by:</div>
                        <div class="mt-4">
                            <div class="sig-name"><?php echo htmlspecialchars($par_form['received_by_name']); ?></div>
                            <div class="sig-sub">Signature Over Printed Name</div>
                            <div class="sig-name mt-3" style="font-weight: 500; text-transform: none;"><?php echo htmlspecialchars($par_form['received_by_position']); ?></div>
                            <div class="sig-sub">Position / Office</div>
                            <div class="sig-name mt-3" style="font-weight: 500; font-size: 13px;">
                                <?php echo ($par_form['received_by_date'] && $par_form['received_by_date'] != '0000-00-00') ? date('F d, Y', strtotime($par_form['received_by_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="sig-sub">Date</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-label">Issued by:</div>
                        <div class="mt-4">
                            <div class="sig-name"><?php echo htmlspecialchars($par_form['issued_by_name']); ?></div>
                            <div class="sig-sub">Signature Over Printed Name</div>
                            <div class="sig-name mt-3" style="font-weight: 500; text-transform: none;"><?php echo htmlspecialchars($par_form['issued_by_position']); ?></div>
                            <div class="sig-sub">Position / Office</div>
                            <div class="sig-name mt-3" style="font-weight: 500; font-size: 13px;">
                                <?php echo ($par_form['issued_by_date'] && $par_form['issued_by_date'] != '0000-00-00') ? date('F d, Y', strtotime($par_form['issued_by_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="sig-sub">Date</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <?php include 'includes/sidebar-scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
