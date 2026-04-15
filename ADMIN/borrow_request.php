<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

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

// Log borrow request page access
logSystemAction($_SESSION['user_id'], 'access', 'borrow_request', 'Admin accessed borrow request page');

// Fetch available assets from assets table with serviceable item count
$assets_query = "SELECT a.id, a.description,
                c.category_name,
                COUNT(ai.id) as available_count,
                GROUP_CONCAT(ai.id) as asset_item_ids,
                GROUP_CONCAT(ai.property_no) as property_numbers
                FROM assets a
                LEFT JOIN asset_categories c ON a.asset_categories_id = c.id
                LEFT JOIN asset_items ai ON a.id = ai.asset_id AND ai.status = 'serviceable'
                WHERE c.category_name NOT IN ('LND', 'Buildings', 'OInfra', 'Land Imp')
                GROUP BY a.id, a.description, c.category_name
                HAVING available_count > 0
                ORDER BY c.category_name, a.description";
$assets_result = mysqli_query($conn, $assets_query);

// Get system settings
$settings_query = "SELECT * FROM system_settings LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Initialize variables
$success_message = '';
$error_message = '';

// Display messages
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Borrow Request - PIMS</title>

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
        /* ══════════════════════════════════════
           SLIP CARD
        ══════════════════════════════════════ */
        #slip-card {
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 13px;
            max-width: 900px;
            margin: 24px auto 40px;
            padding: 32px 40px 40px;
            border: 1px solid #d0d5dd;
            border-radius: 6px;
            box-shadow: 0 2px 14px rgba(0,0,0,.08);
        }

        /* ══════════════════════════════════════
           LGU HEADER
        ══════════════════════════════════════ */
        .slip-gov-header {
            display: grid;
            grid-template-columns: 86px 1fr 178px;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 2.5px solid #1a3c6e;
            margin-bottom: 4px;
        }
        .slip-gov-header .logo-col img {
            width: 76px;
            height: 76px;
            object-fit: contain;
        }
        .slip-gov-header .title-col {
            text-align: center;
            line-height: 1.45;
        }
        .slip-gov-header .title-col p {
            margin: 0;
            font-size: 12.5px;
        }
        .slip-gov-header .title-col h2 {
            margin: 5px 0 0;
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #1a3c6e;
        }
        .slip-gov-header .doc-col {
            text-align: right;
            font-size: 10.5px;
            line-height: 1.65;
        }
        .slip-gov-header .doc-col strong {
            font-size: 11px;
        }

        /* ══════════════════════════════════════
           SLIP TITLE
        ══════════════════════════════════════ */
        .slip-title {
            text-align: center;
            margin: 20px 0 22px;
        }
        .slip-title h3 {
            display: inline-block;
            font-size: 18px;
            font-weight: 900;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1a3c6e;
            margin: 0;
        }

        /* ══════════════════════════════════════
           FORM FIELDS — label above / underline below
        ══════════════════════════════════════ */
        .slip-field {
            margin-bottom: 18px;
        }
        .slip-field label {
            display: block;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 6px;
            color: #111;
        }
        /* Override Bootstrap form controls to look like underline fields */
        .slip-field .form-control,
        .slip-field .form-select {
            border: none;
            border-bottom: 1.5px solid #333;
            border-radius: 0;
            padding: 3px 4px;
            font-size: 13px;
            background: transparent;
            color: #222;
            box-shadow: none;
        }
        .slip-field .form-control:focus,
        .slip-field .form-select:focus {
            outline: none;
            box-shadow: none;
            border-bottom-color: #1a3c6e;
            background: transparent;
        }
        .slip-field .form-control::placeholder {
            color: #aaa;
            font-style: italic;
        }

        /* ══════════════════════════════════════
           ITEMS TABLE
        ══════════════════════════════════════ */
        .slip-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #9aaaca;
            margin-bottom: 0;
        }
        .slip-table thead tr {
            background: #dde4ef;
        }
        .slip-table th {
            border: 1px solid #9aaaca;
            text-align: center;
            font-size: 11.5px;
            font-weight: 800;
            padding: 9px 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #1a3c6e;
        }
        .slip-table td {
            border: 1px solid #9aaaca;
            padding: 7px 10px;
            font-size: 13px;
            vertical-align: middle;
        }
        .slip-table .qty-col  { text-align: center; width: 90px; }
        .slip-table .rem-col  { width: 28%; }
        .slip-table .act-col  { width: 48px; text-align: center; }

        /* Table inline controls — keep minimal styling */
        .slip-table .form-control,
        .slip-table .form-select {
            font-size: 12.5px;
            border-radius: 3px;
        }
        .slip-table .form-select {
            min-width: 180px;
        }

        /* Trash button */
        .btn-remove-item {
            background: none;
            border: none;
            color: #dc3545;
            padding: 2px 6px;
            cursor: pointer;
            border-radius: 4px;
            transition: background .15s;
        }
        .btn-remove-item:hover { background: #fde8e8; }

        /* Add Item button */
        .btn-add-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 6px 16px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1.5px solid #1a3c6e;
            color: #1a3c6e;
            background: #fff;
            border-radius: 20px;
            cursor: pointer;
            transition: all .15s;
        }
        .btn-add-item:hover {
            background: #1a3c6e;
            color: #fff;
        }

        /* ══════════════════════════════════════
           SIGNATURE SECTION
        ══════════════════════════════════════ */
        .sig-block {
            text-align: center;
            margin-top: 32px;
        }
        /* Name input inside sig block */
        .sig-block .sig-name-field {
            border: none;
            border-bottom: 1.5px solid #333;
            border-radius: 0;
            padding: 3px 4px;
            font-size: 13px;
            background: transparent;
            text-align: center;
            width: 100%;
            box-shadow: none;
            color: #222;
        }
        .sig-block .sig-name-field:focus {
            outline: none;
            box-shadow: none;
            border-bottom-color: #1a3c6e;
        }
        .sig-block .sig-label-top {
            font-weight: 700;
            font-size: 12px;
            color: #111;
            text-align: left;
            display: block;
            margin-bottom: 6px;
        }
        .sig-block .sig-line {
            border-bottom: 1.5px solid #000;
            height: 46px;
            margin: 20px 16px 6px;
        }
        .sig-block .sig-label-bottom {
            font-weight: 800;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #1a3c6e;
        }

        /* ══════════════════════════════════════
           ACTION BUTTONS (form submission)
        ══════════════════════════════════════ */
        .form-actions {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e0e4ed;
        }
    </style>
</head>
<body>
<?php $page_title = 'New Borrow Request'; ?>

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
                        <i class="bi bi-plus-circle"></i> New Borrow Request
                    </h1>
                    <p class="text-muted mb-0">Create a new borrow slip for asset lending</p>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success mt-2" role="alert">
                            <i class="bi bi-check-circle"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="clearForm()">
                                    <i class="bi bi-x-circle"></i> Clear Form
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="window.location.href='borrowing.php'">
                                    <i class="bi bi-arrow-left"></i> Back to Borrowing
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="window.location.href='dashboard.php'">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════
             BORROW SLIP — official paper format
        ════════════════════════════════════════ -->
        <div id="slip-card">

            <!-- LGU Government Header -->
            <div class="slip-gov-header">
                <div class="logo-col">
                    <?php if ($settings && !empty($settings['system_logo'])): ?>
                        <img src="../<?php echo htmlspecialchars($settings['system_logo']); ?>" alt="LGU Logo">
                    <?php else: ?>
                        <img src="../img/system_logo.png" alt="LGU Logo">
                    <?php endif; ?>
                </div>
                <div class="title-col">
                    <p>Republic of the Philippines</p>
                    <p>Province of Sorsogon</p>
                    <h2>Local Government Unit of Pilar</h2>
                </div>
                <div class="doc-col">
                    <strong>Document Code: PS-DIT-01-F03-01-01</strong><br>
                    Effective Date:<br>
                    22 May 2023
                </div>
            </div>

            <!-- Slip Title -->
            <div class="slip-title">
                <h3>Borrow Slip</h3>
            </div>

            <!-- Form -->
            <form id="borrowRequestForm" method="POST" action="process_borrow_request.php">

                <!-- Row 1: Name | Date Borrowed | Schedule of Return -->
                <div class="row g-4 mb-1">
                    <div class="col-md-5">
                        <div class="slip-field">
                            <label for="guest_name">Name:</label>
                            <input type="text" class="form-control" id="guest_name" name="guest_name"
                                   placeholder="Enter Borrower's Name" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="slip-field">
                            <label for="date_borrowed">Date Borrowed:</label>
                            <input type="date" class="form-control" id="date_borrowed" name="date_borrowed"
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="slip-field">
                            <label for="schedule_return">Schedule of Return:</label>
                            <input type="date" class="form-control" id="schedule_return" name="schedule_return">
                        </div>
                    </div>
                </div>

                <!-- Row 2: Contact No. | Barangay | Borrower Signature -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="slip-field">
                            <label for="contact">Contact No.:</label>
                            <input type="text" class="form-control" id="contact" name="contact"
                                   placeholder="09XX-XXX-XXXX" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="slip-field">
                            <label for="barangay">Barangay:</label>
                            <input type="text" class="form-control" id="barangay" name="barangay"
                                   placeholder="Enter Barangay" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="slip-field">
                            <label for="borrower_signature">Borrower Signature:</label>
                            <input type="text" class="form-control" id="borrower_signature"
                                   name="borrower_signature" placeholder="&nbsp;">
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="slip-table">
                    <thead>
                        <tr>
                            <th>Asset Type</th>
                            <th class="qty-col">Quantity</th>
                            <th class="rem-col">Remarks</th>
                            <th class="act-col"></th>
                        </tr>
                    </thead>
                    <tbody id="assetSelectionBody">
                        <tr class="asset-selection-row">
                            <td>
                                <div class="asset-search-container">
                                    <select class="form-select asset-select" name="asset_selections[0][asset_id]" required>
                                        <option value="">-- Select Asset Type --</option>
                                        <?php 
                                        // Reset result pointer for first row
                                        mysqli_data_seek($assets_result, 0);
                                        while ($asset = mysqli_fetch_assoc($assets_result)): 
                                        ?>
                                            <option value="<?php echo $asset['id']; ?>"
                                                    data-available-count="<?php echo $asset['available_count']; ?>"
                                                    data-asset-item-ids="<?php echo htmlspecialchars($asset['asset_item_ids']); ?>"
                                                    data-property-numbers="<?php echo htmlspecialchars($asset['property_numbers']); ?>"
                                                    data-description="<?php echo htmlspecialchars($asset['description']); ?>"
                                                    data-category="<?php echo htmlspecialchars($asset['category_name']); ?>">
                                                <?php echo htmlspecialchars($asset['category_name'] . ' - ' . $asset['description'] . ' (Available: ' . $asset['available_count'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </td>
                            <td class="qty-col">
                                <input type="number" class="form-control form-control-sm quantity-input"
                                       name="asset_selections[0][quantity]" value="1" min="1" required>
                            </td>
                            <td class="rem-col">
                                <input type="text" class="form-control form-control-sm"
                                       name="asset_selections[0][remarks]" placeholder="Add remarks here...">
                            </td>
                            <td class="act-col">
                                <button type="button" class="btn-remove-item" onclick="removeAssetSelection(this)" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="button" class="btn-add-item" onclick="addAssetSelection()">
                    <i class="bi bi-plus-circle"></i> Add Asset Type
                </button>

                
                <!-- Signature Section -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="sig-block">
                            <span class="sig-label-top">Releasing Officer:</span>
                            <input type="text" class="sig-name-field" name="releasing_officer"
                                   placeholder="Enter Name" required>
                            <div class="sig-line"></div>
                            <div class="sig-label-bottom">Releasing Officer Signature</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="sig-block">
                            <span class="sig-label-top">Approved by:</span>
                            <input type="text" class="sig-name-field" name="approved_by"
                                   placeholder="Enter Name" required>
                            <div class="sig-line"></div>
                            <div class="sig-label-bottom">Approved by Signature</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-save me-2"></i>Save Borrow Slip
                    </button>
                    <a href="borrowing.php" class="btn btn-secondary">
                        <i class="bi bi-x-lg me-2"></i>Cancel
                    </a>
                </div>

            </form>
        </div><!-- /slip-card -->

    </div><!-- /main-content -->
</div><!-- /main-wrapper -->

<?php require_once 'includes/logout-modal.php'; ?>
<?php require_once 'includes/change-password-modal.php'; ?>
<?php require_once 'includes/footer.php'; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let assetSelectionCount = 1;
    let allIndividualItems = [];

    function addAssetSelection() {
        const tbody = document.getElementById('assetSelectionBody');

        // Clone options from first select
        const firstSelect = document.querySelector('.asset-select');
        const options = firstSelect.innerHTML;

        const newRow = document.createElement('tr');
        newRow.className = 'asset-selection-row';
        newRow.innerHTML = `
            <td>
                <div class="asset-search-container">
                    <select class="form-select asset-select" name="asset_selections[${assetSelectionCount}][asset_id]" required>
                        ${options}
                    </select>
                </div>
            </td>
            <td class="qty-col">
                <input type="number" class="form-control form-control-sm quantity-input"
                       name="asset_selections[${assetSelectionCount}][quantity]" value="1" min="1" required>
            </td>
            <td class="rem-col">
                <input type="text" class="form-control form-control-sm"
                       name="asset_selections[${assetSelectionCount}][remarks]" placeholder="Add remarks here...">
            </td>
            <td class="act-col">
                <button type="button" class="btn-remove-item" onclick="removeAssetSelection(this)" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(newRow);
        
        // Initialize Select2 for the new dropdown
        const newSelect = newRow.querySelector('.asset-select');
        $(newSelect).select2({
            theme: 'bootstrap-5',
            placeholder: 'Search and select asset type...',
            allowClear: true,
            width: '100%'
        });
        
        assetSelectionCount++;
    }

    function removeAssetSelection(button) {
        const row = button.closest('tr');
        const tbody = document.getElementById('assetSelectionBody');

        if (tbody.children.length > 1) {
            row.remove();
            updateIndividualItems();
        } else {
            alert('At least one asset type is required.');
        }
    }

    function updateIndividualItems() {
        allIndividualItems = [];
        const tbody = document.getElementById('assetSelectionBody');
        
        // Clear existing individual item rows
        tbody.querySelectorAll('.individual-item-row').forEach(row => row.remove());

        document.querySelectorAll('.asset-selection-row').forEach((row, rowIndex) => {
            const select = row.querySelector('.asset-select');
            const quantityInput = row.querySelector('.quantity-input');
            const remarksInput = row.querySelector('input[name*="remarks"]');

            if (select.value && quantityInput.value) {
                const selectedOption = select.options[select.selectedIndex];
                const quantity = parseInt(quantityInput.value);
                const availableCount = parseInt(selectedOption.dataset.availableCount);
                const assetItemIds = selectedOption.dataset.assetItemIds.split(',');
                const propertyNumbers = selectedOption.dataset.propertyNumbers.split(',');
                const description = selectedOption.dataset.description;
                const category = selectedOption.dataset.category;

                if (quantity <= availableCount) {
                    // Take the first 'quantity' items from the available asset items
                    for (let i = 0; i < quantity; i++) {
                        const assetItemId = assetItemIds[i].trim();
                        const propertyNo = propertyNumbers[i].trim();
                        if (assetItemId && propertyNo) {
                            allIndividualItems.push({
                                asset_item_id: assetItemId,
                                description: description,
                                category: category,
                                remarks: remarksInput.value || '',
                                quantity: 1
                            });

                            // Create individual item row and insert after the current asset selection row
                            const itemRow = document.createElement('tr');
                            itemRow.className = 'individual-item-row';
                            itemRow.innerHTML = `
                                <td colspan="4" style="padding: 5px 20px; background-color: #f8f9fa; border-left: 3px solid #007bff;">
                                    <small>
                                        <i class="bi bi-box-seam"></i> 
                                        ${description} - ${propertyNo}
                                        ${remarksInput.value ? '<br><em>Remarks: ' + remarksInput.value + '</em>' : ''}
                                    </small>
                                </td>
                            `;
                            
                            // Insert the individual item row after the current asset selection row
                            row.parentNode.insertBefore(itemRow, row.nextSibling);
                        }
                    }
                } else {
                    // Show error if quantity exceeds available
                    quantityInput.setCustomValidity(`Maximum available quantity is ${availableCount}`);
                    quantityInput.reportValidity();
                    return;
                }
            }
        });

        // Update hidden input for form submission
        updateHiddenItemsInput();
    }

    function updateHiddenItemsInput() {
        // Create or update hidden input with all individual items as JSON
        let hiddenInput = document.getElementById('individual_items_json');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = 'individual_items_json';
            hiddenInput.name = 'individual_items_json';
            document.getElementById('borrowRequestForm').appendChild(hiddenInput);
        }
        hiddenInput.value = JSON.stringify(allIndividualItems);
    }

    // Form validation
    document.getElementById('borrowRequestForm').addEventListener('submit', function (e) {
        // Update individual items before validation
        updateIndividualItems();

        const assetSelects = document.querySelectorAll('.asset-select');
        let hasValidItems = false;

        assetSelects.forEach(select => {
            if (select.value) hasValidItems = true;
        });

        if (!hasValidItems) {
            e.preventDefault();
            alert('Please select at least one asset type to borrow.');
            return false;
        }

        if (allIndividualItems.length === 0) {
            e.preventDefault();
            alert('Please specify quantities for selected assets.');
            return false;
        }

        // Validate return date is after borrow date
        const borrowDate  = new Date(document.getElementById('date_borrowed').value);
        const returnValue = document.getElementById('schedule_return').value;

        if (returnValue) {
            const returnDate = new Date(returnValue);
            if (returnDate <= borrowDate) {
                e.preventDefault();
                alert('Schedule of return must be after the date borrowed.');
                return false;
            }
        }
    });

    // Set minimum return date to tomorrow
    document.addEventListener('DOMContentLoaded', function () {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('schedule_return').min = tomorrow.toISOString().split('T')[0];
    });

    // Initialize Select2 for all asset dropdowns
    $(document).ready(function() {
        // Initialize Select2 for existing dropdowns
        $('.asset-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Search and select asset type...',
            allowClear: true,
            width: '100%'
        });
        
        // Handle asset selection change
        $(document).on('change', '.asset-select', function() {
            const selectedOption = $(this).find('option:selected');
            const quantityInput = $(this).closest('tr').find('.quantity-input');
            
            if (selectedOption.val()) {
                const availableCount = parseInt(selectedOption.data('available-count'));
                quantityInput.attr('max', availableCount);
                quantityInput.attr('placeholder', `Max: ${availableCount}`);
                // Update individual items when selection changes
                updateIndividualItems();
            } else {
                quantityInput.removeAttr('max');
                quantityInput.attr('placeholder', '');
                updateIndividualItems();
            }
        });

        // Handle quantity change
        $(document).on('input', '.quantity-input', function() {
            updateIndividualItems();
        });
    });

    // Clear form function
    function clearForm() {
        if (confirm('Are you sure you want to clear all form data?')) {
            document.getElementById('borrowRequestForm').reset();
            
            // Clear Select2 dropdowns
            $('.asset-select').val(null).trigger('change');
            
            // Clear individual items
            const individualItemsContainer = document.getElementById('individualItemsContainer');
            if (individualItemsContainer) {
                individualItemsContainer.innerHTML = '';
            }
            
            // Clear hidden JSON input
            const hiddenInput = document.getElementById('individualItemsJson');
            if (hiddenInput) {
                hiddenInput.value = '';
            }
        }
    }
</script>

<?php include 'includes/sidebar-scripts.php'; ?>
</body>
</html>
