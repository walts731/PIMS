<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin, system_admin, or fuel)
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel'])) {
    header('Location: ../index.php');
    exit();
}

// Log fuel OUT page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_out_page', 'User accessed fuel OUT page');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_fuel_out') {
        $fuel_type = $_POST['fuel_type'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $vehicle_equipment = $_POST['vehicle_equipment'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $tank_number = $_POST['tank_number'] ?? '';
        $odometer_reading = $_POST['odometer_reading'] ?? 0;
        $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
        $driver_name = $_POST['driver_name'] ?? '';
        $department = $_POST['department'] ?? '';
        
        if (!empty($fuel_type) && $quantity > 0 && !empty($vehicle_equipment) && !empty($purpose)) {
            // Check if tank has enough fuel
            $tank_check_sql = "SELECT current_level, capacity FROM fuel_inventory WHERE tank_number = ? AND status = 'active'";
            $tank_check_stmt = $conn->prepare($tank_check_sql);
            $tank_check_stmt->bind_param('s', $tank_number);
            $tank_check_stmt->execute();
            $tank_result = $tank_check_stmt->get_result();
            
            if ($tank_result && $tank_data = $tank_result->fetch_assoc()) {
                if ($tank_data['current_level'] >= $quantity) {
                    // Define variables for bind_param
                    $transaction_type = 'OUT';
                    $user_id = $_SESSION['user_id'];
                    
                    // Insert fuel transaction
                    $insert_sql = "INSERT INTO fuel_transactions 
                                  (transaction_type, transaction_date, quantity, fuel_type, 
                                   vehicle_equipment, purpose, tank_number, odometer_reading, 
                                   driver_name, department, user_id, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param('ssdssssdsi', 
                        $transaction_type,
                        $transaction_date,
                        $quantity,
                        $fuel_type,
                        $vehicle_equipment,
                        $purpose,
                        $tank_number,
                        $odometer_reading,
                        $driver_name,
                        $department,
                        $user_id
                    );
                    
                    if ($stmt->execute()) {
                        // Update fuel inventory
                        $update_sql = "UPDATE fuel_inventory SET current_level = current_level - ?, last_updated = NOW() WHERE tank_number = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param('ds', $quantity, $tank_number);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        $_SESSION['fuel_success'] = 'Fuel OUT transaction added successfully!';
                        logSystemAction($_SESSION['user_id'], 'create', 'fuel_out', "Dispensed {$quantity}L of {$fuel_type} to {$vehicle_equipment}");
                    } else {
                        $_SESSION['fuel_error'] = 'Error adding fuel OUT transaction: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['fuel_error'] = 'Insufficient fuel in tank. Available: ' . number_format($tank_data['current_level'], 2) . 'L';
                }
            } else {
                $_SESSION['fuel_error'] = 'Invalid or inactive tank selected.';
            }
            $tank_check_stmt->close();
        } else {
            $_SESSION['fuel_error'] = 'Please fill all required fields.';
        }
        
        header('Location: fuel_out.php');
        exit();
    }
}

// Get recent fuel OUT transactions
$fuel_out_records = [];
try {
    $fuel_out_sql = "SELECT 
                       id,
                       transaction_date,
                       quantity,
                       fuel_type,
                       vehicle_equipment,
                       purpose,
                       tank_number,
                       odometer_reading,
                       driver_name,
                       department,
                       user_id,
                       created_at
                    FROM fuel_transactions 
                    WHERE transaction_type = 'OUT' 
                    ORDER BY transaction_date DESC 
                    LIMIT 50";
    $fuel_out_result = $conn->query($fuel_out_sql);
    if ($fuel_out_result) {
        while ($row = $fuel_out_result->fetch_assoc()) {
            $fuel_out_records[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Fuel OUT Error: ' . $e->getMessage());
}

// Get available tanks with current levels
$available_tanks = [];
try {
    $tanks_sql = "SELECT tank_number, fuel_type, current_level, capacity FROM fuel_inventory WHERE status = 'active' ORDER BY tank_number";
    $tanks_result = $conn->query($tanks_sql);
    if ($tanks_result) {
        while ($row = $tanks_result->fetch_assoc()) {
            $available_tanks[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Tanks Error: ' . $e->getMessage());
}

// Get common vehicles/equipment
$vehicles = [];
try {
    $vehicles_sql = "SELECT DISTINCT vehicle_equipment FROM fuel_transactions WHERE vehicle_equipment != '' ORDER BY vehicle_equipment LIMIT 20";
    $vehicles_result = $conn->query($vehicles_sql);
    if ($vehicles_result) {
        while ($row = $vehicles_result->fetch_assoc()) {
            $vehicles[] = $row['vehicle_equipment'];
        }
    }
} catch (Exception $e) {
    error_log('Vehicles Error: ' . $e->getMessage());
}

// Calculate statistics
$today_total = array_sum(array_filter(array_column($fuel_out_records, 'quantity'), function($q) {
    return date('Y-m-d') === date('Y-m-d', strtotime($q));
}));
$week_total = array_sum(array_filter(array_column($fuel_out_records, 'quantity'), function($q) {
    return date('W') === date('W');
}));
$total_available = array_sum(array_column($available_tanks, 'current_level'));
$total_transactions = count($fuel_out_records);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Out - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <style>
        .fuel-out-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 1rem 1rem 1rem 5rem;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: calc(100vh - 76px);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin: -2rem -2rem 2rem -2rem;
            border-radius: 20px 20px 0 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card.danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        
        .stat-card.info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #17a2b8, #138496);
        }
        
        .stat-card.warning::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #e0a800);
        }
        
        .stat-card.success::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon.danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .stat-icon.info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .stat-icon.warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .btn-fuel-out {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-fuel-out:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .fuel-out-badge {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .tank-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .tank-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin: 0.5rem;
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
        }
    </style>
</head>
<body class="fuel-out-page">
    <?php
    // Set page title for topbar
    $page_title = 'Fuel Out Management';
    ?>
    <!-- Main Content Wrapper -->
    <div class="fuel-main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once '../MAIN_USER/includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content animate-slide-up">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-arrow-up-circle me-3"></i>
                            Fuel Out Management
                        </h1>
                        <p class="mb-0 opacity-75">Record fuel dispensing and vehicle refueling</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-light btn-sm" onclick="refreshPage()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                        <a href="dashboard.php" class="btn btn-light btn-sm ms-2">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['fuel_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['fuel_success']); unset($_SESSION['fuel_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['fuel_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['fuel_error']); unset($_SESSION['fuel_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card danger">
                        <div class="stat-icon danger">
                            <i class="bi bi-calendar-today"></i>
                        </div>
                        <h6 class="text-muted mb-2">Today's Fuel OUT</h6>
                        <h3 class="mb-0 text-danger"><?php echo number_format($today_total, 2); ?> L</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card info">
                        <div class="stat-icon info">
                            <i class="bi bi-calendar-week"></i>
                        </div>
                        <h6 class="text-muted mb-2">This Week</h6>
                        <h3 class="mb-0 text-info"><?php echo number_format($week_total, 2); ?> L</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total Transactions</h6>
                        <h3 class="mb-0 text-warning"><?php echo $total_transactions; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <h6 class="text-muted mb-2">Available Fuel</h6>
                        <h3 class="mb-0 text-success"><?php echo number_format($total_available, 2); ?> L</h3>
                    </div>
                </div>
            </div>

            <!-- Tank Status Overview -->
            <div class="form-card">
                <h5 class="mb-4">
                    <i class="bi bi-fuel-pump me-2 text-primary"></i>
                    Tank Status Overview
                </h5>
                <div class="row">
                    <?php foreach ($available_tanks as $tank): ?>
                        <div class="col-md-4 mb-3">
                            <div class="tank-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($tank['tank_number']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars(ucfirst($tank['fuel_type'])); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0"><?php echo number_format($tank['current_level'], 1); ?>L</h5>
                                        <small class="text-muted">of <?php echo number_format($tank['capacity'], 1); ?>L</small>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height: 8px;">
                                    <?php 
                                    $percentage = ($tank['current_level'] / $tank['capacity']) * 100;
                                    $progress_class = 'bg-success';
                                    if ($percentage < 20) $progress_class = 'bg-danger';
                                    elseif ($percentage < 50) $progress_class = 'bg-warning';
                                    ?>
                                    <div class="progress-bar <?php echo $progress_class; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%"
                                         aria-valuenow="<?php echo $tank['current_level']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="<?php echo $tank['capacity']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dispense Fuel Form -->
            <div class="form-card">
                <h5 class="mb-4">
                    <i class="bi bi-plus-circle me-2 text-danger"></i>
                    Dispense Fuel
                </h5>
                <form method="POST" action="fuel_out.php" onsubmit="return validateFuelQuantity()">
                    <input type="hidden" name="action" value="add_fuel_out">
                    <div class="row">
                        <div class="col-md-2">
                            <label for="tank_number" class="form-label">Tank *</label>
                            <select class="form-select" id="tank_number" name="tank_number" required onchange="updateTankInfo()">
                                <option value="">Select tank</option>
                                <?php foreach ($available_tanks as $tank): ?>
                                    <option value="<?php echo htmlspecialchars($tank['tank_number']); ?>" 
                                            data-fuel-type="<?php echo htmlspecialchars($tank['fuel_type']); ?>"
                                            data-current-level="<?php echo $tank['current_level']; ?>">
                                        <?php echo htmlspecialchars($tank['tank_number']); ?> (<?php echo number_format($tank['current_level'], 1); ?>L)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="fuel_type" class="form-label">Fuel Type *</label>
                            <input type="text" class="form-control" id="fuel_type" name="fuel_type" readonly required>
                        </div>
                        <div class="col-md-2">
                            <label for="quantity" class="form-label">Quantity (L) *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" step="0.01" min="0.01" required>
                            <small class="text-muted">Available: <span id="available_fuel">0</span>L</small>
                        </div>
                        <div class="col-md-3">
                            <label for="vehicle_equipment" class="form-label">Vehicle/Equipment *</label>
                            <input type="text" class="form-control" id="vehicle_equipment" name="vehicle_equipment" 
                                   list="vehicles_list" required>
                            <datalist id="vehicles_list">
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo htmlspecialchars($vehicle); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-3">
                            <label for="purpose" class="form-label">Purpose *</label>
                            <input type="text" class="form-control" id="purpose" name="purpose" placeholder="Purpose of fuel dispensing" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-2">
                            <label for="driver_name" class="form-label">Driver Name</label>
                            <input type="text" class="form-control" id="driver_name" name="driver_name" placeholder="Driver name">
                        </div>
                        <div class="col-md-2">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">Select department</option>
                                <option value="operations">Operations</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="logistics">Logistics</option>
                                <option value="admin">Administration</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="odometer_reading" class="form-label">Odometer</label>
                            <input type="number" class="form-control" id="odometer_reading" name="odometer_reading" placeholder="km">
                        </div>
                        <div class="col-md-2">
                            <label for="transaction_date" class="form-label">Date/Time</label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-fuel-out w-100">
                                <i class="bi bi-arrow-up-circle me-2"></i>Dispense Fuel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recent Fuel OUT Transactions -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Recent Fuel OUT Transactions
                        <span class="fuel-out-badge ms-2"><?php echo count($fuel_out_records); ?></span>
                    </h5>
                    <button class="btn btn-outline-danger btn-sm" onclick="exportFuelOutData()">
                        <i class="bi bi-download me-1"></i>Export
                    </button>
                </div>
                
                <?php if (!empty($fuel_out_records)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar me-1"></i>Date</th>
                                    <th><i class="bi bi-tag me-1"></i>Fuel Type</th>
                                    <th><i class="bi bi-droplet me-1"></i>Quantity</th>
                                    <th><i class="bi bi-fuel-pump me-1"></i>Tank</th>
                                    <th><i class="bi bi-truck me-1"></i>Vehicle/Equipment</th>
                                    <th><i class="bi bi-person me-1"></i>Driver</th>
                                    <th><i class="bi bi-building me-1"></i>Department</th>
                                    <th><i class="bi bi-chat-left-text me-1"></i>Purpose</th>
                                    <th><i class="bi bi-gear me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuel_out_records as $record): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('M d, Y H:i', strtotime($record['transaction_date'])); ?>
                                            <br><small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($record['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                <?php echo htmlspecialchars(ucfirst($record['fuel_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-danger"><?php echo number_format($record['quantity'], 2); ?> L</strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($record['tank_number'])): ?>
                                                <span class="badge bg-primary text-white">
                                                    <?php echo htmlspecialchars($record['tank_number']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['vehicle_equipment']); ?></td>
                                        <td><?php echo htmlspecialchars($record['driver_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (!empty($record['department'])): ?>
                                                <span class="badge bg-secondary text-white">
                                                    <?php echo htmlspecialchars(ucfirst($record['department'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-info" onclick="viewTransaction(<?php echo $record['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteTransaction(<?php echo $record['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-arrow-up-circle text-muted" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mt-3">No Fuel OUT Transactions Found</h6>
                        <p class="text-muted">Start by dispensing fuel using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    let tankData = {};

    function updateTankInfo() {
        const select = document.getElementById('tank_number');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value) {
            const fuelType = selectedOption.getAttribute('data-fuel-type');
            const currentLevel = parseFloat(selectedOption.getAttribute('data-current-level'));
            
            document.getElementById('fuel_type').value = fuelType;
            document.getElementById('available_fuel').textContent = currentLevel.toFixed(2);
            document.getElementById('quantity').max = currentLevel;
            
            tankData = {
                fuelType: fuelType,
                currentLevel: currentLevel
            };
        } else {
            document.getElementById('fuel_type').value = '';
            document.getElementById('available_fuel').textContent = '0';
            document.getElementById('quantity').max = '';
            tankData = {};
        }
    }

    function validateFuelQuantity() {
        const quantity = parseFloat(document.getElementById('quantity').value);
        const tankNumber = document.getElementById('tank_number').value;
        
        if (!tankNumber) {
            alert('Please select a tank.');
            return false;
        }
        
        if (quantity > tankData.currentLevel) {
            alert('Insufficient fuel in tank. Available: ' + tankData.currentLevel.toFixed(2) + 'L');
            return false;
        }
        
        if (quantity <= 0) {
            alert('Please enter a valid quantity.');
            return false;
        }
        
        return true;
    }

    $(document).ready(function() {
        // Initialize DataTables
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    function refreshPage() {
        location.reload();
    }

    function exportFuelOutData() {
        window.open('pages/export_fuel_report.php?export=1&type=fuel_out', '_blank');
    }

    function viewTransaction(transactionId) {
        fetch('pages/get_transaction.php?id=' + transactionId)
            .then(response => response.json())
            .then(data => {
                alert('Transaction Details:\n\nID: ' + data.id + '\nType: ' + data.transaction_type + '\nFuel Type: ' + data.fuel_type + '\nQuantity: ' + data.quantity + 'L\nVehicle: ' + (data.vehicle_equipment || 'N/A') + '\nPurpose: ' + data.purpose);
            })
            .catch(error => {
                console.error('Error fetching transaction:', error);
                alert('Error loading transaction details.');
            });
    }

    function deleteTransaction(transactionId) {
        if (confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
            fetch('pages/delete_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: transactionId,
                    action: 'delete'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting transaction: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting transaction:', error);
                alert('Error deleting transaction.');
            });
        }
    }
    </script>
</body>
</html>
