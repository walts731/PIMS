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
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, var(--light-accent) 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        .ics-card {
            background: white;
            border: 2px solid #333;
            padding: 0;
            margin: 0 auto;
            max-width: 950px;
            box-shadow: var(--shadow-lg);
            border-radius: 4px;
            overflow: hidden;
        }
        .ics-header {
            padding: 30px;
            border-bottom: 1px solid #333;
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
        .ics-header .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
            margin-top: 15px;
        }
        .ics-annex {
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
        .ics-no-section {
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
        
        .quantity-col { width: 70px; }
        .unit-col { width: 60px; }
        .unit-cost-col { width: 100px; }
        .total-cost-col { width: 110px; }
        .item-no-col { width: 120px; }
        .useful-life-col { width: 120px; }
        
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
            width: 50%;
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
            padding: 2rem;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media print {
            .no-print { display: none !important; }
            .ics-card { box-shadow: none; border: 2px solid #000; margin: 0; max-width: 100%; }
            body { background: white; }
            .main-content { padding: 0; }
            .page-header { display: none !important; }
            .sidebar-toggle, .sidebar { display: none !important; }
        }
    </style>
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
        <!-- Page Header -->
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-file-earmark-text"></i> ICS View
                    </h1>
                    <p class="text-muted mb-0">View Inventory Custodian Slip details</p>
                </div>
                <div class="col-md-4 text-md-end no-print">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="window.open('print_ics.php?id=<?php echo $ics_id; ?>', '_blank')">
                                    <i class="bi bi-file-earmark-pdf me-2"></i> Official Printout
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="ics_entries.php">
                                    <i class="bi bi-list me-2"></i> View All Entries
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="ics_form.php">
                                    <i class="bi bi-plus-circle me-2"></i> New ICS Slip
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ICS Form -->
        <div class="ics-card">
            <!-- Header Section -->
            <div class="ics-header">
                <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?> Logo" class="seal-img">
                <div class="header-content">
                    <div class="header-text">
                        <p>Republic of the Philippines</p>
                        <h3>Municipality of Pilar</h3>
                        <p>Province of Sorsogon</p>
                        <h2 class="title">INVENTORY CUSTODIAN SLIP</h2>
                    </div>
                </div>
                <div class="header-right">
                    <div class="ics-annex">
                        <p>Annex A.1</p>
                    </div>
                </div>
            </div>

            
            <!-- Entity Information -->
            <div style="padding: 20px;">
                <div class="entity-section" style="border-bottom: none;">
                    <div class="entity-row">
                        <div class="entity-label">Entity Name:</div>
                        <div class="entity-value"><?php echo htmlspecialchars($ics_form['entity_name']); ?></div>
                        <div class="entity-label">Fund Cluster:</div>
                        <div class="entity-value"><?php echo htmlspecialchars($ics_form['fund_cluster']); ?></div>
                    </div>
                </div>
                
                <!-- ICS Information -->
                <div class="entity-section">
                    <div class="entity-row">
                        <div class="entity-label">ICS No:</div>
                        <div class="entity-value" style="font-weight: bold;"><?php echo htmlspecialchars($ics_form['ics_no']); ?></div>
                    </div>
                </div>

                
                <!-- Items Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="quantity-col">Quantity</th>
                            <th class="unit-col">Unit</th>
                            <th class="unit-cost-col">Unit Cost</th>
                            <th class="total-cost-col">Total Cost</th>
                            <th class="text-left">Description</th>
                            <th class="item-no-col">Item No.</th>
                            <th class="useful-life-col">Useful Life</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_amount = 0;
                        foreach ($ics_items as $index => $item): 
                            $total_amount += $item['total_cost'];
                        ?>
                            <tr>
                                <td><?php echo number_format($item['quantity'], 0); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td class="text-right"><?php echo number_format($item['unit_cost'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($item['total_cost'], 2); ?></td>
                                <td class="text-left"><?php echo htmlspecialchars($item['description']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_no']); ?></td>
                                <td><?php echo htmlspecialchars($item['useful_life']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php 
                        // Add empty rows to maintain form height
                        $total_items = count($ics_items);
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
                            <td colspan="3">TOTAL AMOUNT:</td>
                            <td class="text-right"><?php echo number_format($total_amount, 2); ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
                
                <!-- Footer / Signatures Section -->
                <div class="footer-section">
                    <table class="footer-table">
                        <tr>
                            <td>
                                <div class="label-row">Received from:</div>
                                <div class="signature-group">
                                    <div class="name-line"><?php echo htmlspecialchars($ics_form['received_from']); ?></div>
                                    <div class="sub-label">Signature Over Printed Name</div>
                                    <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($ics_form['received_from_position']); ?></div>
                                    <div class="sub-label">Position / Office</div>
                                    <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo (!empty($ics_form['received_from_date']) && $ics_form['received_from_date'] !== '0000-00-00') ? date('F d, Y', strtotime($ics_form['received_from_date'])) : ''; ?></div>
                                    <div class="sub-label">Date</div>
                                </div>
                            </td>
                            <td>
                                <div class="label-row">Received by:</div>
                                <div class="signature-group">
                                    <div class="name-line"><?php echo htmlspecialchars($ics_form['received_by']); ?></div>
                                    <div class="sub-label">Signature Over Printed Name</div>
                                    <div class="name-line" style="font-weight: normal; text-transform: none;"><?php echo htmlspecialchars($ics_form['received_by_position']); ?></div>
                                    <div class="sub-label">Position / Office</div>
                                    <div class="name-line" style="font-weight: normal; margin-top: 10px;"><?php echo (!empty($ics_form['received_by_date']) && $ics_form['received_by_date'] !== '0000-00-00') ? date('F d, Y', strtotime($ics_form['received_by_date'])) : ''; ?></div>
                                    <div class="sub-label">Date</div>
                                </div>
                            </td>
                        </tr>
                    </table>
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
