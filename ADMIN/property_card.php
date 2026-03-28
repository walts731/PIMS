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

// Log property card access
logSystemAction($_SESSION['user_id'], 'access', 'property_card', 'User accessed Property Card page');

// Get available categories for filter
$categories = [];
if ($conn && !$conn->connect_error) {
    $result = $conn->query("SELECT id, category_name, category_code FROM asset_categories WHERE status = 'active' ORDER BY category_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}

// Get available offices for filter
$offices = [];
if ($conn && !$conn->connect_error) {
    $result = $conn->query("SELECT id, office_name, office_code FROM offices WHERE status = 'active' ORDER BY office_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
}

// Get filter parameters
$selected_category = $_GET['category'] ?? '';
$selected_office = $_GET['office'] ?? '';

// Get asset items with PAR ID and filters
$asset_items = [];
if ($conn && !$conn->connect_error) {
    try {
        // Optimized query with JOINs to avoid N+1 queries
        $query = "SELECT 
                    ai.id,
                    ai.created_at,
                    ai.property_no,
                    ai.description,
                    ai.value,
                    ai.par_id,
                    ai.employee_id,
                    ai.office_id,
                    COALESCE(ac.category_name, 'Uncategorized') as asset_category,
                    COALESCE(ac.category_code, 'UNCAT') as asset_category_code,
                    COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                    COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code,
                    CONCAT(COALESCE(e.firstname, ''), ' ', COALESCE(e.lastname, '')) as employee_name,
                    e.employee_no,
                    pf.par_no,
                    pf.received_by_name
                  FROM asset_items ai
                  LEFT JOIN asset_categories ac ON ai.category_id = ac.id
                  LEFT JOIN offices o1 ON ai.office_id = o1.id
                  LEFT JOIN employees e ON ai.employee_id = e.id
                  LEFT JOIN offices o2 ON e.office_id = o2.id
                  LEFT JOIN par_forms pf ON ai.par_id = pf.id
                  WHERE ai.par_id IS NOT NULL AND ai.par_id != ''";
        
        // Add category filter
        if (!empty($selected_category)) {
            $query .= " AND ac.category_code = '" . $conn->real_escape_string($selected_category) . "'";
        }
        
        // Add office filter
        if (!empty($selected_office)) {
            $query .= " AND (o1.office_code = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_code = '" . $conn->real_escape_string($selected_office) . "')";
        }
        
        $query .= " ORDER BY ai.created_at ASC";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Clean up employee name if empty
                if (empty(trim($row['employee_name']))) {
                    $row['employee_name'] = '';
                    $row['employee_no'] = '';
                }
                
                // Clean up received_by_name if empty
                if (empty($row['received_by_name'])) {
                    $row['received_by'] = '';
                } else {
                    $row['received_by'] = $row['received_by_name'];
                }
                
                $asset_items[] = $row;
            }
        }
    } catch (Exception $e) {
        // Error handling - could add user feedback here
        error_log("Property Card Query Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Card - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Property Card'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2" style="font-weight: 700; color: var(--primary-color);">
                        <i class="bi bi-credit-card me-2"></i>Property Card
                    </h1>
                    <p class="text-muted mb-0">View all asset items with Property Acknowledgment Receipt (PAR) references</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="showSummary()" data-bs-toggle="tooltip" title="View Summary">
                                    <i class="bi bi-list-ul"></i> Summary
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="exportToCSV()" data-bs-toggle="tooltip" title="Export to CSV">
                                    <i class="bi bi-download"></i> Export CSV
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="exportToPDF()" data-bs-toggle="tooltip" title="Export to PDF">
                                    <i class="bi bi-file-pdf"></i> Export PDF
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
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
        
        <?php if (empty($asset_items)): ?>
            <div class="property-card-table">
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #adb5bd;"></i>
                    <h4 class="mt-3 text-muted">No Property Items Found</h4>
                    <p class="text-muted">There are no asset items with PAR references in the system.</p>
                    <a href="par_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Create PAR Form
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($asset_items); ?></div>
                        <div class="stats-label"><i class="bi bi-box"></i> Total Items</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number">₱<?php echo number_format(array_sum(array_column($asset_items, 'value')), 2); ?></div>
                        <div class="stats-label"><i class="bi bi-currency-dollar"></i> Total Value</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count(array_unique(array_column($asset_items, 'par_id'))); ?></div>
                        <div class="stats-label"><i class="bi bi-file-text"></i> PAR Forms</div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="table-container mb-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <label for="categoryFilter" class="form-label mb-0 fw-semibold">
                                <i class="bi bi-tags me-1"></i>Category
                            </label>
                            <select class="form-select form-select-sm" style="width: auto;" id="categoryFilter" name="category" onchange="autoFilter()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['category_code']); ?>" 
                                            <?php echo $selected_category === $category['category_code'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <label for="officeFilter" class="form-label mb-0 fw-semibold">
                                <i class="bi bi-building me-1"></i>Office
                            </label>
                            <select class="form-select form-select-sm" style="width: auto;" id="officeFilter" name="office" onchange="autoFilter()">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo htmlspecialchars($office['office_code']); ?>" 
                                            <?php echo $selected_office === $office['office_code'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                            <i class="bi bi-x-circle me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover" id="propertyCardTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Property No.</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Office</th>
                                <th>Employee</th>
                                <th>Value</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $item_counter = 1;
                            foreach ($asset_items as $index => $item): 
                            ?>
                                <tr>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="property-no"><?php echo htmlspecialchars($item['property_no']); ?></span>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?php echo htmlspecialchars($item['asset_category_code']); ?></span>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['asset_category']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($item['description']); ?>
                                    </td>
                                    <td>
                                        <span class="office-code-only"><?php echo htmlspecialchars($item['office_code']); ?></span>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['office_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($item['employee_name']): ?>
                                            <?php echo htmlspecialchars($item['employee_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($item['employee_no']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>₱<?php echo number_format($item['value'], 2); ?></strong>
                                    </td>
                                    <td class="no-print">
                                        <div class="btn-group" role="group">
                                            <a href="view_asset_item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Asset Item">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $item_counter++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Initialize DataTable and tooltips
        $(document).ready(function() {
            $('#propertyCardTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'desc']], // Sort by date column (first column) descending
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
        
        function exportToCSV() {
            // Get current filter parameters
            const category = document.getElementById('categoryFilter').value;
            const office = document.getElementById('officeFilter').value;
            
            // Build URL with filter parameters
            let url = 'export_property_card_csv.php';
            const params = new URLSearchParams();
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            // Open export in new window
            window.open(url, '_blank');
        }
        
        function exportToPDF() {
            // Get current filter parameters
            const category = document.getElementById('categoryFilter').value;
            const office = document.getElementById('officeFilter').value;
            
            // Build URL with filter parameters
            let url = 'export_property_card_pdf.php';
            const params = new URLSearchParams();
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            // Open export in new window
            window.open(url, '_blank');
        }
        
        function autoFilter() {
            const category = document.getElementById('categoryFilter').value;
            const office = document.getElementById('officeFilter').value;
            
            // Build URL with filter parameters
            let url = 'property_card.php';
            const params = new URLSearchParams();
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            // Redirect to filtered page
            window.location.href = url;
        }
        
        function clearFilters() {
            // Redirect to page without filters
            window.location.href = 'property_card.php';
        }
        
        function showSummary() {
            // Check if there are any items to summarize
            if (<?php echo count($asset_items); ?> === 0) {
                alert('No items available to summarize.');
                return;
            }
            
            // Get current filter parameters
            const category = document.getElementById('categoryFilter').value;
            const office = document.getElementById('officeFilter').value;
            
            // Build URL with filter parameters
            let url = 'property_summary.php';
            const params = new URLSearchParams();
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            // Redirect to summary page
            window.location.href = url;
        }
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
