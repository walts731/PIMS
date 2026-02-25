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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_assets_per_office', 'Main user accessed assets per office');

$offices = [];
$assets_by_office = [];
$error = null;

// Filter parameters
$office_filter = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
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
        // Get all offices
        $office_query = "SELECT id, office_name FROM offices ORDER BY office_name ASC";
        $office_result = $conn->query($office_query);
        
        // Get all categories
        $category_query = "SELECT id, category_name FROM asset_categories ORDER BY category_name ASC";
        $category_result = $conn->query($category_query);
        
        if ($category_result) {
            while ($category = $category_result->fetch_assoc()) {
                $categories[] = $category;
            }
        }
        
        if ($office_result) {
            while ($office = $office_result->fetch_assoc()) {
                $offices[] = $office;
                
                // Apply office filter
                if ($office_filter > 0 && $office['id'] != $office_filter) {
                    continue;
                }
                
                // Get assets for this office with filters
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
                
                $params = [$office['id']];
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
                    $asset_stmt->bind_param("i", $office['id']);
                }
                $asset_stmt->execute();
                $asset_result = $asset_stmt->get_result();
                
                $assets_by_office[$office['id']] = [
                    'office_name' => $office['office_name'],
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
                    $assets_by_office[$office['id']]['assets'][] = $asset;
                    $assets_by_office[$office['id']]['total_items'] += $asset['total_items'];
                    $assets_by_office[$office['id']]['total_value'] += $asset['total_value'];
                    
                    // Count by status
                    $status = $asset['item_status'];
                    if (isset($assets_by_office[$office['id']]['status_counts'][$status])) {
                        $assets_by_office[$office['id']]['status_counts'][$status] += $asset['total_items'];
                    }
                }
                
                $asset_stmt->close();
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error loading assets per office: ' . $e->getMessage();
        error_log('Main User Assets Per Office Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets per Office - Main User | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
    .office-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .office-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .office-header {
        background: var(--primary-gradient);
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .office-stats {
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
    <?php $page_title = 'Assets per Office'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-building me-2"></i>Assets per Office
                        </h1>
                        <p class="text-muted mb-0">Viewing assets organized by office location with filters.</p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <a class="btn btn-outline-primary btn-sm" href="assets_per_office.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                            <div class="d-inline-block" style="min-width: 200px;">
                                <select class="form-select form-select-sm" id="officeFilter">
                                    <option value="0" <?php echo $office_filter === 0 ? 'selected' : ''; ?>>All Offices</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo (int)$office['id']; ?>" <?php echo $office_filter === (int)$office['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_name']); ?>
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
            <?php if ($office_filter > 0 || $status_filter !== '' || $category_filter > 0): ?>
                <div class="alert alert-info mb-3" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-funnel me-2"></i>
                            <strong>Active Filters:</strong>
                            <?php if ($office_filter > 0): ?>
                                <span class="badge bg-primary me-1">Office: <?php echo htmlspecialchars(array_column($offices, 'office_name', 'id')[$office_filter] ?? 'Unknown'); ?></span>
                            <?php endif; ?>
                            <?php if ($status_filter !== ''): ?>
                                <span class="badge bg-success me-1">Status: <?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?></span>
                            <?php endif; ?>
                            <?php if ($category_filter > 0): ?>
                                <span class="badge bg-info me-1">Category: <?php echo htmlspecialchars(array_column($categories, 'category_name', 'id')[$category_filter] ?? 'Unknown'); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="assets_per_office.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$error && !empty($assets_by_office)): ?>
                <?php foreach ($assets_by_office as $office_id => $office_data): ?>
                    <div class="office-card">
                        <div class="office-header">
                            <div>
                                <h4 class="mb-2"><?php echo htmlspecialchars($office_data['office_name']); ?></h4>
                                <div class="status-summary">
                                    <?php if ($office_data['status_counts']['serviceable'] > 0): ?>
                                        <span class="status-badge status-serviceable">
                                            Serviceable: <?php echo $office_data['status_counts']['serviceable']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($office_data['status_counts']['unserviceable'] > 0): ?>
                                        <span class="status-badge status-unserviceable">
                                            Unserviceable: <?php echo $office_data['status_counts']['unserviceable']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($office_data['status_counts']['red_tagged'] > 0): ?>
                                        <span class="status-badge status-red-tagged">
                                            Red-Tagged: <?php echo $office_data['status_counts']['red_tagged']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($office_data['status_counts']['borrowed'] > 0): ?>
                                        <span class="status-badge status-borrowed">
                                            Borrowed: <?php echo $office_data['status_counts']['borrowed']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($office_data['status_counts']['no_tag'] > 0): ?>
                                        <span class="status-badge status-no-tag">
                                            No Tag: <?php echo $office_data['status_counts']['no_tag']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="office-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo number_format($office_data['total_items']); ?></span>
                                    <span class="stat-label">Total Items</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?php echo number_format($office_data['total_value'], 2); ?></span>
                                    <span class="stat-label">Total Value</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="asset-table">
                            <?php if (!empty($office_data['assets'])): ?>
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
                                            <?php foreach ($office_data['assets'] as $asset): ?>
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
                                                        <a href="view_asset_item.php?id=<?php echo (int)$asset['item_id']; ?>" class="btn btn-sm btn-outline-info">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">No assets found in this office.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-building" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No Office Data Available</h4>
                    <p>No assets or offices found in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const officeFilter = document.getElementById('officeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const categoryFilter = document.getElementById('categoryFilter');

            function applyFilters() {
                const currentUrl = new URL(window.location.href);

                // Apply office filter
                const officeValue = parseInt(officeFilter.value || '0', 10);
                if (officeValue > 0) {
                    currentUrl.searchParams.set('office_id', String(officeValue));
                } else {
                    currentUrl.searchParams.delete('office_id');
                }

                // Apply status filter
                const statusValue = statusFilter.value || '';
                if (statusValue) {
                    currentUrl.searchParams.set('status', statusValue);
                } else {
                    currentUrl.searchParams.delete('status');
                }

                // Apply category filter
                const categoryValue = parseInt(categoryFilter.value || '0', 10);
                if (categoryValue > 0) {
                    currentUrl.searchParams.set('category_id', String(categoryValue));
                } else {
                    currentUrl.searchParams.delete('category_id');
                }

                window.location.href = currentUrl.toString();
            }

            // Add event listeners
            if (officeFilter) {
                officeFilter.addEventListener('change', applyFilters);
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', applyFilters);
            }
            if (categoryFilter) {
                categoryFilter.addEventListener('change', applyFilters);
            }

            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add loading state to filter buttons
            const filterButtons = document.querySelectorAll('.form-select');
            filterButtons.forEach(button => {
                button.addEventListener('change', function() {
                    this.style.opacity = '0.7';
                    setTimeout(() => {
                        this.style.opacity = '1';
                    }, 300);
                });
            });
        });
    </script>
