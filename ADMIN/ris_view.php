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
            font-family: 'DejaVu Sans', sans-serif;
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
            padding: 15px 0;
            border-bottom: 1px solid #333;
            text-align: center;
            background: #fff;
        }
        
        .excel-header h4 {
            color: #000;
            font-weight: bold;
            margin-bottom: 0;
            font-size: 16px;
            font-family: 'DejaVu Sans', sans-serif;
        }
        
        .header-img {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .header-img img {
            max-width: 100%;
            height: auto;
        }
        
        .meta {
            margin: 10px 0;
            font-size: 12px;
        }
        
        .meta table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
        }
        
        .meta td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 11px;
            text-align: left;
        }
        
        .items-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        
        .items-table th, 
        .items-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 11px;
            font-family: 'DejaVu Sans', sans-serif;
            vertical-align: middle;
        }
        
        .items-table th {
            font-weight: bold;
            background: #f2f2f2;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        
        .items-table .text-left {
            text-align: left;
        }
        
        .grand-total {
            font-weight: bold;
            color: red;
            border-top: 1px solid #000;
        }
        
        .footer-table {
            border: 1px solid #000;
            border-collapse: collapse;
            margin-top: 10px;
            width: 100%;
        }
        
        .footer-table th, 
        .footer-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 11px;
            text-align: center;
            font-family: 'DejaVu Sans', sans-serif;
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
            .meta td { padding: 2px 3px; }
            .items-table th, .items-table td { padding: 3px 4px; font-size: 10px; }
            .footer-table th, .footer-table td { padding: 3px 4px; font-size: 10px; }
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
                    <div class="dropdown d-inline-block me-2">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li><a class="dropdown-item" href="#" onclick="window.open('print_ris.php?id=<?php echo $ris_id; ?>', '_blank')">
                                <i class="bi bi-printer text-info"></i> Print RIS
                            </a></li>
                            <li><a class="dropdown-item" href="ris_entries.php">
                                <i class="bi bi-arrow-left text-secondary"></i> Back to Entries
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="ris_form.php">
                                <i class="bi bi-plus-circle text-success"></i> New RIS
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIS Form -->
        <div class="excel-card">
            <!-- Form Header -->
            <div class="excel-header">
                <?php 
                if (!empty($header_image)) {
                    echo '<div class="header-img">';
                    echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image">';
                    echo '</div>';
                }
                ?>
                <h4>REQUISITION AND ISSUE SLIP</h4>
            </div>
            
            <!-- Meta Information -->
            <div class="meta">
                <table>
                    <tr>
                        <td><strong>DIVISION:</strong> <?php echo htmlspecialchars($ris_form['division']); ?></td>
                        <td><strong>Responsibility Center:</strong> <?php echo htmlspecialchars($ris_form['responsibility_center']); ?></td>
                        <td><strong>RIS NO:</strong> <?php echo htmlspecialchars($ris_form['ris_no']); ?></td>
                        <td><strong>DATE:</strong> 
                            <?php if (!empty($ris_form['date']) && $ris_form['date'] !== '0000-00-00' && $ris_form['date'] !== null): ?>
                                <?php echo date('F d, Y', strtotime($ris_form['date'])); ?>
                            <?php else: ?>
                                <span style="color: #999;">No date set</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>OFFICE:</strong> <?php echo htmlspecialchars($ris_form['office']); ?></td>
                        <td><strong>Code:</strong> <?php echo htmlspecialchars($ris_form['code']); ?></td>
                        <td><strong>SAI NO:</strong> <?php echo htmlspecialchars($ris_form['sai_no']); ?></td>
                        <td></td>
                    </tr>
                </table>
            </div>
            
            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th colspan="4">REQUISITION</th>
                        <th colspan="3">ISSUANCE</th>
                    </tr>
                    <tr>
                        <th>Stock No</th>
                        <th>Unit</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Signature</th>
                        <th>Price</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ris_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['stock_no']); ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td class="text-left"><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo number_format($item['quantity'], 2); ?></td>
                            <td></td>
                            <td><?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo number_format($item['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($ris_items)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; font-style: italic; padding: 6px 0; border-top: 1px solid #000;">— NOTHING FOLLOWS —</td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php 
                    // Fill blank rows to match generate_ris_pdf.php format
                    $minRows = 15;
                    $currentRows = count($ris_items);
                    $emptyRows = max(0, $minRows - $currentRows);
                    
                    for ($i = 0; $i < $emptyRows; $i++) {
                        echo '<tr>';
                        echo '<td>&nbsp;</td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '</tr>';
                    }
                    ?>
                    
                    <?php $grandTotal = array_sum(array_column($ris_items, 'total_amount')); ?>
                    <tr>
                        <td colspan="6" style="text-align:right; border-top:1px solid #000;"><strong>Grand Total:</strong></td>
                        <td class="grand-total"><?php echo number_format($grandTotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="7" style="text-align:left; border-top:1px solid #000;"><strong>Purpose:</strong> <?php echo htmlspecialchars($ris_form['purpose']); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Footer Signatures -->
            <table class="footer-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Requested By</th>
                        <th>Approved By</th>
                        <th>Issued By</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Signature</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Printed Name</td>
                        <td><?php echo htmlspecialchars($ris_form['requested_by']); ?></td>
                        <td><?php echo htmlspecialchars($ris_form['approved_by']); ?></td>
                        <td><?php echo htmlspecialchars($ris_form['issued_by']); ?></td>
                        <td><?php echo htmlspecialchars($ris_form['received_by']); ?></td>
                    </tr>
                    <tr>
                        <td>Designation</td>
                        <td><?php echo htmlspecialchars($ris_form['requested_by_position']); ?></td>
                        <td><?php echo htmlspecialchars($ris_form['approved_by_position']); ?></td>
                        <td><?php echo htmlspecialchars($ris_form['issued_by_position']); ?></td>
                        <td><?php echo htmlspecialchars($ris_form['received_by_position']); ?></td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td><?php if (!empty($ris_form['requested_date']) && $ris_form['requested_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris_form['requested_date'])); ?></td>
                        <td><?php if (!empty($ris_form['approved_date']) && $ris_form['approved_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris_form['approved_date'])); ?></td>
                        <td><?php if (!empty($ris_form['issued_date']) && $ris_form['issued_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris_form['issued_date'])); ?></td>
                        <td><?php if (!empty($ris_form['received_date']) && $ris_form['received_date'] !== '0000-00-00') echo date('m/d/Y', strtotime($ris_form['received_date'])); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(risId, risNo) {
            if (confirm('Are you sure you want to delete RIS No: ' + risNo + '?\n\nThis action cannot be undone and will permanently delete this RIS form and all its items.')) {
                // Create and submit a form for deletion
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process_ris.php';
                form.style.display = 'none';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = 'csrf_token';
                csrfToken.value = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
                form.appendChild(csrfToken);
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = risId;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
