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
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', serif;
            background: linear-gradient(135deg, #F6F6F6 0%, #D6E4F0 100%);
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

        .iirup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .iirup-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .iirup-subtitle {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .iirup-form-number {
            font-size: 1.1rem;
            font-weight: bold;
            padding: 8px 16px;
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            border-radius: 4px;
            display: inline-block;
            margin: 10px 0;
        }

        .accountable-section {
            border: 2px solid #333;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            background-color: #f8f9fa;
        }

        .accountable-label {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .accountable-name {
            font-size: 1.3rem;
            font-weight: bold;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .iirup-table {
            border: 2px solid #333;
            font-size: 0.8rem;
            table-layout: fixed;
            width: 100%;
        }

        .iirup-table th {
            background-color: #e9ecef;
            border: 1px solid #333;
            font-weight: bold;
            text-align: center;
            padding: 8px 4px;
            font-size: 0.75rem;
        }

        .iirup-table td {
            border: 1px solid #333;
            padding: 6px 4px;
            font-size: 0.7rem;
            text-align: center;
        }

        .iirup-table td.text-left {
            text-align: left;
        }

        .header-inventory {
            background-color: #d4edda !important;
            color: #155724;
        }

        .header-disposal {
            background-color: #fff3cd !important;
            color: #856404;
        }

        .certification-section {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .certification-text {
            font-style: italic;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .signature-section {
            border-top: 2px solid #333;
            padding-top: 30px;
            margin-top: 30px;
        }

        .signature-box {
            text-align: center;
            margin-bottom: 20px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }

        .signature-line {
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 5px;
            min-height: 30px;
        }

        .signature-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 15px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .form-card {
                box-shadow: none;
                border: none;
            }

            .page-header {
                display: none !important;
            }

            body {
                background: white;
            }

            .iirup-table {
                font-size: 0.6rem;
            }

            .iirup-table th,
            .iirup-table td {
                padding: 4px 2px;
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
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                                <li>
                                    <a href="iirup_entries.php" class="dropdown-item">
                                        <i class="bi bi-arrow-left"></i> Back to Entries
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="window.open('print_iirup.php?id=<?php echo $iirup_id; ?>', '_blank')">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </li>
                                <li>
                                    <a href="iirup_form.php" class="dropdown-item">
                                        <i class="bi bi-plus-circle"></i> New IIRUP
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item" onclick="location.reload()">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IIRUP Form -->
            <div class="form-card">
                <!-- Form Header -->
                <div class="iirup-header">
                    <?php
                    if (!empty($header_image)) {
                        echo '<div style="margin-bottom: 20px;">';
                        echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 150px; object-fit: contain;">';
                        echo '</div>';
                    }
                    ?>
                    
                    <div style="font-style: italic; margin-top: 10px;">As of <?php echo htmlspecialchars($iirup_form['as_of_year']); ?></div>
                </div>

                <!-- Accountable Officer Information -->
                <div class="accountable-section">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="accountable-label">Accountable Officer</div>
                            <div class="accountable-name"><?php echo htmlspecialchars($iirup_form['accountable_officer']); ?></div>
                            <div style="font-size: 0.9rem; margin-top: 5px;"><?php echo htmlspecialchars($iirup_form['accountable_officer_designation']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="accountable-label">Designation</div>
                            <div class="accountable-name"><?php echo htmlspecialchars($iirup_form['designation']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="accountable-label">Department/Office</div>
                            <div class="accountable-name"><?php echo htmlspecialchars($iirup_form['department_office']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Items and Disposal Table -->
                <div class="mb-4">
                    <div class="table-responsive">
                        <table class="iirup-table">
                            <thead>
                                <tr>
                                    <th colspan="10" class="header-inventory">INVENTORY</th>
                                    <th colspan="11" class="header-disposal">INSPECTION AND DISPOSAL</th>
                                </tr>
                                <tr>
                                    <th style="width: 5%;">Date Acquired</th>
                                    <th style="width: 15%;">Particulars</th>
                                    <th style="width: 6%;">Property No.</th>
                                    <th style="width: 5%;">Qty</th>
                                    <th style="width: 6%;">Unit Cost</th>
                                    <th style="width: 6%;">Total Cost</th>
                                    <th style="width: 7%;">Accum. Depreciation</th>
                                    <th style="width: 7%;">Accum. Impairment losses</th>
                                    <th style="width: 7%;">Carrying amount</th>
                                    <th style="width: 4%;">Remarks</th>
                                    <th style="width: 5%;">Sale</th>
                                    <th style="width: 5%;">Transfer</th>
                                    <th style="width: 5%;">Destruction</th>
                                    <th style="width: 4%;">Others</th>
                                    <th style="width: 5%;">Total</th>
                                    <th style="width: 5%;">Appraised value</th>
                                    <th style="width: 4%;">OR no.</th>
                                    <th style="width: 4%;">Amount</th>
                                    <th style="width: 4%;">Dept</th>
                                    <th style="width: 3%;">Code</th>
                                    <th style="width: 5%;">Date received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($iirup_items as $index => $item): ?>
                                    <tr>
                                        <td><?php echo !empty($item['date_acquired']) ? date('M d, Y', strtotime($item['date_acquired'])) : ''; ?></td>
                                        <td class="text-left"><?php echo htmlspecialchars($item['particulars']); ?></td>
                                        <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                                        <td><?php echo number_format($item['quantity'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['total_cost'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['accumulated_depreciation'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['impairment_losses'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['carrying_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['inventory_remarks']); ?></td>
                                        <td>₱<?php echo number_format($item['disposal_sale'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['disposal_transfer'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['disposal_destruction'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['disposal_others']); ?></td>
                                        <td>₱<?php echo number_format($item['disposal_total'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['appraised_value'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['or_no']); ?></td>
                                        <td>₱<?php echo number_format($item['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($item['dept_office']); ?></td>
                                        <td><?php echo htmlspecialchars($item['control_no']); ?></td>
                                        <td><?php echo !empty($item['date_received']) ? date('M d, Y', strtotime($item['date_received'])) : ''; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Certification Section -->
                <div class="certification-section">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="certification-text">
                                I HEREBY request inspection and disposition, pursuant to Section 79 of PD 1445, of property enumerated above.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="certification-text">
                                I CERTIFY that I have inspected each and every article enumerated in this report, and that disposition made thereof was, in my judgment, best for public interest.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="certification-text">
                                I CERTIFY that I have witnessed disposition of articles enumerated on this report this _____ day of _____.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="signature-box">
                                <div class="signature-title">REQUESTED BY:</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['accountable_officer_name']); ?></div>
                                <div class="signature-label">(Signature over Printed Name)</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['accountable_officer_designation']); ?></div>
                                <div class="signature-label">(Designation)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="signature-box">
                                <div class="signature-title">APPROVED BY:</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['authorized_official_name']); ?></div>
                                <div class="signature-label">(Signature over Printed Name)</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['authorized_official_designation']); ?></div>
                                <div class="signature-label">(Designation)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="signature-box">
                                <div class="signature-title">INSPECTED BY:</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['inspection_officer_name']); ?></div>
                                <div class="signature-label">(Signature over Printed Name)</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['inspection_officer_designation'] ?? ''); ?></div>
                                <div class="signature-label">(Designation)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="signature-box">
                                <div class="signature-title">WITNESSED BY:</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['witness_name']); ?></div>
                                <div class="signature-label">(Signature over Printed Name)</div>
                                <div class="signature-line"><?php echo htmlspecialchars($iirup_form['witness_designation'] ?? ''); ?></div>
                                <div class="signature-label">(Designation)</div>
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