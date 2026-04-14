<?php
ob_start();
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

logSystemAction($_SESSION['user_id'], 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php');

// Function to get singular form of unit name
function getSingularForm($unitName) {
    // Common plural to singular conversions
    $singularRules = [
        // Regular -s endings
        'pieces' => 'piece',
        'sets' => 'set',
        'units' => 'unit',
        'boxes' => 'box',
        'cartons' => 'carton',
        'packs' => 'pack',
        'packages' => 'package',
        'bags' => 'bag',
        'containers' => 'container',
        'bottles' => 'bottle',
        'reams' => 'ream',
        'pairs' => 'pair',
        'dozens' => 'dozen',
        'rolls' => 'roll',
        'sheets' => 'sheet',
        'feet' => 'foot',
        'inches' => 'inch',
        'meters' => 'meter',
        'centimeters' => 'centimeter',
        'kilometers' => 'kilometer',
        'liters' => 'liter',
        'milliliters' => 'milliliter',
        'kilograms' => 'kilogram',
        'grams' => 'gram',
        'tons' => 'ton',
        'hours' => 'hour',
        'days' => 'day',
        'months' => 'month',
        'years' => 'year',
        'hectares' => 'hectare',
        // Special cases
        'pcs' => 'pc',
        'kgs' => 'kg',
        'gs' => 'g',
        'ms' => 'm',
        'cms' => 'cm',
        'kms' => 'km',
        'mls' => 'ml',
        'm3s' => 'm3',
        'm2s' => 'm2',
        'has' => 'ha',
        'hrs' => 'hr',
        'mos' => 'mo',
        'yrs' => 'yr',
        'fts' => 'ft',
        'ins' => 'in',
        // ICS-specific units
        'gallons' => 'gallon',
        'canisters' => 'canister',
        'jars' => 'jar',
        'tubes' => 'tube',
        'pounds' => 'pound',
        'ounces' => 'ounce',
        'cases' => 'case',
        'barrels' => 'barrel',
        'drums' => 'drum'
    ];
    
    $lowerUnitName = strtolower($unitName);
    return $singularRules[$lowerUnitName] ?? $unitName; // Return original if no rule found
}

// Get units from database
$units = [];
try {
    $result = $conn->query("SELECT unit_name, unit_code FROM units WHERE status = 'active' ORDER BY unit_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching units: " . $e->getMessage());
    // Fallback to common units if database fails
    $units = [];
    $common_units_fallback = [
        'pc', 'pcs', 'piece', 'pieces', 'set', 'sets', 'unit', 'units',
        'box', 'boxes', 'carton', 'cartons', 'pack', 'packs', 'package', 'packages',
        'liter', 'liters', 'kilogram', 'kilograms', 'meter', 'meters',
        'square_meter', 'square_meters', 'cubic_meter', 'cubic_meters',
        'pair', 'pairs', 'dozen', 'dozens', 'roll', 'rolls',
        'bottle', 'bottles', 'bag', 'bags', 'container', 'containers', 'ream', 'reams',
        'gallon', 'gallons', 'canister', 'canisters', 'jar', 'jars', 'tube', 'tubes',
        'pound', 'pounds', 'ounce', 'ounces', 'case', 'cases', 'barrel', 'barrels', 'drum', 'drums'
    ];
    foreach ($common_units_fallback as $unit) {
        $units[] = ['unit_name' => ucfirst($unit), 'unit_code' => $unit];
    }
}

// Get data for dropdowns
$funds_result = $conn->query("SELECT fund_code, fund_name, fund_cluster FROM funds WHERE status = 'active' ORDER BY fund_code");
$categories_result = $conn->query("SELECT category_code, category_name FROM asset_categories WHERE status = 'active' ORDER BY category_code");
$subcategories_result = $conn->query("SELECT sc.sub_category_code, sc.sub_category_name, ac.category_code FROM asset_sub_categories sc JOIN asset_categories ac ON sc.asset_categories_id = ac.id WHERE sc.status = 'active' ORDER BY ac.category_code, sc.sub_category_code");
$offices_result = $conn->query("SELECT office_code, office_name FROM offices WHERE status = 'active' ORDER BY office_code");

// Get next series number for auto-increment
$next_series = '01';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(property_no, -4, 2) AS UNSIGNED)) as max_series FROM asset_items WHERE property_no LIKE CONCAT(YEAR(CURDATE()), '-%')");
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

// Get next ICS series number
$next_ics_series = '01';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(ics_no, -2, 2) AS UNSIGNED)) as max_series FROM ics_forms WHERE ics_no LIKE '%-I-%' AND ics_no REGEXP '-I-[0-9]{2}$'");
if ($result && $row = $result->fetch_assoc()) {
    $max_series = $row['max_series'];
    if ($max_series) {
        $next_ics_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
    }
}

// Get next ICS number - ENABLED FOR AUTO-GENERATION
$next_ics_no = getNextTagPreview('ics_no');
if ($next_ics_no === null) {
    // Fallback: generate simple ICS number with auto-increment
    $current_year = date('Y');
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(ics_no, -2, 2) AS UNSIGNED)) as max_series FROM ics_forms WHERE ics_no LIKE '%$current_year%' AND ics_no REGEXP '-[0-9]{2}$'");
    $next_series = '01';
    if ($result && $row = $result->fetch_assoc()) {
        $max_series = $row['max_series'];
        if ($max_series) {
            $next_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
        }
    }
    $next_ics_no = "OMMI-$current_year-I-$next_series";
}

// Get ICS configuration for JavaScript - ENABLED FOR AUTO-GENERATION
$ics_config = null;
$result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'ics_no' AND status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $ics_config = $row;
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'ICS'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

// Common units for dropdown - kept for compatibility but will use database units
$common_units = [];
foreach ($units as $unit) {
    $common_units[] = $unit['unit_code'];
}

// Get offices for dropdown
$offices = [];
$result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
}

// Get latest signature data from the most recent ICS form
$latest_signature = [];
$result = $conn->query("SELECT received_from, received_from_position, received_by, received_by_position FROM ics_forms ORDER BY created_at DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $latest_signature = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Custodian Slip - PIMS</title>
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
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
        
        .quantity-field {
            width: 80px;
            min-width: 80px;
        }
        
        .useful-life-field {
            width: 120px;
            min-width: 120px;
        }
        
        .description-field {
            width: 200px;
            min-width: 200px;
        }
        
        .item-no-field {
            width: 180px;
            min-width: 180px;
        }
        
        .cost-field {
            width: 100px;
            min-width: 100px;
        }
        
        .table td {
            padding: 0.5rem;
        }
        
        .table td .form-control,
        .table td .form-select {
            width: 100%;
            margin: 0;
            border-radius: 0;
            border: 1px solid #dee2e6;
        }
        
        .table td .form-control:focus,
        .table td .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: none;
            z-index: 1;
            position: relative;
        }
        
        .quantity-field {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        
        .unit-field {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        
        .cost-field {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        
        .description-field {
            width: 100%;
            min-width: auto;
        }
        
        .item-no-field {
            width: 100%;
            min-width: auto;
        }
        
        .useful-life-field {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        
        .property-number-field {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .property-number-field .form-control {
            padding-right: 80px;
            border-radius: var(--border-radius);
        }
        
        .property-number-field .btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: var(--border-radius-sm);
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            z-index: 10;
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="toastContainer"></div>
    </div>
    
    <?php
    // Set page title for topbar
    $page_title = 'Inventory Custodian Slip';
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
                        <i class="bi bi-file-earmark-text"></i> Inventory Custodian Slip
                    </h1>
                    <p class="text-muted mb-0">Manage Inventory Custodian Slip forms</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="ics_entries.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-list"></i> View Entries
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ICS Form -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> ICS Form
                </h5>
                <div class="no-print">
                    <!-- Action buttons moved to dropdown -->
                </div>
            </div>
            
            <form id="icsForm" method="POST" action="process_ics.php">
                <!-- ICS Form Header -->
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
                        <p style="margin: 0; font-size: 12px;">MUNICIPALITY OF PILAR</p>
                       
                    </div>
                </div>
                
                <!-- Entity Name, Fund Cluster, and ICS No -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Entity Name:</strong></label>
                                    <input type="text" class="form-control" name="entity_name" id="entityNameInput" list="entityNameList" placeholder="Type or select entity name">
                                    <datalist id="entityNameList">
                                        <?php
                                        // Get offices from database for dropdown options
                                        $offices_result = $conn->query("SELECT office_code, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
                                        if ($offices_result) {
                                            while ($office = $offices_result->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($office['office_name']) . '">';
                                            }
                                        }
                                        ?>
                                    </datalist>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Fund Cluster:</strong></label>
                                    <input type="text" class="form-control" name="fund_cluster">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><strong>ICS No:</strong></label>
                                    <input type="text" class="form-control" name="ics_no" id="ics_no" value="<?php echo htmlspecialchars($next_ics_no); ?>" readonly placeholder="Auto-generated when form is loaded">
                                    <small class="text-muted">Auto-generated unique number (Format: OMMI-26-I-01)</small>
                                </div>
                            </div>
                            
                            <!-- Items Table -->
                            <div class="mb-3">
                                <label class="form-label"><strong>Items:</strong></label>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="icsItemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Quantity</th>
                                                <th>Unit</th>
                                                <th colspan="2">Amount</th>
                                                <th>Description</th>
                                                <th>Item No.</th>
                                                <th>Useful Life</th>
                                                <th>Action</th>
                                            </tr>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th>Unit Cost</th>
                                                <th>Total Cost</th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="number" class="form-control form-control-sm quantity-field" name="quantity[]" onchange="calculateTotal(this)"></td>
                                                <td>
                                                    <select class="form-select form-select-sm unit-field" name="unit[]">
                                                        <option value="">Select Unit</option>
                                                        <?php foreach ($units as $unit): ?>
                                                            <option value="<?php echo htmlspecialchars($unit['unit_code']); ?>" data-unit-name="<?php echo htmlspecialchars($unit['unit_name']); ?>" data-singular="<?php echo htmlspecialchars(getSingularForm($unit['unit_name'])); ?>"><?php echo htmlspecialchars($unit['unit_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm cost-field" name="unit_cost[]" onchange="calculateTotal(this)" max="50000" min="0.01"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm cost-field" name="total_cost[]" readonly></td>
                                                <td><input type="text" class="form-control form-control-sm description-field" name="description[]"></td>
                                                <td>
                                                <div class="property-number-field">
                                                    <input type="text" class="form-control form-control-sm item-no-field" name="item_no[]" value="" readonly placeholder="Click 'Generate' to create">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number">
                                                        <i class="bi bi-gear"></i> Generate
                                                    </button>
                                                </div>
                                            </td>
                                                <td><input type="text" class="form-control form-control-sm useful-life-field" name="useful_life[]"></td>
                                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeICSRow(this)"><i class="bi bi-trash"></i></button></td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-primary fw-bold">
                                                <td colspan="3" class="text-end">Grand Total:</td>
                                                <td id="grandTotal">0.00</td>
                                                <td colspan="3"></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addICSRow()">
                                    <i class="bi bi-plus-circle"></i> Add Row
                                </button>
                            </div>
                            
                            <!-- Signature Section -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Received from:</strong></label>
                                    <input type="text" class="form-control" name="received_from" value="<?php echo htmlspecialchars($latest_signature['received_from'] ?? ''); ?>">
                                    <label class="form-label"><strong>Position/Office:</strong></label>
                                    <input type="text" class="form-control" name="received_from_position" value="<?php echo htmlspecialchars($latest_signature['received_from_position'] ?? ''); ?>">
                                    <label class="form-label"><strong>Date:</strong></label>
                                    <input type="date" class="form-control" name="received_from_date">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Received by:</strong></label>
                                    <input type="text" class="form-control" name="received_by" value="<?php echo htmlspecialchars($latest_signature['received_by'] ?? ''); ?>">
                                    <label class="form-label"><strong>Position/Office:</strong></label>
                                    <input type="text" class="form-control" name="received_by_position" value="<?php echo htmlspecialchars($latest_signature['received_by_position'] ?? ''); ?>">
                                    <label class="form-label"><strong>Date:</strong></label>
                                    <input type="date" class="form-control" name="received_by_date">
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save ICS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Threshold Warning Modal -->
    <div class="modal fade" id="thresholdModal" tabindex="-1" aria-labelledby="thresholdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fw-bold" id="thresholdModalLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i> Price Threshold Alert
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-3 text-warning" style="font-size: 3rem;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h5 class="fw-bold mb-3">Unit Cost Limits</h5>
                    <p class="text-muted mb-0">
                        ICS is only used for items with a unit cost <strong>below ₱50,000</strong>.
                    </p>
                    <p class="text-danger fw-semibold mt-2">
                        Items costing ₱50,000 and above must be processed via Property Acknowledgment Receipt (PAR).
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">I Understand</button>
                    <a href="par_form.php" class="btn btn-primary px-4">Go to PAR Form</a>
                </div>
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
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label"><strong>Form Type:</strong></label>
                            <input type="text" class="form-control" id="formType" value="04" readonly>
                            <small class="text-muted">Auto-detected: Inventory Custodian Slip</small>
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
        // Property Number Generator Functions
        let currentPropertyField = null;
        let globalSeriesCounter = 1; // Global counter for all property numbers generated
        
        function showPropertyNumberGenerator(button) {
            currentPropertyField = button.closest('td').querySelector('input[name="item_no[]"], textarea[name="item_no[]"]');
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
            
            // Auto-generate preview immediately without waiting
            setTimeout(() => {
                generatePropertyNumberPreview();
            }, 50); // Reduced timeout for immediate generation
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
            document.getElementById('categorySelect').value = '';
            document.getElementById('subcategorySelect').value = '';
            // Don't clear series - it's auto-incremented
            // Don't clear office - keep default selection
            // Don't clear formType - it's auto-detected and readonly
            document.getElementById('propertyNumberPreview').textContent = '-';
        }
        
        function generatePropertyNumberPreview() {
            const year = new Date().getFullYear();
            const formType = document.getElementById('formType').value || '04';
            const category = document.getElementById('categorySelect').value || '030';
            const subcategory = document.getElementById('subcategorySelect').value || '01';
            const series = document.getElementById('seriesInput').value || '<?php echo $next_series; ?>';
            const office = document.getElementById('officeSelect').value || '01';
            
            // Get quantity from global variable
            const quantity = window.currentQuantity || 1;
            
            // Generate multiple property numbers using format: YEAR-FORM-CATEGORY-SUBCATEGORY+SERIES-OFFICE
            const propertyNumbers = [];
            for (let i = 0; i < quantity; i++) {
                // Use global series counter for proper incrementing across all rows
                const currentSeriesNumber = globalSeriesCounter + i;
                const currentSeries = String(currentSeriesNumber).padStart(2, '0');
                
                // Combine subcategory and series without dash (e.g., 01 + 01 = 0101, 01 + 02 = 0102)
                const subcategorySeries = subcategory + currentSeries;
                
                const propertyNumber = `${year}-${formType}-${category}-${subcategorySeries}-${office}`;
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
                // Get selected category and subcategory values
                const categorySelect = document.getElementById('categorySelect');
                const subcategorySelect = document.getElementById('subcategorySelect');
                const selectedCategoryCode = categorySelect.value;
                const selectedSubcategoryCode = subcategorySelect.value;
                
                // Add hidden fields to store category and subcategory codes
                const row = currentPropertyField.closest('tr');
                
                // Remove existing hidden fields if any
                const existingCatField = row.querySelector('input[name="category_code[]"]');
                const existingSubcatField = row.querySelector('input[name="subcategory_code[]"]');
                if (existingCatField) existingCatField.remove();
                if (existingSubcatField) existingSubcatField.remove();
                
                // Add hidden fields for category and subcategory codes
                if (selectedCategoryCode) {
                    const catHiddenField = document.createElement('input');
                    catHiddenField.type = 'hidden';
                    catHiddenField.name = 'category_code[]';
                    catHiddenField.value = selectedCategoryCode;
                    row.appendChild(catHiddenField);
                }
                
                if (selectedSubcategoryCode) {
                    const subcatHiddenField = document.createElement('input');
                    subcatHiddenField.type = 'hidden';
                    subcatHiddenField.name = 'subcategory_code[]';
                    subcatHiddenField.value = selectedSubcategoryCode;
                    row.appendChild(subcatHiddenField);
                }
                
                if (propertyNumbers.length === 1) {
                    // Single property number - keep as input
                    currentPropertyField.value = propertyNumbers[0];
                    currentPropertyField.style.height = 'auto';
                } else {
                    // Multiple property numbers - create a textarea for multi-line display
                    const textarea = document.createElement('textarea');
                    textarea.className = 'form-control form-control-sm';
                    textarea.name = 'item_no[]';
                    textarea.value = propertyNumbers.join('\n');
                    textarea.style.height = (propertyNumbers.length * 30) + 'px';
                    textarea.style.minHeight = '60px';
                    textarea.style.resize = 'vertical';
                    textarea.readOnly = true;
                    
                    // Replace input with textarea
                    const propertyNumberContainer = currentPropertyField.closest('.property-number-field');
                    const inputContainer = propertyNumberContainer.querySelector('input[name="item_no[]"], textarea[name="item_no[]"]');
                    inputContainer.parentNode.replaceChild(textarea, inputContainer);
                    
                    // Add generate button and format text if not present
                    if (!propertyNumberContainer.querySelector('button')) {
                        propertyNumberContainer.innerHTML += 
                            '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number"><i class="bi bi-gear"></i> Generate</button>';
                    }
                    
                    // Add format text if not present
                    if (!propertyNumberContainer.nextElementSibling || !propertyNumberContainer.nextElementSibling.classList.contains('text-muted')) {
                        const formatText = document.createElement('small');
                        formatText.className = 'text-muted d-block mt-1';
                        formatText.textContent = 'Format: YEAR-FORM-FUND-CATEGORY-SUBCATEGORY+SERIES-OFFICE';
                        propertyNumberContainer.parentNode.insertBefore(formatText, propertyNumberContainer.nextSibling);
                    }
                }
                
                // Update lastUsedSeries to the highest number used
                const lastPropNumParts = propertyNumbers[propertyNumbers.length - 1].split('-');
                const lastSeriesPart = lastPropNumParts[4]; // Get the series part (e.g., 0101, 0102, etc.)
                lastUsedSeries = parseInt(lastSeriesPart) + 1;
                
                // Increment the global series counter by the quantity of property numbers generated
                globalSeriesCounter += propertyNumbers.length;
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('propertyNumberGeneratorModal'));
                modal.hide();
            }
        }
        
        // Auto-update preview when any field changes
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for auto-preview
            const fields = ['categorySelect', 'subcategorySelect', 'officeSelect'];
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
        
        // Auto-generate ICS number when entity name is entered
        function generateICSNumber() {
            const entityInput = document.querySelector('input[name="entity_name"]');
            const icsNoField = document.getElementById('ics_no');
            
            if (entityInput.value && icsNoField) {
                // Get entity name from input field
                const entityName = entityInput.value.trim();
                
                // Get current year (last 2 digits)
                const currentYear = new Date().getFullYear().toString().slice(-2);
                
                // Get next series from PHP
                const nextSeries = '<?php echo $next_ics_series; ?>';
                
                // Generate ICS number: EntityI-Year-Series
                const icsNumber = `${entityName}I-${currentYear}-${nextSeries}`;
                
                icsNoField.value = icsNumber;
            } else {
                icsNoField.value = '';
            }
        }
        
        // Add event listener to entity name input
        document.addEventListener('DOMContentLoaded', function() {
            const entityInput = document.querySelector('input[name="entity_name"]');
            if (entityInput) {
                entityInput.addEventListener('input', generateICSNumber);
                entityInput.addEventListener('blur', generateICSNumber);
            }
            
            // Initialize grand total
            updateGrandTotal();
        });
        
        function addICSRow() {
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            // Create unit dropdown HTML
            const unitOptions = <?php 
                $options = '<option value="">Select Unit</option>';
                foreach ($units as $unit) {
                    $singular = getSingularForm($unit['unit_name']);
                    $options .= '<option value="' . htmlspecialchars($unit['unit_code']) . '" data-unit-name="' . htmlspecialchars($unit['unit_name']) . '" data-singular="' . htmlspecialchars($singular) . '">' . htmlspecialchars($unit['unit_name']) . '</option>';
                }
                echo json_encode($options);
            ?>;
            
            // Get next item number
            const nextItemNumber = table.rows.length;
            
            const cells = [
                '<input type="number" class="form-control form-control-sm quantity-field" name="quantity[]" onchange="calculateTotal(this)">',
                '<select class="form-select form-select-sm unit-field" name="unit[]">' + unitOptions + '</select>',
                '<input type="number" step="0.01" class="form-control form-control-sm cost-field" name="unit_cost[]" onchange="calculateTotal(this)" max="50000" min="0.01">',
                '<input type="number" step="0.01" class="form-control form-control-sm cost-field" name="total_cost[]" readonly>',
                '<input type="text" class="form-control form-control-sm description-field" name="description[]">',
                '<div class="property-number-field">' +
                '<input type="text" class="form-control form-control-sm item-no-field" name="item_no[]" value="" readonly placeholder="Click Generate to create">' +
                '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number"><i class="bi bi-gear"></i> Generate</button>' +
                '</div>',
                '<input type="text" class="form-control form-control-sm useful-life-field" name="useful_life[]">',
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeICSRow(this)"><i class="bi bi-trash"></i></button>'
            ];
            
            cells.forEach((cellHtml, index) => {
                const cell = newRow.insertCell(index);
                cell.innerHTML = cellHtml;
            });
            
            // Add unit display listeners to the new row
            const newQuantityInput = newRow.querySelector('input[name="quantity[]"]');
            const newUnitSelect = newRow.querySelector('select[name="unit[]"]');
            
            if (newQuantityInput) {
                newQuantityInput.addEventListener('input', function() {
                    updateUnitDisplayForRow(newRow);
                });
                newQuantityInput.addEventListener('change', function() {
                    updateUnitDisplayForRow(newRow);
                });
            }
            
            if (newUnitSelect) {
                newUnitSelect.addEventListener('change', function() {
                    updateUnitDisplayForRow(newRow);
                });
            }
        }
        
        function removeICSRow(button) {
            const row = button.closest('tr');
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            
            if (table.rows.length > 1) {
                row.remove();
                // Update grand total after removing row
                updateGrandTotal();
                // No renumbering needed since item numbers are now manual
            } else {
                alert('At least one row is required');
            }
        }
        
        function renumberItems() {
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            const rows = table.rows;
            
            for (let i = 0; i < rows.length; i++) {
                const itemNoCell = rows[i].cells[5]; // Item No is in column 5 (0-indexed)
                const itemNoInput = itemNoCell.querySelector('input[name="item_no[]"]');
                if (itemNoInput) {
                    itemNoInput.value = i + 1;
                }
            }
        }
        
        function calculateTotal(input) {
            const row = input.closest('tr');
            const quantity = row.querySelector('input[name="quantity[]"]').value || 0;
            const unitCost = row.querySelector('input[name="unit_cost[]"]').value || 0;
            const totalCost = (parseFloat(quantity) * parseFloat(unitCost)).toFixed(2);
            
            // Auto-set unit based on quantity with pluralization
            const unitSelect = row.querySelector('select[name="unit[]"]');
            if (unitSelect && quantity > 0) {
                const currentValue = unitSelect.value;
                
                // Handle pluralization for common units
                const pluralMap = {
                    'pc': 'pcs',
                    'piece': 'pieces',
                    'set': 'sets',
                    'box': 'boxes',
                    'carton': 'cartons',
                    'pack': 'packs',
                    'bottle': 'bottles',
                    'liter': 'liters',
                    'gallon': 'gallons',
                    'kilogram': 'kilograms',
                    'gram': 'grams',
                    'meter': 'meters',
                    'centimeter': 'centimeters',
                    'foot': 'feet',
                    'inch': 'inches',
                    'dozen': 'dozens',
                    'pair': 'pairs',
                    'roll': 'rolls',
                    'bag': 'bags',
                    'canister': 'canisters',
                    'jar': 'jars',
                    'tube': 'tubes',
                    'ream': 'reams',
                    'case': 'cases',
                    'barrel': 'barrels',
                    'drum': 'drums',
                    'pound': 'pounds',
                    'ounce': 'ounces'
                };
                
                // Find the appropriate unit based on quantity
                let targetUnit = '';
                if (quantity === 1) {
                    // Use singular form
                    for (const [singular, plural] of Object.entries(pluralMap)) {
                        if (currentValue === plural) {
                            targetUnit = singular;
                            break;
                        } else if (currentValue === singular) {
                            targetUnit = singular;
                            break;
                        }
                    }
                    // If no mapping found, keep current value
                    if (!targetUnit && currentValue) {
                        targetUnit = currentValue;
                    }
                } else if (quantity > 1) {
                    // Use plural form
                    for (const [singular, plural] of Object.entries(pluralMap)) {
                        if (currentValue === singular) {
                            targetUnit = plural;
                            break;
                        } else if (currentValue === plural) {
                            targetUnit = plural;
                            break;
                        }
                    }
                    // If no mapping found, keep current value
                    if (!targetUnit && currentValue) {
                        targetUnit = currentValue;
                    }
                }
                
                // Set the unit if found
                if (targetUnit) {
                    // Check if the target unit exists in the dropdown
                    const optionExists = Array.from(unitSelect.options).some(option => option.value === targetUnit);
                    if (optionExists) {
                        unitSelect.value = targetUnit;
                    }
                }
            }
            
            // Validate unit cost should be less than ₱50,000
            if (parseFloat(unitCost) >= 50000) {
                const modal = new bootstrap.Modal(document.getElementById('thresholdModal'));
                modal.show();
                
                row.querySelector('input[name="unit_cost[]"]').value = '';
                row.querySelector('input[name="total_cost[]"]').value = '';
                row.querySelector('input[name="unit_cost[]"]').focus();
                updateGrandTotal();
                return;
            }
            
            row.querySelector('input[name="total_cost[]"]').value = totalCost;
            
            // Update grand total after calculating total cost
            updateGrandTotal();
        }
        
        function updateGrandTotal() {
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            const rows = table.getElementsByTagName('tr');
            let grandTotal = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const totalCostInput = rows[i].querySelector('input[name="total_cost[]"]');
                if (totalCostInput && totalCostInput.value) {
                    grandTotal += parseFloat(totalCostInput.value) || 0;
                }
            }
            
            // Update the grand total display
            const grandTotalElement = document.getElementById('grandTotal');
            if (grandTotalElement) {
                grandTotalElement.textContent = grandTotal.toFixed(2);
            }
        }
        
        function resetICSForm() {
            if (confirm('Are you sure you want to reset form? All data will be lost.')) {
                document.getElementById('icsForm').reset();
                const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
                while (table.rows.length > 1) {
                    table.deleteRow(1);
                }
                // Clear first row item number since it's now manual input
                const firstRow = table.rows[0];
                const itemNoInput = firstRow.cells[5].querySelector('input[name="item_no[]"]');
                if (itemNoInput) {
                    itemNoInput.value = '';
                }
                // Reset grand total
                const grandTotalElement = document.getElementById('grandTotal');
                if (grandTotalElement) {
                    grandTotalElement.textContent = '0.00';
                }
            }
        }
        
        function createNewICS() {
            document.getElementById('icsForm').reset();
            // Generate fresh ICS number - COMMENTED OUT
            // generateNewIcsNumber();
            // Reset item numbering
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            while (table.rows.length > 1) {
                table.deleteRow(1);
            }
            // Clear first row item number since it's now manual input
            const firstRow = table.rows[0];
            const itemNoInput = firstRow.cells[5].querySelector('input[name="item_no[]"]');
            if (itemNoInput) {
                itemNoInput.value = '';
            }
        }
        
        // Generate new ICS number via AJAX - COMMENTED OUT FOR MANUAL INPUT
        function generateNewIcsNumber() {
            // COMMENTED OUT - Auto-generation disabled
            // <?php if ($ics_config): ?>
            // const components = <?php 
            //     $components = json_decode($ics_config['format_components'], true);
            //     if (is_string($components)) {
            //         $components = json_decode($components, true);
            //     }
            //     echo json_encode($components ?: []);
            // ?>;
            // const digits = <?php echo $ics_config['digits']; ?>;
            // const separator = '<?php echo $ics_config['separator']; ?>';
            // 
            // fetch('../SYSTEM_ADMIN/tags.php', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/x-www-form-urlencoded',
            //     },
            //     body: 'action=generate_preview&tag_type=ics_no&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.preview) {
            //         document.getElementById('ics_no').value = data.preview;
            //     }
            // })
            // .catch(error => {
            //     console.error('Error generating ICS number:', error);
            // });
            // <?php endif; ?>
            
            // Auto-generation disabled - do nothing
        }
        
        // Handle form submission to update counter - COMMENTED OUT FOR MANUAL INPUT
        document.getElementById('icsForm').addEventListener('submit', function(e) {
            // COMMENTED OUT - Auto-generation disabled
            // // Always increment counter since field is always auto-generated
            // const incrementField = document.createElement('input');
            // incrementField.type = 'hidden';
            // incrementField.name = 'increment_ics_counter';
            // incrementField.value = '1';
            // this.appendChild(incrementField);
            
            // No counter increment needed for manual input
        });
        
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0 mb-3" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Auto-remove toast after 5 seconds
            setTimeout(() => {
                toast.dispose();
                toastElement.remove();
            }, 5000);
        }
        
        function exportICSData() {
            // TODO: Implement export functionality
            alert('Export functionality will be implemented');
        }
        
        // Function to update unit display based on quantity for ICS form
        function updateUnitDisplayForRow(row) {
            const quantityInput = row.querySelector('input[name="quantity[]"]');
            const unitSelect = row.querySelector('select[name="unit[]"]');
            
            if (!quantityInput || !unitSelect) return;
            
            const quantity = parseInt(quantityInput.value) || 0;
            
            // Remove any existing temporary options
            const tempOptions = unitSelect.querySelectorAll('option[data-temp-singular]');
            tempOptions.forEach(opt => opt.remove());
            
            // Show all original options
            const allOptions = unitSelect.querySelectorAll('option');
            allOptions.forEach(opt => {
                if (opt.style.display === 'none') {
                    opt.style.display = '';
                }
            });
            
            if (quantity === 1) {
                const selectedOption = unitSelect.options[unitSelect.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const singularName = selectedOption.getAttribute('data-singular');
                    const originalName = selectedOption.getAttribute('data-unit-name');
                    
                    if (singularName && singularName !== originalName) {
                        // Hide the original option
                        selectedOption.style.display = 'none';
                        
                        // Create and add singular option
                        const singularOption = document.createElement('option');
                        singularOption.value = selectedOption.value;
                        singularOption.textContent = singularName;
                        singularOption.setAttribute('data-temp-singular', 'true');
                        singularOption.selected = true;
                        
                        unitSelect.add(singularOption);
                        
                        console.log('ICS: Changed to singular:', originalName, '->', singularName);
                    }
                }
            }
        }
        
        // Function to update all unit displays in the table
        function updateAllUnitDisplays() {
            const table = document.getElementById('icsItemsTable');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                updateUnitDisplayForRow(row);
            });
        }
        
        // Initialize form with correct units
        function initializeICSForm() {
            // Set correct units for existing quantity inputs with pluralization
            const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
            quantityInputs.forEach(input => {
                // Add event listeners for quantity changes
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    updateUnitDisplayForRow(row);
                });
                input.addEventListener('change', function() {
                    const row = this.closest('tr');
                    updateUnitDisplayForRow(row);
                });
                
                // Initial unit display update
                const row = input.closest('tr');
                updateUnitDisplayForRow(row);
            });
            
            // Add event listeners for unit selection changes
            const unitSelects = document.querySelectorAll('select[name="unit[]"]');
            unitSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const row = this.closest('tr');
                    updateUnitDisplayForRow(row);
                });
            });
        }
        
        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeICSForm();
        });
    </script>
</body>
</html>
