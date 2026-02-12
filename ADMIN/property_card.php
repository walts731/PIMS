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
        // Simple query first to test
        $query = "SELECT 
                    ai.id,
                    ai.created_at,
                    ai.property_no,
                    ai.description,
                    ai.value,
                    ai.par_id,
                    ai.employee_id,
                    ai.office_id,
                    COALESCE(ac.category_code, 'UNCAT') as asset_category,
                    COALESCE(o1.office_name, o2.office_name, 'Unassigned') as office_name,
                    COALESCE(o1.office_code, o2.office_code, 'NONE') as office_code
                  FROM asset_items ai
                  LEFT JOIN asset_categories ac ON ai.category_id = ac.id
                  LEFT JOIN offices o1 ON ai.office_id = o1.id
                  LEFT JOIN employees e ON ai.employee_id = e.id
                  LEFT JOIN offices o2 ON e.office_id = o2.id
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
                // Add employee and PAR info separately
                $row['employee_name'] = '';
                $row['employee_no'] = '';
                $row['par_no'] = '';
                
                // Get employee info
                if (!empty($row['employee_id'])) {
                    $emp_query = "SELECT CONCAT(firstname, ' ', lastname) as name, employee_no FROM employees WHERE id = " . intval($row['employee_id']);
                    $emp_result = $conn->query($emp_query);
                    if ($emp_result && $emp_data = $emp_result->fetch_assoc()) {
                        $row['employee_name'] = $emp_data['name'];
                        $row['employee_no'] = $emp_data['employee_no'];
                    }
                }
                
                // Get PAR info
                if (!empty($row['par_id'])) {
                    $par_query = "SELECT par_no, received_by_name FROM par_forms WHERE id = " . intval($row['par_id']);
                    $par_result = $conn->query($par_query);
                    if ($par_result && $par_data = $par_result->fetch_assoc()) {
                        $row['par_no'] = $par_data['par_no'];
                        $row['received_by'] = $par_data['received_by_name'];
                    }
                }
                
                $asset_items[] = $row;
            }
        }
    } catch (Exception $e) {
        // Error handling - could add user feedback here
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
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow: 0 2px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border-left: 4px solid #191BA9;
        }
        
        .property-card-table {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table-custom thead th {
            background: transparent;
            color: #212529;
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-custom thead th:first-child {
            border-top-left-radius: 12px;
        }
        
        .table-custom thead th:last-child {
            border-top-right-radius: 12px;
        }
        
        .table-custom tbody td {
            padding: 0.875rem 0.75rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .table-custom tbody tr:hover {
            background: rgba(25, 27, 169, 0.02);
        }
        
        .property-no {
            font-weight: 600;
            color: #191BA9;
            font-family: 'Courier New', monospace;
        }
        
        .par-reference {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .employee-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .office-code-only {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            font-family: 'Courier New', monospace;
            display: inline-block;
        }
        
        .employee-name {
            font-weight: 500;
            color: #212529;
        }
        
        .employee-no {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .quantity-badge {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 40px;
        }
        
        .value-cell {
            font-weight: 600;
            color: #212529;
            text-align: right;
        }
        
        .balance-qty {
            font-weight: 600;
            color: #fd7e14;
            text-align: center;
        }
        
        .category-badge {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }
        
        .date-cell {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
        }
        
        .description-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .description-cell:hover {
            white-space: normal;
            overflow: visible;
            text-overflow: initial;
        }
        
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-badge {
            background: rgba(25, 27, 169, 0.1);
            color: #191BA9;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .filter-row {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
        }
        
        .filter-row th {
            border-bottom: none;
            padding: 0.75rem;
            vertical-align: middle;
        }
        
        .filter-row .form-label {
            color: #495057;
            font-size: 0.875rem;
        }
        
        .stats-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        @media (max-width: 768px) {
            .table-custom {
                font-size: 0.8rem;
            }
            
            .table-custom thead th,
            .table-custom tbody td {
                padding: 0.5rem 0.25rem;
            }
            
            .description-cell {
                max-width: 100px;
            }
        }
    </style>
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
                    <h1 class="mb-2" style="font-weight: 700; color: #191BA9;">
                        <i class="bi bi-credit-card me-2"></i>Property Card
                    </h1>
                    <p class="text-muted mb-0">View all asset items with Property Acknowledgment Receipt (PAR) references</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn export-btn me-2" onclick="exportToCSV()">
                        <i class="bi bi-download me-1"></i> Export to CSV
                    </button>
                    <button class="btn btn-danger" onclick="exportToPDF()">
                        <i class="bi bi-file-pdf me-1"></i> Export to PDF
                    </button>
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
            <!-- Statistics Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($asset_items); ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format(array_sum(array_column($asset_items, 'value')), 2); ?></div>
                    <div class="stat-label">Total Value (₱)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_unique(array_column($asset_items, 'par_id'))); ?></div>
                    <div class="stat-label">PAR Forms</div>
                </div>
            </div>
            
            <div class="property-card-table">
                <div class="table-responsive">
                    <table class="table table-custom" id="propertyCardTable">
                        <thead>
                            <tr class="filter-row">
                                <th colspan="2" class="text-start">
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
                                </th>
                                <th colspan="2" class="text-start">
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
                                </th>
                                <th colspan="6" class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                                            <i class="bi bi-x-circle me-1"></i>Clear
                                        </button>
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <th>Property No.</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Office</th>
                                <th>Employee</th>
                                <th>Receipt/Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                                <th>Balance Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $item_counter = 1;
                            foreach ($asset_items as $index => $item): 
                            ?>
                                <tr>
                                    <td class="date-cell">
                                        <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="property-no"><?php echo htmlspecialchars($item['property_no']); ?></span>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?php echo htmlspecialchars($item['asset_category']); ?></span>
                                    </td>
                                    <td class="description-cell" title="<?php echo htmlspecialchars($item['description']); ?>">
                                        <?php echo htmlspecialchars($item['description']); ?>
                                    </td>
                                    <td>
                                        <span class="office-code-only"><?php echo htmlspecialchars($item['office_code']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($item['employee_name']): ?>
                                            <div class="employee-info">
                                                <span class="employee-name"><?php echo htmlspecialchars($item['employee_name']); ?></span>
                                                <span class="employee-no"><?php echo htmlspecialchars($item['employee_no']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="quantity-badge">1</span>
                                    </td>
                                    <td class="value-cell">
                                        ₱<?php echo number_format($item['value'], 2); ?>
                                    </td>
                                    <td class="value-cell">
                                        ₱<?php echo number_format($item['value'], 2); ?>
                                    </td>
                                    <td class="balance-qty">
                                        <?php echo $item_counter; ?>
                                    </td>
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
        // Initialize DataTable
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
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
