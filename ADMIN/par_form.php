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

// Log page access
logSystemAction($_SESSION['user_id'], 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php');

// Get data for dropdowns
$funds_result = $conn->query("SELECT fund_code, fund_name, fund_cluster FROM funds WHERE status = 'active' ORDER BY fund_code");
$categories_result = $conn->query("SELECT category_code, category_name FROM asset_categories WHERE status = 'active' ORDER BY category_code");
$subcategories_result = $conn->query("SELECT sc.sub_category_code, sc.sub_category_name, ac.category_code FROM asset_sub_categories sc JOIN asset_categories ac ON sc.asset_categories_id = ac.id WHERE sc.status = 'active' ORDER BY ac.category_code, sc.sub_category_code");
$offices_result = $conn->query("SELECT office_code, office_name FROM offices WHERE status = 'active' ORDER BY office_code");

// Get next PAR series number
$next_par_series = '0001';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(par_no, -4, 4) AS UNSIGNED)) as max_series FROM par_forms WHERE par_no LIKE '%P-%' AND par_no REGEXP 'P-[0-9]{4}-[0-9]{2}-[0-9]{4}$'");
if ($result && $row = $result->fetch_assoc()) {
    $max_series = $row['max_series'];
    if ($max_series) {
        $next_par_series = str_pad($max_series + 1, 4, '0', STR_PAD_LEFT);
    }
}

// Get next series number for auto-increment
$next_series = '01';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(property_number, -4, 2) AS UNSIGNED)) as max_series FROM asset_items WHERE property_number LIKE CONCAT(YEAR(CURDATE()), '-%')");
if ($result && $row = $result->fetch_assoc()) {
    $max_series = $row['max_series'];
    if ($max_series) {
        $next_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
    }
}

// Form type options with codes
$form_options = [
    'PAR' => ['code' => '07', 'name' => 'Property Acknowledgment Receipt'],
    'ICS' => ['code' => '04', 'name' => 'Inventory Custodian Slip'],
    'RIS' => ['code' => '02', 'name' => 'Requisition and Issue Slip'],
    'IIRUP' => ['code' => '06', 'name' => 'Inventory and Inspection Report'],
    'ITR' => ['code' => '08', 'name' => 'Inventory Transfer Request']
];

// Get PAR configuration for JavaScript - COMMENTED OUT FOR MANUAL INPUT
// $par_config = null;
// $result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'par_no' AND status = 'active'");
// if ($result && $row = $result->fetch_assoc()) {
//     $par_config = $row;
// }
$par_config = null; // Disabled for manual input

// Get Property Number configuration for JavaScript - COMMENTED OUT FOR MANUAL INPUT
// $property_config = null;
// $result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'property_no' AND status = 'active'");
// if ($result && $row = $result->fetch_assoc()) {
//     $property_config = $row;
// }
$property_config = null; // Disabled for manual input

// Generate initial property number for display - COMMENTED OUT FOR MANUAL INPUT
// $initial_property_number = '';
// if ($property_config) {
//     $current_number = $property_config['current_number'];
//     $next_number = $current_number + 1;
//     
//     // Build the property number from components
//     $components = json_decode($property_config['format_components'], true);
//     // Handle double-encoded JSON
//     if (is_string($components)) {
//         $components = json_decode($components, true);
//     }
//     
//     if (is_array($components) && !empty($components)) {
//         $parts = [];
//         
//         foreach ($components as $component) {
//             switch($component['type']) {
//                 case 'text':
//                     $parts[] = $component['value'] ?? '';
//                     break;
//                 case 'digits':
//                     $component_digits = $component['digits'] ?? $property_config['digits'] ?? 4;
//                     $number = str_pad($next_number, $component_digits, '0', STR_PAD_LEFT);
//                     $parts[] = $number;
//                     break;
//                 case 'year':
//                     $parts[] = date('Y');
//                     break;
//                 case 'month':
//                     $parts[] = date('m');
//                     break;
//             }
//         }
//         
//         $initial_property_number = implode($property_config['separator'] ?? '', $parts);
//     }
// }
$initial_property_number = ''; // Empty for manual input

// Common units for dropdown
$common_units = [
    'Pieces',
    'Sets',
    'Units',
    'Boxes',
    'Cartons',
    'Packs',
    'Bottles',
    'Liters',
    'Gallons',
    'Kilograms',
    'Grams',
    'Meters',
    'Centimeters',
    'Feet',
    'Inches',
    'Dozens',
    'Pairs',
    'Rolls',
    'Bags',
    'Canisters',
    'Jars',
    'Tubes',
    'Reams'
];

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'PAR'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

// Get latest signature data from the most recent PAR form
$latest_signature = [];
$result = $conn->query("SELECT received_by_name, received_by_position, issued_by_name, issued_by_position FROM par_forms ORDER BY created_at DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $latest_signature = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Acknowledgment Receipt - PIMS</title>
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
        
        .nav-tabs .nav-link {
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border: none;
            transition: var(--transition);
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        @media print {
            .no-print { display: none !important; }
            .form-card { box-shadow: none; }
        }
        
        .property-number-field {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .property-number-field input {
            flex: 1;
        }
        
        .property-number-field .btn {
            flex-shrink: 0;
        }
        
        .generator-modal .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .generator-modal .card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
        }
        
        .generator-modal #propertyNumberPreview {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Property Acknowledgment Receipt';
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
                        <i class="bi bi-file-earmark-text"></i> Property Acknowledgment Receipt
                    </h1>
                    <p class="text-muted mb-0">Manage Property Acknowledgment Receipt forms</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-secondary btn-sm" onclick="viewPAREntries()">
                        <i class="bi bi-list"></i> View Entries
                    </button>
                </div>
            </div>
        </div>

        <?php 
        // Display success/error messages
        if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

                <!-- PAR Form Management -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> PAR Form
                </h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="resetForm()">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </button>
            </div>
            
            <form id="parForm" method="POST" action="process_par.php">
                <!-- PAR Form Header -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php 
                    if (!empty($header_image)) {
                        echo '<div style="margin-bottom: 10px;">';
                        echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 120px; object-fit: contain;">';
                        echo '</div>';
                    }
                    ?>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Office/Location:</strong></label>
                                        <select class="form-select" name="office_location" required>
                                            <option value="">Select Office</option>
                                            <?php
                                            // Get offices from database
                                            $offices_result = $conn->query("SELECT office_code, office_name FROM offices WHERE status = 'active' ORDER BY office_code");
                                            if ($offices_result) {
                                                while ($office = $offices_result->fetch_assoc()) {
                                                    echo '<option value="' . htmlspecialchars($office['office_code']) . '">' . htmlspecialchars($office['office_name']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Entity Name and Fund Cluster -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label"><strong>Entity Name:</strong></label>
                                <input type="text" class="form-control" name="entity_name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><strong>Fund Cluster:</strong></label>
                                <input type="text" class="form-control" name="fund_cluster" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><strong>PAR No:</strong></label>
                                <input type="text" class="form-control" name="par_no" id="par_no" value="" readonly placeholder="Auto-generated when office is selected">
                                <small class="text-muted">Format: OfficeP-Year-Month-Series (e.g., OMMP-2026-02-0001)</small>
                            </div>
                        </div>
                        
                        <!-- Items Table -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Items:</strong></label>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Description</th>
                                            <th>Property Number</th>
                                            <th>Date Acquired</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateAmount(this)"></td>
                                            <td>
                                                <select class="form-select form-select-sm" name="unit[]" required>
                                                    <option value="">Select Unit</option>
                                                    <?php foreach ($common_units as $unit): ?>
                                                        <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control form-control-sm" name="description[]" required></td>
                                            <td>
                                                <div class="property-number-field">
                                                    <input type="text" class="form-control form-control-sm" name="property_number[]" id="initialPropertyNumber" value="" readonly placeholder="Click 'Generate' to create property number">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number">
                                                        <i class="bi bi-gear"></i> Generate
                                                    </button>
                                                </div>
                                                <small class="text-muted">Format: YEAR-FORM-FUND-CATEGORY-SUBCATEGORY+SERIES-OFFICE</small>
                                            </td>
                                            <td><input type="date" class="form-control form-control-sm" name="date_acquired[]"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" required onchange="updateGrandTotal()"></td>
                                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary fw-bold">
                                            <td colspan="5" class="text-end">Grand Total:</td>
                                            <td id="grandTotal">0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addRow()">
                                <i class="bi bi-plus-circle"></i> Add Row
                            </button>
                        </div>
                        
                        <!-- Signature Section -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Received by:</strong></label>
                                <input type="text" class="form-control" name="received_by" required value="<?php echo htmlspecialchars($latest_signature['received_by_name'] ?? ''); ?>">
                                <label class="form-label mt-2"><strong>Position:</strong></label>
                                <input type="text" class="form-control" name="received_by_position" value="<?php echo htmlspecialchars($latest_signature['received_by_position'] ?? ''); ?>">
                                <label class="form-label mt-2"><strong>Date:</strong></label>
                                <input type="date" class="form-control" name="received_by_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>Issued by:</strong></label>
                                <input type="text" class="form-control" name="issued_by" required value="<?php echo htmlspecialchars($latest_signature['issued_by_name'] ?? ''); ?>">
                                <label class="form-label mt-2"><strong>Position:</strong></label>
                                <input type="text" class="form-control" name="issued_by_position" value="<?php echo htmlspecialchars($latest_signature['issued_by_position'] ?? ''); ?>">
                                <label class="form-label mt-2"><strong>Date:</strong></label>
                                <input type="date" class="form-control" name="issued_by_date">
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save PAR
                            </button>
                        </div>
                    </form>
                </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    
    <!-- Property Number Generator Modal -->
    <div class="modal fade generator-modal" id="propertyNumberGeneratorModal" tabindex="-1" aria-labelledby="propertyNumberGeneratorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="propertyNumberGeneratorModalLabel">
                        <i class="bi bi-gear"></i> Property Number Generator
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" id="quantityInfo">
                        <i class="bi bi-info-circle"></i> <span id="quantityText">Generating 1 property number</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Form Type:</strong></label>
                            <input type="text" class="form-control" id="formType" value="07" readonly>
                            <small class="text-muted">Auto-detected: Property Acknowledgment Receipt</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Fund:</strong></label>
                            <select class="form-select" id="fundSelect">
                                <option value="">Select Fund</option>
                                <?php 
                                if ($funds_result) {
                                    // Reset pointer to beginning
                                    $funds_result->data_seek(0);
                                    while ($fund = $funds_result->fetch_assoc()) {
                                        // Extract numeric part from fund code (e.g., "05" from "GEN-2025")
                                        preg_match('/(\d{2})$/', $fund['fund_code'], $matches);
                                        $fund_code = isset($matches[1]) ? $matches[1] : '05';
                                        echo '<option value="' . $fund_code . '">' . htmlspecialchars($fund['fund_name']) . ' (' . htmlspecialchars($fund['fund_code']) . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Asset Category:</strong></label>
                            <select class="form-select" id="categorySelect">
                                <option value="">Select Category</option>
                                <?php 
                                if ($categories_result) {
                                    while ($category = $categories_result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($category['category_code']) . '">' . htmlspecialchars($category['category_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Asset Subcategory:</strong></label>
                            <select class="form-select" id="subcategorySelect">
                                <option value="">Select Subcategory</option>
                                <?php 
                                if ($subcategories_result) {
                                    while ($subcategory = $subcategories_result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($subcategory['sub_category_code']) . '" data-category="' . htmlspecialchars($subcategory['category_code']) . '">' . htmlspecialchars($subcategory['sub_category_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Series (Auto-increment):</strong></label>
                            <input type="text" class="form-control" id="seriesInput" value="<?php echo $next_series; ?>" readonly>
                            <small class="text-muted">Auto-generated next available series number</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Office:</strong></label>
                            <select class="form-select" id="officeSelect">
                                <option value="">Select Office</option>
                                <?php 
                                if ($offices_result) {
                                    // Reset pointer to beginning
                                    $offices_result->data_seek(0);
                                    while ($office = $offices_result->fetch_assoc()) {
                                        $selected = ($office['office_code'] == '01') ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($office['office_code']) . '" ' . $selected . '>' . htmlspecialchars($office['office_name']) . ' (' . htmlspecialchars($office['office_code']) . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-eye"></i> Preview</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 id="propertyNumberPreview" class="text-primary mb-0">-</h4>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="button" class="btn btn-success" onclick="generatePropertyNumberPreview()">
                                            <i class="bi bi-arrow-clockwise"></i> Generate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyPropertyNumber()">
                        <i class="bi bi-check-circle"></i> Apply Property Number
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuration from PHP - COMMENTED OUT FOR MANUAL INPUT
        // const parConfig = <?php echo json_encode($par_config); ?>;
        // const propertyConfig = <?php echo json_encode($property_config); ?>;
        const parConfig = null; // Disabled for manual input
        const propertyConfig = null; // Disabled for manual input
        
        function generatePropertyNumber() {
            // COMMENTED OUT FOR MANUAL INPUT
            // if (!propertyConfig) return '';
            // 
            // // Get current number and increment it
            // const currentNumber = propertyConfig.current_number || 0;
            // const nextNumber = currentNumber + 1;
            // 
            // // Build the property number from components
            // const components = JSON.parse(propertyConfig.format_components || '[]');
            // const parts = [];
            // 
            // components.forEach(component => {
            //     switch(component.type) {
            //         case 'text':
            //             parts.push(component.value || '');
            //             break;
            //         case 'digits':
            //             const digits = component.digits || propertyConfig.digits || 4;
            //             const number = String(nextNumber).padStart(digits, '0');
            //             parts.push(number);
            //             break;
            //         case 'year':
            //             parts.push(new Date().getFullYear());
            //             break;
            //         case 'month':
            //             parts.push(String(new Date().getMonth() + 1).padStart(2, '0'));
            //             break;
            //     }
            // });
            // 
            // return parts.join(propertyConfig.separator || '');
            return ''; // Return empty for manual input
        }
        
        function addRow() {
            const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            // Create unit dropdown HTML
            const unitOptions = <?php 
                $options = '<option value="">Select Unit</option>';
                foreach ($common_units as $unit) {
                    $options .= '<option value="' . htmlspecialchars($unit) . '">' . htmlspecialchars($unit) . '</option>';
                }
                echo json_encode($options);
            ?>;
            
            // Generate auto property number for new row - COMMENTED OUT FOR MANUAL INPUT
            // const autoPropertyNumber = generatePropertyNumber();
            const autoPropertyNumber = ''; // Empty for manual input
            
            const cells = [
                '<input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateAmount(this)">',
                '<select class="form-select form-select-sm" name="unit[]" required>' + unitOptions + '</select>',
                '<input type="text" class="form-control form-control-sm" name="description[]" required>',
                '<div class="property-number-field">' +
                '<input type="text" class="form-control form-control-sm" name="property_number[]" value="" readonly placeholder="Click Generate to create">' +
                '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number"><i class="bi bi-gear"></i> Generate</button>' +
                '</div>' +
                '<small class="text-muted">Format: YEAR-FORM-FUND-CATEGORY-SUBCATEGORY+SERIES-OFFICE</small>',
                '<input type="date" class="form-control form-control-sm" name="date_acquired[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" required onchange="updateGrandTotal()">',
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button>'
            ];
            
            for (let i = 0; i < cells.length; i++) {
                const cell = newRow.insertCell(i);
                cell.innerHTML = cells[i];
            }
            
            // Add amount listeners to the new row
            addAmountListeners();
        }
        
        // Initialize the form with auto-generated property numbers - COMMENTED OUT FOR MANUAL INPUT
        function initializeForm() {
            // Set initial property number - COMMENTED OUT
            // const initialPropertyField = document.getElementById('initialPropertyNumber');
            // if (initialPropertyField && !initialPropertyField.value) {
            //     initialPropertyField.value = generatePropertyNumber();
            // }
            // No initialization needed for manual input
        }
        
        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            addAmountListeners();
            updateGrandTotal(); // Initialize grand total
        });
        
        function removeRow(button) {
            const row = button.closest('tr');
            const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
            
            if (table.rows.length > 1) {
                row.remove();
                // Update grand total after removing row
                updateGrandTotal();
            } else {
                alert('At least one row is required');
            }
        }
        
        function updateGrandTotal() {
            const amountInputs = document.querySelectorAll('input[name="amount[]"]');
            let grandTotal = 0;
            
            amountInputs.forEach(input => {
                const amount = parseFloat(input.value) || 0;
                grandTotal += amount;
            });
            
            // Update the grand total display
            const grandTotalElement = document.getElementById('grandTotal');
            if (grandTotalElement) {
                grandTotalElement.textContent = grandTotal.toFixed(2);
            }
        }
        
        function calculateAmount(input) {
            // Since amount is now directly entered, we don't need to calculate it
            // This function can be used for validation if needed
            const row = input.closest('tr');
            const quantity = row.querySelector('input[name="quantity[]"]').value || 0;
            const amount = row.querySelector('input[name="amount[]"]').value || 0;
            
            // Optional: Validate that amount is reasonable
            if (parseFloat(quantity) < 0) {
                row.querySelector('input[name="quantity[]"]').value = 0;
            }
            if (parseFloat(amount) < 0) {
                row.querySelector('input[name="amount[]"]').value = 0;
            }
        }
        
        // Function to format amount with .00
        function formatAmount(input) {
            let value = input.value.replace(/[^\d.]/g, '');
            
            // Remove multiple decimal points
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Format to 2 decimal places
            if (value && !isNaN(value)) {
                const numValue = parseFloat(value);
                input.value = numValue.toFixed(2);
            } else if (value === '') {
                input.value = '';
            }
        }
        
        // Add event listeners to amount inputs
        function addAmountListeners() {
            const amountInputs = document.querySelectorAll('input[name="amount[]"]');
            amountInputs.forEach(input => {
                // Format on blur (when leaving the field)
                input.addEventListener('blur', function() {
                    formatAmount(this);
                    updateGrandTotal();
                });
                
                // Format on change
                input.addEventListener('change', function() {
                    formatAmount(this);
                    updateGrandTotal();
                });
                
                // Allow only numbers and decimal point during typing
                input.addEventListener('input', function(e) {
                    let value = e.target.value;
                    // Allow only digits and one decimal point
                    const cursorPos = e.target.selectionStart;
                    const beforeCursor = value.substring(0, cursorPos);
                    const afterCursor = value.substring(cursorPos);
                    
                    // Count decimal points before cursor
                    const decimalPointsBefore = (beforeCursor.match(/\./g) || []).length;
                    
                    // If this is a decimal point and there's already one, prevent it
                    if (e.data === '.' && decimalPointsBefore >= 1) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Remove non-numeric characters except decimal point
                    value = value.replace(/[^\d.]/g, '');
                    e.target.value = value;
                });
            });
        }
        
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
                document.getElementById('parForm').reset();
                // Reset to single row
                const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
                while (table.rows.length > 1) {
                    table.deleteRow(1);
                }
                // Reset grand total
                const grandTotalElement = document.getElementById('grandTotal');
                if (grandTotalElement) {
                    grandTotalElement.textContent = '0.00';
                }
            }
        }
        
        function viewPAREntries() {
            // Redirect to PAR entries page or open a modal
            // For now, let's redirect to a dedicated PAR entries page
            window.location.href = 'par_entries.php';
        }
        
        // Generate new PAR number via AJAX - COMMENTED OUT FOR MANUAL INPUT
        function generateNewParNumber() {
            // COMMENTED OUT - Auto-generation disabled
            // <?php if ($par_config): ?>
            // const components = <?php 
            //     $components = json_decode($par_config['format_components'], true);
            //     if (is_string($components)) {
            //         $components = json_decode($components, true);
            //     }
            //     echo json_encode($components ?: []);
            // ?>;
            // const digits = <?php echo $par_config['digits']; ?>;
            // const separator = '<?php echo $par_config['separator']; ?>';
            // 
            // fetch('../SYSTEM_ADMIN/tags.php', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/x-www-form-urlencoded',
            //     },
            //     body: 'action=generate_preview&tag_type=par_no&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.preview) {
            //         document.getElementById('par_no').value = data.preview;
            //     }
            // })
            // .catch(error => {
            //     console.error('Error generating PAR number:', error);
            // });
            // <?php endif; ?>
            
            // Auto-generation disabled - do nothing
        }
        
        // Handle form submission to update counter - COMMENTED OUT FOR MANUAL INPUT
        document.getElementById('parForm').addEventListener('submit', function(e) {
            // COMMENTED OUT - Auto-generation disabled
            // // Always increment counter since field is always auto-generated
            // const incrementField = document.createElement('input');
            // incrementField.type = 'hidden';
            // incrementField.name = 'increment_par_counter';
            // incrementField.value = '1';
            // this.appendChild(incrementField);
            
            // No counter increment needed for manual input
        });
        
        // Property Number Generator Functions
        let currentPropertyField = null;
        let lastUsedSeries = 1; // Track the last used series number globally
        
        function showPropertyNumberGenerator(button) {
            currentPropertyField = button.closest('td').querySelector('input[name="property_number[]"], textarea[name="property_number[]"]');
            const row = button.closest('tr');
            const quantityInput = row.querySelector('input[name="quantity[]"]');
            const quantity = parseInt(quantityInput.value) || 1;
            
            const modal = new bootstrap.Modal(document.getElementById('propertyNumberGeneratorModal'));
            modal.show();
            
            // Store quantity in a global variable instead of modal dataset
            window.currentQuantity = quantity;
            
            // Update quantity display
            const quantityText = document.getElementById('quantityText');
            if (quantity === 1) {
                quantityText.textContent = 'Generating 1 property number';
            } else {
                quantityText.textContent = `Generating ${quantity} property numbers`;
            }
            
            // Clear previous values (except series)
            clearGeneratorForm();
            
            // Get next available series number dynamically
            getNextSeriesNumber();
            
            // Auto-generate initial preview
            setTimeout(() => {
                generatePropertyNumberPreview();
            }, 100);
        }
        
        function getNextSeriesNumber() {
            fetch('../api/get_next_series.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.next_series) {
                    document.getElementById('seriesInput').value = data.next_series;
                    generatePropertyNumberPreview();
                }
            })
            .catch(error => {
                console.error('Error getting next series number:', error);
                // Use fallback value from PHP
                generatePropertyNumberPreview();
            });
        }
        
        function clearGeneratorForm() {
            document.getElementById('fundSelect').value = '';
            document.getElementById('categorySelect').value = '';
            document.getElementById('subcategorySelect').value = '';
            // Don't clear series - it's auto-incremented
            // Don't clear office - keep default selection
            // Don't clear formType - it's auto-detected and readonly
            document.getElementById('propertyNumberPreview').textContent = '-';
            document.getElementById('propertyNumberPreview').style.fontSize = '';
            document.getElementById('propertyNumberPreview').style.lineHeight = '';
        }
        
        function generatePropertyNumberPreview() {
            const year = new Date().getFullYear();
            const formType = document.getElementById('formType').value || '07';
            const fund = document.getElementById('fundSelect').value || '05';
            const category = document.getElementById('categorySelect').value || '030';
            const subcategory = document.getElementById('subcategorySelect').value || '01';
            const baseSeries = document.getElementById('seriesInput').value || '<?php echo $next_series; ?>';
            const office = document.getElementById('officeSelect').value || '01';
            
            // Get quantity from global variable
            const quantity = window.currentQuantity || 1;
            
            // Generate multiple property numbers using the global lastUsedSeries
            const propertyNumbers = [];
            for (let i = 0; i < quantity; i++) {
                // Use the global lastUsedSeries to continue incrementing
                const currentSeriesNumber = lastUsedSeries + i;
                const currentSubcategorySeries = String(currentSeriesNumber).padStart(4, '0');
                
                const propertyNumber = `${year}-${formType}-${fund}-${category}-${currentSubcategorySeries}-${office}`;
                propertyNumbers.push(propertyNumber);
            }
            
            // Display in preview
            const previewElement = document.getElementById('propertyNumberPreview');
            if (quantity === 1) {
                previewElement.textContent = propertyNumbers[0];
            } else {
                previewElement.innerHTML = propertyNumbers.join('<br>');
                previewElement.style.fontSize = '14px';
                previewElement.style.lineHeight = '1.4';
            }
        }
        
        function applyPropertyNumber() {
            const previewElement = document.getElementById('propertyNumberPreview');
            const propertyNumbers = previewElement.innerHTML.split('<br>').filter(num => num.trim());
            
            if (propertyNumbers.length === 0 || propertyNumbers[0] === '-') {
                alert('Please generate a property number first.');
                return;
            }
            
            if (currentPropertyField && propertyNumbers.length > 0) {
                if (propertyNumbers.length === 1) {
                    // Single property number - keep as input
                    currentPropertyField.value = propertyNumbers[0];
                    currentPropertyField.style.height = 'auto';
                    
                    // Update lastUsedSeries
                    const propNumParts = propertyNumbers[0].split('-');
                    const seriesPart = propNumParts[4]; // Get the series part (0101, 0102, etc.)
                    lastUsedSeries = parseInt(seriesPart) + 1;
                } else {
                    // Multiple property numbers - create a textarea for multi-line display
                    const textarea = document.createElement('textarea');
                    textarea.className = 'form-control form-control-sm';
                    textarea.name = 'property_number[]';
                    textarea.value = propertyNumbers.join('\n');
                    textarea.style.height = (propertyNumbers.length * 30) + 'px';
                    textarea.style.minHeight = '60px';
                    textarea.style.resize = 'vertical';
                    textarea.readOnly = true;
                    
                    // Replace the input with textarea
                    const propertyNumberContainer = currentPropertyField.closest('.property-number-field');
                    const inputContainer = propertyNumberContainer.querySelector('input[name="property_number[]"], textarea[name="property_number[]"]');
                    inputContainer.parentNode.replaceChild(textarea, inputContainer);
                    
                    // Add the generate button and format text if not present
                    if (!propertyNumberContainer.querySelector('button')) {
                        propertyNumberContainer.innerHTML += 
                            '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number"><i class="bi bi-gear"></i> Generate</button>';
                    }
                    
                    if (!propertyNumberContainer.nextElementSibling || !propertyNumberContainer.nextElementSibling.classList.contains('text-muted')) {
                        const formatText = document.createElement('small');
                        formatText.className = 'text-muted d-block mt-1';
                        formatText.textContent = 'Format: YEAR-FORM-FUND-CATEGORY-SUBCATEGORY+SERIES-OFFICE';
                        propertyNumberContainer.parentNode.insertBefore(formatText, propertyNumberContainer.nextSibling);
                    }
                    
                    // Update lastUsedSeries to the next number after the last one
                    const lastPropNumParts = propertyNumbers[propertyNumbers.length - 1].split('-');
                    const lastSeriesPart = lastPropNumParts[4]; // Get the series part of the last property number
                    lastUsedSeries = parseInt(lastSeriesPart) + 1;
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('propertyNumberGeneratorModal'));
                modal.hide();
            }
        }
        
        // Auto-generate PAR number when office location is selected
        function generatePARNumber() {
            const officeSelect = document.querySelector('select[name="office_location"]');
            const parNoField = document.getElementById('par_no');
            
            if (officeSelect.value && parNoField) {
                // Get selected office name from option text
                const selectedOption = officeSelect.options[officeSelect.selectedIndex];
                const officeName = selectedOption.text.trim();
                
                // Get current year
                const currentYear = new Date().getFullYear();
                
                // Get current month (2 digits)
                const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');
                
                // Get next series from PHP
                const nextSeries = '<?php echo $next_par_series; ?>';
                
                // Generate PAR number: OfficeP-Year-Month-Series
                const parNumber = `${officeName}P-${currentYear}-${currentMonth}-${nextSeries}`;
                
                parNoField.value = parNumber;
            } else {
                parNoField.value = '';
            }
        }
        
        // Add event listener to office location dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const officeSelect = document.querySelector('select[name="office_location"]');
            if (officeSelect) {
                officeSelect.addEventListener('change', generatePARNumber);
            }
        });
        
        // Auto-update preview when any field changes
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for auto-preview
            const fields = ['fundSelect', 'categorySelect', 'subcategorySelect', 'officeSelect'];
            fields.forEach(fieldId => {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.addEventListener('change', generatePropertyNumberPreview);
                    element.addEventListener('input', generatePropertyNumberPreview);
                }
            });
            
            // Filter subcategories based on category selection
            document.getElementById('categorySelect').addEventListener('change', function() {
                const selectedCategory = this.value;
                const subcategorySelect = document.getElementById('subcategorySelect');
                const options = subcategorySelect.querySelectorAll('option');
                
                options.forEach(option => {
                    if (option.value === '') {
                        option.style.display = 'block';
                    } else {
                        const optionCategory = option.getAttribute('data-category');
                        option.style.display = (optionCategory === selectedCategory || selectedCategory === '') ? 'block' : 'none';
                    }
                });
                
                // Reset subcategory if it doesn't match the new category
                if (subcategorySelect.value && subcategorySelect.options[subcategorySelect.selectedIndex].getAttribute('data-category') !== selectedCategory) {
                    subcategorySelect.value = '';
                }
                
                generatePropertyNumberPreview();
            });
        });
    </script>
</body>
</html>
