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
        // Get all categories
        $category_query = "SELECT id, category_name FROM asset_categories ORDER BY category_name ASC";
        $category_result = $conn->query($category_query);
        
        if ($category_result) {
            while ($category = $category_result->fetch_assoc()) {
                $categories[] = $category;
            }
        }
        
        // Create example branch data
        $example_branches = [
            [
                'id' => 1,
                'branch_name' => 'Supply Room',
                'total_items' => 150,
                'total_value' => 250000.00,
                'status_counts' => [
                    'serviceable' => 120,
                    'unserviceable' => 15,
                    'red_tagged' => 5,
                    'borrowed' => 8,
                    'no_tag' => 2
                ]
            ],
            [
                'id' => 2,
                'branch_name' => 'Main Office',
                'total_items' => 85,
                'total_value' => 180000.00,
                'status_counts' => [
                    'serviceable' => 70,
                    'unserviceable' => 8,
                    'red_tagged' => 3,
                    'borrowed' => 4,
                    'no_tag' => 0
                ]
            ],
            [
                'id' => 3,
                'branch_name' => 'IT Department',
                'total_items' => 200,
                'total_value' => 450000.00,
                'status_counts' => [
                    'serviceable' => 180,
                    'unserviceable' => 12,
                    'red_tagged' => 5,
                    'borrowed' => 3,
                    'no_tag' => 0
                ]
            ],
            [
                'id' => 4,
                'branch_name' => 'Maintenance',
                'total_items' => 95,
                'total_value' => 120000.00,
                'status_counts' => [
                    'serviceable' => 75,
                    'unserviceable' => 10,
                    'red_tagged' => 8,
                    'borrowed' => 2,
                    'no_tag' => 0
                ]
            ],
            [
                'id' => 5,
                'branch_name' => 'Warehouse',
                'total_items' => 300,
                'total_value' => 320000.00,
                'status_counts' => [
                    'serviceable' => 250,
                    'unserviceable' => 30,
                    'red_tagged' => 15,
                    'borrowed' => 5,
                    'no_tag' => 0
                ]
            ],
            [
                'id' => 6,
                'branch_name' => 'Conference Room',
                'total_items' => 45,
                'total_value' => 85000.00,
                'status_counts' => [
                    'serviceable' => 40,
                    'unserviceable' => 3,
                    'red_tagged' => 1,
                    'borrowed' => 1,
                    'no_tag' => 0
                ]
            ]
        ];
        
        // Apply branch filter if needed
        if ($branch_filter > 0) {
            $example_branches = array_filter($example_branches, function($branch) use ($branch_filter) {
                return $branch['id'] == $branch_filter;
            });
        }
        
        foreach ($example_branches as $branch) {
            $assets_by_branch[$branch['id']] = [
                'branch_name' => $branch['branch_name'],
                'total_items' => $branch['total_items'],
                'total_value' => $branch['total_value'],
                'status_counts' => $branch['status_counts'],
                'assets' => [] // Empty for now - would be populated with actual assets
            ];
        }
        
    } catch (Exception $e) {
        $error = 'Error loading branches: ' . $e->getMessage();
        error_log('Main User Branches Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branches - Main User | PIMS</title>
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
    <?php $page_title = 'Branches'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #667eea;">
                            <i class="bi bi-diagram-3 me-2"></i>Branches
                            <a href="assets_per_office.php?office_id=<?php echo $branch_filter; ?>" class="btn btn-outline-primary btn-sm ms-3">
                                <i class="bi bi-building"></i> Offices
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
                            <a class="btn btn-outline-secondary btn-sm" href="assets_per_office.php?office_id=4">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <a class="btn btn-outline-primary btn-sm" href="branches.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                            <div class="d-inline-block" style="min-width: 200px;">
                                <select class="form-select form-select-sm" id="branchFilter">
                                    <option value="0" <?php echo $branch_filter === 0 ? 'selected' : ''; ?>>All Branches</option>
                                    <option value="1" <?php echo $branch_filter === 1 ? 'selected' : ''; ?>>Supply Room</option>
                                    <option value="2" <?php echo $branch_filter === 2 ? 'selected' : ''; ?>>Main Office</option>
                                    <option value="3" <?php echo $branch_filter === 3 ? 'selected' : ''; ?>>IT Department</option>
                                    <option value="4" <?php echo $branch_filter === 4 ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="5" <?php echo $branch_filter === 5 ? 'selected' : ''; ?>>Warehouse</option>
                                    <option value="6" <?php echo $branch_filter === 6 ? 'selected' : ''; ?>>Conference Room</option>
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
                                <?php 
                                $branch_names = [
                                    1 => 'Supply Room',
                                    2 => 'Main Office', 
                                    3 => 'IT Department',
                                    4 => 'Maintenance',
                                    5 => 'Warehouse',
                                    6 => 'Conference Room'
                                ];
                                ?>
                                <span class="badge bg-primary me-1">Branch: <?php echo htmlspecialchars($branch_names[$branch_filter] ?? 'Unknown'); ?></span>
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
                            <a href="assets_per_branch.php?branch_id=<?php echo (int)$branch_id; ?>" class="text-decoration-none branch-card-link">
                                <div class="branch-card">
                                    <div class="branch-header">
                                        <h4><?php echo htmlspecialchars($branch_data['branch_name']); ?></h4>
                                        
                                        <div class="branch-stats">
                                            <div class="stat-item">
                                                <span class="stat-value"><?php echo number_format((int)($branch_data['total_items'] ?? 0)); ?></span>
                                                <span class="stat-label">Total Assets</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="branch-details">
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
                                            <?php if (($branch_data['status_counts']['borrowed'] ?? 0) > 0): ?>
                                                <span class="status-badge status-borrowed">
                                                    Borrowed: <?php echo (int)$branch_data['status_counts']['borrowed']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (($branch_data['status_counts']['no_tag'] ?? 0) > 0): ?>
                                                <span class="status-badge status-no-tag">
                                                    No Tag: <?php echo (int)$branch_data['status_counts']['no_tag']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="branch-actions">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
