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
$should_auto_fill = false;
$multiple_components = false;

if (isset($_GET['auto_fill']) && $_GET['auto_fill'] === 'true') {
    // Check if multiple components are being sent
    if (isset($_GET['multiple_components']) && $_GET['multiple_components'] === 'true') {
        $multiple_components = true;
        $components_json = $_GET['components'] ?? '[]';
        $auto_fill_data = json_decode($components_json, true) ?: [];
        
        // For multiple components, use the first component's property_no for session tracking
        $first_property_no = isset($auto_fill_data[0]['property_no']) ? $auto_fill_data[0]['property_no'] : '';
        $should_auto_fill = !isset($_SESSION['iirup_auto_fill_completed']) || $_SESSION['iirup_auto_fill_completed'] !== $first_property_no;
        
        if ($should_auto_fill) {
            $_SESSION['iirup_auto_fill_completed'] = $first_property_no;
        }
    } else {
        // Single component handling (existing logic)
        $auto_fill_data = [
            'id' => $_GET['id'] ?? '', // This could be asset_id or peripheral_id
            'asset_id' => $_GET['asset_id'] ?? '', // Asset ID for reference
            'description' => $_GET['description'] ?? '',
            'property_no' => $_GET['property_no'] ?? '',
            'inventory_tag' => $_GET['inventory_tag'] ?? '',
            'acquisition_date' => $_GET['acquisition_date'] ?? '',
            'value' => $_GET['value'] ?? '',
            'unit_cost' => $_GET['unit_cost'] ?? '',
            'office_name' => $_GET['office_name'] ?? '',
            'employee_name' => $_GET['employee_name'] ?? '',
            'category_name' => $_GET['category_name'] ?? '',
            'category_code' => $_GET['category_code'] ?? '',
            'asset_description' => $_GET['asset_description'] ?? '',
            'unit' => $_GET['unit'] ?? '',
            'component_type' => $_GET['component_type'] ?? 'main_asset', // Track which component is being added
            'peripheral_name' => $_GET['peripheral_name'] ?? '',
            'peripheral_model' => $_GET['peripheral_model'] ?? '',
            'peripheral_serial_number' => $_GET['peripheral_serial_number'] ?? '',
            'peripheral_status' => $_GET['peripheral_status'] ?? ''
        ];
        
        // Check if we should auto-fill (not a refresh)
        $should_auto_fill = !isset($_SESSION['iirup_auto_fill_completed']) || $_SESSION['iirup_auto_fill_completed'] !== ($_GET['property_no'] ?? '');
        
        if ($should_auto_fill) {
            $_SESSION['iirup_auto_fill_completed'] = $_GET['property_no'] ?? '';
        }
    }
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
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background: var(--primary-gradient);
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
            color: var(--primary-color);
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
    $page_title = 'IIRUP';
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
                        <i class="bi bi-file-earmark-text"></i> Inventory and Inspection Report of Unserviceable Property
                    </h1>
                    <p class="text-muted mb-0">Manage Inventory and Inspection Report of Unserviceable Property forms</p>
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
            
            <form id="iirupForm" method="POST" action="process_iirup.php" onsubmit="return validateForm()">
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
                                    <input type="text" class="form-control" name="department_office" placeholder="Enter department/office name" required>
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
                                                <th>Date Acquired</th>
                                                <th>Particulars/ Articles</th>
                                                <th>Property No.</th>
                                                <th>Qty</th>
                                                <th>Unit Cost</th>
                                                <th>Total Cost</th>
                                                <th>DEPT/OFFICE</th>
                                                <th>Action</th>
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
                                                    
                                                    <!-- Hidden fields for component data -->
                                                    <input type="hidden" name="component_type[]" value="">
                                                    <input type="hidden" name="peripheral_name[]" value="">
                                                    <input type="hidden" name="peripheral_model[]" value="">
                                                    <input type="hidden" name="peripheral_serial_number[]" value="">
                                                    <input type="hidden" name="peripheral_status[]" value="">
                                                    <input type="hidden" name="asset_id[]" value="">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <!-- Hidden fields for component data -->
                                    <?php if ($multiple_components): ?>
                                    <!-- Multiple components - data will be stored per row -->
                                    <input type="hidden" id="multiple_components" name="multiple_components" value="true">
                                    <?php else: ?>
                                    <!-- Single component - existing hidden fields -->
                                    <input type="hidden" id="id" name="id" value="<?php echo htmlspecialchars($auto_fill_data['id'] ?? ''); ?>">
                                    <input type="hidden" id="asset_id" name="asset_id" value="<?php echo htmlspecialchars($auto_fill_data['asset_id'] ?? ''); ?>">
                                    <input type="hidden" id="component_type" name="component_type[]" value="<?php echo htmlspecialchars($auto_fill_data['component_type'] ?? 'main_asset'); ?>">
                                    <input type="hidden" id="peripheral_name" name="peripheral_name" value="<?php echo htmlspecialchars($auto_fill_data['peripheral_name'] ?? ''); ?>">
                                    <input type="hidden" id="peripheral_model" name="peripheral_model" value="<?php echo htmlspecialchars($auto_fill_data['peripheral_model'] ?? ''); ?>">
                                    <input type="hidden" id="peripheral_serial_number" name="peripheral_serial_number" value="<?php echo htmlspecialchars($auto_fill_data['peripheral_serial_number'] ?? ''); ?>">
                                    <input type="hidden" id="peripheral_status" name="peripheral_status" value="<?php echo htmlspecialchars($auto_fill_data['peripheral_status'] ?? ''); ?>">
                                    <?php endif; ?>
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
    
    <?php if (!empty($auto_fill_data) && $should_auto_fill): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for session data to be loaded first
        setTimeout(function() {
            const table = document.getElementById('iirupItemsTable');
            if (table) {
                const tbody = table.getElementsByTagName('tbody')[0];
                let successMessage = '';
                let successCount = 0;
                
                <?php if ($multiple_components): ?>
                // Handle multiple components
                const components = <?php echo json_encode($auto_fill_data); ?>;
                
                // Clear existing rows first
                while (tbody.rows.length > 1) {
                    tbody.deleteRow(1);
                }
                
                // Add each component as a new row
                components.forEach((component, index) => {
                    let targetRow;
                    
                    if (index === 0) {
                        // Use first row for first component
                        targetRow = tbody.rows[0];
                        // Clear first row if it has data
                        if (!isRowEmpty(targetRow)) {
                            clearRowData(targetRow);
                        }
                    } else {
                        // Add new row for additional components
                        addIIRUPRow();
                        targetRow = tbody.rows[tbody.rows.length - 1];
                    }
                    
                    fillRowWithComponentData(targetRow, component);
                });
                
                successMessage = `Components added successfully! ${components.length} selected components have been added to the form.`;
                successCount = components.length;
                
                <?php else: ?>
                // Handle single component (existing logic)
                // Check if first row is empty or has existing data
                const firstRow = tbody.rows[0];
                const isFirstRowEmpty = isRowEmpty(firstRow);
                
                // Add the new asset to an appropriate row
                if (!isFirstRowEmpty) {
                    // First row has data, add a new row for the new asset
                    addIIRUPRow();
                    const newRow = tbody.rows[tbody.rows.length - 1];
                    fillRowWithComponentData(newRow, <?php echo json_encode($auto_fill_data); ?>);
                } else {
                    // First row is empty, fill it
                    fillRowWithComponentData(firstRow, <?php echo json_encode($auto_fill_data); ?>);
                }
                
                successMessage = `Asset added successfully! "<?php echo addslashes($auto_fill_data['description']); ?>" has been added to the form.`;
                successCount = 1;
                <?php endif; ?>
                
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'alert alert-success alert-dismissible fade show';
                successDiv.innerHTML = `
                    <i class="bi bi-check-circle-fill"></i> 
                    <strong>${successMessage}</strong>
                    <br><small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        ${successCount > 1 ? 
                            'You can modify any field or add more components by typing in the "Particulars/Articles" field below.' : 
                            'To add more assets, type in the "Particulars/Articles" field below and search for additional items. Each new asset will be added to a new row.'
                        }
                    </small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const pageHeader = document.querySelector('.page-header');
                if (pageHeader) {
                    pageHeader.parentNode.insertBefore(successDiv, pageHeader.nextSibling);
                }
                
                // Save the updated data to session storage
                setTimeout(saveFormDataToSession, 100);
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
    
    function fillRowWithComponentData(row, component) {
        // Auto-fill Accountable Officer field if available
        if (component.employee_name) {
            const accountableOfficerInput = document.querySelector('input[name="accountable_officer"]');
            if (accountableOfficerInput) {
                accountableOfficerInput.value = component.employee_name;
                accountableOfficerInput.style.backgroundColor = '#e8f5e8';
                accountableOfficerInput.style.border = '1px solid #28a745';
            }
        }
        
        // Fill the form fields with component data
        if (component.description) {
            const particularsInput = row.querySelector('input[name="particulars[]"]');
            if (particularsInput) {
                particularsInput.value = component.description;
                particularsInput.style.backgroundColor = '#e8f5e8';
                particularsInput.style.border = '1px solid #28a745';
            }
        }
        
        if (component.property_no) {
            const propertyNoInput = row.querySelector('input[name="property_no[]"]');
            if (propertyNoInput) {
                propertyNoInput.value = component.property_no;
                propertyNoInput.style.backgroundColor = '#e8f5e8';
                propertyNoInput.style.border = '1px solid #28a745';
            }
        }
        
        if (component.acquisition_date) {
            const dateAcquiredInput = row.querySelector('input[name="date_acquired[]"]');
            if (dateAcquiredInput) {
                dateAcquiredInput.value = component.acquisition_date;
                dateAcquiredInput.style.backgroundColor = '#e8f5e8';
                dateAcquiredInput.style.border = '1px solid #28a745';
            }
        }
        
        if (component.value || component.unit_cost) {
            const qtyInput = row.querySelector('input[name="qty[]"]');
            if (qtyInput) {
                qtyInput.value = 1;
                qtyInput.style.backgroundColor = '#e8f5e8';
                qtyInput.style.border = '1px solid #28a745';
            }
            
            const unitCostInput = row.querySelector('input[name="unit_cost[]"]');
            if (unitCostInput) {
                unitCostInput.value = component.value || component.unit_cost;
                unitCostInput.style.backgroundColor = '#e8f5e8';
                unitCostInput.style.border = '1px solid #28a745';
            }
            
            const totalCostInput = row.querySelector('input[name="total_cost[]"]');
            if (totalCostInput) {
                totalCostInput.value = component.value || component.unit_cost;
                totalCostInput.style.backgroundColor = '#e8f5e8';
                totalCostInput.style.border = '1px solid #28a745';
            }
        }
        
        if (component.office_name) {
            const deptOfficeSelect = row.querySelector('select[name="dept_office[]"]');
            if (deptOfficeSelect) {
                // Check if option exists, if not add it
                let optionExists = false;
                for (let option of deptOfficeSelect.options) {
                    if (option.value === component.office_name) {
                        optionExists = true;
                        break;
                    }
                }
                if (!optionExists) {
                    const newOption = document.createElement('option');
                    newOption.value = component.office_name;
                    newOption.textContent = component.office_name;
                    deptOfficeSelect.appendChild(newOption);
                }
                deptOfficeSelect.value = component.office_name;
                deptOfficeSelect.style.backgroundColor = '#e8f5e8';
                deptOfficeSelect.style.border = '1px solid #28a745';
            }
        }
        
        // Store component type and peripheral data in hidden fields
        if (component.component_type) {
            const componentTypeInput = row.querySelector('input[name="component_type[]"]');
            if (componentTypeInput) {
                componentTypeInput.value = component.component_type;
            }
        }
        
        // Store asset_id (for both main assets and peripherals)
        if (component.id) {
            const assetIdInput = row.querySelector('input[name="asset_id[]"]');
            if (assetIdInput) {
                assetIdInput.value = component.id;
            }
        }
        
        if (component.peripheral_name) {
            const peripheralNameInput = row.querySelector('input[name="peripheral_name[]"]');
            if (peripheralNameInput) {
                peripheralNameInput.value = component.peripheral_name;
            }
        }
        
        if (component.peripheral_model) {
            const peripheralModelInput = row.querySelector('input[name="peripheral_model[]"]');
            if (peripheralModelInput) {
                peripheralModelInput.value = component.peripheral_model;
            }
        }
        
        if (component.peripheral_serial_number) {
            const peripheralSerialInput = row.querySelector('input[name="peripheral_serial_number[]"]');
            if (peripheralSerialInput) {
                peripheralSerialInput.value = component.peripheral_serial_number;
            }
        }
        
        if (component.peripheral_status) {
            const peripheralStatusInput = row.querySelector('input[name="peripheral_status[]"]');
            if (peripheralStatusInput) {
                peripheralStatusInput.value = component.peripheral_status;
            }
        }
    }
    
    // Auto-fill main department_office field if office_name is provided
    <?php if (!$multiple_components && !empty($auto_fill_data['office_name'])): ?>
    const mainDeptOfficeInput = document.querySelector('input[name="department_office"]');
    if (mainDeptOfficeInput) {
        mainDeptOfficeInput.value = '<?php echo addslashes($auto_fill_data['office_name']); ?>';
        mainDeptOfficeInput.style.backgroundColor = '#e8f5e8';
        mainDeptOfficeInput.style.border = '1px solid #28a745';
    }
    <?php endif; ?>
    
    // Asset search functionality with desktop computer specifications
    let currentSearchTimeout;
    
    document.addEventListener('input', function(e) {
        if (e.target.name === 'particulars[]') {
            clearTimeout(currentSearchTimeout);
            const searchInput = e.target;
            const dropdown = searchInput.parentElement.querySelector('.autocomplete-dropdown');
            
            currentSearchTimeout = setTimeout(() => {
                const query = searchInput.value.trim();
                
                if (query.length < 2) {
                    dropdown.style.display = 'none';
                    return;
                }
                
                fetch('api/search_assets.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Search error:', data.error);
                            return;
                        }
                        
                        dropdown.innerHTML = '';
                        
                        if (data.length === 0) {
                            dropdown.innerHTML = '<div class="autocomplete-item">No assets found</div>';
                        } else {
                            data.forEach(asset => {
                                const item = document.createElement('div');
                                item.className = 'autocomplete-item';
                                item.innerHTML = `
                                    <strong>${asset.display_text}</strong>
                                    <small>
                                        Property No: ${asset.property_no || 'N/A'} | 
                                        Inventory Tag: ${asset.inventory_tag || 'N/A'} | 
                                        Office: ${asset.office_name || 'N/A'}
                                        ${asset.employee_name ? ' | Employee: ' + asset.employee_name : ''}
                                    </small>
                                `;
                                
                                item.addEventListener('click', function() {
                                    selectAsset(searchInput, asset);
                                    dropdown.style.display = 'none';
                                });
                                
                                dropdown.appendChild(item);
                            });
                        }
                        
                        dropdown.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                    });
            }, 300);
        }
    });
    
    function selectAsset(input, asset) {
        // Find the current row
        const row = input.closest('tr');
        
        // Fill the row with asset data
        input.value = asset.display_text;
        
        // Fill other fields in the row
        const propertyNoInput = row.querySelector('input[name="property_no[]"]');
        if (propertyNoInput) {
            propertyNoInput.value = asset.property_no || '';
        }
        
        const qtyInput = row.querySelector('input[name="qty[]"]');
        if (qtyInput) {
            qtyInput.value = 1;
        }
        
        const unitCostInput = row.querySelector('input[name="unit_cost[]"]');
        if (unitCostInput) {
            unitCostInput.value = asset.value || 0;
        }
        
        const totalCostInput = row.querySelector('input[name="total_cost[]"]');
        if (totalCostInput) {
            totalCostInput.value = asset.value || 0;
        }
        
        const dateAcquiredInput = row.querySelector('input[name="date_acquired[]"]');
        if (dateAcquiredInput && asset.acquisition_date) {
            dateAcquiredInput.value = asset.acquisition_date;
        }
        
        const deptOfficeSelect = row.querySelector('select[name="dept_office[]"]');
        if (deptOfficeSelect && asset.office_name) {
            // Check if option exists, if not add it
            let optionExists = false;
            for (let option of deptOfficeSelect.options) {
                if (option.value === asset.office_name) {
                    optionExists = true;
                    break;
                }
            }
            
            if (!optionExists) {
                const newOption = document.createElement('option');
                newOption.value = asset.office_name;
                newOption.textContent = asset.office_name;
                deptOfficeSelect.appendChild(newOption);
            }
            
            deptOfficeSelect.value = asset.office_name;
        }
        
        // Store asset data for potential use
        input.dataset.assetId = asset.id;
        input.dataset.assetData = JSON.stringify(asset);
        
        // Highlight the filled fields
        const inputs = row.querySelectorAll('input, select');
        inputs.forEach(inp => {
            if (inp.value) {
                inp.style.backgroundColor = '#e8f5e8';
                inp.style.border = '1px solid #28a745';
            }
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-container')) {
            document.querySelectorAll('.autocomplete-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
    
    // Keyboard navigation for autocomplete
    document.addEventListener('keydown', function(e) {
        if (e.target.name === 'particulars[]') {
            const dropdown = e.target.parentElement.querySelector('.autocomplete-dropdown');
            const items = dropdown.querySelectorAll('.autocomplete-item');
            let selectedIndex = -1;
            
            // Find current selected item
            items.forEach((item, index) => {
                if (item.classList.contains('selected')) {
                    selectedIndex = index;
                }
            });
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelection(items, selectedIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
                updateSelection(items, selectedIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        }
    });
    
    function updateSelection(items, selectedIndex) {
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === selectedIndex);
        });
    }
    
    function clearParticulars(button) {
        const container = button.parentElement;
        const input = container.querySelector('input[name="particulars[]"]');
        const dropdown = container.querySelector('.autocomplete-dropdown');
        
        if (input) {
            input.value = '';
            input.style.backgroundColor = '';
            input.style.border = '';
            delete input.dataset.assetId;
            delete input.dataset.assetData;
        }
        
        dropdown.style.display = 'none';
        
        // Clear other fields in the same row
        const row = container.closest('tr');
        const otherInputs = row.querySelectorAll('input:not([name="particulars[]"]), select');
        otherInputs.forEach(inp => {
            if (inp.type !== 'hidden') {
                inp.value = '';
                inp.style.backgroundColor = '';
                inp.style.border = '';
            }
        });
    }
    
    // Add missing validateForm function
    function validateForm() {
        // Simple validation - just return true for now
        console.log('Form validation passed');
        return true;
    }
    
    // Function to update peripheral statuses
    function updatePeripheralStatuses() {
        if (confirm('Update peripheral statuses to unserviceable? This will update all monitor, UPS, and other peripheral components in the form.')) {
            // Create a hidden form to submit to peripheral update processor
            const form = document.getElementById('iirupForm');
            const formData = new FormData(form);
            
            // Submit to peripheral update processor
            fetch('process_peripheral_updates.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Peripheral update response:', data);
                // Reload the page to see the result
                window.location.reload();
            })
            .catch(error => {
                console.error('Error updating peripherals:', error);
                alert('Error updating peripheral statuses: ' + error);
            });
        }
    }
</script>
    <?php endif; ?>
</body>
</html>
