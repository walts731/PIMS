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

$fuel_in_records = [];
$offices = [];
$error = null;

try {
    // Get offices for dropdown
    $office_sql = "SELECT id, office_name FROM offices ORDER BY office_name";
    $office_result = $conn->query($office_sql);
    if ($office_result) {
        while ($row = $office_result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
    
    // Check which fuel tables exist
    $fuel_tables = ['fuel_transactions'];
    $existing_tables = [];
    
    foreach ($fuel_tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table && $check_table->num_rows > 0) {
            $existing_tables[] = $table;
        }
    }
    
    if (empty($existing_tables)) {
        $error = 'No fuel tables found. Please contact administrator to set up fuel management tables.';
    } else {
        // Get filter parameters - expanded default date range
        $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : date('Y-m-01', strtotime('-3 months'));
        $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : date('Y-m-d');
        $office_filter = isset($_GET['office']) ? (int)$_GET['office'] : 0;
        
        // Get fuel IN records from fuel_transactions table (exactly like fuel_transactions.php)
        if (in_array('fuel_transactions', $existing_tables)) {
            $fuel_in_sql = "SELECT 
                              id,
                              DATE(transaction_date) as fuel_date,
                              quantity as fuel_quantity,
                              fuel_type,
                              vehicle_equipment as vehicle_name,
                              '' as plate_number,
                              odometer_reading,
                              purpose,
                              created_at,
                              user_id as created_by
                           FROM fuel_transactions 
                           WHERE transaction_type = 'IN' 
                           AND DATE(transaction_date) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            if ($office_filter > 0) {
                $fuel_in_sql .= " AND office_id = ?";
                $params[] = $office_filter;
                $types .= "i";
            }
            
            $fuel_in_sql .= " ORDER BY transaction_date DESC";
            
            $fuel_in_stmt = $conn->prepare($fuel_in_sql);
            if ($fuel_in_stmt) {
                $fuel_in_stmt->bind_param($types, ...$params);
                $fuel_in_stmt->execute();
                $fuel_in_result = $fuel_in_stmt->get_result();
                while ($row = $fuel_in_result->fetch_assoc()) {
                    $fuel_in_records[] = $row;
                }
                $fuel_in_stmt->close();
            }
        }
    }
} catch (Exception $e) {
    $error = 'Error loading fuel transactions: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel IN Transactions - PIMS</title>
    
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
            text-align: center;
            margin-bottom: 3rem;
            animation: slideUp 0.5s ease-out;
        }
        .header-section h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }
        .header-section p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .badge-fuel-in {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
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
        .fuel-icon-in {
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
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        .fuel-table {
            border-radius: 10px;
            overflow: hidden;
        }
        .fuel-table thead th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        .fuel-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        .alert {
            animation: slideDown 0.5s ease-out;
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
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <h1>
                <i class="bi bi-arrow-down-circle text-success me-3"></i>
                Fuel IN Transactions
            </h1>
            <p class="lead">View and manage all fuel IN transactions</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-house me-2"></i>Dashboard
                </a>
                <a href="fuel_management.php" class="btn btn-gradient">
                    <i class="bi bi-fuel-pump me-2"></i>Fuel Management
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="fuel-icon fuel-icon-in me-3">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Total Fuel IN</h6>
                            <h3 class="mb-0 text-success">
                                <?php echo count($fuel_in_records); ?>
                                <small>Transactions</small>
                            </h3>
                            <small class="text-muted">This period</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="fuel-icon fuel-icon-in me-3">
                            <i class="bi bi-droplet"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Total Volume</h6>
                            <h3 class="mb-0 text-info">
                                <?php 
                                $total_volume = array_sum(array_column($fuel_in_records, 'fuel_quantity'));
                                echo number_format($total_volume, 2); ?>
                                <small>Liters</small>
                            </h3>
                            <small class="text-muted">Combined volume</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3">
                <i class="bi bi-funnel me-2"></i>
                Filter Fuel IN Transactions
            </h5>
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="office" class="form-label fw-semibold">Office</label>
                    <select class="form-select" id="office" name="office">
                        <option value="0" <?php echo $office_filter === 0 ? 'selected' : ''; ?>>All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo (int)$office['id']; ?>" <?php echo $office_filter === (int)$office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label fw-semibold">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label fw-semibold">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gradient flex-fill">
                            <i class="bi bi-funnel me-1"></i>
                            Filter
                        </button>
                        <a href="fuel_in.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Fuel IN Transactions Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="bi bi-arrow-down-circle text-success me-2"></i>
                    Fuel IN Transaction History
                    <span class="badge badge-fuel-in ms-2">
                        <?php echo count($fuel_in_records); ?> Records
                    </span>
                </h4>
            </div>
            
            <?php if (!empty($fuel_in_records)): ?>
                <div class="table-responsive">
                    <table class="table table-hover fuel-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar3 me-1"></i>Date</th>
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
                            <?php foreach ($fuel_in_records as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M d, Y', strtotime($record['fuel_date'])); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-success">
                                            <?php echo htmlspecialchars($record['vehicle_name'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['plate_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <strong class="text-success">
                                            <?php echo number_format($record['fuel_quantity'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <?php echo htmlspecialchars($record['fuel_type'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['purpose'] ?? ''); ?></td>
                                    <td><?php echo number_format($record['odometer_reading'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars($record['created_by'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-arrow-down-circle text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Fuel IN Transactions Found</h5>
                    <p class="text-muted">No fuel IN transactions found for the selected period and filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</body>
</html>
