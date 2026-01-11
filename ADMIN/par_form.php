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

// Get next PAR number
$next_par_no = getNextTagPreview('par_no');
if ($next_par_no === null) {
    $next_par_no = ''; // Fallback if no configuration exists
}

// Get PAR configuration for JavaScript
$par_config = null;
$result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'par_no' AND status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $par_config = $row;
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
                                            $offices_result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
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
                                <input type="text" class="form-control bg-light" name="par_no" id="par_no" value="<?php echo htmlspecialchars($next_par_no); ?>" readonly>
                                <small class="text-muted">Auto-generated next number from system configuration.</small>
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
                                            <td><input type="text" class="form-control form-control-sm" name="property_number[]"></td>
                                            <td><input type="date" class="form-control form-control-sm" name="date_acquired[]"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" required></td>
                                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    </tbody>
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
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            
            const cells = [
                '<input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateAmount(this)">',
                '<select class="form-select form-select-sm" name="unit[]" required>' + unitOptions + '</select>',
                '<input type="text" class="form-control form-control-sm" name="description[]" required>',
                '<input type="text" class="form-control form-control-sm" name="property_number[]">',
                '<input type="date" class="form-control form-control-sm" name="date_acquired[]">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" required>',
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button>'
            ];
            
            cells.forEach((cellHtml, index) => {
                const cell = newRow.insertCell(index);
                cell.innerHTML = cellHtml;
            });
        }
        
        function removeRow(button) {
            const row = button.closest('tr');
            const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
            
            if (table.rows.length > 1) {
                row.remove();
            } else {
                alert('At least one row is required');
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
        
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
                document.getElementById('parForm').reset();
                // Reset to single row
                const table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
                while (table.rows.length > 1) {
                    table.deleteRow(1);
                }
            }
        }
        
        function viewPAREntries() {
            // Redirect to PAR entries page or open a modal
            // For now, let's redirect to a dedicated PAR entries page
            window.location.href = 'par_entries.php';
        }
        
        // Generate new PAR number via AJAX
        function generateNewParNumber() {
            <?php if ($par_config): ?>
            const components = <?php 
                $components = json_decode($par_config['format_components'], true);
                if (is_string($components)) {
                    $components = json_decode($components, true);
                }
                echo json_encode($components ?: []);
            ?>;
            const digits = <?php echo $par_config['digits']; ?>;
            const separator = '<?php echo $par_config['separator']; ?>';
            
            fetch('../SYSTEM_ADMIN/tags.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_preview&tag_type=par_no&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
            })
            .then(response => response.json())
            .then(data => {
                if (data.preview) {
                    document.getElementById('par_no').value = data.preview;
                }
            })
            .catch(error => {
                console.error('Error generating PAR number:', error);
            });
            <?php endif; ?>
        }
        
        // Handle form submission to update counter
        document.getElementById('parForm').addEventListener('submit', function(e) {
            // Always increment counter since field is always auto-generated
            const incrementField = document.createElement('input');
            incrementField.type = 'hidden';
            incrementField.name = 'increment_par_counter';
            incrementField.value = '1';
            this.appendChild(incrementField);
        });
    </script>
</body>
</html>
