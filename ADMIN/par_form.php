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

// Log page access
logSystemAction($_SESSION['user_id'], 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php');

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
        // PAR-specific units
        'gallons' => 'gallon',
        'canisters' => 'canister',
        'jars' => 'jar',
        'tubes' => 'tube'
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
        'gallon', 'gallons', 'canister', 'canisters', 'jar', 'jars', 'tube', 'tubes'
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

// Common units for dropdown - kept for compatibility but will use database units
$common_units = [];
foreach ($units as $unit) {
    $common_units[] = $unit['unit_code'];
}

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
        
        .quantity-field {
            width: 80px;
            min-width: 80px;
        }
        
        .date-field {
            width: 120px;
            min-width: 120px;
        }
        
        .property-number-field .input-group {
            display: flex;
            align-items: stretch;
        }
        
        .property-number-field .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .property-number-field .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-left: -1px;
            z-index: 1;
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
        
        .nav-tabs .nav-link {
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border: none;
            transition: var(--transition);
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary-gradient);
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
                    <button class="btn btn-primary btn-sm" onclick="viewPAREntries()">
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
                                <input type="text" class="form-control" name="entity_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><strong>Fund Cluster:</strong></label>
                                <input type="text" class="form-control" name="fund_cluster">
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
                                            <td><input type="number" class="form-control form-control-sm quantity-field" name="quantity[]" required onchange="calculateAmount(this)"></td>
                                            <td>
                                                <div class="input-group"><select class="form-select form-select-sm" name="unit[]" required>
                                                    <option value="">Select Unit</option>
                                                    <?php foreach ($units as $unit): ?>
                                                        <option value="<?php echo htmlspecialchars($unit['unit_code']); ?>" data-unit-name="<?php echo htmlspecialchars($unit['unit_name']); ?>" data-singular="<?php echo htmlspecialchars(getSingularForm($unit['unit_name'])); ?>"><?php echo htmlspecialchars($unit['unit_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select></div>
                                            </td>
                                            <td><div class="input-group"><input type="text" class="form-control form-control-sm" name="description[]" required></div></td>
                                            <td>
                                                <div class="property-number-field">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control form-control-sm" name="property_number[]" id="initialPropertyNumber" value="" readonly placeholder="Click 'Generate' to create property number">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number">
                                                            <i class="bi bi-gear"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                            </td>
                                            <td><div class="input-group"><input type="date" class="form-control form-control-sm date-field" name="date_acquired[]" title="Select date from calendar"></div></td>
                                            <td><div class="input-group"><input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" required onchange="updateGrandTotal()"></div></td>
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
                        <div class="col-md-12">
                            <label class="form-label"><strong>Form Type:</strong></label>
                            <input type="text" class="form-control" id="formType" value="07" readonly>
                            <small class="text-muted">Auto-detected: Property Acknowledgment Receipt</small>
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
                            <input type="text" class="form-control" id="selectedOfficeDisplay" readonly>
                            <small class="text-muted">Based on selected Office/Location in form</small>
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
    
    <!-- Reset Confirmation Modal -->
    <div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning"></i> Confirm Reset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><strong>Are you sure you want to reset the form?</strong></p>
                    <p class="text-muted mb-0">All data will be lost and cannot be recovered.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmReset()">
                        <i class="bi bi-arrow-clockwise"></i> Reset Form
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
                foreach ($units as $unit) {
                    $singular = getSingularForm($unit['unit_name']);
                    $options .= '<option value="' . htmlspecialchars($unit['unit_code']) . '" data-unit-name="' . htmlspecialchars($unit['unit_name']) . '" data-singular="' . htmlspecialchars($singular) . '">' . htmlspecialchars($unit['unit_name']) . '</option>';
                }
                echo json_encode($options);
            ?>;
            
            // Generate auto property number for new row - COMMENTED OUT FOR MANUAL INPUT
            // const autoPropertyNumber = generatePropertyNumber();
            const autoPropertyNumber = ''; // Empty for manual input
            
            const cells = [
                '<input type="number" class="form-control form-control-sm quantity-field" name="quantity[]" required onchange="calculateAmount(this)">',
                '<div class="input-group"><select class="form-select form-select-sm" name="unit[]" required>' + unitOptions + '</select></div>',
                '<div class="input-group"><input type="text" class="form-control form-control-sm" name="description[]" required></div>',
                '<div class="property-number-field">' +
                '<div class="input-group"><input type="text" class="form-control form-control-sm" name="property_number[]" value="" readonly placeholder="Click Generate to create">' +
                '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number"><i class="bi bi-gear"></i> </button></div></div>',
                '<div class="input-group"><input type="date" class="form-control form-control-sm date-field" name="date_acquired[]" title="Select date from calendar"></div>',
                '<div class="input-group"><input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" required onchange="updateGrandTotal()"></div>',
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button>'
            ];
            
            for (let i = 0; i < cells.length; i++) {
                const cell = newRow.insertCell(i);
                cell.innerHTML = cells[i];
            }
            
            // Add amount listeners to the new row
            addAmountListeners();
            
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
        
        // Initialize the form with auto-generated property numbers - COMMENTED OUT FOR MANUAL INPUT
        function initializeForm() {
            // Set initial property number - COMMENTED OUT
            // const initialPropertyField = document.getElementById('initialPropertyNumber');
            // if (initialPropertyField && !initialPropertyField.value) {
            //     initialPropertyField.value = generatePropertyNumber();
            // }
            // No initialization needed for manual input
            
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
        
        // Function to update unit display based on quantity for PAR form
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
                        
                        console.log('PAR: Changed to singular:', originalName, '->', singularName);
                    }
                }
            }
        }
        
        // Function to validate amount is above 50,000
        function validateAmount(input) {
            const value = parseFloat(input.value);
            if (!isNaN(value) && value <= 50000) {
                // Show warning notification
                showAmountNotification(input, 'Amount must be above ₱50,000. Current amount: ₱' + value.toFixed(2));
                
                // Highlight the field
                input.style.borderColor = '#dc3545';
                input.style.backgroundColor = '#f8d7da';
            } else {
                // Clear any previous warnings
                hideAmountNotification(input);
                
                // Reset field styling
                input.style.borderColor = '';
                input.style.backgroundColor = '';
            }
        }
        
        // Function to show amount validation notification
        function showAmountNotification(input, message) {
            // Remove any existing notification for this input
            hideAmountNotification(input);
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'alert alert-warning alert-dismissible fade show amount-validation-alert';
            notification.style.cssText = 'margin-top: 5px; padding: 10px; font-size: 0.875rem;';
            notification.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Amount Validation:</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            `;
            
            // Insert notification after the input's parent cell
            const parentCell = input.closest('td');
            if (parentCell) {
                parentCell.appendChild(notification);
            }
        }
        
        // Function to hide amount validation notification
        function hideAmountNotification(input) {
            const parentCell = input.closest('td');
            if (parentCell) {
                const existingAlert = parentCell.querySelector('.amount-validation-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
            }
        }
        
        // Function to update all unit displays in the table
        function updateAllUnitDisplays() {
            const table = document.getElementById('itemsTable');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                updateUnitDisplayForRow(row);
            });
        }
        
        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            addAmountListeners();
            updateGrandTotal(); // Initialize grand total
            
            // Initialize global series counter from the database
            const initialSeries = parseInt('<?php echo $next_series; ?>') || 1;
            globalSeriesCounter = initialSeries;
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
            const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
            const rows = table.getElementsByTagName('tr');
            let grandTotal = 0;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                const amountInput = row.querySelector('input[name="amount[]"]');
                
                if (quantityInput && amountInput) {
                    const quantity = parseFloat(quantityInput.value) || 0;
                    const amount = parseFloat(amountInput.value) || 0;
                    const rowTotal = quantity * amount;
                    grandTotal += rowTotal;
                }
            }
            
            // Update the grand total display
            const grandTotalElement = document.getElementById('grandTotal');
            if (grandTotalElement) {
                grandTotalElement.textContent = grandTotal.toFixed(2);
            }
        }
        
        function calculateAmount(input) {
            // This function is called when quantity changes
            // Update grand total since quantity affects the total
            updateGrandTotal();
            
            // Get the quantity value
            const quantity = parseFloat(input.value) || 0;
            if (quantity < 0) {
                input.value = 0;
                return;
            }
            
            // Auto-set unit based on quantity with pluralization
            const row = input.closest('tr');
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
                    'ream': 'reams'
                };
                
                // Find the singular form and set appropriate unit
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
                    validateAmount(this);
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
            // Show the confirmation modal instead of using confirm()
            const modal = new bootstrap.Modal(document.getElementById('resetConfirmModal'));
            modal.show();
        }
        
        function confirmReset() {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal'));
            modal.hide();
            
            // Perform the actual reset
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
        let globalSeriesCounter = 1; // Global counter for all property numbers generated
        
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
                if (data.success && data.next_series) {
                    document.getElementById('seriesInput').value = data.next_series;
                    generatePropertyNumberPreview();
                }
            })
            .catch(error => {
                console.error('Error getting next property number series:', error);
                // Use fallback value from PHP
                generatePropertyNumberPreview();
            });
        }
        
        function clearGeneratorForm() {
            document.getElementById('categorySelect').value = '';
            document.getElementById('subcategorySelect').value = '';
            // Don't clear series - it's auto-incremented
            // Don't clear office - it's based on main form selection
            // Don't clear formType - it's auto-detected and readonly
            
            // Clear preview
            document.getElementById('propertyNumberPreview').textContent = '-';
            document.getElementById('propertyNumberPreview').style.fontSize = '';
            document.getElementById('propertyNumberPreview').style.lineHeight = '';
        }
        
        function generatePropertyNumberPreview() {
            const currentDate = new Date();
            const year = currentDate.getFullYear();
            const formType = document.getElementById('formType').value || '07';
            const category = document.getElementById('categorySelect').value || '030';
            const subcategory = document.getElementById('subcategorySelect').value || '01';
            const baseSeries = document.getElementById('seriesInput').value || '<?php echo $next_series; ?>';
            const office = document.querySelector('select[name="office_location"]').value || '01';
            
            // Update the office display
            const officeDisplay = document.getElementById('selectedOfficeDisplay');
            const officeOption = document.querySelector(`select[name="office_location"] option[value="${office}"]`);
            if (officeDisplay && officeOption) {
                officeDisplay.value = officeOption.textContent;
            }
            
            // Get quantity from global variable
            const quantity = window.currentQuantity || 1;
            
            // Generate multiple property numbers using the format: YEAR-FORM-CATEGORY-SUBCATEGORY+SERIES-OFFICE
            const propertyNumbers = [];
            for (let i = 0; i < quantity; i++) {
                // Use the global series counter for proper incrementing across all rows
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
                    
                    // Add generate button and format text if not present
                    if (!propertyNumberContainer.querySelector('button')) {
                        propertyNumberContainer.innerHTML += 
                            '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showPropertyNumberGenerator(this)" title="Generate Property Number"><i class="bi bi-gear"></i> Generate</button>';
                    }
                    
                    if (!propertyNumberContainer.nextElementSibling || !propertyNumberContainer.nextElementSibling.classList.contains('text-muted')) {
                        const formatText = document.createElement('small');
                        formatText.className = 'text-muted d-block mt-1';
                        formatText.textContent = '';
                        propertyNumberContainer.parentNode.insertBefore(formatText, propertyNumberContainer.nextSibling);
                    }
                }
                
                // Increment the global series counter by the quantity of property numbers generated
                globalSeriesCounter += propertyNumbers.length;
                
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
            const fields = ['categorySelect', 'subcategorySelect'];
            fields.forEach(fieldId => {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.addEventListener('change', generatePropertyNumberPreview);
                    element.addEventListener('input', generatePropertyNumberPreview);
                }
            });
            
            // Add event listener for main form's office location dropdown
            const officeLocationSelect = document.querySelector('select[name="office_location"]');
            if (officeLocationSelect) {
                officeLocationSelect.addEventListener('change', generatePropertyNumberPreview);
            }
            
            // Filter subcategories based on category selection - separate function
            function setupSubcategoryFilter() {
                const categorySelect = document.getElementById('categorySelect');
                const subcategorySelect = document.getElementById('subcategorySelect');
                
                if (categorySelect && subcategorySelect) {
                    // Remove existing listener to prevent duplicates
                    const newCategorySelect = categorySelect.cloneNode(true);
                    categorySelect.parentNode.replaceChild(newCategorySelect, categorySelect);
                    
                    // Add event listener for category change
                    newCategorySelect.addEventListener('change', function() {
                        const selectedCategory = this.value;
                        const options = subcategorySelect.querySelectorAll('option');
                        
                        console.log('Category changed to:', selectedCategory); // Debug log
                        
                        options.forEach(option => {
                            if (option.value === '') {
                                option.style.display = 'block';
                            } else {
                                const optionCategory = option.getAttribute('data-category');
                                const shouldShow = optionCategory === selectedCategory || selectedCategory === '';
                                option.style.display = shouldShow ? 'block' : 'none';
                                
                                // Debug logging
                                if (shouldShow && option.value) {
                                    console.log('Showing subcategory:', option.value, 'Category:', optionCategory);
                                }
                            }
                        });
                        
                        // Reset subcategory if it doesn't match the new category
                        if (subcategorySelect.value && subcategorySelect.options[subcategorySelect.selectedIndex].getAttribute('data-category') !== selectedCategory) {
                            console.log('Resetting subcategory - old category:', subcategorySelect.options[subcategorySelect.selectedIndex].getAttribute('data-category'), 'new category:', selectedCategory);
                            subcategorySelect.value = '';
                        }
                        
                        generatePropertyNumberPreview();
                    });
                    
                    // Add event listener for subcategory change
                    subcategorySelect.addEventListener('change', function() {
                        console.log('Subcategory changed to:', this.value); // Debug log
                        generatePropertyNumberPreview();
                    });
                }
            }
            
            // Initialize the subcategory filter
            setupSubcategoryFilter();
        });
    </script>
</body>
</html>
