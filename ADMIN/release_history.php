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
$transaction_type = isset($_GET['transaction_type']) ? trim($_GET['transaction_type']) : '';

// Get consumable transaction history (both additions and releases) with filters
$transaction_history = [];
try {
    // Union query to get both additions and releases
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
                to_off.office_name as to_office_name
            FROM consumables c
            LEFT JOIN users u ON 1=0 -- No user for additions, but we need the column
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
                to_off.office_name as to_office_name
            FROM consumable_release_history h
            LEFT JOIN users u ON h.released_by = u.id
            LEFT JOIN consumables c ON h.consumable_id = c.id
            LEFT JOIN offices fo ON h.from_office_id = fo.id
            LEFT JOIN offices to_off ON h.to_office_id = to_off.id)
            
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
        
                
        <!-- Filters -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-12">
                    
                    <form method="GET" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-1">
                            <h5 class="mb-3"><i class="bi bi-funnel"></i> Filters</h5>
                            </div>
                            <!-- First Row: Transaction Type and Office Filters -->
                            <div class="col-md-2">
                                <label class="form-label form-label-sm">Transaction Type</label>
                                <select class="form-select form-select-sm" name="transaction_type">
                                    <option value="">All Types</option>
                                    <option value="addition" <?php echo ($transaction_type == 'addition') ? 'selected' : ''; ?>>Additions</option>
                                    <option value="release" <?php echo ($transaction_type == 'release') ? 'selected' : ''; ?>>Releases</option>
                                </select>
                            </div>
                            <div class="col-md-2">
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
                            <div class="col-md-2">
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
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">Date Range</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From">
                                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To">
                                </div>
                            </div>
                             <div class="col-2">
                                <a href="release_history.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-clockwise"></i> Clear All Filters
                                </a>
                            </div>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
        
        <!-- History Table -->
        <div class="table-container">
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Release History</h5>
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
                            <th>Type</th>
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
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transaction_history)): ?>
                            <?php foreach ($transaction_history as $transaction): ?>
                                <tr>
                                    <td>
                                        <?php if ($transaction['transaction_type'] === 'addition'): ?>
                                            <span class="badge bg-success">Addition</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Release</span>
                                        <?php endif; ?>
                                    </td>
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
                                    <td><small><?php echo htmlspecialchars($transaction['notes'] ?: 'No notes'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
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
        // Auto-search functionality for filters
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-search for all filter inputs (selects and date inputs)
            const filterSelects = document.querySelectorAll('#filterForm select');
            const filterDates = document.querySelectorAll('#filterForm input[type="date"]');
            
            // Add change event listeners to all select elements
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    console.log('Filter changed:', this.name, this.value);
                    performAutoSearch();
                });
            });
            
            // Add change event listeners to all date inputs
            filterDates.forEach(input => {
                input.addEventListener('change', function() {
                    console.log('Date filter changed:', this.name, this.value);
                    performAutoSearch();
                });
            });
            
            // Function to perform auto-search
            function performAutoSearch() {
                const form = document.getElementById('filterForm');
                const formData = new FormData(form);
                const currentUrl = new URL(window.location);
                
                console.log('Performing auto-search with form data:', Object.fromEntries(formData));
                
                // Clear all existing filter parameters first
                currentUrl.searchParams.delete('transaction_type');
                currentUrl.searchParams.delete('from_office');
                currentUrl.searchParams.delete('to_office');
                currentUrl.searchParams.delete('date_from');
                currentUrl.searchParams.delete('date_to');
                // Keep search parameter if it exists from table search
                
                // Add non-empty filter parameters
                if (formData.get('transaction_type')) {
                    currentUrl.searchParams.set('transaction_type', formData.get('transaction_type'));
                }
                if (formData.get('from_office')) {
                    currentUrl.searchParams.set('from_office', formData.get('from_office'));
                }
                if (formData.get('to_office')) {
                    currentUrl.searchParams.set('to_office', formData.get('to_office'));
                }
                if (formData.get('date_from')) {
                    currentUrl.searchParams.set('date_from', formData.get('date_from'));
                }
                if (formData.get('date_to')) {
                    currentUrl.searchParams.set('date_to', formData.get('date_to'));
                }
                
                console.log('Navigating to:', currentUrl.toString());
                
                // Navigate to new URL
                window.location.href = currentUrl.toString();
            }
            
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

        function exportReleaseHistory() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            
            // Create CSV content
            let csvContent = "Type,Date,Description,Quantity,Units,Unit Cost,Total Value,From Office,To Office,Released By,Received By,Notes\n";
            
            <?php if (!empty($transaction_history)): ?>
                <?php foreach ($transaction_history as $transaction): ?>
                    csvContent += "<?php echo ucfirst($transaction['transaction_type']); ?>","<?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?>","<?php echo addslashes($transaction['description']); ?>","<?php echo $transaction['quantity']; ?>","<?php echo addslashes($transaction['units'] ?: 'N/A'); ?>","<?php echo $transaction['unit_cost']; ?>","<?php echo $transaction['total_value']; ?>","<?php echo addslashes($transaction['from_office_name'] ?: 'N/A'); ?>","<?php echo addslashes($transaction['to_office_name'] ?: 'N/A'); ?>","<?php echo addslashes($transaction['released_by_name'] ?: 'System'); ?>","<?php echo addslashes($transaction['received_by'] ?: 'Not specified'); ?>","<?php echo addslashes($transaction['notes'] ?: 'No notes'); ?>"\n";
                <?php endforeach; ?>
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
