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

// Log new borrow request page access
logSystemAction($_SESSION['user_id'], 'access', 'new_borrow_request', 'Admin accessed new borrow request page');

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== FORM SUBMISSION DEBUG ===");
    error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("CONTENT TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET'));
    error_log("POST data received: " . print_r($_POST, true));
    
    if (isset($_POST['action']) && $_POST['action'] === 'submit_borrow_request') {
        error_log("Action confirmed: submit_borrow_request");
        
        // Check if items_json was sent
        if (!isset($_POST['items_json'])) {
            error_log("ERROR: items_json not set in POST data");
            error_log("All POST data: " . print_r($_POST, true));
            $_SESSION['error_message'] = "Items data is missing from form submission. Please ensure you have added items to the form.";
            header("Location: new_borrow_request.php");
            exit();
        }
        
        // Note: items_json validation is now handled after decoding
        error_log("items_json received: " . ($_POST['items_json'] ?? 'NOT SET'));
        
        // Validate and sanitize input
        $guest_name = trim($_POST['guest_name']);
        $barangay = trim($_POST['barangay']);
        $contact = trim($_POST['contact']);
        $date_borrowed = trim($_POST['date_borrowed']);
        $schedule_return = trim($_POST['schedule_return']);
        $releasing_officer = trim($_POST['releasing_officer']);
        $approved_by = trim($_POST['approved_by']);
        
        error_log("Form fields - guest_name: $guest_name, barangay: $barangay, contact: $contact");
        error_log("items_json raw: " . ($_POST['items_json'] ?? 'NULL'));
        
        // Parse items from simple JSON format
        $items_json = $_POST['items_json'];
        $items = [];
        
        error_log("items_json raw: " . $items_json);
        
        if (!empty($items_json)) {
            try {
                $items = json_decode($items_json, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    $items = [];
                }
            } catch (Exception $e) {
                error_log("Items decode error: " . $e->getMessage());
                $items = [];
            }
        }
        
        error_log("Parsed items: " . print_r($items, true));
        
        // Ensure items is always an array
        if (!is_array($items)) {
            $items = [];
            error_log("Items is not an array, setting to empty array");
        }

        // Validate required fields
        $errors = [];
        
        if (empty($guest_name)) {
            $errors[] = "Guest name is required";
        }
        if (empty($barangay)) {
            $errors[] = "Barangay is required";
        }
        if (empty($contact)) {
            $errors[] = "Contact number is required";
        }
        if (empty($date_borrowed)) {
            $errors[] = "Date borrowed is required";
        }
        if (empty($schedule_return)) {
            $errors[] = "Schedule return is required";
        }
        if (empty($releasing_officer)) {
            $errors[] = "Releasing officer is required";
        }
        if (empty($approved_by)) {
            $errors[] = "Approved by is required";
        }
        
        // Validate that items exist and are valid JSON
        if (empty($items_json)) {
            $errors[] = "Items data is required. Please ensure you have selected assets and the form submitted correctly.";
        } else {
            // Test if the items_json is valid JSON
            $test_decode = json_decode($items_json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Items data is not valid JSON: " . json_last_error_msg() . ". Please try selecting assets again.";
            } else {
                // Check if the items array contains actual items
                $items_array = json_decode($items_json, true);
                if (empty($items_array)) {
                    $errors[] = "Please select at least one item to borrow. The items array appears to be empty.";
                }
            }
        }
        
        // Debug: Log what we received
        error_log("=== FORM SUBMISSION DEBUG ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("Form submission debug - items_json: " . ($_POST['items_json'] ?? 'NOT SET'));
        error_log("Items decoded: " . print_r($items, true));
        error_log("Items JSON: " . $items_json);
        error_log("Items JSON empty check: " . (empty($items_json) ? 'EMPTY' : 'NOT EMPTY'));
        error_log("Items JSON length: " . strlen($items_json ?? ''));
        
        if (!empty($items_json)) {
            $decoded = json_decode($items_json, true);
            error_log("Items decoded: " . print_r($decoded, true));
            if (!empty($decoded)) {
                error_log("Items array exists: " . (empty($decoded) ? 'EMPTY' : 'NOT EMPTY'));
                error_log("Items count: " . count($decoded));
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['error_message'] = "Please fix the following errors: " . implode(', ', $errors);
            header("Location: new_borrow_request.php");
            exit();
        } else {
            try {
                $conn->begin_transaction();

                // Insert borrow request
                $stmt = $conn->prepare("INSERT INTO borrow_form_submissions 
                    (guest_name, barangay, contact, date_borrowed, schedule_return, releasing_officer, approved_by, items, status, submitted_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())");
                
                // Create items array with Description/Asset, Quantity, and Remarks
                error_log("=== DATABASE INSERT DEBUG ===");
                error_log("Items array: " . print_r($items, true));
                
                // Create simple items JSON with Description/Asset, Quantity, and Remarks
                $simple_items = [];
                foreach ($items as $item) {
                    $simple_items[] = [
                        'description' => $item['thing'] ?? 'Asset Description',
                        'quantity' => $item['qty'] ?? '1',
                        'remarks' => $item['remarks'] ?? ''
                    ];
                }
                
                $items_json = json_encode($simple_items);
                error_log("Simple items JSON for database: " . $items_json);
                
                $stmt->bind_param("ssssssss", $guest_name, $barangay, $contact, $date_borrowed, 
                                 $schedule_return, $releasing_officer, $approved_by, $items_json);
                
                error_log("Executing database insert...");
                $result = $stmt->execute();
                error_log("Insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("Affected rows: " . $stmt->affected_rows);
                
                $borrow_id = $stmt->insert_id;
                error_log("Borrow ID generated: " . $borrow_id);
                $stmt->close();

                // Update asset items status to borrowed (only if items exist)
                if (!empty($items) && is_array($items)) {
                    foreach ($items as $item) {
                        $asset_item_id = $item['asset_item_id'];
                        
                        // Update the specific asset item to borrowed status
                        $update_stmt = $conn->prepare("UPDATE asset_items SET status = 'borrowed' WHERE id = ?");
                        $update_stmt->bind_param("i", $asset_item_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        error_log("Updated asset item ID {$asset_item_id} to borrowed status");
                    }
                }

                $conn->commit();
                $_SESSION['success_message'] = "Borrow request submitted successfully!";
                logSystemAction($_SESSION['user_id'], 'borrow_request_submit', 'borrowing', "Borrow ID: $borrow_id, Guest: $guest_name");

                header("Location: borrowing.php");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error submitting borrow request: " . $e->getMessage();
                logSystemAction($_SESSION['user_id'], 'borrow_request_submit_failed', 'borrowing', "Error: " . $e->getMessage());
            }
        }
    }
}

// Get serviceable assets for dropdown
$serviceable_assets = [];
try {
    $stmt = $conn->prepare("SELECT ai.id as asset_item_id, a.description as asset_description, ai.description as item_description, 
                                   ai.property_no, ai.inventory_tag, ai.status, ac.category_name
                           FROM asset_items ai 
                           JOIN assets a ON ai.asset_id = a.id 
                           LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id
                           WHERE ai.status = 'serviceable' 
                           ORDER BY a.description, ai.property_no");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $serviceable_assets[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error_message = "Error loading serviceable assets: " . $e->getMessage();
}

// Display messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Debug: Check serviceable assets
error_log("Serviceable assets loaded: " . count($serviceable_assets));
if (!empty($serviceable_assets)) {
    error_log("First serviceable asset: " . print_r($serviceable_assets[0], true));
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
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }
        
        .borrow-slip-container {
            background: white;
            max-width: 1000px;
            margin: 2rem auto;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px;
            position: relative;
        }

        /* Slip Header Style */
        .slip-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            position: relative;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .slip-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 85px;
            height: 85px;
            object-fit: contain;
        }
        
        .header-center {
            text-align: center;
        }
        
        .header-center h5 { margin: 0; font-size: 16px; font-weight: 500; }
        .header-center h4 { margin: 5px 0; font-size: 18px; font-weight: bold; }
        .header-center h3 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 1px; }

        .doc-info {
            position: absolute;
            right: 0;
            top: 0;
            text-align: right;
            font-size: 10px;
            line-height: 1.4;
        }

        .slip-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
            text-transform: uppercase;
        }

        /* Document Field Styles */
        .field-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .field-group {
            flex: 1;
        }

        .field-label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }

        .field-input {
            border: none;
            border-bottom: 1px solid #000;
            width: 100%;
            padding: 5px 0;
            font-size: 16px;
            background: transparent;
            outline: none;
        }

        .field-input:focus {
            border-bottom: 2px solid var(--primary-color);
        }

        /* Table Styles */
        .slip-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .slip-table th {
            border: 1px solid #ccc;
            background: #f8f9fa;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
        }

        .slip-table td {
            border: 1px solid #ccc;
            padding: 10px;
            vertical-align: top;
        }

        .action-row {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signatory-box {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        @media print {
            .no-print, .main-wrapper, .sidebar, .topbar {
                display: none !important;
            }
            .main-content { padding: 0 !important; margin: 0 !important; }
            .borrow-slip-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                width: 100%;
            }
            body { background: white; }
        }
    </style>
</head>
<body>
    <?php 
    $page_title = 'New Borrow Request';
    ?>
<?php
// Get system settings for logo (Reused from card_item pattern)
$system_settings = [];
$settings_result = $conn->query("SELECT setting_name, setting_value FROM system_settings");
while ($row = $settings_result->fetch_assoc()) {
    $system_settings[$row['setting_name']] = $row['setting_value'];
}
$logo_path = '../assets/images/logo.png';
if (!empty($system_settings['system_logo'])) {
    $logo_path = '../' . $system_settings['system_logo'];
}
?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if (!empty($serviceable_assets)): ?>
        <form method="POST">
            <input type="hidden" name="action" value="submit_borrow_request">
            <input type="hidden" name="items_json" id="itemsJsonField">

            <div class="borrow-slip-container">
                <!-- Slip Header -->
                <div class="slip-header">
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="slip-logo">
                    <div class="header-center">
                        <h5>Republic of the Philippines</h5>
                        <h5>Province of Sorsogon</h5>
                        <h3>LOCAL GOVERNMENT UNIT OF PILAR</h3>
                    </div>
                    <div class="doc-info">
                        Document Code: <strong>PS-DIT-01-F03-01-01</strong><br>
                        Effective Date: <br>
                        <strong>22 May 2023</strong>
                    </div>
                </div>

                <div class="slip-title">BORROW SLIP</div>

                <!-- Fields Grid -->
                <div class="field-row">
                    <div class="field-group" style="flex: 2;">
                        <span class="field-label">Name:</span>
                        <input type="text" class="field-input" name="guest_name" placeholder="Enter Borrower's Name" required>
                    </div>
                    <div class="field-group">
                        <span class="field-label">Date Borrowed:</span>
                        <input type="date" class="field-input" name="date_borrowed" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="field-group">
                        <span class="field-label">Schedule of Return:</span>
                        <input type="date" class="field-input" name="schedule_return" required>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-group">
                        <span class="field-label">Contact No.:</span>
                        <input type="text" class="field-input" name="contact" placeholder="09XX-XXX-XXXX" required>
                    </div>
                    <div class="field-group">
                        <span class="field-label">Barangay:</span>
                        <input type="text" class="field-input" name="barangay" placeholder="Enter Barangay" required>
                    </div>
                    <div class="field-group">
                        <span class="field-label">Borrower Signature:</span>
                        <div class="field-input" style="height: 25px; border-bottom: 2px solid #000;"></div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="slip-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 55%;">THINGS BORROWED</th>
                            <th style="width: 10%;">QTY</th>
                            <th style="width: 35%;">REMARKS</th>
                            <th class="no-print" style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <!-- Items dynamically added here -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-outline-primary btn-sm no-print mb-4" onclick="addItemRow()">
                    <i class="bi bi-plus-circle me-1"></i> Add Item
                </button>

                <!-- Signatories Row -->
                <div class="action-row">
                    <div class="signatory-box">
                        <span class="field-label" style="text-align: left;">Releasing Officer:</span>
                        <input type="text" class="field-input" name="releasing_officer" style="text-align: center;" placeholder="Enter Name" required>
                        <div class="signature-line">RELEASING OFFICER SIGNATURE</div>
                    </div>
                    <div class="signatory-box">
                        <span class="field-label" style="text-align: left;">Approved by:</span>
                        <input type="text" class="field-input" name="approved_by" style="text-align: center;" placeholder="Enter Name" required>
                        <div class="signature-line">APPROVED BY SIGNATURE</div>
                    </div>
                </div>

                <div class="mt-5 text-end no-print">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-save me-2"></i> Save Borrow Slip
                    </button>
                    <a href="borrowing.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-warning m-4" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>No serviceable assets available!</strong>
                Please ensure some assets are marked as serviceable.
                <a href="assets.php" class="alert-link">Manage Assets</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Serviceable assets data
        const serviceableAssets = <?php echo json_encode($serviceable_assets); ?>;
        
        console.log('Serviceable assets loaded:', serviceableAssets.length);
        console.log('First serviceable asset:', serviceableAssets[0]);
        console.log('Serviceable assets structure:', JSON.stringify(serviceableAssets, null, 2));
        
        let itemCount = 0;

        // Add item row
        function addItemRow() {
            if (serviceableAssets.length === 0) {
                alert('No serviceable assets available for borrowing.');
                return;
            }
            
            itemCount++;
            const tbody = document.getElementById('itemsTableBody');
            const row = document.createElement('tr');
            row.id = `itemRow${itemCount}`;
            
            const options = serviceableAssets.map((asset, index) => 
                `<option value="${index}">${asset.asset_description} - ${asset.item_description} (Property No: ${asset.property_no || 'N/A'})</option>`
            ).join('');
            
            row.innerHTML = `
                <td class="p-0">
                    <select class="form-select border-0 bg-transparent py-3" name="asset_item_id_${itemCount}" onchange="updateAvailableQuantity(${itemCount})" required>
                        <option value="">-- Click to Select Asset --</option>
                        ${options}
                    </select>
                </td>
                <td class="p-0">
                    <input type="number" class="form-control border-0 bg-transparent text-center py-3" name="quantity_${itemCount}" min="1" value="1" readonly required>
                </td>
                <td class="p-0">
                    <input type="text" class="form-control border-0 bg-transparent py-3" name="remarks_${itemCount}" placeholder="Add remarks here...">
                </td>
                <td class="text-center no-print align-middle">
                    <button type="button" class="btn btn-sm text-danger" onclick="removeItemRow(${itemCount})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        }

        // Remove item row
        function removeItemRow(rowId) {
            const row = document.getElementById(`itemRow${rowId}`);
            if (row) {
                row.remove();
            }
        }

        // Update available quantity (modified to handle new UI)
        function updateAvailableQuantity(rowId) {
            const select = document.querySelector(`select[name="asset_item_id_${rowId}"]`);
            const quantityInput = document.querySelector(`input[name="quantity_${rowId}"]`);
            
            const selectedIndex = select.value;
            if (selectedIndex !== '') {
                const selectedAsset = serviceableAssets[selectedIndex];
                if (selectedAsset) {
                    quantityInput.value = '1';
                    quantityInput.readOnly = true;
                }
            } else {
                quantityInput.value = '1';
                quantityInput.readOnly = false;
            }
        }

        // Form submission
        const form = document.querySelector('form');
        if (form) {
            console.log('Form found, attaching submit event listener');
            form.addEventListener('submit', function(e) {
                console.log('=== FORM SUBMISSION DEBUG START ===');
                e.preventDefault();
                
                console.log('Prevented default submission');
                
                // Collect items data
                const items = [];
                const rows = document.querySelectorAll('#itemsTableBody tr');
                
                console.log('Found rows:', rows.length);
                console.log('Serviceable assets:', serviceableAssets.length);
                
                if (rows.length === 0) {
                    alert('Please add at least one item to borrow.');
                    return false;
                }
                
                rows.forEach((row, index) => {
                    const assetItemSelect = row.querySelector('select[name^="asset_item_id_"]');
                    const quantityInput = row.querySelector('input[name^="quantity_"]');
                    const remarksInput = row.querySelector('input[name^="remarks_"]');
                    
                    console.log(`=== ROW ${index} DEBUG ===`);
                    console.log('assetItemSelect:', assetItemSelect);
                    console.log('assetItemSelect value:', assetItemSelect?.value);
                    console.log('quantityInput:', quantityInput);
                    console.log('quantityInput value:', quantityInput?.value);
                    console.log('remarksInput:', remarksInput);
                    console.log('remarksInput value:', remarksInput?.value);
                    
                    // Check if asset is selected
                    if (assetItemSelect && assetItemSelect.value === '') {
                        console.log(`Row ${index}: No asset selected, skipping`);
                        return; // Skip this row
                    }
                    
                    // Process if select has valid value (quantity is always 1 for individual items)
                    if (assetItemSelect && assetItemSelect.value !== '' && quantityInput && quantityInput.value !== '' && parseInt(quantityInput.value) >= 1) {
                        const selectedIndex = parseInt(assetItemSelect.value);
                        const selectedAsset = serviceableAssets[selectedIndex];
                        
                        console.log(`Processing asset at index ${selectedIndex}:`, selectedAsset);
                        
                        if (selectedAsset) {
                            // Create the "thing" field with formatted asset information
                            const thingText = `${selectedAsset.asset_description}\r\nInventory Tag: ${selectedAsset.inventory_tag}\r\nProperty No: ${selectedAsset.property_no}`;
                            
                            const itemData = {
                                asset_item_id: selectedAsset.asset_item_id,
                                thing: thingText,
                                inventory_tag: selectedAsset.inventory_tag,
                                property_no: selectedAsset.property_no,
                                category: selectedAsset.category_name || 'Uncategorized',
                                qty: "1", // String as per example
                                remarks: remarksInput ? remarksInput.value : ''
                            };
                            
                            console.log(`Adding item:`, itemData);
                            items.push(itemData);
                        } else {
                            console.error(`No asset found at index ${selectedIndex}`);
                        }
                    } else {
                        console.log(`Skipping row ${index} - missing required data`);
                        console.log('Condition check results:');
                        console.log('assetItemSelect exists:', !!assetItemSelect);
                        console.log('assetItemSelect value not empty:', assetItemSelect?.value !== '');
                        console.log('quantityInput exists:', !!quantityInput);
                        console.log('quantityInput value not empty:', quantityInput?.value !== '');
                        console.log('quantityInput value >= 1:', parseInt(quantityInput?.value) >= 1);
                    }
                });
                
                console.log('=== FINAL ITEMS DEBUG ===');
                console.log('Items collected:', items);
                console.log('Items length:', items.length);
                console.log('Items JSON string:', JSON.stringify(items));
                
                if (items.length === 0) {
                    alert('Please select an asset from the dropdown menu for each item row. Make sure to choose an asset before submitting.');
                    return false;
                }
                
                // Set items as simple JSON with Description/Asset, Quantity, and Remarks
                const itemsJsonField = document.getElementById('itemsJsonField');
                
                // Create simple items array with Description/Asset, Quantity, and Remarks
                const simpleItems = items.map(item => ({
                    description: item.thing || 'Asset Description',
                    quantity: item.qty || '1',
                    remarks: item.remarks || ''
                }));
                
                const itemsJson = JSON.stringify(simpleItems);
                itemsJsonField.value = itemsJson;
                
                console.log('Setting items_json:', itemsJson);
                console.log('itemsJson field value after setting:', itemsJsonField.value);
                
                // Verify the field is set before proceeding
                if (!itemsJsonField.value || itemsJsonField.value === '[]') {
                    alert('Items data is not properly set. Please try again.');
                    console.error('Items field not set properly:', itemsJsonField.value);
                    return false;
                }
                
                console.log('=== PRE-SUBMISSION CHECK ===');
                console.log('Items array:', items);
                console.log('Items JSON field value:', itemsJsonField.value);
                
                // Parse the items JSON to check if it contains actual items
                try {
                    const parsedItems = JSON.parse(itemsJson);
                    if (!parsedItems || parsedItems.length === 0) {
                        alert('No items selected! Please select at least one asset before submitting.');
                        return false;
                    }
                } catch (e) {
                    alert('Invalid data format! Please try again.');
                    console.error('JSON parse error:', e);
                    return false;
                }
                
                // Create a temporary form submission to ensure data is sent
                const tempForm = document.createElement('form');
                tempForm.method = 'POST';
                tempForm.action = form.action;
                
                // Copy all form fields
                const formData = new FormData(form);
                console.log('=== FORM DATA DEBUG ===');
                for (let [key, value] of formData.entries()) {
                    console.log('Form field:', key, '=', value);
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    tempForm.appendChild(input);
                }
                
                // Ensure items_json is properly set
                const itemsInput = document.createElement('input');
                itemsInput.type = 'hidden';
                itemsInput.name = 'items_json';
                itemsInput.value = itemsJson;
                tempForm.appendChild(itemsInput);
                
                console.log('=== TEMP FORM DEBUG ===');
                console.log('Temp form action:', tempForm.action);
                console.log('Temp form method:', tempForm.method);
                console.log('Items JSON being sent:', itemsJson);
                
                document.body.appendChild(tempForm);
                console.log('Submitting temporary form...');
                tempForm.submit();
                
                return false;
            });
        } else {
            console.error('Form not found!');
        }

        // Add initial item row when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('itemsTableBody');
            console.log('=== PAGE LOAD DEBUG ===');
            console.log('Serviceable assets available:', serviceableAssets.length);
            console.log('Tbody element:', tbody);
            console.log('Tbody children count:', tbody ? tbody.children.length : 'N/A');
            
            if (tbody && tbody.children.length === 0 && serviceableAssets.length > 0) {
                console.log('Adding initial item row...');
                addItemRow();
            } else if (serviceableAssets.length === 0) {
                console.error('No serviceable assets available for borrowing!');
                // Show a more user-friendly message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = '<strong>No Assets Available:</strong> There are no serviceable assets available for borrowing. Please add some assets to the system first.';
                const cardBody = document.querySelector('.card-body');
                if (cardBody) {
                    cardBody.insertBefore(alertDiv, cardBody.firstChild);
                }
            }
            
            // Set today's date as default for date borrowed
            const today = new Date().toISOString().split('T')[0];
            const dateBorrowedInput = document.querySelector('input[name="date_borrowed"]');
            if (dateBorrowedInput) {
                dateBorrowedInput.value = today;
            }
        });
    </script>

    <?php include 'includes/sidebar-scripts.php'; ?>
</body>
</html>
