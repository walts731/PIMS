<?php
require_once '../config.php';

// Get current fuel stock from fuel_stock table joined with fuel_types
$stock_query = "SELECT fs.*, ft.name as fuel_type_name 
               FROM fuel_stock fs 
               LEFT JOIN fuel_types ft ON fs.fuel_type_id = ft.id 
               ORDER BY ft.name";
$stock_result = $conn->query($stock_query);

// Get fuel inventory summary by fuel type for display
$inventory_query = "SELECT ft.id, ft.name as fuel_type_name,
       COALESCE(SUM(fs.quantity), 0) as total_current,
       COUNT(DISTINCT fi.id) as transaction_count
       FROM fuel_types ft 
       LEFT JOIN fuel_stock fs ON ft.id = fs.fuel_type_id 
       LEFT JOIN fuel_in fi ON ft.id = fi.fuel_type 
       WHERE ft.is_active = 1 
       GROUP BY ft.id, ft.name
       ORDER BY ft.name";
$inventory_result = $conn->query($inventory_query);

// Get fuel types for dropdown
$fuel_types_query = "SELECT id, name FROM fuel_types WHERE is_active = 1 ORDER BY name";
$fuel_types_result = $conn->query($fuel_types_query);
?>

<!-- Fuel Statistics Cards -->
<div class="row mb-4">
    <?php 
    $total_fuel = 0;
    $total_transactions = 0;
    while ($stats = $inventory_result->fetch_assoc()): 
        $total_fuel += $stats['total_current'];
        $total_transactions += $stats['transaction_count'];
    ?>
        <div class="col-md-4 mb-2">
            <div class="fuel-stats-card compact <?php echo strtolower(htmlspecialchars($stats['fuel_type_name'])); ?>">
                <div class="stats-content">
                    <div class="stats-info">
                        <div class="stats-value"><?php echo number_format($stats['total_current'], 2); ?> L</div>
                        <div class="stats-label"><?php echo ucfirst(htmlspecialchars($stats['fuel_type_name'])); ?> Stock</div>
                    </div>
                    <div class="stats-detail"><?php echo $stats['transaction_count']; ?> Transactions</div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    
    <?php $inventory_result->data_seek(0); ?>
    <div class="col-md-4 mb-2">
        <div class="fuel-stats-card compact total">
            <div class="stats-content">
                <div class="stats-info">
                    <div class="stats-value"><?php echo number_format($total_fuel, 2); ?> L</div>
                    <div class="stats-label">Total Fuel Stock</div>
                </div>
                <div class="stats-detail">All Fuel Types</div>
            </div>
        </div>
    </div>
</div>

<!-- Page Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Fuel Inventory</h4>
    <div>
        <button class="btn btn-outline-secondary btn-sm" onclick="refreshInventory()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- Fuel Stock Summary Table -->
<div class="table-responsive">
    <table class="table table-hover" id="inventoryTable">
        <thead>
            <tr>
                <th>Fuel Type</th>
                <th>Current Stock (L)</th>
                <th>Last Updated</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($inventory_result->num_rows > 0): ?>
                <?php while ($fuel = $inventory_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="fuel-type-badge fuel-type-<?php echo strtolower(htmlspecialchars($fuel['fuel_type_name'])); ?>">
                                <?php echo ucfirst(htmlspecialchars($fuel['fuel_type_name'])); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo number_format($fuel['total_current'], 2); ?></strong> L
                        </td>
                        <td>
                            <?php 
                            // Get last update time for this fuel type
                            $last_update_query = "SELECT MAX(updated_at) as last_update FROM fuel_stock WHERE fuel_type_id = " . intval($fuel['id']);
                            $last_update_result = $conn->query($last_update_query);
                            $last_update = $last_update_result->fetch_assoc()['last_update'];
                            echo $last_update ? date('M j, Y g:i A', strtotime($last_update)) : 'Never';
                            ?>
                        </td>
                        <td>
                            <?php
                            $stock_level = $fuel['total_current'];
                            if ($stock_level > 1000) {
                                echo '<span class="status-badge status-full">High Stock</span>';
                            } elseif ($stock_level > 500) {
                                echo '<span class="status-badge status-normal">Normal</span>';
                            } else {
                                echo '<span class="status-badge status-low">Low Stock</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-fuel-pump" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0">No fuel inventory found</p>
                            <small>Add fuel transactions to populate inventory</small>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Fuel Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStockForm">
                    <div class="mb-3">
                        <label class="form-label">Fuel Type</label>
                        <select name="fuel_type_id" class="form-select" required>
                            <option value="">Select Fuel Type</option>
                            <?php while ($fuel_type = $fuel_types_result->fetch_assoc()): ?>
                                <option value="<?php echo $fuel_type['id']; ?>">
                                    <?php echo htmlspecialchars($fuel_type['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity (Liters)</label>
                        <input type="number" name="quantity" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addStockForm" class="btn btn-primary">Add Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
function refreshInventory() {
    location.reload();
}

function showAddStockModal() {
    const modal = new bootstrap.Modal(document.getElementById('addStockModal'));
    modal.show();
}

function editStock(fuelTypeId) {
    // Implementation for editing stock
    alert('Edit stock functionality for fuel type ID: ' + fuelTypeId);
}

function viewStockHistory(fuelTypeId) {
    // Switch to reports tab and filter by fuel type
    const reportsTab = document.getElementById('reports-tab');
    reportsTab.click();
    
    // Set filter after tab loads
    setTimeout(() => {
        if (typeof filterByFuelType === 'function') {
            filterByFuelType(fuelTypeId);
        }
    }, 100);
}

// Handle add stock form
document.getElementById('addStockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('fuel_tabs/process_fuel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addStockModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding stock');
    });
});

// Initialize DataTable
$(document).ready(function() {
    $('#inventoryTable').DataTable({
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            search: "Search fuel types:",
            lengthMenu: "Show _MENU_ fuel types per page"
        }
    });
});
</script>
