<?php
require_once '../config.php';

// Get recent fuel out transactions
$fuel_out_query = "SELECT ft.*, u.first_name, u.last_name, e.firstname as emp_firstname, e.lastname as emp_lastname
                   FROM fuel_transactions ft 
                   LEFT JOIN users u ON ft.user_id = u.id 
                   LEFT JOIN employees e ON ft.employee_id = e.id
                   WHERE ft.transaction_type = 'OUT' 
                   ORDER BY ft.created_at DESC 
                   LIMIT 50";
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
?>

<!-- Page Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Fuel Out Transactions</h4>
    <div>
        <button class="btn btn-danger btn-sm" onclick="showFuelOutModal()">
            <i class="bi bi-dash-circle"></i> Record Fuel Out
        </button>
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

<!-- Transactions Table -->
<div class="table-responsive">
    <table class="table table-hover" id="fuelOutTable">
        <thead>
            <tr>
                <th>Date</th>
                <th>Fuel Type</th>
                <th>Quantity (L)</th>
                <th>Employee</th>
                <th>Purpose</th>
                <th>Vehicle/Equipment</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($fuel_out_result->num_rows > 0): ?>
                <?php while ($transaction = $fuel_out_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                        <td>
                            <span class="fuel-type-badge fuel-type-<?php echo htmlspecialchars($transaction['fuel_type']); ?>">
                                <?php echo ucfirst(htmlspecialchars($transaction['fuel_type'])); ?>
                            </span>
                        </td>
                        <td><strong><?php echo number_format($transaction['quantity'], 2); ?></strong></td>
                        <td>
                            <?php 
                            if ($transaction['employee_id']) {
                                echo htmlspecialchars($transaction['emp_firstname'] . ' ' . $transaction['emp_lastname']);
                            } else {
                                echo htmlspecialchars($transaction['recipient_name'] ?? 'N/A');
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($transaction['purpose']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['vehicle_equipment'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-arrow-up-circle" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0">No fuel out transactions found</p>
                            <small>Record your first fuel dispensing</small>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Fuel Out Modal -->
<div class="modal fade" id="fuelOutModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Fuel Out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="fuelOutForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fuel Type *</label>
                            <select name="fo_fuel_type" class="form-select" required>
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
                            <label class="form-label">Fuel No</label>
                            <input type="text" name="fo_fuel_no" class="form-control" placeholder="Fuel number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plate No</label>
                            <input type="text" name="fo_plate_no" class="form-control" placeholder="Vehicle plate number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Request</label>
                            <input type="text" name="fo_request" class="form-control" placeholder="Purpose of fuel usage">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Liters *</label>
                            <input type="number" name="fo_liters" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vehicle Type</label>
                            <input type="text" name="fo_vehicle_type" class="form-control" placeholder="e.g., Sedan, SUV, Truck">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receiver</label>
                            <input type="text" name="fo_receiver" class="form-control" placeholder="Name of receiver">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time In</label>
                            <input type="time" name="fo_time_in" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time Out</label>
                            <input type="time" name="fo_time_out" class="form-control">
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
                <button type="submit" form="fuelOutForm" class="btn btn-danger">Record Fuel Out</button>
            </div>
        </div>
    </div>
</div>

<script>
function showFuelOutModal() {
    const modal = new bootstrap.Modal(document.getElementById('fuelOutModal'));
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
            body: `action=delete-fuel-out&id=${id}`
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

// Handle full form
document.getElementById('fuelOutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add-fuel-out');
    
    fetch('fuel_tabs/process_fuel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('fuelOutModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error recording fuel out');
    });
});

// Initialize DataTable
$(document).ready(function() {
    $('#fuelOutTable').DataTable({
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
