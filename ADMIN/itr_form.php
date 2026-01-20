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
                            <input type="text" class="form-control" name="from_office" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>ITR No:</strong></label>
                            <input type="text" class="form-control bg-light" name="itr_no" id="itr_no" value="<?php echo htmlspecialchars($next_itr_no); ?>" readonly>

                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label"><strong>To Accountable Officer/Agency/Fund Cluster:</strong></label>
                            <input type="text" class="form-control" name="to_office" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Date:</strong></label>
                            <input type="date" class="form-control" name="transfer_date" required>
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
                                        <td><input type="date" class="form-control form-control-sm" name="date_acquired[]" required></td>
                                        <td><input type="text" class="form-control form-control-sm" name="item_no[]" value="1" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm" name="ics_par_no[]" placeholder="ICS & PAR No./Date"></td>
                                        <td>
                                            <div class="autocomplete-container">
                                                <input type="text" class="form-control form-control-sm" name="description[]" required autocomplete="off">
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
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
                            <p class="mb-4">Signature:</p>
                            <p class="mb-5">Printed Name:</p>
                            <p class="mb-5">Designation:</p>
                            <p class="mb-3">Date:</p>
                        </div>

                        <div class="col-md-3">
                            <p class="mb-3" style="visibility: hidden;">h</p>

                            <input type="text" class="form-control mb-3" name="approved_by" required>

                            <input type="text" class="form-control mb-3" name="approved_by_position" required>

                            <input type="date" class="form-control mb-3" name="approved_date" required>
                        </div>

                        <div class="col-md-3">
                            <p class="mb-3" style="visibility: hidden;">h</p>

                            <input type="text" class="form-control mb-3" name="released_by" required>

                            <input type="text" class="form-control mb-3" name="released_by_position" required>

                            <input type="date" class="form-control mb-3" name="released_date" required>
                        </div>

                        <div class="col-md-3">
                            <p class="mb-3" style="visibility: hidden;">h</p>

                            <input type="text" class="form-control mb-3" name="received_by" required>

                            <input type="text" class="form-control mb-3" name="received_by_position" required>

                            <input type="date" class="form-control mb-3" name="received_date" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addITRRow() {
            const table = document.getElementById('itrItemsTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();

            // Get next item number
            const currentRows = table.rows.length;
            const nextItemNo = currentRows;

            const cells = [
                '<input type="date" class="form-control form-control-sm" name="date_acquired[]" required>',
                '<input type="text" class="form-control form-control-sm" name="item_no[]" value="' + nextItemNo + '" readonly>',
                '<input type="text" class="form-control form-control-sm" name="ics_par_no[]" placeholder="ICS & PAR No./Date">',
                '<div class="autocomplete-container"><input type="text" class="form-control form-control-sm" name="description[]" required autocomplete="off"><div class="autocomplete-dropdown"></div></div>',
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

            // Initialize autocomplete for description fields
            initializeAutocomplete();
        });

        // Autocomplete functionality for ITR asset search
        function initializeAutocomplete() {
            // Add autocomplete to existing description fields
            document.querySelectorAll('input[name="description[]"]').forEach(input => {
                setupAutocomplete(input);
            });

            // Setup autocomplete for new rows when added
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                const descriptionInput = node.querySelector('input[name="description[]"]');
                                if (descriptionInput) {
                                    setupAutocomplete(descriptionInput);
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

        function setupAutocomplete(input) {
            let timeout;
            const container = input.closest('.autocomplete-container');
            const dropdown = container.querySelector('.autocomplete-dropdown');

            console.log('Autocomplete setup for:', input);
            console.log('Container:', container);
            console.log('Dropdown:', dropdown);

            input.addEventListener('input', function() {
                const query = this.value.trim();

                clearTimeout(timeout);

                if (query.length < 2) {
                    dropdown.style.display = 'none';
                    return;
                }

                timeout = setTimeout(() => {
                    searchITRAssets(query, dropdown, input);
                }, 300);
            });

            input.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    const query = this.value.trim();
                    searchITRAssets(query, dropdown, input);
                }
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

            // Hide dropdown when scrolling
            window.addEventListener('scroll', function() {
                dropdown.style.display = 'none';
            });

            // Hide dropdown when window is resized
            window.addEventListener('resize', function() {
                dropdown.style.display = 'none';
            });
        }

        function searchITRAssets(query, dropdown, input) {
            console.log('Searching for:', query);
            fetch(`../api/search_itr_assets.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Search results:', data);
                    // Always call display function for testing
                    displayAutocompleteResults(data.assets || [], dropdown, input);

                    // Original logic (commented out for testing)
                    // if (data.success && data.assets.length > 0) {
                    //     displayAutocompleteResults(data.assets, dropdown, input);
                    // } else {
                    //     dropdown.style.display = 'none';
                    // }
                })
                .catch(error => {
                    console.error('Error searching assets:', error);
                    dropdown.style.display = 'none';
                });
        }

        function displayAutocompleteResults(assets, dropdown, input) {
            console.log('Displaying results for assets:', assets);
            console.log('Dropdown element:', dropdown);

            dropdown.innerHTML = '';

            if (assets.length === 0) {
                // Add a test item to see if dropdown shows
                const testItem = document.createElement('div');
                testItem.className = 'autocomplete-item';
                testItem.innerHTML = '<strong>No results found</strong><br><small>Test item</small>';
                testItem.style.background = 'red';
                testItem.style.color = 'white';
                dropdown.appendChild(testItem);
            } else {
                assets.forEach(asset => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.innerHTML = `
                        <strong>${highlightMatch(asset.description, input.value.trim())}</strong><br>
                        <small>Property No: ${asset.property_no} | Unit Cost: ₱${parseFloat(asset.value).toFixed(2)}</small>
                    `;

                    item.addEventListener('click', function() {
                        selectAsset(asset, input);
                        dropdown.style.display = 'none';
                    });

                    dropdown.appendChild(item);
                });
            }

            // Calculate position for fixed dropdown
            const rect = input.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + 2) + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.width = Math.max(rect.width, 300) + 'px';

            console.log('Dropdown HTML after adding items:', dropdown.innerHTML);
            dropdown.style.display = 'block';
            dropdown.style.visibility = 'visible';
            dropdown.style.opacity = '1';
            console.log('Dropdown position set to:', dropdown.style.top, dropdown.style.left);
            console.log('Dropdown display set to block');
        }

        function selectAsset(asset, input) {
            const row = input.closest('tr');

            // Fill the form fields
            input.value = asset.description;

            // Fill other fields if they exist
            const dateAcquiredInput = row.querySelector('input[name="date_acquired[]"]');
            if (dateAcquiredInput && asset.acquisition_date) {
                dateAcquiredInput.value = asset.acquisition_date;
            }

            const icsParInput = row.querySelector('input[name="ics_par_no[]"]');
            if (icsParInput) {
                icsParInput.value = asset.property_no;
            }

            const unitPriceInput = row.querySelector('input[name="unit_price[]"]');
            if (unitPriceInput) {
                unitPriceInput.value = asset.value;
                // Trigger total calculation if function exists
                if (typeof calculateITRTotal === 'function') {
                    calculateITRTotal(unitPriceInput);
                }
            }
        }

        function highlightMatch(text, query) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }
    </script>
</body>

</html>