<?php
require_once '../config.php';

// Get recent fuel in transactions from fuel_in table
$fuel_in_query = "SELECT fi.*, ft.name as fuel_type_name, u.first_name, u.last_name 
                 FROM fuel_in fi 
                 LEFT JOIN fuel_types ft ON fi.fuel_type = ft.id 
                 LEFT JOIN users u ON fi.received_by = u.id 
                 ORDER BY fi.date_time DESC 
                 LIMIT 50";
$fuel_in_result = $conn->query($fuel_in_query);

// Get today's fuel in summary
$today_fuel_in_query = "SELECT ft.name as fuel_type, SUM(fi.quantity) as total_quantity 
                        FROM fuel_in fi 
                        LEFT JOIN fuel_types ft ON fi.fuel_type = ft.id 
                        WHERE DATE(fi.date_time) = CURDATE() 
                        GROUP BY ft.name";
$today_fuel_in_result = $conn->query($today_fuel_in_query);

// Get fuel types for dropdown
$fuel_types_query = "SELECT id, name FROM fuel_types WHERE is_active = 1 ORDER BY name";
$fuel_types_result = $conn->query($fuel_types_query);
?>

<!-- Page Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Fuel In Transactions</h4>
    <div>
        <button class="btn btn-success btn-sm" onclick="showFuelInModal()">
            <i class="bi bi-plus-circle"></i> Record Fuel In
        </button>
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

<!-- Transactions Table -->
<div class="table-responsive">
    <table class="table table-hover" id="fuelInTable">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>Fuel Type</th>
                <th>Quantity (L)</th>
                <th>Unit Price</th>
                <th>Total Cost</th>
                <th>Supplier</th>
                <th>Storage Location</th>
                <th>Received By</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($fuel_in_result->num_rows > 0): ?>
                <?php while ($transaction = $fuel_in_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($transaction['date_time'])); ?></td>
                        <td>
                            <span class="fuel-type-badge fuel-type-<?php echo strtolower(htmlspecialchars($transaction['fuel_type_name'] ?? 'unknown')); ?>">
                                <?php echo ucfirst(htmlspecialchars($transaction['fuel_type_name'] ?? 'Unknown')); ?>
                            </span>
                        </td>
                        <td><strong><?php echo number_format($transaction['quantity'], 2); ?></strong></td>
                        <td><?php echo number_format($transaction['unit_price'], 2); ?></td>
                        <td><?php echo number_format($transaction['total_cost'], 2); ?></td>
                        <td><?php echo htmlspecialchars($transaction['supplier_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['storage_location'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(($transaction['first_name'] ?? '') . ' ' . ($transaction['last_name'] ?? '')); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center py-4">
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

<!-- Fuel In Modal -->
<div class="modal fade" id="fuelInModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Fuel In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="fuelInForm" action="fuel_tabs/process_fuel.php" method="POST">
                    <input type="hidden" name="action" value="add-fuel-in">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fuel Type *</label>
                            <select name="fuel_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <?php 
                                $fuel_types_result->data_seek(0);
                                while ($fuel_type = $fuel_types_result->fetch_assoc()): ?>
                                    <option value="<?php echo $fuel_type['id']; ?>">
                                        <?php echo htmlspecialchars($fuel_type['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity (Liters) *</label>
                            <input type="number" name="quantity" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Price</label>
                            <input type="number" name="unit_price" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier_name" class="form-control" placeholder="Supplier name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Storage Location</label>
                            <input type="text" name="storage_location" class="form-control" placeholder="e.g., Main Tank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Delivery Receipt #</label>
                            <input type="text" name="delivery_receipt" class="form-control" placeholder="Receipt number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="fuelInForm" class="btn btn-success">Record Fuel In</button>
            </div>
        </div>
    </div>
</div>

<script>
function showFuelInModal() {
    const modal = new bootstrap.Modal(document.getElementById('fuelInModal'));
    modal.show();
}

function viewTransaction(id) {
    // Implementation for viewing transaction details
    alert('View transaction details for ID: ' + id);
}

function deleteTransaction(id) {
    if (confirm('Are you sure you want to delete this transaction?')) {
        fetch('fuel_tabs/process_fuel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete-fuel-in&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting transaction');
        });
    }
}

// Initialize DataTable
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
