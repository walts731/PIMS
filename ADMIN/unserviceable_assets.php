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

logSystemAction($_SESSION['user_id'], 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php');

// Handle search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';

// Build query to include unserviceable assets
$where_conditions = ["ai.status = 'unserviceable'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(ai.description LIKE ? OR ai.inventory_tag LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($office_filter)) {
    $where_conditions[] = "ai.office_id = ?";
    $params[] = $office_filter;
    $types .= 'i';
}

if (!empty($category_filter)) {
    $where_conditions[] = "ai.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get unserviceable assets
$unserviceable_assets = [];
$sql = "SELECT ai.*, ac.category_name, ac.id as category_id,
               ac.category_code, o.office_name, o.id as office_id, e.firstname, e.lastname, e.position
        FROM asset_items ai 
        LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id 
        LEFT JOIN offices o ON ai.office_id = o.id 
        LEFT JOIN employees e ON ai.employee_id = e.id 
        $where_clause 
        ORDER BY ai.last_updated DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unserviceable_assets[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $unserviceable_assets[] = $row;
        }
    }
}

// Get statistics
$total_unserviceable = count($unserviceable_assets);
$total_value = array_sum(array_column($unserviceable_assets, 'value'));

// Get offices for filter
$offices = [];
$offices_result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
if ($offices_result) {
    while ($office = $offices_result->fetch_assoc()) {
        $offices[] = $office;
    }
}

// Get categories for filter
$categories = [];
$categories_result = $conn->query("SELECT id, category_name, category_code FROM asset_categories WHERE status = 'active' ORDER BY category_name");
if ($categories_result) {
    while ($category = $categories_result->fetch_assoc()) {
        $categories[] = $category;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unserviceable Assets - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Unserviceable Assets';
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
                        <i class="bi bi-x-circle"></i> Unserviceable Assets
                    </h1>
                    <p class="text-muted mb-0">Manage and track unserviceable and disposed assets</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="no-print">
                        <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button type="button" class="dropdown-item" onclick="exportToCSV()">
                                    <i class="bi bi-download"></i> Export to CSV
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item" onclick="openPrintPreview()">
                                    <i class="bi bi-printer"></i> Print List
                                </button>
                            </li>
                            <li>
                                <a href="assets.php" class="dropdown-item">
                                    <i class="bi bi-box"></i> View All Assets
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button type="button" class="dropdown-item" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </button>
                            </li>
                        </ul>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Total Unserviceable</h6>
                            <div class="stats-number"><?php echo $total_unserviceable; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Total Value</h6>
                            <div class="stats-number">₱<?php echo number_format($total_value, 2); ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-currency-peso" style="font-size: 2rem; color: #28a745;"></i>
                        </div>
                    </div>
                </div>
            </div>
           
        </div>

        
        <!-- Assets Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover" id="unserviceableAssetsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>Category</th>
                            <th style="display: none;">Category ID</th>
                            <th>Status</th>
                            <th>Value</th>
                            <th>Office</th>
                            <th style="display: none;">Office ID</th>
                            <th>Assigned To</th>
                            <th>Last Updated</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($unserviceable_assets)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2">No unserviceable assets found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($unserviceable_assets as $asset): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($asset['description']); ?></strong>
                                        <?php if (!empty($asset['inventory_tag'])): ?>
                                            <br><small class="text-muted">Tag: <?php echo htmlspecialchars($asset['inventory_tag']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Check if category exists, if not show a message
                                        if (!empty($asset['category_name'])) {
                                            echo htmlspecialchars($asset['category_name']);
                                        } else {
                                            echo '<span class="text-muted">No Category Assigned</span>';
                                        }
                                        ?>
                                    </td>
                                    <td style="display: none;"><?php echo $asset['category_id'] ?? ''; ?></td>
                                    <td>
                                        <?php
                                        // Show unserviceable status
                                        if ($asset['status'] === 'unserviceable') {
                                            echo '<span class="status-badge status-unserviceable">Unserviceable</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-value">₱<?php echo number_format($asset['value'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($asset['office_name'] ?? 'N/A'); ?></td>
                                    <td style="display: none;"><?php echo $asset['office_id'] ?? ''; ?></td>
                                    <td>
                                        <?php if (!empty($asset['firstname'])): ?>
                                            <?php echo htmlspecialchars($asset['firstname'] . ' ' . $asset['lastname']); ?>
                                        <?php elseif (!empty($asset['end_user'])): ?>
                                            <?php echo htmlspecialchars($asset['end_user']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($asset['last_updated'])); ?></td>
                                    <td class="no-print">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_asset_item.php?id=<?php echo $asset['id']; ?>" class="btn btn-outline-primary btn-action" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="create_redtag.php?asset_id=<?php echo $asset['id']; ?>&description=<?php echo urlencode($asset['description']); ?>&inventory_tag=<?php echo urlencode($asset['inventory_tag'] ?? ''); ?>&acquisition_date=<?php echo $asset['acquisition_date']; ?>&value=<?php echo $asset['value']; ?>&office_name=<?php echo urlencode($asset['office_name'] ?? ''); ?>&component_type=main_asset&component_description=<?php echo urlencode($asset['description']); ?>&component_value=<?php echo $asset['value']; ?>" class="btn btn-outline-danger btn-action" title="Create Red Tag">
                                                <i class="bi bi-exclamation-triangle"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    
    <script>
        function exportToCSV() {
            let csv = 'Description,Category,Status,Value,Office,Assigned To,Last Updated\n';
            
            <?php foreach ($unserviceable_assets as $asset): ?>
            csv += '<?php echo addslashes($asset['description']); ?>,';
            csv += '<?php echo addslashes($asset['category_name'] ?? 'N/A'); ?>,';
            csv += 'Unserviceable,';
            csv += '<?php echo $asset['value']; ?>,';
            csv += '<?php echo addslashes($asset['office_name'] ?? 'N/A'); ?>,';
            csv += '<?php echo addslashes(!empty($asset['firstname']) ? $asset['firstname'] . ' ' . $asset['lastname'] : ($asset['end_user'] ?? 'Unassigned')); ?>,';
            csv += '<?php echo date('Y-m-d', strtotime($asset['last_updated'])); ?>\n';
            <?php endforeach; ?>
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'unserviceable_assets_<?php echo date('Y-m-d'); ?>.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        function markAsServiceable(assetId) {
            if (confirm('Are you sure you want to mark this asset as serviceable?')) {
                fetch('process_asset_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'asset_id=' + assetId + '&status=available'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the asset status.');
                });
            }
        }
        
        // Print preview functionality
        function openPrintPreview() {
            // Get current filter values
            const officeValue = document.getElementById('officeFilter')?.value || '';
            const categoryValue = document.getElementById('categoryFilter')?.value || '';
            
            // Build URL for print preview
            const params = new URLSearchParams();
            if (officeValue) params.append('office', officeValue);
            if (categoryValue) params.append('category', categoryValue);
            params.append('print_preview', 'true');
            
            const printUrl = 'unserviceable_assets_print.php?' + params.toString();
            
            // Open print preview in new window
            const printWindow = window.open(printUrl, 'printPreview', 'width=800,height=600,scrollbars=yes,resizable=yes');
            
            if (printWindow) {
                printWindow.focus();
            } else {
                // Fallback if popup is blocked
                window.location.href = printUrl;
            }
        }
        
        // Initialize DataTables
        $(document).ready(function() {
            // Only initialize DataTables if there are assets to display
            var tableRows = $('#unserviceableAssetsTable tbody tr').length;
            var hasData = tableRows > 1 || (tableRows === 1 && !$('#unserviceableAssetsTable tbody tr td[colspan]').length);
            
            if (hasData) {
                var table = $('#unserviceableAssetsTable').DataTable({
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    order: [[6, 'desc']], // Sort by Last Updated column by default
                    language: {
                        search: "Search assets:",
                        lengthMenu: "Show _MENU_ assets per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ unserviceable assets",
                        infoEmpty: "No unserviceable assets found",
                        infoFiltered: "(filtered from _MAX_ total assets)",
                        zeroRecords: "No unserviceable assets found",
                        emptyTable: "No unserviceable assets found",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    columnDefs: [
                        { 
                            targets: [9], // Actions column
                            orderable: false,
                            searchable: false
                        },
                        {
                            targets: [2], // Hidden Category ID column
                            visible: false,
                            searchable: true
                        },
                        {
                            targets: [6], // Hidden Office ID column
                            visible: false,
                            searchable: true
                        },
                        {
                            targets: [3], // Status column
                            orderable: true,
                            searchable: true
                        }
                    ],
                    dom: '<"row"<"col-md-3"l><"col-md-3 office-filter-container"><"col-md-3 category-filter-container"><"col-md-3"f>>' +
                         '<"row"<"col-12"tr>>' +
                         '<"row"<"col-md-6"i><"col-md-6"p>>',
                    initComplete: function() {
                        // Apply custom styling to DataTables elements
                        $('.dataTables_wrapper').addClass('mt-3');
                        $('.dataTables_filter input').addClass('form-control form-control-sm');
                        $('.dataTables_length select').addClass('form-select form-select-sm');
                        
                        // Add office filter to DataTables
                        $('.office-filter-container').html(`
                            <select id="officeFilter" class="form-select form-select-sm">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        `);
                        
                        // Add category filter to DataTables
                        $('.category-filter-container').html(`
                            <select id="categoryFilter" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                        <?php if (!empty($category['category_code'])): ?>
                                            (<?php echo htmlspecialchars($category['category_code']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        `);
                        
                        // Office and category filter functionality
                        $('#officeFilter, #categoryFilter').on('change', function() {
                            var officeId = $('#officeFilter').val();
                            var categoryId = $('#categoryFilter').val();
                            
                            // Clear all filters first
                            table.column(6).search('').draw(); // Office ID column
                            table.column(2).search('').draw(); // Category ID column
                            
                            // Apply office filter
                            if (officeId) {
                                table.column(6).search(officeId).draw();
                            }
                            
                            // Apply category filter
                            if (categoryId) {
                                table.column(2).search(categoryId).draw();
                            }
                            
                            // If both filters are cleared, redraw table
                            if (!officeId && !categoryId) {
                                table.draw();
                            }
                        });
                        
                        // Apply initial filters if set
                        <?php if ($office_filter > 0): ?>
                            table.column(6).search('<?php echo $office_filter; ?>').draw();
                        <?php endif; ?>
                        
                        <?php if ($category_filter > 0): ?>
                            table.column(2).search('<?php echo $category_filter; ?>').draw();
                        <?php endif; ?>
                    }
                });
            }
        });
    </script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
