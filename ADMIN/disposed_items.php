<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    $_SESSION['error'] = 'Access denied. You do not have permission to access this page.';
    header('Location: ../index.php');
    exit();
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = 'Session expired. Please login again.';
    header('Location: ../index.php');
    exit();
}

// Update last activity
$_SESSION['last_activity'] = time();

// Log page access
logSystemAction($_SESSION['user_id'], 'page_access', 'disposed_items', 'Accessed disposed items page');

// Get system settings for print header
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

// Get disposed items from both red_tags and asset_items
$disposed_items = [];
try {
    // Get disposed items from red_tags
    $red_tags_sql = "SELECT rt.*, ai.description as asset_description, ai.property_no, ai.inventory_tag, 
                            ai.value, ai.disposal_date as asset_disposal_date, ai.disposal_reason as asset_disposal_reason,
                            a.description as category_name, o.office_name,
                            CONCAT(u.first_name, ' ', u.last_name) as disposed_by_name
                     FROM red_tags rt
                     LEFT JOIN asset_items ai ON rt.asset_item_id = ai.id
                     LEFT JOIN assets a ON ai.asset_id = a.id
                     LEFT JOIN offices o ON rt.office_id = o.id
                     LEFT JOIN users u ON rt.updated_by = u.id
                     WHERE rt.action = 'disposed'
                     ORDER BY COALESCE(rt.disposal_date, ai.disposal_date) DESC, rt.updated_at DESC";
    
    $stmt = $conn->prepare($red_tags_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $disposed_items[] = $row;
    }
    $stmt->close();
    
    // Also get disposed items from asset_items that don't have red tags
    $asset_items_sql = "SELECT ai.*, 
                                a.description as category_name, o.office_name,
                                'System' as disposed_by_name,
                                'Direct Disposal' as control_no,
                                CONCAT('DISPOSAL-', ai.id) as red_tag_no,
                                ai.disposal_date,
                                ai.disposal_reason
                         FROM asset_items ai
                         LEFT JOIN assets a ON ai.asset_id = a.id
                         LEFT JOIN offices o ON ai.office_id = o.id
                         WHERE ai.status = 'disposed' 
                         AND ai.id NOT IN (SELECT DISTINCT asset_item_id FROM red_tags WHERE action = 'disposed' AND asset_item_id IS NOT NULL)
                         ORDER BY ai.disposal_date DESC, ai.last_updated DESC";
    
    $stmt = $conn->prepare($asset_items_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Map the fields to match red_tags structure
        $row['item_description'] = $row['description'];
        $row['tagged_by'] = $row['disposed_by_name'] ?? 'System';
        $row['item_location'] = $row['office_name'] ?? 'N/A';
        $row['removal_reason'] = $row['disposal_reason'] ?? 'N/A';
        $disposed_items[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching disposed items: " . $e->getMessage());
}

// Get statistics
$stats = [
    'total_disposed' => 0,
    'total_value' => 0,
    'this_month' => 0,
    'this_year' => 0
];

try {
    // Total disposed items (from both red_tags and asset_items)
    $count_sql = "(SELECT COUNT(*) as count FROM red_tags WHERE action = 'disposed') 
                  UNION 
                  (SELECT COUNT(*) as count FROM asset_items WHERE status = 'disposed' 
                   AND id NOT IN (SELECT DISTINCT asset_item_id FROM red_tags WHERE action = 'disposed' AND asset_item_id IS NOT NULL))";
    $result = $conn->query($count_sql);
    $stats['total_disposed'] = 0;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['total_disposed'] += $row['count'];
        }
    }
    
    // Total value of disposed items (from both sources)
    $value_sql = "(SELECT COALESCE(SUM(ai.value), 0) as total_value 
                  FROM red_tags rt 
                  LEFT JOIN asset_items ai ON rt.asset_item_id = ai.id 
                  WHERE rt.action = 'disposed')
                  UNION
                  (SELECT COALESCE(SUM(value), 0) as total_value 
                  FROM asset_items 
                  WHERE status = 'disposed' 
                  AND id NOT IN (SELECT DISTINCT asset_item_id FROM red_tags WHERE action = 'disposed' AND asset_item_id IS NOT NULL))";
    $result = $conn->query($value_sql);
    $stats['total_value'] = 0;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['total_value'] += $row['total_value'];
        }
    }
    
    // This month (from both sources)
    $month_sql = "(SELECT COUNT(*) as count FROM red_tags 
                  WHERE action = 'disposed' 
                  AND MONTH(disposal_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(disposal_date) = YEAR(CURRENT_DATE()))
                  UNION
                  (SELECT COUNT(*) as count FROM asset_items 
                  WHERE status = 'disposed' 
                  AND MONTH(disposal_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(disposal_date) = YEAR(CURRENT_DATE())
                  AND id NOT IN (SELECT DISTINCT asset_item_id FROM red_tags WHERE action = 'disposed' AND asset_item_id IS NOT NULL))";
    $result = $conn->query($month_sql);
    $stats['this_month'] = 0;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['this_month'] += $row['count'];
        }
    }
    
    // This year (from both sources)
    $year_sql = "(SELECT COUNT(*) as count FROM red_tags 
                 WHERE action = 'disposed' 
                 AND YEAR(disposal_date) = YEAR(CURRENT_DATE()))
                 UNION
                 (SELECT COUNT(*) as count FROM asset_items 
                 WHERE status = 'disposed' 
                 AND YEAR(disposal_date) = YEAR(CURRENT_DATE())
                 AND id NOT IN (SELECT DISTINCT asset_item_id FROM red_tags WHERE action = 'disposed' AND asset_item_id IS NOT NULL))";
    $result = $conn->query($year_sql);
    $stats['this_year'] = 0;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['this_year'] += $row['count'];
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching disposed items stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disposed Items - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            border-left: 4px solid var(--danger-color);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #dc3545 0%, #f8d7da 100%);
            color: #721c24;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .warning-badge {
            background: #dc3545;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .text-value {
            font-weight: 600;
            color: #dc3545;
        }
        
        .alert-warning {
            border-left: 4px solid #ffc107;
        }
        
        .office-filter-wrapper {
            display: inline-flex;
            align-items: center;
        }
        
        .office-filter-wrapper label {
            margin-bottom: 0;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Disposed Items';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-trash3"></i> Disposed Items
                    </h1>
                    <p class="text-muted mb-0">View and manage all disposed items in the system</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" class="btn btn-primary btn-custom" onclick="exportDisposedItems()">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <button type="button" class="btn btn-success btn-custom" onclick="printDisposedItems()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Print Header (hidden by default, visible only when printing) -->
        <div class="print-header" style="display: none;">
            <div style="display: flex; align-items: flex-start; gap: 20px;">
                <!-- Logo on the left -->
                <div style="flex-shrink: 0;">
                    <?php 
                    $logo_path = !empty($system_settings['system_logo']) ? '../' . htmlspecialchars($system_settings['system_logo']) : '../img/trans_logo.png';
                    $system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');
                    ?>
                    <img src="<?php echo $logo_path; ?>" alt="<?php echo $system_name; ?>" style="max-width: 250px; max-height: 100px;">
                </div>
                
                <!-- Government header on the right -->
                <div style="flex: 1;">
                    <div class="gov-header" style="text-align: center; padding: 0;">
                        <div class="gov-title">Republic of the Philippines</div>
                        <div class="municipality">Municipality of Pilar</div>
                        <div class="province">Province of Sorsogon</div>
                        <div class="print-title"><?php echo $system_name; ?> - Disposed Items Report</div>
                        <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-6 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_disposed']); ?></div>
                    <div class="stats-label"><i class="bi bi-trash3"></i> Total Disposed</div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="stats-card">
                    <div class="stats-number">₱<?php echo number_format($stats['total_value'], 2); ?></div>
                    <div class="stats-label"><i class="bi bi-currency-dollar"></i> Total Value</div>
                </div>
            </div>
        </div>
        
        <!-- Warning Alert -->
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Attention:</strong> The following items have been disposed and are no longer active in the inventory system.
        </div>
        
        <!-- Disposed Items Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Disposed Items</h5>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="office-filter-wrapper">
                                <label for="officeFilter" class="form-label me-2">Office:</label>
                                <select class="form-select form-select-sm" id="officeFilter" style="width: 200px;">
                                    <option value="">All Offices</option>
                                    <?php 
                                    // Get unique offices from disposed items for JavaScript
                                    $offices = [];
                                    foreach ($disposed_items as $item) {
                                        if (!empty($item['office_name']) && !in_array($item['office_name'], $offices)) {
                                            $offices[] = $item['office_name'];
                                        }
                                    }
                                    sort($offices);
                                    foreach ($offices as $office): ?>
                                        <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search disposed items...">
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($disposed_items)): ?>
                <div class="empty-state">
                    <i class="bi bi-trash3"></i>
                    <h4>No Disposed Items</h4>
                    <p class="text-muted">No items have been disposed yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="disposedItemsTable">
                        <thead>
                            <tr>
                                <th>Control No.</th>
                                <th>Item Description</th>
                                <th>Property No.</th>
                                <th>Value</th>
                                <th>Office</th>
                                <th>Disposal Date</th>
                                <th>Disposal Reason</th>
                                <th>Disposed By</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disposed_items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['control_no']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['red_tag_no']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($item['item_description']); ?>
                                        <?php if (!empty($item['category_name'])): ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['property_no'] ?? 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($item['value'] ?? 0, 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                        $disposal_date = $item['disposal_date'] ?? $item['asset_disposal_date'] ?? 'N/A';
                                        if ($disposal_date !== 'N/A') {
                                            echo date('M d, Y', strtotime($disposal_date));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $disposal_reason = $item['disposal_reason'] ?? $item['asset_disposal_reason'] ?? 'N/A';
                                        ?>
                                        <div class="disposal-reason" title="<?php echo htmlspecialchars($disposal_reason); ?>">
                                            <?php echo htmlspecialchars($disposal_reason); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['disposed_by_name'] ?? 'N/A'); ?></td>
                                    <td class="no-print">
                                        <div class="btn-group" role="group">
                                            <?php 
                                            $asset_item_id = $item['asset_item_id'] ?? $item['id'] ?? null;
                                            if ($asset_item_id): ?>
                                                <a href="view_asset_item.php?id=<?php echo $asset_item_id; ?>" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   title="View Asset Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
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
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            var table = $('#disposedItemsTable').DataTable({
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 text-end"p>>' + // Length and Pagination
                     '<"row"<"col-sm-12"tr>>' + // Table
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>', // Info and Pagination
                pageLength: 25,
                responsive: true,
                order: [[5, 'desc']], // Sort by disposal date by default
                searching: false, // Disable built-in search
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ disposed items",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                columnDefs: [
                    { targets: -1, orderable: false }, // Disable sorting on Actions column
                    { targets: 'no-print', searchable: false } // Hide actions column from search
                ]
            });
            
            // Custom search functionality
            $('#searchInput').on('keyup', function() {
                table.search($(this).val()).draw();
            });
            
            // Office filter functionality
            $('#officeFilter').on('change', function() {
                var office = $(this).val();
                table.column(4).search(office).draw(); // Office column is index 4 (0-based)
            });
        });
        
        // Export disposed items
        function exportDisposedItems() {
            const table = document.getElementById('disposedItemsTable');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            let csv = [];
            const headers = [];
            const rows = table.querySelectorAll('tr');
            
            // Get headers (exclude action column)
            rows[0].querySelectorAll('th').forEach((th, index) => {
                if (!th.classList.contains('no-print')) {
                    headers.push(th.textContent.trim());
                }
            });
            csv.push(headers.join(','));
            
            // Get data rows
            for (let i = 1; i < rows.length; i++) {
                const row = [];
                const cells = rows[i].querySelectorAll('td');
                
                cells.forEach((cell, index) => {
                    if (!cell.classList.contains('no-print')) {
                        let text = cell.textContent.trim();
                        // Remove extra whitespace and newlines
                        text = text.replace(/\s+/g, ' ');
                        // Escape quotes
                        text = text.replace(/"/g, '""');
                        row.push(`"${text}"`);
                    }
                });
                
                csv.push(row.join(','));
            }
            
            // Download CSV
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'disposed_items_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Print disposed items
        function printDisposedItems() {
            // Get current filters
            var officeFilter = $('#officeFilter').val();
            var searchValue = $('#searchInput').val();
            
            // Build URL with filters
            var url = 'print_disposed.php';
            var params = [];
            
            if (officeFilter) {
                params.push('office=' + encodeURIComponent(officeFilter));
            }
            
            if (searchValue) {
                params.push('search=' + encodeURIComponent(searchValue));
            }
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            // Open print window
            window.open(url, '_blank');
        }
    </script>
    
    <style>
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
                font-size: 10px;
            }
            
            .page-header, .no-print {
                display: none !important;
            }
            
            .table-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .table {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .table th {
                background: #f0f0f0 !important;
                color: #000 !important;
                border: 1px solid #000;
            }
            
            .table td {
                border: 1px solid #000;
            }
            
            .stats-card {
                border: 1px solid #000;
                background: white !important;
                color: black !important;
                margin-bottom: 10px;
            }
            
            .print-header {
                display: block !important;
                padding: 10px;
                margin-bottom: 20px;
            }
            
            .gov-header {
                text-align: center;
                margin-bottom: 20px;
                padding: 15px;
                background: #f8f9fa;
            }
            
            .gov-title {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 5px;
                color: #333;
            }
            
            .municipality {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 3px;
                color: #000;
            }
            
            .province {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #000;
            }
            
            .print-title {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 5px;
                color: #191BA9;
            }
            
            .print-subtitle {
                font-size: 12px;
                color: #666;
            }
            
            /* Hide browser print headers and footers */
            @page {
                size: legal landscape;
                margin: 0.5in;
            }
            
            html {
                overflow: hidden;
            }
        }
    </style>
</body>
</html>
