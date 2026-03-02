<?php
require_once '../config.php';

// Get date range from GET parameters or default to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$fuel_type_filter = $_GET['fuel_type'] ?? '';

// Build base query
$where_conditions = ["transaction_date BETWEEN '$start_date' AND '$end_date'"];
$params = [];
$types = '';

if ($fuel_type_filter) {
    $where_conditions[] = "fuel_type = ?";
    $params[] = $fuel_type_filter;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get fuel summary
$summary_query = "SELECT 
                    fuel_type,
                    transaction_type,
                    SUM(quantity) as total_quantity,
                    COUNT(*) as transaction_count
                 FROM fuel_transactions 
                 WHERE $where_clause
                 GROUP BY fuel_type, transaction_type
                 ORDER BY fuel_type, transaction_type";

$stmt = $conn->prepare($summary_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$summary_result = $stmt->get_result();

// Get detailed transactions
$transactions_query = "SELECT ft.*, u.first_name, u.last_name, e.firstname, e.lastname as employee_lastname
                      FROM fuel_transactions ft 
                      LEFT JOIN users u ON ft.user_id = u.id 
                      LEFT JOIN employees e ON ft.employee_id = e.id
                      WHERE $where_clause
                      ORDER BY ft.transaction_date DESC, ft.created_at DESC";

$stmt = $conn->prepare($transactions_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions_result = $stmt->get_result();

// Get available fuel types for filters
$fuel_types_query = "SELECT DISTINCT fuel_type FROM fuel_transactions ORDER BY fuel_type";
$fuel_types_result = $conn->query($fuel_types_query);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h4>Fuel Reports</h4>
        <p class="text-muted">Analyze fuel consumption and inventory trends</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-primary" onclick="exportReport()">
            <i class="bi bi-download"></i> Export Report
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fuel Type</label>
                <select name="fuel_type" class="form-select">
                    <option value="">All Types</option>
                    <?php while ($type = $fuel_types_result->fetch_assoc()): ?>
                        <option value="<?php echo $type['fuel_type']; ?>" <?php echo $fuel_type_filter == $type['fuel_type'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type['fuel_type']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <?php
    $summary_data = [];
    while ($row = $summary_result->fetch_assoc()) {
        $summary_data[$row['fuel_type']][$row['transaction_type']] = $row;
    }
    
    foreach ($summary_data as $fuel_type => $data): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><?php echo ucfirst($fuel_type); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-success"><?php echo number_format($data['IN']['total_quantity'] ?? 0, 2); ?></h5>
                            <small>Fuel In (L)</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-danger"><?php echo number_format($data['OUT']['total_quantity'] ?? 0, 2); ?></h5>
                            <small>Fuel Out (L)</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h6 class="text-primary">
                            <?php 
                            $net = ($data['IN']['total_quantity'] ?? 0) - ($data['OUT']['total_quantity'] ?? 0);
                            echo number_format($net, 2); 
                            ?>
                        </h6>
                        <small>Net Change (L)</small>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Detailed Transactions -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Transaction Details</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="reportsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Fuel Type</th>
                        <th>Quantity (L)</th>
                        <th>Employee/Source</th>
                        <th>Purpose/Supplier</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions_result->num_rows > 0): ?>
                        <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($transaction['transaction_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['transaction_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                        <?php echo $transaction['transaction_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fuel-type-badge fuel-type-<?php echo $transaction['fuel_type']; ?>">
                                        <?php echo ucfirst($transaction['fuel_type']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($transaction['quantity'], 2); ?></strong></td>
                                <td>
                                    <?php if ($transaction['transaction_type'] == 'IN'): ?>
                                        <?php echo htmlspecialchars($transaction['source']); ?>
                                    <?php else: ?>
                                        <?php 
                                        if ($transaction['employee_id']) {
                                            echo htmlspecialchars($transaction['firstname'] . ' ' . $transaction['employee_lastname']);
                                        } else {
                                            echo htmlspecialchars($transaction['recipient_name'] ?? 'N/A');
                                        }
                                        ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transaction['transaction_type'] == 'IN'): ?>
                                        <?php echo htmlspecialchars($transaction['supplier'] ?? 'N/A'); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($transaction['purpose']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="py-4">
                                    <i class="bi bi-file-earmark-bar-graph" style="font-size: 3rem; color: #6c757d;"></i>
                                    <p class="mt-2 mb-0">No transactions found for the selected period</p>
                                    <small>Try adjusting your filters</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#reportsTable').DataTable({
        responsive: true,
        order: [[0, 'desc'], [1, 'desc']],
        pageLength: 50,
        language: {
            search: "Search transactions:",
            lengthMenu: "Show _MENU_ transactions per page"
        }
    });
});

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    
    window.open('fuel_tabs/export_fuel_report.php?' + params.toString(), '_blank');
}
</script>
