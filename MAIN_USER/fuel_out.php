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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_out', 'Main user accessed fuel OUT page');

// Initialize error variable
$error = null;

// Get total fuel OUT from entire database (like admin)
$total_fuel_out_all = 0;

// Check which fuel tables exist
$fuel_tables = ['fuel_out', 'fuel_transactions'];
$existing_tables = [];

foreach ($fuel_tables as $table) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check_table && $check_table->num_rows > 0) {
        $existing_tables[] = $table;
    }
}

if (in_array('fuel_out', $existing_tables)) {
    $total_out_query = "SELECT SUM(fo_liters) as total FROM fuel_out";
    $total_out_result = $conn->query($total_out_query);
    if ($total_out_result && $row = $total_out_result->fetch_assoc()) {
        $total_fuel_out_all = $row['total'] ?? 0;
    }
}

// If no fuel_out table, get total from fuel_transactions
if (empty($total_fuel_out_all) && in_array('fuel_transactions', $existing_tables)) {
    $total_out_trans_query = "SELECT SUM(quantity) as total FROM fuel_transactions WHERE transaction_type = 'OUT'";
    $total_out_trans_result = $conn->query($total_out_trans_query);
    if ($total_out_trans_result && $row = $total_out_trans_result->fetch_assoc()) {
        $total_fuel_out_all = $row['total'] ?? 0;
    }
}

// Get recent fuel out transactions (like admin)
$fuel_out_query = "SELECT ft.*, u.first_name, u.last_name, e.firstname as emp_firstname, e.lastname as emp_lastname
                   FROM fuel_transactions ft 
                   LEFT JOIN users u ON ft.user_id = u.id 
                   LEFT JOIN employees e ON ft.employee_id = e.id
                   WHERE ft.transaction_type = 'OUT' 
                   ORDER BY ft.created_at DESC";
$fuel_out_result = $conn->query($fuel_out_query);

// Get today's fuel out summary
$today_fuel_out_query = "SELECT fuel_type, SUM(quantity) as total_quantity 
                        FROM fuel_transactions 
                        WHERE transaction_type = 'OUT' AND DATE(transaction_date) = CURDATE() 
                        GROUP BY fuel_type";
$today_fuel_out_result = $conn->query($today_fuel_out_query);

// Get fuel types for dropdown
$fuel_types_query = "SELECT id, name FROM fuel_types WHERE is_active = 1 ORDER BY name";
$fuel_types_result = $conn->query($fuel_types_query);

// Get employees for dropdown
$employees_query = "SELECT id, firstname, lastname FROM employees WHERE employment_status = 'permanent' ORDER BY firstname, lastname";
$employees_result = $conn->query($employees_query);

// Calculate totals for display
$total_transactions = 0;
if ($fuel_out_result) {
    $total_transactions = $fuel_out_result->num_rows;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel OUT - PIMS</title>
    
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
            background: linear-gradient(135deg, #dc3545, #c82333);
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
            background: linear-gradient(135deg, #dc3545, #c82333);
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
        .badge-fuel-out {
            background: linear-gradient(135deg, #dc3545, #c82333);
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
        .fuel-out-icon {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }
        .alert {
            animation: slideDown 0.5s ease-out;
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
                        <i class="bi bi-arrow-up-circle me-3"></i>
                        Fuel OUT Records
                    </h1>
                    <p class="mb-0 opacity-75">Detailed view of all fuel OUT transactions</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="fuel_management.php" class="btn btn-light btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Dashboard
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

        <!-- Statistics Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="fuel-icon fuel-out-icon me-3">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Total Fuel OUT</h6>
                            <h3 class="mb-0 text-danger">
                                <?php echo number_format($total_fuel_out_all, 2); ?>
                                <small>Liters</small>
                            </h3>
                            <small class="text-muted">All Time Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Summary -->
        <div class="row mb-4">
            <?php while ($summary = $today_fuel_out_result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h5 class="card-title text-danger"><?php echo number_format($summary['total_quantity'], 2); ?> L</h5>
                            <p class="card-text"><?php echo ucfirst(htmlspecialchars($summary['fuel_type'])); ?> Today</p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Fuel OUT Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="bi bi-arrow-up-circle text-danger me-2"></i>
                    Fuel OUT Transactions
                    <span class="badge badge-fuel-out ms-2">
                        <?php echo $total_transactions; ?> Records
                    </span>
                </h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover fuel-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                            <th><i class="bi bi-fuel-pump me-1"></i>Fuel Type</th>
                            <th><i class="bi bi-droplet me-1"></i>Quantity (L)</th>
                            <th><i class="bi bi-person me-1"></i>Employee</th>
                            <th><i class="bi bi-chat-text me-1"></i>Purpose</th>
                            <th><i class="bi bi-truck me-1"></i>Vehicle/Equipment</th>
                            <th><i class="bi bi-person-check me-1"></i>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fuel_out_result->num_rows > 0): ?>
                            <?php while ($transaction = $fuel_out_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M d, Y g:i A', strtotime($transaction['transaction_date'])); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-danger">
                                            <?php echo ucfirst(htmlspecialchars($transaction['fuel_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-danger">
                                            <?php echo number_format($transaction['quantity'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($transaction['employee_id']) {
                                            echo htmlspecialchars($transaction['emp_firstname'] . ' ' . $transaction['emp_lastname']);
                                        } else {
                                            echo htmlspecialchars($transaction['recipient_name'] ?? 'N/A');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['purpose'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['vehicle_equipment'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-arrow-up-circle" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">No fuel out transactions found</p>
                                        <small>No fuel dispensing recorded yet</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
