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

logSystemAction($_SESSION['user_id'], 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php');

// Get next ITR number
$next_itr_no = getNextTagPreview('itr_no');
if ($next_itr_no === null) {
    $next_itr_no = ''; // Fallback if no configuration exists
}

// Get ITR configuration for JavaScript
$itr_config = null;
$result = $conn->query("SELECT * FROM tag_formats WHERE tag_type = 'itr_no' AND status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $itr_config = $row;
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'ITR'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}

// Get active employees for dropdown
$employees = [];
$employees_sql = "SELECT id, employee_no, firstname, lastname FROM employees WHERE employment_status IN ('permanent', 'contractual', 'job_order') AND clearance_status = 'uncleared' ORDER BY lastname, firstname";
$employees_result = $conn->query($employees_sql);
while ($employee_row = $employees_result->fetch_assoc()) {
    $employees[] = $employee_row;
}

// Get all active employees for "To" dropdown (can receive assets regardless of clearance)
$to_employees = [];
$to_employees_sql = "SELECT id, employee_no, firstname, lastname FROM employees WHERE employment_status = 'permanent' ORDER BY lastname, firstname";
$to_employees_result = $conn->query($to_employees_sql);
while ($employee_row = $to_employees_result->fetch_assoc()) {
    $to_employees[] = $employee_row;
}

// Get latest signature data from the most recent ITR form
$latest_signature = [];
$result = $conn->query("SELECT approved_by, approved_by_position, approved_date, released_by, released_by_position, released_date, received_by, received_by_position, received_date FROM itr_forms ORDER BY created_at DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $latest_signature = $row;
}

// Check if editing existing ITR form
$itr_data = null;
$itr_items = [];
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $itr_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM itr_forms WHERE id = ?");
    $stmt->bind_param("i", $itr_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $itr_data = $result->fetch_assoc();
        
        // Get ITR items
        $items_stmt = $conn->prepare("SELECT * FROM itr_items WHERE form_id = ? ORDER BY item_no");
        $items_stmt->bind_param("i", $itr_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        while ($item_row = $items_result->fetch_assoc()) {
            $itr_items[] = $item_row;
        }
        $items_stmt->close();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Transfer Request - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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

        .form-control,
        .form-select {
            border-radius: var(--border-radius);
            border: 1px solid #dee2e6;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
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
            .no-print {
                display: none !important;
            }

            .form-card {
                box-shadow: none;
            }
        }

        /* Autocomplete Dropdown Styles */
        .autocomplete-container {
            position: relative;
            width: 100%;
        }

        .autocomplete-dropdown {
            position: fixed;
            background: white;
            border: 2px solid #191BA9;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            max-height: 200px;
            overflow-y: auto;
            z-index: 99999;
            display: none;
            min-width: 300px;
            max-width: 500px;
        }

        .autocomplete-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease-in-out;
        }

        .autocomplete-item:hover {
            background-color: #f8f9fa;
        }

        .autocomplete-item.active {
            background-color: #191BA9;
            color: white;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item strong {
            color: #191BA9;
        }

        .autocomplete-item.active strong {
            color: white;
        }

        /* Employee Search Results Styles */
        .position-relative {
            position: relative !important;
        }

        #from_employee_results, #to_employee_results {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            background: white !important;
            border: 2px solid #191BA9 !important;
            border-top: none !important;
            border-radius: 0 0 0.5rem 0.5rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            max-height: 200px !important;
            overflow-y: auto !important;
            z-index: 99999 !important;
            display: none !important;
            min-width: 100% !important;
        }

        .employee-result-item {
            padding: 10px 15px !important;
            cursor: pointer !important;
            border-bottom: 1px solid #f8f9fa !important;
            transition: all 0.3s ease-in-out !important;
            background: white !important;
        }

        .employee-result-item:hover {
            background-color: #f8f9fa !important;
        }

        .employee-result-item:last-child {
            border-bottom: none !important;
        }

        .employee-result-item strong {
            color: #191BA9 !important;
        }
    </style>
</head>

<body>
    <?php
    // Set page title for topbar
    $page_title = 'Inventory Transfer Request';
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
                            <i class="bi bi-file-earmark-text"></i> Inventory Transfer Request
                        </h1>
                        <p class="text-muted mb-0">Manage Inventory Transfer Request forms</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="itr_entries.php" class="btn btn-outline-secondary">
                            <i class="bi bi-list-ul"></i> View Entries
                        </a>
                    </div>
                </div>
            </div>

            <!-- ITR Form -->
            <div class="form-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square"></i> ITR Form
                    </h5>
                    <div class="no-print">
                        <button class="btn btn-sm btn-outline-secondary" onclick="resetITRForm()">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </button>
                    </div>
                </div>

                <form id="itrForm" method="POST" action="process_itr.php">
                    <!-- ITR Form Header -->
                    <div style="text-align: center; margin-bottom: 20px;">
                        <?php
                        if (!empty($header_image)) {
                            echo '<div style="margin-bottom: 10px;">';
                            echo '<img src="../uploads/forms/' . htmlspecialchars($header_image) . '" alt="Header Image" style="width: 100%; max-height: 120px; object-fit: contain;">';
                            echo '</div>';
                        }
                        ?>

                    </div>

                    <!-- Entity Name, Fund Cluster, and ITR No -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Entity Name:</strong></label>
                            <input type="text" class="form-control" name="entity_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Fund Cluster:</strong></label>
                            <input type="text" class="form-control" name="fund_cluster" required>
                        </div>

                    </div>

                    <!-- Transfer Details -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label"><strong>From Accountable Officer/Agency/Fund Cluster:</strong></label>
                            <select class="form-select" id="from_employee_search" name="from_office" required>
                                <option value="">Select Employee (Uncleared Only)</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_no'] . ' - ' . $employee['lastname'] . ', ' . $employee['firstname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>ITR No:</strong></label>
                            <input type="text" class="form-control bg-light" name="itr_no" id="itr_no" value="<?php echo htmlspecialchars($next_itr_no); ?>" readonly>

                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label"><strong>To Accountable Officer/Agency/Fund Cluster:</strong></label>
                            <select class="form-select" id="to_employee_search" name="to_office" required>
                                <option value="">Select Employee (Permanent Only)</option>
                                <?php foreach ($to_employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_no'] . ' - ' . $employee['lastname'] . ', ' . $employee['firstname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Date:</strong></label>
                            <input type="date" class="form-control" name="transfer_date" value="<?php echo $itr_data ? htmlspecialchars($itr_data['transfer_date']) : date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label"><strong>Transfer Type:</strong></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transfer_type" id="donation" value="Donation" required>
                                    <label class="form-check-label" for="donation">Donation</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transfer_type" id="reassignment" value="Reassignment">
                                    <label class="form-check-label" for="reassignment">Reassignment</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transfer_type" id="relocate" value="Relocate">
                                    <label class="form-check-label" for="relocate">Relocate</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transfer_type" id="others" value="Others">
                                    <label class="form-check-label" for="others">Others</label>
                                </div>
                            </div>
                            <div class="mt-2" id="others_input_div" style="display: none;">
                                <label class="form-label"><strong>Specify:</strong></label>
                                <input type="text" class="form-control" name="transfer_type_others" id="transfer_type_others" placeholder="Please specify transfer type">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>End User:</strong></label>
                            <input type="text" class="form-control" name="end_user" placeholder="Enter end user name">
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Items:</strong></label>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itrItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 10%;">Date Acquired</th>
                                        <th style="width: 8%;">Item No.</th>
                                        <th style="width: 15%;">ICS & PAR No./Date</th>
                                        <th style="width: 25%;">Description</th>
                                        <th style="width: 10%;">Quantity</th>
                                        <th style="width: 10%;">Unit Price</th>
                                        <th style="width: 12%;">Total Amount</th>
                                        <th style="width: 12%;">Condition of Inventory</th>
                                        <th style="width: 10%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="date" class="form-control form-control-sm" name="date_acquired[]" value="<?php echo date('Y-m-d'); ?>" required></td>
                                        <td><input type="text" class="form-control form-control-sm" name="item_no[]" value="1" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm" name="ics_par_no[]" placeholder="ICS & PAR No./Date"></td>
                                        <td>
                                            <select class="form-select form-select-sm" name="description[]" required>
                                                <option value="">Select Asset</option>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="quantity[]" value="1" min="1" onchange="calculateITRTotal(this)"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="unit_price[]" required onchange="calculateITRTotal(this)"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="total_amount[]" readonly></td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="condition[]" value="serviceable" readonly>
                                        </td>
                                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeITRRow(this)"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addITRRow()">
                            <i class="bi bi-plus-circle"></i> Add Row
                        </button>
                    </div>

                    <!-- Purpose -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Purpose of Transfer:</strong></label>
                        <textarea class="form-control" name="purpose" rows="3" required></textarea>
                    </div>

                    <!-- Signature Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">

                        </div>
                        <div class="col-md-3">
                            <p class="text-center">Approved by:</p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-center">Released/Issued by:</p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-center">Received by:</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                           
                            <p class="mb-5">Printed Name:</p>
                            <p class="mb-5">Designation:</p>
                            <p class="mb-3">Date:</p>
                        </div>

                        <div class="col-md-3">

                            <input type="text" class="form-control mb-3" name="approved_by" value="<?php echo $itr_data ? htmlspecialchars($itr_data['approved_by']) : (isset($latest_signature['approved_by']) ? htmlspecialchars($latest_signature['approved_by']) : ''); ?>" required>

                            <input type="text" class="form-control mb-3" name="approved_by_position" value="<?php echo $itr_data ? htmlspecialchars($itr_data['approved_by_position']) : (isset($latest_signature['approved_by_position']) ? htmlspecialchars($latest_signature['approved_by_position']) : ''); ?>" required>

                            <input type="date" class="form-control mb-3" name="approved_date" value="<?php echo $itr_data ? htmlspecialchars($itr_data['approved_date']) : (isset($latest_signature['approved_date']) ? htmlspecialchars($latest_signature['approved_date']) : ''); ?>">
                        </div>

                        <div class="col-md-3">
                            
                            <input type="text" class="form-control mb-3" name="released_by" value="<?php echo $itr_data ? htmlspecialchars($itr_data['released_by']) : (isset($latest_signature['released_by']) ? htmlspecialchars($latest_signature['released_by']) : ''); ?>" required>

                            <input type="text" class="form-control mb-3" name="released_by_position" value="<?php echo $itr_data ? htmlspecialchars($itr_data['released_by_position']) : (isset($latest_signature['released_by_position']) ? htmlspecialchars($latest_signature['released_by_position']) : ''); ?>" required>

                            <input type="date" class="form-control mb-3" name="released_date" value="<?php echo $itr_data ? htmlspecialchars($itr_data['released_date']) : (isset($latest_signature['released_date']) ? htmlspecialchars($latest_signature['released_date']) : ''); ?>">
                        </div>

                        <div class="col-md-3">
                            
                            <input type="text" class="form-control mb-3" name="received_by" value="<?php echo $itr_data ? htmlspecialchars($itr_data['received_by']) : (isset($latest_signature['received_by']) ? htmlspecialchars($latest_signature['received_by']) : ''); ?>" required>

                            <input type="text" class="form-control mb-3" name="received_by_position" value="<?php echo $itr_data ? htmlspecialchars($itr_data['received_by_position']) : (isset($latest_signature['received_by_position']) ? htmlspecialchars($latest_signature['received_by_position']) : ''); ?>" required>

                            <input type="date" class="form-control mb-3" name="received_date" value="<?php echo $itr_data ? htmlspecialchars($itr_data['received_date']) : (isset($latest_signature['received_date']) ? htmlspecialchars($latest_signature['received_date']) : ''); ?>">
                        </div>

                    </div>

                    <!-- Form Actions -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save ITR
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addITRRow() {
            const table = document.getElementById('itrItemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();

            // Get next item number
            const currentRows = table.rows.length;
            const nextItemNo = currentRows;

            const cells = [
                '<input type="date" class="form-control form-control-sm" name="date_acquired[]" value="' + new Date().toISOString().split('T')[0] + '" required>',
                '<input type="text" class="form-control form-control-sm" name="item_no[]" value="' + nextItemNo + '" readonly>',
                '<input type="text" class="form-control form-control-sm" name="ics_par_no[]" placeholder="ICS & PAR No./Date">',
                '<select class="form-select form-select-sm" name="description[]" required><option value="">Select Asset</option></select>',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="quantity[]" value="1" min="1" onchange="calculateITRTotal(this)">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="unit_price[]" required onchange="calculateITRTotal(this)">',
                '<input type="number" step="0.01" class="form-control form-control-sm" name="total_amount[]" readonly>',
                '<input type="text" class="form-control form-control-sm" name="condition[]" value="serviceable" readonly>',
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeITRRow(this)"><i class="bi bi-trash"></i></button>'
            ];

            cells.forEach((cellHtml, index) => {
                const cell = newRow.insertCell(index);
                cell.innerHTML = cellHtml;
            });
        }

        function removeITRRow(button) {
            const row = button.closest('tr');
            const table = document.getElementById('itrItemsTable').getElementsByTagName('tbody')[0];

            if (table.rows.length > 1) {
                row.remove();
                // Renumber all item numbers after deletion
                renumberItemNos();
            } else {
                alert('At least one row is required');
            }
        }

        function renumberItemNos() {
            const table = document.getElementById('itrItemsTable').getElementsByTagName('tbody')[0];
            const rows = table.rows;

            for (let i = 0; i < rows.length; i++) {
                const itemNoInput = rows[i].querySelector('input[name="item_no[]"]');
                if (itemNoInput) {
                    itemNoInput.value = i + 1;
                }
            }
        }

        function calculateITRTotal(input) {
            const row = input.closest('tr');
            const quantity = row.querySelector('input[name="quantity[]"]').value || 0;
            const unitPrice = row.querySelector('input[name="unit_price[]"]').value || 0;
            const totalAmount = (parseFloat(quantity) * parseFloat(unitPrice)).toFixed(2);

            row.querySelector('input[name="total_amount[]"]').value = totalAmount;
        }

        function resetITRForm() {
            if (confirm('Are you sure you want to reset form? All data will be lost.')) {
                document.getElementById('itrForm').reset();
                const table = document.getElementById('itrItemsTable').getElementsByTagName('tbody')[0];
                while (table.rows.length > 1) {
                    table.deleteRow(1);
                }
            }
        }

        function printITRForm() {
            window.print();
        }

        function createNewITR() {
            document.getElementById('itrForm').reset();
            // Generate fresh ITR number
            generateNewItrNumber();
        }

        // Generate new ITR number via AJAX
        function generateNewItrNumber() {
            <?php if ($itr_config): ?>
                const components = <?php
                                    $components = json_decode($itr_config['format_components'], true);
                                    if (is_string($components)) {
                                        $components = json_decode($components, true);
                                    }
                                    echo json_encode($components ?: []);
                                    ?>;
                const digits = <?php echo $itr_config['digits']; ?>;
                const separator = '<?php echo $itr_config['separator']; ?>';

                fetch('../SYSTEM_ADMIN/tags.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=generate_preview&tag_type=itr_no&components=' + encodeURIComponent(JSON.stringify(components)) + '&digits=' + digits + '&separator=' + encodeURIComponent(separator)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.preview) {
                            document.getElementById('itr_no').value = data.preview;
                        }
                    })
                    .catch(error => {
                        console.error('Error generating ITR number:', error);
                    });
            <?php endif; ?>
        }

        // Handle form submission to update counter
        document.getElementById('itrForm').addEventListener('submit', function(e) {
            // Always increment counter since field is always auto-generated
            const incrementField = document.createElement('input');
            incrementField.type = 'hidden';
            incrementField.name = 'increment_itr_counter';
            incrementField.value = '1';
            this.appendChild(incrementField);
        });

        function exportITRData() {
            // TODO: Implement export functionality
            alert('Export functionality will be implemented');
        }

        // Handle Transfer Type radio button change
        document.addEventListener('DOMContentLoaded', function() {
            const transferTypeRadios = document.querySelectorAll('input[name="transfer_type"]');
            const othersInputDiv = document.getElementById('others_input_div');

            function toggleOthersInput() {
                if (document.getElementById('others').checked) {
                    othersInputDiv.style.display = 'block';
                    document.getElementById('transfer_type_others').required = true;
                } else {
                    othersInputDiv.style.display = 'none';
                    document.getElementById('transfer_type_others').required = false;
                    document.getElementById('transfer_type_others').value = '';
                }
            }

            transferTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleOthersInput);
            });

            // Initial check
            toggleOthersInput();

            // Initialize Select2 for employee dropdowns
            $('#from_employee_search').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select employee...',
                allowClear: true,
                width: '100%'
            });
            
            $('#to_employee_search').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select employee...',
                allowClear: true,
                width: '100%'
            });

            // Initialize Select2 for asset description dropdowns
            initializeAssetDropdowns();
            
            // Update asset dropdowns when employee selection changes
            $('#from_employee_search').on('change', function() {
                updateAllAssetDropdowns();
                filterToEmployeeDropdown();
            });
        });

        function filterToEmployeeDropdown() {
            const fromEmployeeId = $('#from_employee_search').val();
            const $toDropdown = $('#to_employee_search');
            
            // Get current selection in "To" dropdown
            const currentToSelection = $toDropdown.val();
            
            // Store all original options if not already stored
            if (!$toDropdown.data('original-options')) {
                $toDropdown.data('original-options', $toDropdown.html());
            }
            
            // Restore original options
            $toDropdown.html($toDropdown.data('original-options'));
            
            // Remove the selected "From" employee from "To" dropdown
            if (fromEmployeeId) {
                $toDropdown.find(`option[value="${fromEmployeeId}"]`).remove();
            }
            
            // Restore previous selection if it's still valid (not the excluded employee)
            if (currentToSelection && currentToSelection !== fromEmployeeId) {
                $toDropdown.val(currentToSelection);
            } else {
                // Clear selection if the previously selected employee is now excluded
                $toDropdown.val('').trigger('change');
            }
            
            // Re-initialize Select2 to reflect changes
            $toDropdown.trigger('change.select2');
        }

        function initializeAssetDropdowns() {
            // Initialize Select2 for all existing asset dropdowns
            $('select[name="description[]"]').each(function() {
                initializeAssetDropdown($(this));
            });

            // Setup for new rows when added
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                const assetSelect = node.querySelector('select[name="description[]"]');
                                if (assetSelect) {
                                    initializeAssetDropdown($(assetSelect));
                                }
                            }
                        });
                    }
                });
            });

            observer.observe(document.getElementById('itrItemsTable').getElementsByTagName('tbody')[0], {
                childList: true,
                subtree: true
            });
        }

        function initializeAssetDropdown($select) {
            $select.select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Asset',
                allowClear: true,
                width: '100%'
            });

            // Handle asset selection
            $select.on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const row = $(this).closest('tr');
                
                if (selectedOption.val()) {
                    // Get asset data from data attributes
                    const assetData = {
                        description: selectedOption.text(),
                        acquisition_date: selectedOption.data('acquisition-date'),
                        property_no: selectedOption.data('property-no'),
                        value: selectedOption.data('value')
                    };
                    
                    // Fill form fields
                    fillAssetFields(row, assetData);
                } else {
                    // Clear fields when no asset selected
                    clearAssetFields(row);
                }
            });
        }

        function updateAllAssetDropdowns() {
            const employeeId = $('#from_employee_search').val();
            console.log('Updating asset dropdowns for employee ID:', employeeId);
            
            if (!employeeId) {
                console.log('No employee selected, clearing dropdowns');
                // Clear all asset dropdowns if no employee selected
                $('select[name="description[]"]').each(function() {
                    $(this).html('<option value="">Select Asset</option>');
                    $(this).trigger('change');
                });
                return;
            }

            console.log('Fetching assets for employee:', employeeId);
            // Fetch assets for the selected employee
            $.ajax({
                url: `../api/search_itr_assets.php?employee_id=${employeeId}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('API Response:', data);
                    if (data.success && data.assets) {
                        console.log('Found assets:', data.assets);
                        // Update all asset dropdowns with the employee's assets
                        $('select[name="description[]"]').each(function() {
                            const $select = $(this);
                            const currentValue = $select.val();
                            
                            // Build options HTML
                            let options = '<option value="">Select Asset</option>';
                            data.assets.forEach(function(asset) {
                                const selected = asset.id == currentValue ? 'selected' : '';
                                options += `<option value="${asset.id}" ${selected}
                                           data-acquisition-date="${asset.acquisition_date}"
                                           data-property-no="${asset.property_no}"
                                           data-value="${asset.value}">
                                           ${asset.description}
                                           </option>`;
                            });
                            
                            console.log('Setting dropdown options:', options);
                            $select.html(options);
                            
                            // Re-initialize Select2
                            $select.trigger('change');
                        });
                    } else {
                        console.log('No assets found or error:', data);
                        // Clear dropdowns if no assets
                        $('select[name="description[]"]').each(function() {
                            $(this).html('<option value="">No Assets Available</option>');
                            $(this).trigger('change');
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                }
            });
        }

        function fillAssetFields(row, assetData) {
            // Fill Date Acquired
            const dateAcquiredInput = row.find('input[name="date_acquired[]"]');
            if (dateAcquiredInput.length && assetData.acquisition_date) {
                dateAcquiredInput.val(assetData.acquisition_date);
            }

            // Fill ICS & PAR No
            const icsParInput = row.find('input[name="ics_par_no[]"]');
            if (icsParInput.length && assetData.property_no) {
                icsParInput.val(assetData.property_no);
            }

            // Fill Unit Price and calculate total
            const unitPriceInput = row.find('input[name="unit_price[]"]');
            if (unitPriceInput.length && assetData.value) {
                unitPriceInput.val(assetData.value);
                calculateITRTotal(unitPriceInput[0]);
            }
        }

        function clearAssetFields(row) {
            // Clear all related fields
            row.find('input[name="date_acquired[]"]').val('');
            row.find('input[name="ics_par_no[]"]').val('');
            row.find('input[name="unit_price[]"]').val('');
            row.find('input[name="total_amount[]"]').val('');
        }

        // Employee Search Functionality
        function initializeEmployeeSearch() {
            setupEmployeeSearch('from_employee_search', 'from_employee_results', 'from_employee_id');
            setupEmployeeSearch('to_employee_search', 'to_employee_results', 'to_employee_id');
        }

        function setupEmployeeSearch(inputId, resultsId, hiddenId) {
            const searchInput = document.getElementById(inputId);
            const resultsDiv = document.getElementById(resultsId);
            const hiddenInput = document.getElementById(hiddenId);
            let timeout;

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                clearTimeout(timeout);

                if (query.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                timeout = setTimeout(() => {
                    searchEmployees(query, resultsDiv, searchInput, hiddenInput);
                }, 300);
            });

            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    const query = this.value.trim();
                    searchEmployees(query, resultsDiv, searchInput, hiddenInput);
                }
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });
        }

        function searchEmployees(query, resultsDiv, searchInput, hiddenInput) {
            console.log('Searching employees with query:', query);
            console.log('Results div:', resultsDiv);
            console.log('Search input:', searchInput);
            console.log('Hidden input:', hiddenInput);
            
            const url = `../includes/search_employees.php?q=${encodeURIComponent(query)}`;
            console.log('Fetch URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log('Search results data:', data);
                    console.log('Data type:', typeof data);
                    console.log('Data length:', data.length);
                    
                    displayEmployeeResults(data, resultsDiv, searchInput, hiddenInput, query);
                })
                .catch(error => {
                    console.error('Error searching employees:', error);
                    console.error('Error details:', error.message);
                    resultsDiv.style.display = 'none';
                });
        }

        function displayEmployeeResults(employees, resultsDiv, searchInput, hiddenInput, query) {
            console.log('Displaying employee results:', employees);
            console.log('Results div element:', resultsDiv);
            console.log('Search input element:', searchInput);
            console.log('Hidden input element:', hiddenInput);
            console.log('Query:', query);
            console.log('Employees length:', employees ? employees.length : 'null/undefined');
            
            if (!employees || employees.length === 0) {
                console.log('No employees found, showing message');
                resultsDiv.innerHTML = '<div class="p-2 text-muted">No employees found</div>';
                resultsDiv.style.display = 'block';
                console.log('Set display to block, innerHTML:', resultsDiv.innerHTML);
                return;
            }

            let html = '';
            employees.forEach((employee, index) => {
                console.log(`Processing employee ${index}:`, employee);
                const displayName = `${employee.firstname} ${employee.lastname}`;
                const displayText = `${employee.employee_no} - ${displayName}`;
                if (employee.position) {
                    displayText += ` - ${employee.position}`;
                }
                if (employee.office_name) {
                    displayText += ` (${employee.office_name})`;
                }

                html += `
                    <div class="employee-result-item p-2 border-bottom" 
                         onclick="selectEmployee('${employee.id}', '${displayName.replace(/'/g, "\\'")}', '${searchInput.id}', '${hiddenInput.id}')"
                         style="cursor: pointer;">
                        <div class="fw-bold">${highlightMatch(displayText, query)}</div>
                        <small class="text-muted">${employee.employee_no}</small>
                    </div>
                `;
            });

            console.log('Generated HTML:', html);
            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
            console.log('Final innerHTML:', resultsDiv.innerHTML);
            console.log('Final display style:', resultsDiv.style.display);
        }

        function selectEmployee(employeeId, employeeName, searchInputId, hiddenInputId) {
            const searchInput = document.getElementById(searchInputId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const resultsDiv = document.getElementById(searchInputId.replace('_search', '_results'));

            searchInput.value = employeeName;
            hiddenInput.value = employeeId;
            resultsDiv.style.display = 'none';
        }

        // Initialize employee search when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeEmployeeSearch();
        });
    </script>
</body>

</html>