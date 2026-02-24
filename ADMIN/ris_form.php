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

logSystemAction($_SESSION['user_id'], 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php');

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

        <!-- RIS Form -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> RIS Form
                </h5>
                <div class="no-print">
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetRISForm()">
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
                        <input type="text" class="form-control" name="division" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>Responsibility Center:</strong></label>
                        <input type="text" class="form-control" name="responsibility_center" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>RIS NO:</strong></label>
                        <input type="text" class="form-control" name="ris_no" id="ris_no" placeholder="Enter RIS number manually">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>DATE:</strong></label>
                        <input type="date" class="form-control" name="date" required>
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
                        <input type="date" class="form-control" name="date_2" required>
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
                                                        <option value="pcs">pcs</option>
                                                        <option value="sets">sets</option>
                                                        <option value="boxes">boxes</option>
                                                        <option value="packs">packs</option>
                                                        <option value="bottles">bottles</option>
                                                        <option value="liters">liters</option>
                                                        <option value="gallons">gallons</option>
                                                        <option value="kilograms">kilograms</option>
                                                        <option value="grams">grams</option>
                                                        <option value="meters">meters</option>
                                                        <option value="feet">feet</option>
                                                        <option value="inches">inches</option>
                                                        <option value="reams">reams</option>
                                                        <option value="dozens">dozens</option>
                                                        <option value="pairs">pairs</option>
                                                        <option value="rolls">rolls</option>
                                                        <option value="bags">bags</option>
                                                        <option value="cans">cans</option>
                                                        <option value="tubes">tubes</option>
                                                        <option value="units">units</option>
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
            
            const units = [
                'pcs', 'sets', 'boxes', 'packs', 'bottles', 'liters', 'gallons', 
                'kilograms', 'grams', 'meters', 'feet', 'inches', 'reams', 
                'dozens', 'pairs', 'rolls', 'bags', 'cans', 'tubes', 'units'
            ].map(unit => `<option value="${unit}">${unit}</option>`).join('');
            
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
            
            const total = parseFloat(quantity) * parseFloat(price);
            totalAmount.value = total.toFixed(2);
            
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
        
        // Handle form submission
        document.getElementById('risForm').addEventListener('submit', function(e) {
            // No auto-increment needed for manual fields
        });
        
        // Initialize stock numbers and grand total on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateStockNumbers();
            updateGrandTotal();
        });
        
    </script>
</body>
</html>
