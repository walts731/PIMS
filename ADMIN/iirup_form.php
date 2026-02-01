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

// Handle auto-fill data from view_asset_item.php
$auto_fill_data = [];
if (isset($_GET['auto_fill']) && $_GET['auto_fill'] === 'true') {
    $auto_fill_data = [
        'asset_id' => $_GET['asset_id'] ?? '',
        'description' => $_GET['description'] ?? '',
        'property_no' => $_GET['property_no'] ?? '',
        'inventory_tag' => $_GET['inventory_tag'] ?? '',
        'acquisition_date' => $_GET['acquisition_date'] ?? '',
        'value' => $_GET['value'] ?? '',
        'unit_cost' => $_GET['unit_cost'] ?? '',
        'office_name' => $_GET['office_name'] ?? '',
        'category_name' => $_GET['category_name'] ?? '',
        'category_code' => $_GET['category_code'] ?? '',
        'asset_description' => $_GET['asset_description'] ?? '',
        'unit' => $_GET['unit'] ?? ''
    ];
}

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

// Get latest IIRUP form record for auto-populating header and footer
$latest_iirup = null;
$result = $conn->query("SELECT * FROM iirup_forms ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $latest_iirup = $row;
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
                                    <input type="text" class="form-control" name="accountable_officer" placeholder="Enter name of accountable officer" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" style="font-weight: normal; margin-bottom: 5px;">Designation:</label>
                                    <input type="text" class="form-control" name="designation" placeholder="Enter designation" required>
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
                                                <td><input type="text" class="form-control form-control-sm" name="inventory_remarks[]" value="unserviceable"></td>
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
                            <input type="text" class="form-control form-control-sm mb-2" name="accountable_officer_name" placeholder="Signature over Printed Name of Accountable Officer" value="<?php echo htmlspecialchars($latest_iirup['accountable_officer_name'] ?? ''); ?>">
                            <input type="text" class="form-control form-control-sm mb-2" name="accountable_officer_designation" placeholder="Designation of Accountable Officer" value="<?php echo htmlspecialchars($latest_iirup['accountable_officer_designation'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Approved by:</p>
                        <div class="signature-block">
                            <input type="text" class="form-control form-control-sm mb-2" name="authorized_official_name" placeholder="Signature over Printed Name of Authorized Official" value="<?php echo htmlspecialchars($latest_iirup['authorized_official_name'] ?? ''); ?>">
                            <input type="text" class="form-control form-control-sm mb-2" name="authorized_official_designation" placeholder="Designation of Authorized Official" value="<?php echo htmlspecialchars($latest_iirup['authorized_official_designation'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">
                        <p>I CERTIFY that I have inspected each and every article enumerated in this report, and that disposition made thereof was, in my judgment, best for public interest.</p>
                        <div class="signature-block">
                            <input type="text" class="form-control form-control-sm mb-2" name="inspection_officer_name" placeholder="Signature over Printed Name of Inspection Officer" value="<?php echo htmlspecialchars($latest_iirup['inspection_officer_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>I CERTIFY that I have witnessed disposition of articles enumerated on this report this _____ day of _____.</p>
                        <div class="signature-block">
                            <input type="text" class="form-control form-control-sm mb-2" name="witness_name" placeholder="Signature over Printed Name of Witness" value="<?php echo htmlspecialchars($latest_iirup['witness_name'] ?? ''); ?>">
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
    <?php include 'includes/iirup_modals.php'; ?>
    
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/iirup_form.js?v=<?php echo time(); ?>"></script>
    
    <?php if (!empty($auto_fill_data)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for session data to be loaded first
        setTimeout(function() {
            const table = document.getElementById('iirupItemsTable');
            if (table) {
                const tbody = table.getElementsByTagName('tbody')[0];
                
                // Check if first row is empty or has existing data
                const firstRow = tbody.rows[0];
                const isFirstRowEmpty = isRowEmpty(firstRow);
                
                // Add the new asset to an appropriate row
                if (!isFirstRowEmpty) {
                    // First row has data, add a new row for the new asset
                    addIIRUPRow();
                    const newRow = tbody.rows[tbody.rows.length - 1];
                    fillRowWithAutoFillData(newRow);
                } else {
                    // First row is empty, fill it
                    fillRowWithAutoFillData(firstRow);
                }
                
                // Save the updated data to session storage
                setTimeout(saveFormDataToSession, 100);
                
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'alert alert-success alert-dismissible fade show';
                successDiv.innerHTML = `
                    <i class="bi bi-check-circle-fill"></i> 
                    <strong>Asset added successfully!</strong> "<?php echo addslashes($auto_fill_data['description']); ?>" has been added to the form.
                    <br><small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        To add more assets, type in the "Particulars/Articles" field below and search for additional items. 
                        Each new asset will be added to a new row.
                    </small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const pageHeader = document.querySelector('.page-header');
                if (pageHeader) {
                    pageHeader.parentNode.insertBefore(successDiv, pageHeader.nextSibling);
                }
            }
        }, 200); // Small delay to ensure session data is loaded
    });
    
    function isRowEmpty(row) {
        const particularsInput = row.querySelector('input[name="particulars[]"]');
        const propertyNoInput = row.querySelector('input[name="property_no[]"]');
        const qtyInput = row.querySelector('input[name="qty[]"]');
        
        return (!particularsInput || !particularsInput.value.trim()) && 
               (!propertyNoInput || !propertyNoInput.value.trim()) && 
               (!qtyInput || !qtyInput.value);
    }
    
    function fillRowWithAutoFillData(row) {
        // Fill the form fields with auto-fill asset data
        <?php if (!empty($auto_fill_data['description'])): ?>
        const particularsInput = row.querySelector('input[name="particulars[]"]');
        if (particularsInput) {
            particularsInput.value = '<?php echo addslashes($auto_fill_data['description']); ?>';
            particularsInput.style.backgroundColor = '#e8f5e8';
            particularsInput.style.border = '1px solid #28a745';
        }
        <?php endif; ?>
        
        <?php if (!empty($auto_fill_data['property_no'])): ?>
        const propertyNoInput = row.querySelector('input[name="property_no[]"]');
        if (propertyNoInput) {
            propertyNoInput.value = '<?php echo addslashes($auto_fill_data['property_no']); ?>';
            propertyNoInput.style.backgroundColor = '#e8f5e8';
            propertyNoInput.style.border = '1px solid #28a745';
        }
        <?php endif; ?>
        
        <?php if (!empty($auto_fill_data['acquisition_date'])): ?>
        const dateAcquiredInput = row.querySelector('input[name="date_acquired[]"]');
        if (dateAcquiredInput) {
            dateAcquiredInput.value = '<?php echo $auto_fill_data['acquisition_date']; ?>';
            dateAcquiredInput.style.backgroundColor = '#e8f5e8';
            dateAcquiredInput.style.border = '1px solid #28a745';
        }
        <?php endif; ?>
        
        <?php if (!empty($auto_fill_data['value'])): ?>
        const qtyInput = row.querySelector('input[name="qty[]"]');
        if (qtyInput) {
            qtyInput.value = 1;
            qtyInput.style.backgroundColor = '#e8f5e8';
            qtyInput.style.border = '1px solid #28a745';
        }
        
        const unitCostInput = row.querySelector('input[name="unit_cost[]"]');
        if (unitCostInput) {
            unitCostInput.value = '<?php echo $auto_fill_data['value']; ?>';
            unitCostInput.style.backgroundColor = '#e8f5e8';
            unitCostInput.style.border = '1px solid #28a745';
        }
        
        const totalCostInput = row.querySelector('input[name="total_cost[]"]');
        if (totalCostInput) {
            totalCostInput.value = '<?php echo $auto_fill_data['value']; ?>';
            totalCostInput.style.backgroundColor = '#e8f5e8';
            totalCostInput.style.border = '1px solid #28a745';
        }
        <?php endif; ?>
        
        <?php if (!empty($auto_fill_data['office_name'])): ?>
        const deptOfficeSelect = row.querySelector('select[name="dept_office[]"]');
        if (deptOfficeSelect) {
            // Check if option exists, if not add it
            let optionExists = false;
            for (let option of deptOfficeSelect.options) {
                if (option.value === '<?php echo addslashes($auto_fill_data['office_name']); ?>') {
                    optionExists = true;
                    break;
                }
            }
            if (!optionExists) {
                const newOption = document.createElement('option');
                newOption.value = '<?php echo addslashes($auto_fill_data['office_name']); ?>';
                newOption.textContent = '<?php echo addslashes($auto_fill_data['office_name']); ?>';
                deptOfficeSelect.appendChild(newOption);
            }
            deptOfficeSelect.value = '<?php echo addslashes($auto_fill_data['office_name']); ?>';
        }
        <?php endif; ?>
    }
    </script>
    <?php endif; ?>
</body>
</html>
