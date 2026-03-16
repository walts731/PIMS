<?php
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

// Get report parameters
$report_type = $_GET['report_type'] ?? 'summary';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$fuel_type_filter = $_GET['fuel_type'] ?? '';

// Initialize report data
$report_data = [];
$summary_stats = [];

try {
    switch ($report_type) {
        case 'summary':
            // Get summary statistics
            $summary_sql = "SELECT 
                              transaction_type,
                              fuel_type,
                              SUM(quantity) as total_quantity,
                              COUNT(*) as transaction_count,
                              AVG(quantity) as avg_quantity,
                              MIN(quantity) as min_quantity,
                              MAX(quantity) as max_quantity
                           FROM fuel_transactions 
                           WHERE DATE(transaction_date) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = 'ss';
            
            if (!empty($fuel_type_filter)) {
                $summary_sql .= " AND fuel_type = ?";
                $params[] = $fuel_type_filter;
                $types .= 's';
            }
            
            $summary_sql .= " GROUP BY transaction_type, fuel_type ORDER BY total_quantity DESC";
            
            $stmt = $conn->prepare($summary_sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $stmt->close();
            
            // Calculate overall stats
            $total_in = 0;
            $total_out = 0;
            $total_transactions = 0;
            
            foreach ($report_data as $row) {
                if ($row['transaction_type'] === 'IN') {
                    $total_in += $row['total_quantity'];
                } else {
                    $total_out += $row['total_quantity'];
                }
                $total_transactions += $row['transaction_count'];
            }
            
            $summary_stats = [
                'total_fuel_in' => $total_in,
                'total_fuel_out' => $total_out,
                'net_balance' => $total_in - $total_out,
                'total_transactions' => $total_transactions,
                'avg_transaction_size' => $total_transactions > 0 ? ($total_in + $total_out) / $total_transactions : 0
            ];
            break;
            
        case 'transactions':
            // Get detailed transactions
            $transactions_sql = "SELECT 
                                   id,
                                   transaction_type,
                                   transaction_date,
                                   quantity,
                                   fuel_type,
                                   supplier,
                                   vehicle_equipment,
                                   purpose,
                                   tank_number,
                                   driver_name,
                                   department,
                                   user_id,
                                   created_at
                                FROM fuel_transactions 
                                WHERE DATE(transaction_date) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = 'ss';
            
            if (!empty($fuel_type_filter)) {
                $transactions_sql .= " AND fuel_type = ?";
                $params[] = $fuel_type_filter;
                $types .= 's';
            }
            
            $transactions_sql .= " ORDER BY transaction_date DESC";
            
            $stmt = $conn->prepare($transactions_sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $stmt->close();
            break;
            
        case 'inventory':
            // Get inventory status
            $inventory_sql = "SELECT 
                                tank_number,
                                fuel_type,
                                capacity,
                                current_level,
                                location,
                                status,
                                last_updated
                              FROM fuel_inventory 
                              ORDER BY fuel_type, tank_number";
            
            $result = $conn->query($inventory_sql);
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            break;
    }
} catch (Exception $e) {
    error_log('Reports Error: ' . $e->getMessage());
    $error = 'Error generating report: ' . $e->getMessage();
}
?>

<!-- Fuel Reports -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h5><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Fuel Reports</h5>
            <p class="text-muted mb-0">Generate comprehensive reports and analytics</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" onclick="exportReport()">
                <i class="bi bi-download me-2"></i>Export Report
            </button>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Report Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="../dashboard.php">
                <input type="hidden" name="page" value="reports">
                <div class="row">
                    <div class="col-md-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                            <option value="summary" <?php echo $report_type === 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                            <option value="transactions" <?php echo $report_type === 'transactions' ? 'selected' : ''; ?>>Detailed Transactions</option>
                            <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Status</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo $date_from; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo $date_to; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label for="fuel_type" class="form-label">Fuel Type</label>
                        <select class="form-select" id="fuel_type" name="fuel_type" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="diesel" <?php echo $fuel_type_filter === 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="gasoline" <?php echo $fuel_type_filter === 'gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                            <option value="premium" <?php echo $fuel_type_filter === 'premium' ? 'selected' : ''; ?>>Premium</option>
                            <option value="kerosene" <?php echo $fuel_type_filter === 'kerosene' ? 'selected' : ''; ?>>Kerosene</option>
                            <option value="lpg" <?php echo $fuel_type_filter === 'lpg' ? 'selected' : ''; ?>>LPG</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="resetFilters()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="printReport()">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($report_type === 'summary'): ?>
        <!-- Summary Report -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Fuel IN</h6>
                        <h4 class="text-success mb-0"><?php echo number_format($summary_stats['total_fuel_in'], 2); ?> L</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Fuel OUT</h6>
                        <h4 class="text-danger mb-0"><?php echo number_format($summary_stats['total_fuel_out'], 2); ?> L</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Net Balance</h6>
                        <h4 class="text-info mb-0"><?php echo number_format($summary_stats['net_balance'], 2); ?> L</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Transactions</h6>
                        <h4 class="text-warning mb-0"><?php echo $summary_stats['total_transactions']; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Summary by Fuel Type</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fuel Type</th>
                                <th>Transaction Type</th>
                                <th>Total Quantity (L)</th>
                                <th>Transaction Count</th>
                                <th>Avg Quantity (L)</th>
                                <th>Min Quantity (L)</th>
                                <th>Max Quantity (L)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <?php echo htmlspecialchars(ucfirst($row['fuel_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['transaction_type'] === 'IN' ? 'success' : 'danger'; ?> text-white">
                                            <?php echo $row['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo number_format($row['total_quantity'], 2); ?></strong></td>
                                    <td><?php echo $row['transaction_count']; ?></td>
                                    <td><?php echo number_format($row['avg_quantity'], 2); ?></td>
                                    <td><?php echo number_format($row['min_quantity'], 2); ?></td>
                                    <td><?php echo number_format($row['max_quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($report_type === 'transactions'): ?>
        <!-- Detailed Transactions Report -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    Detailed Transactions
                    <span class="badge bg-primary text-white ms-2"><?php echo count($report_data); ?></span>
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Fuel Type</th>
                                <th>Quantity (L)</th>
                                <th>Supplier</th>
                                <th>Vehicle/Equipment</th>
                                <th>Tank</th>
                                <th>Driver</th>
                                <th>Purpose</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($row['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['transaction_type'] === 'IN' ? 'success' : 'danger'; ?> text-white">
                                            <?php echo $row['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(ucfirst($row['fuel_type'])); ?></td>
                                    <td><?php echo number_format($row['quantity'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['vehicle_equipment'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['tank_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['driver_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td><?php echo $row['user_id']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($report_type === 'inventory'): ?>
        <!-- Inventory Status Report -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Current Inventory Status</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Tank Number</th>
                                <th>Fuel Type</th>
                                <th>Capacity (L)</th>
                                <th>Current Level (L)</th>
                                <th>Fill %</th>
                                <th>Available (L)</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['tank_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(ucfirst($row['fuel_type'])); ?></td>
                                    <td><?php echo number_format($row['capacity'], 2); ?></td>
                                    <td><?php echo number_format($row['current_level'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $percentage = ($row['current_level'] / $row['capacity']) * 100;
                                        $badge_class = 'success';
                                        if ($percentage < 20) $badge_class = 'danger';
                                        elseif ($percentage < 50) $badge_class = 'warning';
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?> text-white">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </td>
                                    <td><?php echo number_format($row['current_level'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?> text-white">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($row['last_updated'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.open('pages/export_fuel_report.php?' + params.toString(), '_blank');
}

function printReport() {
    window.print();
}

function resetFilters() {
    window.location.href = '../dashboard.php?page=reports';
}
</script>

<style>
@media print {
    .btn, .card-header, .modal {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
}
</style>
