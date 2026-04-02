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

logSystemAction($_SESSION['user_id'], 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php');

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
        // RIS-specific units
        'gallons' => 'gallon',
        'cans' => 'can',
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
        'gallon', 'gallons', 'can', 'cans', 'tube', 'tubes'
    ];
    foreach ($common_units_fallback as $unit) {
        $units[] = ['unit_name' => ucfirst($unit), 'unit_code' => $unit];
    }
}

// Get next RIS number
$next_ris_no = getNextTagPreview('ris_no');
if ($next_ris_no === null) {
    $next_ris_no = ''; // Fallback if no configuration exists
}

// Get next SAI number
$next_sai_no = getNextTagPreview('sai_no');
if ($next_sai_no === null) {
    $next_sai_no = ''; // Fallback if no configuration exists
}

// Get next Code
$next_code = getNextTagPreview('code');
if ($next_code === null) {
    $next_code = ''; // Fallback if no configuration exists
}

// Get RIS configuration for JavaScript
$ris_config = null;
$result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'ris_no' AND status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $ris_config = $row;
}

// Get SAI configuration for JavaScript
$sai_config = null;
$result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'sai_no' AND status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $sai_config = $row;
}

// Get Code configuration for JavaScript
$code_config = null;
$result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'code' AND status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $code_config = $row;
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'RIS'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

// Get offices for dropdown
$offices = [];
$result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
}

// Get latest signature data from the most recent RIS form
$latest_signature = [];
$result = $conn->query("SELECT requested_by, requested_by_position, approved_by, approved_by_position, issued_by, issued_by_position, received_by, received_by_position FROM ris_forms ORDER BY created_at DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $latest_signature = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisition and Issue Slip - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <!-- Excel Parsing Library -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Requisition and Issue Slip';
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
                        <i class="bi bi-file-earmark-text"></i> Requisition and Issue Slip
                    </h1>
                    <p class="text-muted mb-0">Manage Requisition and Issue Slip forms</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="ris_entries.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-list"></i> View Entries
                    </a>
                    <!-- Action buttons removed as requested -->
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

        <!-- RIS Form -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> RIS Form
                </h5>
                <div class="no-print d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="document.getElementById('importExcelItem').click()">
                        <i class="bi bi-file-earmark-excel"></i> Import Office Supplies
                    </button>
                    <input type="file" id="importExcelItem" class="d-none" accept=".xlsx, .xls" onchange="handleExcelImport(this)">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetRISForm()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>
            
            <form id="risForm" method="POST" action="process_ris.php">
                <!-- RIS Form Header -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php 
                    if (!empty($header_image)) {
                        echo '<div style="margin-bottom: 10px;">';
                        echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 120px; object-fit: contain;">';
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <!-- Entity Fields Header -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label"><strong>DIVISION:</strong></label>
                        <input type="text" class="form-control" name="division">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>Responsibility Center:</strong></label>
                        <input type="text" class="form-control" name="responsibility_center">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>RIS NO:</strong></label>
                        <input type="text" class="form-control" name="ris_no" id="ris_no" placeholder="Enter RIS number manually">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>DATE:</strong></label>
                        <input type="date" class="form-control" name="date">
                    </div>
                </div>
                
                <!-- Entity Fields Values -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label"><strong>OFFICE:</strong></label>
                        <select class="form-control" name="office" required>
                            <option value="">Select Office</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo htmlspecialchars($office['office_name']); ?>">
                                    <?php echo htmlspecialchars($office['office_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>Code:</strong></label>
                        <input type="text" class="form-control" name="code" id="code" placeholder="Enter code manually">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>SAI NO.:</strong></label>
                        <input type="text" class="form-control" name="sai_no" id="sai_no" placeholder="Enter SAI number manually">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>Date:</strong></label>
                        <input type="date" class="form-control" name="date_2">
                    </div>
                </div>
                            
                            <!-- Items Table -->
                            <div class="mb-3">
                                <label class="form-label"><strong>Items:</strong></label>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="risItemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Stock No.</th>
                                                <th>Unit</th>
                                                <th>Description</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Total Amount</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="stock_no[]" readonly></td>
                                                <td>
                                                    <select class="form-control form-control-sm" name="unit[]" required>
                                                        <option value="">Select Unit</option>
                                                        <option value="pc">pc</option>
                                                        <option value="pcs">pcs</option>
                                                        <option value="piece">piece</option>
                                                        <option value="pieces">pieces</option>
                                                        <option value="set">set</option>
                                                        <option value="sets">sets</option>
                                                        <option value="unit">unit</option>
                                                        <option value="units">units</option>
                                                        <option value="box">box</option>
                                                        <option value="boxes">boxes</option>
                                                        <option value="pack">pack</option>
                                                        <option value="packs">packs</option>
                                                        <option value="bottle">bottle</option>
                                                        <option value="bottles">bottles</option>
                                                        <option value="liter">liter</option>
                                                        <option value="liters">liters</option>
                                                        <option value="gallon">gallon</option>
                                                        <option value="gallons">gallons</option>
                                                        <option value="kilogram">kilogram</option>
                                                        <option value="kilograms">kilograms</option>
                                                        <option value="gram">gram</option>
                                                        <option value="grams">grams</option>
                                                        <option value="meter">meter</option>
                                                        <option value="meters">meters</option>
                                                        <option value="foot">foot</option>
                                                        <option value="feet">feet</option>
                                                        <option value="inch">inch</option>
                                                        <option value="inches">inches</option>
                                                        <option value="ream">ream</option>
                                                        <option value="reams">reams</option>
                                                        <option value="dozen">dozen</option>
                                                        <option value="dozens">dozens</option>
                                                        <option value="pair">pair</option>
                                                        <option value="pairs">pairs</option>
                                                        <option value="roll">roll</option>
                                                        <option value="rolls">rolls</option>
                                                        <option value="bag">bag</option>
                                                        <option value="bags">bags</option>
                                                        <option value="can">can</option>
                                                        <option value="cans">cans</option>
                                                        <option value="tube">tube</option>
                                                        <option value="tubes">tubes</option>
                                                    </select>
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm" name="description[]" required></td>
                                                <td><input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateTotal(this)"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="price[]" step="0.01" onchange="calculateTotal(this)"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="total_amount[]" readonly step="0.01"></td>
                                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRISRow(this)"><i class="bi bi-trash"></i></button></td>
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
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addRISRow()">
                                    <i class="bi bi-plus-circle"></i> Add Row
                                </button>
                            </div>
                            
                            <!-- Purpose -->
                            <div class="mb-3">
                                <label class="form-label"><strong>Purpose:</strong></label>
                                <textarea class="form-control" name="purpose" rows="3" required></textarea>
                            </div>
                            
                            <!-- Signature Section -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="border p-3 text-center">
                                        <label class="form-label"><strong>REQUESTED BY:</strong></label>
                                        <div class="mb-3">
                                            <small class="text-muted">SIGNATURE:</small>
                                            <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">PRINTED NAME:</small>
                                            <input type="text" class="form-control form-control-sm" name="requested_by" value="<?php echo htmlspecialchars($latest_signature['requested_by'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="requested_by_position" value="<?php echo htmlspecialchars($latest_signature['requested_by_position'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="requested_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border p-3 text-center">
                                        <label class="form-label"><strong>APPROVED BY:</strong></label>
                                        <div class="mb-3">
                                            <small class="text-muted">SIGNATURE:</small>
                                            <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">PRINTED NAME:</small>
                                            <input type="text" class="form-control form-control-sm" name="approved_by" value="<?php echo htmlspecialchars($latest_signature['approved_by'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="approved_by_position" value="<?php echo htmlspecialchars($latest_signature['approved_by_position'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="approved_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border p-3 text-center">
                                        <label class="form-label"><strong>ISSUED BY:</strong></label>
                                        <div class="mb-3">
                                            <small class="text-muted">SIGNATURE:</small>
                                            <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">PRINTED NAME:</small>
                                            <input type="text" class="form-control form-control-sm" name="issued_by" value="<?php echo htmlspecialchars($latest_signature['issued_by'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="issued_by_position" value="<?php echo htmlspecialchars($latest_signature['issued_by_position'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="issued_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border p-3 text-center">
                                        <label class="form-label"><strong>RECEIVED BY:</strong></label>
                                        <div class="mb-3">
                                            <small class="text-muted">SIGNATURE:</small>
                                            <div style="height: 60px; border-bottom: 1px solid #ccc;"></div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">PRINTED NAME:</small>
                                            <input type="text" class="form-control form-control-sm" name="received_by" value="<?php echo htmlspecialchars($latest_signature['received_by'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="received_by_position" value="<?php echo htmlspecialchars($latest_signature['received_by_position'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="received_date">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save RIS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addRISRow() {
            const table = document.getElementById('risItemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            const units = <?php 
                $options = '<option value="">Select Unit</option>';
                foreach ($units as $unit) {
                    $singular = getSingularForm($unit['unit_name']);
                    $options .= '<option value="' . htmlspecialchars($unit['unit_code']) . '" data-unit-name="' . htmlspecialchars($unit['unit_name']) . '" data-singular="' . htmlspecialchars($singular) . '">' . htmlspecialchars($unit['unit_name']) . '</option>';
                }
                echo json_encode($options);
            ?>;
            
            const cells = [
                '<input type="text" class="form-control form-control-sm" name="stock_no[]" readonly>',
                `<select class="form-control form-control-sm" name="unit[]" required><option value="">Select Unit</option>${units}</select>`,
                '<input type="text" class="form-control form-control-sm" name="description[]" required>',
                '<input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateTotal(this)">',
                '<input type="number" class="form-control form-control-sm" name="price[]" step="0.01" onchange="calculateTotal(this)">',
                '<input type="number" class="form-control form-control-sm" name="total_amount[]" readonly step="0.01">',
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeRISRow(this)"><i class="bi bi-trash"></i></button>'
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
            
            // Update stock numbers
            updateStockNumbers();
        }
        
        function removeRISRow(button) {
            const row = button.closest('tr');
            const table = document.getElementById('risItemsTable').getElementsByTagName('tbody')[0];
            
            if (table.rows.length > 1) {
                row.remove();
                // Update stock numbers after removal
                updateStockNumbers();
                // Update grand total after removal
                updateGrandTotal();
            } else {
                alert('At least one row is required');
            }
        }
        
        function updateGrandTotal() {
            const totalAmountInputs = document.querySelectorAll('input[name="total_amount[]"]');
            let grandTotal = 0;
            
            totalAmountInputs.forEach(input => {
                const total = parseFloat(input.value) || 0;
                grandTotal += total;
            });
            
            // Update the grand total display
            const grandTotalElement = document.getElementById('grandTotal');
            if (grandTotalElement) {
                grandTotalElement.textContent = grandTotal.toFixed(2);
            }
        }
        
        // Function to update unit display based on quantity for RIS form
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
                        
                        console.log('RIS: Changed to singular:', originalName, '->', singularName);
                    }
                }
            }
        }
        
        // Function to update all unit displays in the table
        function updateAllUnitDisplays() {
            const table = document.getElementById('risItemsTable');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                updateUnitDisplayForRow(row);
            });
        }
        
        function updateStockNumbers() {
            const table = document.getElementById('risItemsTable').getElementsByTagName('tbody')[0];
            const stockNoInputs = table.querySelectorAll('input[name="stock_no[]"]');
            
            stockNoInputs.forEach((input, index) => {
                input.value = index + 1;
            });
        }
        
        function calculateTotal(element) {
            const row = element.closest('tr');
            const quantity = row.querySelector('input[name="quantity[]"]').value || 0;
            const price = row.querySelector('input[name="price[]"]').value || 0;
            const totalAmount = row.querySelector('input[name="total_amount[]"]');
            
            // Update unit display based on quantity
            updateUnitDisplayForRow(row);
            
            // Calculate total amount
            const total = parseFloat(quantity) * parseFloat(price);
            if (totalAmount) {
                totalAmount.value = total.toFixed(2);
            }
            
            // Update grand total
            updateGrandTotal();
        }
        
        function resetRISForm() {
            if (confirm('Are you sure you want to reset form? All data will be lost.')) {
                document.getElementById('risForm').reset();
                const table = document.getElementById('risItemsTable').getElementsByTagName('tbody')[0];
                while (table.rows.length > 1) {
                    table.deleteRow(1);
                }
                // Reset stock numbers
                updateStockNumbers();
                // Reset grand total
                updateGrandTotal();
            }
        }
        
        // Handle Excel Import for Consolidated Office Supplies
        async function handleExcelImport(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet);

                    if (jsonData.length === 0) {
                        alert('The selected Excel file is empty or invalid.');
                        return;
                    }

                    // Attempt to auto-select the office based on filename (e.g., OMM_Office_Supplies.xlsx)
                    const fileName = file.name;
                    const officePart = fileName.split('_')[0];
                    if (officePart) {
                        const officeSelect = document.querySelector('select[name="office"]');
                        if (officeSelect) {
                            for (let i = 0; i < officeSelect.options.length; i++) {
                                const opt = officeSelect.options[i];
                                if (opt.value.toLowerCase().includes(officePart.toLowerCase()) || 
                                    officePart.toLowerCase().includes(opt.value.toLowerCase())) {
                                    officeSelect.selectedIndex = i;
                                    break;
                                }
                            }
                        }
                    }

                    // Clear existing rows (if more than one row or if the first row is not empty)
                    const table = document.getElementById('risItemsTable').getElementsByTagName('tbody')[0];
                    const firstRow = table.rows[0];
                    const firstDesc = firstRow.querySelector('input[name="description[]"]').value.trim();
                    
                    if (firstDesc !== '') {
                        if (confirm('Replace current items with Excel data?')) {
                            while (table.rows.length > 0) table.deleteRow(0);
                        } else {
                            input.value = '';
                            return;
                        }
                    } else {
                        // Just clear the first empty row to start fresh
                        table.deleteRow(0);
                    }

                    // Map Excel columns to RIS fields
                    // Consolidated Excel format from consumable_requests.php:
                    // 'Item Description', 'Unit', 'Quantity'
                    
                    // Step 1: Collect/Find all unique units in the Excel file to pre-populate missing ones
                    let unitOptions = `<?php 
                        $options = '<option value="">Select Unit</option>';
                        foreach ($units as $unit) {
                            $singular = getSingularForm($unit['unit_name']);
                            $options .= '<option value="' . htmlspecialchars($unit['unit_code']) . '" data-unit-name="' . htmlspecialchars($unit['unit_name']) . '" data-singular="' . htmlspecialchars($singular) . '">' . htmlspecialchars($unit['unit_name']) . '</option>';
                        }
                        echo $options;
                    ?>`;

                    // Check which units from Excel are missing in the current list
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = `<select>${unitOptions}</select>`;
                    const existingSelect = tempDiv.firstChild;
                    const existingUnits = Array.from(existingSelect.options).map(o => o.value.toLowerCase());
                    const existingTexts = Array.from(existingSelect.options).map(o => o.text.toLowerCase());
                    
                    jsonData.forEach(row => {
                        const u = row['Unit'] || row['unit'];
                        if (u) {
                            const uTrim = String(u).trim();
                            const uLower = uTrim.toLowerCase();
                            if (!existingUnits.includes(uLower) && !existingTexts.includes(uLower)) {
                                unitOptions += `<option value="${uTrim}">${uTrim}</option>`;
                                existingUnits.push(uLower); // Prevent duplicate adds
                            }
                        }
                    });

                    jsonData.forEach((row, index) => {
                        const desc = row['Item Description'] || row['Description'] || '';
                        const unit = row['Unit'] || '';
                        const qty = row['Quantity'] || 0;

                        if (!desc && !qty) return;

                        // Add new row logic
                        const newRow = table.insertRow();
                        
                        newRow.innerHTML = `
                            <td><input type="text" class="form-control form-control-sm" name="stock_no[]" value="${table.rows.length}" readonly></td>
                            <td>
                                <select class="form-control form-control-sm" name="unit[]" required onchange="updateUnitDisplayForRow(this.closest('tr'))">
                                    ${unitOptions}
                                </select>
                            </td>
                            <td><input type="text" class="form-control form-control-sm" name="description[]" value="${desc.replace(/"/g, '&quot;')}" required></td>
                            <td><input type="number" class="form-control form-control-sm" name="quantity[]" value="${qty}" required onchange="calculateTotal(this)"></td>
                            <td><input type="number" class="form-control form-control-sm" name="price[]" step="0.01" onchange="calculateTotal(this)"></td>
                            <td><input type="number" class="form-control form-control-sm" name="total_amount[]" readonly step="0.01" value="0.00"></td>
                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRISRow(this)"><i class="bi bi-trash"></i></button></td>
                        `;

                        // Match Unit
                        if (unit) {
                            const unitSelect = newRow.querySelector('select[name="unit[]"]');
                            const normUnit = unit.toLowerCase().trim();
                            for (let i = 0; i < unitSelect.options.length; i++) {
                                const opt = unitSelect.options[i];
                                if (opt.value.toLowerCase() === normUnit || opt.text.toLowerCase() === normUnit) {
                                    unitSelect.selectedIndex = i;
                                    break;
                                }
                            }
                        }

                        // Trigger unit display update
                        updateUnitDisplayForRow(newRow);
                    });

                    updateGrandTotal();
                    alert('Successfully imported ' + jsonData.length + ' items.');

                } catch (err) {
                    console.error(err);
                    alert('Error parsing Excel file: ' + err.message);
                }
                input.value = ''; // Reset input
            };
            reader.readAsArrayBuffer(file);
        }

        // Handle form submission
        document.getElementById('risForm').addEventListener('submit', function(e) {
            // Client-side validation before submission
            const form = e.target;
            
            // Get field values with better error handling
            const risNo = form.querySelector('[name="ris_no"]')?.value?.trim() || '';
            const office = form.querySelector('[name="office"]')?.value || '';
            const purpose = form.querySelector('[name="purpose"]')?.value?.trim() || '';
            const requestedBy = form.querySelector('[name="requested_by"]')?.value?.trim() || '';
            const requestedByPosition = form.querySelector('[name="requested_by_position"]')?.value?.trim() || '';
            const approvedBy = form.querySelector('[name="approved_by"]')?.value?.trim() || '';
            const approvedByPosition = form.querySelector('[name="approved_by_position"]')?.value?.trim() || '';
            const issuedBy = form.querySelector('[name="issued_by"]')?.value?.trim() || '';
            const issuedByPosition = form.querySelector('[name="issued_by_position"]')?.value?.trim() || '';
            const receivedBy = form.querySelector('[name="received_by"]')?.value?.trim() || '';
            const receivedByPosition = form.querySelector('[name="received_by_position"]')?.value?.trim() || '';
            
            // Debug: Log values to check
            console.log('Validation Debug:', {
                risNo, office, purpose, requestedBy, requestedByPosition,
                approvedBy, approvedByPosition, issuedBy, issuedByPosition,
                receivedBy, receivedByPosition
            });
            
            // Check essential fields
            const missingFields = [];
            if (!risNo) missingFields.push('RIS No');
            if (!office) missingFields.push('Office');
            if (!purpose) missingFields.push('Purpose');
            if (!requestedBy) missingFields.push('Requested By Name');
            if (!requestedByPosition) missingFields.push('Requested By Position');
            if (!approvedBy) missingFields.push('Approved By Name');
            if (!approvedByPosition) missingFields.push('Approved By Position');
            if (!issuedBy) missingFields.push('Issued By Name');
            if (!issuedByPosition) missingFields.push('Issued By Position');
            if (!receivedBy) missingFields.push('Received By Name');
            if (!receivedByPosition) missingFields.push('Received By Position');
            
            if (missingFields.length > 0) {
                e.preventDefault();
                alert('Please fill in the following required fields:\n' + missingFields.join('\n'));
                return false;
            }
            
            // Check if at least one item is filled
            const descriptions = form.querySelectorAll('[name="description[]"]');
            const quantities = form.querySelectorAll('[name="quantity[]"]');
            const units = form.querySelectorAll('[name="unit[]"]');
            
            let hasValidItem = false;
            for (let i = 0; i < descriptions.length; i++) {
                if (descriptions[i]?.value?.trim() && quantities[i]?.value && units[i]?.value) {
                    hasValidItem = true;
                    break;
                }
            }
            
            if (!hasValidItem) {
                e.preventDefault();
                alert('Please add at least one complete item with description, quantity, and unit.');
                return false;
            }
            
            console.log('Validation passed - submitting form');
        });
        
        // Initialize stock numbers and grand total on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateStockNumbers();
            updateGrandTotal();
            
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
        });
        
    </script>
</body>
</html>
