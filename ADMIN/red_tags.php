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
    `asset_item_id` int(11) DEFAULT NULL,
    `disposal_reason` text DEFAULT NULL,
    `disposal_date` date DEFAULT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `control_no` (`control_no`),
    UNIQUE KEY `red_tag_no` (`red_tag_no`),
    KEY `office_id` (`office_id`),
    KEY `asset_item_id` (`asset_item_id`),
    KEY `updated_by` (`updated_by`),
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
    // Simple query with component information
    $sql = "SELECT rt.*, 
                   adc.monitor_status, adc.ups_status, adc.monitor_name, adc.ups_name
            FROM red_tags rt 
            LEFT JOIN asset_desktop_computers adc ON rt.asset_item_id = adc.asset_item_id
            ORDER BY rt.created_at DESC";
    
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
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Red Tags'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-tag"></i> Red Tags
                    </h1>
                    <p class="text-muted mb-0">View and manage all 5S red tags in the system</p>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-check-circle-fill"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="no-print">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" class="dropdown-item" onclick="printSelectedTags()">
                                        <i class="bi bi-printer"></i> Print Selected
                                    </button>
                                </li>
                                <li>
                                    <a href="unserviceable_assets.php" class="dropdown-item">
                                        <i class="bi bi-x-circle"></i> Unserviceable Assets
                                    </a>
                                </li>
                                <li>
                                    <a href="assets.php" class="dropdown-item">
                                        <i class="bi bi-box"></i> View All Assets
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item" onclick="location.reload()">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['total_red_tags'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-tag"></i> Total Red Tags</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo number_format($stats['offices_with_tags'] ?? 0); ?></div>
                    <div class="stats-label"><i class="bi bi-building"></i> Offices with Tags</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($red_tags); ?></div>
                    <div class="stats-label"><i class="bi bi-list-check"></i> Current Results</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo date('M Y'); ?></div>
                    <div class="stats-label"><i class="bi bi-calendar"></i> Current Period</div>
                </div>
            </div>
        </div>

        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-funnel"></i> Search & Filters
            </div>
            <form id="filterForm" class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="searchInput" class="form-control" placeholder="Search red tags..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="office" id="officeFilter" class="form-select">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" id="dateFromFilter" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" id="dateToFilter" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                </div>
            </form>
        </div>

        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-tag"></i> Red Tags Management
            </div>
            
            <?php if (empty($red_tags)): ?>
                <div class="empty-state">
                    <i class="bi bi-tag"></i>
                    <h4>No Red Tags Found</h4>
                    <p class="text-muted">No red tags match your search criteria.</p>
                    <a href="unserviceable_assets.php" class="btn btn-danger">
                        <i class="bi bi-plus-circle"></i> Create First Red Tag
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40" class="no-print">
                                    <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                </th>
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
                                    <td class="no-print">
                                        <input type="checkbox" name="selected_tags[]" value="<?php echo $red_tag['id']; ?>" class="tag-checkbox">
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($red_tag['control_no']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($red_tag['red_tag_no']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($red_tag['date_received'])); ?></td>
                                    <td>
                                        <?php 
                                        // Display component information if available
                                        $description = $red_tag['item_description'];
                                        if (isset($red_tag['component_type']) && $red_tag['component_type'] !== 'main_asset') {
                                            if ($red_tag['component_type'] === 'monitor' && !empty($red_tag['monitor_name'])) {
                                                $description .= ' | Monitor: ' . $red_tag['monitor_name'];
                                            } elseif ($red_tag['component_type'] === 'ups' && !empty($red_tag['ups_name'])) {
                                                $description .= ' | UPS: ' . $red_tag['ups_name'];
                                            }
                                        }
                                        echo htmlspecialchars(substr($description, 0, 50));
                                        if (strlen($description) > 50): ?>...<?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($red_tag['item_location']); ?></td>
                                    <td><?php echo htmlspecialchars($red_tag['action']); ?></td>
                                    <td><?php echo htmlspecialchars($red_tag['tagged_by']); ?></td>
                                    <td class="no-print">
                                        <div class="btn-group" role="group">
                                            <?php if (!empty($red_tag['asset_item_id'])): ?>
                                                <a href="view_asset_item.php?id=<?php echo $red_tag['asset_item_id']; ?>" class="btn btn-outline-primary btn-sm" title="View Asset Item">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="print_redtag.php?control_no=<?php echo urlencode($red_tag['control_no']); ?>" class="btn btn-outline-danger btn-sm" title="Print Red Tag" target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <?php if (strtolower($red_tag['action']) === 'disposal' || strtolower($red_tag['action']) === 'dispose'): ?>
                                                <button type="button" class="btn btn-warning btn-sm" title="Dispose Item" data-bs-toggle="modal" data-bs-target="#disposeModal" 
                                                        onclick="setDisposalData(<?php echo $red_tag['id']; ?>, '<?php echo htmlspecialchars($red_tag['control_no']); ?>', '<?php echo htmlspecialchars($red_tag['item_description']); ?>')">
                                                    <i class="bi bi-trash"></i> Dispose
                                                </button>
                                            <?php endif; ?>
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
    
    <!-- Disposal Confirmation Modal -->
    <div class="modal fade" id="disposeModal" tabindex="-1" aria-labelledby="disposeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="disposeModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning"></i> Confirm Disposal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="disposeForm" method="POST" action="process_disposal.php">
                        <input type="hidden" name="red_tag_id" id="disposeRedTagId">
                        <input type="hidden" name="action" value="dispose">
                        <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action cannot be undone. The item will be marked as disposed and removed from active inventory.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Control No:</strong></label>
                            <p class="form-control-plaintext" id="disposeControlNo"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Item Description:</strong></label>
                            <p class="form-control-plaintext" id="disposeDescription"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="disposalReason" class="form-label"><strong>Disposal Reason:</strong></label>
                            <textarea class="form-control" id="disposalReason" name="disposal_reason" rows="3" 
                                      placeholder="Enter reason for disposal..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="disposalDate" class="form-label"><strong>Disposal Date:</strong></label>
                            <input type="date" class="form-control" id="disposalDate" name="disposal_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-warning" onclick="confirmDisposal()">
                        <i class="bi bi-trash"></i> Confirm Disposal
                    </button>
                </div>
            </div>
        </div>
    </div>
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
            
            // Toggle all checkboxes
            window.toggleAllCheckboxes = function() {
                const selectAll = document.getElementById('selectAll');
                const checkboxes = document.querySelectorAll('.tag-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
            };
            
            // Print selected tags - open in new tab
            window.printSelectedTags = function() {
                const checkboxes = document.querySelectorAll('.tag-checkbox:checked');
                if (checkboxes.length === 0) {
                    alert('Please select at least one red tag to print.');
                    return;
                }

                const tagIds = Array.from(checkboxes).map(cb => cb.value).join(',');
                console.log('Printing selected tags:', tagIds);
                window.open('print_redtags.php?ids=' + tagIds, '_blank');
            };
            
            // Set disposal data in modal
            window.setDisposalData = function(redTagId, controlNo, description) {
                document.getElementById('disposeRedTagId').value = redTagId;
                document.getElementById('disposeControlNo').textContent = controlNo;
                document.getElementById('disposeDescription').textContent = description;
                
                // Reset form fields
                document.getElementById('disposalReason').value = '';
                document.getElementById('disposalDate').value = new Date().toISOString().split('T')[0];
            };
            
            // Confirm disposal and submit form
            window.confirmDisposal = function() {
                const reason = document.getElementById('disposalReason').value.trim();
                const date = document.getElementById('disposalDate').value;
                
                if (!reason) {
                    alert('Please enter a disposal reason.');
                    return;
                }
                
                if (!date) {
                    alert('Please select a disposal date.');
                    return;
                }
                
                // Submit the form
                document.getElementById('disposeForm').submit();
            };
        });
    </script>
</body>
</html>
