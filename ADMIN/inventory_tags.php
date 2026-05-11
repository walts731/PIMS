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

// Log inventory tags page access
logSystemAction($_SESSION['user_id'], 'access', 'inventory_tags', 'Admin accessed inventory tags page');

// Handle filter
$office_filter = intval($_GET['office'] ?? 0);
$category_filter = intval($_GET['category'] ?? 0);

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

// Always filter for serviceable items only
$where_conditions[] = "ai.status = 'serviceable'";

// Only show items that have property numbers (required for inventory tags)
$where_conditions[] = "ai.property_no IS NOT NULL AND ai.property_no != ''";


if ($office_filter > 0) {
    $where_conditions[] = "ai.office_id = ?";
    $params[] = $office_filter;
    $types .= 'i';
}

if ($category_filter > 0) {
    $where_conditions[] = "COALESCE(ai.category_id, a.asset_categories_id) = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get inventory tags
$tags = [];
try {
    $sql = "SELECT ai.id, ai.inventory_tag, ai.property_no, ai.model, ai.serial_number, ai.description, ai.status, ai.date_counted, ai.image, ai.qr_code,
                   a.description as asset_description, a.unit_cost,
                   ac.category_name, ac.category_code, ac.id as category_id,
                   o.office_name, o.id as office_id,
                   e.employee_no, e.firstname, e.lastname,
                   ai.created_at
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON COALESCE(ai.category_id, a.asset_categories_id) = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN employees e ON ai.employee_id = e.id 
            $where_clause
            ORDER BY ai.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
        
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    $stmt->close();
    
    // Filter out unwanted categories after fetching
    $excluded_categories = ['LND', 'Land Imp', 'RN', 'OInfra', 'Buildings', 'School bldg', 'HHC', 'MKT', 'SLH', 'Ostruct', 'PP&MUN', 'P&T'];
    $filtered_tags = [];
    foreach ($tags as $tag) {
        $category_name = $tag['category_name'] ?? '';
        
        // Skip if category name is in excluded list or if no category is assigned
        if (in_array($category_name, $excluded_categories) || empty($category_name)) {
            continue;
        }
        
        $filtered_tags[] = $tag;
    }
    $tags = $filtered_tags;
    
} catch (Exception $e) {
    error_log("Error fetching tags: " . $e->getMessage());
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

// Get categories for filter
$categories = [];
try {
    $result = $conn->query("SELECT id, category_name, category_code FROM asset_categories WHERE status = 'active' ORDER BY category_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
            // Debug: Log all available categories
            error_log("Available category: " . ($row['category_code'] ?? 'NULL') . " - " . ($row['category_name'] ?? 'NULL'));
        }
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Tags - PIMS</title>
    
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
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php $page_title = 'Inventory Tags'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-qr-code"></i> Inventory Tags
                    </h1>
                    <p class="text-muted mb-0">View and print inventory tags for all serviceable assets with property numbers</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-primary btn-sm" onclick="printSelectedTags()">
                            <i class="bi bi-check2"></i> Print Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        
        
        <div class="section-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0">
                    <i class="bi bi-qr-code"></i> Inventory Tags
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm btn-action" onclick="selectAllTags()">
                        <i class="bi bi-check-all"></i> Select All
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-action" onclick="clearSelection()">
                        <i class="bi bi-x-square"></i> Clear Selection
                    </button>
                </div>
            </div>
            <?php if (!empty($tags)): ?>
                <form id="tagsForm">
                    <div class="table-responsive">
                        <table class="table table-hover" id="inventoryTagsTable">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                    </th>
                                    <th>Property No</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Office</th>
                                    <th style="display: none;">Office ID</th>
                                    <th style="display: none;">Category ID</th>
                                    <th>Person Accountable</th>
                                    <th>Status</th>
                                    <th width="80" class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_tags[]" value="<?php echo $tag['id']; ?>" class="tag-checkbox">
                                        </td>
                                        <td><?php echo htmlspecialchars($tag['property_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($tag['description']); ?>
                                        </td>
                                        <td>
                                            <span class="category-badge">
                                                <?php echo htmlspecialchars($tag['category_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($tag['office_name'] ?? 'N/A'); ?></td>
                                        <td style="display: none;"><?php echo $tag['office_id'] ?? ''; ?></td>
                                        <td style="display: none;"><?php echo $tag['category_id'] ?? ''; ?></td>
                                        <td>
                                            <?php if ($tag['firstname'] && $tag['lastname']): ?>
                                                <?php echo htmlspecialchars($tag['firstname'] . ' ' . $tag['lastname']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($tag['status']) {
                                                case 'serviceable':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'unserviceable':
                                                    $status_class = 'bg-danger';
                                                    break;
                                                case 'in_use':
                                                    $status_class = 'bg-primary';
                                                    break;
                                                case 'available':
                                                    $status_class = 'bg-secondary';
                                                    break;
                                                default:
                                                    $status_class = 'bg-warning';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst(htmlspecialchars($tag['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="no-print">
                                            <button type="button" class="btn btn-sm btn-primary btn-action" onclick="printTag(<?php echo $tag['id']; ?>)">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-tags"></i>
                    <h5>No Inventory Tags Found</h5>
                    <p class="text-muted">
                        <?php if ($office_filter > 0 || $category_filter > 0): ?>
                            No serviceable assets with property numbers match your filter criteria. Try adjusting your filters.
                        <?php else: ?>
                            No serviceable assets with property numbers found. Assets need property numbers to generate inventory tags.
                        <?php endif; ?>
                    </p>
                    <?php if ($office_filter > 0 || $category_filter > 0): ?>
                        <a href="inventory_tags.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-clockwise"></i>
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="QR Code" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <!-- Selection Alert Modal -->
    <div class="modal fade" id="selectionAlertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> No Selection</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-check-square fs-1 text-warning mb-3"></i>
                    <h6>Please select at least one tag to print.</h6>
                    <p class="text-muted mb-0">Choose one or more inventory tags from the list before clicking the Print Selected button.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#inventoryTagsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[1, 'asc']], // Sort by Property No ascending by default
                columnDefs: [
                    { 
                        targets: 0, // Checkbox column
                        orderable: false,
                        searchable: false,
                        className: 'no-print'
                    },
                    { 
                        targets: 5, // Hidden Office ID column
                        visible: false,
                        searchable: true
                    },
                    { 
                        targets: 6, // Hidden Category ID column
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
                dom: '<"row"<"col-md-3"l><"col-md-3 office-filter-container"><"col-md-3 category-filter-container"><"col-md-3"f>><"row"<"col-12"rt>><"row"<"col-md-6"i><"col-md-6"p>>',
                language: {
                    search: "Search inventory tags:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ inventory tags",
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
            
            // Add category filter to DataTables
            $('.category-filter-container').html(`
                <select id="categoryFilter" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            `);
            
            // Add office and category filters to DataTables search
            $('#officeFilter, #categoryFilter').on('change', function() {
                var officeId = $('#officeFilter').val();
                var categoryId = $('#categoryFilter').val();
                
                // Clear all filters first
                table.column(5).search('').draw(); // Office ID column
                table.column(6).search('').draw(); // Category ID column
                
                // Apply office filter
                if (officeId) {
                    table.column(5).search(officeId).draw();
                }
                
                // Apply category filter
                if (categoryId) {
                    table.column(6).search(categoryId).draw();
                }
                
                // If both filters are cleared, redraw table
                if (!officeId && !categoryId) {
                    table.draw();
                }
            });
            
            // Apply initial filters if set
            <?php if ($office_filter > 0): ?>
                table.column(5).search('<?php echo $office_filter; ?>').draw();
            <?php endif; ?>
            
            <?php if ($category_filter > 0): ?>
                table.column(6).search('<?php echo $category_filter; ?>').draw();
            <?php endif; ?>
        });

        // Toggle all checkboxes
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.tag-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Select all tags
        function selectAllTags() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.tag-checkbox');
            selectAll.checked = true;
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        // Clear selection
        function clearSelection() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.tag-checkbox');
            selectAll.checked = false;
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        // Show image modal
        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        // Print single tag - open in new tab (working version)
        function printTag(tagId) {
            console.log('printTag called with tagId:', tagId);
            window.open('print_inventory_tag.php?id=' + tagId, '_blank');
        }

        // Print selected tags - open in new tab (working version)
        function printSelectedTags() {
            const checkboxes = document.querySelectorAll('.tag-checkbox:checked');
            if (checkboxes.length === 0) {
                // Show modal instead of alert
                const modal = new bootstrap.Modal(document.getElementById('selectionAlertModal'));
                modal.show();
                return;
            }

            const tagIds = Array.from(checkboxes).map(cb => cb.value).join(',');
            console.log('Printing selected tags:', tagIds);
            window.open('print_inventory_tags.php?ids=' + tagIds, '_blank');
        }
    </script>
    </div>
</body>
</html>
