<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_assets_per_branch', 'Main user accessed assets per branch');

$branches = [];
$assets_by_branch = [];
$error = null;

// Filter parameters
$branch_filter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$status_filter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$category_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'borrowed', 'no_tag'];
if ($status_filter !== '' && !in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}

$categories = [];
if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Get all branches
        $branch_query = "SELECT id, office_name as branch_name FROM offices ORDER BY office_name ASC";
        $branch_result = $conn->query($branch_query);
        
        // Get all categories
        $category_query = "SELECT id, category_name FROM asset_categories ORDER BY category_name ASC";
        $category_result = $conn->query($category_query);
        
        if ($category_result) {
            while ($category = $category_result->fetch_assoc()) {
                $categories[] = $category;
            }
        }
        
        if ($branch_result) {
            while ($branch = $branch_result->fetch_assoc()) {
                $branches[] = $branch;
                
                // Apply branch filter
                if ($branch_filter > 0 && $branch['id'] != $branch_filter) {
                    continue;
                }
                
                // Get assets for this branch with filters
                $asset_query = "SELECT 
                                    ai.id as item_id,
                                    ai.description as item_description,
                                    ai.status as item_status,
                                    ai.value as item_value,
                                    ai.property_no,
                                    ai.acquisition_date,
                                    a.description as asset_description,
                                    a.unit,
                                    ac.category_name,
                                    ac.category_code,
                                    ac.id as category_id,
                                    COUNT(ai.id) as total_items,
                                    COALESCE(SUM(ai.value), 0) as total_value
                                FROM asset_items ai
                                LEFT JOIN assets a ON ai.asset_id = a.id
                                LEFT JOIN asset_categories ac ON ac.id = a.asset_categories_id
                                WHERE ai.office_id = ?";
                
                $params = [$branch['id']];
                $types = "i";
                
                // Apply status filter
                if ($status_filter !== '') {
                    $asset_query .= " AND ai.status = ?";
                    $params[] = $status_filter;
                    $types .= "s";
                }
                
                // Apply category filter
                if ($category_filter > 0) {
                    $asset_query .= " AND ac.id = ?";
                    $params[] = $category_filter;
                    $types .= "i";
                }
                
                $asset_query .= " GROUP BY a.id, ai.id ORDER BY a.description, ai.property_no";
                
                $asset_stmt = $conn->prepare($asset_query);
                if (!empty($params)) {
                    $asset_stmt->bind_param($types, ...$params);
                } else {
                    $asset_stmt->bind_param("i", $branch['id']);
                }
                $asset_stmt->execute();
                $asset_result = $asset_stmt->get_result();
                
                $assets_by_branch[$branch['id']] = [
                    'branch_name' => $branch['branch_name'],
                    'assets' => [],
                    'total_items' => 0,
                    'total_value' => 0,
                    'status_counts' => [
                        'serviceable' => 0,
                        'unserviceable' => 0,
                        'red_tagged' => 0,
                        'borrowed' => 0,
                        'no_tag' => 0
                    ]
                ];
                
                while ($asset = $asset_result->fetch_assoc()) {
                    $assets_by_branch[$branch['id']]['assets'][] = $asset;
                    $assets_by_branch[$branch['id']]['total_items'] += $asset['total_items'];
                    $assets_by_branch[$branch['id']]['total_value'] += $asset['total_value'];
                    
                    // Count by status
                    $status = $asset['item_status'];
                    if (isset($assets_by_branch[$branch['id']]['status_counts'][$status])) {
                        $assets_by_branch[$branch['id']]['status_counts'][$status] += $asset['total_items'];
                    }
                }
                
                $asset_stmt->close();
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error loading assets per branch: ' . $e->getMessage();
        error_log('Main User Assets Per Branch Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets per Branch - Main User | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
    .branch-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .branch-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .branch-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .branch-stats {
        display: flex;
        gap: 2rem;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        display: block;
    }
    
    .stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        white-space: nowrap;
        display: inline-block;
        margin: 0.125rem;
    }
    
    .status-serviceable {
        background: #d4edda;
        color: #155724;
    }
    
    .status-unserviceable {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-red-tagged {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-borrowed {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-no-tag {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .asset-table {
        margin: 1.5rem;
    }
    
    .status-summary {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
    }
    </style>
</head>
<body>
    <?php $page_title = 'Assets per Branch'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #667eea;">
                            <i class="bi bi-diagram-3 me-2"></i>Assets per Branch
                            <a href="branches.php" class="btn btn-outline-success btn-sm ms-3">
                                <i class="bi bi-diagram-3"></i> Branches
                            </a>
                        </h1>
                        <p class="text-muted mb-0">Viewing assets organized by branch location with filters.</p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <a class="btn btn-outline-primary btn-sm" href="assets_per_branch.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                            <div class="d-inline-block" style="min-width: 200px;">
                                <select class="form-select form-select-sm" id="branchFilter">
                                    <option value="0" <?php echo $branch_filter === 0 ? 'selected' : ''; ?>>All Branches</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo (int)$branch['id']; ?>" <?php echo $branch_filter === (int)$branch['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-inline-block" style="min-width: 180px;">
                                <select class="form-select form-select-sm" id="statusFilter">
                                    <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="serviceable" <?php echo $status_filter === 'serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                    <option value="unserviceable" <?php echo $status_filter === 'unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                    <option value="red_tagged" <?php echo $status_filter === 'red_tagged' ? 'selected' : ''; ?>>Red-Tagged</option>
                                    <option value="borrowed" <?php echo $status_filter === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                    <option value="no_tag" <?php echo $status_filter === 'no_tag' ? 'selected' : ''; ?>>No Tag</option>
                                </select>
                            </div>
                            <div class="d-inline-block" style="min-width: 180px;">
                                <select class="form-select form-select-sm" id="categoryFilter">
                                    <option value="0" <?php echo $category_filter === 0 ? 'selected' : ''; ?>>All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo (int)$category['id']; ?>" <?php echo $category_filter === (int)$category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Summary -->
            <?php if ($branch_filter > 0 || $status_filter !== '' || $category_filter > 0): ?>
                <div class="alert alert-info mb-3" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-funnel me-2"></i>
                            <strong>Active Filters:</strong>
                            <?php if ($branch_filter > 0): ?>
                                <span class="badge bg-primary me-1">Branch: <?php echo htmlspecialchars(array_column($branches, 'branch_name', 'id')[$branch_filter] ?? 'Unknown'); ?></span>
                            <?php endif; ?>
                            <?php if ($status_filter !== ''): ?>
                                <span class="badge bg-success me-1">Status: <?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?></span>
                            <?php endif; ?>
                            <?php if ($category_filter > 0): ?>
                                <span class="badge bg-info me-1">Category: <?php echo htmlspecialchars(array_column($categories, 'category_name', 'id')[$category_filter] ?? 'Unknown'); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="assets_per_branch.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$error && !empty($assets_by_branch)): ?>
                <?php foreach ($assets_by_branch as $branch_id => $branch_data): ?>
                    <div class="branch-card">
                        <div class="branch-header">
                            <div>
                                <h4 class="mb-2"><?php echo htmlspecialchars($branch_data['branch_name']); ?></h4>
                                <div class="status-summary">
                                    <?php if ($branch_data['status_counts']['serviceable'] > 0): ?>
                                        <span class="status-badge status-serviceable">
                                            Serviceable: <?php echo $branch_data['status_counts']['serviceable']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($branch_data['status_counts']['unserviceable'] > 0): ?>
                                        <span class="status-badge status-unserviceable">
                                            Unserviceable: <?php echo $branch_data['status_counts']['unserviceable']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($branch_data['status_counts']['red_tagged'] > 0): ?>
                                        <span class="status-badge status-red-tagged">
                                            Red-Tagged: <?php echo $branch_data['status_counts']['red_tagged']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($branch_data['status_counts']['borrowed'] > 0): ?>
                                        <span class="status-badge status-borrowed">
                                            Borrowed: <?php echo $branch_data['status_counts']['borrowed']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($branch_data['status_counts']['no_tag'] > 0): ?>
                                        <span class="status-badge status-no-tag">
                                            No Tag: <?php echo $branch_data['status_counts']['no_tag']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="branch-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo number_format($branch_data['total_items']); ?></span>
                                    <span class="stat-label">Total Items</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo number_format($branch_data['total_value'], 2); ?></span>
                                    <span class="stat-label">Total Value</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="asset-table">
                            <?php if (!empty($branch_data['assets'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Property No</th>
                                                <th>Description</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th class="text-end">Value</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($branch_data['assets'] as $asset): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($asset['property_no'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($asset['item_description'] ?? ''); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($asset['asset_description'] ?? ''); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($asset['category_name'] ?? ''); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status = $asset['item_status'] ?? '';
                                                        $status_class = '';
                                                        $display_status = '';
                                                        switch($status) {
                                                            case 'serviceable':
                                                                $status_class = 'status-serviceable';
                                                                $display_status = 'Serviceable';
                                                                break;
                                                            case 'unserviceable':
                                                                $status_class = 'status-unserviceable';
                                                                $display_status = 'Unserviceable';
                                                                break;
                                                            case 'red_tagged':
                                                                $status_class = 'status-red-tagged';
                                                                $display_status = 'Red-Tagged';
                                                                break;
                                                            case 'borrowed':
                                                                $status_class = 'status-borrowed';
                                                                $display_status = 'Borrowed';
                                                                break;
                                                            case 'no_tag':
                                                                $status_class = 'status-no-tag';
                                                                $display_status = 'No Tag';
                                                                break;
                                                            default:
                                                                $status_class = 'status-unknown';
                                                                $display_status = ucfirst(str_replace('_', ' ', $status));
                                                        }
                                                        ?>
                                                        <span class="status-badge <?php echo $status_class; ?>">
                                                            <?php echo $display_status; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end"><?php echo number_format((float)($asset['item_value'] ?? 0), 2); ?></td>
                                                    <td>
                                                        <a href="view_asset_item.php?id=<?php echo (int)$asset['item_id']; ?>" class="btn btn-sm btn-outline-info me-1">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                        <?php if ($asset['item_status'] === 'serviceable'): ?>
                                                            <button class="btn btn-sm btn-outline-warning" onclick="borrowItem(<?php echo (int)$asset['item_id']; ?>)">
                                                                <i class="bi bi-arrow-left-right"></i> Borrow
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">No assets found in this branch.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-diagram-3" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No Branch Data Available</h4>
                    <p>No assets or branches found in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter functionality
        document.getElementById('branchFilter')?.addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value === '0') {
                url.searchParams.delete('branch_id');
            } else {
                url.searchParams.set('branch_id', this.value);
            }
            window.location.href = url.toString();
        });

        document.getElementById('statusFilter')?.addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value === '') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', this.value);
            }
            window.location.href = url.toString();
        });

        document.getElementById('categoryFilter')?.addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value === '0') {
                url.searchParams.delete('category_id');
            } else {
                url.searchParams.set('category_id', this.value);
            }
            window.location.href = url.toString();
        });

        function borrowItem(itemId) {
            if (confirm('Are you sure you want to borrow this item?')) {
                // Implement borrow functionality
                window.location.href = 'borrow_item.php?id=' + itemId;
            }
        }
    </script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
</body>
</html>
