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

// Get IIRUP ID from URL
$iirup_id = $_GET['id'] ?? 0;
if (empty($iirup_id)) {
    header('Location: iirup_entries.php');
    exit();
}

// Get IIRUP form details
$iirup_form = null;
$stmt = $conn->prepare("SELECT * FROM iirup_forms WHERE id = ?");
$stmt->bind_param("i", $iirup_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $iirup_form = $result->fetch_assoc();
}
$stmt->close();

if (!$iirup_form) {
    header('Location: iirup_entries.php');
    exit();
}

// Get IIRUP items
$iirup_items = [];
$stmt = $conn->prepare("SELECT * FROM iirup_items WHERE form_id = ? ORDER BY item_order");
$stmt->bind_param("i", $iirup_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $iirup_items[] = $row;
}
$stmt->close();

logSystemAction($_SESSION['user_id'], 'Viewed IIRUP Form', 'forms', "IIRUP ID: $iirup_id, Form No: {$iirup_form['form_number']}");

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'IIRUP'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IIRUP View - <?php echo htmlspecialchars($iirup_form['form_number']); ?> - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', serif;
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

        .iirup-number {
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .status-submitted {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-processed {
            background-color: #cce5ff;
            color: #004085;
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

        .disposal-section {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .form-card {
                box-shadow: none;
            }

            .page-header {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <?php
    // Set page title for topbar
    $page_title = 'IIRUP View - ' . htmlspecialchars($iirup_form['form_number']);
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
                            <i class="bi bi-file-earmark-text"></i> IIRUP View
                        </h1>
                        <p class="text-muted mb-0">View Individual Item Request for User Property details</p>
                    </div>
                    <div class="col-md-4 text-md-end no-print">
                        <a href="iirup_entries.php" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-arrow-left"></i> Back to Entries
                        </a>
                        <button class="btn btn-outline-info btn-sm me-2" onclick="window.open('print_iirup.php?id=<?php echo $iirup_id; ?>', '_blank')">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <a href="iirup_form.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> New IIRUP
                        </a>
                    </div>
                </div>
            </div>

            <!-- IIRUP Form -->
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

                </div>

                <!-- Form Information -->
                <div class="row mb-3">
                    <div class="col-md-12 text-center">
                        <label class="form-label" style="font-style: italic; font-family: 'Times New Roman', serif;">As of <?php echo htmlspecialchars($iirup_form['as_of_year']); ?></label>
                    </div>
                </div>

                <!-- Accountable Officer Information -->
                <div class="row mb-3">
                    <div class="col-md-4 text-center">
                        <p class="form-control-plaintext" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['accountable_officer']); ?></p>
                        <label class="form-label">(Accountable Officer)</label>

                    </div>
                    <div class="col-md-4 text-center">
                        <p class="form-control-plaintext" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['designation']); ?></p>
                        <label class="form-label">(Designation)</label>

                    </div>
                    <div class="col-md-4 text-center">
                        <p class="form-control-plaintext" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['department_office']); ?></p>
                        <label class="form-label">(Department/Office)</label>

                    </div>
                </div>

                <!-- Items and Disposal Table -->
                <div class="mb-4">
                    <div class="table-responsive">
                        <table class="table table-bordered" style="font-size: 0.7rem; table-layout: fixed; width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="10" class="text-center" style="background-color: #f8f9fa; font-weight: bold;">INVENTORY</th>
                                    <th colspan="11" class="text-center" style="background-color: #e9ecef; font-weight: bold;">INSPECTION AND DISPOSAL</th>
                                </tr>
                                <tr>
                                    <th style="width: 6%;">Date Acquired</th>
                                    <th style="width: 18%;">Particulars</th>
                                    <th style="width: 7%;">Property No.</th>
                                    <th style="width: 6%;">Qty</th>
                                    <th style="width: 7%;">Unit Cost</th>
                                    <th style="width: 7%;">Total Cost</th>
                                    <th style="width: 8%;">Accum. Depreciation</th>
                                    <th style="width: 8%;">Accum. Impairment losses</th>
                                    <th style="width: 8%;">Carrying amount</th>
                                    <th style="width: 5%;">Remarks</th>
                                    <th style="width: 6%;">Sale</th>
                                    <th style="width: 6%;">Transfer</th>
                                    <th style="width: 6%;">Destruction</th>
                                    <th style="width: 5%;">Others</th>
                                    <th style="width: 6%;">Total</th>
                                    <th style="width: 6%;">Appraised value</th>
                                    <th style="width: 4%;">OR no.</th>
                                    <th style="width: 5%;">Amount</th>
                                    <th style="width: 5%;">Dept</th>
                                    <th style="width: 4%;">Code</th>
                                    <th style="width: 6%;">Date received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($iirup_items as $index => $item): ?>
                                    <tr>
                                        <td style="font-size: 0.65rem;"><?php echo !empty($item['date_acquired']) ? date('M d, Y', strtotime($item['date_acquired'])) : ''; ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['particulars']); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['property_no']); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo number_format($item['quantity'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['total_cost'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['accumulated_depreciation'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['impairment_losses'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['carrying_amount'], 2); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['inventory_remarks']); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['disposal_sale'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['disposal_transfer'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['disposal_destruction'], 2); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['disposal_others']); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['disposal_total'], 2); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['appraised_value'], 2); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['or_no']); ?></td>
                                        <td style="font-size: 0.65rem;">₱<?php echo number_format($item['amount'], 2); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['dept_office']); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo htmlspecialchars($item['control_no']); ?></td>
                                        <td style="font-size: 0.65rem;"><?php echo !empty($item['date_received']) ? date('M d, Y', strtotime($item['date_received'])) : ''; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="signature-label">
                    <div class="row">
                        <div class="col-md-6">
                            <p>I HEREBY request inspection and disposition, pursuant to Section 79 of PD 1445, of property enumerated above.</p>
                        </div>
                        <div class="col-md-3">
                            I CERTIFY that I have inspected each and every article enumerated in this report, and that disposition made thereof was, in my judgment, best for public interest.
                        </div>
                        <div class="col-md-3">
                            I CERTIFY that I have witnessed disposition of articles enumerated on this report this _____ day of _____.
                        </div>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="row">
                        <div class="col-md-3">
                            <h6><strong>Requested by:</strong></h6>
                            <p class="text-center" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['accountable_officer_name']); ?></p>
                            <p class="text-center" style="font-size: 0.7rem;">(Signature over Printed Name of Accountable Officer)</p>
                            <p class="text-center" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['accountable_officer_designation']); ?></p>
                            <p class="text-center" style="font-size: 0.7rem;">(Designation of Accountable Officer)</p>
                        </div>
                        <div class="col-md-3">
                            <h6><strong>Approved by:</strong></h6>
                            <p class="text-center" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['authorized_official_name']); ?></p>
                            <p class="text-center" style="font-size: 0.7rem;">(Signature over Printed Name of Authorized Official)</p>
                            <p class="text-center" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['authorized_official_designation']); ?></p>
                            <p class="text-center" style="font-size: 0.7rem;">(Designation of Authorized Official)</p>


                        </div>
                        <div class="col-md-3">
                           
                            <p class="text-center" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['inspection_officer_name']); ?></p>
                            <p class="text-center" style="font-size: 0.7rem;">(Signature over Printed Name of Inspection
                                Officer)</p>
                        </div>
                        <div class="col-md-3">
                           
                            <p class="text-center" style="border-bottom: 2px solid #333; padding-bottom: 5px;"><?php echo htmlspecialchars($iirup_form['witness_name']); ?></p>
                            <p class="text-center" style="font-size: 0.7rem;">(Signature over Printed Name of Witness)</p>
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