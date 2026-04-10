<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id === 0) {
    die("Invalid Item ID");
}

// Get asset item details
$item = null;
$item_sql = "SELECT ai.*, 
                   ac.category_name, ac.category_code,
                   o.office_name, o.office_code,
                   e.firstname, e.lastname, e.employee_no,
                   veh.brand as vehicle_brand, veh.model as vehicle_model, veh.plate_number, veh.engine_number, veh.chassis_number,
                   furn.material, furn.dimensions,
                   mach.machine_type, mach.serial_number as mach_serial,
                   comp.processor, comp.ram_capacity, comp.storage_capacity
            FROM asset_items ai 
            LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN employees e ON ai.employee_id = e.id
            LEFT JOIN asset_vehicles veh ON ai.id = veh.asset_item_id
            LEFT JOIN asset_furniture furn ON ai.id = furn.asset_item_id
            LEFT JOIN asset_machinery mach ON ai.id = mach.asset_item_id
            LEFT JOIN asset_computers comp ON ai.id = comp.asset_item_id
            WHERE ai.id = ?";

$stmt = $conn->prepare($item_sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    die("Item not found");
}

// Get system settings for logo
$system_settings = [];
$settings_result = $conn->query("SELECT setting_name, setting_value FROM system_settings");
while ($row = $settings_result->fetch_assoc()) {
    $system_settings[$row['setting_name']] = $row['setting_value'];
}
$logo_path = '../assets/images/logo.png';
if (!empty($system_settings['system_logo'])) {
    $logo_path = '../' . $system_settings['system_logo'];
}
$system_name = $system_settings['system_name'] ?? 'PIMS';

// Prepare Article description
$article_details = $item['description'];
if ($item['category_code'] === '030') { // Computer
    $article_details .= "\nPROCESSOR: " . ($item['processor'] ?: 'N/A');
    $article_details .= "\nRAM: " . ($item['ram_capacity'] ?: 'N/A');
} elseif ($item['category_code'] === '07') { // Vehicles
    $article_details .= "\nMODEL: " . ($item['vehicle_model'] ?: 'N/A');
    $article_details .= "\nENGINE: " . ($item['engine_number'] ?: 'N/A');
}

// Prep employee name for display
$emp_name = trim(($item['firstname'] ?? '') . ' ' . ($item['lastname'] ?? ''));
if (empty($emp_name)) $emp_name = 'N/A';

// Determine if we should show the Accountable column based on category
$excluded_cats = ['LND', 'OInfra', 'Buildings', 'Land Imp'];
$show_emp_col = !in_array($item['category_name'], $excluded_cats) && !in_array($item['category_code'], $excluded_cats);
$article_width = $show_emp_col ? 20 : 35;

// Handle Add Improvement Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_improvement') {
    $improvement_date = $_POST['improvement_date'] ?: date('Y-m-d');
    $description = trim($_POST['description']);
    $qty = intval($_POST['qty']) ?: 1;
    $amount = floatval($_POST['amount']);
    $remarks = trim($_POST['remarks']);
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Ensure quantity column exists and status is flexible in asset_items
        $check_q_sql = "SHOW COLUMNS FROM `asset_items` LIKE 'quantity'";
        $q_result = $conn->query($check_q_sql);
        if ($q_result->num_rows == 0) {
            $conn->query("ALTER TABLE `asset_items` ADD COLUMN `quantity` INT DEFAULT 1 AFTER `unit`");
        }
        
        // Also ensure status column can hold longer remarks to avoid truncation
        $conn->query("ALTER TABLE `asset_items` MODIFY COLUMN `status` VARCHAR(100) DEFAULT 'serviceable'");

        // 1. Insert search for PREVIOUS state (using current $item data) into improvements table
        $prev_sql = "INSERT INTO asset_item_improvements (item_id, improvement_date, description, qty, amount, remarks) VALUES (?, ?, ?, ?, ?, ?)";
        $prev_stmt = $conn->prepare($prev_sql);
        $curr_date = date('Y-m-d');
        $current_qty = isset($item['quantity']) ? $item['quantity'] : 1;
        $prev_stmt->bind_param("issids", $item_id, $curr_date, $item['description'], $current_qty, $item['value'], $item['status']);
        $prev_stmt->execute();
        
        // 2. Insert NEW data (from modal) into improvements table
        $new_sql = "INSERT INTO asset_item_improvements (item_id, improvement_date, description, qty, amount, remarks) VALUES (?, ?, ?, ?, ?, ?)";
        $new_stmt = $conn->prepare($new_sql);
        $new_stmt->bind_param("issids", $item_id, $improvement_date, $description, $qty, $amount, $remarks);
        $new_stmt->execute();
        
        // 3. Update asset_items table with the new data
        $upd_sql = "UPDATE asset_items SET quantity = ?, value = ?, status = ? WHERE id = ?";
        $upd_stmt = $conn->prepare($upd_sql);
        $upd_stmt->bind_param("idsi", $qty, $amount, $remarks, $item_id);
        $upd_stmt->execute();
        
        // Record in system history
        logSystemAction($_SESSION['user_id'], 'add_improvement', 'asset_management', "Added improvement/repair record and updated Asset Item ID: {$item_id} (New Value: {$amount}, Qty: {$qty}, Status: {$remarks})");
        
        $conn->commit();
        $_SESSION['success'] = "Improvement and asset status updated successfully!";
        header("Location: card_item.php?id=" . $item_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to add improvement: " . $e->getMessage();
    }
}

// Fetch all improvements
$improvements = [];
$imp_result = $conn->query("SELECT * FROM asset_item_improvements WHERE item_id = $item_id ORDER BY improvement_date ASC");
if ($imp_result) {
    while ($row = $imp_result->fetch_assoc()) {
        $improvements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Card Detail - <?php echo htmlspecialchars($item['property_no']); ?> | PIMS</title>
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
        .property-card-view {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            max-width: 900px;
            margin: 0 auto;
        }

        .card-preview {
            border: 2px solid #000;
            padding: 20px;
            box-sizing: border-box;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            text-align: left;
        }
        
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            position: relative;
        }
        
        .logo-img {
            position: absolute;
            left: 0;
            top: 0;
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .header-text {
            text-align: center;
        }
        
        .gov-title { font-size: 12px; font-weight: bold; }
        .municipality { font-size: 14px; font-weight: bold; }
        .province { font-size: 12px; }
        
        .main-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
            text-decoration: underline;
        }
        
        .office-location {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #0d6efd;
            text-decoration: underline;
            margin-top: 3px;
        }
        
        .office-sub {
            text-align: center;
            font-size: 10px;
            margin-bottom: 15px;
            color: #666;
        }
        
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
            table-layout: fixed;
        }
        
        .info-grid td {
            border: 1px solid #000;
            padding: 4px 6px;
            word-wrap: break-word;
            overflow: hidden;
        }
        
        .label { font-weight: normal; font-size: 9px; width: 15%; }
        .data { font-weight: bold; text-transform: uppercase; font-size: 10px; }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            table-layout: fixed;
        }
        
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            word-break: break-all;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 8px;
            height: 25px;
        }
        
        .article-cell {
            text-align: left !important;
            white-space: pre-line;
            font-weight: bold;
            font-size: 7.5px;
        }
        
        .emp-cell {
            text-align: left !important;
            white-space: pre-line;
            font-size: 7.5px;
            font-weight: 500;
        }
        
        .repair-cell {
            text-align: left !important;
            font-size: 8px;
        }

        @media print {
            /* Hide all UI elements */
            .sidebar, .topbar, .page-header, .no-print, .sidebar-toggle, .sidebar-overlay {
                display: none !important;
            }
            
            /* Reset layout for print */
            body { 
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 8.5in;
                height: 5.5in;
            }

            .main-wrapper, .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 8.5in !important;
                height: 5.5in !important;
                min-height: unset !important;
                background: white !important;
                display: block !important;
                box-shadow: none !important;
            }
            
            .property-card-view {
                padding: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                max-width: none !important;
                background: none !important;
                display: block !important;
            }

            .card-preview {
                width: 8in !important;
                height: 5in !important;
                border: 2px solid #000 !important;
                margin: 0.25in auto !important;
                box-sizing: border-box !important;
                page-break-inside: avoid;
            }
            
            @page {
                size: 8.5in 5.5in landscape;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="page-header no-print">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-card-text"></i> Property Card Preview
                        </h1>
                        <p class="text-muted mb-0">Single item property card record for administrative filing</p>
                    </div>
                    <div class="col-md-4 text-md-end no-print">
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="cardActions" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear-fill me-1"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px;">
                                <li>
                                    <button class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#improvementModal">
                                        <i class="bi bi-plus-circle me-2 text-success"></i> Add Improvement
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item py-2" onclick="window.print()">
                                        <i class="bi bi-printer me-2 text-primary"></i> Print Property Card
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a href="property_card.php" class="dropdown-item py-2 text-danger">
                                        <i class="bi bi-arrow-left-circle me-2"></i> Back to Property Card
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toast Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="property-card-view">
                <div class="card-preview">
                    <!-- Card Header -->
                    <div class="header">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="logo-img">
                        <div class="header-text">
                            <div class="gov-title">Republic of the Philippines</div>
                            <div class="municipality">Municipality of Pilar</div>
                            <div class="province">Province of Sorsogon</div>
                        </div>
                    </div>
                    
                    <div class="main-title">PROPERTY CARD</div>
                    <div class="office-location"><?php echo htmlspecialchars($item['office_name']); ?></div>
                    <div class="office-sub">(Department/Office/Location)</div>
                    
                    <!-- Item Info Grid -->
                    <table class="info-grid">
                        <tr>
                            <td class="label" style="width: 15%;">Property Number:</td>
                            <td class="data" style="width: 35%;"><?php echo htmlspecialchars($item['property_no']); ?></td>
                            <td class="label" style="width: 15%;">Description:</td>
                            <td class="data" colspan="3"><?php echo htmlspecialchars($item['description']); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Type :</td>
                            <td class="data"><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td class="label">Serial No.</td>
                            <td class="data" style="width: 20%;"><?php echo htmlspecialchars($item['serial_number'] ?: 'N/A'); ?></td>
                            <td class="label" style="width: 10%;">Location:</td>
                            <td class="data"><?php echo htmlspecialchars($item['office_name']); ?></td>
                        </tr>

                    </table>
                    
                    <!-- Transactions Table -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Acquisition Date</th>
                                <th style="width: <?php echo $article_width; ?>%;">Article</th>
                                <?php if ($show_emp_col): ?>
                                    <th style="width: 15%;">Accountable</th>
                                <?php endif; ?>
                                <th style="width: 5%;">Qty</th>
                                <th style="width: 12.5%;">Unit Value</th>
                                <th style="width: 15%;">Improvement / Repairs</th>
                                <th style="width: 12.5%;">Total</th>
                                <th style="width: 10%;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($improvements)): ?>
                            <!-- Initial Acquisition Row -->
                            <tr>
                                <td><?php echo date('M. Y', strtotime($item['acquisition_date'] ?: $item['created_at'])); ?></td>
                                <td class="article-cell"><?php echo htmlspecialchars($article_details); ?></td>
                                <?php if ($show_emp_col): ?>
                                    <td class="emp-cell"><?php echo htmlspecialchars($emp_name); ?></td>
                                <?php endif; ?>
                                <td>1</td>
                                <td><?php echo number_format($item['value'], 2); ?></td>
                                <td>N/A</td>
                                <td><strong><?php echo number_format($item['value'], 2); ?></strong></td>
                                <td style="font-weight: bold;"><?php echo strtoupper($item['status']); ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Improvement Rows -->
                            <?php foreach ($improvements as $imp): ?>
                            <tr>
                                <td><?php echo date('M. Y', strtotime($imp['improvement_date'])); ?></td>
                                <td class="article-cell"><?php echo htmlspecialchars($article_details); ?></td>
                                <?php if ($show_emp_col): ?>
                                    <td class="emp-cell"><?php echo htmlspecialchars($emp_name); ?></td>
                                <?php endif; ?>
                                <td><?php echo $imp['qty']; ?></td>
                                <td><?php echo number_format($imp['amount'] / $imp['qty'], 2); ?></td>
                                <td class="repair-cell"><?php echo htmlspecialchars($imp['description']); ?></td>
                                <td><strong><?php echo number_format($imp['amount'], 2); ?></strong></td>
                                <td style="font-weight: bold;"><?php echo strtoupper($imp['remarks'] ?: 'REPAIR/IMP'); ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- Fill empty rows if needed for alignment -->
                            <?php for($i = count($improvements); $i < 4; $i++): ?>
                            <tr>
                                <td height="35"></td>
                                <td></td>
                                <?php if ($show_emp_col): ?>
                                    <td></td>
                                <?php endif; ?>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Improvement Modal -->
    <div class="modal fade no-print" id="improvementModal" tabindex="-1" aria-labelledby="improvementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="improvementModalLabel">
                        <i class="bi bi-tools text-primary me-2"></i> Add Improvement / Repair
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_improvement">
                    <div class="modal-body pt-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Date of Improvement</label>
                            <input type="date" name="improvement_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Description / Article Details</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe the improvement or repair detail..." required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Quantity</label>
                                <input type="number" name="qty" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Total Cost (Amount)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">₱</span>
                                    <input type="number" name="amount" class="form-control" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted">Remarks / Status</label>
                            <select name="remarks" class="form-select">
                                <option value="serviceable" <?php echo ($item['status'] == 'serviceable') ? 'selected' : ''; ?>>Serviceable</option>
                                <option value="unserviceable" <?php echo ($item['status'] == 'unserviceable') ? 'selected' : ''; ?>>Unserviceable</option>
                                <option value="maintenance" <?php echo ($item['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Save Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
