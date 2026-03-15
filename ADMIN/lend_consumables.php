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
            $consumable_stmt = $conn->prepare("SELECT description, quantity, unit_cost, office_id, for_office_id FROM consumables WHERE id = ? AND quantity >= ?");
            $consumable_stmt->bind_param("ii", $consumable_id, $quantity_lent);
            $consumable_stmt->execute();
            $consumable_result = $consumable_stmt->get_result();
            
            if ($consumable_result->num_rows > 0) {
                $consumable = $consumable_result->fetch_assoc();
                $total_value = $quantity_lent * $consumable['unit_cost'];
                
                // Insert detailed transaction record into lend_consumables table
                $insert_lend_stmt = $conn->prepare("INSERT INTO lend_consumables (consumable_id, description, quantity_lent, unit_cost, total_value, from_office_id, to_office_id, lent_by, received_by, date_lent, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'lent', ?, NOW(), NOW())");
                $insert_lend_stmt->bind_param("isidiiissss", $consumable_id, $consumable['description'], $quantity_lent, $consumable['unit_cost'], $total_value, $consumable['office_id'], $to_office_id, $lent_by, $received_by, $date_lent, $notes);
                $insert_lend_stmt->execute();
                $insert_lend_stmt->close();
                
                // Check if balance record exists for this consumable and office
                $balance_stmt = $conn->prepare("SELECT id, total_borrowed, current_balance FROM consumable_balance WHERE consumable_id = ? AND office_id = ? AND for_office_id = ?");
                $balance_stmt->bind_param("iii", $consumable_id, $consumable['office_id'], $to_office_id);
                $balance_stmt->execute();
                $balance_result = $balance_stmt->get_result();
                
                if ($balance_result->num_rows > 0) {
                    // Update existing balance record
                    $balance = $balance_result->fetch_assoc();
                    $new_total_borrowed = $balance['total_borrowed'] + $quantity_lent;
                    $new_current_balance = $balance['current_balance'] + $quantity_lent;
                    
                    $update_balance_stmt = $conn->prepare("UPDATE consumable_balance SET total_borrowed = ?, current_balance = ?, last_updated = NOW() WHERE id = ?");
                    $update_balance_stmt->bind_param("iii", $new_total_borrowed, $new_current_balance, $balance['id']);
                    $update_balance_stmt->execute();
                    $update_balance_stmt->close();
                } else {
                    // Insert new balance record
                    $insert_balance_stmt = $conn->prepare("INSERT INTO consumable_balance (consumable_id, consumable_description, office_id, office_name, for_office_id, total_borrowed, total_deducted, current_balance, last_updated, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    
                    // Get office names
                    $from_office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
                    $from_office_stmt->bind_param("i", $consumable['office_id']);
                    $from_office_stmt->execute();
                    $from_office_result = $from_office_stmt->get_result();
                    $from_office_name = $from_office_result->fetch_assoc()['office_name'] ?? 'Unknown';
                    $from_office_stmt->close();
                    
                    $insert_balance_stmt->bind_param("isisiiii", $consumable_id, $consumable['description'], $consumable['office_id'], $from_office_name, $to_office_id, $quantity_lent, 0, $quantity_lent);
                    $insert_balance_stmt->execute();
                    $insert_balance_stmt->close();
                }
                $balance_stmt->close();
                
                // Update consumable quantity
                $new_quantity = $consumable['quantity'] - $quantity_lent;
                $update_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_quantity, $consumable_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Insert regular inventory record in target office (for_office_id = NULL)
                $target_check_stmt = $conn->prepare("SELECT id, quantity FROM consumables WHERE description = ? AND office_id = ? AND for_office_id IS NULL FOR UPDATE");
                $target_check_stmt->bind_param("si", $consumable['description'], $to_office_id);
                $target_check_stmt->execute();
                $target_check_result = $target_check_stmt->get_result();
                
                if ($target_check_result->num_rows > 0) {
                    // Update existing regular inventory in target office
                    $target_consumable = $target_check_result->fetch_assoc();
                    $new_target_quantity = $target_consumable['quantity'] + $quantity_lent;
                    
                    $update_target_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                    $update_target_stmt->bind_param("ii", $new_target_quantity, $target_consumable['id']);
                    $update_target_stmt->execute();
                    $update_target_stmt->close();
                } else {
                    // Insert new regular inventory record in target office
                    $insert_target_stmt = $conn->prepare("INSERT INTO consumables (description, quantity, unit_cost, reorder_level, office_id, for_office_id) VALUES (?, ?, ?, ?, ?, NULL)");
                    $insert_target_stmt->bind_param("sidii", 
                        $consumable['description'], 
                        $quantity_lent, 
                        $consumable['unit_cost'], 
                        0, // Default reorder level
                        $to_office_id
                    );
                    $insert_target_stmt->execute();
                    $insert_target_stmt->close();
                }
                $target_check_stmt->close();
                
                $message = "Consumable lent successfully!";
                $message_type = "success";
                logSystemAction($_SESSION['user_id'], 'consumable_lent', 'lend_consumables', "Lent {$quantity_lent} units of {$consumable['description']} to office ID {$to_office_id}");
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
        `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_consumable_office` (`consumable_id`, `office_id`, `for_office_id`),
        INDEX `idx_for_office` (`for_office_id`),
        FOREIGN KEY (`consumable_id`) REFERENCES `consumables`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`for_office_id`) REFERENCES `offices`(`id`) ON DELETE SET NULL
    )";
    $conn->query($create_table_sql);
    
    // First, calculate and insert main total borrowed records from lend_consumables
    $main_totals_sql = "SELECT 
                            lc.consumable_id,
                            c.description as consumable_description,
                            lc.from_office_id as office_id,
                            o1.office_name as office_name,
                            lc.to_office_id as for_office_id,
                            o2.office_name as for_office_name,
                            SUM(lc.quantity_lent) as total_borrowed,
                            SUM(CASE WHEN lc.status = 'returned' THEN lc.quantity_lent ELSE 0 END) as total_deducted,
                            SUM(CASE WHEN lc.status = 'lent' THEN lc.quantity_lent ELSE 0 END) - SUM(CASE WHEN lc.status = 'returned' THEN lc.quantity_lent ELSE 0 END) as current_balance
                        FROM lend_consumables lc
                        LEFT JOIN consumables c ON lc.consumable_id = c.id
                        LEFT JOIN offices o1 ON lc.from_office_id = o1.id
                        LEFT JOIN offices o2 ON lc.to_office_id = o2.id";
    
    $main_where_conditions = [];
    $main_params = [];
    $main_types = '';
    
    if ($from_office_filter > 0) {
        $main_where_conditions[] = "lc.from_office_id = ?";
        $main_params[] = $from_office_filter;
        $main_types .= 'i';
    }
    
    if ($to_office_filter > 0) {
        $main_where_conditions[] = "lc.to_office_id = ?";
        $main_params[] = $to_office_filter;
        $main_types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $main_where_conditions[] = "(c.description LIKE ? OR o1.office_name LIKE ? OR o2.office_name LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $main_params[] = $search_term;
        $main_params[] = $search_term;
        $main_params[] = $search_term;
        $main_types .= 'sss';
    }
    
    if (!empty($main_where_conditions)) {
        $main_totals_sql .= " WHERE " . implode(" AND ", $main_where_conditions);
    }
    
    $main_totals_sql .= " GROUP BY lc.consumable_id, c.description, lc.from_office_id, o1.office_name, lc.to_office_id, o2.office_name";
    
    // Debug: Log the SQL query
    error_log("Main totals SQL: " . $main_totals_sql);
    error_log("Main params: " . print_r($main_params, true));
    
    $main_stmt = $conn->prepare($main_totals_sql);
    if (!empty($main_params)) {
        $main_stmt->bind_param($main_types, ...$main_params);
    }
    $main_stmt->execute();
    $main_result = $main_stmt->get_result();
    
    error_log("Main result count: " . $main_result->num_rows);
    
    // Insert/update main total borrowed records
    $insert_count = 0;
    $update_count = 0;
    while ($main_row = $main_result->fetch_assoc()) {
        $check_main_stmt = $conn->prepare("SELECT id FROM consumable_balance WHERE consumable_id = ? AND office_id = ? AND for_office_id = ?");
        $check_main_stmt->bind_param("iii", $main_row['consumable_id'], $main_row['office_id'], $main_row['for_office_id']);
        $check_main_stmt->execute();
        $check_result = $check_main_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing main record
            $update_main_stmt = $conn->prepare("UPDATE consumable_balance SET total_borrowed = ?, total_deducted = ?, current_balance = ?, last_updated = NOW() WHERE consumable_id = ? AND office_id = ? AND for_office_id = ?");
            $update_main_stmt->bind_param("iiiiii", $main_row['total_borrowed'], $main_row['total_deducted'], $main_row['current_balance'], $main_row['consumable_id'], $main_row['office_id'], $main_row['for_office_id']);
            $update_main_stmt->execute();
            $update_main_stmt->close();
            $update_count++;
        } else {
            // Insert new main record
            $insert_main_stmt = $conn->prepare("INSERT INTO consumable_balance (consumable_id, consumable_description, office_id, office_name, for_office_id, total_borrowed, total_deducted, current_balance, last_updated, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $insert_main_stmt->bind_param("isisiiii", $main_row['consumable_id'], $main_row['consumable_description'], $main_row['office_id'], $main_row['office_name'], $main_row['for_office_id'], $main_row['total_borrowed'], $main_row['total_deducted'], $main_row['current_balance']);
            $insert_main_stmt->execute();
            $insert_main_stmt->close();
            $insert_count++;
        }
        $check_main_stmt->close();
    }
    $main_stmt->close();
    
    error_log("Inserted: $insert_count, Updated: $update_count main records");
    
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
                    <div class="stats-number"><?php echo $stats['total_balance_records'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-balance-scale"></i> Balance Records</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_borrowed'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-box"></i> Total Borrowed</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_current_balance'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-arrow-left-right"></i> Current Balance</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['active_balances'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-check-circle"></i> Active Balances</div>
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
                            <th>Total Borrowed</th>
                            <th>Total Deducted</th>
                            <th>Current Balance</th>
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
                                    <td><?php echo $record['total_borrowed']; ?></td>
                                    <td><?php echo $record['total_deducted']; ?></td>
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
