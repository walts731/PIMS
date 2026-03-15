<?php
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_fuel_in') {
        $fuel_type = $_POST['fuel_type'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $supplier = $_POST['supplier'] ?? '';
        $vehicle_equipment = $_POST['vehicle_equipment'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $tank_number = $_POST['tank_number'] ?? '';
        $odometer_reading = $_POST['odometer_reading'] ?? 0;
        $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
        
        if (!empty($fuel_type) && $quantity > 0 && !empty($purpose)) {
            // Insert fuel transaction
            $insert_sql = "INSERT INTO fuel_transactions 
                          (transaction_type, transaction_date, quantity, fuel_type, supplier, 
                           vehicle_equipment, purpose, tank_number, odometer_reading, user_id, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('ssdssssdsi', 
                $transaction_type = 'IN',
                $transaction_date,
                $quantity,
                $fuel_type,
                $supplier,
                $vehicle_equipment,
                $purpose,
                $tank_number,
                $odometer_reading,
                $_SESSION['user_id']
            );
            
            if ($stmt->execute()) {
                // Update fuel inventory if tank is specified
                if (!empty($tank_number)) {
                    $update_sql = "UPDATE fuel_inventory SET current_level = current_level + ?, last_updated = NOW() WHERE tank_number = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('ds', $quantity, $tank_number);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                $_SESSION['fuel_success'] = 'Fuel IN transaction added successfully!';
                logSystemAction($_SESSION['user_id'], 'create', 'fuel_in', "Added fuel IN: {$quantity}L of {$fuel_type}");
            } else {
                $_SESSION['fuel_error'] = 'Error adding fuel IN transaction: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['fuel_error'] = 'Please fill all required fields.';
        }
        
        header('Location: ../dashboard.php?page=fuelin');
        exit();
    }
}

// Get recent fuel IN transactions
$fuel_in_records = [];
try {
    $fuel_in_sql = "SELECT 
                      id,
                      transaction_date,
                      quantity,
                      fuel_type,
                      supplier,
                      vehicle_equipment,
                      purpose,
                      tank_number,
                      odometer_reading,
                      user_id,
                      created_at
                   FROM fuel_transactions 
                   WHERE transaction_type = 'IN' 
                   ORDER BY transaction_date DESC 
                   LIMIT 50";
    $fuel_in_result = $conn->query($fuel_in_sql);
    if ($fuel_in_result) {
        while ($row = $fuel_in_result->fetch_assoc()) {
            $fuel_in_records[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Fuel IN Error: ' . $e->getMessage());
}

// Get available tanks
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
?>

<!-- Fuel IN Management -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h5><i class="bi bi-arrow-down-circle me-2 text-success"></i>Fuel In Management</h5>
            <p class="text-muted mb-0">Record fuel deliveries and refueling operations</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFuelInModal">
                <i class="bi bi-plus-circle me-2"></i>Add Fuel IN
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Today's Fuel IN</h6>
                    <h4 class="text-success mb-0">
                        <?php 
                        $today_total = array_sum(array_filter(array_column($fuel_in_records, 'quantity'), function($q) {
                            return date('Y-m-d') === date('Y-m-d', strtotime($q));
                        }));
                        echo number_format($today_total, 2); 
                        ?> L
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">This Week</h6>
                    <h4 class="text-info mb-0">
                        <?php 
                        $week_total = array_sum(array_filter(array_column($fuel_in_records, 'quantity'), function($q) {
                            return date('W') === date('W');
                        }));
                        echo number_format($week_total, 2); 
                        ?> L
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Transactions</h6>
                    <h4 class="text-warning mb-0"><?php echo count($fuel_in_records); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Fuel IN Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Quick Add Fuel IN</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="../dashboard.php?page=fuelin">
                <input type="hidden" name="action" value="add_fuel_in">
                <div class="row">
                    <div class="col-md-3">
                        <label for="fuel_type" class="form-label">Fuel Type *</label>
                        <select class="form-select" id="fuel_type" name="fuel_type" required>
                            <option value="">Select fuel type</option>
                            <option value="diesel">Diesel</option>
                            <option value="gasoline">Gasoline</option>
                            <option value="premium">Premium Gasoline</option>
                            <option value="kerosene">Kerosene</option>
                            <option value="lpg">LPG</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="quantity" class="form-label">Quantity (L) *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <label for="supplier" class="form-label">Supplier</label>
                        <input type="text" class="form-control" id="supplier" name="supplier" placeholder="Supplier name">
                    </div>
                    <div class="col-md-2">
                        <label for="tank_number" class="form-label">Target Tank</label>
                        <select class="form-select" id="tank_number" name="tank_number">
                            <option value="">Select tank</option>
                            <?php foreach ($available_tanks as $tank): ?>
                                <option value="<?php echo htmlspecialchars($tank['tank_number']); ?>">
                                    <?php echo htmlspecialchars($tank['tank_number']); ?> - <?php echo htmlspecialchars($tank['fuel_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="transaction_date" class="form-label">Date/Time</label>
                        <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                               value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="vehicle_equipment" class="form-label">Vehicle/Equipment</label>
                        <input type="text" class="form-control" id="vehicle_equipment" name="vehicle_equipment" placeholder="Vehicle or equipment">
                    </div>
                    <div class="col-md-2">
                        <label for="odometer_reading" class="form-label">Odometer</label>
                        <input type="number" class="form-control" id="odometer_reading" name="odometer_reading" placeholder="km">
                    </div>
                    <div class="col-md-4">
                        <label for="purpose" class="form-label">Purpose *</label>
                        <input type="text" class="form-control" id="purpose" name="purpose" placeholder="Purpose of fuel addition" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle me-2"></i>Add Fuel IN
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Fuel IN Transactions -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Recent Fuel IN Transactions
                <span class="badge bg-success text-white ms-2"><?php echo count($fuel_in_records); ?></span>
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($fuel_in_records)): ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar me-1"></i>Date</th>
                                <th><i class="bi bi-tag me-1"></i>Fuel Type</th>
                                <th><i class="bi bi-droplet me-1"></i>Quantity</th>
                                <th><i class="bi bi-building me-1"></i>Supplier</th>
                                <th><i class="bi bi-fuel-pump me-1"></i>Tank</th>
                                <th><i class="bi bi-truck me-1"></i>Vehicle/Equipment</th>
                                <th><i class="bi bi-chat-left-text me-1"></i>Purpose</th>
                                <th><i class="bi bi-person me-1"></i>User</th>
                                <th><i class="bi bi-gear me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fuel_in_records as $record): ?>
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
                                        <strong class="text-success"><?php echo number_format($record['quantity'], 2); ?> L</strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['supplier'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($record['tank_number'])): ?>
                                            <span class="badge bg-primary text-white">
                                                <?php echo htmlspecialchars($record['tank_number']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['vehicle_equipment'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                                    <td>
                                        <small class="text-muted">ID: <?php echo $record['user_id']; ?></small>
                                    </td>
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
                    <i class="bi bi-arrow-down-circle text-muted" style="font-size: 3rem;"></i>
                    <h6 class="text-muted mt-3">No Fuel IN Transactions Found</h6>
                    <p class="text-muted">Start by adding your first fuel IN transaction above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function viewTransaction(transactionId) {
    fetch('pages/get_transaction.php?id=' + transactionId)
        .then(response => response.json())
        .then(data => {
            alert('Transaction Details:\n\nID: ' + data.id + '\nType: ' + data.transaction_type + '\nFuel Type: ' + data.fuel_type + '\nQuantity: ' + data.quantity + 'L\nPurpose: ' + data.purpose);
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
