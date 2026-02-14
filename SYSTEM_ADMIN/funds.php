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
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log funds page access
logSystemAction($_SESSION['user_id'], 'access', 'funds', 'System admin accessed funds page');

// Handle CRUD operations
$message = '';
$message_type = '';

// CREATE - Add new fund
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $fund_code = trim($_POST['fund_code'] ?? '');
    $fund_name = trim($_POST['fund_name'] ?? '');
    $fund_cluster = trim($_POST['fund_cluster'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($fund_code) || empty($fund_name) || empty($fund_cluster)) {
        $message = "Fund code, fund name, and fund cluster are required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO funds (fund_code, fund_name, fund_cluster, description, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $fund_code, $fund_name, $fund_cluster, $description, $status, $_SESSION['user_id']);
            $stmt->execute();
            
            $message = "Fund added successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'fund_added', 'fund_management', "Added fund: {$fund_name} ({$fund_code})");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Fund code already exists.";
            } else {
                $message = "Error adding fund: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// UPDATE - Edit fund
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $fund_code = trim($_POST['fund_code'] ?? '');
    $fund_name = trim($_POST['fund_name'] ?? '');
    $fund_cluster = trim($_POST['fund_cluster'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($fund_code) || empty($fund_name) || empty($fund_cluster)) {
        $message = "Fund code, fund name, and fund cluster are required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE funds SET fund_code = ?, fund_name = ?, fund_cluster = ?, description = ?, status = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $fund_code, $fund_name, $fund_cluster, $description, $status, $_SESSION['user_id'], $id);
            $stmt->execute();
            
            $message = "Fund updated successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'fund_updated', 'fund_management', "Updated fund: {$fund_name} ({$fund_code})");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Fund code already exists.";
            } else {
                $message = "Error updating fund: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// DELETE - Delete fund
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Check if fund has transactions
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM fund_transactions WHERE fund_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            $message = "Cannot delete fund with existing transactions.";
            $message_type = "danger";
        } else {
            // Get fund info for logging
            $info_stmt = $conn->prepare("SELECT fund_code, fund_name FROM funds WHERE id = ?");
            $info_stmt->bind_param("i", $id);
            $info_stmt->execute();
            $fund_info = $info_stmt->get_result()->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM funds WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Fund deleted successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'fund_deleted', 'fund_management', "Deleted fund: {$fund_info['fund_name']} ({$fund_info['fund_code']})");
        }
        
    } catch (Exception $e) {
        $message = "Error deleting fund: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get all funds
$funds = [];
try {
    $stmt = $conn->prepare("SELECT f.*, u1.username as created_by_name, u2.username as updated_by_name FROM funds f LEFT JOIN users u1 ON f.created_by = u1.id LEFT JOIN users u2 ON f.updated_by = u2.id ORDER BY f.fund_code");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $funds[] = $row;
    }
} catch (Exception $e) {
    $message = "Error fetching funds: " . $e->getMessage();
    $message_type = "danger";
}

// Get fund for editing
$edit_fund = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM funds WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_fund = $result->fetch_assoc();
    } catch (Exception $e) {
        $message = "Error fetching fund: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Get system settings for theme
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback to default
    $system_settings['system_name'] = 'PIMS';
}

// Set page title for topbar
$page_title = 'Funds';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funds - <?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?></title>
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
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
        }
        
        /* Sidebar Toggle Styles */
        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1051;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar-toggle:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .sidebar-toggle.sidebar-active {
            left: 300px;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
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
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .main-wrapper.sidebar-active {
            margin-left: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle.sidebar-active {
                left: 20px;
            }
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
        }
        
        .amount-cell {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
    </style>
</head>
<body>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-cash-coin"></i> Funds Management
                    </h1>
                    <p class="text-muted mb-0">Manage government funds and budget allocations</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-info btn-sm" onclick="exportFunds()">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFundModal">
                            <i class="bi bi-plus-circle"></i> Add Fund
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Funds Statistics -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count($funds); ?></div>
                            <div class="text-muted">Total Funds</div>
                            <small class="text-success">
                                <i class="bi bi-cash-coin"></i> 
                                Budget Items
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-cash-coin fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_filter($funds, fn($f) => $f['status'] == 'active')); ?></div>
                            <div class="text-muted">Active Funds</div>
                            <small class="text-success">Ready for Use</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo count(array_filter($funds, fn($f) => $f['status'] == 'inactive')); ?></div>
                            <div class="text-muted">Inactive Funds</div>
                            <small class="text-warning">Disabled</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-pause-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Funds Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-cash-coin"></i> Funds Management</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="fundsTable">
                                <thead>
                                    <tr>
                                        <th>Fund Code</th>
                                        <th>Fund Name</th>
                                        <th>Fund Cluster</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($funds as $fund): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fund['fund_code']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($fund['fund_name']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($fund['fund_cluster']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($fund['description'] ?? '', 0, 100)) . (strlen($fund['description'] ?? '') > 100 ? '...' : ''); ?></td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input status-switch" type="checkbox" 
                                                           id="status_<?php echo $fund['id']; ?>" 
                                                           data-fund-id="<?php echo $fund['id']; ?>"
                                                           <?php echo ($fund['status'] == 'active') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="status_<?php echo $fund['id']; ?>">
                                                        <span class="badge bg-<?php echo $fund['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo ucfirst($fund['status']); ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($fund['created_at'])); ?>
                                                    <br>by <?php echo htmlspecialchars($fund['created_by_name'] ?? 'System'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editFund(<?php echo $fund['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteFund(<?php echo $fund['id']; ?>, '<?php echo htmlspecialchars($fund['fund_name']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Fund Modal -->
    <div class="modal fade" id="addFundModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Fund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="funds.php">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fund_code" class="form-label">Fund Code *</label>
                                <input type="text" class="form-control" id="fund_code" name="fund_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fund_name" class="form-label">Fund Name *</label>
                                <input type="text" class="form-control" id="fund_name" name="fund_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fund_cluster" class="form-label">Fund Cluster *</label>
                                <input type="text" class="form-control" id="fund_cluster" name="fund_cluster" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Fund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Fund Modal -->
    <div class="modal fade" id="editFundModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Fund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="funds.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_fund_code" class="form-label">Fund Code *</label>
                                <input type="text" class="form-control" id="edit_fund_code" name="fund_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_fund_name" class="form-label">Fund Name *</label>
                                <input type="text" class="form-control" id="edit_fund_name" name="fund_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_fund_cluster" class="form-label">Fund Cluster *</label>
                                <input type="text" class="form-control" id="edit_fund_cluster" name="fund_cluster" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Fund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete fund "<span id="deleteFundName"></span>"?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="deleteConfirmBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
</div> <!-- Close main wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
<?php require_once 'includes/sidebar-scripts.php'; ?>

// Fix modal backdrop issues
document.addEventListener('DOMContentLoaded', function() {
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
        logoutModal.addEventListener('show.bs.modal', function () {
            // Ensure proper backdrop
            document.body.classList.add('modal-open');
        });
        
        logoutModal.addEventListener('hidden.bs.modal', function () {
            // Clean up backdrop
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        });
        
        // Ensure cancel button works properly
        const cancelButton = logoutModal.querySelector('[data-bs-dismiss="modal"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = bootstrap.Modal.getInstance(logoutModal);
                if (modal) {
                    modal.hide();
                }
            });
        }
    }
});

    // Initialize DataTables
    $(document).ready(function() {
        $('#fundsTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'asc']],
            language: {
                search: "Search funds:",
                lengthMenu: "Show _MENU_ funds",
                info: "Showing _START_ to _END_ of _TOTAL_ funds",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });

    function editFund(id) {
        console.log('Fetching fund data for ID:', id);
        
        fetch(`ajax/get_fund.php?action=edit&id=${id}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check content type to ensure it's JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Expected JSON but got:', text.substring(0, 200));
                        throw new Error('Server returned non-JSON response. Check console for details.');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                
                if (data.success) {
                    document.getElementById('edit_id').value = data.fund.id;
                    document.getElementById('edit_fund_code').value = data.fund.fund_code;
                    document.getElementById('edit_fund_name').value = data.fund.fund_name;
                    document.getElementById('edit_fund_cluster').value = data.fund.fund_cluster;
                    document.getElementById('edit_status').value = data.fund.status;
                    document.getElementById('edit_description').value = data.fund.description || '';
                    
                    new bootstrap.Modal(document.getElementById('editFundModal')).show();
                } else {
                    console.error('Server error:', data);
                    alert('Error fetching fund: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                console.error('Error details:', error.message);
                alert('Error fetching fund data: ' + error.message);
            });
    }
    
    function deleteFund(id, name) {
        document.getElementById('deleteFundName').textContent = name;
        document.getElementById('deleteConfirmBtn').href = `funds.php?action=delete&id=${id}`;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    
    // Handle status switch changes
    document.querySelectorAll('.status-switch').forEach(switchElement => {
        switchElement.addEventListener('change', function() {
            const fundId = this.dataset.fundId;
            const newStatus = this.checked ? 'active' : 'inactive';
            
            // Show loading state
            const badge = this.nextElementSibling.querySelector('span');
            const originalText = badge.textContent;
            badge.textContent = 'Updating...';
            badge.className = 'badge bg-warning';
            
            // Send AJAX request to update status
            fetch('ajax/update_fund_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `fund_id=${fundId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update badge
                    badge.textContent = newStatus === 'active' ? 'Active' : 'Inactive';
                    badge.className = `badge bg-${newStatus === 'active' ? 'success' : 'secondary'}`;
                    
                    // Show success message
                    showAlert(data.message, 'success');
                } else {
                    // Revert switch and show error
                    this.checked = !this.checked;
                    badge.textContent = originalText;
                    badge.className = `badge bg-${this.checked ? 'success' : 'secondary'}`;
                    showAlert(data.message || 'Error updating status', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert switch and show error
                this.checked = !this.checked;
                badge.textContent = originalText;
                badge.className = `badge bg-${this.checked ? 'success' : 'secondary'}`;
                showAlert('Error updating status', 'danger');
            });
        });
    });
    
    function showAlert(message, type) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert after page header
        const pageHeader = document.querySelector('.page-header');
        pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
    
    // Export funds
    function exportFunds() {
        let csv = 'Fund Code,Fund Name,Fund Cluster,Description,Status\n';
        
        const rows = document.querySelectorAll('#fundsTable tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const rowData = [
                cells[0].textContent.trim(),
                cells[1].textContent.trim(),
                cells[2].textContent.trim(),
                cells[3].textContent.trim(),
                cells[4].textContent.trim()
            ];
            csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'funds_export_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
</script>
</body>
</html>
