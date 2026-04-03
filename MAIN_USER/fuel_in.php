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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_in', 'Main user accessed fuel IN page');

// Get filter parameters
$fuel_type_filter = isset($_GET['fuel_type']) ? trim((string)$_GET['fuel_type']) : '';
$period_filter = isset($_GET['period']) ? trim((string)$_GET['period']) : 'all';



// Calculate date range based on period filter
$date_condition = "";
$date_params = [];
$date_types = "";

switch ($period_filter) {
    case 'today':
        $date_condition = " AND DATE(ft.transaction_date) = CURDATE()";
        break;
    case 'week':
        $date_condition = " AND ft.transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = " AND ft.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $date_condition = " AND ft.transaction_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        break;
    default:
        $date_condition = "";
        break;
}

// Create date condition for summary query (without ft alias)
$summary_date_condition = "";
switch ($period_filter) {
    case 'today':
        $summary_date_condition = " AND DATE(transaction_date) = CURDATE()";
        break;
    case 'week':
        $summary_date_condition = " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $summary_date_condition = " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $summary_date_condition = " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        break;
    default:
        $summary_date_condition = "";
        break;
}



// Get recent fuel in transactions from fuel_in table
$fuel_in_query = "SELECT fi.*, u.first_name, u.last_name 
                 FROM fuel_in fi 
                 LEFT JOIN users u ON fi.created_by = u.id";

// Add filters to query
$where_conditions = [];

if (!empty($fuel_type_filter)) {
    // Map fuel type filter to integer ID based on fuel_in table structure
    $fuel_type_mapping = [
        'diesel' => 1,
        'gasoline' => 2, 
        'premium' => 3
    ];
    if (isset($fuel_type_mapping[$fuel_type_filter])) {
        $where_conditions[] = "fi.fuel_type = " . $fuel_type_mapping[$fuel_type_filter];
    }
}

if (!empty($date_condition)) {
    // Adjust date condition for fuel_in table (date_time field instead of transaction_date)
    $fuel_in_date_condition = str_replace('ft.transaction_date', 'fi.date_time', $date_condition);
    $where_conditions[] = substr($fuel_in_date_condition, 5); // Remove " AND " prefix
}

if (!empty($where_conditions)) {
    $fuel_in_query .= " WHERE " . implode(" AND ", $where_conditions);
}

$fuel_in_query .= " ORDER BY fi.date_time DESC 
                 LIMIT 50";

// Debug: Log the query for troubleshooting
error_log('Fuel IN Query: ' . $fuel_in_query);
error_log('Fuel Type Filter: ' . $fuel_type_filter);
error_log('Period Filter: ' . $period_filter);

$fuel_in_result = $conn->query($fuel_in_query);



$today_fuel_in_query = "SELECT fuel_type, SUM(quantity) as total_quantity 
                        FROM fuel_in";

// Add fuel type filter to summary if selected
if (!empty($fuel_type_filter)) {
    $fuel_type_mapping = [
        'diesel' => 1,
        'gasoline' => 2, 
        'premium' => 3
    ];
    if (isset($fuel_type_mapping[$fuel_type_filter])) {
        $today_fuel_in_query .= " WHERE fuel_type = " . $fuel_type_mapping[$fuel_type_filter];
    }
}

// Add date condition to summary (adjust for date_time field)
if (!empty($summary_date_condition)) {
    $fuel_in_summary_date_condition = str_replace('transaction_date', 'date_time', $summary_date_condition);
    if (empty($fuel_type_filter)) {
        $today_fuel_in_query .= " WHERE " . substr($fuel_in_summary_date_condition, 5); // Remove " AND " prefix
    } else {
        $today_fuel_in_query .= $fuel_in_summary_date_condition;
    }
}

$today_fuel_in_query .= " GROUP BY fuel_type";

// Debug: Log the summary query
error_log('Summary Query: ' . $today_fuel_in_query);

$today_fuel_in_result = $conn->query($today_fuel_in_query);



// Get fuel types for dropdown (using enum values)
$fuel_types = [
    ['id' => 'diesel', 'name' => 'Diesel'],
    ['id' => 'gasoline', 'name' => 'Gasoline'],
    ['id' => 'premium', 'name' => 'Premium']
];



// Get total fuel IN from fuel_in table (primary source)
$total_fuel_in_all = 0;

// Check which fuel tables exist
$fuel_tables = ['fuel_in', 'fuel_transactions'];
$existing_tables = [];

foreach ($fuel_tables as $table) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check_table && $check_table->num_rows > 0) {
        $existing_tables[] = $table;
    }
}

if (in_array('fuel_in', $existing_tables)) {
    $total_in_query = "SELECT SUM(quantity) as total FROM fuel_in";
    
    // Add fuel type filter to total calculation if selected
    if (!empty($fuel_type_filter)) {
        $fuel_type_mapping = [
            'diesel' => 1,
            'gasoline' => 2, 
            'premium' => 3
        ];
        if (isset($fuel_type_mapping[$fuel_type_filter])) {
            $total_in_query .= " WHERE fuel_type = " . $fuel_type_mapping[$fuel_type_filter];
        }
    }
    
    // Add period filter to total calculation if not 'all'
    if ($period_filter !== 'all' && !empty($summary_date_condition)) {
        $fuel_in_total_date_condition = str_replace('transaction_date', 'date_time', $summary_date_condition);
        if (empty($fuel_type_filter)) {
            $total_in_query .= " WHERE " . substr($fuel_in_total_date_condition, 5); // Remove " AND " prefix
        } else {
            $total_in_query .= $fuel_in_total_date_condition;
        }
    }
    
    $total_in_result = $conn->query($total_in_query);
    
    // Debug: Log the total query
    error_log('Total Query: ' . $total_in_query);
    
    if ($total_in_result && $row = $total_in_result->fetch_assoc()) {
        $total_fuel_in_all = $row['total'] ?? 0;
    }
}

// If no fuel_in table or no results, get total from fuel_transactions as fallback
if (empty($total_fuel_in_all) && in_array('fuel_transactions', $existing_tables)) {
    $total_in_trans_query = "SELECT SUM(quantity) as total FROM fuel_transactions WHERE transaction_type = 'IN'";
    
    // Add fuel type filter to total calculation if selected
    if (!empty($fuel_type_filter)) {
        $total_in_trans_query .= " AND fuel_type = '" . $conn->real_escape_string($fuel_type_filter) . "'";
    }
    
    // Add period filter to total calculation if not 'all'
    if ($period_filter !== 'all' && !empty($summary_date_condition)) {
        $total_in_trans_query .= $summary_date_condition;
    }
    
    $total_in_trans_result = $conn->query($total_in_trans_query);
    
    // Debug: Log the fallback query
    error_log('Fallback Total Query: ' . $total_in_trans_query);
    
    if ($total_in_trans_result && $row = $total_in_trans_result->fetch_assoc()) {
        $total_fuel_in_all = $row['total'] ?? 0;
    }
}

// Calculate total transactions for display

$total_transactions = 0;

if ($fuel_in_result) {

    $total_transactions = $fuel_in_result->num_rows;

}

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fuel IN Records - Main User | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
    .section-card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        overflow: hidden;
        margin-bottom: 2rem;
        animation: slideDown 0.8s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fuel-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        animation: bounceIn 0.8s ease-out 0.5s both;
    }
    
    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .table-responsive {
        border-radius: 20px;
        overflow: hidden;
    }
    
    /* Mobile UI Fixes */
    @media (max-width: 992px) {
        .dashboard-header .row {
            flex-direction: column;
            gap: 1rem;
        }
        
        .dashboard-header .col-md-4 {
            text-align: left !important;
        }
        
        .dashboard-header h1 {
            font-size: 1.75rem;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-header h1 {
            font-size: 1.5rem;
        }
        
        .dashboard-header p {
            font-size: 0.9rem;
        }
        
        .dashboard-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
            gap: 0.5rem;
        }
        
        .dashboard-header .d-inline-block {
            width: 100% !important;
        }
    }
    </style>


</head>

<body>
    <?php $page_title = 'Fuel IN Records'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-arrow-down-circle me-2"></i>Fuel IN Records
                        </h1>
                        <p class="text-muted mb-0">Detailed view of all fuel IN transactions</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="fuel_management.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>



        <!-- Page Actions -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h4 class="mb-0">Fuel In Transactions</h4>

            </div>



            <!-- Statistics Summary -->
            <div class="section-card">
                <div class="d-flex align-items-center">
                    <div class="fuel-icon fuel-in-icon me-3" style="background: linear-gradient(135deg, #191BA9, #0d6efd);">
                        <i class="bi bi-arrow-down-circle text-white"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-2">Total Fuel IN</h6>
                        <h3 class="mb-0 text-success">
                            <?php echo number_format($total_fuel_in_all, 2); ?>
                            <small>Liters</small>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="section-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div>
                                <label class="form-label fw-semibold mb-1">Fuel Type:</label>
                                <select class="form-select form-select-sm" name="fuel_type" onchange="window.location.href='?fuel_type='+this.value+'&period=<?php echo urlencode($period_filter); ?>'">
                                    <option value="">All Types</option>
                                    <option value="diesel" <?php echo $fuel_type_filter === 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                                    <option value="gasoline" <?php echo $fuel_type_filter === 'gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                                    <option value="premium" <?php echo $fuel_type_filter === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label fw-semibold mb-1">Period:</label>
                                <select class="form-select form-select-sm" name="period" onchange="window.location.href='?period='+this.value+'&fuel_type=<?php echo urlencode($fuel_type_filter); ?>'">
                                    <option value="all" <?php echo $period_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                    <option value="today" <?php echo $period_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $period_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="month" <?php echo $period_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="year" <?php echo $period_filter === 'year' ? 'selected' : ''; ?>>Last Year</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="fuel_in.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Reset Filters
                        </a>
                    </div>
                </div>
            </div>

            <!-- Fuel Transactions Table -->
            <div class="section-card">
            <h4 class="mb-3">
                <i class="bi bi-arrow-down-circle text-success me-2"></i>
                Fuel IN Transaction History
                <span class="badge bg-success text-white ms-2">
                    <?php echo $fuel_in_result ? $fuel_in_result->num_rows : 0; ?> Records
                </span>
            </h4>
            
            <?php if ($fuel_in_result && $fuel_in_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Quantity (L)</th>
                                <th>Fuel Type</th>
                                <th>Supplier</th>
                                <th>Delivery Receipt</th>
                                <th>Storage Location</th>
                                <th>Added By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset result pointer and format data from fuel_in table
                            $fuel_in_result->data_seek(0);
                            while ($transaction = $fuel_in_result->fetch_assoc()): 
                                // Map fuel type ID to name
                                $fuel_type_names = [
                                    1 => 'diesel',
                                    2 => 'gasoline', 
                                    3 => 'premium'
                                ];
                                $fuel_type_name = $fuel_type_names[$transaction['fuel_type']] ?? 'Unknown';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M d, Y', strtotime($transaction['date_time'])); ?></strong>
                                        <br><small class="text-muted"><?php echo date('h:i A', strtotime($transaction['date_time'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success text-white">IN</span>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            <?php echo number_format($transaction['quantity'], 2); ?>
                                        </strong>
                                        <?php if (!empty($transaction['unit_price'])): ?>
                                            <br><small class="text-muted">@ <?php echo number_format($transaction['unit_price'], 2); ?>/L</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <?php echo ucfirst(htmlspecialchars($fuel_type_name)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($transaction['supplier_name'] ?? 'N/A'); ?></strong>
                                        <?php if (!empty($transaction['received_by'])): ?>
                                            <br><small class="text-muted">Received by: <?php echo htmlspecialchars($transaction['received_by']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo htmlspecialchars($transaction['delivery_receipt'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($transaction['storage_location'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                        <br><small class="text-muted"><?php echo date('M j', strtotime($transaction['created_at'] ?? $transaction['date_time'])); ?></small>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-arrow-down-circle text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Fuel IN Transactions Found</h5>
                    <p class="text-muted">No fuel IN transactions found in database.</p>
                </div>
            <?php endif; ?>
            </div>
        </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>

