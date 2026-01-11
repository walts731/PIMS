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
                        <input type="text" class="form-control bg-light" name="ris_no" id="ris_no" value="<?php echo htmlspecialchars($next_ris_no); ?>" readonly>
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
                        <input type="text" class="form-control" name="office" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>Code:</strong></label>
                        <input type="text" class="form-control bg-light" name="code" id="code" value="<?php echo htmlspecialchars($next_code); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><strong>SAI NO.:</strong></label>
                        <input type="text" class="form-control bg-light" name="sai_no" id="sai_no" value="<?php echo htmlspecialchars($next_sai_no); ?>" readonly>
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
                                                <td><input type="text" class="form-control form-control-sm" name="unit[]" required></td>
                                                <td><input type="text" class="form-control form-control-sm" name="description[]" required></td>
                                                <td><input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateTotal(this)"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="price[]" step="0.01" onchange="calculateTotal(this)"></td>
                                                <td><input type="number" class="form-control form-control-sm" name="total_amount[]" readonly step="0.01"></td>
                                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRISRow(this)"><i class="bi bi-trash"></i></button></td>
                                            </tr>
                                        </tbody>
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
                                            <input type="text" class="form-control form-control-sm" name="requested_by" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="requested_by_position" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="requested_date" required>
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
                                            <input type="text" class="form-control form-control-sm" name="approved_by" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="approved_by_position" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="approved_date" required>
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
                                            <input type="text" class="form-control form-control-sm" name="issued_by" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="issued_by_position" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="issued_date" required>
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
                                            <input type="text" class="form-control form-control-sm" name="received_by" required>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">DESIGNATION:</small>
                                            <input type="text" class="form-control form-control-sm" name="received_by_position" required>
                                        </div>
                                        <div>
                                            <small class="text-muted">DATE:</small>
                                            <input type="date" class="form-control form-control-sm" name="received_date" required>
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
            
            const cells = [
                '<input type="text" class="form-control form-control-sm" name="stock_no[]" readonly>',
                '<input type="text" class="form-control form-control-sm" name="unit[]" required>',
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
            } else {
                alert('At least one row is required');
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
            }
        }
        
        // Generate new RIS number via AJAX
        function generateNewRisNumber() {
            <?php if ($ris_config): ?>
            const components = <?php 
                $components = json_decode($ris_config['format_components'], true);
                if (is_string($components)) {
                    $components = json_decode($components, true);
                }
                echo json_encode($components ?: []);
            ?>;
            const digits = <?php echo $ris_config['digits']; ?>;
            const separator = '<?php echo $ris_config['separator']; ?>';
            
            fetch('../SYSTEM_ADMIN/tags.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_preview&tag_type=ris_no&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
            })
            .then(response => response.json())
            .then(data => {
                if (data.preview) {
                    document.getElementById('ris_no').value = data.preview;
                }
            })
            .catch(error => {
                console.error('Error generating RIS number:', error);
            });
            <?php endif; ?>
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
                    document.getElementById('sai_no').value = data.preview;
                }
            })
            .catch(error => {
                console.error('Error generating SAI number:', error);
            });
            <?php endif; ?>
        }
        
        // Generate new Code via AJAX
        function generateNewCode() {
            <?php if ($code_config): ?>
            const components = <?php 
                $components = json_decode($code_config['format_components'], true);
                if (is_string($components)) {
                    $components = json_decode($components, true);
                }
                echo json_encode($components ?: []);
            ?>;
            const digits = <?php echo $code_config['digits']; ?>;
            const separator = '<?php echo $code_config['separator']; ?>';
            
            fetch('../SYSTEM_ADMIN/tags.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_preview&tag_type=code&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
            })
            .then(response => response.json())
            .then(data => {
                if (data.preview) {
                    document.getElementById('code').value = data.preview;
                }
            })
            .catch(error => {
                console.error('Error generating Code:', error);
            });
            <?php endif; ?>
        }
        
        // Handle form submission to update counters
        document.getElementById('risForm').addEventListener('submit', function(e) {
            // Increment counters for all auto-generated fields
            const incrementRisField = document.createElement('input');
            incrementRisField.type = 'hidden';
            incrementRisField.name = 'increment_ris_counter';
            incrementRisField.value = '1';
            this.appendChild(incrementRisField);
            
            const incrementSaiField = document.createElement('input');
            incrementSaiField.type = 'hidden';
            incrementSaiField.name = 'increment_sai_counter';
            incrementSaiField.value = '1';
            this.appendChild(incrementSaiField);
            
            const incrementCodeField = document.createElement('input');
            incrementCodeField.type = 'hidden';
            incrementCodeField.name = 'increment_code_counter';
            incrementCodeField.value = '1';
            this.appendChild(incrementCodeField);
        });
        
        // Initialize stock numbers on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateStockNumbers();
        });
        
    </script>
</body>
</html>
