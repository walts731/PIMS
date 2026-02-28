<?php
require_once '../config.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

switch ($type) {
    case 'add-tank':
        showAddTankForm();
        break;
    case 'edit-tank':
        showEditTankForm($id);
        break;
    case 'fuel-in':
        showFuelInForm();
        break;
    case 'fuel-out':
        showFuelOutForm();
        break;
    case 'edit-transaction':
        showEditTransactionForm($id);
        break;
    default:
        echo '<div class="alert alert-danger">Invalid modal type</div>';
}

function showAddTankForm() {
    ?>
    <form id="addTankForm" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tank Number *</label>
            <input type="text" name="tank_number" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Fuel Type *</label>
            <select name="fuel_type" class="form-select" required>
                <option value="">Select Type</option>
                <option value="diesel">Diesel</option>
                <option value="gasoline">Gasoline</option>
                <option value="premium">Premium</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Capacity (Liters) *</label>
            <input type="number" name="capacity" class="form-control" step="0.01" min="0" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Current Level (Liters)</label>
            <input type="number" name="current_level" class="form-control" step="0.01" min="0" value="0">
        </div>
        <div class="col-12">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g., Main Station, Backup Tank">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Tank
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
    
    <script>
    $('#addTankForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add-tank');
        
        fetch('fuel_tabs/process_fuel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('fuelModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing request');
        });
    });
    </script>
    <?php
}

function showEditTankForm($tank_id) {
    global $conn;
    
    $sql = "SELECT * FROM fuel_inventory WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $tank_id);
    $stmt->execute();
    $tank = $stmt->get_result()->fetch_assoc();
    
    if (!$tank) {
        echo '<div class="alert alert-danger">Tank not found</div>';
        return;
    }
    
    ?>
    <form id="editTankForm" class="row g-3">
        <input type="hidden" name="tank_id" value="<?php echo $tank['id']; ?>">
        <div class="col-md-6">
            <label class="form-label">Tank Number *</label>
            <input type="text" name="tank_number" class="form-control" value="<?php echo htmlspecialchars($tank['tank_number']); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Fuel Type *</label>
            <select name="fuel_type" class="form-select" required>
                <option value="">Select Type</option>
                <option value="diesel" <?php echo $tank['fuel_type'] == 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                <option value="gasoline" <?php echo $tank['fuel_type'] == 'gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                <option value="premium" <?php echo $tank['fuel_type'] == 'premium' ? 'selected' : ''; ?>>Premium</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Capacity (Liters) *</label>
            <input type="number" name="capacity" class="form-control" step="0.01" min="0" value="<?php echo $tank['capacity']; ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Current Level (Liters)</label>
            <input type="number" name="current_level" class="form-control" step="0.01" min="0" value="<?php echo $tank['current_level']; ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($tank['location']); ?>" placeholder="e.g., Main Station, Backup Tank">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Update Tank
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
    
    <script>
    $('#editTankForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'edit-tank');
        
        fetch('fuel_tabs/process_fuel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('fuelModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing request');
        });
    });
    </script>
    <?php
}

function showFuelInForm() {
    global $conn;
    
    // Get available tanks
    $tanks_query = "SELECT * FROM fuel_inventory ORDER BY fuel_type, tank_number";
    $tanks_result = $conn->query($tanks_query);
    
    ?>
    <form id="fuelInForm" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Fuel Type *</label>
            <select name="fuel_type" class="form-select" required>
                <option value="">Select Type</option>
                <option value="diesel">Diesel</option>
                <option value="gasoline">Gasoline</option>
                <option value="premium">Premium</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Quantity (Liters) *</label>
            <input type="number" name="quantity" class="form-control" step="0.01" min="0" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Source *</label>
            <select name="source" class="form-select" required>
                <option value="">Select Source</option>
                <option value="Delivery">Delivery</option>
                <option value="Transfer">Transfer</option>
                <option value="Refill">Refill</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Supplier</label>
            <input type="text" name="supplier" class="form-control" placeholder="Supplier name">
        </div>
        <div class="col-12">
            <label class="form-label">Target Tank</label>
            <select name="tank_number" class="form-select">
                <option value="">Auto-assign</option>
                <?php while ($tank = $tanks_result->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($tank['tank_number']); ?>">
                        <?php echo htmlspecialchars($tank['tank_number']); ?> - <?php echo ucfirst($tank['fuel_type']); ?>
                        (<?php echo number_format($tank['capacity'] - $tank['current_level'], 2); ?> L available)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Record Fuel In
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
    
    <script>
    $('#fuelInForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'fuel-in');
        
        fetch('fuel_tabs/process_fuel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('fuelModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing request');
        });
    });
    </script>
    <?php
}

function showFuelOutForm() {
    global $conn;
    
    // Get employees
    $employees_query = "SELECT id, firstname, lastname FROM employees WHERE employment_status = 'permanent' ORDER BY firstname, lastname";
    $employees_result = $conn->query($employees_query);
    
    ?>
    <form id="fuelOutForm" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Fuel Type *</label>
            <select name="fuel_type" class="form-select" required>
                <option value="">Select Type</option>
                <option value="diesel">Diesel</option>
                <option value="gasoline">Gasoline</option>
                <option value="premium">Premium</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Quantity (Liters) *</label>
            <input type="number" name="quantity" class="form-control" step="0.01" min="0" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Employee</label>
            <select name="employee_id" class="form-select">
                <option value="">Select Employee</option>
                <?php while ($employee = $employees_result->fetch_assoc()): ?>
                    <option value="<?php echo $employee['id']; ?>">
                        <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Recipient Name</label>
            <input type="text" name="recipient_name" class="form-control" placeholder="If no employee selected">
        </div>
        <div class="col-md-6">
            <label class="form-label">Purpose *</label>
            <input type="text" name="purpose" class="form-control" placeholder="e.g., Travel, Operations" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Vehicle/Equipment</label>
            <input type="text" name="vehicle_equipment" class="form-control" placeholder="Vehicle ID or equipment name">
        </div>
        <div class="col-md-6">
            <label class="form-label">Odometer Reading</label>
            <input type="number" name="odometer_reading" class="form-control" placeholder="Current odometer">
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-dash-circle"></i> Record Fuel Out
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>
    
    <script>
    $('#fuelOutForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'fuel-out');
        
        fetch('fuel_tabs/process_fuel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('fuelModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing request');
        });
    });
    </script>
    <?php
}

function showEditTransactionForm($transaction_id) {
    global $conn;
    
    $sql = "SELECT ft.*, u.first_name, u.last_name, e.firstname, e.lastname as employee_lastname
            FROM fuel_transactions ft 
            LEFT JOIN users u ON ft.user_id = u.id 
            LEFT JOIN employees e ON ft.employee_id = e.id
            WHERE ft.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $transaction_id);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    
    if (!$transaction) {
        echo '<div class="alert alert-danger">Transaction not found</div>';
        return;
    }
    
    ?>
    <div class="alert alert-info">
        <h6>Transaction Details</h6>
        <p><strong>ID:</strong> <?php echo $transaction['id']; ?></p>
        <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($transaction['transaction_date'])); ?></p>
        <p><strong>Type:</strong> <?php echo $transaction['transaction_type']; ?></p>
        <p><strong>Fuel Type:</strong> <?php echo ucfirst($transaction['fuel_type']); ?></p>
        <p><strong>Quantity:</strong> <?php echo number_format($transaction['quantity'], 2); ?> L</p>
        <p><strong>Recorded By:</strong> <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></p>
    </div>
    
    <div class="alert alert-warning">
        <strong>Note:</strong> For audit purposes, fuel transactions cannot be edited. 
        If there was an error, please delete this transaction and create a new one.
    </div>
    
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-danger" onclick="deleteTransaction(<?php echo $transaction['id']; ?>)">
            <i class="bi bi-trash"></i> Delete Transaction
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
    <?php
}
?>
