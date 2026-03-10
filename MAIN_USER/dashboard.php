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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_dashboard', 'Main user accessed dashboard');

$stats = [];

if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        $check_item_status = $conn->query("SHOW COLUMNS FROM asset_items LIKE 'status'");
        $item_has_status = $check_item_status && $check_item_status->num_rows > 0;

        $all_items_query = "SELECT 
                COUNT(*) as total_items" .
            ($item_has_status ? ",
                SUM(CASE WHEN status = 'serviceable' THEN 1 ELSE 0 END) as serviceable_items,
                SUM(CASE WHEN status = 'unserviceable' THEN 1 ELSE 0 END) as unserviceable_items" : ",
                0 as serviceable_items,
                0 as unserviceable_items") . ",
                COALESCE(SUM(value), 0) as total_value,
                COUNT(DISTINCT office_id) as total_offices
            FROM asset_items";

        $result = $conn->query($all_items_query);
        if ($result) {
            $stats = array_merge($stats, $result->fetch_assoc());
        }

        $categories_query = "SELECT 
                ac.id, ac.category_code as code, ac.category_name as name,
                COUNT(ai.id) as item_count,
                SUM(ai.value) as total_value
            FROM asset_categories ac
            LEFT JOIN assets a ON ac.id = a.asset_categories_id
            LEFT JOIN asset_items ai ON a.id = ai.asset_id
            GROUP BY ac.id, ac.category_code, ac.category_name
            ORDER BY total_value DESC
            LIMIT 6";
        $result = $conn->query($categories_query);
        $stats['top_categories'] = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['top_categories'][] = $row;
            }
        }

        $recent_items_query = "SELECT 
                ai.id, ai.description" . ($item_has_status ? ", ai.status" : "") . ", ai.last_updated,
                a.description as asset_description,
                o.office_name
            FROM asset_items ai
            LEFT JOIN assets a ON ai.asset_id = a.id
            LEFT JOIN offices o ON ai.office_id = o.id
            ORDER BY ai.last_updated DESC
            LIMIT 8";
        $result = $conn->query($recent_items_query);
        $stats['recent_items'] = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['recent_items'][] = $row;
            }
        }

        $status_distribution = [];
        if ($item_has_status) {
            $dist_sql = "SELECT status, COUNT(*) as cnt FROM asset_items GROUP BY status";
            $result = $conn->query($dist_sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $status_distribution[$row['status'] ?? 'unknown'] = (int)($row['cnt'] ?? 0);
                }
            }
        }
        $stats['status_distribution'] = $status_distribution;
        
        $fuel_report_query = "SELECT 
                                COUNT(*) as total_fuel_records,
                                SUM(liters) as total_liters,
                                SUM(cost) as total_cost,
                                AVG(cost_per_liter) as avg_cost_per_liter,
                                MAX(date_filled) as last_fuel_date,
                                COUNT(DISTINCT vehicle_id) as vehicles_used,
                                SUM(CASE WHEN transaction_type = 'fuel_in' THEN liters ELSE 0 END) as fuel_in_liters,
                                SUM(CASE WHEN transaction_type = 'fuel_out' THEN liters ELSE 0 END) as fuel_out_liters,
                                SUM(CASE WHEN transaction_type = 'fuel_in' THEN cost ELSE 0 END) as fuel_in_cost,
                                SUM(CASE WHEN transaction_type = 'fuel_out' THEN cost ELSE 0 END) as fuel_out_cost
                            FROM fuel_records 
                            WHERE date_filled >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $fuel_result = $conn->query($fuel_report_query);
        if ($fuel_result) {
            $stats['fuel_report'] = $fuel_result->fetch_assoc();
        }
        
        $recent_fuel_query = "SELECT 
                                fr.id,
                                fr.liters,
                                fr.cost,
                                fr.date_filled,
                                v.vehicle_name,
                                v.plate_number
                            FROM fuel_records fr
                            LEFT JOIN vehicles v ON fr.vehicle_id = v.id
                            ORDER BY fr.date_filled DESC
                            LIMIT 3";
        $recent_fuel_result = $conn->query($recent_fuel_query);
        $stats['recent_fuel'] = [];
        if ($recent_fuel_result) {
            while ($row = $recent_fuel_result->fetch_assoc()) {
                $stats['recent_fuel'][] = $row;
            }
        }
    } catch (Exception $e) {
        $stats['error'] = 'Error fetching dashboard data: ' . $e->getMessage();
        error_log('Main User Dashboard Error: ' . $e->getMessage());
    }
}

$defaults = [
    'total_items' => 0,
    'serviceable_items' => 0,
    'unserviceable_items' => 0,
    'total_value' => 0,
    'total_offices' => 0,
    'top_categories' => [],
    'recent_items' => [],
    'status_distribution' => [],
    'fuel_report' => [],
    'recent_fuel' => []
];
foreach ($defaults as $key => $value) {
    if (!isset($stats[$key])) {
        $stats[$key] = $value;
    }
}

$category_chart_data = [];
foreach ($stats['top_categories'] as $cat) {
    $category_chart_data[] = [
        'name' => $cat['code'],
        'value' => (float)($cat['total_value'] ?? 0)
    ];
}

$page_title = 'Main User Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main User Dashboard - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
        /* Mobile dashboard value styling */
        @media (max-width: 768px) {
            .stat-value {
                font-size: 1.5rem !important;
                line-height: 1.2 !important;
            }
            
            .category-value {
                font-size: 0.9rem !important;
                font-weight: 600 !important;
            }
            
            .fuel-report .fw-bold {
                font-size: 0.85rem !important;
            }
            
            .small.text-success,
            .small.text-danger,
            .small.text-primary {
                font-size: 0.7rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .stat-value {
                font-size: 1.25rem !important;
                line-height: 1.1 !important;
            }
            
            .category-value {
                font-size: 0.8rem !important;
                font-weight: 600 !important;
            }
            
            .fuel-report .fw-bold {
                font-size: 0.75rem !important;
            }
            
            .small.text-success,
            .small.text-danger,
            .small.text-primary {
                font-size: 0.65rem !important;
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
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-grid-1x2-fill me-2"></i>Main User Dashboard
                        </h1>
                        <p class="text-muted mb-0">Asset items across all offices.</p>
                        <?php if (isset($stats['error'])): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($stats['error']); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a class="btn btn-outline-primary btn-sm" href="dashboard.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format((float)$stats['total_items']); ?></div>
                        <div class="stat-label">Total Asset Items</div>
                        <div class="stat-sublabel"><?php echo number_format((float)$stats['serviceable_items']); ?> serviceable</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <span style="font-size: 1.25rem; font-weight: 600;">₱</span>
                        </div>
                        <div class="stat-value"><?php echo number_format((float)$stats['total_value'], 2); ?></div>
                        <div class="stat-label">Total Value</div>
                        <div class="stat-sublabel"><?php echo number_format((float)$stats['unserviceable_items']); ?> unserviceable</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format((float)$stats['total_offices']); ?></div>
                        <div class="stat-label">Offices Covered</div>
                        <div class="stat-sublabel">All offices</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="section-card">
                                <div class="section-title">
                                    <i class="bi bi-pie-chart"></i> Asset Status Distribution
                                </div>
                                <!-- DEBUG: <?php echo htmlspecialchars(json_encode($stats['status_distribution'])); ?> -->
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                    <div id="statusChartFallback" class="text-center text-muted py-4" style="display: none;">
                                        <i class="bi bi-pie-chart fs-1"></i>
                                        <div class="mt-2">No status data available</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="section-card">
                                <div class="section-title">
                                    <i class="bi bi-bar-chart"></i> Top Categories (Value)
                                </div>
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-title mb-0">
                                <i class="bi bi-clock-history"></i> Recent Asset Item Updates
                            </div>
                            <a href="asset_items.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-right"></i> View All
                            </a>
                        </div>

                        <div class="activity-list">
                            <?php if (!empty($stats['recent_items'])): ?>
                                <?php foreach ($stats['recent_items'] as $ri): ?>
                                    <div class="activity-item" onclick="window.location.href='view_asset_item.php?id=<?php echo (int)$ri['id']; ?>'" style="cursor: pointer;">
                                        <div class="activity-icon">
                                            <i class="bi bi-box"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title"><?php echo htmlspecialchars($ri['description'] ?? ''); ?></div>
                                            <div class="activity-subtitle"><?php echo htmlspecialchars(($ri['asset_description'] ?? '') !== '' ? $ri['asset_description'] : ''); ?><?php echo !empty($ri['office_name']) ? ' • ' . htmlspecialchars($ri['office_name']) : ''; ?></div>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo !empty($ri['last_updated']) ? htmlspecialchars(date('M j', strtotime((string)$ri['last_updated']))) : ''; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">No recent updates found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="section-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-title mb-0">
                                <i class="bi bi-tags"></i> Top Categories
                            </div>
                            <a href="assets.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-right"></i> View Assets
                            </a>
                        </div>

                        <?php if (!empty($stats['top_categories'])): ?>
                            <?php foreach ($stats['top_categories'] as $cat): ?>
                                <div class="category-item">
                                    <div class="category-info">
                                        <div class="category-code"><?php echo htmlspecialchars($cat['code'] ?? ''); ?></div>
                                        <div class="category-name"><?php echo htmlspecialchars($cat['name'] ?? ''); ?></div>
                                    </div>
                                    <div class="category-value">
                                        ₱<?php echo number_format((float)($cat['total_value'] ?? 0), 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">No category data available.</div>
                        <?php endif; ?>
                    </div>

                    <div class="section-card mb-4 fuel-report">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-title mb-0">
                                <i class="bi bi-fuel-pump"></i> Fuel Report (30 Days)
                            </div>
                        </div>
                        
                        <?php if (isset($stats['fuel_report']) && !empty($stats['fuel_report'])): ?>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-success bg-opacity-10 rounded">
                                        <div class="fw-bold text-success"><?php echo number_format((float)($stats['fuel_report']['fuel_in_liters'] ?? 0), 2); ?>L</div>
                                        <div class="small text-muted">Fuel In</div>
                                        <div class="small text-success">₱<?php echo number_format((float)($stats['fuel_report']['fuel_in_cost'] ?? 0), 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-danger bg-opacity-10 rounded">
                                        <div class="fw-bold text-danger"><?php echo number_format((float)($stats['fuel_report']['fuel_out_liters'] ?? 0), 2); ?>L</div>
                                        <div class="small text-muted">Fuel Out</div>
                                        <div class="small text-danger">₱<?php echo number_format((float)($stats['fuel_report']['fuel_out_cost'] ?? 0), 2); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-info"><?php echo number_format((float)($stats['fuel_report']['avg_cost_per_liter'] ?? 0), 2); ?></div>
                                        <div class="small text-muted">Avg Cost/L</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-primary"><?php echo (int)($stats['fuel_report']['vehicles_used'] ?? 0); ?></div>
                                        <div class="small text-muted">Vehicles</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center p-2 bg-primary bg-opacity-10 rounded mb-3">
                                <div class="fw-bold text-primary"><?php echo number_format((float)(($stats['fuel_report']['fuel_in_liters'] ?? 0) - ($stats['fuel_report']['fuel_out_liters'] ?? 0)), 2); ?>L</div>
                                <div class="small text-muted">Net Balance</div>
                                <div class="small text-primary">₱<?php echo number_format((float)(($stats['fuel_report']['fuel_in_cost'] ?? 0) - ($stats['fuel_report']['fuel_out_cost'] ?? 0)), 2); ?></div>
                            </div>
                            
                            <div class="text-center mb-3">
                                <small class="text-muted">
                                    Last: <?php echo !empty($stats['fuel_report']['last_fuel_date']) ? date('M j', strtotime($stats['fuel_report']['last_fuel_date'])) : 'No records'; ?>
                                </small>
                            </div>
                            
                            <div class="section-title mb-2">
                                <i class="bi bi-clock-history"></i> Recent
                            </div>
                            
                            <?php if (!empty($stats['recent_fuel'])): ?>
                                <?php foreach ($stats['recent_fuel'] as $fuel): ?>
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-1">
                                        <div>
                                            <div class="fw-bold small"><?php echo htmlspecialchars($fuel['vehicle_name'] ?? 'Unknown'); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($fuel['plate_number'] ?? ''); ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold small"><?php echo number_format((float)($fuel['liters'] ?? 0), 2); ?>L</div>
                                            <div class="small text-muted">₱<?php echo number_format((float)($fuel['cost'] ?? 0), 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-2">
                                    <small>No recent fuel records</small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-fuel-pump" style="font-size: 2rem;"></i>
                                <div class="mt-2">No fuel data available</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="section-card">
                        <div class="section-title">
                            <i class="bi bi-info-circle"></i> Quick Links
                        </div>
                        <div class="d-grid gap-2">
                            <a class="btn btn-primary" href="asset_items.php">
                                <i class="bi bi-list-ul me-2"></i>Browse Asset Items
                            </a>
                            <a class="btn btn-outline-primary" href="assets.php">
                                <i class="bi bi-box-seam me-2"></i>Browse Assets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusCtx = document.getElementById('statusChart');
            const catCtx = document.getElementById('categoryChart');
            const statusFallback = document.getElementById('statusChartFallback');

            const statusData = <?php echo json_encode($stats['status_distribution']); ?>;
            const statusLabels = Object.keys(statusData);
            const statusValues = Object.values(statusData);

            if (statusCtx && statusLabels.length) {
                try {
                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: statusLabels.map(s => String(s).replace(/_/g, ' ')),
                            datasets: [{
                                data: statusValues,
                                backgroundColor: ['#28a745', '#007bff', '#dc3545', '#ffc107', '#6c757d', '#17a2b8']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                } catch (e) {
                    console.error('Status chart error:', e);
                    if (statusFallback) statusFallback.style.display = 'block';
                }
            } else {
                if (statusFallback) statusFallback.style.display = 'block';
            }

            const categoryData = <?php echo json_encode($category_chart_data); ?>;
            if (catCtx && categoryData.length) {
                try {
                    new Chart(catCtx, {
                        type: 'bar',
                        data: {
                            labels: categoryData.map(c => c.name),
                            datasets: [{
                                label: 'Value',
                                data: categoryData.map(c => c.value),
                                backgroundColor: 'rgba(25, 27, 169, 0.7)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                } catch (e) {
                    console.error('Category chart error:', e);
                }
            }
        });

    </script>

</body>
</html>
