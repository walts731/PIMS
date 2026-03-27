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

// Handle filter
$office_filter = intval($_GET['office'] ?? 0);

// Build WHERE clause for office filter only
$where_conditions = [];
$params = [];
$types = '';

if ($office_filter > 0) {
    $where_conditions[] = "rt.office_id = ?";
    $params[] = $office_filter;
    $types .= 'i';
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

// Get red tags with office information
$red_tags = [];
try {
    // Query with office information and component information
    $sql = "SELECT rt.*, 
                   o.office_name,
                   adc.monitor_status, adc.ups_status, adc.monitor_name, adc.ups_name
            FROM red_tags rt 
            LEFT JOIN offices o ON rt.office_id = o.id
            LEFT JOIN asset_desktop_computers adc ON rt.asset_item_id = adc.asset_item_id
            $where_clause
            ORDER BY rt.created_at DESC";
    
    // Debug: Log the SQL query
    error_log("Red Tags SQL: " . $sql);
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
                    <table class="table table-hover" id="redTagsTable">
                        <thead>
                            <tr>
                                <th width="40" class="no-print">
                                    <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                </th>
                                <th>Control No</th>
                                <th>Date Received</th>
                                <th>Item Description</th>
                                <th>Action</th>
                                <th>Office</th>
                                <th style="display: none;">Office ID</th>
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
                                    <td>
                                        <?php
                                        $action = strtolower($red_tag['action']);
                                        $badge_class = '';
                                        switch ($action) {
                                            case 'disposal':
                                            case 'dispose':
                                                $badge_class = 'bg-danger';
                                                break;
                                            case 'disposed':
                                                $badge_class = 'bg-dark';
                                                break;
                                            case 'repair':
                                                $badge_class = 'bg-warning';
                                                break;
                                            case 'recondition':
                                                $badge_class = 'bg-info';
                                                break;
                                            case 'transfer':
                                                $badge_class = 'bg-primary';
                                                break;
                                            case 'replacement':
                                                $badge_class = 'bg-secondary';
                                                break;
                                            default:
                                                $badge_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($red_tag['action'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($red_tag['office_name']) ? htmlspecialchars($red_tag['office_name']) : 'Not Assigned'; ?></td>
                                    <td style="display: none;"><?php echo $red_tag['office_id']; ?></td>
                                    <td class="no-print">
                                        <div class="btn-group" role="group">
                                            <?php if (!empty($red_tag['asset_item_id'])): ?>
                                                <a href="view_asset_item.php?id=<?php echo $red_tag['asset_item_id']; ?>" class="btn btn-outline-primary btn-sm" title="View Asset Item">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (strtolower($red_tag['action']) !== 'disposed'): ?>
                                                <a href="print_redtag.php?id=<?php echo urlencode($red_tag['id']); ?>" class="btn btn-outline-danger btn-sm" title="Print Red Tag" target="_blank">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (strtolower($red_tag['action']) === 'disposal' || strtolower($red_tag['action']) === 'dispose'): ?>
                                                <button type="button" class="btn btn-outline-warning btn-sm" title="Dispose Item" data-bs-toggle="modal" data-bs-target="#disposeModal" 
                                                        onclick="setDisposalData(<?php echo $red_tag['id']; ?>, '<?php echo htmlspecialchars($red_tag['control_no']); ?>', '<?php echo htmlspecialchars($red_tag['item_description']); ?>')">
                                                    <i class="bi bi-trash"></i>
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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#redTagsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[1, 'desc']], // Sort by Control No descending by default
                columnDefs: [
                    { 
                        targets: 0, // Checkbox column
                        orderable: false,
                        searchable: false,
                        className: 'no-print'
                    },
                    { 
                        targets: 6, // Hidden Office ID column
                        visible: false,
                        searchable: true
                    },
                    { 
                        targets: -1, // Actions column
                        orderable: false,
                        searchable: false,
                        className: 'no-print'
                    }
                ],
                dom: '<"row"<"col-md-3"l><"col-md-3 office-filter-container"><"col-md-6"f>><"row"<"col-12"rt>><"row"<"col-md-6"i><"col-md-6"p>>',
                language: {
                    search: "Search red tags:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ red tags",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
            
            // Add office filter to DataTables
            $('.office-filter-container').html(`
                <select id="officeFilter" class="form-select form-select-sm">
                    <option value="">All Offices</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?php echo $office['id']; ?>" <?php echo $office_filter == $office['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($office['office_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            `);
            
            // Office filter functionality
            $('#officeFilter').on('change', function() {
                var officeId = $(this).val();
                table.column(6).search(officeId).draw(); // Search in the hidden Office ID column (index 6)
            });
            
            // Apply initial filter if set
            <?php if ($office_filter > 0): ?>
                table.column(6).search('<?php echo $office_filter; ?>').draw();
            <?php endif; ?>
            
            // Toggle all checkboxes
            window.toggleAllCheckboxes = function() {
                var selectAll = document.getElementById('selectAll');
                var checkboxes = document.querySelectorAll('.tag-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            };
            
            // Print selected tags
            window.printSelectedTags = function() {
                var checkboxes = document.querySelectorAll('.tag-checkbox:checked');
                if (checkboxes.length === 0) {
                    var modal = new bootstrap.Modal(document.getElementById('noSelectionModal'));
                    modal.show();
                    return;
                }

                var tagIds = Array.from(checkboxes).map(function(cb) { return cb.value; }).join(',');
                console.log('Printing selected tags:', tagIds);
                window.open('print_redtags.php?ids=' + tagIds, '_blank');
            };
            
            // Clear filters function
            window.clearFilters = function() {
                window.location.href = window.location.pathname;
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
                var reason = document.getElementById('disposalReason').value.trim();
                var date = document.getElementById('disposalDate').value;
                
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
    
    <!-- No Selection Modal -->
    <div class="modal fade" id="noSelectionModal" tabindex="-1" aria-labelledby="noSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noSelectionModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning"></i> No Selection
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        <i class="bi bi-info-circle text-info"></i>
                        Please select at least one red tag to print.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
