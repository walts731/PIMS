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

logSystemAction($_SESSION['user_id'], 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php');

// Get next SAI number (IIRUP uses sai_no tag type)
$next_sai_no = getNextTagPreview('sai_no');
if ($next_sai_no === null) {
    $next_sai_no = ''; // Fallback if no configuration exists
}

// Get SAI configuration for JavaScript
$sai_config = null;
$result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'sai_no' AND status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $sai_config = $row;
}

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
    <title>Individual Item Request for User Property - PIMS</title>
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
            transition: var(--transition);
        }
        
        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .table-bordered {
            border: 1px solid #dee2e6;
        }
        
        .form-control, .form-select {
            border-radius: var(--border-radius);
            border: 1px solid #dee2e6;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1572C6 0%, #4AB8E8 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        @media print {
            .no-print { display: none !important; }
            .form-card { box-shadow: none; }
        }
        
        /* Make table inputs more compact */
        .form-control-sm {
            padding: 2px 4px !important;
            font-size: 11px !important;
            height: 24px !important;
            line-height: 1.2 !important;
        }
        
        .table th, .table td {
            padding: 4px 6px !important;
            font-size: 11px !important;
            vertical-align: middle !important;
        }
        
        .table th {
            font-size: 10px !important;
            font-weight: 600 !important;
        }
        
        .table-responsive {
            font-size: 11px !important;
        }
        
        /* Reduce button sizes */
        .btn-sm {
            padding: 2px 6px !important;
            font-size: 10px !important;
            height: 24px !important;
        }
        
        /* Footer Styles */
        .signature-block {
            margin-bottom: 30px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 5px;
            min-height: 30px;
            display: flex;
            align-items: center;
        }
        .signature-block p {
            margin-bottom: 5px;
            font-size: 12px;
        }
        .signature-block p:not(.signature-line) {
            font-style: italic;
            color: #666;
        }
        
        /* Autocomplete styles */
        .autocomplete-container {
            position: relative;
        }
        
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            font-size: 11px;
        }
        
        .autocomplete-item:hover {
            background-color: #f8f9fa;
        }
        
        .autocomplete-item.selected {
            background-color: #e9ecef;
        }
        
        .autocomplete-item strong {
            color: #191BA9;
        }
        
        .autocomplete-item small {
            color: #6c757d;
            display: block;
            margin-top: 2px;
        }
        
        /* Clear button styles */
        .position-relative {
            position: relative !important;
        }
        
        .position-absolute {
            position: absolute !important;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Individual Item Request for User Property';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-file-earmark-text"></i> Individual Item Request for User Property
                    </h1>
                    <p class="text-muted mb-0">Manage Individual Item Request for User Property forms</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="iirup_entries.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-list"></i> View Entries
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- IIRUP Form -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> IIRUP Form
                </h5>
                <div class="no-print">
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetIIRUPForm()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                    <div class="btn-group" role="group">
                        <input type="text" class="form-control form-control-sm" id="formNumberSearch" placeholder="Form Number..." style="width: 200px;">
                        <button class="btn btn-sm btn-info" onclick="loadIIRUPForm()" title="Load Form">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <form id="iirupForm" method="POST" action="process_iirup.php">
                <!-- IIRUP Form Header -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php 
                    if (!empty($header_image)) {
                        echo '<div style="margin-bottom: 10px;">';
                        echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 120px; object-fit: contain;">';
                        echo '</div>';
                    }
                    ?>
                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 16px; font-weight: bold;">As of Year: <?php echo date('Y'); ?></p>
                    </div>
                </div>
                
                <!-- Header Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div style="border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; background-color: #f8f9fa;">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label" style="font-weight: normal; margin-bottom: 5px;">Name of Accountable Officer:</label>
                                    <input type="text" class="form-control" name="accountable_officer" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" style="font-weight: normal; margin-bottom: 5px;">Designation:</label>
                                    <input type="text" class="form-control" name="designation" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" style="font-weight: normal; margin-bottom: 5px;">Department/Office:</label>
                                    <select class="form-control" name="department_office" required>
                                        <option value="">Select Department/Office</option>
                                        <?php
                                        // Fetch offices from database
                                        $offices_result = $conn->query("SELECT office_name FROM offices WHERE status = 'active' ORDER BY office_name");
                                        if ($offices_result) {
                                            while ($office = $offices_result->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($office['office_name']) . '">' . htmlspecialchars($office['office_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden field for as_of_year -->
                <input type="hidden" name="as_of_year" value="<?php echo date('Y'); ?>">
                
                <!-- Items Table -->
                            <div class="mb-3">
                                <label class="form-label"><strong>Items:</strong></label>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="iirupItemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th rowspan="3">Date Acquired</th>
                                                <th rowspan="3">Particulars/ Articles</th>
                                                <th rowspan="3">Property No.</th>
                                                <th rowspan="3">Qty</th>
                                                <th colspan="6">INVENTORY</th>
                                                <th colspan="7">INSPECTION and DISPOSAL</th>
                                                <th colspan="2">RECORD OF SALES</th>
                                                <th rowspan="3">DEPT/OFFICE</th>
                                                <th rowspan="3">CONTROL NO.</th>
                                                <th rowspan="3">DATE RECEIVED</th>
                                                <th rowspan="3">Action</th>
                                            </tr>
                                            <tr>
                                                <th rowspan="2">Unit Cost</th>
                                                <th rowspan="2">Total Cost</th>
                                                <th rowspan="2">Accumulated Depreciation</th>
                                                <th rowspan="2">Accumulated Impairment Losses</th>
                                                <th rowspan="2">Carrying Amount</th>
                                                <th rowspan="2">Remarks</th>
                                                <th colspan="5">DISPOSAL</th>
                                                <th rowspan="2">Appraised Value</th>
                                                <th rowspan="2">Total</th>
                                                <th rowspan="2">OR No.</th>
                                                <th rowspan="2">Amount</th>
                                            </tr>
                                            <tr>
                                                <th>Sale</th>
                                                <th>Transfer</th>
                                                <th>Destruction</th>
                                                <th>Others (Specify)</th>
                                                <th>Total</th>
                                            </tr>
                                            <tr>
                                                <th>(1)</th>
                                                <th>(2)</th>
                                                <th>(3)</th>
                                                <th>(4)</th>
                                                <th>(5)</th>
                                                <th>(6)</th>
                                                <th>(7)</th>
                                                <th>(8)</th>
                                                <th>(9)</th>
                                                <th>(10)</th>
                                                <th>(11)</th>
                                                <th>(12)</th>
                                                <th>(13)</th>
                                                <th>(14)</th>
                                                <th>(15)</th>
                                                <th>(16)</th>
                                                <th>(17)</th>
                                                <th>(18)</th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="date" class="form-control form-control-sm" name="date_acquired[]"></td>
                                                <td>
                                                    <div class="autocomplete-container position-relative">
                                                        <input type="text" class="form-control form-control-sm" name="particulars[]" placeholder="Type to search assets..." autocomplete="off">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="right: 2px; top: 2px; padding: 2px 6px; font-size: 10px;" onclick="clearParticulars(this)" title="Clear">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                        <div class="autocomplete-dropdown"></div>
                                                    </div>
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm" name="property_no[]"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="qty[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="unit_cost[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="total_cost[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="accumulated_depreciation[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="impairment_losses[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="carrying_amount[]"></td>
                                                <td><input type="text" class="form-control form-control-sm" name="inventory_remarks[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="disposal_sale[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="disposal_transfer[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="disposal_destruction[]"></td>
                                                <td><input type="text" class="form-control form-control-sm" name="disposal_others[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="disposal_total[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="appraised_value[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="total[]"></td>
                                                <td><input type="text" class="form-control form-control-sm" name="or_no[]"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="amount[]"></td>
                                                <td><select class="form-control form-control-sm" name="dept_office[]">
                                                    <option value="">Select Department/Office</option>
                                                    <?php
                                                    // Fetch offices from database
                                                    $offices_result = $conn->query("SELECT office_name FROM offices WHERE status = 'active' ORDER BY office_name");
                                                    if ($offices_result) {
                                                        while ($office = $offices_result->fetch_assoc()) {
                                                            echo '<option value="' . htmlspecialchars($office['office_name']) . '">' . htmlspecialchars($office['office_name']) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select></td>
                                                <td><input type="text" class="form-control form-control-sm" name="control_no[]"></td>
                                                <td><input type="date" class="form-control form-control-sm" name="date_received[]"></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-sm btn-info" onclick="openFillModal(this)" title="Fill Data">
                                                            <i class="bi bi-pencil-fill"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="clearRowData(this)" title="Clear Row">
                                                            <i class="bi bi-arrow-clockwise"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeIIRUPRow(this)" title="Delete Row">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addIIRUPRow()">
                                    <i class="bi bi-plus-circle"></i> Add Row
                                </button>
                            </div>
                            
                            <!-- Footer Section -->
        <div class="row mt-5">
            <div class="col-md-6">
                <p class="mb-4">I HEREBY request inspection and disposition, pursuant to Section 79 of PD 1445, of property enumerated above.</p>
                <div class="row">
                    <div class="col-md-6">
                        <p>Requested by:</p>
                        <div class="signature-block">
                            <input type="text" class="form-control form-control-sm mb-2" name="accountable_officer_name" placeholder="Signature over Printed Name of Accountable Officer">
                            <input type="text" class="form-control form-control-sm mb-2" name="accountable_officer_designation" placeholder="Designation of Accountable Officer">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Approved by:</p>
                        <div class="signature-block">
                            <input type="text" class="form-control form-control-sm mb-2" name="authorized_official_name" placeholder="Signature over Printed Name of Authorized Official">
                            <input type="text" class="form-control form-control-sm mb-2" name="authorized_official_designation" placeholder="Designation of Authorized Official">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">
                        <p>I CERTIFY that I have inspected each and every article enumerated in this report, and that disposition made thereof was, in my judgment, best for public interest.</p>
                        <div class="signature-block">
                            <input type="text" class="form-control form-control-sm mb-2" name="inspection_officer_name" placeholder="Signature over Printed Name of Inspection Officer">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>I CERTIFY that I have witnessed disposition of articles enumerated on this report this _____ day of _____.</p>
                        <div class="signature-block">
                            <input type="text" class="form-control form-control-sm mb-2" name="witness_name" placeholder="Signature over Printed Name of Witness">
                        </div>
                    </div>
                </div>
            </div>

              <!-- Form Actions -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save IIRUP
                            </button>
                        </div>
        </div>
    </div>
                            
                          
                    </form>
                </div>
            </div>
        </div>

        

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    
    <!-- Fill Data Modal -->
    <div class="modal fade" id="fillDataModal" tabindex="-1" aria-labelledby="fillDataModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fillDataModalLabel">
                        <i class="bi bi-pencil-fill"></i> Fill Row Data
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date Acquired</label>
                            <input type="date" class="form-control" id="modal_date_acquired">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Property No.</label>
                            <input type="text" class="form-control" id="modal_property_no">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Particulars/Articles</label>
                            <div class="autocomplete-container position-relative">
                                <input type="text" class="form-control" id="modal_particulars" placeholder="Type to search assets..." autocomplete="off">
                                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="right: 2px; top: 2px; padding: 2px 6px; font-size: 10px;" onclick="clearModalParticulars()" title="Clear">
                                    <i class="bi bi-x"></i>
                                </button>
                                <div class="autocomplete-dropdown"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="modal_qty">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit Cost</label>
                            <input type="number" step="0.01" class="form-control" id="modal_unit_cost">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Cost</label>
                            <input type="number" step="0.01" class="form-control" id="modal_total_cost">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Accumulated Depreciation</label>
                            <input type="number" step="0.01" class="form-control" id="modal_accumulated_depreciation">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Impairment Losses</label>
                            <input type="number" step="0.01" class="form-control" id="modal_impairment_losses">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Carrying Amount</label>
                            <input type="number" step="0.01" class="form-control" id="modal_carrying_amount">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Inventory Remarks</label>
                            <input type="text" class="form-control" id="modal_inventory_remarks">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Appraised Value</label>
                            <input type="number" step="0.01" class="form-control" id="modal_appraised_value">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Disposal - Sale</label>
                            <input type="number" step="0.01" class="form-control" id="modal_disposal_sale">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Disposal - Transfer</label>
                            <input type="number" step="0.01" class="form-control" id="modal_disposal_transfer">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Disposal - Destruction</label>
                            <input type="number" step="0.01" class="form-control" id="modal_disposal_destruction">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Disposal - Others</label>
                            <input type="text" class="form-control" id="modal_disposal_others">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Disposal Total</label>
                            <input type="number" step="0.01" class="form-control" id="modal_disposal_total">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total</label>
                            <input type="number" step="0.01" class="form-control" id="modal_total">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">OR No.</label>
                            <input type="text" class="form-control" id="modal_or_no">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="modal_amount">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Department/Office</label>
                            <select class="form-control" id="modal_dept_office">
                                <option value="">Select Department/Office</option>
                                <?php
                                // Fetch offices from database
                                $offices_result = $conn->query("SELECT office_name FROM offices WHERE status = 'active' ORDER BY office_name");
                                if ($offices_result) {
                                    while ($office = $offices_result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($office['office_name']) . '">' . htmlspecialchars($office['office_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Control No.</label>
                            <input type="text" class="form-control" id="modal_control_no">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date Received</label>
                            <input type="date" class="form-control" id="modal_date_received">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveFillData()">
                        <i class="bi bi-check-lg"></i> Save Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addIIRUPRow() {
            const table = document.getElementById('iirupItemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            const cells = [
                '<input type="date" class="form-control form-control-sm" name="date_acquired[]">',
                '<div class="autocomplete-container"><input type="text" class="form-control form-control-sm" name="particulars[]" placeholder="Type to search assets..." autocomplete="off"><div class="autocomplete-dropdown"></div></div>',
                '<input type="text" class="form-control form-control-sm" name="property_no[]">',
                '<input type="number" class="form-control form-control-sm" name="qty[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="unit_cost[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="total_cost[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="accumulated_depreciation[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="impairment_losses[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="carrying_amount[]">',
                '<input type="text" class="form-control form-control-sm" name="inventory_remarks[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_sale[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_transfer[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_destruction[]">',
                '<input type="text" class="form-control form-control-sm" name="disposal_others[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="disposal_total[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="appraised_value[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="total[]">',
                '<input type="text" class="form-control form-control-sm" name="or_no[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="amount[]">',
                '<select class="form-control form-control-sm" name="dept_office[]">' +
                    '<option value="">Select Department/Office</option>' +
                '</select>',
                '<input type="text" class="form-control form-control-sm" name="control_no[]">',
                '<input type="date" class="form-control form-control-sm" name="date_received[]">',
                '<div class="btn-group btn-group-sm" role="group">' +
                    '<button type="button" class="btn btn-sm btn-info" onclick="openFillModal(this)" title="Fill Data">' +
                        '<i class="bi bi-pencil-fill"></i>' +
                    '</button>' +
                    '<button type="button" class="btn btn-sm btn-warning" onclick="clearRowData(this)" title="Clear Row">' +
                        '<i class="bi bi-arrow-clockwise"></i>' +
                    '</button>' +
                    '<button type="button" class="btn btn-sm btn-danger" onclick="removeIIRUPRow(this)" title="Delete Row">' +
                        '<i class="bi bi-trash"></i>' +
                    '</button>' +
                '</div>'
            ];
            
            cells.forEach((cellHtml, index) => {
                const cell = newRow.insertCell(index);
                cell.innerHTML = cellHtml;
            });
        }
        
        function clearRowData(button) {
            const row = button.closest('tr');
            const inputs = row.getElementsByTagName('input');
            const selects = row.getElementsByTagName('select');
            
            // Clear all input values and make them editable
            inputs.forEach(input => {
                input.value = '';
                input.readOnly = false;
                input.style.backgroundColor = '';
            });
            
            // Reset select fields and make them editable
            selects.forEach(select => {
                select.value = '';
                select.disabled = false;
                select.style.backgroundColor = '';
            });
            
            // Hide autocomplete dropdown if visible
            const dropdown = row.querySelector('.autocomplete-dropdown');
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        }
        
        function removeIIRUPRow(button) {
            const row = button.closest('tr');
            const table = document.getElementById('iirupItemsTable').getElementsByTagName('tbody')[0];
            
            if (table.rows.length > 1) {
                row.remove();
            } else {
                alert('At least one row is required');
            }
        }
        
        function calculateIIRUPTotal(input) {
            const row = input.closest('tr');
            const quantity = row.querySelector('input[name="quantity[]"]').value || 0;
            const unitPrice = row.querySelector('input[name="unit_price[]"]').value || 0;
            const totalAmount = (parseFloat(quantity) * parseFloat(unitPrice)).toFixed(2);
            
            row.querySelector('input[name="total_amount[]"]').value = totalAmount;
        }
        
        function resetIIRUPForm() {
            if (confirm('Are you sure you want to reset form? All data will be lost.')) {
                document.getElementById('iirupForm').reset();
                
                // Clear all read-only states and backgrounds
                const allInputs = document.querySelectorAll('#iirupItemsTable input');
                const allSelects = document.querySelectorAll('#iirupItemsTable select');
                
                allInputs.forEach(input => {
                    input.readOnly = false;
                    input.style.backgroundColor = '';
                });
                
                allSelects.forEach(select => {
                    select.disabled = false;
                    select.style.backgroundColor = '';
                });
                
                const table = document.getElementById('iirupItemsTable').getElementsByTagName('tbody')[0];
                while (table.rows.length > 1) {
                    table.deleteRow(1);
                }
            }
        }
        
        function printIIRUPForm() {
            window.print();
        }
        
        function createNewIIRUP() {
            document.getElementById('iirupForm').reset();
            // Generate fresh SAI number
            generateNewSaiNumber();
        }
        
        // Generate new SAI number via AJAX
        function generateNewSaiNumber() {
            <?php if ($sai_config): ?>
            const components = <?php 
                $components = json_decode($sai_config['format_components'], true);
                if (is_string($components)) {
                    $components = json_decode($components, true);
                }
                echo json_encode($components ?: []);
            ?>;
            const digits = <?php echo $sai_config['digits']; ?>;
            const separator = '<?php echo $sai_config['separator']; ?>';
            
            fetch('../SYSTEM_ADMIN/tags.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_preview&tag_type=sai_no&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
            })
            .then(response => response.json())
            .then(data => {
                if (data.preview) {
                    document.getElementById('iirup_no').value = data.preview;
                }
            })
            .catch(error => {
                console.error('Error generating SAI number:', error);
            });
            <?php endif; ?>
        }
        
        // Handle form submission to update counter
        document.getElementById('iirupForm').addEventListener('submit', function(e) {
            console.log('Form submitting...');
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            
            // Always increment counter since field is always auto-generated
            const incrementField = document.createElement('input');
            incrementField.type = 'hidden';
            incrementField.name = 'increment_sai_counter';
            incrementField.value = '1';
            this.appendChild(incrementField);
        });
        
        function exportIIRUPData() {
            // TODO: Implement export functionality
            alert('Export functionality will be implemented');
        }
        
        let currentRow = null;
        
        function openFillModal(button) {
            currentRow = button.closest('tr');
            const modal = new bootstrap.Modal(document.getElementById('fillDataModal'));
            
            // Reset modal fields to editable state
            const modalInputs = document.querySelectorAll('#fillDataModal input');
            const modalSelects = document.querySelectorAll('#fillDataModal select');
            
            modalInputs.forEach(input => {
                input.readOnly = false;
                input.style.backgroundColor = '';
                input.value = '';
            });
            
            modalSelects.forEach(select => {
                select.disabled = false;
                select.style.backgroundColor = '';
                select.value = '';
            });
            
            // Get current values from the row
            const inputs = currentRow.getElementsByTagName('input');
            const selects = currentRow.getElementsByTagName('select');
            
            // Populate modal with current values (all 22 fields)
            document.getElementById('modal_date_acquired').value = inputs[0].value || '';
            document.getElementById('modal_particulars').value = inputs[1].value || '';
            document.getElementById('modal_property_no').value = inputs[2].value || '';
            document.getElementById('modal_qty').value = inputs[3].value || '';
            document.getElementById('modal_unit_cost').value = inputs[4].value || '';
            document.getElementById('modal_total_cost').value = inputs[5].value || '';
            document.getElementById('modal_accumulated_depreciation').value = inputs[6].value || '';
            document.getElementById('modal_impairment_losses').value = inputs[7].value || '';
            document.getElementById('modal_carrying_amount').value = inputs[8].value || '';
            document.getElementById('modal_inventory_remarks').value = inputs[9].value || '';
            document.getElementById('modal_disposal_sale').value = inputs[10].value || '';
            document.getElementById('modal_disposal_transfer').value = inputs[11].value || '';
            document.getElementById('modal_disposal_destruction').value = inputs[12].value || '';
            document.getElementById('modal_disposal_others').value = inputs[13].value || '';
            document.getElementById('modal_disposal_total').value = inputs[14].value || '';
            document.getElementById('modal_appraised_value').value = inputs[15].value || '';
            document.getElementById('modal_total').value = inputs[16].value || '';
            document.getElementById('modal_or_no').value = inputs[17].value || '';
            document.getElementById('modal_amount').value = inputs[18].value || '';
            document.getElementById('modal_dept_office').value = selects[0].value || '';
            document.getElementById('modal_control_no').value = inputs[19].value || '';
            document.getElementById('modal_date_received').value = inputs[20].value || '';
            
            modal.show();
        }
        
        function saveFillData() {
            if (!currentRow) return;
            
            const inputs = currentRow.getElementsByTagName('input');
            const selects = currentRow.getElementsByTagName('select');
            
            // Save modal values back to the row (all 22 fields)
            inputs[0].value = document.getElementById('modal_date_acquired').value;
            inputs[1].value = document.getElementById('modal_particulars').value;
            inputs[2].value = document.getElementById('modal_property_no').value;
            inputs[3].value = document.getElementById('modal_qty').value;
            inputs[4].value = document.getElementById('modal_unit_cost').value;
            inputs[5].value = document.getElementById('modal_total_cost').value;
            inputs[6].value = document.getElementById('modal_accumulated_depreciation').value;
            inputs[7].value = document.getElementById('modal_impairment_losses').value;
            inputs[8].value = document.getElementById('modal_carrying_amount').value;
            inputs[9].value = document.getElementById('modal_inventory_remarks').value;
            inputs[10].value = document.getElementById('modal_disposal_sale').value;
            inputs[11].value = document.getElementById('modal_disposal_transfer').value;
            inputs[12].value = document.getElementById('modal_disposal_destruction').value;
            inputs[13].value = document.getElementById('modal_disposal_others').value;
            inputs[14].value = document.getElementById('modal_disposal_total').value;
            inputs[15].value = document.getElementById('modal_appraised_value').value;
            inputs[16].value = document.getElementById('modal_total').value;
            inputs[17].value = document.getElementById('modal_or_no').value;
            inputs[18].value = document.getElementById('modal_amount').value;
            selects[0].value = document.getElementById('modal_dept_office').value;
            inputs[19].value = document.getElementById('modal_control_no').value;
            inputs[20].value = document.getElementById('modal_date_received').value;
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('fillDataModal'));
            modal.hide();
            
            // Clear current row reference
            currentRow = null;
        }
        
        // Autocomplete functionality for asset search
        let searchTimeout;
        let currentSearchIndex = -1;
        
        function initAutocomplete() {
            // Add event listeners to all particulars inputs (both table and modal)
            document.addEventListener('input', function(e) {
                if (e.target.matches('input[name="particulars[]"]') || e.target.matches('#modal_particulars')) {
                    const input = e.target;
                    const container = input.closest('.autocomplete-container');
                    const dropdown = container.querySelector('.autocomplete-dropdown');
                    
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        searchAssets(input.value, dropdown, input);
                    }, 150);
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.autocomplete-container')) {
                    document.querySelectorAll('.autocomplete-dropdown').forEach(dropdown => {
                        dropdown.style.display = 'none';
                    });
                }
            });
            
            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.target.matches('input[name="particulars[]"]') || e.target.matches('#modal_particulars')) {
                    const container = e.target.closest('.autocomplete-container');
                    const dropdown = container.querySelector('.autocomplete-dropdown');
                    const items = dropdown.querySelectorAll('.autocomplete-item');
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        currentSearchIndex = Math.min(currentSearchIndex + 1, items.length - 1);
                        updateSelection(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        currentSearchIndex = Math.max(currentSearchIndex - 1, -1);
                        updateSelection(items);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (currentSearchIndex >= 0 && items[currentSearchIndex]) {
                            items[currentSearchIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        dropdown.style.display = 'none';
                        currentSearchIndex = -1;
                    }
                }
            });
        }
        
        function searchAssets(query, dropdown, input) {
            if (query.length < 1) {
                dropdown.style.display = 'none';
                return;
            }
            
            console.log('Searching for:', query);
            fetch('../api/search_assets.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    console.log('Search results:', data);
                    if (data.success && data.assets.length > 0) {
                        displaySearchResults(data.assets, dropdown, input);
                    } else {
                        dropdown.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error searching assets:', error);
                    dropdown.style.display = 'none';
                });
        }
        
        function displaySearchResults(assets, dropdown, input) {
            dropdown.innerHTML = '';
            currentSearchIndex = -1;
            
            assets.forEach((asset, index) => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerHTML = `
                    <strong>${asset.description}</strong>
                    <small>Property No: ${asset.property_no || 'N/A'} | Value: ₱${parseFloat(asset.value).toFixed(2)} | Status: ${asset.status}</small>
                `;
                
                item.addEventListener('click', function() {
                    if (input.id === 'modal_particulars') {
                        selectAssetForModal(asset, input);
                    } else {
                        selectAsset(asset, input);
                    }
                    dropdown.style.display = 'none';
                });
                
                dropdown.appendChild(item);
            });
            
            dropdown.style.display = 'block';
        }
        
        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === currentSearchIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }
        
        function selectAsset(asset, input) {
            const row = input.closest('tr');
            const inputs = row.getElementsByTagName('input');
            const selects = row.getElementsByTagName('select');
            
            // Fill the form fields with asset data
            // Find the correct input indices (accounting for the autocomplete container)
            let inputIndex = 0;
            for (let i = 0; i < inputs.length; i++) {
                if (inputs[i].name === 'particulars[]') {
                    inputs[i].value = asset.description;
                    inputIndex = i;
                    break;
                }
            }
            
            // Fill other fields and make them read-only
            const dateAcquired = row.querySelector('input[name="date_acquired[]"]');
            if (dateAcquired) {
                // Use created_at date, fallback to acquisition_date, then leave empty
                let dateToUse = asset.created_at || asset.acquisition_date || '';
                if (dateToUse) {
                    // Format the date to YYYY-MM-DD for input field
                    const dateObj = new Date(dateToUse);
                    if (!isNaN(dateObj.getTime())) {
                        dateToUse = dateObj.toISOString().split('T')[0]; // Format as YYYY-MM-DD
                        dateAcquired.value = dateToUse;
                        dateAcquired.readOnly = true;
                        dateAcquired.style.backgroundColor = '#f8f9fa';
                    }
                }
            }
            
            // Fill property_no field
            const propertyNo = row.querySelector('input[name="property_no[]"]');
            if (propertyNo && asset.property_no) {
                propertyNo.value = asset.property_no;
                propertyNo.readOnly = true;
                propertyNo.style.backgroundColor = '#f8f9fa';
            }
            
            const qty = row.querySelector('input[name="qty[]"]');
            if (qty) {
                qty.value = 1; // Default quantity
                qty.readOnly = true;
                qty.style.backgroundColor = '#f8f9fa';
            }
            
            const unitCost = row.querySelector('input[name="unit_cost[]"]');
            if (unitCost && asset.value) {
                unitCost.value = asset.value;
                unitCost.readOnly = true;
                unitCost.style.backgroundColor = '#f8f9fa';
            }
            
            const totalCost = row.querySelector('input[name="total_cost[]"]');
            if (totalCost && asset.value) {
                totalCost.value = asset.value;
                totalCost.readOnly = true;
                totalCost.style.backgroundColor = '#f8f9fa';
            }
            
            // Set department/office if available
            if (asset.office_name) {
                const deptOffice = row.querySelector('select[name="dept_office[]"]');
                if (deptOffice) {
                    // Add option if not exists
                    let optionExists = false;
                    for (let option of deptOffice.options) {
                        if (option.value === asset.office_name) {
                            optionExists = true;
                            break;
                        }
                    }
                    if (!optionExists) {
                        const newOption = document.createElement('option');
                        newOption.value = asset.office_name;
                        newOption.textContent = asset.office_name;
                        deptOffice.appendChild(newOption);
                    }
                    deptOffice.value = asset.office_name;
                    deptOffice.disabled = true; // Make read-only
                    deptOffice.style.backgroundColor = '#f8f9fa';
                }
            }
        }
        
        function selectAssetForModal(asset, input) {
            // Fill modal fields with asset data and make them read-only (except particulars)
            const particularsField = document.getElementById('modal_particulars');
            particularsField.value = asset.description;
            // Keep particulars field editable - don't make it read-only
            
            // Use created_at date, fallback to acquisition_date, then leave empty
            let dateToUse = asset.created_at || asset.acquisition_date || '';
            if (dateToUse) {
                const dateField = document.getElementById('modal_date_acquired');
                // Format the date to YYYY-MM-DD for input field
                const dateObj = new Date(dateToUse);
                if (!isNaN(dateObj.getTime())) {
                    dateToUse = dateObj.toISOString().split('T')[0]; // Format as YYYY-MM-DD
                    dateField.value = dateToUse;
                    dateField.readOnly = true;
                    dateField.style.backgroundColor = '#f8f9fa';
                }
            }
            
            // Fill property_no field in modal
            if (asset.property_no) {
                const propertyNoField = document.getElementById('modal_property_no');
                propertyNoField.value = asset.property_no;
                propertyNoField.readOnly = true;
                propertyNoField.style.backgroundColor = '#f8f9fa';
            }
            
            const qtyField = document.getElementById('modal_qty');
            qtyField.value = 1; // Default quantity
            qtyField.readOnly = true;
            qtyField.style.backgroundColor = '#f8f9fa';
            
            if (asset.value) {
                const unitCostField = document.getElementById('modal_unit_cost');
                unitCostField.value = asset.value;
                unitCostField.readOnly = true;
                unitCostField.style.backgroundColor = '#f8f9fa';
                
                const totalCostField = document.getElementById('modal_total_cost');
                totalCostField.value = asset.value;
                totalCostField.readOnly = true;
                totalCostField.style.backgroundColor = '#f8f9fa';
            }
            
            // Set department/office if available
            if (asset.office_name) {
                const deptOffice = document.getElementById('modal_dept_office');
                if (deptOffice) {
                    // Add option if not exists
                    let optionExists = false;
                    for (let option of deptOffice.options) {
                        if (option.value === asset.office_name) {
                            optionExists = true;
                            break;
                        }
                    }
                    if (!optionExists) {
                        const newOption = document.createElement('option');
                        newOption.value = asset.office_name;
                        newOption.textContent = asset.office_name;
                        deptOffice.appendChild(newOption);
                    }
                    deptOffice.value = asset.office_name;
                    deptOffice.disabled = true; // Make read-only
                    deptOffice.style.backgroundColor = '#f8f9fa';
                }
            }
        }
        
        function clearParticulars(button) {
            const container = button.closest('.autocomplete-container');
            const input = container.querySelector('input[name="particulars[]"]');
            if (input) {
                input.value = '';
                input.focus();
            }
            
            // Hide autocomplete dropdown if visible
            const dropdown = container.querySelector('.autocomplete-dropdown');
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        }
        
        function clearModalParticulars() {
            const input = document.getElementById('modal_particulars');
            if (input) {
                input.value = '';
                input.focus();
            }
            
            // Hide autocomplete dropdown if visible
            const dropdown = document.querySelector('#fillDataModal .autocomplete-dropdown');
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        }
        
        function loadIIRUPForm() {
            const formNumber = document.getElementById('formNumberSearch').value.trim();
            
            if (!formNumber) {
                alert('Please enter a form number');
                return;
            }
            
            fetch(`../api/get_iirup_form.php?form_number=${encodeURIComponent(formNumber)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateIIRUPForm(data.form);
                        populateIIRUPItems(data.items);
                        
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            <i class="bi bi-check-circle-fill"></i> 
                            IIRUP Form '${formNumber}' loaded successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        
                        // Insert after the header
                        const header = document.querySelector('.d-flex.justify-content-between');
                        header.parentNode.insertBefore(alertDiv, header.nextSibling);
                        
                        // Auto-hide after 3 seconds
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 3000);
                        
                    } else {
                        alert('Form not found: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading form:', error);
                    alert('Error loading form. Please try again.');
                });
        }
        
        function populateIIRUPForm(form) {
            // Populate header fields
            document.querySelector('input[name="accountable_officer"]').value = form.accountable_officer || '';
            document.querySelector('input[name="designation"]').value = form.designation || '';
            document.querySelector('select[name="department_office"]').value = form.department_office || '';
            document.querySelector('input[name="accountable_officer_name"]').value = form.accountable_officer_name || '';
            document.querySelector('input[name="accountable_officer_designation"]').value = form.accountable_officer_designation || '';
            document.querySelector('input[name="authorized_official_name"]').value = form.authorized_official_name || '';
            document.querySelector('input[name="authorized_official_designation"]').value = form.authorized_official_designation || '';
            document.querySelector('input[name="inspection_officer_name"]').value = form.inspection_officer_name || '';
            document.querySelector('input[name="witness_name"]').value = form.witness_name || '';
            
            // Update as_of_year if available
            if (form.as_of_year) {
                const yearDisplay = document.querySelector('p');
                if (yearDisplay) {
                    yearDisplay.textContent = 'As of Year: ' + form.as_of_year;
                }
            }
        }
        
        function populateIIRUPItems(items) {
            // Clear existing rows
            const tbody = document.querySelector('#iirupItemsTable tbody');
            tbody.innerHTML = '';
            
            // Add items
            items.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="date" class="form-control form-control-sm" name="date_acquired[]" value="${item.date_acquired || ''}"></td>
                    <td>
                        <div class="autocomplete-container position-relative">
                            <input type="text" class="form-control form-control-sm" name="particulars[]" value="${item.particulars || ''}" placeholder="Type to search assets..." autocomplete="off">
                            <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="right: 2px; top: 2px; padding: 2px 6px; font-size: 10px;" onclick="clearParticulars(this)" title="Clear">
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="autocomplete-dropdown"></div>
                        </div>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="property_no[]" value="${item.property_no || ''}"></td>
                    <td><input type="number" class="form-control form-control-sm" name="qty[]" value="${item.quantity || '1'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="unit_cost[]" value="${item.unit_cost || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="total_cost[]" value="${item.total_cost || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="accumulated_depreciation[]" value="${item.accumulated_depreciation || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="impairment_losses[]" value="${item.impairment_losses || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="carrying_amount[]" value="${item.carrying_amount || '0'}" step="0.01"></td>
                    <td><input type="text" class="form-control form-control-sm" name="inventory_remarks[]" value="${item.inventory_remarks || ''}"></td>
                    <td><input type="number" class="form-control form-control-sm" name="disposal_sale[]" value="${item.disposal_sale || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="disposal_transfer[]" value="${item.disposal_transfer || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="disposal_destruction[]" value="${item.disposal_destruction || '0'}" step="0.01"></td>
                    <td><input type="text" class="form-control form-control-sm" name="disposal_others[]" value="${item.disposal_others || ''}"></td>
                    <td><input type="number" class="form-control form-control-sm" name="disposal_total[]" value="${item.disposal_total || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="appraised_value[]" value="${item.appraised_value || '0'}" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="total[]" value="${item.total || '0'}" step="0.01"></td>
                    <td><input type="text" class="form-control form-control-sm" name="or_no[]" value="${item.or_no || ''}"></td>
                    <td><input type="number" class="form-control form-control-sm" name="amount[]" value="${item.amount || '0'}" step="0.01"></td>
                    <td>
                        <select class="form-control form-control-sm" name="dept_office[]">
                            <option value="">Select Department/Office</option>
                            ${getOfficeOptions(item.dept_office)}
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="control_no[]" value="${item.control_no || ''}"></td>
                    <td><input type="date" class="form-control form-control-sm" name="date_received[]" value="${item.date_received || ''}"></td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-sm btn-info" onclick="openFillModal(this)" title="Fill Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" onclick="clearRowData(this)" title="Clear Row">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteRow(this)" title="Delete Row">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        function getOfficeOptions(selectedOffice = '') {
            // This would need to fetch from server, for now return empty
            return selectedOffice ? `<option value="${selectedOffice}" selected>${selectedOffice}</option>` : '';
        }
        
        // Initialize autocomplete when page loads
        document.addEventListener('DOMContentLoaded', initAutocomplete);
    </script>
</body>
</html>
