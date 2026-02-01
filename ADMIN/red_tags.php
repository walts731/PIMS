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

// Log red tags page access
logSystemAction($_SESSION['user_id'], 'access', 'red_tags', 'Admin accessed red tags page');

// Handle search and filter
$search = trim($_GET['search'] ?? '');
$office_filter = intval($_GET['office'] ?? 0);
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(rt.control_no LIKE ? OR rt.red_tag_no LIKE ? OR rt.item_description LIKE ? OR rt.tagged_by LIKE ? OR rt.item_location LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= 'sssss';
}

if ($office_filter > 0) {
    $where_conditions[] = "rt.office_id = ?";
    $params[] = $office_filter;
    $types .= 'i';
}

if (!empty($date_from)) {
    $where_conditions[] = "rt.date_received >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "rt.date_received <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Create red_tags table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `red_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `control_no` varchar(50) NOT NULL,
    `red_tag_no` varchar(50) NOT NULL,
    `date_received` date NOT NULL,
    `tagged_by` varchar(100) NOT NULL,
    `item_location` varchar(255) NOT NULL,
    `item_description` text NOT NULL,
    `removal_reason` text NOT NULL,
    `action` varchar(50) NOT NULL,
    `office_id` int(11) DEFAULT NULL,
    `asset_id` int(11) DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `control_no` (`control_no`),
    UNIQUE KEY `red_tag_no` (`red_tag_no`),
    KEY `office_id` (`office_id`),
    KEY `asset_id` (`asset_id`),
    KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($create_table_sql);

// Check if status column exists and remove it if it does
$column_check = $conn->query("SHOW COLUMNS FROM red_tags LIKE 'status'");
if ($column_check && $column_check->num_rows > 0) {
    // Remove the status column
    $conn->query("ALTER TABLE red_tags DROP COLUMN status");
    error_log("Removed status column from red_tags table");
}

// Check if table exists and has data
$table_check = $conn->query("SHOW TABLES LIKE 'red_tags'");
$table_exists = $table_check && $table_check->num_rows > 0;

$data_count = 0;
if ($table_exists) {
    $count_result = $conn->query("SELECT COUNT(*) as count FROM red_tags");
    if ($count_result) {
        $row = $count_result->fetch_assoc();
        $data_count = $row['count'];
    }
}

// Debug: Log table status
error_log("Red Tags table exists: " . ($table_exists ? 'Yes' : 'No'));
error_log("Red Tags table has data: " . $data_count . " rows");

// Get red tags
$red_tags = [];
try {
    // Simple query without JOINs to avoid foreign key issues
    $sql = "SELECT * FROM red_tags rt ORDER BY rt.created_at DESC";
    
    // Debug: Log the SQL query
    error_log("Red Tags SQL: " . $sql);
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $red_tags[] = $row;
        }
    }
    $stmt->close();
    
    // Debug: Log the number of red tags found
    error_log("Red Tags found: " . count($red_tags));
    
} catch (Exception $e) {
    error_log("Error fetching red tags: " . $e->getMessage());
}

// Get offices for filter
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

// Get statistics
$stats = [];
try {
    $stats_sql = "SELECT 
                COUNT(*) as total_red_tags,
                COUNT(DISTINCT rt.office_id) as offices_with_tags
              FROM red_tags rt";
    $result = $conn->query($stats_sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Tags - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #dc3545;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #dc3545 0%, #f8d7da 100%);
            color: #721c24;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
        }
        
        .stats-number {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            word-wrap: break-word;
            line-height: 1.2;
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
        }
        
        .filter-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 2.5rem;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
        }
        
        .search-box input:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .table-container {
                box-shadow: none;
            }
            
            body {
                background: white;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .btn-custom {
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Red Tags';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-tag"></i> Red Tags
                    </h1>
                    <p class="text-muted mb-0">View and manage all 5S red tags in the system</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="create_redtag.php" class="btn btn-danger btn-custom">
                        <i class="bi bi-plus-circle"></i> Create Red Tag
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 no-print">
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_red_tags'] ?? 0); ?></div>
                    <div class="stats-label">Total Red Tags</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['offices_with_tags'] ?? 0); ?></div>
                    <div class="stats-label">Offices with Tags</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo date('M Y'); ?></div>
                    <div class="stats-label">Current Period</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section no-print">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search red tags..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="officeFilter">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="dateFromFilter" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="dateToFilter" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Red Tags Table -->
        <div class="table-container">
            <?php if (empty($red_tags)): ?>
                <div class="empty-state">
                    <i class="bi bi-tag"></i>
                    <h4>No Red Tags Found</h4>
                    <p class="text-muted">No red tags match your search criteria.</p>
                    <a href="create_redtag.php" class="btn btn-danger">
                        <i class="bi bi-plus-circle"></i> Create First Red Tag
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Control No</th>
                                <th>Red Tag No</th>
                                <th>Date Received</th>
                                <th>Item Description</th>
                                <th>Location</th>
                                <th>Action</th>
                                <th>Tagged By</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($red_tags as $red_tag): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($red_tag['control_no']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($red_tag['red_tag_no']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($red_tag['date_received'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($red_tag['item_description'], 0, 50)); ?>
                                        <?php if (strlen($red_tag['item_description']) > 50): ?>...<?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($red_tag['item_location']); ?></td>
                                    <td><?php echo htmlspecialchars($red_tag['action']); ?></td>
                                    <td><?php echo htmlspecialchars($red_tag['tagged_by']); ?></td>
                                    <td class="no-print">
                                        <div class="btn-group" role="group">
                                            <a href="create_redtag.php?control_no=<?php echo urlencode($red_tag['control_no']); ?>" class="btn btn-outline-danger btn-sm" title="Print Red Tag">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-filter functionality using vanilla JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded and ready');
            
            // Office filter change
            const officeFilter = document.getElementById('officeFilter');
            if (officeFilter) {
                officeFilter.addEventListener('change', function() {
                    console.log('Office filter changed to:', this.value);
                    updateFilters();
                });
            }
            
            // Date from filter change
            const dateFromFilter = document.getElementById('dateFromFilter');
            if (dateFromFilter) {
                dateFromFilter.addEventListener('change', function() {
                    console.log('Date from filter changed to:', this.value);
                    updateFilters();
                });
            }
            
            // Date to filter change
            const dateToFilter = document.getElementById('dateToFilter');
            if (dateToFilter) {
                dateToFilter.addEventListener('change', function() {
                    console.log('Date to filter changed to:', this.value);
                    updateFilters();
                });
            }
            
            // Search input with debouncing
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    console.log('Search input changed to:', this.value);
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        console.log('Executing search for:', searchInput.value.trim());
                        updateFilters();
                    }, 500); // Wait 500ms after user stops typing
                });
            }
            
            // Function to update filters
            function updateFilters() {
                const searchValue = searchInput ? searchInput.value.trim() : '';
                const officeValue = officeFilter ? officeFilter.value : '';
                const dateFromValue = dateFromFilter ? dateFromFilter.value : '';
                const dateToValue = dateToFilter ? dateToFilter.value : '';
                
                // Build URL with parameters
                let url = 'red_tags.php';
                const params = [];
                
                if (searchValue) params.push('search=' + encodeURIComponent(searchValue));
                if (officeValue) params.push('office=' + encodeURIComponent(officeValue));
                if (dateFromValue) params.push('date_from=' + encodeURIComponent(dateFromValue));
                if (dateToValue) params.push('date_to=' + encodeURIComponent(dateToValue));
                
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                
                console.log('Redirecting to:', url);
                window.location.href = url;
            }
            
            // Clear filters function
            window.clearFilters = function() {
                console.log('Clearing filters');
                window.location.href = 'red_tags.php';
            };
        });
    </script>
</body>
</html>
