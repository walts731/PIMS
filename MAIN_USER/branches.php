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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_branches', 'Main user accessed branches page');

$branches = [];
$assets_by_branch = [];
$error = null;

// Filter parameters
$branch_filter = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$office_filter = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$status_filter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$category_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$allowed_statuses = ['serviceable', 'unserviceable', 'red_tagged', 'in_use', 'no_tag'];
if ($status_filter !== '' && !in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}

$categories = [];
if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Get all categories
        $category_query = "SELECT id, category_name FROM asset_categories ORDER BY category_name ASC";
        $category_result = $conn->query($category_query);
        
        if ($category_result) {
            while ($category = $category_result->fetch_assoc()) {
                $categories[] = $category;
            }
        }
        
        // Get all offices to build hierarchy
        $all_offices_query = "SELECT id, office_name, branch FROM offices ORDER BY office_name ASC";
        $all_offices_result = $conn->query($all_offices_query);
        $all_offices = [];
        if ($all_offices_result) {
            while ($row = $all_offices_result->fetch_assoc()) {
                $all_offices[] = $row;
            }
        }
        
        // Build hierarchy tree
        $office_tree = [];
        $main_offices = [];
        
        foreach ($all_offices as $office) {
            if ($office['branch'] === null) {
                // This is a main office
                $office['children'] = [];
                $office['level'] = 0;
                $office_tree[$office['id']] = $office;
                $main_offices[$office['id']] = $office['office_name'];
            }
        }
        
        // Add branches to their parents
        foreach ($all_offices as $office) {
            if ($office['branch'] !== null && isset($office_tree[$office['branch']])) {
                $office['children'] = [];
                $office['level'] = 1;
                $office_tree[$office['branch']]['children'][] = $office;
            }
        }
        
        // Add sub-branches to their parents
        foreach ($all_offices as $office) {
            if ($office['branch'] !== null && !isset($office_tree[$office['branch']])) {
                // This is a sub-branch, find its parent
                foreach ($office_tree as $parent_id => $parent_office) {
                    foreach ($parent_office['children'] as $child) {
                        if ($child['id'] == $office['branch']) {
                            $office['children'] = [];
                            $office['level'] = 2;
                            $child['children'][] = $office;
                            break 2;
                        }
                    }
                }
            }
        }
        
        // Flatten the tree for display if no specific office filter
        $branches = [];
        if ($office_filter == 0) {
            // Show all offices with hierarchy
            foreach ($office_tree as $main_office) {
                $branches[] = $main_office;
                foreach ($main_office['children'] as $branch) {
                    $branches[] = $branch;
                    foreach ($branch['children'] as $sub_branch) {
                        $branches[] = $sub_branch;
                    }
                }
            }
        } else {
            // Show only offices under the specified main office
            if (isset($office_tree[$office_filter])) {
                $main_office = $office_tree[$office_filter];
                $office_name = $main_office['office_name'];
                foreach ($main_office['children'] as $branch) {
                    $branches[] = $branch;
                    foreach ($branch['children'] as $sub_branch) {
                        $branches[] = $sub_branch;
                    }
                }
            }
        }
        
        // Load assets for each branch
        foreach ($branches as $branch) {
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
            
            // Get parent office name for display
            $parent_office_name = '';
            if ($branch['branch'] !== null) {
                foreach ($all_offices as $office) {
                    if ($office['id'] == $branch['branch']) {
                        $parent_office_name = $office['office_name'];
                        break;
                    }
                }
            }
            
            $assets_by_branch[$branch['id']] = [
                'branch_name' => $branch['office_name'],
                'office_name' => $parent_office_name,
                'level' => $branch['level'],
                'assets' => [],
                'total_items' => 0,
                'total_value' => 0,
                'status_counts' => [
                    'serviceable' => 0,
                    'unserviceable' => 0,
                    'red_tagged' => 0,
                    'in_use' => 0,
                    'no_tag' => 0
                ]
            ];
            
            if ($asset_result) {
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
            }
            
            $asset_stmt->close();
        }
        
    } catch (Exception $e) {
        $error = 'Error loading branches: ' . $e->getMessage();
        error_log('Main User Branches Error: ' . $e->getMessage());
    }
}

$page_title = 'Branches';
if ($office_filter > 0 && !empty($office_name)) {
    $page_title = "Branches - {$office_name}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Main User | PIMS</title>
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
    
    .branch-card-link {
        display: block;
        color: inherit;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .branch-card-link:hover {
        color: inherit;
        text-decoration: none;
        transform: scale(1.02);
    }
    
    .branch-card-link:hover .branch-card {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }
    
    .branch-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        position: relative;
    }
    
    .branch-header h4 {
        margin: 0;
        font-weight: 700;
        font-size: 1.25rem;
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
    
    .branch-details {
        padding: 1.5rem;
    }
    
    .status-badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
    }
    
    .branch-actions {
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
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
    
    @media (max-width: 768px) {
        .branch-card {
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .branch-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .branch-stats {
            gap: 1rem;
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
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #667eea;">
                            <i class="bi bi-diagram-3 me-2"></i>
                            <?php 
                            if ($office_filter > 0 && !empty($office_name)) {
                                echo htmlspecialchars("{$office_name} Branches");
                            } else {
                                echo "All Branches";
                            }
                            ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <?php 
                            if ($office_filter > 0 && !empty($office_name)) {
                                echo "Viewing branches for " . htmlspecialchars($office_name) . " with filters.";
                            } else {
                                echo "Viewing all branches across all offices with filters.";
                            }
                            ?>
                        </p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <?php if ($office_filter > 0): ?>
                                <a class="btn btn-outline-secondary btn-sm" href="assets_per_office.php?office_id=<?php echo (int)$office_filter; ?>">
                                    <i class="bi bi-arrow-left"></i> Back to Office
                                </a>
                            <?php else: ?>
                                <a class="btn btn-outline-secondary btn-sm" href="assets_per_office.php">
                                    <i class="bi bi-arrow-left"></i> Back to Offices
                                </a>
                            <?php endif; ?>
                            <a class="btn btn-outline-primary btn-sm" href="branches.php">
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
                                    <option value="in_use" <?php echo $status_filter === 'in_use' ? 'selected' : ''; ?>>Borrowed</option>
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
                        <a href="branches.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$error && !empty($assets_by_branch)): ?>
                <div class="row">
                    <?php foreach ($assets_by_branch as $branch_id => $branch_data): ?>
                        <div class="col-lg-6 col-xl-4">
                            <a href="assets_per_branch.php?branch_id=<?php echo (int)$branch_id; ?>" class="text-decoration-none office-card-link">
                                <div class="office-card <?php echo $branch_data['level'] > 0 ? 'branch-card' : ''; ?>">
                                    <div class="office-header <?php echo $branch_data['level'] > 0 ? 'branch-header' : ''; ?>">
                                        <h4>
                                            <?php if ($branch_data['level'] > 0): ?>
                                                <i class="bi bi-diagram-2 me-2"></i>
                                            <?php else: ?>
                                                <i class="bi bi-building me-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($branch_data['branch_name']); ?>
                                        </h4>
                                        <?php if (!empty($branch_data['office_name'])): ?>
                                            <small class="d-block mt-1 opacity-75">
                                                <i class="bi bi-arrow-up-right"></i> 
                                                Under: <?php echo htmlspecialchars($branch_data['office_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                        
                                        <div class="office-stats">
                                            <div class="stat-item">
                                                <span class="stat-value"><?php echo number_format((int)($branch_data['total_items'] ?? 0)); ?></span>
                                                <span class="stat-label">Total Assets</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="office-details">
                                        <div class="status-badges">
                                            <?php if (($branch_data['status_counts']['serviceable'] ?? 0) > 0): ?>
                                                <span class="status-badge status-serviceable">
                                                    Serviceable: <?php echo (int)$branch_data['status_counts']['serviceable']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (($branch_data['status_counts']['unserviceable'] ?? 0) > 0): ?>
                                                <span class="status-badge status-unserviceable">
                                                    Unserviceable: <?php echo (int)$branch_data['status_counts']['unserviceable']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (($branch_data['status_counts']['red_tagged'] ?? 0) > 0): ?>
                                                <span class="status-badge status-red-tagged">
                                                    Red-Tagged: <?php echo (int)$branch_data['status_counts']['red_tagged']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (($branch_data['status_counts']['in_use'] ?? 0) > 0): ?>
                                                <span class="status-badge status-borrowed">
                                                    Borrowed: <?php echo (int)$branch_data['status_counts']['in_use']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (($branch_data['status_counts']['no_tag'] ?? 0) > 0): ?>
                                                <span class="status-badge status-no-tag">
                                                    No Tag: <?php echo (int)$branch_data['status_counts']['no_tag']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="office-actions">
                                        <span class="text-primary small">
                                            <i class="bi bi-arrow-right-circle"></i> Click to view details
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-diagram-3" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No Branches Found</h4>
                    <p>No branches have been set up in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
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
    </script>
</body>
</html>
