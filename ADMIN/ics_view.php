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
        
        .ics-number {
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
                    <a href="ics_entries.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> Back to Entries
                    </a>
                    <button class="btn btn-outline-info btn-sm me-2" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="ics_form.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> New ICS
                    </a>
                </div>
            </div>
        </div>

        <!-- ICS Form -->
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
                    <p style="margin: 0; font-size: 16px; font-weight: bold;">INVENTORY CUSTODIAN SLIP</p>
                </div>
            </div>
            
            <!-- Entity Information -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Entity Name:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ics_form['entity_name']); ?></p>
                    <label class="form-label"><strong>Fund Cluster:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ics_form['fund_cluster']); ?></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><strong>ICS Number:</strong></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($ics_form['ics_no']); ?></p>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="mb-4">
                <h5 class="mb-3"><strong>Items:</strong></h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Item No.</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                                <th>Useful Life</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ics_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_no']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td><?php echo number_format($item['quantity'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['total_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['useful_life']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="5" class="text-end">Total:</th>
                                <th>₱<?php echo number_format(array_sum(array_column($ics_items, 'total_cost')), 2); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <p class="text-muted fst-italic">Nothing follows</p>
                </div>
            </div>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <div class="row">
                    <div class="col-md-6">
                        <h6><strong>Received From:</strong></h6>
                        <p><?php echo htmlspecialchars($ics_form['received_from']); ?></p>
                        <p class="text-muted"><?php echo htmlspecialchars($ics_form['received_from_position']); ?></p>
                        <?php if (!empty($ics_form['received_from_date']) && $ics_form['received_from_date'] !== '0000-00-00'): ?>
                            <p class="text-muted">Date: <?php echo date('F d, Y', strtotime($ics_form['received_from_date'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Date: ______________</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6><strong>Received By:</strong></h6>
                        <p><?php echo htmlspecialchars($ics_form['received_by']); ?></p>
                        <p class="text-muted"><?php echo htmlspecialchars($ics_form['received_by_position']); ?></p>
                        <?php if (!empty($ics_form['received_by_date']) && $ics_form['received_by_date'] !== '0000-00-00'): ?>
                            <p class="text-muted">Date: <?php echo date('F d, Y', strtotime($ics_form['received_by_date'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Date: ______________</p>
                        <?php endif; ?>
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
