<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'office_fuel_out_analysis', 'Main user accessed office fuel out analysis page');

$office_fuel_data = [];
$error = null;
$period_filter = isset($_GET['period']) ? trim((string)$_GET['period']) : 'month';
$date_from = '';
$date_to = '';

try {
    // Check if required tables exist
    $fuel_out_table_exists = false;
    $fuel_types_table_exists = false;
    
    $check_fuel_out = $conn->query("SHOW TABLES LIKE 'fuel_out'");
    if ($check_fuel_out && $check_fuel_out->num_rows > 0) {
        $fuel_out_table_exists = true;
    }
    
    $check_fuel_types = $conn->query("SHOW TABLES LIKE 'fuel_types'");
    if ($check_fuel_types && $check_fuel_types->num_rows > 0) {
        $fuel_types_table_exists = true;
    }
    
    if (!$fuel_out_table_exists) {
        $error = 'Fuel out table not found. Please contact administrator to set up fuel management tables.';
    } else {
        
        // Calculate date range based on period filter
        switch ($period_filter) {
            case 'today':
                $date_from = date('Y-m-d');
                $date_to = date('Y-m-d');
                break;
            case 'week':
                $date_from = date('Y-m-d', strtotime('-7 days'));
                $date_to = date('Y-m-d');
                break;
            case 'month':
                $date_from = date('Y-m-d', strtotime('-30 days'));
                $date_to = date('Y-m-d');
                break;
            case 'year':
                $date_from = date('Y-01-01');
                $date_to = date('Y-m-d');
                break;
            default:
                $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : date('Y-m-01', strtotime('-3 months'));
                $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : date('Y-m-d');
                break;
        }
        
        // Get offices only - exclude branches
        $all_offices = [];
        
        // 1. Get offices from fuel_out table first
        $fuel_out_offices = "SELECT DISTINCT office_name FROM fuel_out WHERE office_name IS NOT NULL AND office_name != ''";
        $result = $conn->query($fuel_out_offices);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $office_name = $row['office_name'];
                // Filter out branches - only include offices
                if (stripos($office_name, 'branch') === false && 
                    stripos($office_name, 'satellite') === false && 
                    stripos($office_name, 'field') === false &&
                    stripos($office_name, 'regional') === false &&
                    stripos($office_name, 'district') === false) {
                    $all_offices[] = $office_name;
                }
            }
        }
        
        // 2. Check for offices table only - exclude branches, locations, departments
        $office_tables = ['offices', 'office'];
        foreach ($office_tables as $table) {
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check && $check->num_rows > 0) {
                // Try different column names
                $columns = ['office_name', 'name', 'office'];
                foreach ($columns as $col) {
                    $col_check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                    if ($col_check && $col_check->num_rows > 0) {
                        $office_query = "SELECT DISTINCT `$col` FROM `$table` WHERE `$col` IS NOT NULL AND `$col` != ''";
                        $office_result = $conn->query($office_query);
                        if ($office_result) {
                            while ($row = $office_result->fetch_assoc()) {
                                $office_name = $row[$col];
                                // Filter out branches - only include offices
                                if ($office_name && 
                                    stripos($office_name, 'branch') === false && 
                                    stripos($office_name, 'satellite') === false && 
                                    stripos($office_name, 'field') === false &&
                                    stripos($office_name, 'regional') === false &&
                                    stripos($office_name, 'district') === false &&
                                    !in_array($office_name, $all_offices)) {
                                    $all_offices[] = $office_name;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // 3. Add common office names if still empty
        if (empty($all_offices)) {
            $all_offices = [
                'Main Office', 'Head Office', 'Corporate Office', 
                'Central Office', 'Administrative Office'
            ];
        }
        
        // Build dynamic SQL for all offices
        if (count($all_offices) > 0) {
            $union_parts = [];
            $params = [];
            $types = '';
            
            foreach ($all_offices as $office) {
                $union_parts[] = '?';
                $params[] = $office;
                $types .= 's';
            }
            
            // Add date parameters
            $params[] = $date_from;
            $params[] = $date_to;
            $types .= 'ss';
            
            $union_select = 'SELECT ' . implode(' as office_name UNION SELECT ', $union_parts) . ' as office_name';
            
            $sql = "SELECT 
                        offices.office_name,
                        COUNT(fo.fo_liters) as transaction_count,
                        COALESCE(SUM(fo.fo_liters), 0) as total_fuel_out,
                        COALESCE(AVG(fo.fo_liters), 0) as avg_fuel_per_transaction,
                        COALESCE(MIN(fo.fo_liters), 0) as min_fuel_out,
                        COALESCE(MAX(fo.fo_liters), 0) as max_fuel_out,
                        MIN(DATE(fo.fo_date)) as first_transaction,
                        MAX(DATE(fo.fo_date)) as last_transaction
                    FROM ($union_select) offices
                    LEFT JOIN fuel_out fo ON offices.office_name = fo.office_name AND DATE(fo.fo_date) BETWEEN ? AND ?
                    GROUP BY offices.office_name
                    ORDER BY total_fuel_out DESC";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $office_fuel_data[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Get fuel type breakdown for each office
        if ($fuel_types_table_exists && !empty($office_fuel_data)) {
            foreach ($office_fuel_data as &$office) {
                if ($office['transaction_count'] > 0) {
                    $fuel_type_sql = "SELECT 
                                        ft.name as fuel_type,
                                        SUM(fo.fo_liters) as fuel_amount,
                                        COUNT(*) as transaction_count
                                      FROM fuel_out fo
                                      LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
                                      WHERE fo.office_name = ? AND DATE(fo.fo_date) BETWEEN ? AND ?
                                      GROUP BY ft.name
                                      ORDER BY fuel_amount DESC";
                    
                    $fuel_stmt = $conn->prepare($fuel_type_sql);
                    if ($fuel_stmt) {
                        $fuel_stmt->bind_param("sss", $office['office_name'], $date_from, $date_to);
                        $fuel_stmt->execute();
                        $fuel_result = $fuel_stmt->get_result();
                        
                        $office['fuel_types'] = [];
                        while ($fuel_row = $fuel_result->fetch_assoc()) {
                            $office['fuel_types'][] = $fuel_row;
                        }
                        $fuel_stmt->close();
                    }
                } else {
                    $office['fuel_types'] = [];
                }
            }
        }
    }
} catch (Exception $e) {
    $error = 'An error occurred while fetching office fuel out data: ' . $e->getMessage();
}

// Calculate totals
$total_fuel_out_all = array_sum(array_column($office_fuel_data, 'total_fuel_out'));
$total_transactions_all = array_sum(array_column($office_fuel_data, 'transaction_count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Fuel Out Analysis - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Global smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            min-height: 100vh;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            border-radius: 0;
            animation: slideUp 0.8s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            animation: slideUp 0.6s ease-out 0.2s both;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        .header-section {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            animation: slideDown 0.8s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .office-card {
            background: white;
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 0.75rem;
            border-left: 4px solid #ff6b6b;
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }
        .office-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        .office-card.rank-1 {
            border-left-color: #ffd700;
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
        }
        .office-card.rank-2 {
            border-left-color: #c0c0c0;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }
        .office-card.rank-3 {
            border-left-color: #cd7f32;
            background: linear-gradient(135deg, #fff4e6 0%, #ffffff 100%);
        }
        .rank-badge {
            font-size: 1.2rem;
            font-weight: bold;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .rank-1 .rank-badge {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
        }
        .rank-2 .rank-badge {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            color: #333;
        }
        .rank-3 .rank-badge {
            background: linear-gradient(135deg, #cd7f32, #daa520);
        }
        .fuel-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff6b6b;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        .fuel-type-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            margin: 0.05rem;
        }
        .progress-custom {
            height: 6px;
            border-radius: 3px;
            background-color: #e9ecef;
        }
        .progress-bar-custom {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            border-radius: 3px;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        h5 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        h6 {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            animation: slideUp 0.6s ease-out 0.4s both;
        }
        .small {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="bi bi-building me-3"></i>
                        Office Fuel Out Analysis
                    </h1>
                    <p class="mb-0 opacity-75">Offices ranked by total fuel consumption (highest to lowest)</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="fuel_management.php" class="btn btn-light btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Fuel Management
                    </a>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section mb-2">
            <div class="row align-items-end">
                <div class="col-md-12">
                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                        <div>
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-calendar-range me-1"></i>Time Period
                            </label>
                            <div class="btn-group" role="group">
                                <a href="?period=today" class="btn btn-<?php echo $period_filter === 'today' ? 'warning' : 'outline-warning'; ?> btn-sm">
                                    <i class="bi bi-calendar-day me-1"></i>Today
                                </a>
                                <a href="?period=week" class="btn btn-<?php echo $period_filter === 'week' ? 'warning' : 'outline-warning'; ?> btn-sm">
                                    <i class="bi bi-calendar-week me-1"></i>Week
                                </a>
                                <a href="?period=month" class="btn btn-<?php echo $period_filter === 'month' ? 'warning' : 'outline-warning'; ?> btn-sm">
                                    <i class="bi bi-calendar-month me-1"></i>Month
                                </a>
                                <a href="?period=year" class="btn btn-<?php echo $period_filter === 'year' ? 'warning' : 'outline-warning'; ?> btn-sm">
                                    <i class="bi bi-calendar-year me-1"></i>Year
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="text-muted">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            Showing data from <strong><?php echo date('M d, Y', strtotime($date_from)); ?></strong> 
                            to <strong><?php echo date('M d, Y', strtotime($date_to)); ?></strong>
                            <?php if ($period_filter !== 'custom' && isset($_GET['period'])): ?>
                                <span class="badge bg-warning ms-2"><?php echo ucfirst($period_filter); ?></span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-2">
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <i class="bi bi-building" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Offices</h6>
                            <h4 class="mb-0"><?php echo count($office_fuel_data); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <i class="bi bi-fuel-pump" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Fuel Out</h6>
                            <h4 class="mb-0"><?php echo number_format($total_fuel_out_all, 2); ?> L</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <i class="bi bi-list-check" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Transactions</h6>
                            <h4 class="mb-0"><?php echo number_format($total_transactions_all); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Office Rankings -->
        <div class="row">
            <div class="col-12">
                <div class="stats-card">
                    <h5 class="mb-2">
                        <i class="bi bi-trophy text-warning me-2"></i>
                        Office Fuel Consumption Rankings
                    </h5>
                    
                    <?php if (!empty($office_fuel_data)): ?>
                        <?php foreach ($office_fuel_data as $index => $office): ?>
                            <?php 
                            $rank = $index + 1;
                            $rank_class = $rank <= 3 ? "rank-{$rank}" : '';
                            $percentage = $total_fuel_out_all > 0 ? ($office['total_fuel_out'] / $total_fuel_out_all) * 100 : 0;
                            ?>
                            <div class="office-card <?php echo $rank_class; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-1 text-center">
                                        <div class="rank-badge">
                                            <?php if ($rank == 1): ?>
                                                <i class="bi bi-trophy-fill"></i>
                                            <?php elseif ($rank == 2): ?>
                                                <i class="bi bi-award-fill"></i>
                                            <?php elseif ($rank == 3): ?>
                                                <i class="bi bi-award"></i>
                                            <?php else: ?>
                                                <?php echo $rank; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($office['office_name']); ?></h6>
                                        <small class="text-muted">Office: <?php echo htmlspecialchars($office['office_name']); ?></small>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="fuel-amount <?php echo $office['total_fuel_out'] == 0 ? 'text-muted' : ''; ?>">
                                            <?php echo number_format($office['total_fuel_out'], 2); ?>
                                        </div>
                                        <small class="text-muted">Liters</small>
                                        <?php if ($office['total_fuel_out'] == 0): ?>
                                            <div><small class="badge bg-secondary">No Activity</small></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-1">
                                            <strong><?php echo number_format($percentage, 1); ?>%</strong>
                                            <small class="text-muted"> of total</small>
                                        </div>
                                        <div class="progress-custom">
                                            <div class="progress-bar-custom" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-1">
                                            <i class="bi bi-list-check text-muted me-1"></i>
                                            <strong><?php echo number_format($office['transaction_count']); ?></strong>
                                            <small class="text-muted"> trx</small>
                                        </div>
                                        <div>
                                            <i class="bi bi-calculator text-muted me-1"></i>
                                            <strong><?php echo $office['transaction_count'] > 0 ? number_format($office['avg_fuel_per_transaction'], 2) : '0.00'; ?></strong>
                                            <small class="text-muted"> avg</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-1">
                                            <small class="text-muted">Range:</small><br>
                                            <strong><?php echo $office['transaction_count'] > 0 ? number_format($office['min_fuel_out'], 2) : '0.00'; ?>L - <?php echo $office['transaction_count'] > 0 ? number_format($office['max_fuel_out'], 2) : '0.00'; ?>L</strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($office['fuel_types'])): ?>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <small class="text-muted">Fuel Types:</small>
                                            <div class="mt-1">
                                                <?php foreach ($office['fuel_types'] as $fuel_type): ?>
                                                    <span class="badge bg-info fuel-type-badge">
                                                        <?php echo htmlspecialchars($fuel_type['fuel_type'] ?: 'Unknown'); ?>: 
                                                        <?php echo number_format($fuel_type['fuel_amount'], 2); ?>L 
                                                        (<?php echo $fuel_type['transaction_count']; ?> trx)
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-building text-muted" style="font-size: 2rem;"></i>
                            <h6 class="text-muted mt-2">No Office Fuel Data Found</h6>
                            <p class="text-muted small">No fuel out transactions found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
