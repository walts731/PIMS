<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Set page title for topbar
$page_title = 'Office Consumables';

// Get office-specific consumables
$consumables = [];
$stats = [
    'total_consumables' => 0,
    'total_value' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0
];

// Use office_id directly from session
$office_id = $_SESSION['office_id'] ?? null;

if ($office_id && $conn) {
    try {
        // Debug: Check session and office_id values
        error_log("DEBUG: Session office_id = " . ($office_id ?? 'NULL'));
        error_log("DEBUG: Session office = " . ($_SESSION['office'] ?? 'NOT SET'));
        error_log("DEBUG: Session email = " . ($_SESSION['email'] ?? 'NOT SET'));
        
        // Fetch consumables for this office
        $query = "SELECT c.*, o.office_name 
                 FROM consumables c 
                 LEFT JOIN offices o ON c.office_id = o.id 
                 WHERE c.office_id = ? 
                 ORDER BY c.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("DEBUG: Query executed, rows found: " . $result->num_rows);
        
        while ($row = $result->fetch_assoc()) {
            $consumables[] = $row;
            
            // Calculate statistics
            $stats['total_consumables']++;
            $total_value = $row['quantity'] * $row['unit_cost'];
            $stats['total_value'] += $total_value;
            
            if ($row['quantity'] <= $row['reorder_level']) {
                $stats['low_stock']++;
            }
            
            if ($row['quantity'] == 0) {
                $stats['out_of_stock']++;
            }
        }
        
    } catch (Exception $e) {
        error_log("Error fetching consumables: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Consumables - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .consumable-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.1);
            margin-bottom: 1rem;
        }
        
        .consumable-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stock-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stock-normal {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .stock-low {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }
        
        .stock-out {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .search-box {
            background: white;
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .form-control {
            background: var(--light-color);
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        /* Custom scrollbar for webkit browsers */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: rgba(25, 27, 169, 0.1);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
    </style>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'Office Consumables';
?>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-archive"></i> Office Consumables
                    </h1>
                    <p class="text-muted mb-0">Manage your office consumables and inventory</p>
                    <?php if (isset($stats['error'])): ?>
                        <div class="alert alert-warning mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Database Warning:</strong> <?php echo htmlspecialchars($stats['error']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportData()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Total Consumables</h6>
                            <div class="stats-number"><?php echo $stats['total_consumables']; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-box-seam text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Total Value</h6>
                            <div class="stats-number">₱<?php echo number_format($stats['total_value'], 2); ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-currency-dollar text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Low Stock</h6>
                            <div class="stats-number text-warning"><?php echo $stats['low_stock']; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Out of Stock</h6>
                            <div class="stats-number text-danger"><?php echo $stats['out_of_stock']; ?></div>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Consumables Table -->
        <div class="card">
            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul"></i> Consumables Inventory
                        </h5>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addConsumableModal">
                            <i class="bi bi-plus-circle"></i> Add Consumable
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="consumablesTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($consumables)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">No consumables found in your office.</p>
                                    <small>Click "Add Consumable" to add your first item.</small>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($consumables as $consumable): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-box-seam text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($consumable['description']); ?></div>
                                                <small class="text-muted">ID: #<?php echo $consumable['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white"><?php echo $consumable['quantity'] . ' ' . htmlspecialchars($consumable['units']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $stock_status = 'normal';
                                        $status_class = 'stock-normal';
                                        $status_text = 'Normal';
                                        
                                        if ($consumable['quantity'] == 0) {
                                            $stock_status = 'out';
                                            $status_class = 'stock-out';
                                            $status_text = 'Out of Stock';
                                        } elseif ($consumable['quantity'] <= $consumable['reorder_level']) {
                                            $stock_status = 'low';
                                            $status_class = 'stock-low';
                                            $status_text = 'Low Stock';
                                        }
                                        ?>
                                        <span class="stock-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary action-btn" onclick="editConsumable(<?php echo $consumable['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success action-btn" onclick="restockConsumable(<?php echo $consumable['id']; ?>)">
                                                <i class="bi bi-plus-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger action-btn" onclick="deleteConsumable(<?php echo $consumable['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Add Consumable Modal -->
    <div class="modal fade" id="addConsumableModal" tabindex="-1" aria-labelledby="addConsumableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addConsumableModalLabel">
                        <i class="bi bi-plus-circle"></i> Add New Consumable
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addConsumableForm">
                        <div class="mb-3">
                            <label for="consumableDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="consumableDescription" name="description" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="consumableQuantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="consumableQuantity" name="quantity" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="consumableUnitCost" class="form-label">Unit Cost</label>
                                <input type="number" class="form-control" id="consumableUnitCost" name="unit_cost" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="consumableReorderLevel" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="consumableReorderLevel" name="reorder_level" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveConsumable()">Save Consumable</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Consumable Modal -->
    <div class="modal fade" id="editConsumableModal" tabindex="-1" aria-labelledby="editConsumableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editConsumableModalLabel">
                        <i class="bi bi-pencil"></i> Edit Consumable
                    </h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editConsumableForm">
                        <input type="hidden" id="editConsumableId" name="id">
                        <div class="mb-3">
                            <label for="editConsumableDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="editConsumableDescription" name="description" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editConsumableQuantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="editConsumableQuantity" name="quantity" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editConsumableUnitCost" class="form-label">Unit Cost</label>
                                <input type="number" class="form-control" id="editConsumableUnitCost" name="unit_cost" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editConsumableReorderLevel" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="editConsumableReorderLevel" name="reorder_level" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="updateConsumable()">Update Consumable</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restock Modal -->
    <div class="modal fade" id="restockModal" tabindex="-1" aria-labelledby="restockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="restockModalLabel">
                        <i class="bi bi-plus-circle"></i> Restock Consumable
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="restockForm">
                        <input type="hidden" id="restockConsumableId" name="id">
                        <div class="mb-3">
                            <label for="restockDescription" class="form-label">Item</label>
                            <input type="text" class="form-control" id="restockDescription" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="restockCurrentQuantity" class="form-label">Current Quantity</label>
                            <input type="number" class="form-control" id="restockCurrentQuantity" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="restockAmount" class="form-label">Add Quantity</label>
                            <input type="number" class="form-control" id="restockAmount" name="add_quantity" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="processRestock()">Add Stock</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#consumablesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                emptyTable: "No consumables data available in your office",
                zeroRecords: "No consumables found matching your search"
            }
        });
    });
    
    // Refresh dashboard
    function refreshDashboard() {
        location.reload();
    }
    
    // Export data
    function exportData() {
        // Simple CSV export
        let csv = 'Description,Quantity,Status\n';
        
        <?php foreach ($consumables as $consumable): ?>
        csv += '<?php echo addslashes($consumable['description']); ?>,<?php echo $consumable['quantity']; ?>,<?php 
            $status = 'Normal';
            if ($consumable['quantity'] == 0) $status = 'Out of Stock';
            elseif ($consumable['quantity'] <= $consumable['reorder_level']) $status = 'Low Stock';
            echo addslashes($status);
        ?>\n';
        <?php endforeach; ?>
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'consumables_<?php echo date('Y-m-d'); ?>.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // Add consumable
    function saveConsumable() {
        const form = document.getElementById('addConsumableForm');
        const formData = new FormData(form);
        
        // Add office_id
        formData.append('office_id', <?php echo $office_id; ?>);
        
        fetch('api/add_consumable.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addConsumableModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the consumable.');
        });
    }
    
    // Edit consumable
    function editConsumable(id) {
        // Find consumable data
        <?php foreach ($consumables as $consumable): ?>
        if (id === <?php echo $consumable['id']; ?>) {
            document.getElementById('editConsumableId').value = <?php echo $consumable['id']; ?>;
            document.getElementById('editConsumableDescription').value = '<?php echo addslashes($consumable['description']); ?>';
            document.getElementById('editConsumableQuantity').value = <?php echo $consumable['quantity']; ?>;
            document.getElementById('editConsumableUnitCost').value = <?php echo $consumable['unit_cost']; ?>;
            document.getElementById('editConsumableReorderLevel').value = <?php echo $consumable['reorder_level']; ?>;
            
            new bootstrap.Modal(document.getElementById('editConsumableModal')).show();
        }
        <?php endforeach; ?>
    }
    
    // Update consumable
    function updateConsumable() {
        const form = document.getElementById('editConsumableForm');
        const formData = new FormData(form);
        
        fetch('api/update_consumable.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editConsumableModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the consumable.');
        });
    }
    
    // Restock consumable
    function restockConsumable(id) {
        // Find consumable data
        <?php foreach ($consumables as $consumable): ?>
        if (id === <?php echo $consumable['id']; ?>) {
            document.getElementById('restockConsumableId').value = <?php echo $consumable['id']; ?>;
            document.getElementById('restockDescription').value = '<?php echo addslashes($consumable['description']); ?>';
            document.getElementById('restockCurrentQuantity').value = <?php echo $consumable['quantity']; ?>;
            
            new bootstrap.Modal(document.getElementById('restockModal')).show();
        }
        <?php endforeach; ?>
    }
    
    // Process restock
    function processRestock() {
        const form = document.getElementById('restockForm');
        const formData = new FormData(form);
        
        fetch('api/restock_consumable.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('restockModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while restocking the consumable.');
        });
    }
    
    // Delete consumable
    function deleteConsumable(id) {
        if (confirm('Are you sure you want to delete this consumable? This action cannot be undone.')) {
            fetch('api/delete_consumable.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
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
                alert('An error occurred while deleting the consumable.');
            });
        }
    }
    </script>
    
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
