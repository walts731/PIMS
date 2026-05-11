<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log analytics page access
logSystemAction($_SESSION['user_id'], 'analytics_accessed', 'analytics', 'Accessed advanced analytics page');

// Get analytics data from database
$asset_stats = [];
$employee_stats = [];
$office_stats = [];
$monthly_data = [];

try {
    // Asset Statistics
    $asset_summary_sql = "SELECT 
        COUNT(*) as total_items,
        SUM(value) as total_value,
        COUNT(CASE WHEN status = 'serviceable' THEN 1 END) as serviceable_count,
        SUM(CASE WHEN status = 'serviceable' THEN value ELSE 0 END) as serviceable_value,
        COUNT(CASE WHEN status = 'unserviceable' THEN 1 END) as unserviceable_count,
        SUM(CASE WHEN status = 'unserviceable' THEN value ELSE 0 END) as unserviceable_value,
        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_count,
        SUM(CASE WHEN status = 'maintenance' THEN value ELSE 0 END) as maintenance_value,
        COUNT(CASE WHEN status = 'red_tagged' THEN 1 END) as red_tagged_count,
        SUM(CASE WHEN status = 'red_tagged' THEN value ELSE 0 END) as red_tagged_value,
        COUNT(CASE WHEN status = 'disposed' THEN 1 END) as disposed_count,
        SUM(CASE WHEN status = 'disposed' THEN value ELSE 0 END) as disposed_value,
        COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed_count,
        SUM(CASE WHEN status = 'borrowed' THEN value ELSE 0 END) as borrowed_value,
        COUNT(CASE WHEN status = 'no_tag' THEN 1 END) as no_tag_count,
        COUNT(CASE WHEN office_id IS NOT NULL THEN 1 END) as assigned_count,
        COUNT(CASE WHEN office_id IS NULL THEN 1 END) as unassigned_count
        FROM asset_items";
    
    $result = $conn->query($asset_summary_sql);
    if ($row = $result->fetch_assoc()) {
        $asset_stats = $row;
    }
    
    // Employee Statistics
    $employee_summary_sql = "SELECT 
        COUNT(*) as total_employees,
        COUNT(CASE WHEN employment_status = 'permanent' THEN 1 END) as permanent_count,
        COUNT(CASE WHEN employment_status = 'contractual' THEN 1 END) as contractual_count,
        COUNT(CASE WHEN employment_status = 'job_order' THEN 1 END) as job_order_count,
        COUNT(CASE WHEN employment_status = 'resigned' THEN 1 END) as resigned_count,
        COUNT(CASE WHEN employment_status = 'retired' THEN 1 END) as retired_count,
        COUNT(CASE WHEN clearance_status = 'cleared' THEN 1 END) as cleared_count,
        COUNT(CASE WHEN clearance_status = 'uncleared' THEN 1 END) as uncleared_count,
        COUNT(CASE WHEN office_id IS NOT NULL THEN 1 END) as assigned_employees
        FROM employees";
    
    $result = $conn->query($employee_summary_sql);
    if ($row = $result->fetch_assoc()) {
        $employee_stats = $row;
    }
    
    // Office Statistics
    $office_stats = [];
    $total_offices = 0;
    
    $office_summary_sql = "SELECT 
        COUNT(*) as total_offices,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_offices,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_offices,
        SUM(capacity) as total_capacity
        FROM offices";
    
    $result = $conn->query($office_summary_sql);
    if ($row = $result->fetch_assoc()) {
        $office_stats = $row;
    }
    
    // Office Asset Data for Chart
    $office_asset_sql = "SELECT 
        o.office_name,
        COUNT(ai.id) as asset_count,
        SUM(ai.value) as total_value
        FROM offices o
        LEFT JOIN asset_items ai ON o.id = ai.office_id
        WHERE o.status = 'active' AND o.office_code NOT LIKE 'L%' AND o.office_code NOT LIKE 'B%'
        GROUP BY o.id, o.office_name
        ORDER BY total_value DESC";
    
    $office_asset_data = [];
    $result = $conn->query($office_asset_sql);
    while ($row = $result->fetch_assoc()) {
        $office_asset_data[] = $row;
    }
    
    // Special Office Asset Data (L and B) for Chart
    $special_office_asset_sql = "SELECT 
        o.office_name,
        COUNT(ai.id) as asset_count,
        SUM(ai.value) as total_value
        FROM offices o
        LEFT JOIN asset_items ai ON o.id = ai.office_id
        WHERE o.status = 'active' AND (o.office_code LIKE 'L%' OR o.office_code LIKE 'B%')
        GROUP BY o.id, o.office_name
        ORDER BY total_value DESC";
    
    $special_office_asset_data = [];
    $result = $conn->query($special_office_asset_sql);
    while ($row = $result->fetch_assoc()) {
        $special_office_asset_data[] = $row;
    }
    
    // Category Asset Data for Chart
    $category_asset_sql = "SELECT 
        ac.id, ac.category_code as code, ac.category_name as name,
        COUNT(ai.id) as item_count,
        COUNT(DISTINCT ai.asset_id) as asset_count,
        SUM(ai.value) as total_value
        FROM asset_categories ac
        LEFT JOIN assets a ON ac.id = a.asset_categories_id
        LEFT JOIN asset_items ai ON a.id = ai.asset_id
        GROUP BY ac.id, ac.category_code, ac.category_name
        ORDER BY total_value DESC
        LIMIT 10";
    
    $category_asset_data = [];
    $result = $conn->query($category_asset_sql);
    while ($row = $result->fetch_assoc()) {
        $category_asset_data[] = $row;
    }
    
    // Most Consumed Consumables Data
    $consumables_sql = "SELECT 
        a.asset_name,
        COUNT(ai.id) as consumption_count,
        SUM(ai.value) as total_consumed_value
        FROM asset_items ai
        JOIN assets a ON ai.asset_id = a.id
        WHERE a.asset_type = 'consumable' 
        AND ai.status = 'disposed'
        GROUP BY a.asset_name
        ORDER BY consumption_count DESC
        LIMIT 10";
    
    $consumables_data = [];
    $result = $conn->query($consumables_sql);
    while ($row = $result->fetch_assoc()) {
        $consumables_data[] = $row;
    }
    
    // Monthly Data for Charts
    $monthly_sql = "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(CASE WHEN status = 'serviceable' THEN 1 END) as serviceable_monthly,
        COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed_monthly,
        COUNT(CASE WHEN status = 'disposed' THEN 1 END) as disposed_monthly
        SUM(CASE WHEN status = 'serviceable' THEN value ELSE 0 END) as serviceable_value_monthly,
        SUM(CASE WHEN status = 'borrowed' THEN value ELSE 0 END) as borrowed_value_monthly
        FROM asset_items 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month";
    
    $result = $conn->query($monthly_sql);
    while ($row = $result->fetch_assoc()) {
        $monthly_data[] = $row;
    }
    
} catch (Exception $e) {
    error_log("Error loading analytics data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics - PIMS</title>
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    
    <style>
        .analytics-container {
            padding: 20px;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .chart-card h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .page-header h1,
        .page-header p {
            color: #333;
        }
        
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .dropdown-item {
            color: #333;
        }
        
        .dropdown-item:hover {
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .btn-primary {
            background: rgba(0, 123, 255, 0.8);
            border: 1px solid rgba(0, 123, 255, 0.9);
            color: #fff;
        }
        
        .btn-primary:hover {
            background: rgba(0, 123, 255, 0.9);
            border: 1px solid rgba(0, 123, 255, 1);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            display: none;
            min-width: 10rem;
            padding: 0.5rem 0;
            margin: 0.125rem 0 0;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0,0,0,.15);
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.25rem 1.5rem;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            cursor: pointer;
        }
        
        .dropdown-item:hover {
            color: #1e2125;
            background-color: #e9ecef;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .back-link {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            z-index: 1000;
        }
        
        .back-link:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
    </style>
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php $page_title = 'Advanced Analytics'; ?>
    
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
                        <i class="bi bi-graph-up-arrow"></i> Advanced Analytics
                    </h1>
                    <p class="text-muted mb-0">Comprehensive data visualization and insights</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary-custom dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="exportAnalyticsData()">
                                    <i class="bi bi-download"></i> Export Data
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="window.location.href='reports.php'">
                                    <i class="bi bi-arrow-left"></i> Back to Reports
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analytics Dashboard -->
        <div class="analytics-container">
            
            <!-- Charts Section -->
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5><i class="bi bi-pie-chart"></i> Asset Status Distribution</h5>
                        <div class="chart-container">
                            <canvas id="assetStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5><i class="bi bi-box-seam"></i> Most Consumed Consumables</h5>
                        <div class="chart-container">
                            <canvas id="consumablesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Asset Value by Category -->
            <div class="row">
                <div class="col-md-12">
                    <div class="chart-card">
                        <h5><i class="bi bi-tags"></i> Asset Value by Category</h5>
                        <div class="chart-container">
                            <canvas id="categoryValueChart"></canvas>
                        </div>
                    </div>
                </div>
            
            <!-- Asset Value by Office (Bar) -->
            <div class="row">
                <div class="col-md-12">
                    <div class="chart-card">
                        <h5><i class="bi bi-bar-chart"></i> Asset Value by Office Breakdown</h5>
                        <div class="chart-container">
                            <canvas id="officePieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Value by Special Office (L & B) -->
            <div class="row">
                <div class="col-md-12">
                    <div class="chart-card">
                        <h5><i class="bi bi-bar-chart-steps"></i> Location Assets Breakdown </h5>
                        <div class="chart-container">
                            <canvas id="specialOfficeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
        
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Register Chart.js datalabels plugin
        Chart.register(ChartDataLabels);
        
        // Initialize dropdowns after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Simple dropdown toggle
            const dropdownBtn = document.getElementById('actionsDropdown');
            const dropdownMenu = dropdownBtn.nextElementSibling;
            
            dropdownBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                dropdownMenu.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
        
        // Quick Stats Data
        <?php
        // Prepare asset stats data
        $asset_data = [
            'totalItems' => (int)($asset_stats['total_items'] ?? 0),
            'totalValue' => (float)($asset_stats['total_value'] ?? 0),
            'serviceable' => (int)($asset_stats['serviceable_count'] ?? 0),
            'unserviceable' => (int)($asset_stats['unserviceable_count'] ?? 0),
            'maintenance' => (int)($asset_stats['maintenance_count'] ?? 0),
            'redTagged' => (int)($asset_stats['red_tagged_count'] ?? 0),
            'disposed' => (int)($asset_stats['disposed_count'] ?? 0),
            'borrowed' => (int)($asset_stats['borrowed_count'] ?? 0)
        ];
        
        // Prepare employee stats data
        $employee_data = [
            'total' => (int)($employee_stats['total_employees'] ?? 0),
            'cleared' => (int)($employee_stats['cleared_count'] ?? 0),
            'uncleared' => (int)($employee_stats['uncleared_count'] ?? 0)
        ];
        
        // Prepare office stats data
        $office_data = [
            'total' => (int)($office_stats['total_offices'] ?? 0),
            'active' => (int)($office_stats['active_offices'] ?? 0),
            'inactive' => (int)($office_stats['inactive_offices'] ?? 0)
        ];
        ?>
        
        const assetStats = <?php echo json_encode($asset_data); ?>;
        const employeeStats = <?php echo json_encode($employee_data); ?>;
        const officeStats = <?php echo json_encode($office_data); ?>;
        
        // Asset Status Distribution Chart
        const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
        new Chart(assetStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Serviceable', 'Unserviceable', 'Maintenance', 'Red Tagged', 'Disposed', 'Borrowed'],
                datasets: [{
                    data: [
                        assetStats.serviceable,
                        assetStats.unserviceable,
                        assetStats.maintenance,
                        assetStats.redTagged,
                        assetStats.disposed,
                        assetStats.borrowed
                    ],
                    backgroundColor: [
                        'rgba(52, 211, 153, 0.8)',  // Emerald glass
                        'rgba(251, 191, 36, 0.8)',   // Amber glass
                        'rgba(251, 146, 60, 0.8)',   // Orange glass
                        'rgba(239, 68, 68, 0.8)',    // Red glass
                        'rgba(107, 114, 128, 0.8)',  // Gray glass
                        'rgba(59, 130, 246, 0.8)'    // Blue glass
                    ],
                    borderColor: [
                        'rgba(52, 211, 153, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(251, 146, 60, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(107, 114, 128, 1)',
                        'rgba(59, 130, 246, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#333',
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            }
        });
        
        // Asset Value by Office Bar Chart
        <?php
        // Prepare office asset data for chart
        $office_labels = [];
        $office_values = [];
        foreach ($office_asset_data as $office) {
            $office_labels[] = $office['office_name'];
            $office_values[] = $office['total_value'];
        }
        ?>
        
        // Asset Value by Category Chart
        <?php
        // Prepare category asset data for chart
        $category_labels = [];
        $category_values = [];
        foreach ($category_asset_data as $category) {
            $category_labels[] = $category['code'] . ' - ' . $category['name'];
            $category_values[] = $category['total_value'];
        }
        ?>
        
        const categoryValueCtx = document.getElementById('categoryValueChart').getContext('2d');
        new Chart(categoryValueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    label: 'Asset Value by Category (P)',
                    data: <?php echo json_encode($category_values); ?>,
                    backgroundColor: 'rgba(52, 211, 153, 0.8)',
                    borderColor: 'rgba(52, 211, 153, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'P' + value.toLocaleString();
                            },
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = 'P' + context.parsed.y.toLocaleString();
                                return label + ': ' + value;
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            }
        });
        
        // Asset Value by Office Bar Chart
        const officePieCtx = document.getElementById('officePieChart').getContext('2d');
        new Chart(officePieCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($office_labels); ?>,
                datasets: [{
                    label: 'Asset Value by Office (P)',
                    data: <?php echo json_encode($office_values); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'P' + value.toLocaleString();
                            },
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = 'P' + context.parsed.y.toLocaleString();
                                return label + ': ' + value;
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            }
        });

        // Asset Value by Special Office (L & B) Chart
        <?php
        $special_office_labels = [];
        $special_office_values = [];
        foreach ($special_office_asset_data as $office) {
            $special_office_labels[] = $office['office_name'];
            $special_office_values[] = $office['total_value'];
        }
        ?>
        
        const specialOfficeCtx = document.getElementById('specialOfficeChart').getContext('2d');
        new Chart(specialOfficeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($special_office_labels); ?>,
                datasets: [{
                    label: 'Asset Value (P)',
                    data: <?php echo json_encode($special_office_values); ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)', // Purple glass
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'P' + value.toLocaleString();
                            },
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = 'P' + context.parsed.y.toLocaleString();
                                return label + ': ' + value;
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            }
        });
        
        // Most Consumed Consumables Chart
        <?php
        // Prepare consumables data for chart
        $consumables_labels = [];
        $consumables_counts = [];
        foreach ($consumables_data as $consumable) {
            $consumables_labels[] = $consumable['asset_name'];
            $consumables_counts[] = $consumable['consumption_count'];
        }
        ?>
        
        const consumablesCtx = document.getElementById('consumablesChart').getContext('2d');
        new Chart(consumablesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($consumables_labels); ?>,
                datasets: [{
                    label: 'Consumption Count',
                    data: <?php echo json_encode($consumables_counts); ?>,
                    backgroundColor: 'rgba(251, 146, 60, 0.8)',
                    borderColor: 'rgba(251, 146, 60, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y;
                                return label + ': ' + value + ' items';
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            }
        });
        
        // Export Analytics Data
        function exportAnalyticsData() {
            // Redirect to print charts page
            window.open('print_charts.php', '_blank');
        }
    </script>
    
    </div><!-- End Main Content -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    </div><!-- End Main Wrapper -->
    </body>
</html>
