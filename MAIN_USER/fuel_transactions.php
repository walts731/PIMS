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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_transactions', 'Main user accessed fuel transactions page');

$fuel_in_records = [];
$fuel_out_records = [];
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
    $fuel_tables = ['fuel_in', 'fuel_out', 'fuel_transactions'];
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
        
        // Get fuel IN records from fuel_transactions table
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
        
        // Get fuel OUT records from fuel_transactions table
        if (in_array('fuel_transactions', $existing_tables)) {
            $fuel_out_sql = "SELECT 
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
                            WHERE transaction_type = 'OUT' 
                            AND DATE(transaction_date) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            if ($office_filter > 0) {
                $fuel_out_sql .= " AND office_id = ?";
                $params[] = $office_filter;
                $types .= "i";
            }
            
            $fuel_out_sql .= " ORDER BY transaction_date DESC";
            
            $fuel_out_stmt = $conn->prepare($fuel_out_sql);
            if ($fuel_out_stmt) {
                $fuel_out_stmt->bind_param($types, ...$params);
                $fuel_out_stmt->execute();
                $fuel_out_result = $fuel_out_stmt->get_result();
                while ($row = $fuel_out_result->fetch_assoc()) {
                    $fuel_out_records[] = $row;
                }
                $fuel_out_stmt->close();
            }
        }
        
    }
} catch (Exception $e) {
    $error = 'An error occurred while fetching fuel transactions: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Fuel Transactions - PIMS</title>
    
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
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
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
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
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
        .badge-fuel-transactions {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
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
        .transactions-icon {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            color: white;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(111, 66, 193, 0.3);
        }
        .alert {
            animation: slideDown 0.5s ease-out;
        }
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            animation: slideUp 0.6s ease-out 0.4s both;
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
                        <i class="bi bi-list-check me-3"></i>
                        All Fuel Transactions
                    </h1>
                    <p class="mb-0 opacity-75">Complete view of all fuel IN and OUT transactions</p>
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

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3">
                <i class="bi bi-funnel me-2"></i>
                Filter Transactions
            </h5>
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="date_from" class="form-label fw-semibold">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label fw-semibold">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gradient flex-fill">
                            <i class="bi bi-funnel me-1"></i>
                            Filter
                        </button>
                        <a href="fuel_transactions.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Reset
                        </a>
                        <a href="fuel_transactions.php?date_from=2020-01-01&date_to=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-info">
                            <i class="bi bi-calendar-range me-1"></i>
                            Show All
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="fuel-icon transactions-icon me-3">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Total Transactions</h6>
                            <h3 class="mb-0 text-info">
                                <?php echo count($fuel_in_records) + count($fuel_out_records); ?>
                                <small>Records</small>
                            </h3>
                            <small class="text-muted">
                                <?php echo count($fuel_in_records); ?> IN / <?php echo count($fuel_out_records); ?> OUT
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3">
                <i class="bi bi-funnel me-2"></i>
                Filter Transactions
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
                        <a href="fuel_transactions.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- All Transactions Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i>
                    Complete Transaction History
                    <span class="badge badge-fuel-transactions ms-2">
                        <?php 
                        $total_transactions = count($fuel_in_records) + count($fuel_out_records);
                        echo $total_transactions; ?> 
                        Records
                    </span>
                </h4>
            </div>
            
            <?php if (!empty($fuel_in_records) || !empty($fuel_out_records)): ?>
                <div class="table-responsive">
                    <table class="table table-hover fuel-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                                <th><i class="bi bi-arrow-up-down me-1"></i>Type</th>
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
                            // Combine and sort all transactions
                            $all_transactions = [];
                            
                            // Add fuel IN records
                            foreach ($fuel_in_records as $record) {
                                $all_transactions[] = array_merge($record, ['transaction_type' => 'IN', 'type_color' => 'success']);
                            }
                            
                            // Add fuel OUT records
                            foreach ($fuel_out_records as $record) {
                                $all_transactions[] = array_merge($record, ['transaction_type' => 'OUT', 'type_color' => 'danger']);
                            }
                            
                            // Sort by date
                            usort($all_transactions, function($a, $b) {
                                return strtotime($b['fuel_date']) - strtotime($a['fuel_date']);
                            });
                            
                            foreach ($all_transactions as $record): 
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M d, Y', strtotime($record['fuel_date'])); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $record['type_color']; ?> text-white">
                                            <?php echo $record['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-<?php echo $record['type_color']; ?>">
                                            <?php echo htmlspecialchars($record['vehicle_name'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['plate_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <strong class="text-<?php echo $record['type_color']; ?>">
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
                    <i class="bi bi-list-ul text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Fuel Transactions Found</h5>
                    <p class="text-muted">No fuel transactions found for the selected period and filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
