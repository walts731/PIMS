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

// Get form data from database
$form_data = [];
try {
    $stmt = $conn->prepare("SELECT * FROM par_forms ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $form_data[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching PAR forms: " . $e->getMessage());
}

// Get header image if any
$header_image = '';
try {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_name = 'header_image'");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $header_image = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching header image: " . $e->getMessage());
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
                    <button class="btn btn-outline-primary btn-sm" onclick="createNewPAR()">
                        <i class="bi bi-plus-circle"></i> Create New PAR
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportPARData()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

                <!-- PAR Form Management -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="bi bi-pencil-square"></i> PAR Form
                </h5>
                <div class="no-print">
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetForm()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                    <button class="btn btn-sm btn-outline-info ms-2" onclick="printForm()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
            
            <ul class="nav nav-tabs mb-4" id="parTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="par-input-tab" data-bs-toggle="tab" data-bs-target="#par-input" type="button" role="tab">
                        <i class="bi bi-pencil-square"></i> PAR Input
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="par-entries-tab" data-bs-toggle="tab" data-bs-target="#par-entries" type="button" role="tab">
                        <i class="bi bi-list"></i> PAR Entries
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="parTabsContent">
                <!-- PAR Input Tab -->
                <div class="tab-pane fade show active" id="par-input" role="tabpanel">
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
                            <div style="text-align: center;">
                                <p style="margin: 0; font-size: 16px; font-weight: bold;">PROPERTY ACKNOWLEDGEMENT RECEIPT</p>
                                <p style="margin: 0; font-size: 12px;">MUNICIPALITY OF PILAR</p>
                                <p style="margin: 0; font-size: 12px;">OMM</p>
                                <p style="margin: 0; font-size: 12px;">OFFICE/LOCATION</p>
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
                                <input type="text" class="form-control" name="par_no" required>
                            </div>
                        </div>
                        
                        <!-- Items Table -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Items:</strong></label>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item No.</th>
                                            <th>Description</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Unit Price</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm" name="item_no[]" required></td>
                                            <td><input type="text" class="form-control form-control-sm" name="description[]" required></td>
                                            <td><input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateAmount(this)"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="unit[]" required></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="unit_price[]" required onchange="calculateAmount(this)"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" readonly></td>
                                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addRow()">
                                <i class="bi bi-plus-circle"></i> Add Row
                            </button>
                        </div>
                        
                        <!-- Remarks -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Remarks:</strong></label>
                            <textarea class="form-control" name="remarks" rows="3"></textarea>
                        </div>
                        
                        <!-- Signature Section -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Received by:</strong></label>
                                <input type="text" class="form-control" name="received_by" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>Issued by:</strong></label>
                                <input type="text" class="form-control" name="issued_by" required>
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
                
                <!-- PAR Entries Tab -->
                <div class="tab-pane fade" id="par-entries" role="tabpanel">
                    <?php if (empty($form_data)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No PAR entries found</p>
                            <button class="btn btn-primary" onclick="createNewPAR()">
                                <i class="bi bi-plus-circle"></i> Create First PAR
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="parTable" class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>PAR No.</th>
                                        <th>Entity Name</th>
                                        <th>Received By</th>
                                        <th>Office</th>
                                        <th>Date Received</th>
                                        <th>Items Count</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($form_data as $entry): ?>
                                        <?php
                                        // Get item count for this PAR
                                        $item_count_result = $conn->query("SELECT COUNT(*) as count FROM par_items WHERE form_id = " . $entry['id']);
                                        $item_count = $item_count_result->fetch_assoc()['count'];
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($entry['par_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($entry['entity_name']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['received_by_name']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['office_name'] ?? 'Not assigned'); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($entry['date_received_left'])); ?></td>
                                            <td><span class="badge bg-info"><?php echo $item_count; ?> items</span></td>
                                            <td>
                                                <div class="form-actions">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewPAR(<?php echo $entry['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" onclick="manageItems(<?php echo $entry['id']; ?>)">
                                                        <i class="bi bi-box"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
            
            const cells = [
                '<input type="text" class="form-control form-control-sm" name="item_no[]" required>',
                '<input type="text" class="form-control form-control-sm" name="description[]" required>',
                '<input type="number" class="form-control form-control-sm" name="quantity[]" required onchange="calculateAmount(this)">',
                '<input type="text" class="form-control form-control-sm" name="unit[]" required>',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="unit_price[]" required onchange="calculateAmount(this)">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="amount[]" readonly>',
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
            const row = input.closest('tr');
            const quantity = row.querySelector('input[name="quantity[]"]').value || 0;
            const unitPrice = row.querySelector('input[name="unit_price[]"]').value || 0;
            const amount = (parseFloat(quantity) * parseFloat(unitPrice)).toFixed(2);
            
            row.querySelector('input[name="amount[]"]').value = amount;
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
        
        function printForm() {
            window.print();
        }
        
        function createNewPAR() {
            // Switch to input tab
            const inputTab = document.getElementById('par-input-tab');
            const tab = new bootstrap.Tab(inputTab);
            tab.show();
        }
    </script>
</body>
</html>
