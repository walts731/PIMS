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



// Get recent fuel in transactions from fuel_transactions table
$fuel_in_query = "SELECT ft.*, u.first_name, u.last_name 
                 FROM fuel_transactions ft 
                 LEFT JOIN users u ON ft.user_id = u.id 
                 WHERE ft.transaction_type = 'IN'";



// Add filters to query

$where_conditions = [];

if (!empty($fuel_type_filter)) {
    $where_conditions[] = "ft.fuel_type = '" . $fuel_type_filter . "'";
}

if (!empty($date_condition)) {
    $where_conditions[] = substr($date_condition, 5); // Remove " AND " prefix
}

if (!empty($where_conditions)) {
    $fuel_in_query .= " AND " . implode(" AND ", $where_conditions);
}

$fuel_in_query .= " ORDER BY ft.transaction_date DESC 
                 LIMIT 50";

$fuel_in_result = $conn->query($fuel_in_query);



// Get filtered period fuel in summary

$summary_date_condition = "";

switch ($period_filter) {
    case 'today':
        $summary_date_condition = " AND DATE(ft.transaction_date) = CURDATE()";
        break;
    case 'week':
        $summary_date_condition = " AND ft.transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $summary_date_condition = " AND ft.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    default:
        $summary_date_condition = "";
        break;

}



$today_fuel_in_query = "SELECT fuel_type, SUM(quantity) as total_quantity 
                        FROM fuel_transactions 
                        WHERE transaction_type = 'IN'";

// Add fuel type filter to summary if selected
if (!empty($fuel_type_filter)) {
    $today_fuel_in_query .= " AND fuel_type = '" . $fuel_type_filter . "'";
}

// Add date condition to summary
$today_fuel_in_query .= $summary_date_condition;

$today_fuel_in_query .= " GROUP BY fuel_type";

$today_fuel_in_result = $conn->query($today_fuel_in_query);



// Get fuel types for dropdown (using enum values)
$fuel_types = [
    ['id' => 'diesel', 'name' => 'Diesel'],
    ['id' => 'gasoline', 'name' => 'Gasoline'],
    ['id' => 'premium', 'name' => 'Premium']
];



// Get total fuel IN from entire database (like admin)

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

    $total_in_result = $conn->query($total_in_query);

    if ($total_in_result && $row = $total_in_result->fetch_assoc()) {

        $total_fuel_in_all = $row['total'] ?? 0;

    }

}



// If no fuel_in table, get total from fuel_transactions

if (empty($total_fuel_in_all) && in_array('fuel_transactions', $existing_tables)) {

    $total_in_trans_query = "SELECT SUM(quantity) as total FROM fuel_transactions WHERE transaction_type = 'IN'";

    $total_in_trans_result = $conn->query($total_in_trans_query);

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

            height: 100vh;

        }

        .main-container {

            background: rgba(255, 255, 255, 0.95);

            backdrop-filter: blur(10px);

            min-height: 100vh;

            margin: 0;

            padding: 2rem;

            border-radius: 0;

            animation: slideUp 0.8s ease-out;

            overflow-y: auto;

            height: 100vh;

        }

        @keyframes slideUp {

            from {

                opacity: 0;

                transform: translateY(50px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }

        .stats-card {

            background: white;

            border-radius: 15px;

            padding: 1.5rem;

            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);

            transition: all 0.3s ease;

            border: none;

            animation: slideUp 0.6s ease-out 0.2s both;

        }

        .stats-card:hover {

            transform: translateY(-5px);

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);

        }

        .header-section {

            background: linear-gradient(135deg, #28a745, #20c997);

            color: white;

            padding: 2rem;

            border-radius: 15px;

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

        .filter-section {

            background: white;

            border-radius: 15px;

            padding: 1.5rem;

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);

            margin-bottom: 2rem;

            animation: slideUp 0.6s ease-out 0.4s both;

        }

        .table-container {

            background: white;

            border-radius: 15px;

            padding: 1.5rem;

            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);

            animation: slideUp 0.6s ease-out 0.8s both;

        }

        .fuel-table {

            border-radius: 10px;

            overflow: hidden;

        }

        .fuel-table thead {

            background: linear-gradient(135deg, #28a745, #20c997);

            color: white;

        }

        .fuel-table tbody tr {

            animation: slideUp 0.4s ease-out;

            transition: all 0.3s ease;

        }

        .fuel-table tbody tr:hover {

            background-color: #f8f9fa;

            transform: translateX(5px);

        }

        .badge-fuel-in {

            background: linear-gradient(135deg, #28a745, #20c997);

            padding: 0.5rem 1rem;

            border-radius: 20px;

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

        .fuel-in-icon {

            background: linear-gradient(135deg, #28a745, #20c997);

            color: white;

        }

        .btn-gradient {

            background: linear-gradient(135deg, #28a745, #20c997);

            border: none;

            color: white;

            padding: 0.75rem 2rem;

            border-radius: 25px;

            font-weight: 600;

            transition: all 0.3s ease;

        }

        .btn-gradient:hover {

            transform: translateY(-2px);

            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);

        }

        .alert {

            animation: slideDown 0.5s ease-out;

        }

        .filter-section {

            background: white;

            border-radius: 15px;

            padding: 1.5rem;

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);

            animation: slideUp 0.6s ease-out 0.4s both;

        }

        .filter-section .form-select:focus {

            border-color: #28a745;

            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);

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

                <div>

                    <button class="btn btn-success btn-sm" onclick="showFuelInModal()">

                        <i class="bi bi-plus-circle"></i> Record Fuel In

                    </button>

                </div>

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

                                <option value="0" <?php echo $fuel_type_filter === 0 ? 'selected' : ''; ?>>

                                    All Fuel Types

                                </option>

                                <?php 

                                $fuel_types_result->data_seek(0);

                                while ($fuel_type = $fuel_types_result->fetch_assoc()): ?>

                                    <option value="<?php echo $fuel_type['id']; ?>" 

                                            <?php echo $fuel_type_filter === (int)$fuel_type['id'] ? 'selected' : ''; ?>>

                                        <?php echo htmlspecialchars($fuel_type['name']); ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                            <?php if ($fuel_type_filter > 0 || $period_filter !== 'all'): ?>

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

                                <?php if ($period_filter !== 'all' || $fuel_type_filter > 0): ?>

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

                                    if ($fuel_type_filter > 0) {

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



            <!-- Today's Summary -->

            <div class="row mb-4">

                <?php while ($summary = $today_fuel_in_result->fetch_assoc()): ?>

                    <div class="col-md-4">

                        <div class="card border-success">

                            <div class="card-body text-center">

                                <h5 class="card-title text-success"><?php echo number_format($summary['total_quantity'], 2); ?> L</h5>

                                <p class="card-text"><?php echo ucfirst(htmlspecialchars($summary['fuel_type'] ?? 'Unknown')); ?> Today</p>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>



            <!-- Database View Toggle -->
<div class="mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary active" onclick="showTableView()">
            <i class="bi bi-table"></i> Table View
        </button>
        <button type="button" class="btn btn-outline-info" onclick="showDatabaseView()">
            <i class="bi bi-database"></i> Database View
        </button>
    </div>
</div>

<!-- Standard Table View -->
<div id="tableView">
    <div class="table-responsive">
        <table class="table table-hover fuel-table">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Fuel Type</th>
                    <th>Quantity (L)</th>
                    <th>Supplier</th>
                    <th>Receipt #</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>

                    <tbody>

                        <?php if ($fuel_in_result->num_rows > 0): ?>

                            <?php while ($transaction = $fuel_in_result->fetch_assoc()): ?>

                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo ucfirst(htmlspecialchars($transaction['fuel_type'])); ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($transaction['quantity'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($transaction['supplier'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['source'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction(<?php echo $transaction['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7" class="text-center py-4">

                                    <div class="text-muted">

                                        <i class="bi bi-arrow-down-circle" style="font-size: 3rem;"></i>

                                        <p class="mt-2 mb-0">No fuel in transactions found</p>

                                        <small>Record your first fuel delivery</small>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

</div>

<!-- Database View -->
<div id="databaseView" style="display: none;">
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Transaction Type</th>
                    <th>Fuel Type</th>
                    <th>Quantity (L)</th>
                    <th>Transaction Date</th>
                    <th>Source</th>
                    <th>Supplier</th>
                    <th>Tank Number</th>
                    <th>Recipient Name</th>
                    <th>Purpose</th>
                    <th>Vehicle Equipment</th>
                    <th>User ID</th>
                    <th>Notes</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Reset result pointer for database view
                $fuel_in_result->data_seek(0);
                if ($fuel_in_result->num_rows > 0): 
                    while ($transaction = $fuel_in_result->fetch_assoc()): 
                ?>
                    <tr>
                        <td><?php echo $transaction['id']; ?></td>
                        <td><span class="badge bg-success"><?php echo $transaction['transaction_type']; ?></span></td>
                        <td><span class="badge bg-primary"><?php echo ucfirst($transaction['fuel_type']); ?></span></td>
                        <td><strong><?php echo number_format($transaction['quantity'], 2); ?></strong></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])); ?></td>
                        <td><?php echo htmlspecialchars($transaction['source'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['supplier'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['tank_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['recipient_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['purpose'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['vehicle_equipment'] ?? 'N/A'); ?></td>
                        <td><?php echo $transaction['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($transaction['notes'] ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['created_at'])); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['updated_at'])); ?></td>
                        <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                    </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <tr>
                        <td colspan="16" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-database" style="font-size: 3rem;"></i>
                                <p class="mt-2 mb-0">No fuel in transactions found</p>
                                <small>No data in fuel_transactions table</small>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Fuel IN Transactions Table (like fuel_transactions.php) -->
<div class="container-fluid mt-4">
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="bi bi-arrow-down-circle text-success me-2"></i>
                Fuel IN Transaction History
                <span class="badge bg-success text-white ms-2">
                    <?php echo count($fuel_in_result); ?> Records
                </span>
            </h4>
        </div>
        
        <?php if ($fuel_in_result && $fuel_in_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover fuel-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                            <th><i class="bi bi-arrow-down-circle me-1"></i>Type</th>
                            <th><i class="bi bi-truck me-1"></i>Vehicle</th>
                            <th><i class="bi bi-upc me-1"></i>Plate</th>
                            <th><i class="bi bi-droplet me-1"></i>Quantity (L)</th>
                            <th><i class="bi bi-fuel-pump me-1"></i>Fuel Type</th>
                            <th><i class="bi bi-chat-text me-1"></i>Purpose</th>
                            <th><i class="bi bi-speedometer2 me-1"></i>Odometer</th>
                            <th><i class="bi bi-person me-1"></i>Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset result pointer and format data like fuel_transactions.php
                        $fuel_in_result->data_seek(0);
                        while ($transaction = $fuel_in_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-success text-white">
                                        IN
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-success">
                                        <?php echo htmlspecialchars($transaction['vehicle_equipment'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['tank_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <strong class="text-success">
                                        <?php echo number_format($transaction['quantity'], 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">
                                        <?php echo htmlspecialchars($transaction['fuel_type'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['purpose'] ?? ''); ?></td>
                                <td><?php echo number_format($transaction['odometer_reading'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-arrow-down-circle text-muted" style="font-size: 3rem;"></i>
                <h5 class="text-muted mt-3">No Fuel IN Transactions Found</h5>
                <p class="text-muted">No fuel IN transactions found in the database.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

    <script>
function viewTransaction(id) {
    alert('View transaction details for ID: ' + id);
}

function deleteTransaction(id) {
    if (confirm('Are you sure you want to delete this transaction?')) {
        // Implement delete functionality
        alert('Delete transaction ID: ' + id);
    }
}

function showTableView() {
    document.getElementById('tableView').style.display = 'block';
    document.getElementById('databaseView').style.display = 'none';
    
    // Update button states
    document.querySelectorAll('.btn-group button')[0].classList.add('active');
    document.querySelectorAll('.btn-group button')[1].classList.remove('active');
}

function showDatabaseView() {
    document.getElementById('tableView').style.display = 'none';
    document.getElementById('databaseView').style.display = 'block';
    
    // Update button states
    document.querySelectorAll('.btn-group button')[0].classList.remove('active');
    document.querySelectorAll('.btn-group button')[1].classList.add('active');
}

$(document).ready(function() {
    $('#fuelInTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "Search transactions:",
            lengthMenu: "Show _MENU_ transactions per page"
        }
    });
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

