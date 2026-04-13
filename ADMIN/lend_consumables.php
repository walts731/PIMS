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

// Borrow consumable functionality removed


// Handle filter parameters
$to_office_filter = isset($_GET['to_office']) ? intval($_GET['to_office']) : 0;
$from_office_filter = isset($_GET['from_office']) ? intval($_GET['from_office']) : 3; // Default to Supply Office (ID = 3)
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get consumable balance records with filters
$balance_records = [];
try {
    // First, ensure consumable_balance table exists with for_office_id column
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `consumable_balance` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `consumable_id` INT NOT NULL,
        `consumable_description` VARCHAR(255) NOT NULL,
        `office_id` INT NOT NULL,
        `office_name` VARCHAR(255) NOT NULL,
        `for_office_id` INT DEFAULT NULL,
        `total_borrowed` INT DEFAULT 0,
        `total_deducted` INT DEFAULT 0,
        `current_balance` INT DEFAULT 0,
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_consumable_office` (`consumable_id`, `office_id`, `for_office_id`),
        INDEX `idx_for_office` (`for_office_id`),
        FOREIGN KEY (`consumable_id`) REFERENCES `consumables`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`for_office_id`) REFERENCES `offices`(`id`) ON DELETE SET NULL
    )";
    $conn->query($create_table_sql);
    
    // Fix existing table: remove ON UPDATE CURRENT_TIMESTAMP from last_updated column
    $alter_table_sql = "ALTER TABLE consumable_balance MODIFY COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $conn->query($alter_table_sql);
    
    // Now get the final balance records for display
    $sql = "SELECT 
                cb.id,
                cb.consumable_id,
                cb.consumable_description,
                cb.office_id,
                cb.office_name,
                cb.for_office_id,
                cb.total_borrowed,
                cb.total_deducted,
                cb.current_balance,
                cb.last_updated,
                cb.created_at,
                fo.office_name as for_office_name,
                c.unit_cost
            FROM consumable_balance cb
            LEFT JOIN offices fo ON cb.for_office_id = fo.id
            LEFT JOIN consumables c ON cb.consumable_id = c.id";
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($from_office_filter > 0) {
        $where_conditions[] = "cb.office_id = ?";
        $params[] = $from_office_filter;
        $types .= 'i';
    }
    
    if ($to_office_filter > 0) {
        $where_conditions[] = "cb.for_office_id = ?";
        $params[] = $to_office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $where_conditions[] = "(cb.consumable_description LIKE ? OR cb.office_name LIKE ? OR fo.office_name LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY cb.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $balance_records[] = $row;
        }
    }
    $stmt->close();
    
} catch (Exception $e) {
    $message = "Error fetching balance records: " . $e->getMessage();
    $message_type = "danger";
    error_log("Error in balance records: " . $e->getMessage());
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

// Available consumables for lending functionality removed

// Get statistics based on filter
$stats = [];
try {
    $sql = "SELECT 
                COUNT(*) as total_balance_records,
                COUNT(DISTINCT consumable_id) as unique_consumables,
                SUM(total_borrowed) as total_borrowed,
                SUM(total_deducted) as total_deducted,
                SUM(current_balance) as total_current_balance,
                COUNT(CASE WHEN current_balance > 0 THEN 1 END) as active_balances
            FROM consumable_balance";
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Apply same filter as the main table
    if ($from_office_filter > 0) {
        $where_conditions[] = "office_id = ?";
        $params[] = $from_office_filter;
        $types .= 'i';
    }
    
    if ($to_office_filter > 0) {
        $where_conditions[] = "for_office_id = ?";
        $params[] = $to_office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $where_conditions[] = "(consumable_description LIKE ? OR office_name LIKE ?)";
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
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
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
                                <button class="dropdown-item" onclick="exportBorrowTransactions()">
                                    <i class="bi bi-download"></i> Export Transactions
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
                
        <!-- Balance Records Table -->
        <div class="table-container">
            <div class="row mb-3 align-items-center">
                <div class="col-md-2">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Balance Records</h5>
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
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search by description or office..." value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th> Balance</th>
                            <th>From Office</th>
                            <th>To Office</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($balance_records)): ?>
                            <?php foreach ($balance_records as $record): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($record['consumable_description']); ?>
                                    </td>
                                    
                                    <td>
                                        <span class="badge bg-<?php echo $record['current_balance'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $record['current_balance']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['office_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($record['for_office_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['last_updated'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No balance records found. Click "Borrow Consumable" to create your first balance record.</p>
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
        
        // Borrow consumable modal functionality removed
        
        // Export balance records function
        function exportBorrowTransactions() {
            // Get current table data from DOM
            const table = document.querySelector('table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let csv = 'Description,Total Borrowed,Total Deducted,Current Balance,From Office,To Office,Last Updated\n';
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length === 8) { // Skip empty message row
                    const rowData = [
                        cells[0].textContent.replace(/\s+/g, ' ').trim(), // Description
                        cells[1].textContent.trim(), // Total Borrowed
                        cells[2].textContent.trim(), // Total Deducted
                        cells[3].textContent.trim(), // Current Balance
                        cells[4].textContent.trim(), // From Office
                        cells[5].textContent.trim(), // To Office
                        cells[6].textContent.trim()  // Last Updated
                    ];
                    csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
                }
            }
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `balance_records_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
    </script>
</body>
</html>

<?php
// No AJAX handlers needed for balance system
?>
