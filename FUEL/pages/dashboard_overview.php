<?php
// Dashboard Overview Page
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

// Get recent transactions for overview
$recent_transactions = [];
try {
    $recent_sql = "SELECT 
                     id,
                     transaction_type,
                     transaction_date,
                     quantity,
                     fuel_type,
                     vehicle_equipment,
                     purpose,
                     user_id
                   FROM fuel_transactions 
                   ORDER BY transaction_date DESC 
                   LIMIT 10";
    $result = $conn->query($recent_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_transactions[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Recent Transactions Error: ' . $e->getMessage());
}
?>

<!-- Dashboard Overview Content -->
<div class="row">
    <div class="col-12">
        <div class="table-container">
            <h5 class="mb-3">
                <i class="bi bi-clock-history me-2"></i>Recent Transactions
            </h5>
            <?php if (!empty($recent_transactions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Fuel Type</th>
                                <th>Vehicle/Equipment</th>
                                <th>Purpose</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['transaction_type'] === 'IN' ? 'success' : 'danger'; ?> text-white">
                                            <?php echo $transaction['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($transaction['quantity'], 2); ?> L</td>
                                    <td><?php echo htmlspecialchars(ucfirst($transaction['fuel_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['vehicle_equipment'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['purpose']); ?></td>
                                    <td><?php echo $transaction['user_id']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                    <h6 class="text-muted mt-3">No Recent Transactions</h6>
                    <p class="text-muted">Start by adding fuel transactions using the modules above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
