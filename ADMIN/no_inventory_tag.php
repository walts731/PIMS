<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';
require_once '../includes/asset_specific_manager.php';

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

// Log no inventory tag page access
logSystemAction($_SESSION['user_id'], 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page');

// Initialize asset specific manager
$assetManager = new AssetSpecificManager($conn);

// Handle filter parameters
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;

// Get asset items without inventory tags
$asset_items = [];
try {
    $sql = "SELECT ai.*, a.description as asset_description, ac.category_name, ac.category_code, o.office_name
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Filter by asset items that might need inventory tags (you can customize this logic)
    $sql .= " AND (ai.description LIKE '%no tag%' OR ai.description LIKE '%untagged%' OR ai.status = 'no_tag')";
    
    if ($office_filter > 0) {
        $sql .= " AND ai.office_id = ?";
        $params[] = $office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $sql .= " AND (ai.description LIKE ? OR ac.category_name LIKE ? OR o.office_name LIKE ? OR a.description LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ssss';
    }
    
    $sql .= " ORDER BY ai.last_updated DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $asset_items[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    $message = "Error fetching asset items: " . $e->getMessage();
    $message_type = "danger";
}

// Get offices for dropdown
$offices = [];
try {
    $result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching offices: " . $e->getMessage());
}

// Get statistics
$stats = [];
try {
    $sql = "SELECT 
                COUNT(*) as total_untagged,
                SUM(value) as total_value
            FROM asset_items 
            WHERE description LIKE '%no tag%' OR description LIKE '%untagged%' OR status = 'no_tag'";
    $result = $conn->query($sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Inventory Tag - PIMS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS removed -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        .search-box .bi-search {
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 1;
        }
        .search-box .form-control {
            padding-left: 2.5rem !important;
        }
    </style>
</head>
<body>
    <?php $page_title = 'No Inventory Tag'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-exclamation-triangle"></i> No Inventory Tag
                    </h1>
                    <p class="text-muted mb-0">Assets that require inventory tagging</p>
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="create_tag.php" class="dropdown-item">
                                    <i class="bi bi-tag"></i> Create Tag
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportUntagged()">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics and Filters Section -->
        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-speedometer2"></i> Overview & Filters
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_untagged'] ?? 0; ?></div>
                        <div class="stats-label"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Untagged Assets</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                        <div class="stats-label"><i class="bi bi-cash-stack text-success"></i> Total Value</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="search-box position-relative">
                        <i class="bi bi-search position-absolute"></i>
                        <input type="text" name="search" id="searchInput" class="form-control ps-4" placeholder="Search assets..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="office" id="officeFilter" class="form-select">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Attention:</strong> The following assets may require inventory tagging for proper tracking and management.
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="untaggedTable">
                <thead>
                    <tr>
                        <th>Asset Description</th>
                        <th>Status</th>
                        <th>Value</th>
                        <th>Office</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($asset_items)): ?>
                        <?php foreach ($asset_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['asset_description']); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($item['status']) {
                                        case 'available':
                                            $status_class = 'bg-success';
                                            $status_icon = 'bi-check-circle';
                                            break;
                                        case 'in_use':
                                            $status_class = 'bg-primary';
                                            $status_icon = 'bi-person';
                                            break;
                                        case 'maintenance':
                                            $status_class = 'bg-warning';
                                            $status_icon = 'bi-tools';
                                            break;
                                        case 'disposed':
                                            $status_class = 'bg-danger';
                                            $status_icon = 'bi-trash';
                                            break;
                                        case 'no_tag':
                                            $status_class = 'bg-danger';
                                            $status_icon = 'bi-exclamation-triangle';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                            $status_icon = 'bi-question-circle';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="bi <?php echo $status_icon; ?>"></i> <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                    </span>
                                </td>
                                <td data-order="<?php echo $item['value']; ?>"><?php echo number_format($item['value'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                                <td data-order="<?php echo strtotime($item['last_updated']); ?>"><small><?php echo date('M j, Y', strtotime($item['last_updated'])); ?></small></td>
                                <td>
                                    <a href="create_tag.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-warning btn-action" title="Create Tag">
                                        <i class="bi bi-tag"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle fs-1"></i>
                                <p class="mt-2">No asset items requiring inventory tags found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS removed -->
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Add basic table styling
            $('#untaggedTable').addClass('table-striped');
            
            // Initialize filters
            initializeFilters();
        });
        
        function initializeFilters() {
            // Apply initial search from URL parameter
            var initialSearch = '<?php echo htmlspecialchars($search_filter); ?>';
            if (initialSearch !== '') {
                $('#searchInput').val(initialSearch);
                performSearch();
            }
            
            // Initialize office filter from URL parameter
            var initialOffice = '<?php echo $office_filter; ?>';
            if (initialOffice !== '0') {
                $('#officeFilter').val(initialOffice);
                performSearch();
            }
            
            // Set up event listeners
            $('#searchInput').on('input', performSearch);
            $('#officeFilter').on('change', performSearch);
        }
        
        function performSearch() {
            var searchTerm = $('#searchInput').val().toLowerCase();
            var officeFilter = $('#officeFilter').val();
            
            $('#untaggedTable tbody tr').each(function() {
                var $row = $(this);
                var showRow = true;
                
                // Skip empty state row
                if ($row.find('td[colspan]').length > 0) {
                    return;
                }
                
                // Search filter
                if (searchTerm) {
                    var rowText = $row.text().toLowerCase();
                    if (rowText.indexOf(searchTerm) === -1) {
                        showRow = false;
                    }
                }
                
                // Office filter
                if (officeFilter && showRow) {
                    var officeCell = $row.find('td:eq(3)'); // Office column (index 3)
                    if (officeCell.text().trim() !== $('#officeFilter option:selected').text().trim()) {
                        showRow = false;
                    }
                }
                
                $row.toggle(showRow);
            });
        }

        // Export untagged assets function
        function exportUntagged() {
            console.log('Export function called');
            
            let csv = 'Asset Description,Status,Value,Office,Last Updated\n';
            
            $('#untaggedTable tbody tr').each(function() {
                const $row = $(this);
                // Skip empty state rows
                if ($row.find('td[colspan]').length > 0) {
                    return;
                }
                
                const rowData = [];
                $row.find('td').each(function(index) {
                    let cellText = $(this).text().trim();
                    // Only include first 5 columns (exclude Actions column)
                    if (index < 5) {
                        // Clean up the text for CSV
                        cellText = cellText.replace(/[\n\r]/g, ' ').replace(/\s+/g, ' ').trim();
                        rowData.push(cellText);
                    }
                });
                
                if (rowData.length > 0) {
                    csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
                }
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'untagged_assets_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
