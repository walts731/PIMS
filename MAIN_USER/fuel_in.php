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

    <title>Fuel IN - PIMS</title>

    

    <!-- Bootstrap CSS -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    

    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header-section {
            background: #28a745;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
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

                        <i class="bi bi-arrow-down-circle me-3"></i>

                        Fuel IN Records

                    </h1>

                    <p class="mb-0 opacity-75">Detailed view of all fuel IN transactions</p>

                </div>

                <div class="col-md-4 text-end">

                    <a href="fuel_management.php" class="btn btn-light btn-lg">

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

            <div class="row mb-4">

                <div class="col-md-12">

                    <div class="stats-card h-100">

                        <div class="d-flex align-items-center">

                            <div class="fuel-icon fuel-in-icon me-3" style="background: linear-gradient(135deg, #28a745, #20c997);">

                                <i class="bi bi-arrow-down-circle text-white"></i>

                            </div>

                            <div>

                                <h6 class="text-muted mb-2">Total Fuel IN</h6>

                                <h3 class="mb-0 text-success">

                                    <?php echo number_format($total_fuel_in_all, 2); ?>

                                    <small>Liters</small>

                                </h3>

                                <small class="text-muted">All Time Total</small>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Filter Section -->

            <div class="filter-section mb-4">

                <div class="row align-items-end">

                    <div class="col-md-3">

                        <label for="period" class="form-label fw-semibold">

                            <i class="bi bi-calendar-range me-1"></i>Time Period

                        </label>

                        <form method="GET" class="d-flex gap-2">

                            <select class="form-select" id="period" name="period" onchange="this.form.submit()">

                                <option value="all" <?php echo $period_filter === 'all' ? 'selected' : ''; ?>>

                                    All Time

                                </option>

                                <option value="today" <?php echo $period_filter === 'today' ? 'selected' : ''; ?>>

                                    Today

                                </option>

                                <option value="week" <?php echo $period_filter === 'week' ? 'selected' : ''; ?>>

                                    Last 7 Days

                                </option>

                                <option value="month" <?php echo $period_filter === 'month' ? 'selected' : ''; ?>>

                                    Last 30 Days

                                </option>

                                <option value="year" <?php echo $period_filter === 'year' ? 'selected' : ''; ?>>

                                    Last 365 Days

                                </option>

                            </select>

                            <select class="form-select" id="fuel_type" name="fuel_type" onchange="this.form.submit()">

                                <option value="" <?php echo empty($fuel_type_filter) ? 'selected' : ''; ?>>

                                    All Fuel Types

                                </option>

                                <?php foreach ($fuel_types as $fuel_type): ?>

                                    <option value="<?php echo $fuel_type['id']; ?>" 

                                            <?php echo $fuel_type_filter === $fuel_type['id'] ? 'selected' : ''; ?>>

                                        <?php echo htmlspecialchars($fuel_type['name']); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <?php if (!empty($fuel_type_filter) || $period_filter !== 'all'): ?>

                                <a href="fuel_in.php" class="btn btn-outline-secondary">

                                    <i class="bi bi-x-circle me-1"></i>

                                    Clear

                                </a>

                            <?php endif; ?>

                        </form>

                    </div>

                    <div class="col-md-9">

                        <div class="text-muted">

                            <small>

                                <?php if ($period_filter !== 'all' || !empty($fuel_type_filter)): ?>

                                    <i class="bi bi-funnel me-1"></i>

                                    Showing transactions for 

                                    <?php 

                                    $period_labels = [

                                        'today' => 'Today',

                                        'week' => 'Last 7 Days',

                                        'month' => 'Last 30 Days',

                                        'year' => 'Last 365 Days'

                                    ];

                                    if ($period_filter !== 'all') {

                                        echo '<span class="badge bg-primary me-1">' . htmlspecialchars($period_labels[$period_filter]) . '</span>';

                                    }

                                    if (!empty($fuel_type_filter)) {

                                        echo '<span class="badge bg-success">Selected Fuel Type</span>';

                                    }

                                    ?>

                                <?php else: ?>

                                    <i class="bi bi-info-circle me-1"></i>

                                    Showing all fuel IN transactions. Use filters above to narrow results.

                                <?php endif; ?>

                            </small>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- Today's Summary -->

        <div class="row mb-4">
            <?php 
            // Create fuel type name mapping for display
            $fuel_type_names = [
                1 => 'diesel',
                2 => 'gasoline', 
                3 => 'premium'
            ];
            
            // Check if there are summary results
            $summary_count = 0;
            $today_fuel_in_result->data_seek(0);
            while ($summary = $today_fuel_in_result->fetch_assoc()) {
                $summary_count++;
            }
            
            // Reset pointer for display
            $today_fuel_in_result->data_seek(0);
            
            if ($summary_count > 0):
                while ($summary = $today_fuel_in_result->fetch_assoc()): 
                    $fuel_type_name = $fuel_type_names[$summary['fuel_type']] ?? 'Unknown';
            ?>
                <div class="col-md-4">
                    <div class="stats-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="fuel-icon fuel-in-icon me-3">
                                <i class="bi bi-droplet text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2"><?php echo ucfirst(htmlspecialchars($fuel_type_name)); ?></h6>
                                <h4 class="mb-0 text-success">
                                    <?php echo number_format($summary['total_quantity'], 2); ?>
                                    <small class="fs-6">Liters</small>
                                </h4>
                                <small class="text-muted">Today's Total</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile; 
            else:
            ?>
                <div class="col-12">
                    <div class="stats-card h-100">
                        <div class="text-center py-3">
                            <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No fuel transactions for today</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>



        <!-- Fuel IN Transactions Table -->
        <div class="table-container">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

