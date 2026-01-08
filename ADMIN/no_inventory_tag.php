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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'No Inventory Tag';
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
                    <button class="btn btn-warning" onclick="window.location.href='assets.php'">
                        <i class="bi bi-arrow-left"></i> Back to Assets
                    </button>
                    <button class="btn btn-success btn-sm ms-2" onclick="exportUntagged()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Warning Alert -->
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Attention:</strong> The following assets may require inventory tagging for proper tracking and management.
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-6 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_untagged'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-exclamation-triangle"></i> Untagged Assets</div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                    <div class="stats-label"><i class="bi bi-currency-dollar"></i> Total Value</div>
                </div>
            </div>
        </div>
        
        <!-- Assets Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Asset Items Requiring Tags</h5>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" id="officeFilter">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search assets..." value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="untaggedTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Asset Description</th>
                            <th>Item Description</th>
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
                                    <td>
                                        <span class="warning-badge">
                                            <?php echo htmlspecialchars($item['category_code'] ?? 'N/A'); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['asset_description']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
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
                                    <td class="text-value"><?php echo number_format($item['value'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo date('M j, Y', strtotime($item['last_updated'])); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="tagItem(<?php echo $item['id']; ?>)">
                                            <i class="bi bi-tag"></i> Tag
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
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
    </div> <!-- Close main-wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#untaggedTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[7, 'desc']], // Sort by last updated column by default
                searching: false, // Disable DataTables default search
                language: {
                    lengthMenu: "Show _MENU_ assets per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ untagged assets",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No asset items requiring inventory tags found."
                },
                columnDefs: [
                    {
                        targets: [0], // Category column
                        orderable: true
                    },
                    {
                        targets: [7], // Actions column
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Custom search functionality
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Office filter functionality
            $('#officeFilter').on('change', function() {
                var officeValue = this.value;
                if (officeValue === '') {
                    // Clear office filter
                    table.column(5).search('').draw();
                } else {
                    // Apply office filter to the Office column (index 5)
                    table.column(5).search(officeValue).draw();
                }
            });

            // Set initial office filter if selected
            var initialOfficeFilter = '<?php echo $office_filter; ?>';
            if (initialOfficeFilter !== '0') {
                $('#officeFilter').val(initialOfficeFilter);
                table.column(5).search(initialOfficeFilter).draw();
            }

            // Set initial search if provided
            var initialSearch = '<?php echo htmlspecialchars($search_filter); ?>';
            if (initialSearch !== '') {
                $('#searchInput').val(initialSearch);
                table.search(initialSearch).draw();
            }
        });

        // Export untagged assets function
        function exportUntagged() {
            const table = $('#untaggedTable').DataTable();
            let csv = 'ID,Category,Asset Description,Item Description,Status,Value,Office,Last Updated\n';
            
            const data = table.data().toArray();
            for (let row of data) {
                const rowData = [
                    row[0], // Category
                    row[1], // Asset Description  
                    row[2], // Item Description
                    row[3], // Status
                    row[4], // Value
                    row[5], // Office
                    row[6]  // Last Updated
                ];
                csv += rowData.map(cell => `"${cell.toString().trim()}"`).join(',') + '\n';
            }
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `untagged_asset_items_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Tag item function
        function tagItem(itemId) {
            if (confirm('Are you sure you want to mark this item as tagged?')) {
                // You can implement AJAX call here to update the item status
                alert('Tag functionality will be implemented. Item ID: ' + itemId);
            }
        }
    </script>
</body>
</html>
