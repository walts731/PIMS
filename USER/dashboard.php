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

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'user_dashboard', 'User accessed dashboard');

$stats = [];
$user_office_id = null;
$user_office_name = null;

if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        $user_office_value = null;

        $stmt = $conn->prepare("SELECT office FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_office_value = $row['office'] ?? null;
        }
        $stmt->close();

        if ($user_office_value !== null && $user_office_value !== '') {
            if (ctype_digit((string)$user_office_value)) {
                $user_office_id = (int)$user_office_value;
                $stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
                $stmt->bind_param('i', $user_office_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $user_office_name = $result->fetch_assoc()['office_name'];
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("SELECT id, office_name FROM offices WHERE office_name = ? OR office_code = ? LIMIT 1");
                $stmt->bind_param('ss', $user_office_value, $user_office_value);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $user_office_id = (int)$row['id'];
                    $user_office_name = $row['office_name'];
                }
                $stmt->close();
            }
        }

        if (!$user_office_id) {
            $stats['error'] = 'Office not assigned to your account. Please contact the administrator.';
        } else {
            $check_item_status = $conn->query("SHOW COLUMNS FROM asset_items LIKE 'status'");
            $item_has_status = $check_item_status && $check_item_status->num_rows > 0;

            $office_assets_query = "SELECT 
                COUNT(*) as total_items" .
                ($item_has_status ? ",
                SUM(CASE WHEN status = 'serviceable' THEN 1 ELSE 0 END) as serviceable_items,
                SUM(CASE WHEN status = 'unserviceable' THEN 1 ELSE 0 END) as unserviceable_items" : ",
                0 as serviceable_items,
                0 as unserviceable_items") . ",
                COALESCE(SUM(value), 0) as total_value
                FROM asset_items
                WHERE office_id = ?";
            $stmt = $conn->prepare($office_assets_query);
            $stmt->bind_param('i', $user_office_id);
            $stmt->execute();
            $office_assets_result = $stmt->get_result();
            if ($office_assets_result) {
                $stats = array_merge($stats, $office_assets_result->fetch_assoc());
            }
            $stmt->close();

            $categories_query = "SELECT 
                ac.id, ac.category_code as code, ac.category_name as name,
                COUNT(ai.id) as item_count,
                SUM(ai.value) as total_value
                FROM asset_categories ac
                LEFT JOIN assets a ON ac.id = a.asset_categories_id
                LEFT JOIN asset_items ai ON a.id = ai.asset_id AND ai.office_id = ?
                GROUP BY ac.id, ac.category_code, ac.category_name
                ORDER BY total_value DESC
                LIMIT 6";
            $stmt = $conn->prepare($categories_query);
            $stmt->bind_param('i', $user_office_id);
            $stmt->execute();
            $categories_result = $stmt->get_result();
            $stats['top_categories'] = [];
            if ($categories_result) {
                while ($row = $categories_result->fetch_assoc()) {
                    $stats['top_categories'][] = $row;
                }
            }
            $stmt->close();

            $recent_items_query = "SELECT 
                ai.id, ai.description" . ($item_has_status ? ", ai.status" : "") . ", ai.last_updated,
                a.description as asset_description
                FROM asset_items ai
                LEFT JOIN assets a ON ai.asset_id = a.id
                WHERE ai.office_id = ?
                ORDER BY ai.last_updated DESC
                LIMIT 8";
            $stmt = $conn->prepare($recent_items_query);
            $stmt->bind_param('i', $user_office_id);
            $stmt->execute();
            $recent_items_result = $stmt->get_result();
            $stats['recent_items'] = [];
            if ($recent_items_result) {
                while ($row = $recent_items_result->fetch_assoc()) {
                    $stats['recent_items'][] = $row;
                }
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $stats['error'] = 'Error fetching dashboard data: ' . $e->getMessage();
        error_log('User Dashboard Error: ' . $e->getMessage());
    }
}

$defaults = [
    'total_items' => 0,
    'serviceable_items' => 0,
    'unserviceable_items' => 0,
    'total_value' => 0,
    'top_categories' => [],
    'recent_items' => []
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'User Dashboard'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                        <i class="bi bi-grid-1x2-fill me-2"></i>User Dashboard
                    </h1>
                    <p class="text-muted mb-0">
                        Asset items for your office<?php echo $user_office_name ? ': ' . htmlspecialchars($user_office_name) : ''; ?>.
                    </p>
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
                    <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
                    <div class="stat-label">Total Asset Items</div>
                    <div class="stat-sublabel"><?php echo number_format($stats['serviceable_items']); ?> serviceable</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <span style="font-size: 1.25rem; font-weight: 600;">₱</span>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_value'], 2); ?></div>
                    <div class="stat-label">Total Asset Value</div>
                    <div class="stat-sublabel">Your office only</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($user_office_name ?? 'N/A'); ?></div>
                    <div class="stat-label">Office</div>
                    <div class="stat-sublabel">Assigned to your account</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="section-card">
                            <div class="section-title">
                                <i class="bi bi-pie-chart"></i> Asset Status Distribution
                            </div>
                            <div class="chart-container">
                                <canvas id="assetStatusChart"></canvas>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-6">
                                    <div class="small text-muted">Serviceable</div>
                                    <div class="fw-bold text-success"><?php echo (int)$stats['serviceable_items']; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Unserviceable</div>
                                    <div class="fw-bold text-danger"><?php echo (int)$stats['unserviceable_items']; ?></div>
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
                            <script type="application/json" id="categoryData"><?php echo json_encode($category_chart_data); ?></script>
                        </div>
                    </div>
                </div>

                <div class="section-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">
                            <i class="bi bi-clock-history"></i> Recent Asset Item Updates
                        </div>
                    </div>

                    <div class="activity-list">
                        <?php if (!empty($stats['recent_items'])): ?>
                            <?php foreach ($stats['recent_items'] as $item): ?>
                                <?php
                                $time_diff = time() - strtotime($item['last_updated']);
                                if ($time_diff < 3600) {
                                    $time_display = floor($time_diff / 60) . ' min ago';
                                } elseif ($time_diff < 86400) {
                                    $time_display = floor($time_diff / 3600) . ' hours ago';
                                } else {
                                    $time_display = floor($time_diff / 86400) . ' days ago';
                                }
                                ?>
                                <div class="activity-item">
                                    <div class="activity-icon blue" style="background: rgba(25, 27, 169, 0.1); color: #191BA9;">
                                        <i class="bi bi-box"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                        <div class="activity-meta">
                                            <?php if (isset($item['status'])): ?>
                                                Status: <?php echo htmlspecialchars(ucfirst($item['status'])); ?>
                                            <?php else: ?>
                                                Updated
                                            <?php endif; ?>
                                            <?php if (!empty($item['asset_description'])): ?>
                                                - <?php echo htmlspecialchars($item['asset_description']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="activity-time"><?php echo $time_display; ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4"></i>
                                <p class="small mt-2 mb-0">No recent asset item updates</p>
                            </div>
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
                    </div>
                    <?php if (!empty($stats['top_categories'])): ?>
                        <?php foreach ($stats['top_categories'] as $category): ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <span class="category-code"><?php echo htmlspecialchars($category['code']); ?></span>
                                    <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                </div>
                                <div class="category-count"><?php echo (int)($category['item_count'] ?? 0); ?> items</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <small>No categories found</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-info-circle"></i> Summary
                    </div>
                    <div class="row text-center g-2">
                        <div class="col-6">
                            <div class="p-2 rounded" style="background: rgba(25, 27, 169, 0.05);">
                                <div class="fw-bold" style="color: #191BA9; font-size: 1.25rem;"><?php echo number_format($stats['serviceable_items']); ?></div>
                                <div class="small text-muted">Serviceable</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded" style="background: rgba(220, 53, 69, 0.05);">
                                <div class="fw-bold text-danger" style="font-size: 1.25rem;"><?php echo number_format($stats['unserviceable_items']); ?></div>
                                <div class="small text-muted">Unserviceable</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-2 rounded" style="background: rgba(40, 167, 69, 0.05);">
                                <div class="fw-bold text-success" style="font-size: 1.25rem;">PHP <?php echo number_format($stats['total_value'], 2); ?></div>
                                <div class="small text-muted">Total Value</div>
                            </div>
                        </div>
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
        (function() {
            const serviceable = <?php echo (int)$stats['serviceable_items']; ?>;
            const unserviceable = <?php echo (int)$stats['unserviceable_items']; ?>;

            const statusCtx = document.getElementById('assetStatusChart');
            if (statusCtx && typeof Chart !== 'undefined') {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Serviceable', 'Unserviceable'],
                        datasets: [{
                            data: [serviceable, unserviceable],
                            backgroundColor: ['rgba(40, 167, 69, 0.8)', 'rgba(220, 53, 69, 0.8)'],
                            borderColor: ['rgba(40, 167, 69, 1)', 'rgba(220, 53, 69, 1)'],
                            borderWidth: 1
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
            }

            const categoryCtx = document.getElementById('categoryChart');
            const categoryDataEl = document.getElementById('categoryData');
            if (categoryCtx && categoryDataEl && typeof Chart !== 'undefined') {
                let categoryData = [];
                try { categoryData = JSON.parse(categoryDataEl.textContent || '[]'); } catch (e) {}

                const labels = categoryData.map(x => x.name);
                const values = categoryData.map(x => x.value);

                new Chart(categoryCtx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Value',
                            data: values,
                            backgroundColor: 'rgba(25, 27, 169, 0.6)',
                            borderColor: 'rgba(25, 27, 169, 1)',
                            borderWidth: 1,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })();
    </script>
</body>
</html>
