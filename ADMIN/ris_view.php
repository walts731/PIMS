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

// Get RIS ID from URL
$ris_id = $_GET['id'] ?? 0;
if (empty($ris_id)) {
    header('Location: ris_entries.php');
    exit();
}

// Get RIS form details
$ris_form = null;
$stmt = $conn->prepare("SELECT * FROM ris_forms WHERE id = ?");
$stmt->bind_param("i", $ris_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $ris_form = $result->fetch_assoc();
}
$stmt->close();

if (!$ris_form) {
    header('Location: ris_entries.php');
    exit();
}

// Get RIS items
$ris_items = [];
$stmt = $conn->prepare("SELECT * FROM ris_items WHERE ris_form_id = ? ORDER BY id");
$stmt->bind_param("i", $ris_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ris_items[] = $row;
}
$stmt->close();

logSystemAction($_SESSION['user_id'], 'Viewed RIS Form', 'forms', "RIS ID: $ris_id, RIS No: {$ris_form['ris_no']}");

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'RIS'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIS View - <?php echo htmlspecialchars($ris_form['ris_no']); ?> - PIMS</title>
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
            font-weight: bold;
            margin-bottom: 0;
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
        
        .excel-grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .excel-grid-table th, .excel-grid-table td {
            border: 1px solid #333;
            padding: 12px 15px;
            font-size: 13px;
            vertical-align: middle;
        }
        
        .excel-grid-table th {
            background: #f8f9fa;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            color: #333;
        }
        
        .purpose-section {
            padding: 20px;
            border-bottom: 1px solid #333;
        }
        
        .purpose-label {
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            color: var(--secondary-color);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .purpose-text {
            font-weight: 500;
            font-size: 14px;
            color: #1a1a1a;
            line-height: 1.5;
        }
        
        .nothing-follows {
            text-align: center;
            font-style: italic;
            padding: 20px;
            color: #666;
            font-size: 13px;
        }
        
        .signature-section {
            background: #fff;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signature-table td {
            border: 1px solid #333;
            padding: 25px 20px;
            vertical-align: top;
            width: 25%;
        }
        
        .sig-label {
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 12px;
            color: #333;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .sig-line {
            border-bottom: 2px solid #333;
            height: 40px;
            margin-bottom: 8px;
        }
        
        .sig-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
            text-align: center;
        }
        
        .sig-name {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            font-size: 13px;
            padding-bottom: 2px;
            min-height: 20px;
        }
        
        .sig-position {
            text-align: center;
            font-size: 11px;
            font-weight: 500;
            color: #555;
            font-style: italic;
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
            min-height: 18px;
            padding-bottom: 2px;
        }
        
        .sig-date {
            text-align: center;
            font-size: 12px;
            border-bottom: 1px solid #333;
            min-height: 20px;
            padding-bottom: 2px;
            font-weight: 500;
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
            .page-header { display: none !important; }
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'RIS View - ' . htmlspecialchars($ris_form['ris_no']);
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
                        <i class="bi bi-file-earmark-text"></i> RIS View
                    </h1>
                    <p class="text-muted mb-0">View Requisition and Issue Slip details</p>
                </div>
                <div class="col-md-4 text-md-end no-print">
                    <a href="ris_entries.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> Back to Entries
                    </a>
                    <button class="btn btn-outline-info btn-sm me-2" onclick="window.open('print_ris.php?id=<?php echo $ris_id; ?>', '_blank')">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="ris_form.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> New RIS
                    </a>
                </div>
            </div>
        </div>

        <!-- RIS Form -->
        <div class="excel-card">
            <!-- Form Header -->
            <div class="excel-header">
                <?php 
                if (!empty($header_image)) {
                    echo '<div style="margin-bottom: 15px;">';
                    echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 80px; object-fit: contain;">';
                    echo '</div>';
                }
                ?>
                <h4>REQUISITION AND ISSUE SLIP</h4>
            </div>
            
            <!-- Entity Information Header -->
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Division</div>
                    <div class="info-value"><?php echo htmlspecialchars($ris_form['division']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Responsibility Center</div>
                    <div class="info-value"><?php echo htmlspecialchars($ris_form['responsibility_center']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">RIS No</div>
                    <div class="info-value text-primary"><?php echo htmlspecialchars($ris_form['ris_no']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Date</div>
                    <div class="info-value">
                        <?php if (!empty($ris_form['date']) && $ris_form['date'] !== '0000-00-00' && $ris_form['date'] !== null): ?>
                            <?php echo date('F d, Y', strtotime($ris_form['date'])); ?>
                        <?php else: ?>
                            <span style="color: #999;">No date set</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Entity Information Values -->
            <div class="info-row">
                <div class="info-cell">
                    <div class="info-label">Office</div>
                    <div class="info-value"><?php echo htmlspecialchars($ris_form['office']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Code</div>
                    <div class="info-value"><?php echo htmlspecialchars($ris_form['code']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">SAI No</div>
                    <div class="info-value"><?php echo htmlspecialchars($ris_form['sai_no']); ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Date</div>
                    <div class="info-value">
                        <?php if (!empty($ris_form['date_2']) && $ris_form['date_2'] !== '0000-00-00' && $ris_form['date_2'] !== null): ?>
                            <?php echo date('F d, Y', strtotime($ris_form['date_2'])); ?>
                        <?php else: ?>
                            <span style="color: #999;">No date set</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="excel-grid-table">
                <thead>
                    <tr>
                        <th width="10%">Stock No</th>
                        <th width="8%">Unit</th>
                        <th width="35%">Description</th>
                        <th width="8%">Quantity</th>
                        <th width="12%">Price</th>
                        <th width="12%">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ris_items as $item): ?>
                        <tr>
                            <td class="text-center"><?php echo htmlspecialchars($item['stock_no']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td class="text-center"><?php echo number_format($item['quantity'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($item['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end" style="font-weight: 700;">Total</th>
                        <th class="text-end" style="font-weight: 700;">₱<?php echo number_format(array_sum(array_column($ris_items, 'total_amount')), 2); ?></th>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Purpose -->
            <div class="purpose-section">
                <div class="purpose-label">Purpose</div>
                <div class="purpose-text"><?php echo htmlspecialchars($ris_form['purpose']); ?></div>
            </div>
            
            <div class="nothing-follows">
                Nothing follows
            </div>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="sig-label">Requested By</div>
                            <div class="sig-line"></div>
                            <div class="sig-info">Signature over Printed Name</div>
                            <div class="sig-name"><?php echo htmlspecialchars($ris_form['requested_by']); ?></div>
                            <div class="sig-info">Designation</div>
                            <div class="sig-position"><?php echo htmlspecialchars($ris_form['requested_by_position']); ?></div>
                            <div class="sig-info">Date</div>
                            <div class="sig-date">
                                <?php if (!empty($ris_form['requested_date']) && $ris_form['requested_date'] !== '0000-00-00'): ?>
                                    <?php echo date('m/d/Y', strtotime($ris_form['requested_date'])); ?>
                                <?php else: ?>
                                    ____________
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="sig-label">Approved By</div>
                            <div class="sig-line"></div>
                            <div class="sig-info">Signature over Printed Name</div>
                            <div class="sig-name"><?php echo htmlspecialchars($ris_form['approved_by']); ?></div>
                            <div class="sig-info">Designation</div>
                            <div class="sig-position"><?php echo htmlspecialchars($ris_form['approved_by_position']); ?></div>
                            <div class="sig-info">Date</div>
                            <div class="sig-date">
                                <?php if (!empty($ris_form['approved_date']) && $ris_form['approved_date'] !== '0000-00-00'): ?>
                                    <?php echo date('m/d/Y', strtotime($ris_form['approved_date'])); ?>
                                <?php else: ?>
                                    ____________
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="sig-label">Issued By</div>
                            <div class="sig-line"></div>
                            <div class="sig-info">Signature over Printed Name</div>
                            <div class="sig-name"><?php echo htmlspecialchars($ris_form['issued_by']); ?></div>
                            <div class="sig-info">Designation</div>
                            <div class="sig-position"><?php echo htmlspecialchars($ris_form['issued_by_position']); ?></div>
                            <div class="sig-info">Date</div>
                            <div class="sig-date">
                                <?php if (!empty($ris_form['issued_date']) && $ris_form['issued_date'] !== '0000-00-00'): ?>
                                    <?php echo date('m/d/Y', strtotime($ris_form['issued_date'])); ?>
                                <?php else: ?>
                                    ____________
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="sig-label">Received By</div>
                            <div class="sig-line"></div>
                            <div class="sig-info">Signature over Printed Name</div>
                            <div class="sig-name"><?php echo htmlspecialchars($ris_form['received_by']); ?></div>
                            <div class="sig-info">Designation</div>
                            <div class="sig-position"><?php echo htmlspecialchars($ris_form['received_by_position']); ?></div>
                            <div class="sig-info">Date</div>
                            <div class="sig-date">
                                <?php if (!empty($ris_form['received_date']) && $ris_form['received_date'] !== '0000-00-00'): ?>
                                    <?php echo date('m/d/Y', strtotime($ris_form['received_date'])); ?>
                                <?php else: ?>
                                    ____________
                                <?php endif; ?>
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
