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
}

// Get summary data
$office_summary = [];
$category_summary = [];
$total_items = 0;
$total_value = 0;

if ($conn && !$conn->connect_error) {
    try {
        // Base query
        $base_query = "FROM asset_items ai
                       LEFT JOIN asset_categories ac ON ai.category_id = ac.id
                       LEFT JOIN offices o1 ON ai.office_id = o1.id
                       LEFT JOIN employees e ON ai.employee_id = e.id
                       LEFT JOIN offices o2 ON e.office_id = o2.id
                       LEFT JOIN par_forms pf ON ai.par_id = pf.id
                       WHERE ai.par_id IS NOT NULL AND ai.par_id != ''";
        
        // Add filters
        $where_conditions = [];
        if (!empty($selected_category)) {
            $where_conditions[] = "ac.category_code = '" . $conn->real_escape_string($selected_category) . "'";
        }
        if (!empty($selected_office)) {
            $where_conditions[] = "(o1.office_code = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_code = '" . $conn->real_escape_string($selected_office) . "')";
        }
        
        if (!empty($where_conditions)) {
            $base_query .= " AND " . implode(" AND ", $where_conditions);
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
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow: 0 2px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border-left: 4px solid #191BA9;
        }
        
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .summary-table {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table-custom thead th {
            background: transparent;
            color: #212529;
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-custom thead th:first-child {
            border-top-left-radius: 12px;
        }
        
        .table-custom thead th:last-child {
            border-top-right-radius: 12px;
        }
        
        .table-custom tbody td {
            padding: 0.875rem 0.75rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .table-custom tbody tr:hover {
            background: rgba(25, 27, 169, 0.02);
        }
        
        .office-code {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            font-family: 'Courier New', monospace;
            display: inline-block;
        }
        
        .category-badge {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }
        
        .value-cell {
            font-weight: 600;
            color: #212529;
            text-align: right;
        }
        
        .count-cell {
            font-weight: 500;
            color: #6c757d;
            text-align: center;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .filter-info {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .table-custom {
                font-size: 0.8rem;
            }
            
            .table-custom thead th,
            .table-custom tbody td {
                padding: 0.5rem 0.25rem;
            }
        }
        
        @media print {
            @page {
                size: landscape;
                margin: 0.5in;
            }
            
            body {
                background: white;
                font-family: 'Times New Roman', serif;
                font-size: 12px;
                line-height: 1.4;
            }
            
            .page-header,
            .summary-card,
            .summary-table {
                box-shadow: none;
                border: none;
                margin-bottom: 20px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-header {
                margin-bottom: 20px;
            }
            
            .gov-header {
                text-align: center;
                margin-bottom: 15px;
                padding: 10px;
                background: #f8f9fa;
            }
            
            .gov-title {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 8px;
                color: #333;
                line-height: 1.2;
            }
            
            .municipality {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 4px;
                color: #000;
            }
            
            .province {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 15px;
                color: #000;
            }
            
            .print-title {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 4px;
                color: #191BA9;
            }
            
            .print-subtitle {
                font-size: 11px;
                color: #666;
                margin-bottom: 15px;
            }
            
            .filters-info {
                font-size: 10px;
                color: #666;
                margin-bottom: 15px;
                padding: 8px;
                background: #f9f9f9;
                border: 1px solid #ddd;
            }
            
            .report-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }
            
            .report-table th,
            .report-table td {
                border: 1px solid #000;
                padding: 6px;
                text-align: left;
                vertical-align: top;
            }
            
            .report-table th {
                background: #f0f0f0;
                font-weight: bold;
                font-size: 10px;
                text-transform: uppercase;
            }
            
            .report-table td {
                font-size: 10px;
            }
            
            .text-center {
                text-align: center;
            }
            
            .text-right {
                text-align: right;
            }
            
            .summary-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .stat-box {
                border: 2px solid #191BA9;
                padding: 10px;
                text-align: center;
                min-width: 120px;
                background: #f8f9fa;
            }
            
            .stat-number {
                font-size: 20px;
                font-weight: bold;
                color: #191BA9;
                margin-bottom: 4px;
            }
            
            .stat-label {
                font-size: 10px;
                color: #666;
                text-transform: uppercase;
            }
            
            .summary-table h3 {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #191BA9;
                text-align: center;
            }
            
            .table-custom {
                width: 100%;
                border-collapse: collapse;
            }
            
            .table-custom th,
            .table-custom td {
                border: 1px solid #000;
                padding: 6px;
                text-align: left;
                font-size: 10px;
            }
            
            .table-custom th {
                background: #f0f0f0;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .table-custom tr:last-child td {
                border-top: 2px solid #000;
                font-weight: bold;
                background: #f8f9fa;
            }
        }
    </style>
</head>
<body>
    <!-- Print Header (only visible when printing) -->
    <div class="print-header" style="display: none;">
        <div style="display: flex; align-items: flex-start; gap: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <?php 
                if (!empty($system_settings['system_logo'])) {
                    echo '<img src="../' . htmlspecialchars($system_settings['system_logo']) . '" alt="' . htmlspecialchars($system_settings['system_name'] ?? 'PIMS') . '" style="max-width: 250px; max-height: 100px;">';
                } else {
                    echo '<img src="../img/system_logo.png" alt="' . htmlspecialchars($system_settings['system_name'] ?? 'PIMS') . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Government header on the right -->
            <div style="flex: 1;">
                <div class="gov-header" style="text-align: center; padding: 0;">
                    <div class="gov-title">Republic of the Philippines</div>
                    <div class="municipality">Municipality of Pilar</div>
                    <div class="province">Province of Sorsogon</div>
                    <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?> - Property Summary Report</div>
                    <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
                </div>
            </div>
        </div>
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
                    <h1 class="mb-2" style="font-weight: 700; color: #191BA9;">
                        <i class="bi bi-list-ul me-2"></i>Property Summary
                    </h1>
                    <p class="text-muted mb-0">Summary of asset values by office and category</p>
                </div>
                <div class="col-md-4 text-md-end no-print">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="printSection('offices')">
                                <i class="bi bi-building me-2"></i>Print Offices Only
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="printSection('categories')">
                                <i class="bi bi-tags me-2"></i>Print Categories Only
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="printSection('all')">
                                <i class="bi bi-file-earmark-text me-2"></i>Print All
                            </a></li>
                        </ul>
                    </div>
                    <a href="property_card.php" class="btn btn-primary ms-2">
                        <i class="bi bi-arrow-left me-1"></i> Back to Property Card
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (!empty($selected_category) || !empty($selected_office)): ?>
            <div class="filter-info">
                <strong>Active Filters:</strong>
                <?php if (!empty($selected_category)): ?>
                    Category: <?php echo htmlspecialchars($selected_category); ?>
                <?php endif; ?>
                <?php if (!empty($selected_office)): ?>
                    <?php if (!empty($selected_category)) echo ' | '; ?>
                    Office: <?php echo htmlspecialchars($selected_office); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Overall Statistics -->
        <div class="summary-card">
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($total_items); ?></div>
                        <div class="stat-label">Total Items</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-value">₱<?php echo number_format($total_value, 2); ?></div>
                        <div class="stat-label">Total Value</div>
                    </div>
                </div>
              
            </div>
        </div>
        
        <!-- Office Summary -->
        <div class="summary-table" id="officeSummary">
            <h3 class="mb-3" style="color: #191BA9; font-weight: 600;">
                <i class="bi bi-building me-2"></i>Summary by Office
            </h3>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Office Name</th>
                            <th>Office Code</th>
                            <th>Number of Items</th>
                            <th>Total Value</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($office_summary as $office): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($office['office_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="office-code"><?php echo htmlspecialchars($office['office_code']); ?></span>
                                </td>
                                <td class="count-cell">
                                    <?php echo number_format($office['item_count']); ?>
                                </td>
                                <td class="value-cell">
                                    ₱<?php echo number_format($office['total_value'], 2); ?>
                                </td>
                                <td class="count-cell">
                                    <?php echo number_format($total_value > 0 ? ($office['total_value'] / $total_value) * 100 : 0, 1); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!empty($office_summary)): ?>
                            <tr style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; font-weight: 600;">
                                <td colspan="2">
                                    <strong>Grand Total</strong>
                                </td>
                                <td class="count-cell" style="font-weight: 700;">
                                    <?php echo number_format($total_items); ?>
                                </td>
                                <td class="value-cell" style="font-weight: 700;">
                                    ₱<?php echo number_format($total_value, 2); ?>
                                </td>
                                <td class="count-cell" style="font-weight: 700;">
                                    100.0%
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Category Summary -->
        <div class="summary-table" id="categorySummary">
            <h3 class="mb-3" style="color: #191BA9; font-weight: 600;">
                <i class="bi bi-tags me-2"></i>Summary by Category
            </h3>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Category Code</th>
                            <th>Number of Items</th>
                            <th>Total Value</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_summary as $category): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="category-badge"><?php echo htmlspecialchars($category['category_code']); ?></span>
                                </td>
                                <td class="count-cell">
                                    <?php echo number_format($category['item_count']); ?>
                                </td>
                                <td class="value-cell">
                                    ₱<?php echo number_format($category['total_value'], 2); ?>
                                </td>
                                <td class="count-cell">
                                    <?php echo number_format($total_value > 0 ? ($category['total_value'] / $total_value) * 100 : 0, 1); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!empty($category_summary)): ?>
                            <tr style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; font-weight: 600;">
                                <td colspan="2">
                                    <strong>Grand Total</strong>
                                </td>
                                <td class="count-cell" style="font-weight: 700;">
                                    <?php echo number_format($total_items); ?>
                                </td>
                                <td class="value-cell" style="font-weight: 700;">
                                    ₱<?php echo number_format($total_value, 2); ?>
                                </td>
                                <td class="count-cell" style="font-weight: 700;">
                                    100.0%
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if (empty($office_summary) && empty($category_summary)): ?>
            <div class="summary-table">
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #adb5bd;"></i>
                    <h4 class="mt-3 text-muted">No Data Found</h4>
                    <p class="text-muted">No property items found matching the current filters.</p>
                    <a href="property_card.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Property Card
                    </a>
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
            // Get current filter parameters
            const category = document.getElementById('categoryFilter') ? document.getElementById('categoryFilter').value : '';
            const office = document.getElementById('officeFilter') ? document.getElementById('officeFilter').value : '';
            
            // Build URL with filter parameters
            let url = 'print_property_summary.php';
            const params = new URLSearchParams();
            
            params.append('section', section);
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            // Open print window
            const printWindow = window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            
            if (!printWindow || printWindow.closed || typeof printWindow.closed === 'undefined') {
                // Popup blocked - show user message and provide alternative
                alert('Popup blocked! Please allow popups for this site or use the browser print function instead.\n\nTip: Click Ctrl+P or Cmd+P to print directly.');
                return;
            }
            
            // Focus on the print window
            try {
                printWindow.focus();
            } catch (e) {
                console.warn('Could not focus print window:', e);
            }
        }
    </script>
</body>
</html>
