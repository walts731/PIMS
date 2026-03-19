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

// Log dashboard access
logSystemAction($_SESSION['user_id'], 'access', 'admin_dashboard', 'Admin accessed dashboard');

// Get dashboard statistics
$stats = [];

// Check database connection first
if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // ===== ASSET ITEMS SUMMARY =====
        // Check if status column exists
        $check_item_status = $conn->query("SHOW COLUMNS FROM asset_items LIKE 'status'");
        $item_has_status = $check_item_status && $check_item_status->num_rows > 0;
        
        // Total Asset Value - separate query for clarity
        $total_value_query = "SELECT COALESCE(SUM(value), 0) as total_asset_value FROM asset_items";
        $total_value_result = $conn->query($total_value_query);
        if ($total_value_result) {
            $total_value_data = $total_value_result->fetch_assoc();
            $stats['total_asset_value'] = $total_value_data['total_asset_value'];
        }
        
        $asset_items_query = "SELECT 
            COUNT(*) as total_items" .
            ($item_has_status ? ",
            SUM(CASE WHEN status = 'serviceable' THEN 1 ELSE 0 END) as serviceable_items,
            SUM(CASE WHEN status = 'red_tagged' THEN 1 ELSE 0 END) as red_tagged_items,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_items,
            SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed_items,
            SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_items,
            SUM(CASE WHEN status = 'unserviceable' THEN 1 ELSE 0 END) as unserviceable_items" : ",
            0 as serviceable_items,
            0 as red_tagged_items,
            0 as maintenance_items,
            0 as borrowed_items,
            0 as disposed_items,
            0 as unserviceable_items") . "
            FROM asset_items";
        $asset_result = $conn->query($asset_items_query);
        if ($asset_result) {
            $asset_data = $asset_result->fetch_assoc();
            $stats = array_merge($stats, $asset_data);
        }
        
        // ===== ASSET CATEGORIES =====
        $categories_query = "SELECT 
            ac.id, ac.category_code as code, ac.category_name as name,
            COUNT(ai.id) as item_count,
            COUNT(DISTINCT ai.asset_id) as asset_count,
            SUM(ai.value) as total_value
            FROM asset_categories ac
            LEFT JOIN assets a ON ac.id = a.asset_categories_id
            LEFT JOIN asset_items ai ON a.id = ai.asset_id
            GROUP BY ac.id, ac.category_code, ac.category_name
            ORDER BY total_value DESC
            LIMIT 6";
        $categories_result = $conn->query($categories_query);
        $stats['top_categories'] = [];
        if ($categories_result) {
            while ($row = $categories_result->fetch_assoc()) {
                $stats['top_categories'][] = $row;
            }
        }

        // ===== CONSUMABLES =====
        $consumables_query = "SELECT 
            COUNT(*) as total_consumables,
            SUM(quantity) as total_quantity,
            SUM(quantity * unit_cost) as total_value,
            COUNT(DISTINCT office_id) as offices_with_consumables
            FROM consumables";
        $consumables_result = $conn->query($consumables_query);
        if ($consumables_result) {
            $stats = array_merge($stats, $consumables_result->fetch_assoc());
        }

        // Low stock count
        $low_stock_query = "SELECT COUNT(*) as low_stock_count FROM consumables WHERE quantity <= reorder_level";
        $low_stock_result = $conn->query($low_stock_query);
        if ($low_stock_result) {
            $stats['low_stock_count'] = $low_stock_result->fetch_assoc()['low_stock_count'];
        }

        // ===== FORMS/ENTRIES SUMMARY =====
        // PAR Forms - calculate from par_items
        $par_query = "SELECT COUNT(*) as par_count FROM par_forms";
        $par_result = $conn->query($par_query);
        if ($par_result) {
            $par_data = $par_result->fetch_assoc();
            $stats['par_count'] = $par_data['par_count'];
        }
        
        // Calculate PAR total from par_items
        $par_value_query = "SELECT COALESCE(SUM(amount), 0) as par_value FROM par_items";
        $par_value_result = $conn->query($par_value_query);
        if ($par_value_result) {
            $par_value_data = $par_value_result->fetch_assoc();
            $stats['par_value'] = $par_value_data['par_value'];
        } else {
            $stats['par_value'] = 0;
        }

        // ICS Forms - calculate from ics_items using total_cost column
        $ics_query = "SELECT COUNT(*) as ics_count FROM ics_forms";
        $ics_result = $conn->query($ics_query);
        if ($ics_result) {
            $ics_data = $ics_result->fetch_assoc();
            $stats['ics_count'] = $ics_data['ics_count'];
        }
        
        // Calculate ICS total from ics_items using total_cost column
        $ics_value_query = "SELECT COALESCE(SUM(total_cost), 0) as ics_value FROM ics_items";
        $ics_value_result = $conn->query($ics_value_query);
        if ($ics_value_result) {
            $ics_value_data = $ics_value_result->fetch_assoc();
            $stats['ics_value'] = $ics_value_data['ics_value'];
        } else {
            $stats['ics_value'] = 0;
        }

        // RIS Forms - has total_amount column
        $check_ris = $conn->query("SHOW COLUMNS FROM ris_forms LIKE 'total_amount'");
        $ris_has_total = $check_ris && $check_ris->num_rows > 0;
        $ris_query = "SELECT COUNT(*) as ris_count" . ($ris_has_total ? ", COALESCE(SUM(total_amount), 0) as ris_value" : ", 0 as ris_value") . " FROM ris_forms";
        $ris_result = $conn->query($ris_query);
        if ($ris_result) {
            $ris_data = $ris_result->fetch_assoc();
            $stats['ris_count'] = $ris_data['ris_count'];
            $stats['ris_value'] = $ris_data['ris_value'];
        }

        // IIRUP Forms - calculate from iirup_items
        $iirup_query = "SELECT COUNT(*) as iirup_count FROM iirup_forms";
        $iirup_result = $conn->query($iirup_query);
        if ($iirup_result) {
            $iirup_data = $iirup_result->fetch_assoc();
            $stats['iirup_count'] = $iirup_data['iirup_count'];
        }
        
        // Calculate IIRUP total from iirup_items using total_cost column
        $iirup_value_query = "SELECT COALESCE(SUM(total_cost), 0) as iirup_value FROM iirup_items";
        $iirup_value_result = $conn->query($iirup_value_query);
        if ($iirup_value_result) {
            $iirup_value_data = $iirup_value_result->fetch_assoc();
            $stats['iirup_value'] = $iirup_value_data['iirup_value'];
        } else {
            $stats['iirup_value'] = 0;
        }

        // ITR Forms - check if we should use items table instead
        $itr_items_check = $conn->query("SELECT COUNT(*) as count FROM itr_items");
        $itr_items_count = $itr_items_check->fetch_assoc()['count'];
        
        if ($itr_items_count > 0) {
            // Use items table if it has data
            $itr_query = "SELECT COUNT(*) as itr_count FROM itr_forms";
            $itr_result = $conn->query($itr_query);
            if ($itr_result) {
                $itr_data = $itr_result->fetch_assoc();
                $stats['itr_count'] = $itr_data['itr_count'];
            }
            
            // Calculate ITR total from itr_items
            $itr_value_query = "SELECT COALESCE(SUM(total_amount), 0) as itr_value FROM itr_items";
            $itr_value_result = $conn->query($itr_value_query);
            if ($itr_value_result) {
                $itr_value_data = $itr_value_result->fetch_assoc();
                $stats['itr_value'] = $itr_value_data['itr_value'];
            } else {
                $stats['itr_value'] = 0;
            }
        } else {
            // Use forms table if no items exist
            $check_itr = $conn->query("SHOW COLUMNS FROM itr_forms LIKE 'total_amount'");
            $itr_has_total = $check_itr && $check_itr->num_rows > 0;
            $itr_query = "SELECT COUNT(*) as itr_count" . ($itr_has_total ? ", COALESCE(SUM(total_amount), 0) as itr_value" : ", 0 as itr_value") . " FROM itr_forms";
            $itr_result = $conn->query($itr_query);
            if ($itr_result) {
                $itr_data = $itr_result->fetch_assoc();
                $stats['itr_count'] = $itr_data['itr_count'];
                $stats['itr_value'] = $itr_data['itr_value'];
            }
        }

        // ===== INVENTORY TAGS & RED TAGS =====
        // Check if inventory_tags table exists
        $check_inventory_tags_table = $conn->query("SHOW TABLES LIKE 'inventory_tags'");
        $inventory_tags_exists = $check_inventory_tags_table && $check_inventory_tags_table->num_rows > 0;
        
        if ($inventory_tags_exists) {
            // Check if status column exists
            $check_tags_status = $conn->query("SHOW COLUMNS FROM inventory_tags LIKE 'status'");
            $tags_has_status = $check_tags_status && $check_tags_status->num_rows > 0;
            
            $tags_query = "SELECT 
                COUNT(*) as total_tags" . 
                ($tags_has_status ? ",
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_tags,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tags" : ",
                0 as active_tags,
                0 as pending_tags") . "
                FROM inventory_tags";
            $tags_result = $conn->query($tags_query);
            if ($tags_result) {
                $stats = array_merge($stats, $tags_result->fetch_assoc());
            }
        } else {
            // Set default values if table doesn't exist
            $stats['total_tags'] = 0;
            $stats['active_tags'] = 0;
            $stats['pending_tags'] = 0;
        }

        $check_red_status = $conn->query("SHOW COLUMNS FROM red_tags LIKE 'status'");
        $red_has_status = $check_red_status && $check_red_status->num_rows > 0;
        
        $red_tags_query = "SELECT 
            COUNT(*) as red_tag_count" . 
            ($red_has_status ? ",
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_red_tags" : ",
            0 as active_red_tags") . "
            FROM red_tags";
        $red_tags_result = $conn->query($red_tags_query);
        if ($red_tags_result) {
            $red_data = $red_tags_result->fetch_assoc();
            $stats['red_tag_count'] = $red_data['red_tag_count'];
            $stats['active_red_tags'] = $red_data['active_red_tags'];
        }

        // ===== OFFICES & EMPLOYEES =====
        // Check if status column exists
        $check_office_status = $conn->query("SHOW COLUMNS FROM offices LIKE 'status'");
        $office_has_status = $check_office_status && $check_office_status->num_rows > 0;
        
        $offices_query = "SELECT COUNT(*) as office_count FROM offices" . ($office_has_status ? " WHERE status = 'active'" : "");
        $offices_result = $conn->query($offices_query);
        if ($offices_result) {
            $stats['office_count'] = $offices_result->fetch_assoc()['office_count'];
        }

        $check_emp_status = $conn->query("SHOW COLUMNS FROM employees LIKE 'status'");
        $emp_has_status = $check_emp_status && $check_emp_status->num_rows > 0;
        
        $employees_query = "SELECT 
            COUNT(*) as employee_count" .
            ($emp_has_status ? ",
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_employees" : ",
            COUNT(*) as active_employees") . "
            FROM employees";
        $employees_result = $conn->query($employees_query);
        if ($employees_result) {
            $emp_data = $employees_result->fetch_assoc();
            $stats['employee_count'] = $emp_data['employee_count'];
            $stats['active_employees'] = $emp_data['active_employees'];
        }

        // ===== OFFICE DISTRIBUTION =====
        $office_dist_query = "SELECT 
            o.office_name,
            COUNT(ai.id) as item_count,
            SUM(ai.value) as item_value
            FROM offices o
            LEFT JOIN asset_items ai ON o.id = ai.office_id
            GROUP BY o.id, o.office_name
            ORDER BY item_value DESC
            LIMIT 5";
        $office_dist_result = $conn->query($office_dist_query);
        $stats['office_distribution'] = [];
        $total_value_from_offices = 0;
        if ($office_dist_result) {
            while ($row = $office_dist_result->fetch_assoc()) {
                $stats['office_distribution'][] = $row;
                $total_value_from_offices += $row['item_value'];
            }
            // Update total_value to be the sum of all office distribution values
            $stats['total_value'] = $total_value_from_offices;
        }

        // ===== RECENT ACTIVITY =====
        $recent_items_query = "SELECT 
            ai.id, ai.description" . ($item_has_status ? ", ai.status" : "") . ", ai.last_updated,
            a.description as asset_description,
            o.office_name
            FROM asset_items ai
            LEFT JOIN assets a ON ai.asset_id = a.id
            LEFT JOIN offices o ON ai.office_id = o.id
            ORDER BY ai.last_updated DESC
            LIMIT 5";
        $recent_items_result = $conn->query($recent_items_query);
        $stats['recent_items'] = [];
        if ($recent_items_result) {
            while ($row = $recent_items_result->fetch_assoc()) {
                $stats['recent_items'][] = $row;
            }
        }

        $recent_forms_query = "
            (SELECT 'PAR' as form_type, par_no COLLATE utf8mb4_unicode_ci as form_no, created_at, 0 as total_amount, 'par_entries.php' as link FROM par_forms ORDER BY created_at DESC LIMIT 2)
            UNION ALL
            (SELECT 'ICS' as form_type, ics_no COLLATE utf8mb4_unicode_ci as form_no, created_at, COALESCE((SELECT SUM(total_cost) FROM ics_items WHERE form_id = ics_forms.id), 0) as total_amount, 'ics_entries.php' as link FROM ics_forms ORDER BY created_at DESC LIMIT 2)
            UNION ALL
            (SELECT 'RIS' as form_type, ris_no COLLATE utf8mb4_unicode_ci as form_no, created_at, total_amount as total_amount, 'ris_entries.php' as link FROM ris_forms ORDER BY created_at DESC LIMIT 2)
            UNION ALL
            (SELECT 'IIRUP' as form_type, form_number COLLATE utf8mb4_unicode_ci as form_no, created_at, COALESCE((SELECT SUM(total_cost) FROM iirup_items WHERE form_id = iirup_forms.id), 0) as total_amount, 'iirup_entries.php' as link FROM iirup_forms ORDER BY created_at DESC LIMIT 2)
            UNION ALL
            (SELECT 'ITR' as form_type, itr_no COLLATE utf8mb4_unicode_ci as form_no, created_at, COALESCE(
                (CASE 
                    WHEN (SELECT COUNT(*) FROM itr_items) > 0 
                    THEN (SELECT SUM(total_amount) FROM itr_items WHERE form_id = itr_forms.id)
                    ELSE total_amount
                END), 0) as total_amount, 'itr_entries.php' as link FROM itr_forms ORDER BY created_at DESC LIMIT 2)
            ORDER BY created_at DESC
            LIMIT 5";
        $recent_forms_result = $conn->query($recent_forms_query);
        $stats['recent_forms'] = [];
        if ($recent_forms_result) {
            while ($row = $recent_forms_result->fetch_assoc()) {
                $stats['recent_forms'][] = $row;
            }
        }

        // ===== MAINTENANCE ITEMS =====
        $check_item_status = $conn->query("SHOW COLUMNS FROM asset_items LIKE 'status'");
        $item_has_status = $check_item_status && $check_item_status->num_rows > 0;
        $maintenance_query = "SELECT 
            ai.id, ai.description" . ($item_has_status ? ", ai.status" : "") . ", ai.last_updated,
            a.description as asset_description,
            o.office_name
            FROM asset_items ai
            LEFT JOIN assets a ON ai.asset_id = a.id
            LEFT JOIN offices o ON ai.office_id = o.id" .
            ($item_has_status ? " WHERE ai.status = 'maintenance'" : "") . "
            ORDER BY ai.last_updated DESC
            LIMIT 5";
        $maintenance_result = $conn->query($maintenance_query);
        $stats['maintenance_items_list'] = [];
        if ($maintenance_result) {
            while ($row = $maintenance_result->fetch_assoc()) {
                $stats['maintenance_items_list'][] = $row;
            }
        }

        // ===== LOW STOCK ALERTS =====
        $low_stock_items_query = "SELECT 
            c.id, c.description, c.quantity, c.reorder_level,
            o.office_name
            FROM consumables c
            LEFT JOIN offices o ON c.office_id = o.id
            WHERE c.quantity <= c.reorder_level
            ORDER BY c.quantity ASC
            LIMIT 5";
        $low_stock_items_result = $conn->query($low_stock_items_query);
        $stats['low_stock_items'] = [];
        if ($low_stock_items_result) {
            while ($row = $low_stock_items_result->fetch_assoc()) {
                $stats['low_stock_items'][] = $row;
            }
        }

    } catch (Exception $e) {
        $stats['error'] = "Error fetching dashboard stats: " . $e->getMessage();
        error_log("Admin Dashboard Error: " . $e->getMessage());
    }
}

// Set default values if not set
$defaults = [
    'total_items' => 0, 'serviceable_items' => 0, 'red_tagged_items' => 0, 'maintenance_items' => 0, 'borrowed_items' => 0, 'disposed_items' => 0, 'unserviceable_items' => 0,
    'total_asset_value' => 0, 'total_value' => 0, 'top_categories' => [],
    'total_consumables' => 0, 'total_quantity' => 0, 
    'low_stock_count' => 0,
    'total_fuel_stock' => 0, 'fuel_types_count' => 0,
    'today_transactions' => 0, 'fuel_out_today' => 0, 'fuel_in_today' => 0,
    'par_count' => 0, 'ics_count' => 0, 'ris_count' => 0, 'iirup_count' => 0, 'itr_count' => 0,
    'par_value' => 0, 'ics_value' => 0, 'ris_value' => 0, 'iirup_value' => 0, 'itr_value' => 0,
    'total_tags' => 0, 'active_tags' => 0, 'pending_tags' => 0,
    'red_tag_count' => 0, 'active_red_tags' => 0,
    'office_count' => 0, 'employee_count' => 0, 'active_employees' => 0,
    'office_distribution' => [],
    'recent_items' => [], 'recent_forms' => [],
    'maintenance_items_list' => [], 'low_stock_items' => []
];

foreach ($defaults as $key => $value) {
    if (!isset($stats[$key])) {
        $stats[$key] = $value;
    }
}

// Calculate totals
$total_forms = $stats['par_count'] + $stats['ics_count'] + $stats['ris_count'] + $stats['iirup_count'] + $stats['itr_count'];
$total_forms_value = $stats['par_value'] + $stats['ics_value'] + $stats['ris_value'] + $stats['iirup_value'] + $stats['itr_value'];
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PIMS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Verify Chart.js loaded
        console.log('Chart.js version:', typeof Chart !== 'undefined' ? Chart.version : 'Not loaded');
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Admin Dashboard'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-grid-1x2-fill"></i> Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0">Welcome back! Here's an overview of your property inventory system.</p>
                    <?php if (isset($stats['error'])): ?>
                        <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <small><?php echo htmlspecialchars($stats['error']); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="exportData()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <a href="assets.php" class="module-card">
                    <div class="module-icon blue">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="module-title">Assets</div>
                    <div class="module-desc">Manage assets</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="consumables.php" class="module-card">
                    <div class="module-icon orange">
                        <i class="bi bi-archive"></i>
                    </div>
                    <div class="module-title">Consumables</div>
                    <div class="module-desc">Supplies & stock</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="borrowing.php" class="module-card">
                    <div class="module-icon green">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="module-title">Borrowing</div>
                    <div class="module-desc">Asset borrowing</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="inventory_tags.php" class="module-card">
                    <div class="module-icon teal">
                        <i class="bi bi-qr-code"></i>
                    </div>
                    <div class="module-title">Inventory Tags</div>
                    <div class="module-desc">QR & tracking</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="reports.php" class="module-card">
                    <div class="module-icon red">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="module-title">Reports</div>
                    <div class="module-desc">View reports</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="employees.php" class="module-card">
                    <div class="module-icon purple">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="module-title">Employees</div>
                    <div class="module-desc">Staff management</div>
                </a>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
                    <div class="stat-label"><i class="bi bi-box-seam"></i> Total Asset Items</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-value">₱<?php echo number_format($stats['total_value'], 2); ?></div>
                    <div class="stat-label"><i class="bi bi-cash-stack"></i> Total Asset Value</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['employee_count']); ?></div>
                    <div class="stat-label"><i class="bi bi-people"></i> Employees</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="section-card glass-morphism h-100">
                            <div class="section-title">
                                <i class="bi bi-pie-chart"></i> Asset Status Distribution
                            </div>
                            <div class="chart-container">
                                <canvas id="assetStatusChart"></canvas>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-4">
                                    <div class="small text-muted">Serviceable</div>
                                    <div class="fw-bold text-success"><?php echo $stats['serviceable_items']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Red Tagged</div>
                                    <div class="fw-bold text-danger"><?php echo $stats['red_tagged_items']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Maintenance</div>
                                    <div class="fw-bold text-warning"><?php echo $stats['maintenance_items']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Borrowed</div>
                                    <div class="fw-bold text-primary"><?php echo $stats['borrowed_items']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Disposed</div>
                                    <div class="fw-bold text-danger"><?php echo $stats['disposed_items']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Unserviceable</div>
                                    <div class="fw-bold text-secondary"><?php echo $stats['unserviceable_items']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="section-card h-100">
                            <div class="section-title">
                                <i class="bi bi-building"></i> Office Distribution
                            </div>
                            <div class="chart-container">
                                <canvas id="officeChart"></canvas>
                            </div>
                            <!-- Office data for JavaScript -->
                            <script type="application/json" id="officeData">
                            <?php echo json_encode(array_slice($stats['office_distribution'], 0, 5)); ?>
                            </script>

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="section-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="section-title mb-0">
                                    <i class="bi bi-tags"></i> Top Categories
                                </div>
                                <a href="asset_categories.php" class="view-all">
                                    All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <?php if (!empty($stats['top_categories'])): ?>
                                <?php foreach ($stats['top_categories'] as $category): ?>
                                <div class="category-item">
                                    <div class="category-info">
                                        <span class="category-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                    </div>
                                    <div class="category-count"><?php echo $category['item_count']; ?> items</div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <small>No categories found</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="section-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">
                            <i class="bi bi-clock-history"></i> Recent Activity
                        </div>
                    </div>
                    <div class="activity-list">
                        <?php 
                        $all_activity = [];
                        
                        foreach ($stats['recent_items'] as $item) {
                            $all_activity[] = [
                                'type' => 'asset',
                                'icon' => 'bi-box',
                                'icon_class' => 'blue',
                                'title' => htmlspecialchars($item['description']),
                                'meta' => 'Status: ' . ucfirst($item['status']),
                                'time' => $item['last_updated']
                            ];
                        }
                        
                        foreach ($stats['recent_forms'] as $form) {
                            $all_activity[] = [
                                'type' => 'form',
                                'icon' => 'bi-file-earmark',
                                'icon_class' => 'purple',
                                'title' => $form['form_type'] . ' - ' . $form['form_no'],
                                'meta' => 'PHP ' . number_format($form['total_amount'], 2),
                                'time' => $form['created_at']
                            ];
                        }
                        
                        usort($all_activity, function($a, $b) {
                            return strtotime($b['time']) - strtotime($a['time']);
                        });
                        
                        $all_activity = array_slice($all_activity, 0, 6);
                        
                        if (!empty($all_activity)): 
                            foreach ($all_activity as $activity): 
                                $time_diff = time() - strtotime($activity['time']);
                                if ($time_diff < 3600) {
                                    $time_display = floor($time_diff / 60) . ' min ago';
                                } elseif ($time_diff < 86400) {
                                    $time_display = floor($time_diff / 3600) . ' hours ago';
                                } else {
                                    $time_display = floor($time_diff / 86400) . ' days ago';
                                }
                        ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['icon_class']; ?>">
                                    <i class="bi <?php echo $activity['icon']; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title font-weight-bold fs-6"><?php echo $activity['title']; ?> <span class="activity-meta fw-bold text-primary"><?php echo $activity['meta']; ?>:</span> <span class="activity-time fw-semibold text-secondary"><?php echo $time_display; ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4"></i>
                                <p class="small mt-2">No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            

        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script src="dashboard.js"></script>
</body>
</html>