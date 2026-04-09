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

// Log summary page access
logSystemAction($_SESSION['user_id'], 'access', 'property_summary', 'User accessed Property Summary page');

// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';

// Get system settings for print header
$system_settings = [];
if ($conn && !$conn->connect_error) {
    $result = $conn->query("SELECT * FROM system_settings LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $system_settings = $row;
    }
}// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';
$selected_tab = $_GET['tab'] ?? 'fixed';

// Get summary data
$office_summary = [];
$category_summary = [];
$total_items = 0;
$total_value = 0;

if ($conn && !$conn->connect_error) {
    try {
        // Base query conditions depending on tab
        $tab_condition = "";
        if ($selected_tab == 'semi') {
            $tab_condition = "(ai.ics_id IS NOT NULL AND ai.ics_id != '') OR (ai.ics_par_no IS NOT NULL AND ai.ics_par_no != '' AND ai.value < 50000)";
        } else {
            $tab_condition = "(ai.par_id IS NOT NULL AND ai.par_id != '') OR (ai.ics_par_no IS NOT NULL AND ai.ics_par_no != '' AND ai.value >= 50000)";
        }

        $base_query = "FROM asset_items ai
                       LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                       LEFT JOIN offices o1 ON ai.office_id = o1.id
                       LEFT JOIN employees e ON ai.employee_id = e.id
                       LEFT JOIN offices o2 ON e.office_id = o2.id
                       WHERE ($tab_condition)";
        
        // Add filters
        if (!empty($selected_category)) {
            $base_query .= " AND ac.category_name = '" . $conn->real_escape_string($selected_category) . "'";
        }
        if (!empty($selected_office)) {
            $base_query .= " AND (o1.office_name = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_name = '" . $conn->real_escape_string($selected_office) . "')";
        }
        
        // Get office summary
        $office_query = "SELECT 
                            COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                            COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code,
                            COUNT(ai.id) as item_count,
                            SUM(ai.value) as total_value
                          $base_query
                          GROUP BY COALESCE(o1.office_name, o2.office_name, 'Unassigned'), COALESCE(o1.office_code, o2.office_code, 'NONE')
                          ORDER BY total_value DESC";
        
        $result = $conn->query($office_query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $office_summary[] = $row;
                $total_items += $row['item_count'];
                $total_value += $row['total_value'];
            }
        }
        
        // Get category summary
        $category_query = "SELECT 
                              COALESCE(ac.category_code, 'UNCAT') as category_code,
                              COALESCE(ac.category_name, 'Uncategorized') as category_name,
                              COUNT(ai.id) as item_count,
                              SUM(ai.value) as total_value
                            $base_query
                            GROUP BY COALESCE(ac.category_code, 'UNCAT'), COALESCE(ac.category_name, 'Uncategorized')
                            ORDER BY total_value DESC";
        
        $result = $conn->query($category_query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $category_summary[] = $row;
            }
        }
        
    } catch (Exception $e) {
        error_log("Property Summary Query Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Summary - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <div class="print-header no-print" style="display: none;">
        <!-- Header content remains same for print -->
    </div>
    
    <?php $page_title = 'Property Summary'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2" style="font-weight: 700; color: var(--primary-color);">
                        <i class="bi bi-pie-chart-fill me-2"></i>Property Summary
                    </h1>
                    <p class="text-muted mb-0">Summary of asset values by office and category</p>
                </div>
                <div class="col-md-4 text-md-end no-print">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-lightning-fill me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px;">
                            <li><h6 class="dropdown-header small text-muted text-uppercase tracking-wider">Print Options</h6></li>
                            <li><button class="dropdown-item py-2" onclick="printSection('full')">
                                <i class="bi bi-file-earmark-pdf me-2 text-primary"></i> Print Full Report
                            </button></li>
                            <li><button class="dropdown-item py-2" onclick="printSection('offices')">
                                <i class="bi bi-building me-2 text-primary"></i> Summary by Office
                            </button></li>
                            <li><button class="dropdown-item py-2" onclick="printSection('categories')">
                                <i class="bi bi-tags me-2 text-primary"></i> Summary by Category
                            </button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="property_card.php">
                                <i class="bi bi-arrow-left-circle me-2"></i> Back to Property Card
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4 px-3" id="summaryTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'fixed') ? 'active' : ''; ?>" 
                   href="?tab=fixed<?php echo $selected_category ? '&category='.urlencode($selected_category) : ''; ?><?php echo $selected_office ? '&office='.urlencode($selected_office) : ''; ?>" 
                   style="font-weight: 600;">
                    <i class="bi bi-card-checklist me-2"></i>PPE
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'semi') ? 'active' : ''; ?>" 
                   href="?tab=semi<?php echo $selected_category ? '&category='.urlencode($selected_category) : ''; ?><?php echo $selected_office ? '&office='.urlencode($selected_office) : ''; ?>" 
                   style="font-weight: 600;">
                    <i class="bi bi-box-seam me-2"></i>Semi-Expandable
                </a>
            </li>
        </ul>

        <?php if (!empty($selected_category) || !empty($selected_office)): ?>
            <div class="alert alert-info d-flex align-items-center mb-4 mx-3">
                <i class="bi bi-filter-circle-fill me-2"></i>
                <div>
                    <strong>Active Filters:</strong> 
                    <?php if ($selected_category) echo '<span class="badge bg-primary ms-1">Category: '.$selected_category.'</span>'; ?>
                    <?php if ($selected_office) echo '<span class="badge bg-primary ms-1">Office: '.$selected_office.'</span>'; ?>
                    <a href="?" class="ms-2 text-decoration-none text-dark"><i class="bi bi-x-circle-fill"></i> Clear</a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Dashboard -->
        <div class="row mb-4 px-3">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #fff; border-left: 5px solid var(--primary-color) !important;">
                    <div class="card-body p-4 text-center">
                        <h3 class="display-5 fw-bold mb-0" style="color: var(--primary-color);"><?php echo number_format($total_items); ?></h3>
                        <p class="text-muted mb-0">Total Items in Report</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #fff; border-left: 5px solid var(--primary-color) !important;">
                    <div class="card-body p-4 text-center">
                        <h3 class="display-5 fw-bold mb-0" style="color: var(--primary-color);">₱<?php echo number_format($total_value, 2); ?></h3>
                        <p class="text-muted mb-0">Total Estimated Value</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row px-3">
            <!-- Table Container 1 -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="card-title fw-bold mb-0">
                            <i class="bi bi-building text-primary me-2"></i>Summary by Office
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Office Name</th>
                                        <th class="text-center">Count</th>
                                        <th class="text-end">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($office_summary as $office): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($office['office_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($office['office_code']); ?></small>
                                            </td>
                                            <td class="text-center"><?php echo number_format($office['item_count']); ?></td>
                                            <td class="text-end fw-bold">₱<?php echo number_format($office['total_value'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Table Container 2 -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="card-title fw-bold mb-0">
                            <i class="bi bi-tags text-primary me-2"></i>Summary by Category
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-center">Count</th>
                                        <th class="text-end">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_summary as $category): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($category['category_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($category['category_code']); ?></small>
                                            </td>
                                            <td class="text-center"><?php echo number_format($category['item_count']); ?></td>
                                            <td class="text-end fw-bold">₱<?php echo number_format($category['total_value'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($office_summary) && empty($category_summary)): ?>
            <div class="row px-3">
                <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="bi bi-database-exclamation text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">No summary data found</h5>
                    <p class="text-muted">Adjust filters or check if records exist.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        function printSection(section) {
            // Get current filter parameters from URL
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category') || '<?php echo $selected_category; ?>';
            const office = urlParams.get('office') || '<?php echo $selected_office; ?>';
            const tab = urlParams.get('tab') || '<?php echo $selected_tab; ?>';
            
            // Determine target file
            let url = (section === 'full') ? 'print_full_report.php' : 'print_property_summary.php';
            
            const params = new URLSearchParams();
            if (section !== 'full') {
                params.append('section', section);
            }
            params.append('tab', tab);
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            
            url += '?' + params.toString();
            
            // Open print window
            const printWindow = window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            
            if (!printWindow || printWindow.closed || typeof printWindow.closed === 'undefined') {
                alert('Popup blocked! Please allow popups for this site.');
                return;
            }
            
            try {
                printWindow.focus();
            } catch (e) {
                console.warn('Could not focus print window:', e);
            }
        }
    </script>
</body>
</html>
