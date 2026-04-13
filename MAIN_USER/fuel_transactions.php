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
    $fuel_tables = ['fuel_in', 'fuel_out', 'fuel_types'];
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
        // Get filter parameters
        $date_period = isset($_GET['date_period']) ? trim((string)$_GET['date_period']) : '3months';
        $transaction_type_filter = isset($_GET['transaction_type']) ? trim((string)$_GET['transaction_type']) : '';
        $office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;
        
        // Calculate date range based on period filter
        $date_from = '';
        $date_to = '';
        
        switch ($date_period) {
            case 'today':
                $date_from = date('Y-m-d');
                $date_to = date('Y-m-d');
                break;
            case '1month':
                $date_from = date('Y-m-01', strtotime('-1 month'));
                $date_to = date('Y-m-d');
                break;
            case 'custom':
                $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : date('Y-m-01', strtotime('-3 months'));
                $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : date('Y-m-d');
                break;
            case '3months':
            default:
                $date_from = date('Y-m-01', strtotime('-3 months'));
                $date_to = date('Y-m-d');
                break;
        }
        
        // Fuel type name mapping (fuel_in uses integer IDs)
        $fuel_type_names = [
            1 => 'Diesel',
            2 => 'Gasoline',
            3 => 'Premium'
        ];
        
        // Get fuel IN records from fuel_in table (if transaction type filter allows IN)
        if (in_array('fuel_in', $existing_tables) && (empty($transaction_type_filter) || $transaction_type_filter === 'IN')) {
            $fuel_in_sql = "SELECT 
                              fi.id,
                              DATE(fi.date_time) as fuel_date,
                              fi.quantity as fuel_quantity,
                              fi.fuel_type,
                              fi.supplier_name as vehicle_name,
                              fi.delivery_receipt as plate_number,
                              fi.received_by,
                              fi.storage_location as purpose,
                              fi.unit_price,
                              fi.created_at
                           FROM fuel_in fi
                           WHERE DATE(fi.date_time) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            $fuel_in_sql .= " ORDER BY fi.date_time DESC";
            
            $fuel_in_stmt = $conn->prepare($fuel_in_sql);
            if ($fuel_in_stmt) {
                $fuel_in_stmt->bind_param($types, ...$params);
                $fuel_in_stmt->execute();
                $fuel_in_result = $fuel_in_stmt->get_result();
                while ($row = $fuel_in_result->fetch_assoc()) {
                    // Map fuel_type integer to name
                    $row['fuel_type_name'] = $fuel_type_names[$row['fuel_type']] ?? 'Unknown';
                    // Set office_name as N/A since column doesn't exist in fuel_in table
                    $row['office_name'] = 'N/A';
                    $fuel_in_records[] = $row;
                }
                $fuel_in_stmt->close();
            } else {
                $error = 'Fuel IN SQL Error: ' . $conn->error;
            }
        }
        
        // Get fuel OUT records from fuel_out table (if transaction type filter allows OUT)
        if (in_array('fuel_out', $existing_tables) && in_array('fuel_types', $existing_tables) && (empty($transaction_type_filter) || $transaction_type_filter === 'OUT')) {
            $fuel_out_sql = "SELECT 
                               fo.id,
                               DATE(fo.fo_date) as fuel_date,
                               fo.fo_time_in,
                               fo.fo_liters as fuel_quantity,
                               ft.name as fuel_type_name,
                               fo.fo_receiver as vehicle_name,
                               fo.fo_plate_no as plate_number,
                               fo.fo_request as purpose,
                               fo.created_at,
                               fo.office_name
                            FROM fuel_out fo
                            LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
                            WHERE DATE(fo.fo_date) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            // Add office filter to SQL if selected
            if ($office_filter > 0) {
                $selected_office_name = '';
                foreach ($offices as $office) {
                    if ($office['id'] == $office_filter) {
                        $selected_office_name = $office['office_name'];
                        break;
                    }
                }
                if (!empty($selected_office_name)) {
                    $fuel_out_sql .= " AND fo.office_name = ?";
                    $params[] = $selected_office_name;
                    $types .= "s";
                }
            }
            
            $fuel_out_sql .= " ORDER BY fo.created_at DESC";
            
            $fuel_out_stmt = $conn->prepare($fuel_out_sql);
            if ($fuel_out_stmt) {
                $fuel_out_stmt->bind_param($types, ...$params);
                $fuel_out_stmt->execute();
                $fuel_out_result = $fuel_out_stmt->get_result();
                while ($row = $fuel_out_result->fetch_assoc()) {
                    $fuel_out_records[] = $row;
                }
                $fuel_out_stmt->close();
            } else {
                $error = 'Fuel OUT SQL Error: ' . $conn->error;
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
    <title>Fuel Transactions - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
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
                        Fuel Transactions
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
        <div class="filter-section mb-4">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="transaction_type" class="form-label fw-semibold">
                        <i class="bi bi-arrow-up-down me-1"></i>Transaction Type
                    </label>
                    <form method="GET" class="d-flex gap-2">
                        <select class="form-select" id="transaction_type" name="transaction_type" onchange="this.form.submit()">
                            <option value="" <?php echo empty($transaction_type_filter) ? 'selected' : ''; ?>>
                                All Types
                            </option>
                            <option value="IN" <?php echo $transaction_type_filter === 'IN' ? 'selected' : ''; ?>>
                                Fuel IN
                            </option>
                            <option value="OUT" <?php echo $transaction_type_filter === 'OUT' ? 'selected' : ''; ?>>
                                Fuel OUT
                            </option>
                        </select>
                        <select class="form-select" id="office" name="office" onchange="this.form.submit()">
                            <option value="0" <?php echo $office_filter === 0 ? 'selected' : ''; ?>>
                                All Offices
                            </option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo (int)$office['id']; ?>" 
                                        <?php echo $office_filter === (int)$office['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($office['office_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select" id="date_period" name="date_period" onchange="updateDateRange(); this.form.submit()">
                            <option value="3months" <?php echo (isset($_GET['date_period']) && $_GET['date_period'] === '3months') ? 'selected' : ''; ?>>
                                Last 3 Months
                            </option>
                            <option value="1month" <?php echo (isset($_GET['date_period']) && $_GET['date_period'] === '1month') ? 'selected' : ''; ?>>
                                Last Month
                            </option>
                            <option value="today" <?php echo (isset($_GET['date_period']) && $_GET['date_period'] === 'today') ? 'selected' : ''; ?>>
                                Today
                            </option>
                            <option value="custom" <?php echo (isset($_GET['date_period']) && $_GET['date_period'] === 'custom') ? 'selected' : ''; ?>>
                                Custom Range
                            </option>
                        </select>
                        <?php if (!empty($transaction_type_filter) || $office_filter > 0 || (isset($_GET['date_period']) && $_GET['date_period'] !== '3months')): ?>
                            <a href="fuel_transactions.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>
                                Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-md-8">
                    <div class="text-muted">
                        <small>
                            <?php if (!empty($transaction_type_filter) || $office_filter > 0 || (isset($_GET['date_period']) && $_GET['date_period'] !== '3months')): ?>
                                <i class="bi bi-funnel me-1"></i>
                                Showing transactions for 
                                <?php 
                                if (!empty($transaction_type_filter)) {
                                    echo '<span class="badge bg-primary me-1">' . htmlspecialchars($transaction_type_filter) . '</span>';
                                }
                                if ($office_filter > 0) {
                                    foreach ($offices as $office) {
                                        if ($office['id'] == $office_filter) {
                                            echo '<span class="badge bg-info me-1">' . htmlspecialchars($office['office_name']) . '</span>';
                                            break;
                                        }
                                    }
                                }
                                if (isset($_GET['date_period']) && $_GET['date_period'] !== '3months') {
                                    $period_labels = [
                                        '1month' => 'Last Month',
                                        'today' => 'Today',
                                        'custom' => 'Custom Range'
                                    ];
                                    echo '<span class="badge bg-warning">' . htmlspecialchars($period_labels[$_GET['date_period']]) . '</span>';
                                }
                                ?>
                            <?php else: ?>
                                <i class="bi bi-info-circle me-1"></i>
                                Showing all fuel transactions from the last 3 months. Use filters above to narrow results.
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php if (isset($_GET['date_period']) && $_GET['date_period'] === 'custom'): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <form method="GET" class="d-flex gap-2 align-items-end">
                            <input type="hidden" name="transaction_type" value="<?php echo htmlspecialchars($transaction_type_filter); ?>">
                            <input type="hidden" name="office" value="<?php echo $office_filter; ?>">
                            <input type="hidden" name="date_period" value="custom">
                            <div>
                                <label for="date_from" class="form-label fw-semibold">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div>
                                <label for="date_to" class="form-label fw-semibold">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <button type="submit" class="btn btn-gradient">
                                <i class="bi bi-funnel me-1"></i>
                                Apply Date Range
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Transactions Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">
                        <i class="bi bi-list-ul text-primary me-2"></i>
                        Complete Transaction History
                        <span class="badge badge-fuel-transactions ms-2">
                            <?php 
                            $total_transactions = count($fuel_in_records) + count($fuel_out_records);
                            echo $total_transactions; ?> 
                            Records
                        </span>
                    </h4>
                    <?php if ($office_filter > 0): ?>
                        <?php 
                        $selected_office_display = '';
                        foreach ($offices as $office) {
                            if ($office['id'] == $office_filter) {
                                $selected_office_display = $office['office_name'];
                                break;
                            }
                        }
                        ?>
                        <p class="mb-0 text-muted">
                            <i class="bi bi-building me-1"></i>
                            Office: <strong><?php echo htmlspecialchars($selected_office_display); ?></strong>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($fuel_in_records) || !empty($fuel_out_records)): ?>
                <div class="table-responsive">
                    <table class="table table-hover fuel-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                                <th><i class="bi bi-arrow-up-down me-1"></i>Type</th>
                                <th><i class="bi bi-truck me-1"></i>Vehicle/Supplier</th>
                                <th><i class="bi bi-upc me-1"></i>Receipt/Plate</th>
                                <th><i class="bi bi-droplet me-1"></i>Quantity (L)</th>
                                <th><i class="bi bi-fuel-pump me-1"></i>Fuel Type</th>
                                <th><i class="bi bi-building me-1"></i>Office</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Combine and sort all transactions
                            $all_transactions = [];
                            
                            // Add fuel IN records
                            foreach ($fuel_in_records as $record) {
                                $all_transactions[] = array_merge($record, [
                                    'transaction_type' => 'IN', 
                                    'type_color' => 'success',
                                    'record_time' => $record['date_time'] ?? $record['created_at']
                                ]);
                            }
                            
                            // Add fuel OUT records
                            foreach ($fuel_out_records as $record) {
                                $all_transactions[] = array_merge($record, [
                                    'transaction_type' => 'OUT', 
                                    'type_color' => 'danger',
                                    'record_time' => $record['fo_time_in'] ?? $record['created_at']
                                ]);
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
                                        <?php if (!empty($record['record_time'])): ?>
                                            <br><small class="text-muted"><?php echo date('h:i A', strtotime($record['record_time'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $record['type_color']; ?> text-white">
                                            <?php echo $record['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-<?php echo $record['type_color']; ?>">
                                            <?php echo htmlspecialchars($record['vehicle_name'] ?? 'N/A'); ?>
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
                                            <?php echo htmlspecialchars($record['fuel_type_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($record['office_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
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
    <script>
        function updateDateRange() {
            const period = document.getElementById('date_period').value;
            const form = document.querySelector('form');
            
            if (period !== 'custom') {
                // Remove date_from and date_to from form if not custom
                const dateFromInput = document.getElementById('date_from');
                const dateToInput = document.getElementById('date_to');
                if (dateFromInput) dateFromInput.remove();
                if (dateToInput) dateToInput.remove();
            }
        }
    </script>
</body>
</html>
