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
        $query = "SELECT c.*, o.office_name, 
                        COALESCE(crh.release_date, c.created_at) as release_date
                 FROM consumables c 
                 LEFT JOIN offices o ON c.office_id = o.id 
                 LEFT JOIN consumable_release_history crh ON c.id = crh.consumable_id 
                 WHERE c.office_id = ? 
                 ORDER BY COALESCE(crh.release_date, c.created_at) DESC";
        
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
        <div class="row mb-4 justify-content-center">
            <div class="col-md-4 mb-3">
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
            <div class="col-md-4 mb-3">
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
            <div class="col-md-4 mb-3">
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
                                            <button type="button" class="btn btn-sm btn-outline-primary action-btn" onclick="viewConsumable(<?php echo $consumable['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning action-btn" onclick="consumeConsumable(<?php echo $consumable['id']; ?>)">
                                                <i class="bi bi-dash-circle"></i>
                                            </button>
                                            <!-- <button type="button" class="btn btn-sm btn-outline-success action-btn" onclick="restockConsumable(<?php echo $consumable['id']; ?>)">
                                                <i class="bi bi-plus-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger action-btn" onclick="deleteConsumable(<?php echo $consumable['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button> -->
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

    <!-- View Consumable Modal -->
    <div class="modal fade" id="viewConsumableModal" tabindex="-1" aria-labelledby="viewConsumableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewConsumableModalLabel">
                        <i class="bi bi-eye"></i> Consumable Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="bi bi-box-seam"></i> Description</h6>
                                <p id="viewDescription" class="info-value">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="bi bi-tag"></i> Unit</h6>
                                <p id="viewUnit" class="info-value">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="bi bi-stack"></i> Quantity</h6>
                                <p id="viewQuantity" class="info-value">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="bi bi-building"></i> Office</h6>
                                <p id="viewOffice" class="info-value">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="bi bi-calendar-plus"></i> Released to Office</h6>
                                <p id="viewCreatedAt" class="info-value">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="bi bi-arrow-clockwise"></i> Last Updated</h6>
                                <p id="viewUpdatedAt" class="info-value">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Consume Consumable Modal -->
    <div class="modal fade" id="consumeModal" tabindex="-1" aria-labelledby="consumeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="consumeModalLabel">
                        <i class="bi bi-dash-circle"></i> Consume Consumable
                    </h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="consumeForm">
                        <input type="hidden" id="consumeConsumableId" name="consumable_id">
                        <div class="mb-3">
                            <label for="consumeDescription" class="form-label">Description</label>
                            <input type="text" class="form-control" id="consumeDescription" readonly>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="consumeCurrentQuantity" class="form-label">Current Quantity</label>
                                <input type="number" class="form-control" id="consumeCurrentQuantity" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="consumeQuantity" class="form-label">Quantity to Consume</label>
                                <input type="number" class="form-control" id="consumeQuantity" name="quantity" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="consumeNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="consumeNotes" name="notes" rows="3" placeholder="Reason for consumption..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="confirmConsume()">Consume</button>
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
    
    // View consumable
    function viewConsumable(id) {
        // Find consumable data
        <?php foreach ($consumables as $consumable): ?>
        if (id === <?php echo $consumable['id']; ?>) {
            document.getElementById('viewDescription').textContent = '<?php echo addslashes($consumable['description']); ?>';
            document.getElementById('viewUnit').textContent = '<?php echo addslashes($consumable['units'] ?? $consumable['unit'] ?? ''); ?>';
            document.getElementById('viewQuantity').textContent = <?php echo $consumable['quantity']; ?>;
            document.getElementById('viewOffice').textContent = '<?php echo addslashes($consumable['office_name']); ?>';
            document.getElementById('viewCreatedAt').textContent = '<?php echo date('M j, Y g:i A', strtotime($consumable['release_date'])); ?>';
            document.getElementById('viewUpdatedAt').textContent = '<?php echo date('M j, Y g:i A', strtotime($consumable['updated_at'])); ?>';
            
            // Get or create modal instance
            let modalElement = document.getElementById('viewConsumableModal');
            let modalInstance = bootstrap.Modal.getInstance(modalElement);
            
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalElement);
            }
            
            modalInstance.show();
        }
        <?php endforeach; ?>
    }
    
    // Consume consumable
    function consumeConsumable(id) {
        // Find consumable data
        <?php foreach ($consumables as $consumable): ?>
        if (id === <?php echo $consumable['id']; ?>) {
            document.getElementById('consumeConsumableId').value = <?php echo $consumable['id']; ?>;
            document.getElementById('consumeDescription').value = '<?php echo addslashes($consumable['description']); ?>';
            document.getElementById('consumeCurrentQuantity').value = <?php echo $consumable['quantity']; ?>;
            document.getElementById('consumeQuantity').value = '';
            document.getElementById('consumeNotes').value = '';
            
            // Get or create modal instance
            let modalElement = document.getElementById('consumeModal');
            let modalInstance = bootstrap.Modal.getInstance(modalElement);
            
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalElement);
            }
            
            modalInstance.show();
        }
        <?php endforeach; ?>
    }
    
    // Confirm consume
    function confirmConsume() {
        console.log('DEBUG: confirmConsume() called');
        
        const consumableId = document.getElementById('consumeConsumableId').value;
        const quantity = document.getElementById('consumeQuantity').value;
        const notes = document.getElementById('consumeNotes').value;
        
        console.log('DEBUG: Form data - ID:', consumableId, 'Quantity:', quantity, 'Notes:', notes);
        
        if (!quantity || quantity <= 0) {
            console.log('DEBUG: Quantity validation failed');
            alert('Please enter a valid quantity to consume.');
            return;
        }
        
        if (!confirm('Are you sure you want to consume this consumable? This will reduce the available quantity.')) {
            console.log('DEBUG: User cancelled consumption');
            return;
        }
        
        console.log('DEBUG: Sending API request...');
        
        fetch('api/consume_consumable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `consumable_id=${consumableId}&quantity=${quantity}&notes=${encodeURIComponent(notes)}`
        })
        .then(response => {
            console.log('DEBUG: Raw response:', response);
            return response.json();
        })
        .then(data => {
            console.log('DEBUG: Parsed response:', data);
            if (data.success) {
                console.log('DEBUG: Consumption successful, hiding modal');
                bootstrap.Modal.getInstance(document.getElementById('consumeModal')).hide();
                location.reload();
            } else {
                console.log('DEBUG: Consumption failed:', data.message);
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('DEBUG: Fetch error:', error);
            alert('An error occurred while consuming the consumable.');
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
