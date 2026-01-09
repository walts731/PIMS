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

logSystemAction($_SESSION['user_id'], 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php');

// Get next ICS number
$next_ics_no = getNextTagPreview('ics_no');
if ($next_ics_no === null) {
    $next_ics_no = ''; // Fallback if no configuration exists
}

// Get ICS configuration for JavaScript
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
    'Rolls',
    'Bags',
    'Canisters',
    'Jars',
    'Tubes',
    'Pounds',
    'Ounces',
    'Dozens',
    'Pairs',
    'Reams',
    'Cases',
    'Barrels',
    'Drums'
];

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
                    <a href="ics_entries.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-list"></i> View Entries
                    </a>
                </div>
            </div>
        </div>

        <!-- ICS Form -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> ICS Form
                </h5>
                <div class="no-print">
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetICSForm()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
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
                        <p style="margin: 0; font-size: 12px;">OMM</p>
                        <p style="margin: 0; font-size: 12px;">OFFICE/LOCATION</p>
                    </div>
                </div>
                
                <!-- Entity Name, Fund Cluster, and ICS No -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Entity Name:</strong></label>
                                    <select class="form-select" name="entity_name" required>
                                        <option value="">Select Office</option>
                                        <?php foreach ($offices as $office): ?>
                                            <option value="<?php echo htmlspecialchars($office['office_name']); ?>">
                                                <?php echo htmlspecialchars($office['office_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><strong>Fund Cluster:</strong></label>
                                    <input type="text" class="form-control" name="fund_cluster" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><strong>ICS No:</strong></label>
                                    <input type="text" class="form-control bg-light" name="ics_no" id="ics_no" value="<?php echo htmlspecialchars($next_ics_no); ?>" readonly>
                                    <small class="text-muted">Auto-generated next number from system configuration.</small>
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
                                                <th>Estimated Useful Life</th>
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
                                                <td><input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateTotal(this)"></td>
                                                <td>
                                                    <select class="form-select form-select-sm" name="unit[]" required>
                                                        <option value="">Select Unit</option>
                                                        <?php foreach ($common_units as $unit): ?>
                                                            <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="unit_cost[]" required onchange="calculateTotal(this)"></td>
                                                <td><input type="number" step="0.01" class="form-control form-control-sm" name="total_cost[]" readonly></td>
                                                <td><input type="text" class="form-control form-control-sm" name="description[]" required></td>
                                                <td><input type="text" class="form-control form-control-sm bg-light" name="item_no[]" value="1" readonly></td>
                                                <td><input type="text" class="form-control form-control-sm" name="useful_life[]" required></td>
                                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeICSRow(this)"><i class="bi bi-trash"></i></button></td>
                                            </tr>
                                        </tbody>
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
                                    <input type="text" class="form-control" name="received_from" required value="<?php echo htmlspecialchars($latest_signature['received_from'] ?? ''); ?>">
                                    <label class="form-label"><strong>Position/Office:</strong></label>
                                    <input type="text" class="form-control" name="received_from_position" required value="<?php echo htmlspecialchars($latest_signature['received_from_position'] ?? ''); ?>">
                                    <label class="form-label"><strong>Date:</strong></label>
                                    <input type="date" class="form-control" name="received_from_date">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Received by:</strong></label>
                                    <input type="text" class="form-control" name="received_by" required value="<?php echo htmlspecialchars($latest_signature['received_by'] ?? ''); ?>">
                                    <label class="form-label"><strong>Position/Office:</strong></label>
                                    <input type="text" class="form-control" name="received_by_position" required value="<?php echo htmlspecialchars($latest_signature['received_by_position'] ?? ''); ?>">
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

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addICSRow() {
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            
            // Create unit dropdown HTML
            const unitOptions = <?php 
                $options = '<option value="">Select Unit</option>';
                foreach ($common_units as $unit) {
                    $options .= '<option value="' . htmlspecialchars($unit) . '">' . htmlspecialchars($unit) . '</option>';
                }
                echo json_encode($options);
            ?>;
            
            // Get next item number
            const nextItemNumber = table.rows.length;
            
            const cells = [
                '<input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateTotal(this)">',
                '<select class="form-select form-select-sm" name="unit[]" required>' + unitOptions + '</select>',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="unit_cost[]" required onchange="calculateTotal(this)">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="total_cost[]" readonly>',
                '<input type="text" class="form-control form-control-sm" name="description[]" required>',
                '<input type="text" class="form-control form-control-sm bg-light" name="item_no[]" value="' + nextItemNumber + '" readonly>',
                '<input type="text" class="form-control form-control-sm" name="useful_life[]" required>',
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeICSRow(this)"><i class="bi bi-trash"></i></button>'
            ];
            
            cells.forEach((cellHtml, index) => {
                const cell = newRow.insertCell(index);
                cell.innerHTML = cellHtml;
            });
        }
        
        function removeICSRow(button) {
            const row = button.closest('tr');
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            
            if (table.rows.length > 1) {
                row.remove();
                // Renumber items after removal
                renumberItems();
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
            
            // Validate unit cost should be ₱50,000 or below
            if (parseFloat(unitCost) > 50000) {
                showToast('Unit cost should be ₱50,000 or below. Please enter a valid amount.', 'danger');
                row.querySelector('input[name="unit_cost[]"]').value = '';
                row.querySelector('input[name="total_cost[]"]').value = '';
                row.querySelector('input[name="unit_cost[]"]').focus();
                return;
            }
            
            row.querySelector('input[name="total_cost[]"]').value = totalCost;
        }
        
        function resetICSForm() {
            if (confirm('Are you sure you want to reset form? All data will be lost.')) {
                document.getElementById('icsForm').reset();
                const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
                while (table.rows.length > 1) {
                    table.deleteRow(1);
                }
                // Reset the first row item number to 1
                const firstRow = table.rows[0];
                const itemNoInput = firstRow.cells[5].querySelector('input[name="item_no[]"]');
                if (itemNoInput) {
                    itemNoInput.value = '1';
                }
            }
        }
        
        function createNewICS() {
            document.getElementById('icsForm').reset();
            // Generate fresh ICS number
            generateNewIcsNumber();
            // Reset item numbering
            const table = document.getElementById('icsItemsTable').getElementsByTagName('tbody')[0];
            while (table.rows.length > 1) {
                table.deleteRow(1);
            }
            // Reset the first row item number to 1
            const firstRow = table.rows[0];
            const itemNoInput = firstRow.cells[5].querySelector('input[name="item_no[]"]');
            if (itemNoInput) {
                itemNoInput.value = '1';
            }
        }
        
        // Generate new ICS number via AJAX
        function generateNewIcsNumber() {
            <?php if ($ics_config): ?>
            const components = <?php 
                $components = json_decode($ics_config['format_components'], true);
                if (is_string($components)) {
                    $components = json_decode($components, true);
                }
                echo json_encode($components ?: []);
            ?>;
            const digits = <?php echo $ics_config['digits']; ?>;
            const separator = '<?php echo $ics_config['separator']; ?>';
            
            fetch('../SYSTEM_ADMIN/tags.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_preview&tag_type=ics_no&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
            })
            .then(response => response.json())
            .then(data => {
                if (data.preview) {
                    document.getElementById('ics_no').value = data.preview;
                }
            })
            .catch(error => {
                console.error('Error generating ICS number:', error);
            });
            <?php endif; ?>
        }
        
        // Handle form submission to update counter
        document.getElementById('icsForm').addEventListener('submit', function(e) {
            // Always increment counter since field is always auto-generated
            const incrementField = document.createElement('input');
            incrementField.type = 'hidden';
            incrementField.name = 'increment_ics_counter';
            incrementField.value = '1';
            this.appendChild(incrementField);
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
    </script>
</body>
</html>
