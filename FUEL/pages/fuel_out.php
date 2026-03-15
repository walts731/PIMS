<?php
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

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
                    // Insert fuel transaction
                    $insert_sql = "INSERT INTO fuel_transactions 
                                  (transaction_type, transaction_date, quantity, fuel_type, 
                                   vehicle_equipment, purpose, tank_number, odometer_reading, 
                                   driver_name, department, user_id, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param('ssdssssdsi', 
                        $transaction_type = 'OUT',
                        $transaction_date,
                        $quantity,
                        $fuel_type,
                        $vehicle_equipment,
                        $purpose,
                        $tank_number,
                        $odometer_reading,
                        $driver_name,
                        $department,
                        $_SESSION['user_id']
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
        
        header('Location: ../dashboard.php?page=fuelout');
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
?>

<!-- Fuel OUT Management -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h5><i class="bi bi-arrow-up-circle me-2 text-danger"></i>Fuel Out Management</h5>
            <p class="text-muted mb-0">Record fuel dispensing and vehicle refueling</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addFuelOutModal">
                <i class="bi bi-plus-circle me-2"></i>Dispense Fuel
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Today's Fuel OUT</h6>
                    <h4 class="text-danger mb-0">
                        <?php 
                        $today_total = array_sum(array_filter(array_column($fuel_out_records, 'quantity'), function($q) {
                            return date('Y-m-d') === date('Y-m-d', strtotime($q));
                        }));
                        echo number_format($today_total, 2); 
                        ?> L
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">This Week</h6>
                    <h4 class="text-info mb-0">
                        <?php 
                        $week_total = array_sum(array_filter(array_column($fuel_out_records, 'quantity'), function($q) {
                            return date('W') === date('W');
                        }));
                        echo number_format($week_total, 2); 
                        ?> L
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Transactions</h6>
                    <h4 class="text-warning mb-0"><?php echo count($fuel_out_records); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Available Fuel</h6>
                    <h4 class="text-success mb-0">
                        <?php 
                        $total_available = array_sum(array_column($available_tanks, 'current_level'));
                        echo number_format($total_available, 2); 
                        ?> L
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Tank Status Overview -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-fuel-pump me-2"></i>Tank Status Overview</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($available_tanks as $tank): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
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
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Dispense Fuel Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Quick Dispense Fuel</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="../dashboard.php?page=fuelout">
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
                        <button type="submit" class="btn btn-danger w-100" onclick="return validateFuelQuantity()">
                            <i class="bi bi-arrow-up-circle me-2"></i>Dispense Fuel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Fuel OUT Transactions -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Recent Fuel OUT Transactions
                <span class="badge bg-danger text-white ms-2"><?php echo count($fuel_out_records); ?></span>
            </h6>
        </div>
        <div class="card-body">
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
