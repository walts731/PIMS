<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_management', 'Main user accessed fuel management display');

$fuel_in_records = [];
$fuel_out_records = [];
$error = null;

try {
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
        // Get filter parameters
        $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : date('Y-m-01');
        $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : date('Y-m-d');
        
        // Get fuel IN records
        if (in_array('fuel_in', $existing_tables)) {
            $fuel_in_sql = "SELECT 
                              id,
                              fi_date as fuel_date,
                              fi_quantity as fuel_quantity,
                              fi_fuel_type as fuel_type,
                              fi_vehicle_name as vehicle_name,
                              fi_plate_number as plate_number,
                              fi_odometer as odometer_reading,
                              fi_purpose as purpose,
                              created_at,
                              created_by
                           FROM fuel_in 
                           WHERE fi_date BETWEEN ? AND ?
                           ORDER BY fi_date DESC";
            
            $fuel_in_stmt = $conn->prepare($fuel_in_sql);
            if ($fuel_in_stmt) {
                $fuel_in_stmt->bind_param('ss', $date_from, $date_to);
                $fuel_in_stmt->execute();
                $fuel_in_result = $fuel_in_stmt->get_result();
                while ($row = $fuel_in_result->fetch_assoc()) {
                    $fuel_in_records[] = $row;
                }
                $fuel_in_stmt->close();
            }
        }
        
        // Get fuel OUT records
        if (in_array('fuel_out', $existing_tables)) {
            $fuel_out_sql = "SELECT 
                               id,
                               fo_date as fuel_date,
                               fo_quantity as fuel_quantity,
                               fo_fuel_type as fuel_type,
                               fo_vehicle_name as vehicle_name,
                               fo_plate_number as plate_number,
                               fo_odometer as odometer_reading,
                               fo_purpose as purpose,
                               created_at,
                               created_by
                            FROM fuel_out 
                            WHERE fo_date BETWEEN ? AND ?
                            ORDER BY fo_date DESC";
            
            $fuel_out_stmt = $conn->prepare($fuel_out_sql);
            if ($fuel_out_stmt) {
                $fuel_out_stmt->bind_param('ss', $date_from, $date_to);
                $fuel_out_stmt->execute();
                $fuel_out_result = $fuel_out_stmt->get_result();
                while ($row = $fuel_out_result->fetch_assoc()) {
                    $fuel_out_records[] = $row;
                }
                $fuel_out_stmt->close();
            }
        }
        
        // If no fuel_in/fuel_out tables, get from fuel_transactions
        if (empty($fuel_in_records) && empty($fuel_out_records) && in_array('fuel_transactions', $existing_tables)) {
            // Get IN transactions
            $in_sql = "SELECT 
                          id,
                          DATE(transaction_date) as fuel_date,
                          quantity as fuel_quantity,
                          fuel_type,
                          supplier as vehicle_name,
                          '' as plate_number,
                          0 as odometer_reading,
                          notes as purpose,
                          created_at,
                          user_id as created_by
                       FROM fuel_transactions 
                       WHERE transaction_type = 'IN' 
                       AND DATE(transaction_date) BETWEEN ? AND ?
                       ORDER BY transaction_date DESC";
            
            $in_stmt = $conn->prepare($in_sql);
            if ($in_stmt) {
                $in_stmt->bind_param('ss', $date_from, $date_to);
                $in_stmt->execute();
                $in_result = $in_stmt->get_result();
                while ($row = $in_result->fetch_assoc()) {
                    $fuel_in_records[] = $row;
                }
                $in_stmt->close();
            }
            
            // Get OUT transactions
            $out_sql = "SELECT 
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
                        AND DATE(transaction_date) BETWEEN ? AND ?
                        ORDER BY transaction_date DESC";
            
            $out_stmt = $conn->prepare($out_sql);
            if ($out_stmt) {
                $out_stmt->bind_param('ss', $date_from, $date_to);
                $out_stmt->execute();
                $out_result = $out_stmt->get_result();
                while ($row = $out_result->fetch_assoc()) {
                    $fuel_out_records[] = $row;
                }
                $out_stmt->close();
            }
        }
    }
} catch (Exception $e) {
    $error = 'Error loading fuel records: ' . $e->getMessage();
    error_log('Main User Fuel Management Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Management - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .fuel-card {
            transition: transform 0.2s;
        }
        .fuel-card:hover {
            transform: translateY(-2px);
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .badge-fuel-in {
            background-color: #198754 !important;
        }
        .badge-fuel-out {
            background-color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Fuel Management</h4>
                        <p class="text-muted mb-0">Track fuel IN and fuel OUT transactions</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card fuel-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">Fuel IN</h6>
                                        <h3 class="mb-0 text-success"><?php echo number_format(array_sum(array_column($fuel_in_records, 'fuel_quantity')), 2); ?> L</h3>
                                    </div>
                                    <div class="text-success">
                                        <i class="bi bi-arrow-down-circle fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card fuel-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">Fuel OUT</h6>
                                        <h3 class="mb-0 text-danger"><?php echo number_format(array_sum(array_column($fuel_out_records, 'fuel_quantity')), 2); ?> L</h3>
                                    </div>
                                    <div class="text-danger">
                                        <i class="bi bi-arrow-up-circle fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card fuel-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">Net Fuel</h6>
                                        <h3 class="mb-0 text-primary"><?php echo number_format(array_sum(array_column($fuel_in_records, 'fuel_quantity')) - array_sum(array_column($fuel_out_records, 'fuel_quantity')), 2); ?> L</h3>
                                    </div>
                                    <div class="text-primary">
                                        <i class="bi bi-calculator fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card fuel-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">Total Transactions</h6>
                                        <h3 class="mb-0 text-info"><?php echo count($fuel_in_records) + count($fuel_out_records); ?></h3>
                                    </div>
                                    <div class="text-info">
                                        <i class="bi bi-list-check fs-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                    <a href="fuel_management_display.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Fuel IN Section -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-down-circle"></i> Fuel IN Records
                            <span class="badge bg-light text-success ms-2"><?php echo count($fuel_in_records); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($fuel_in_records)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Vehicle</th>
                                            <th>Plate</th>
                                            <th>Quantity (L)</th>
                                            <th>Fuel Type</th>
                                            <th>Purpose</th>
                                            <th>Odometer</th>
                                            <th>Added By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fuel_in_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['fuel_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['vehicle_name'] ?? 'Unknown'); ?></td>
                                                <td><?php echo htmlspecialchars($record['plate_number'] ?? 'N/A'); ?></td>
                                                <td class="fw-semibold text-success"><?php echo number_format($record['fuel_quantity'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($record['fuel_type'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($record['purpose'] ?? ''); ?></td>
                                                <td><?php echo number_format($record['odometer_reading'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($record['created_by'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-arrow-down-circle fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No fuel IN records found for the selected period.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Fuel OUT Section -->
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-up-circle"></i> Fuel OUT Records
                            <span class="badge bg-light text-danger ms-2"><?php echo count($fuel_out_records); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($fuel_out_records)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Vehicle</th>
                                            <th>Plate</th>
                                            <th>Quantity (L)</th>
                                            <th>Fuel Type</th>
                                            <th>Purpose</th>
                                            <th>Odometer</th>
                                            <th>Added By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fuel_out_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['fuel_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['vehicle_name'] ?? 'Unknown'); ?></td>
                                                <td><?php echo htmlspecialchars($record['plate_number'] ?? 'N/A'); ?></td>
                                                <td class="fw-semibold text-danger"><?php echo number_format($record['fuel_quantity'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($record['fuel_type'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($record['purpose'] ?? ''); ?></td>
                                                <td><?php echo number_format($record['odometer_reading'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($record['created_by'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-arrow-up-circle fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No fuel OUT records found for the selected period.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
