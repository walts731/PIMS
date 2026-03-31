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
        
        .form-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .ics-number {
            background: var(--primary-gradient);
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
        <div class="form-card">
            <!-- Form Header -->
            <div class="text-center mb-4">
                <?php 
                if (!empty($header_image)) {
                    echo '<div class="mb-3">';
                    echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" class="img-fluid" style="max-height: 120px; object-fit: contain;">';
                    echo '</div>';
                }
                ?>
                <h4 class="fw-bold text-uppercase">Inventory Custodian Slip</h4>
            </div>
            
            <!-- Entity Information Layout -->
            <div class="row mb-4 border-bottom pb-3">
                <div class="col-md-7 border-end">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Entity Name:</div>
                        <div class="col-8 border-bottom text-primary fw-semibold"><?php echo htmlspecialchars($ics_form['entity_name']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Fund Cluster:</div>
                        <div class="col-8 border-bottom"><?php echo htmlspecialchars($ics_form['fund_cluster']); ?></div>
                    </div>
                </div>
                <div class="col-md-5 ps-md-4">
                    <div class="row">
                        <div class="col-5 fw-bold text-nowrap">ICS Number:</div>
                        <div class="col-7 border-bottom text-danger fw-bold"><?php echo htmlspecialchars($ics_form['ics_no']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Items Table - Following Excel Order -->
            <div class="mb-5">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light text-center small fw-bold">
                            <tr>
                                <th rowspan="2" style="width: 80px;">Quantity</th>
                                <th rowspan="2" style="width: 80px;">Unit</th>
                                <th colspan="2">Amount</th>
                                <th rowspan="2">Description</th>
                                <th rowspan="2" style="width: 120px;">Inventory<br>Item No.</th>
                                <th rowspan="2" style="width: 120px;">Estimated<br>Useful Life</th>
                            </tr>
                            <tr>
                                <th style="width: 120px;">Unit Cost</th>
                                <th style="width: 120px;">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php foreach ($ics_items as $item): ?>
                                <tr>
                                    <td><?php echo number_format($item['quantity'], 0); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                                    <td class="fw-bold">₱<?php echo number_format($item['total_cost'], 2); ?></td>
                                    <td class="text-start"><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td><small class="text-muted fw-mono"><?php echo htmlspecialchars($item['item_no']); ?></small></td>
                                    <td><?php echo htmlspecialchars($item['useful_life']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">Total Amount:</td>
                                <td class="text-center text-primary">₱<?php echo number_format(array_sum(array_column($ics_items, 'total_cost')), 2); ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="text-center mt-2">
                    <p class="text-muted small fst-italic">*** Nothing follows ***</p>
                </div>
            </div>
            
            <!-- Signature Section - Excel Grid Style -->
            <div class="signature-section pt-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded shadow-sm bg-light h-100">
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Received from:</h6>
                            <div class="text-center py-2 border-bottom fw-bold text-uppercase">
                                <?php echo htmlspecialchars($ics_form['received_from']); ?>
                            </div>
                            <div class="text-center small text-muted mb-3">Signature over Printed Name</div>
                            
                            <div class="text-center py-1 border-bottom fst-italic">
                                <?php echo htmlspecialchars($ics_form['received_from_position']); ?>
                            </div>
                            <div class="text-center small text-muted mb-3">Position / Office</div>

                            <div class="text-center py-1 border-bottom">
                                <?php echo (!empty($ics_form['received_from_date']) && $ics_form['received_from_date'] !== '0000-00-00') ? date('F d, Y', strtotime($ics_form['received_from_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="text-center small text-muted">Date</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded shadow-sm bg-light h-100">
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Received by:</h6>
                            <div class="text-center py-2 border-bottom fw-bold text-uppercase">
                                <?php echo htmlspecialchars($ics_form['received_by']); ?>
                            </div>
                            <div class="text-center small text-muted mb-3">Signature over Printed Name</div>
                            
                            <div class="text-center py-1 border-bottom fst-italic">
                                <?php echo htmlspecialchars($ics_form['received_by_position']); ?>
                            </div>
                            <div class="text-center small text-muted mb-3">Position / Office</div>

                            <div class="text-center py-1 border-bottom">
                                <?php echo (!empty($ics_form['received_by_date']) && $ics_form['received_by_date'] !== '0000-00-00') ? date('F d, Y', strtotime($ics_form['received_by_date'])) : '&nbsp;'; ?>
                            </div>
                            <div class="text-center small text-muted">Date</div>
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
