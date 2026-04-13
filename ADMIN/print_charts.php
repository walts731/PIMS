<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log analytics export
logSystemAction($_SESSION['user_id'], 'print_charts', 'analytics', 'Printed analytics charts');

// Get system settings for logo
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

// Get analytics data from database
$asset_stats = [];
$employee_stats = [];
$office_stats = [];
$office_asset_data = [];
$category_asset_data = [];
$consumables_data = [];
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
    
    // Office Asset Data
    $office_asset_sql = "SELECT 
        o.office_name,
        COUNT(ai.id) as asset_count,
        SUM(ai.value) as total_value
        FROM offices o
        LEFT JOIN asset_items ai ON o.id = ai.office_id
        WHERE o.status = 'active'
        GROUP BY o.id, o.office_name
        ORDER BY total_value DESC";
    
    $result = $conn->query($office_asset_sql);
    while ($row = $result->fetch_assoc()) {
        $office_asset_data[] = $row;
    }
    
    // Category Asset Data
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
    
    $result = $conn->query($consumables_sql);
    while ($row = $result->fetch_assoc()) {
        $consumables_data[] = $row;
    }
    
    // Monthly Data
    $monthly_sql = "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(CASE WHEN status = 'serviceable' THEN 1 END) as serviceable_monthly,
        COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed_monthly,
        COUNT(CASE WHEN status = 'disposed' THEN 1 END) as disposed_monthly,
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Print Charts - PIMS</title>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        @page {
            size: legal landscape;
            margin: 0.5in;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            color: #000;
        }
        
        @media screen {
            .print-header {
                margin: 0 auto;
                width: 13in;
            }
        }
        
        .preview-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .no-print {
            display: block !important;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .print-header {
                width: 100%;
            }
        }
        
        .print-header {
            text-align: left;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .print-header img {
            max-width: 200px;
            object-fit: contain;
            margin-right: 20px;
        }
        
        .print-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .print-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .gov-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.2;
        }
        
        .municipality {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
        }
        
        .province {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #000;
        }
        
        .charts-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .chart-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-half {
            flex: 1;
            min-width: 45%;
        }
        
        .chart-full {
            width: 100%;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            background: white;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            text-align: center;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="preview-toolbar no-print">
        <div><i class="bi bi-graph-up-arrow me-2"></i>Analytics Charts Preview</div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print Charts</button>
            <button onclick="window.close()" class="btn btn-light btn-sm ms-2">Close</button>
        </div>
    </div>
    
    <div class="print-header">
        <div style="display: flex; align-items: flex-start; gap: 20px;">
            <!-- Logo on the left -->
            <div style="flex-shrink: 0;">
                <?php 
                if (!empty($system_settings['system_logo'])) {
                    echo '<img src="../' . htmlspecialchars($system_settings['system_logo']) . '" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">';
                } else {
                    echo '<img src="../img/system_logo.png" alt="' . htmlspecialchars($system_settings['system_name']) . '" style="max-width: 250px; max-height: 100px;">';
                }
                ?>
            </div>
            
            <!-- Header info on the right -->
            <div style="flex-grow: 1;">
                <div class="gov-title">Republic of the Philippines</div>
                <div class="municipality">Municipality of Pilar</div>
                <div class="province">Province of Sorsogon</div>
                <div class="print-title"><?php echo htmlspecialchars($system_settings['system_name']); ?> - Analytics Charts</div>
                <div class="print-subtitle">Generated on <?php echo date('F j, Y g:i A'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Overview Statistics -->
    <div class="charts-section">
        <div class="section-title">Overview Statistics</div>
        <div class="chart-row">
            <div class="chart-half">
                <div class="chart-title">Asset Status Distribution</div>
                <div class="chart-container">
                    <canvas id="assetStatusChart"></canvas>
                </div>
            </div>
            <div class="chart-half">
                <div class="chart-title">Most Consumed Consumables</div>
                <div class="chart-container">
                    <canvas id="consumablesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Asset Value Charts -->
    <div class="charts-section">
        <div class="section-title">Asset Value Analysis</div>
        <div class="chart-row">
            <div class="chart-full">
                <div class="chart-title">Asset Value by Category</div>
                <div class="chart-container">
                    <canvas id="categoryValueChart"></canvas>
                </div>
            </div>
        </div>
        <div class="chart-row">
            <div class="chart-full">
                <div class="chart-title">Asset Value by Office</div>
                <div class="chart-container">
                    <canvas id="officeValueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Asset Status Distribution Chart
        const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
        new Chart(assetStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Serviceable', 'Unserviceable', 'Maintenance', 'Red Tagged', 'Disposed', 'Borrowed'],
                datasets: [{
                    data: [
                        <?php echo $asset_stats['serviceable_count'] ?? 0; ?>,
                        <?php echo $asset_stats['unserviceable_count'] ?? 0; ?>,
                        <?php echo $asset_stats['maintenance_count'] ?? 0; ?>,
                        <?php echo $asset_stats['red_tagged_count'] ?? 0; ?>,
                        <?php echo $asset_stats['disposed_count'] ?? 0; ?>,
                        <?php echo $asset_stats['borrowed_count'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(52, 211, 153, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(107, 114, 128, 0.8)',
                        'rgba(59, 130, 246, 0.8)'
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
                            color: '#333'
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
                    }
                }
            }
        });
        
        // Most Consumed Consumables Chart
        const consumablesCtx = document.getElementById('consumablesChart').getContext('2d');
        new Chart(consumablesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($consumables_data, 'asset_name')); ?>,
                datasets: [{
                    label: 'Consumption Count',
                    data: <?php echo json_encode(array_column($consumables_data, 'consumption_count')); ?>,
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
                            color: 'rgba(0, 0, 0, 0.1)'
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
                    }
                }
            }
        });
        
        // Asset Value by Category Chart
        const categoryValueCtx = document.getElementById('categoryValueChart').getContext('2d');
        new Chart(categoryValueCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $labels = [];
                    foreach ($category_asset_data as $category) {
                        $labels[] = $category['code'] . ' - ' . $category['name'];
                    }
                    echo json_encode($labels);
                ?>,
                datasets: [{
                    label: 'Asset Value (₱)',
                    data: <?php echo json_encode(array_column($category_asset_data, 'total_value')); ?>,
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
                                return '₱' + value.toLocaleString();
                            },
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
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
                                const value = '₱' + context.parsed.y.toLocaleString();
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Asset Value by Office Chart
        const officeValueCtx = document.getElementById('officeValueChart').getContext('2d');
        new Chart(officeValueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($office_asset_data, 'office_name')); ?>,
                datasets: [{
                    label: 'Asset Value (₱)',
                    data: <?php echo json_encode(array_column($office_asset_data, 'total_value')); ?>,
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
                                return '₱' + value.toLocaleString();
                            },
                            color: '#333',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
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
                                const value = '₱' + context.parsed.y.toLocaleString();
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            }
        });
    // Debug information
        console.log('Asset Stats:', <?php echo json_encode($asset_stats); ?>);
        console.log('Category Data:', <?php echo json_encode($category_asset_data); ?>);
        console.log('Office Data:', <?php echo json_encode($office_asset_data); ?>);
        console.log('Consumables Data:', <?php echo json_encode($consumables_data); ?>);
    </script>
</body>
</html>
