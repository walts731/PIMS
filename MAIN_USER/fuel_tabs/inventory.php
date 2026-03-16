<?php
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../index.php');
    exit();
}

// Check if user has correct role
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel', 'main_user'])) {
    header('Location: ../../index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_tank') {
        $tank_number = $_POST['tank_number'] ?? '';
        $fuel_type = $_POST['fuel_type'] ?? '';
        $capacity = $_POST['capacity'] ?? 0;
        $location = $_POST['location'] ?? '';
        
        if (!empty($tank_number) && !empty($fuel_type) && $capacity > 0) {
            $insert_sql = "INSERT INTO fuel_inventory (tank_number, fuel_type, capacity, current_level, location, status, created_at, updated_by) 
                          VALUES (?, ?, ?, 0, ?, 'active', NOW(), ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('ssdsi', $tank_number, $fuel_type, $capacity, $location, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['fuel_success'] = 'Fuel tank added successfully!';
                logSystemAction($_SESSION['user_id'], 'create', 'fuel_inventory', "Added fuel tank: $tank_number");
            } else {
                $_SESSION['fuel_error'] = 'Error adding fuel tank: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['fuel_error'] = 'Please fill all required fields.';
        }
        
        header('Location: ../fuel_dashboard.php?tab=inventory');
        exit();
    }
    
    if ($action === 'update_tank') {
        $tank_id = $_POST['tank_id'] ?? 0;
        $current_level = $_POST['current_level'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($tank_id > 0) {
            $update_sql = "UPDATE fuel_inventory SET current_level = ?, status = ?, last_updated = NOW(), updated_by = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('dsii', $current_level, $status, $_SESSION['user_id'], $tank_id);
            
            if ($stmt->execute()) {
                $_SESSION['fuel_success'] = 'Tank updated successfully!';
                logSystemAction($_SESSION['user_id'], 'update', 'fuel_inventory', "Updated tank ID: $tank_id");
            } else {
                $_SESSION['fuel_error'] = 'Error updating tank: ' . $stmt->error;
            }
            $stmt->close();
        }
        
        header('Location: ../fuel_dashboard.php?tab=inventory');
        exit();
    }
}

// Get fuel inventory data
$fuel_inventory = [];
try {
    $inventory_sql = "SELECT 
                        id,
                        tank_number,
                        fuel_type,
                        capacity,
                        current_level,
                        location,
                        status,
                        last_updated,
                        created_at,
                        updated_by
                     FROM fuel_inventory 
                     ORDER BY fuel_type, tank_number";
    $inventory_result = $conn->query($inventory_sql);
    if ($inventory_result) {
        while ($row = $inventory_result->fetch_assoc()) {
            $fuel_inventory[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Inventory Error: ' . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h5><i class="bi bi-fuel-pump me-2"></i>Fuel Inventory Management</h5>
            <p class="text-muted mb-0">Manage fuel tanks and monitor stock levels</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTankModal">
                <i class="bi bi-plus-circle me-2"></i>Add New Tank
            </button>
        </div>
    </div>

    <?php if (!empty($fuel_inventory)): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTable">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash me-1"></i>Tank #</th>
                        <th><i class="bi bi-tag me-1"></i>Fuel Type</th>
                        <th><i class="bi bi-rulers me-1"></i>Capacity</th>
                        <th><i class="bi bi-droplet me-1"></i>Current Level</th>
                        <th><i class="bi bi-speedometer2 me-1"></i>Fill %</th>
                        <th><i class="bi bi-geo-alt me-1"></i>Location</th>
                        <th><i class="bi bi-info-circle me-1"></i>Status</th>
                        <th><i class="bi bi-clock me-1"></i>Last Updated</th>
                        <th><i class="bi bi-gear me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fuel_inventory as $tank): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tank['tank_number']); ?></strong></td>
                            <td>
                                <span class="badge bg-info text-white">
                                    <?php echo htmlspecialchars(ucfirst($tank['fuel_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($tank['capacity'], 2); ?> L</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="me-2"><?php echo number_format($tank['current_level'], 2); ?> L</span>
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo min(100, ($tank['current_level'] / $tank['capacity']) * 100); ?>%"
                                             aria-valuenow="<?php echo $tank['current_level']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?php echo $tank['capacity']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $percentage = ($tank['current_level'] / $tank['capacity']) * 100;
                                $badge_class = 'success';
                                if ($percentage < 20) $badge_class = 'danger';
                                elseif ($percentage < 50) $badge_class = 'warning';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?> text-white">
                                    <?php echo number_format($percentage, 1); ?>%
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($tank['location']); ?></td>
                            <td>
                                <?php
                                $status_class = 'success';
                                if ($tank['status'] === 'inactive') $status_class = 'danger';
                                elseif ($tank['status'] === 'maintenance') $status_class = 'warning';
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?> text-white">
                                    <?php echo htmlspecialchars(ucfirst($tank['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($tank['last_updated'])); ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-primary" onclick="editTank(<?php echo $tank['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-info" onclick="viewTankHistory(<?php echo $tank['id']; ?>)">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-fuel-pump text-muted" style="font-size: 4rem;"></i>
            <h5 class="text-muted mt-3">No Fuel Tanks Found</h5>
            <p class="text-muted">No fuel tanks have been set up in the system yet.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTankModal">
                <i class="bi bi-plus-circle me-2"></i>Add Your First Tank
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Add Tank Modal -->
<div class="modal fade" id="addTankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Fuel Tank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_tank">
                    
                    <div class="mb-3">
                        <label for="tank_number" class="form-label">Tank Number</label>
                        <input type="text" class="form-control" id="tank_number" name="tank_number" required>
                        <div class="form-text">Unique identifier for the tank (e.g., TANK-001)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fuel_type" class="form-label">Fuel Type</label>
                        <select class="form-select" id="fuel_type" name="fuel_type" required>
                            <option value="">Select fuel type</option>
                            <option value="diesel">Diesel</option>
                            <option value="gasoline">Gasoline</option>
                            <option value="premium">Premium Gasoline</option>
                            <option value="kerosene">Kerosene</option>
                            <option value="lpg">LPG</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity (Liters)</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" step="0.01" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                        <div class="form-text">Physical location of the tank</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Tank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tank Modal -->
<div class="modal fade" id="editTankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Tank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_tank">
                    <input type="hidden" id="edit_tank_id" name="tank_id">
                    
                    <div class="mb-3">
                        <label for="edit_current_level" class="form-label">Current Level (Liters)</label>
                        <input type="number" class="form-control" id="edit_current_level" name="current_level" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Tank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTank(tankId) {
    // Fetch tank data and populate edit modal
    fetch('fuel_tabs/get_tank_data.php?id=' + tankId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_tank_id').value = data.id;
            document.getElementById('edit_current_level').value = data.current_level;
            document.getElementById('edit_status').value = data.status;
            
            const modal = new bootstrap.Modal(document.getElementById('editTankModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching tank data:', error);
            alert('Error loading tank data. Please try again.');
        });
}

function viewTankHistory(tankId) {
    // Open tank history in new window or modal
    window.open('fuel_tabs/tank_history.php?id=' + tankId, '_blank');
}

// Initialize DataTable
$(document).ready(function() {
    $('#inventoryTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: -1, orderable: false }
        ]
    });
});
</script>
