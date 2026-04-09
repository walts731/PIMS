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
                    pf.received_by_name,
                    ai.ics_par_no
                   FROM asset_items ai
                   LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                   LEFT JOIN offices o1 ON ai.office_id = o1.id
                  LEFT JOIN employees e ON ai.employee_id = e.id
                  LEFT JOIN offices o2 ON e.office_id = o2.id
                  LEFT JOIN par_forms pf ON ai.par_id = pf.id
                  WHERE (ai.par_id IS NOT NULL AND ai.par_id != '') 
                  OR (ai.ics_par_no IS NOT NULL AND ai.ics_par_no != '' AND ai.value >= 50000)";
        
        // Add category filter
        if (!empty($selected_category)) {
            $query .= " AND ac.category_name = '" . $conn->real_escape_string($selected_category) . "'";
        }
        
        // Add office filter
        if (!empty($selected_office)) {
            $query .= " AND (o1.office_name = '" . $conn->real_escape_string($selected_office) . "' OR o2.office_name = '" . $conn->real_escape_string($selected_office) . "')";
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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
        
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4 px-3" id="propertyTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'fixed') ? 'active' : ''; ?>" 
                   href="?tab=fixed<?php echo $selected_category ? '&category='.urlencode($selected_category) : ''; ?><?php echo $selected_office ? '&office='.urlencode($selected_office) : ''; ?>" 
                   role="tab" style="font-weight: 600;">
                    <i class="bi bi-card-checklist me-2"></i>PPE
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'semi') ? 'active' : ''; ?>" 
                   href="?tab=semi<?php echo $selected_category ? '&category='.urlencode($selected_category) : ''; ?><?php echo $selected_office ? '&office='.urlencode($selected_office) : ''; ?>" 
                   role="tab" style="font-weight: 600;">
                    <i class="bi bi-box-seam me-2"></i>Semi-Expandable
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <?php 
            $current_tab = $_GET['tab'] ?? 'fixed';
            if ($current_tab == 'fixed'): 
            ?>
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
                    <!-- Property Card Table -->
                    <div class="table-container">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Property Card Records (Fixed Assets)</h5>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="propertyCardTable" style="width: 100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>PAR No.</th>
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
                                    foreach ($asset_items as $item): 
                                    ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($item['par_no'] ?: $item['ics_par_no'] ?: 'N/A'); ?></span>
                                            </td>
                                            <td>
                                                <span class="property-no"><?php echo htmlspecialchars($item['property_no']); ?></span>
                                            </td>
                                            <td>
                                                <span class="category-badge"><?php echo htmlspecialchars($item['asset_category']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($item['description']); ?>
                                            </td>
                                            <td>
                                                <span class="office-name"><?php echo htmlspecialchars($item['office_name']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($item['employee_name']): ?>
                                                    <?php echo htmlspecialchars($item['employee_name']); ?>
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
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Semi-Expandable Tab Content -->
                <?php include 'semi_expandable.php'; ?>
            <?php endif; ?>
        </div>

    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            if ($('#propertyCardTable').length) {
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
                },
                dom: '<"row"<"col-md-2"l><"col-md-3 category-filter-container"><"col-md-3 office-filter-container"><"col-md-4"f>>rtip',
                initComplete: function(settings, json) {
                    // Add category filter to DataTables
                    $('.category-filter-container').html(`
                        <select id="categoryFilter" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_name']; ?>" <?php echo $selected_category === $category['category_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    `);
                    
                    // Add office filter to DataTables
                    $('.office-filter-container').html(`
                        <select id="officeFilter" class="form-select form-select-sm">
                            <option value="">All Offices</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo $office['office_name']; ?>" <?php echo $selected_office === $office['office_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($office['office_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    `);
                    
                    // Apply filter events with DataTables API
                    $('#categoryFilter, #officeFilter').on('change', function() {
                        applyDataTablesFilters();
                    });
                    
                    // Initial filter application
                    applyDataTablesFilters();
                },
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer"></i> Print',
                        className: 'btn btn-primary btn-sm',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    }
                ]
                });
            }
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
        
        // DataTables custom filtering function
        function applyDataTablesFilters() {
            if (!$('#propertyCardTable').length) return;
            const table = $('#propertyCardTable').DataTable();
            const category = $('#categoryFilter').val();
            const office = $('#officeFilter').val();
            
            // Clear all previous search functions
            $.fn.dataTable.ext.search = [];
            
            // Apply custom search function
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                // Ensure we only filter the propertyCardTable
                if (settings.nTable.id !== 'propertyCardTable') return true;

                const category = $('#categoryFilter').val();
                const office = $('#officeFilter').val();
                
                // Get data and strip HTML just in case
                const stripHtml = (html) => {
                    const tmp = document.createElement("DIV");
                    tmp.innerHTML = html;
                    return tmp.textContent || tmp.innerText || "";
                };
                
                const categoryValue = stripHtml(data[3] || ''); 
                const officeValue = stripHtml(data[5] || '');   
                
                // Category filter
                if (category && categoryValue.trim() !== category) {
                    return false;
                }
                
                // Office filter
                if (office && officeValue.trim() !== office) {
                    return false;
                }
                
                return true;
            });
            
            // Redraw table
            table.draw();
        }
        
        function exportToCSV() {
            // Get current filter values
            const category = document.getElementById('categoryFilter')?.value || '';
            const office = document.getElementById('officeFilter')?.value || '';
            const tab = new URLSearchParams(window.location.search).get('tab') || 'fixed';
            
            // Build URL with filter parameters
            let url = 'export_property_card_csv.php';
            const params = new URLSearchParams();
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            params.append('tab', tab);
            
            url += '?' + params.toString();
            
            // Open export in new window
            window.open(url, '_blank');
        }
        
        function exportToPDF() {
            // Get current filter values
            const category = document.getElementById('categoryFilter')?.value || '';
            const office = document.getElementById('officeFilter')?.value || '';
            const tab = new URLSearchParams(window.location.search).get('tab') || 'fixed';
            
            // Build URL with filter parameters
            let url = 'export_property_card_pdf.php';
            const params = new URLSearchParams();
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            params.append('tab', tab);
            
            url += '?' + params.toString();
            
            // Open export in new window
            window.open(url, '_blank');
        }
        
                
        function showSummary() {
            // Check if there are any items to summarize
            const currentTab = new URLSearchParams(window.location.search).get('tab') || 'fixed';
            const itemCount = currentTab === 'fixed' ? <?php echo count($asset_items); ?> : 1; // Simplification for semi tab
            
            if (itemCount === 0) {
                alert('No items available to summarize.');
                return;
            }
            
            // Get current filter values
            const category = document.getElementById('categoryFilter')?.value || '';
            const office = document.getElementById('officeFilter')?.value || '';
            
            // Build URL with filter parameters
            let url = 'property_summary.php';
            const params = new URLSearchParams();
            
            if (category) params.append('category', category);
            if (office) params.append('office', office);
            params.append('tab', currentTab);
            
            url += '?' + params.toString();
            
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
