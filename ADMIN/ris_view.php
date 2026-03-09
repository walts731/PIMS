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
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
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
        
        .form-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .ris-number {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .table-bordered {
            border: 1px solid #dee2e6;
        }
        
        .signature-section {
            border-top: 2px solid #dee2e6;
            padding-top: 2rem;
            margin-top: 2rem;
        }
        
        @media print {
            .no-print { display: none !important; }
            .form-card { box-shadow: none; }
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
        <div class="form-card">
            <!-- Form Header -->
            <div style="text-align: center; margin-bottom: 20px;">
                <?php 
                if (!empty($header_image)) {
                    echo '<div style="margin-bottom: 10px;">';
                    echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 120px; object-fit: contain;">';
                    echo '</div>';
                }
                ?>
                <div style="text-align: center;">
                    <p style="margin: 0; font-size: 16px; font-weight: bold;">REQUISITION AND ISSUE SLIP</p>
                </div>
            </div>
            
            <!-- Entity Information Header -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><strong>DIVISION:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ris_form['division']); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Responsibility Center:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ris_form['responsibility_center']); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>RIS NO:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ris_form['ris_no']); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>DATE:</strong></label>
                    <p class="form-control-plaintext"><?php echo date('F d, Y', strtotime($ris_form['date'])); ?></p>
                </div>
            </div>
            
            <!-- Entity Information Values -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><strong>OFFICE:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ris_form['office']); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Code:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ris_form['code']); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>SAI NO.:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ris_form['sai_no']); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Date:</strong></label>
                    <p class="form-control-plaintext"><?php echo date('F d, Y', strtotime($ris_form['date_2'])); ?></p>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="mb-4">
                <h5 class="mb-3"><strong>Items:</strong></h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Stock No.</th>
                                <th>Unit</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ris_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['stock_no']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td><?php echo number_format($item['quantity'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['total_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="5" class="text-end">Total:</th>
                                <th>₱<?php echo number_format(array_sum(array_column($ris_items, 'total_amount')), 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Purpose -->
            <div class="row mb-4">
                <div class="col-12">
                    <label class="form-label"><strong>Purpose:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ris_form['purpose']); ?></p>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p class="text-muted fst-italic">Nothing follows</p>
            </div>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <div class="row">
                    <div class="col-md-3">
                        <div class="border p-3 text-center">
                            <label class="form-label"><strong>REQUESTED BY:</strong></label>
                            <div class="mb-3">
                                <small class="text-muted">SIGNATURE:</small>
                                <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">PRINTED NAME:</small>
                                <p class="mb-1"><?php echo htmlspecialchars($ris_form['requested_by']); ?></p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">DESIGNATION:</small>
                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($ris_form['requested_by_position']); ?></p>
                            </div>
                            <div>
                                <small class="text-muted">DATE:</small>
                                <?php if (!empty($ris_form['requested_date']) && $ris_form['requested_date'] !== '0000-00-00'): ?>
                                    <p class="mb-1"><?php echo date('F d, Y', strtotime($ris_form['requested_date'])); ?></p>
                                <?php else: ?>
                                    <p class="mb-1">______________</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border p-3 text-center">
                            <label class="form-label"><strong>APPROVED BY:</strong></label>
                            <div class="mb-3">
                                <small class="text-muted">SIGNATURE:</small>
                                <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">PRINTED NAME:</small>
                                <p class="mb-1"><?php echo htmlspecialchars($ris_form['approved_by']); ?></p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">DESIGNATION:</small>
                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($ris_form['approved_by_position']); ?></p>
                            </div>
                            <div>
                                <small class="text-muted">DATE:</small>
                                <?php if (!empty($ris_form['approved_date']) && $ris_form['approved_date'] !== '0000-00-00'): ?>
                                    <p class="mb-1"><?php echo date('F d, Y', strtotime($ris_form['approved_date'])); ?></p>
                                <?php else: ?>
                                    <p class="mb-1">______________</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border p-3 text-center">
                            <label class="form-label"><strong>ISSUED BY:</strong></label>
                            <div class="mb-3">
                                <small class="text-muted">SIGNATURE:</small>
                                <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">PRINTED NAME:</small>
                                <p class="mb-1"><?php echo htmlspecialchars($ris_form['issued_by']); ?></p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">DESIGNATION:</small>
                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($ris_form['issued_by_position']); ?></p>
                            </div>
                            <div>
                                <small class="text-muted">DATE:</small>
                                <?php if (!empty($ris_form['issued_date']) && $ris_form['issued_date'] !== '0000-00-00'): ?>
                                    <p class="mb-1"><?php echo date('F d, Y', strtotime($ris_form['issued_date'])); ?></p>
                                <?php else: ?>
                                    <p class="mb-1">______________</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border p-3 text-center">
                            <label class="form-label"><strong>RECEIVED BY:</strong></label>
                            <div class="mb-3">
                                <small class="text-muted">SIGNATURE:</small>
                                <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">PRINTED NAME:</small>
                                <p class="mb-1"><?php echo htmlspecialchars($ris_form['received_by']); ?></p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">DESIGNATION:</small>
                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($ris_form['received_by_position']); ?></p>
                            </div>
                            <div>
                                <small class="text-muted">DATE:</small>
                                <?php if (!empty($ris_form['received_date']) && $ris_form['received_date'] !== '0000-00-00'): ?>
                                    <p class="mb-1"><?php echo date('F d, Y', strtotime($ris_form['received_date'])); ?></p>
                                <?php else: ?>
                                    <p class="mb-1">______________</p>
                                <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
