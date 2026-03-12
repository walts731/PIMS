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

// Log release history page access
logSystemAction($_SESSION['user_id'], 'access', 'release_history', 'Admin accessed release history page');

// Handle filter parameters
$from_office_filter = isset($_GET['from_office']) ? intval($_GET['from_office']) : 0;
$to_office_filter = isset($_GET['to_office']) ? intval($_GET['to_office']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$transaction_type = isset($_GET['transaction_type']) ? trim($_GET['transaction_type']) : 'addition'; // Default to addition tab

// Get consumable transaction history (additions, releases, and lends) with filters
$transaction_history = [];
try {
    // Union query to get additions, releases, and lends
    $sql = "(SELECT 
                'addition' as transaction_type,
                c.id,
                c.description,
                c.quantity as quantity,
                c.units,
                c.unit_cost,
                c.quantity * c.unit_cost as total_value,
                c.office_id as from_office_id,
                c.for_office_id as to_office_id,
                NULL as released_by,
                CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                NULL as received_by,
                c.created_at as transaction_date,
                'Consumable added to inventory' as notes,
                c.created_at,
                fo.office_name as from_office_name,
                to_off.office_name as to_office_name,
                NULL as expected_return_date,
                NULL as actual_return_date,
                NULL as lend_status
            FROM consumables c
            LEFT JOIN users u ON 1=0 -- No user for additions, but we need column
            LEFT JOIN offices fo ON c.office_id = fo.id
            LEFT JOIN offices to_off ON c.for_office_id = to_off.id
            WHERE c.created_at IS NOT NULL) 
            
            UNION ALL
            
            (SELECT 
                'release' as transaction_type,
                h.id,
                h.description,
                h.quantity_released as quantity,
                c.units,
                h.unit_cost,
                h.total_value,
                h.from_office_id,
                h.to_office_id,
                h.released_by,
                CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                h.received_by,
                h.release_date as transaction_date,
                h.notes,
                h.created_at,
                fo.office_name as from_office_name,
                to_off.office_name as to_office_name,
                NULL as expected_return_date,
                NULL as actual_return_date,
                NULL as lend_status
            FROM consumable_release_history h
            LEFT JOIN users u ON h.released_by = u.id
            LEFT JOIN consumables c ON h.consumable_id = c.id
            LEFT JOIN offices fo ON h.from_office_id = fo.id
            LEFT JOIN offices to_off ON h.to_office_id = to_off.id)
            
            UNION ALL
            
            (SELECT 
                'lend' as transaction_type,
                l.id,
                l.description,
                l.quantity_lent as quantity,
                c.units,
                l.unit_cost,
                l.total_value,
                l.from_office_id,
                l.to_office_id,
                l.lent_by,
                CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                l.received_by,
                l.date_lent as transaction_date,
                l.notes,
                l.created_at,
                fo.office_name as from_office_name,
                to_off.office_name as to_office_name,
                l.expected_return_date,
                l.actual_return_date,
                l.status as lend_status
            FROM lend_consumables l
            LEFT JOIN users u ON l.lent_by = u.id
            LEFT JOIN consumables c ON l.consumable_id = c.id
            LEFT JOIN offices fo ON l.from_office_id = fo.id
            LEFT JOIN offices to_off ON l.to_office_id = to_off.id)
            
            ORDER BY transaction_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Apply filters
        $include_record = true;
        
        // Filter by transaction type
        if (!empty($transaction_type) && $row['transaction_type'] !== $transaction_type) {
            $include_record = false;
        }
        
        // Filter by from office
        if ($from_office_filter > 0 && $row['from_office_id'] != $from_office_filter) {
            $include_record = false;
        }
        
        // Filter by to office
        if ($to_office_filter > 0 && $row['to_office_id'] != $to_office_filter) {
            $include_record = false;
        }
        
        // Filter by search term
        if (!empty($search_filter)) {
            $search_term = strtolower($search_filter);
            $description_match = strpos(strtolower($row['description']), $search_term) !== false;
            $from_office_match = strpos(strtolower($row['from_office_name'] ?? ''), $search_term) !== false;
            $to_office_match = strpos(strtolower($row['to_office_name'] ?? ''), $search_term) !== false;
            $released_by_match = strpos(strtolower($row['released_by_name'] ?? ''), $search_term) !== false;
            
            if (!$description_match && !$from_office_match && !$to_office_match && !$released_by_match) {
                $include_record = false;
            }
        }
        
        // Filter by date range
        if (!empty($date_from) && date('Y-m-d', strtotime($row['transaction_date'])) < $date_from) {
            $include_record = false;
        }
        
        if (!empty($date_to) && date('Y-m-d', strtotime($row['transaction_date'])) > $date_to) {
            $include_record = false;
        }
        
        if ($include_record) {
            $transaction_history[] = $row;
        }
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching transaction history: " . $e->getMessage());
}

// Get offices for dropdown filters
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release History - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
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
    $page_title = 'Release History';
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
                        <i class="bi bi-clock-history"></i> Release History
                    </h1>
                    <p class="text-muted mb-0">Track all consumable release transactions</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="consumables.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> Back to Consumables
                    </a>
                    <button class="btn btn-outline-success btn-sm" onclick="exportReleaseHistory()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
                
        <!-- Transaction Type Tabs -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-12">
                    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Transaction History</h5>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($transaction_type == 'addition') ? 'active' : ''; ?>" 
                                    id="additions-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#additions" 
                                    type="button" 
                                    role="tab" 
                                    onclick="switchTab('addition')">
                                <i class="bi bi-plus-circle"></i> Additions
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($transaction_type == 'release') ? 'active' : ''; ?>" 
                                    id="releases-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#releases" 
                                    type="button" 
                                    role="tab" 
                                    onclick="switchTab('release')">
                                <i class="bi bi-box-arrow-right"></i> Releases
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($transaction_type == 'lend') ? 'active' : ''; ?>" 
                                    id="lends-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#lends" 
                                    type="button" 
                                    role="tab" 
                                    onclick="switchTab('lend')">
                                <i class="bi bi-arrow-up-right"></i> Lends
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Remaining Filters -->
                    <form method="GET" id="filterForm" class="mt-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">From Office</label>
                                <select class="form-select form-select-sm" name="from_office">
                                    <option value="">All Offices</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo ($from_office_filter == $office['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">To Office</label>
                                <select class="form-select form-select-sm" name="to_office">
                                    <option value="">All Offices</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo ($to_office_filter == $office['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label form-label-sm">Date Range</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From">
                                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm">&nbsp;</label>
                                <div>
                                    <a href="release_history.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-arrow-clockwise"></i> Clear Filters
                                    </a>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="transaction_type" id="transactionTypeInput" value="<?php echo htmlspecialchars($transaction_type); ?>">
                    </form>
                </div>
            </div>
        </div>
        
        <!-- History Table -->
        <div class="table-container">
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> 
                        <?php 
                        switch($transaction_type) {
                            case 'addition': echo 'Addition History'; break;
                            case 'release': echo 'Release History'; break;
                            case 'lend': echo 'Lend History'; break;
                            default: echo 'Transaction History'; break;
                        }
                        ?>
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="tableSearch" placeholder="Search transactions..." value="<?php echo htmlspecialchars($search_filter); ?>">
                                <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-secondary me-2"><?php echo count($transaction_history); ?> records</span>
                            <?php if (!empty($transaction_type)): ?>
                                <span class="badge bg-info me-2"><?php echo ucfirst($transaction_type); ?></span>
                            <?php endif; ?>
                            <?php if ($from_office_filter > 0 || $to_office_filter > 0 || !empty($date_from) || !empty($date_to)): ?>
                                <span class="badge bg-success">Filters Applied</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="releaseHistoryTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Units</th>
                            <th>Unit Cost</th>
                            <th>Total Value</th>
                            <th>From Office</th>
                            <th>To Office</th>
                            <th>Released By</th>
                            <th>Received By</th>
                            <?php if ($transaction_type == 'lend'): ?>
                                <th>Expected Return</th>
                                <th>Status</th>
                            <?php endif; ?>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transaction_history)): ?>
                            <?php foreach ($transaction_history as $transaction): ?>
                                <tr>
                                    <td><small><?php echo date('M j, Y H:i', strtotime($transaction['transaction_date'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td><span class="quantity-badge"><?php echo $transaction['quantity']; ?></span></td>
                                    <td><?php echo htmlspecialchars($transaction['units'] ?: 'N/A'); ?></td>
                                    <td><?php echo number_format($transaction['unit_cost'], 2); ?></td>
                                    <td><span class="value-badge"><?php echo number_format($transaction['total_value'], 2); ?></span></td>
                                    <td><?php echo htmlspecialchars($transaction['from_office_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['to_office_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['released_by_name'] ?: 'System'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['received_by'] ?: 'Not specified'); ?></td>
                                    <?php if ($transaction_type == 'lend'): ?>
                                        <td>
                                            <?php if ($transaction['transaction_type'] === 'lend' && !empty($transaction['expected_return_date'])): ?>
                                                <small><?php echo date('M j, Y', strtotime($transaction['expected_return_date'])); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">N/A</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['transaction_type'] === 'lend' && !empty($transaction['lend_status'])): ?>
                                                <?php if ($transaction['lend_status'] === 'lent'): ?>
                                                    <span class="badge bg-warning text-dark">Lent</span>
                                                <?php elseif ($transaction['lend_status'] === 'returned'): ?>
                                                    <span class="badge bg-success">Returned</span>
                                                <?php elseif ($transaction['lend_status'] === 'overdue'): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="text-muted">N/A</small>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td><small><?php echo htmlspecialchars($transaction['notes'] ?: 'No notes'); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewTransactionDetails(<?php echo $transaction['id']; ?>, '<?php echo $transaction['transaction_type']; ?>')">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($transaction_type == 'lend') ? '14' : '12'; ?>" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No transaction history found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
    
    <!-- View Transaction Details Modal -->
    <div class="modal fade" id="viewTransactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Transaction Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="transactionDetails">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading transaction details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <script>
        // Tab switching function
        function switchTab(transactionType) {
            const form = document.getElementById('filterForm');
            const transactionTypeInput = document.getElementById('transactionTypeInput');
            
            // Update hidden input
            transactionTypeInput.value = transactionType;
            
            // Submit form to reload page with new tab
            form.submit();
        }
        
        // Auto-search functionality for filters
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-search for all filter inputs (selects and date inputs)
            const filterSelects = document.querySelectorAll('#filterForm select');
            const filterDates = document.querySelectorAll('#filterForm input[type="date"]');
            
            // Add change event listeners to all select elements
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    console.log('Filter changed:', this.name, this.value);
                    // Auto-submit form
                    const form = document.getElementById('filterForm');
                    form.submit();
                });
            });
            
            // Add change event listeners to all date inputs
            filterDates.forEach(input => {
                input.addEventListener('change', function() {
                    console.log('Date filter changed:', this.name, this.value);
                    // Auto-submit form
                    const form = document.getElementById('filterForm');
                    form.submit();
                });
            });
            
            // Search functionality from table area
            const searchInput = document.getElementById('tableSearch');
            const searchBtn = document.getElementById('searchBtn');
            
            // Function to perform search
            function performSearch() {
                const searchValue = searchInput.value.trim();
                const currentUrl = new URL(window.location);
                
                // Update or remove search parameter
                if (searchValue) {
                    currentUrl.searchParams.set('search', searchValue);
                } else {
                    currentUrl.searchParams.delete('search');
                }
                
                // Navigate to new URL
                window.location.href = currentUrl.toString();
            }
            
            // Search on button click
            if (searchBtn) {
                searchBtn.addEventListener('click', performSearch);
            }
            
            // Search on Enter key press
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        performSearch();
                    }
                });
            }
        });
        
        // View transaction details function
        function viewTransactionDetails(transactionId, transactionType) {
            const modal = new bootstrap.Modal(document.getElementById('viewTransactionModal'));
            const detailsContainer = document.getElementById('transactionDetails');
            
            // Show loading state
            detailsContainer.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading transaction details...</p>
                </div>
            `;
            
            // Show modal
            modal.show();
            
            // Fetch transaction details
            fetch(`get_transaction_details.php?id=${transactionId}&type=${transactionType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTransactionDetails(data.data);
                    } else {
                        detailsContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> 
                                Error: ${data.error}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    detailsContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Error loading transaction details. Please try again.
                        </div>
                    `;
                });
        }
        
        // Display transaction details function
        function displayTransactionDetails(transaction) {
            const detailsContainer = document.getElementById('transactionDetails');
            
            let typeBadge = '';
            let typeIcon = '';
            let typeTitle = '';
            
            switch(transaction.transaction_type) {
                case 'addition':
                    typeBadge = '<span class="badge bg-success">Addition</span>';
                    typeIcon = '<i class="bi bi-plus-circle text-success"></i>';
                    typeTitle = 'Consumable Addition';
                    break;
                case 'release':
                    typeBadge = '<span class="badge bg-primary">Release</span>';
                    typeIcon = '<i class="bi bi-box-arrow-right text-primary"></i>';
                    typeTitle = 'Consumable Release';
                    break;
                case 'lend':
                    typeBadge = '<span class="badge bg-warning text-dark">Lend</span>';
                    typeIcon = '<i class="bi bi-arrow-up-right text-warning"></i>';
                    typeTitle = 'Consumable Lend';
                    break;
            }
            
            let detailsHTML = `
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center mb-3">
                            ${typeIcon}
                            <h5 class="mb-0 ms-2">${typeTitle}</h5>
                            <div class="ms-auto">${typeBadge}</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Transaction Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Date:</strong></div>
                                    <div class="col-sm-8">${transaction.transaction_date_formatted}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Description:</strong></div>
                                    <div class="col-sm-8">${transaction.description}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Quantity:</strong></div>
                                    <div class="col-sm-8">${transaction.quantity}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Units:</strong></div>
                                    <div class="col-sm-8">${transaction.units || 'N/A'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Unit Cost:</strong></div>
                                    <div class="col-sm-8">₱${parseFloat(transaction.unit_cost || 0).toFixed(2)}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Total Value:</strong></div>
                                    <div class="col-sm-8">₱${(parseFloat(transaction.quantity || 0) * parseFloat(transaction.unit_cost || 0)).toFixed(2)}</div>
                                </div>
                                ${transaction.notes || (transaction.transaction_type === 'addition' ? 'Consumable added to inventory' : '') ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Notes:</strong></div>
                                    <div class="col-sm-8">${transaction.notes || (transaction.transaction_type === 'addition' ? 'Consumable added to inventory' : '')}</div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-people"></i> People & Offices</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>From Office:</strong></div>
                                    <div class="col-sm-8">${transaction.from_office_name || 'N/A'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>To Office:</strong></div>
                                    <div class="col-sm-8">${transaction.to_office_name || 'N/A'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Processed By:</strong></div>
                                    <div class="col-sm-8">${transaction.released_by_name || transaction.lent_by_name || 'System'}</div>
                                </div>
                                ${transaction.received_by || (transaction.transaction_type === 'addition' ? 'N/A' : '') ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Received By:</strong></div>
                                    <div class="col-sm-8">${transaction.received_by || (transaction.transaction_type === 'addition' ? 'N/A' : '')}</div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        ${transaction.transaction_type === 'lend' ? `
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Lend Information</h6>
                            </div>
                            <div class="card-body">
                                ${transaction.expected_return_date ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Expected Return:</strong></div>
                                    <div class="col-sm-8">${transaction.expected_return_date_formatted}</div>
                                </div>
                                ` : ''}
                                ${transaction.actual_return_date ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Actual Return:</strong></div>
                                    <div class="col-sm-8">${transaction.actual_return_date_formatted}</div>
                                </div>
                                ` : ''}
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Status:</strong></div>
                                    <div class="col-sm-8">
                                        ${transaction.status === 'lent' ? '<span class="badge bg-warning text-dark">Lent</span>' : ''}
                                        ${transaction.status === 'returned' ? '<span class="badge bg-success">Returned</span>' : ''}
                                        ${transaction.status === 'overdue' ? '<span class="badge bg-danger">Overdue</span>' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            detailsContainer.innerHTML = detailsHTML;
        }

        function exportReleaseHistory() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            const currentTab = params.get('transaction_type') || 'addition';
            
            // Create CSV content based on current tab
            let csvContent = "";
            if (currentTab === 'lend') {
                csvContent = "Date,Description,Quantity,Units,Unit Cost,Total Value,From Office,To Office,Released By,Received By,Expected Return,Status,Notes\n";
            } else {
                csvContent = "Date,Description,Quantity,Units,Unit Cost,Total Value,From Office,To Office,Released By,Received By,Notes\n";
            }
            
            <?php if (!empty($transaction_history)): ?>
                // Transaction data as JSON for safe handling
                const transactionData = <?php echo json_encode($transaction_history); ?>;
                
                // Process each transaction
                transactionData.forEach(transaction => {
                    const date = transaction.transaction_date ? new Date(transaction.transaction_date).toISOString().slice(0, 19).replace('T', ' ') : '';
                    const description = (transaction.description || '').replace(/"/g, '""');
                    const quantity = transaction.quantity || 0;
                    const units = (transaction.units || 'N/A').replace(/"/g, '""');
                    const unitCost = transaction.unit_cost || 0;
                    const totalValue = transaction.total_value || 0;
                    const fromOffice = (transaction.from_office_name || 'N/A').replace(/"/g, '""');
                    const toOffice = (transaction.to_office_name || 'N/A').replace(/"/g, '""');
                    const releasedBy = (transaction.released_by_name || 'System').replace(/"/g, '""');
                    const receivedBy = (transaction.received_by || 'Not specified').replace(/"/g, '""');
                    const notes = (transaction.notes || 'No notes').replace(/"/g, '""');
                    
                    if (currentTab === 'lend') {
                        const expectedReturn = (transaction.transaction_type === 'lend' && transaction.expected_return_date) ? 
                            new Date(transaction.expected_return_date).toISOString().slice(0, 10) : 'N/A';
                        const status = (transaction.transaction_type === 'lend' && transaction.lend_status) ? 
                            transaction.lend_status.charAt(0).toUpperCase() + transaction.lend_status.slice(1) : 'N/A';
                        
                        csvContent += `"${date}","${description}","${quantity}","${units}","${unitCost}","${totalValue}","${fromOffice}","${toOffice}","${releasedBy}","${receivedBy}","${expectedReturn}","${status}","${notes}"\n`;
                    } else {
                        csvContent += `"${date}","${description}","${quantity}","${units}","${unitCost}","${totalValue}","${fromOffice}","${toOffice}","${releasedBy}","${receivedBy}","${notes}"\n`;
                    }
                });
            <?php endif; ?>
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'consumable_transaction_history_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
