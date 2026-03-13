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

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log lend consumables page access
logSystemAction($_SESSION['user_id'], 'access', 'lend_consumables', 'Admin accessed lend consumables page');

// Handle CRUD operations
$message = '';
$message_type = '';

// CREATE - Add new lend transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'lend') {
    $consumable_id = intval($_POST['consumable_id'] ?? 0);
    $quantity_lent = intval($_POST['quantity_lent'] ?? 0);
    $to_office_id = intval($_POST['to_office_id'] ?? 0);
    $lent_by = $_SESSION['user_id'];
    $received_by = trim($_POST['received_by'] ?? '');
    $date_lent = $_POST['date_lent'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if ($consumable_id <= 0) {
        $message = "Please select a consumable.";
        $message_type = "danger";
    } elseif ($quantity_lent <= 0) {
        $message = "Quantity borrowed must be greater than 0.";
        $message_type = "danger";
    } elseif ($to_office_id <= 0) {
        $message = "Please select a destination office.";
        $message_type = "danger";
    } elseif (empty($received_by)) {
        $message = "Received by is required.";
        $message_type = "danger";
    } else {
        try {
            // Check consumable availability
            $consumable_stmt = $conn->prepare("SELECT description, quantity, unit_cost, office_id FROM consumables WHERE id = ? AND quantity >= ?");
            $consumable_stmt->bind_param("ii", $consumable_id, $quantity_lent);
            $consumable_stmt->execute();
            $consumable_result = $consumable_stmt->get_result();
            
            if ($consumable_result->num_rows > 0) {
                $consumable = $consumable_result->fetch_assoc();
                $total_value = $quantity_lent * $consumable['unit_cost'];
                
                // Insert lend transaction
                $insert_stmt = $conn->prepare("INSERT INTO lend_consumables (consumable_id, description, quantity_lent, unit_cost, total_value, from_office_id, to_office_id, lent_by, received_by, date_lent, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("isidiiissss", $consumable_id, $consumable['description'], $quantity_lent, $consumable['unit_cost'], $total_value, $consumable['office_id'], $to_office_id, $lent_by, $received_by, $date_lent, $notes);
                
                if ($insert_stmt->execute()) {
                    // Update consumable quantity
                    $new_quantity = $consumable['quantity'] - $quantity_lent;
                    $update_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $new_quantity, $consumable_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $message = "Consumable lent successfully!";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'consumable_lent', 'lend_consumables', "Lent {$quantity_lent} units of {$consumable['description']} to office ID {$to_office_id}");
                } else {
                    throw new Exception("Failed to record lend transaction: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            } else {
                $message = "Insufficient consumable quantity or consumable not found.";
                $message_type = "danger";
            }
            $consumable_stmt->close();
        } catch (Exception $e) {
            $message = "Error lending consumable: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Handle return transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'return') {
    $lend_id = intval($_POST['lend_id'] ?? 0);
    $actual_return_date = $_POST['actual_return_date'] ?? date('Y-m-d');
    
    if ($lend_id <= 0) {
        $message = "Invalid lend transaction ID.";
        $message_type = "danger";
    } else {
        try {
            // Get lend transaction details
            $lend_stmt = $conn->prepare("SELECT consumable_id, quantity_lent, status FROM lend_consumables WHERE id = ?");
            $lend_stmt->bind_param("i", $lend_id);
            $lend_stmt->execute();
            $lend_result = $lend_stmt->get_result();
            
            if ($lend_result->num_rows > 0) {
                $lend = $lend_result->fetch_assoc();
                
                if ($lend['status'] == 'pending') {
                    // Update lend transaction
                    $update_stmt = $conn->prepare("UPDATE lend_consumables SET status = 'returned', actual_return_date = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("si", $actual_return_date, $lend_id);
                    
                    if ($update_stmt->execute()) {
                        // Update consumable quantity
                        $consumable_stmt = $conn->prepare("UPDATE consumables SET quantity = quantity + ? WHERE id = ?");
                        $consumable_stmt->bind_param("ii", $lend['quantity_lent'], $lend['consumable_id']);
                        $consumable_stmt->execute();
                        $consumable_stmt->close();
                        
                        $message = "Consumable returned successfully!";
                        $message_type = "success";
                        logSystemAction($_SESSION['user_id'], 'consumable_returned', 'lend_consumables', "Returned consumable lend transaction ID {$lend_id}");
                    } else {
                        throw new Exception("Failed to update return transaction: " . $update_stmt->error);
                    }
                    $update_stmt->close();
                } else {
                    $message = "This transaction has already been returned.";
                    $message_type = "warning";
                }
            } else {
                $message = "Lend transaction not found.";
                $message_type = "danger";
            }
            $lend_stmt->close();
        } catch (Exception $e) {
            $message = "Error returning consumable: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Handle filter parameters
$to_office_filter = isset($_GET['to_office']) ? intval($_GET['to_office']) : 0;
$from_office_filter = isset($_GET['from_office']) ? intval($_GET['from_office']) : 3; // Default to Supply Office (ID = 3)
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get lend transactions with merged quantities for same consumable_id and to_office_id
$lend_transactions = [];
try {
    $sql = "SELECT 
                lc.consumable_id,
                lc.description,
                SUM(lc.quantity_lent) as total_quantity_lent,
                lc.unit_cost,
                SUM(lc.total_value) as total_value,
                lc.from_office_id,
                lc.to_office_id,
                fo.office_name as to_office_name,
                o.office_name as from_office_name,
                lc.status,
                COUNT(lc.id) as transaction_count,
                MAX(lc.date_lent) as latest_date_lent,
                MAX(lc.expected_return_date) as latest_expected_return_date,
                GROUP_CONCAT(DISTINCT lc.received_by ORDER BY lc.date_lent DESC SEPARATOR ', ') as received_by_list
            FROM lend_consumables lc
            LEFT JOIN offices fo ON lc.to_office_id = fo.id
            LEFT JOIN offices o ON lc.from_office_id = o.id";
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($from_office_filter > 0) {
        $where_conditions[] = "lc.from_office_id = ?";
        $params[] = $from_office_filter;
        $types .= 'i';
    }
    
    if ($to_office_filter > 0) {
        $where_conditions[] = "lc.to_office_id = ?";
        $params[] = $to_office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $where_conditions[] = "(lc.description LIKE ? OR fo.office_name LIKE ? OR lc.received_by LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " GROUP BY lc.consumable_id, lc.from_office_id, lc.to_office_id, lc.description, lc.unit_cost, lc.status
              ORDER BY lc.date_lent DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lend_transactions[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    $message = "Error fetching lend transactions: " . $e->getMessage();
    $message_type = "danger";
}

// Get offices for dropdown
$offices = [];
try {
    $result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching offices: " . $e->getMessage());
}

// Get available consumables for lending from Supply Office only
$available_consumables = [];
try {
    $result = $conn->query("SELECT id, description, quantity, unit_cost, office_id FROM consumables WHERE quantity > 0 AND office_id = 3 ORDER BY description");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $available_consumables[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching consumables: " . $e->getMessage());
}

// Get statistics based on filter
$stats = [];
try {
    $sql = "SELECT 
                COUNT(DISTINCT id) as total_transactions,
                COUNT(DISTINCT consumable_id) as unique_consumables,
                SUM(quantity_lent) as total_quantity_lent,
                SUM(total_value) as total_value_lent,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_returns,
                COUNT(CASE WHEN status = 'returned' THEN 1 END) as completed_returns
            FROM lend_consumables";
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Apply same filter as the main table
    if ($from_office_filter > 0) {
        $where_conditions[] = "from_office_id = ?";
        $params[] = $from_office_filter;
        $types .= 'i';
    }
    
    if ($to_office_filter > 0) {
        $where_conditions[] = "to_office_id = ?";
        $params[] = $to_office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $where_conditions[] = "(description LIKE ? OR received_by LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats = $result->fetch_assoc();
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Consumables - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Borrowing Consumables';
    ?>
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
                        <i class="bi bi-arrow-left-right"></i> Borrow Consumables
                    </h1>
                    <p class="text-muted mb-0">Manage consumable borrowing to offices</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lendConsumableModal">
                            <i class="bi bi-arrow-up-right"></i> Borrow Consumable
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="consumables.php">
                                        <i class="bi bi-box-seam"></i> Back to Consumables
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="release_history.php">
                                        <i class="bi bi-clock-history"></i> Release History
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="exportBorrowTransactions()">
                                        <i class="bi bi-download"></i> Export Transactions
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_transactions'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-arrow-left-right"></i> Total Transactions</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_quantity_lent'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-box"></i> Total Quantity Borrowed</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_value_lent'] ?? 0, 2); ?></div>
                    <div class="stats-label"><i class="bi bi-currency-dollar"></i> Total Value Borrowed</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['pending_returns'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-clock"></i> Pending Returns</div>
                </div>
            </div>
        </div>
        
        <!-- Lend Transactions Table -->
        <div class="table-container">
            <div class="row mb-3 align-items-center">
                <div class="col-md-2">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Borrow Transactions</h5>
                </div>
                <div class="col-md-10">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="fromOfficeFilter">
                                <option value="">All From Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $from_office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="toOfficeFilter">
                                <option value="">All To Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['id']; ?>" <?php echo $to_office_filter == $office['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search by description, office, or received by..." value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Total Quantity Borrowed</th>
                            <th>Unit Cost</th>
                            <th>Total Value</th>
                            <th>From Office</th>
                            <th>To Office</th>
                            <th>Latest Date Borrowed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lend_transactions)): ?>
                            <?php foreach ($lend_transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                    </td>
                                    <td><?php echo $transaction['total_quantity_lent']; ?></td>
                                    <td><?php echo number_format($transaction['unit_cost'], 2); ?></td>
                                    <td class="text-value"><?php echo number_format($transaction['total_value'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['from_office_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['to_office_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($transaction['latest_date_lent'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No borrow transactions found. Click "Borrow Consumable" to create your first transaction.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Lend Consumable Modal -->
    <div class="modal fade" id="lendConsumableModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-up-right"></i> Borrow Consumable</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="lend">
                        
                        <div class="mb-3">
                            <label class="form-label">Consumable from Supply Office *</label>
                            <input type="text" class="form-control" id="consumableSearch" name="consumable_search" list="consumableList" placeholder="Search and select consumable..." autocomplete="off" required>
                            <input type="hidden" name="consumable_id" id="consumableId" required>
                            <datalist id="consumableList">
                                <?php foreach ($available_consumables as $consumable): ?>
                                    <option value="<?php echo htmlspecialchars($consumable['description']); ?>" 
                                            data-id="<?php echo $consumable['id']; ?>"
                                            data-quantity="<?php echo $consumable['quantity']; ?>"
                                            data-unit-cost="<?php echo $consumable['unit_cost']; ?>"
                                            data-display-text="<?php echo htmlspecialchars($consumable['description']); ?> (Available: <?php echo $consumable['quantity']; ?>)">
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                            <small class="text-muted">Only consumables stored in Supply Office are available for borrowing</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantity to Borrow *</label>
                                    <input type="number" class="form-control" name="quantity_lent" id="quantityLent" min="1" required>
                                    <small class="text-muted">Available quantity will be shown here</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Cost</label>
                                    <input type="number" class="form-control" id="unitCost" step="0.01" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">To Office *</label>
                                    <select class="form-select" name="to_office_id" required>
                                        <option value="">Select Office</option>
                                        <?php foreach ($offices as $office): ?>
                                            <option value="<?php echo $office['id']; ?>">
                                                <?php echo htmlspecialchars($office['office_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Received By *</label>
                                    <input type="text" class="form-control" name="received_by" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Date Borrowed *</label>
                                    <input type="date" class="form-control" name="date_lent" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <strong>Total Value:</strong> <span id="totalValue">0.00</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-up-right"></i> Borrow Consumable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Return Consumable Modal -->
    <div class="modal fade" id="returnConsumableModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-left"></i> Return Consumable</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="return">
                        <input type="hidden" name="lend_id" id="returnLendId">
                        
                        <div class="mb-3">
                            <label class="form-label">Actual Return Date *</label>
                            <input type="date" class="form-control" name="actual_return_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This will mark the selected transaction as returned and update the consumable quantity.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-box-arrow-in-left"></i> Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle consumable selection (now handled by datalist functionality above)
            // The old consumableSelect handler is no longer needed
            
            // Calculate total value when quantity changes
            document.getElementById('quantityLent').addEventListener('input', function() {
                const quantity = parseInt(this.value) || 0;
                const unitCost = parseFloat(document.getElementById('unitCost').value) || 0;
                const totalValue = quantity * unitCost;
                document.getElementById('totalValue').textContent = totalValue.toFixed(2);
            });
            
            function calculateTotalValue() {
                const quantity = parseInt(document.getElementById('quantityLent').value) || 0;
                const unitCost = parseFloat(document.getElementById('unitCost').value) || 0;
                const totalValue = quantity * unitCost;
                document.getElementById('totalValue').textContent = totalValue.toFixed(2);
            }
            
            // Filter functionality
            document.getElementById('fromOfficeFilter').addEventListener('change', function() {
                const fromOfficeValue = this.value;
                const currentUrl = new URL(window.location);
                if (fromOfficeValue) {
                    currentUrl.searchParams.set('from_office', fromOfficeValue);
                } else {
                    currentUrl.searchParams.delete('from_office');
                }
                // Preserve other parameters
                const toOfficeValue = currentUrl.searchParams.get('to_office');
                if (!toOfficeValue) {
                    currentUrl.searchParams.delete('to_office');
                }
                const searchValue = currentUrl.searchParams.get('search');
                if (!searchValue) {
                    currentUrl.searchParams.delete('search');
                }
                window.location.href = currentUrl.toString();
            });
            
            document.getElementById('toOfficeFilter').addEventListener('change', function() {
                const toOfficeValue = this.value;
                const currentUrl = new URL(window.location);
                if (toOfficeValue) {
                    currentUrl.searchParams.set('to_office', toOfficeValue);
                } else {
                    currentUrl.searchParams.delete('to_office');
                }
                // Preserve other parameters
                const fromOfficeValue = currentUrl.searchParams.get('from_office');
                if (!fromOfficeValue) {
                    currentUrl.searchParams.delete('from_office');
                }
                const searchValue = currentUrl.searchParams.get('search');
                if (!searchValue) {
                    currentUrl.searchParams.delete('search');
                }
                window.location.href = currentUrl.toString();
            });
            
            // Search functionality with debounce
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchValue = this.value.trim();
                
                searchTimeout = setTimeout(() => {
                    const currentUrl = new URL(window.location);
                    if (searchValue) {
                        currentUrl.searchParams.set('search', searchValue);
                    } else {
                        currentUrl.searchParams.delete('search');
                    }
                    // Preserve other parameters
                    const fromOfficeValue = currentUrl.searchParams.get('from_office');
                    if (!fromOfficeValue) {
                        currentUrl.searchParams.delete('from_office');
                    }
                    const toOfficeValue = currentUrl.searchParams.get('to_office');
                    if (!toOfficeValue) {
                        currentUrl.searchParams.delete('to_office');
                    }
                    window.location.href = currentUrl.toString();
                }, 500);
            });
        });
        
        // Searchable datalist functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('consumableSearch');
            const hiddenIdInput = document.getElementById('consumableId');
            const quantityLentInput = document.getElementById('quantityLent');
            const unitCostInput = document.getElementById('unitCost');
            const datalist = document.getElementById('consumableList');
            
            // Store consumable data for quick lookup
            const consumableData = {};
            const options = datalist.querySelectorAll('option');
            options.forEach(option => {
                const id = option.dataset.id;
                if (id) {
                    consumableData[id] = {
                        id: id,
                        description: option.value,
                        quantity: parseInt(option.dataset.quantity),
                        unitCost: parseFloat(option.dataset.unitCost),
                        displayText: option.dataset.displayText
                    };
                }
            });
            
            // Handle input change
            searchInput.addEventListener('input', function() {
                const inputValue = this.value.trim();
                const matchingOption = Array.from(options).find(option => 
                    option.value.toLowerCase() === inputValue.toLowerCase()
                );
                
                if (matchingOption) {
                    const data = consumableData[matchingOption.dataset.id];
                    if (data) {
                        hiddenIdInput.value = data.id;
                        quantityLentInput.max = data.quantity;
                        quantityLentInput.placeholder = `Max: ${data.quantity}`;
                        unitCostInput.value = data.unitCost.toFixed(2);
                        calculateTotalValue();
                    }
                } else {
                    // Clear fields if no match
                    hiddenIdInput.value = '';
                    quantityLentInput.max = '';
                    quantityLentInput.placeholder = '';
                    unitCostInput.value = '';
                    calculateTotalValue();
                }
            });
            
            // Handle change event for final selection
            searchInput.addEventListener('change', function() {
                const inputValue = this.value.trim();
                const matchingOption = Array.from(options).find(option => 
                    option.value.toLowerCase() === inputValue.toLowerCase()
                );
                
                if (matchingOption) {
                    const data = consumableData[matchingOption.dataset.id];
                    if (data) {
                        this.value = data.description; // Ensure clean description
                        hiddenIdInput.value = data.id;
                    }
                } else {
                    // Clear if invalid selection
                    this.value = '';
                    hiddenIdInput.value = '';
                }
            });
            
            // Calculate total value function
            function calculateTotalValue() {
                const quantity = parseInt(quantityLentInput.value) || 0;
                const unitCost = parseFloat(unitCostInput.value) || 0;
                const totalValue = quantity * unitCost;
                document.getElementById('totalValue').textContent = totalValue.toFixed(2);
            }
            
            // Quantity change handler
            quantityLentInput.addEventListener('input', calculateTotalValue);
        });
        
        // Export borrow transactions function
        function exportBorrowTransactions() {
            // Get current table data from DOM
            const table = document.querySelector('table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let csv = 'Description,Total Quantity Borrowed,Unit Cost,Total Value,From Office,To Office,Latest Date Borrowed\n';
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length === 7) { // Skip empty message row
                    const rowData = [
                        cells[0].textContent.replace(/\s+/g, ' ').trim(), // Description
                        cells[1].textContent.trim(), // Total Quantity Borrowed
                        cells[2].textContent.trim(), // Unit Cost
                        cells[3].textContent.replace(/[^0-9.-]+/g, '').trim(), // Total Value
                        cells[4].textContent.trim(), // From Office
                        cells[5].textContent.trim(), // To Office
                        cells[6].textContent.trim()  // Latest Date Borrowed
                    ];
                    csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
                }
            }
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `borrow_transactions_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Open return modal function
        function openReturnModal(consumableId, toOfficeId) {
            // Find the latest pending lend transaction for this consumable and office
            fetch(`lend_consumables.php?action=get_pending&consumable_id=${consumableId}&to_office_id=${toOfficeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('returnLendId').value = data.lend_id;
                        const modal = new bootstrap.Modal(document.getElementById('returnConsumableModal'));
                        modal.show();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading return data');
                });
        }
    </script>
</body>
</html>

<?php
// Handle AJAX request for getting pending lend transaction
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_pending') {
    $consumable_id = intval($_GET['consumable_id'] ?? 0);
    $to_office_id = intval($_GET['to_office_id'] ?? 0);
    
    if ($consumable_id > 0 && $to_office_id > 0) {
        try {
            $query = "SELECT id FROM lend_consumables WHERE consumable_id = ? AND to_office_id = ? AND status = 'pending' ORDER BY date_lent DESC LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $consumable_id, $to_office_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'lend_id' => $row['id']]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'No pending transaction found']);
            }
            $stmt->close();
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }
}
?>
