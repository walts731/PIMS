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

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log sub categories page access
logSystemAction($_SESSION['user_id'], 'access', 'sub_categories', 'System admin accessed sub categories page');

// Handle CRUD operations
$message = '';
$message_type = '';

// CREATE - Add new sub category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $sub_category_name = trim($_POST['sub_category_name'] ?? '');
    $sub_category_code = trim($_POST['sub_category_code'] ?? '');
    $asset_categories_id = intval($_POST['asset_categories_id'] ?? 0);
    $useful_life = intval($_POST['useful_life'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($sub_category_name) || empty($sub_category_code)) {
        $message = "Sub category name and code are required.";
        $message_type = "danger";
    } elseif ($asset_categories_id <= 0) {
        $message = "Please select a parent category.";
        $message_type = "danger";
    } elseif (!preg_match('/^\d{2,5}$/', $sub_category_code)) {
        $message = "Sub category code must be 2-5 digits only.";
        $message_type = "danger";
    } elseif ($useful_life < 0) {
        $message = "Useful life must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            $sub_category_name = mysqli_real_escape_string($conn, $sub_category_name);
            $sub_category_code = mysqli_real_escape_string($conn, $sub_category_code);
            $asset_categories_id = intval($asset_categories_id);
            $useful_life = intval($useful_life);
            $status = mysqli_real_escape_string($conn, $status);
            $created_by = intval($_SESSION['user_id']);
            
            $sql = "INSERT INTO asset_sub_categories (sub_category_name, sub_category_code, asset_categories_id, useful_life, status, created_by) 
                    VALUES ('$sub_category_name', '$sub_category_code', $asset_categories_id, $useful_life, '$status', $created_by)";
            
            $conn->query($sql);
            
            $message = "Sub category added successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'sub_category_added', 'asset_management', "Added sub category: {$sub_category_name} ({$sub_category_code})");
            
        } catch (Exception $e) {
            $message = "Error adding sub category: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// UPDATE - Edit sub category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $sub_category_name = trim($_POST['sub_category_name'] ?? '');
    $sub_category_code = trim($_POST['sub_category_code'] ?? '');
    $asset_categories_id = intval($_POST['asset_categories_id'] ?? 0);
    $useful_life = intval($_POST['useful_life'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($sub_category_name) || empty($sub_category_code)) {
        $message = "Sub category name and code are required.";
        $message_type = "danger";
    } elseif ($asset_categories_id <= 0) {
        $message = "Please select a parent category.";
        $message_type = "danger";
    } elseif (!preg_match('/^\d{2,5}$/', $sub_category_code)) {
        $message = "Sub category code must be 2-5 digits only.";
        $message_type = "danger";
    } elseif ($useful_life < 0) {
        $message = "Useful life must be a positive number.";
        $message_type = "danger";
    } else {
        try {
            // Escape values for traditional SQL
            $sub_category_name = mysqli_real_escape_string($conn, $sub_category_name);
            $sub_category_code = mysqli_real_escape_string($conn, $sub_category_code);
            $asset_categories_id = intval($asset_categories_id);
            $useful_life = intval($useful_life);
            $status = mysqli_real_escape_string($conn, $status);
            $updated_by = intval($_SESSION['user_id']);
            $id = intval($id);
            
            $sql = "UPDATE asset_sub_categories SET 
                    sub_category_name = '$sub_category_name', 
                    sub_category_code = '$sub_category_code', 
                    asset_categories_id = $asset_categories_id, 
                    useful_life = $useful_life, 
                    status = '$status', 
                    updated_by = $updated_by 
                    WHERE id = $id";
            
            $conn->query($sql);
            
            $message = "Sub category updated successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'sub_category_updated', 'asset_management', "Updated sub category: {$sub_category_name} ({$sub_category_code})");
            
        } catch (Exception $e) {
            $message = "Error updating sub category: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}


// Get all sub categories with parent category info
$sub_categories = [];
try {
    $stmt = $conn->prepare("SELECT sc.*, ac.category_name, ac.category_code, u1.username as created_by_name, u2.username as updated_by_name 
                          FROM asset_sub_categories sc 
                          LEFT JOIN asset_categories ac ON sc.asset_categories_id = ac.id 
                          LEFT JOIN users u1 ON sc.created_by = u1.id 
                          LEFT JOIN users u2 ON sc.updated_by = u2.id 
                          ORDER BY ac.category_name, sc.sub_category_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sub_categories[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching sub categories: " . $e->getMessage());
}

// Get parent categories for dropdown
$categories = [];
try {
    $stmt = $conn->prepare("SELECT id, category_code, category_name FROM asset_categories WHERE status = 'active' ORDER BY category_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
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
    <title>Sub Categories - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
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
            border-left: 4px solid var(--primary-color);
        }
        
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.125rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .category-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .modal-header {
            background: var(--primary-gradient);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 27, 169, 0.05);
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Sub Categories Management';
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
                        <i class="bi bi-tags-fill"></i> Sub Categories Management
                    </h1>
                    <p class="text-muted mb-0">Manage asset sub categories with parent category relationships</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="subCategoryActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="subCategoryActionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                                    <i class="bi bi-plus-circle text-primary"></i> Add Sub Category
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportSubCategories()">
                                    <i class="bi bi-download text-success"></i> Export Sub Categories
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="refreshSubCategories()">
                                    <i class="bi bi-arrow-clockwise text-warning"></i> Refresh Data
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="printSubCategories()">
                                    <i class="bi bi-printer text-secondary"></i> Print List
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        
        <!-- Sub Categories Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Sub Categories List</h5>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="subCategoriesTable">
                    <thead>
                        <tr>
                            <th>Sub Category Code</th>
                            <th>Sub Category Name</th>
                            <th>Parent Category</th>
                            <th>Useful Life</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sub_categories)): ?>
                            <?php foreach ($sub_categories as $sub_category): ?>
                                <tr>
                                    <td>
                                        <span class="category-badge">
                                            <?php echo htmlspecialchars($sub_category['sub_category_code']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub_category['sub_category_name']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($sub_category['category_code'] . ' - ' . $sub_category['category_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $sub_category['useful_life'] ? $sub_category['useful_life'] . ' years' : 'Not set'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $sub_category['status']; ?>">
                                            <?php echo ucfirst($sub_category['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action" onclick="editSubCategory(<?php echo $sub_category['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No sub categories found. Click "Add Sub Category" to create your first sub category.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main-content and main wrapper -->
    
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Add Sub Category Modal -->
    <div class="modal fade" id="addSubCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Sub Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Parent Category *</label>
                            <select class="form-select" name="asset_categories_id" required>
                                <option value="">Select Parent Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sub Category Name *</label>
                            <input type="text" class="form-control" name="sub_category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sub Category Code *</label>
                            <input type="text" class="form-control" name="sub_category_code" pattern="\d{2,5}" title="2-5 digits only" required>
                            <small class="form-text text-muted">2-5 digits only (e.g., 01, 123, 4567)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Useful Life (years)</label>
                                    <input type="number" class="form-control" name="useful_life" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Sub Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Sub Category Modal -->
    <div class="modal fade" id="editSubCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Sub Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editId" name="id">
                        
                        <div class="mb-3">
                            <label class="form-label">Parent Category *</label>
                            <select class="form-select" id="editAssetCategoriesId" name="asset_categories_id" required>
                                <option value="">Select Parent Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['category_code'] . ' - ' . $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sub Category Name *</label>
                            <input type="text" class="form-control" id="editSubCategoryName" name="sub_category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sub Category Code *</label>
                            <input type="text" class="form-control" id="editSubCategoryCode" name="sub_category_code" pattern="\d{2,5}" title="2-5 digits only" required>
                            <small class="form-text text-muted">2-5 digits only (e.g., 01, 123, 4567)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Useful Life (years)</label>
                                    <input type="number" class="form-control" id="editUsefulLife" name="useful_life" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="editStatus" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Update Sub Category
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
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Sub categories data for editing
        const subCategoriesData = <?php echo json_encode($sub_categories); ?>;
        
        // Initialize DataTable
        let subCategoriesTable;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            subCategoriesTable = $('#subCategoriesTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'asc']], // Sort by Sub Category Code by default
                columnDefs: [
                    {
                        targets: -1, // Actions column (last column)
                        orderable: false,
                        searchable: false
                    }
                ],
                dom: '<"row"<"col-md-6"l><"col-md-6 text-end"f>>rtip',
                language: {
                    search: "Search sub categories:",
                    lengthMenu: "Show _MENU_ sub categories per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ sub categories",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No sub categories available",
                    zeroRecords: "No matching sub categories found"
                }
            });
        });
        
        // Edit sub category function
        function editSubCategory(id) {
            const subCategory = subCategoriesData.find(sc => sc.id == id);
            if (subCategory) {
                $('#editId').val(subCategory.id);
                $('#editAssetCategoriesId').val(subCategory.asset_categories_id);
                $('#editSubCategoryName').val(subCategory.sub_category_name);
                $('#editSubCategoryCode').val(subCategory.sub_category_code);
                $('#editUsefulLife').val(subCategory.useful_life || '');
                $('#editStatus').val(subCategory.status);
                
                const modal = new bootstrap.Modal(document.getElementById('editSubCategoryModal'));
                modal.show();
            }
        }
        
                
        // Export sub categories function
        function exportSubCategories() {
            const data = subCategoriesTable.data().toArray();
            let csv = 'Sub Category Code,Sub Category Name,Parent Category,Useful Life,Status\n';
            
            data.forEach(row => {
                const rowData = [
                    row[0].replace(/<[^>]*>/g, '').trim(), // Sub Category Code
                    row[1], // Sub Category Name
                    row[2].replace(/<[^>]*>/g, '').trim(), // Parent Category
                    row[3], // Useful Life
                    row[4].replace(/<[^>]*>/g, '').trim() // Status
                ];
                csv += rowData.map(cell => `"${cell.trim()}"`).join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `sub_categories_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Refresh sub categories function
        function refreshSubCategories() {
            // Show loading state
            showAlert('Refreshing sub categories data...', 'info');
            
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        // Print sub categories function
        function printSubCategories() {
            // Get all data from DataTable (not just current page)
            const allData = subCategoriesTable.data().toArray();
            
            if (allData.length === 0) {
                showAlert('No sub categories data to print', 'warning');
                return;
            }
            
            // Create a print preview window
            const printWindow = window.open('', '_blank', 'width=1000,height=800');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Sub Categories - Print Preview</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 0.5in;
                        }
                        
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 12px;
                            color: #333;
                            background: white;
                        }
                        
                        .preview-toolbar {
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            z-index: 1000;
                            background: #191BA9;
                            color: white;
                            padding: 12px 20px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                        }
                        
                        .preview-toolbar .title {
                            font-weight: bold;
                            font-size: 14px;
                            display: flex;
                            align-items: center;
                        }
                        
                        .preview-toolbar .actions {
                            display: flex;
                            gap: 10px;
                        }
                        
                        .preview-toolbar button {
                            padding: 6px 12px;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 12px;
                            transition: all 0.3s ease;
                        }
                        
                        .preview-toolbar .btn-print {
                            background: #28a745;
                            color: white;
                        }
                        
                        .preview-toolbar .btn-print:hover {
                            background: #218838;
                            transform: translateY(-1px);
                        }
                        
                        .preview-toolbar .btn-close {
                            background: #6c757d;
                            color: white;
                        }
                        
                        .preview-toolbar .btn-close:hover {
                            background: #5a6268;
                            transform: translateY(-1px);
                        }
                        
                        .print-container {
                            width: 100%;
                            max-width: 8.5in;
                            margin: 0 auto;
                            padding: 60px 20px 20px;
                            background: white;
                            min-height: 11in;
                            position: relative;
                        }
                        
                        @media screen {
                            body {
                                background: #525659;
                                padding: 0;
                            }
                            .print-container {
                                background: white;
                                box-shadow: 0 0 20px rgba(0,0,0,0.5);
                                margin: 60px auto 20px;
                            }
                        }
                        
                        @media print {
                            .no-print { display: none !important; }
                            body { background: white; margin: 0; padding: 0; }
                            .print-container { 
                                box-shadow: none; 
                                margin: 0 auto; 
                                padding: 20px;
                                width: 100%;
                            }
                        }
                        
                        .report-header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 2px solid #191BA9;
                            padding-bottom: 15px;
                        }
                        
                        .report-header h1 {
                            color: #191BA9;
                            font-size: 24px;
                            font-weight: bold;
                            margin-bottom: 5px;
                            text-transform: uppercase;
                        }
                        
                        .report-header .subtitle {
                            color: #666;
                            font-size: 14px;
                            margin-bottom: 10px;
                        }
                        
                        .report-header .meta {
                            color: #333;
                            font-size: 12px;
                            font-weight: bold;
                        }
                        
                        .categories-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 20px 0;
                        }
                        
                        .categories-table th,
                        .categories-table td {
                            border: 1px solid #333;
                            padding: 10px;
                            text-align: left;
                            vertical-align: top;
                        }
                        
                        .categories-table th {
                            background-color: #f8f9fa;
                            font-weight: bold;
                            color: #333;
                            text-transform: uppercase;
                            font-size: 11px;
                        }
                        
                        .categories-table .category-name {
                            font-weight: bold;
                            min-width: 150px;
                        }
                        
                        .categories-table .category-code {
                            font-family: monospace;
                            background-color: #f8f9fa;
                            padding: 4px 8px;
                            border-radius: 3px;
                            font-size: 11px;
                            min-width: 100px;
                            text-align: center;
                        }
                        
                        .categories-table .parent-category {
                            max-width: 200px;
                            word-wrap: break-word;
                            font-size: 11px;
                        }
                        
                        .categories-table .useful-life {
                            text-align: center;
                            font-weight: bold;
                            min-width: 80px;
                        }
                        
                        .categories-table .status-active {
                            color: #28a745;
                            font-weight: bold;
                            text-align: center;
                            text-transform: uppercase;
                            font-size: 11px;
                        }
                        
                        .categories-table .status-inactive {
                            color: #6c757d;
                            font-weight: bold;
                            text-align: center;
                            text-transform: uppercase;
                            font-size: 11px;
                        }
                        
                        .report-footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 1px solid #ddd;
                            font-size: 11px;
                            color: #666;
                            text-align: center;
                        }
                        
                        .report-footer .summary {
                            font-weight: bold;
                            margin-bottom: 5px;
                            color: #333;
                        }
                        
                        .report-footer .user-info {
                            font-style: italic;
                        }
                        
                        /* Alternating row colors */
                        .categories-table tbody tr:nth-child(even) {
                            background-color: #f9f9f9;
                        }
                        
                        .categories-table tbody tr:hover {
                            background-color: #f0f8ff;
                        }
                    </style>
                </head>
                <body>
                    <div class="preview-toolbar no-print">
                        <div class="title">
                            <i class="bi bi-printer-fill me-2"></i>Sub Categories Print Preview
                        </div>
                        <div class="actions">
                            <button onclick="window.print()" class="btn-print">
                                <i class="bi bi-printer me-1"></i>Print Report
                            </button>
                            <button onclick="window.close()" class="btn-close">
                                <i class="bi bi-x-lg me-1"></i>Close Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="print-container">
                        <div class="report-header">
                            <h1>Sub Categories Report</h1>
                            <div class="subtitle">Property and Inventory Management System</div>
                            <div class="meta">
                                Generated on: ${new Date().toLocaleString()} | Total Sub Categories: ${allData.length}
                            </div>
                        </div>
                        
                        <table class="categories-table">
                            <thead>
                                <tr>
                                    <th>Sub Category Code</th>
                                    <th>Sub Category Name</th>
                                    <th>Parent Category</th>
                                    <th>Useful Life</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${allData.map((row, index) => {
                                    // Extract data from DataTable row
                                    const subCategoryCode = row[0] || '';
                                    const subCategoryName = row[1] || '';
                                    const parentCategory = row[2] || '';
                                    const usefulLife = row[3] || '';
                                    const status = row[4] || 'inactive';
                                    
                                    // Clean text content
                                    const cleanCode = subCategoryCode.replace(/<[^>]*>/g, '').trim();
                                    const cleanName = subCategoryName.replace(/<[^>]*>/g, '').trim();
                                    const cleanParent = parentCategory.replace(/<[^>]*>/g, '').trim();
                                    const cleanLife = usefulLife.replace(/<[^>]*>/g, '').trim();
                                    const cleanStatus = status.replace(/<[^>]*>/g, '').trim().toLowerCase();
                                    
                                    const statusClass = cleanStatus === 'active' ? 'status-active' : 'status-inactive';
                                    
                                    return `
                                        <tr>
                                            <td><span class="category-code">${cleanCode}</span></td>
                                            <td class="category-name">${cleanName}</td>
                                            <td class="parent-category">${cleanParent}</td>
                                            <td class="useful-life">${cleanLife}</td>
                                            <td class="${statusClass}">${cleanStatus.charAt(0).toUpperCase() + cleanStatus.slice(1)}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                        
                        <div class="report-footer">
                            <div class="summary">
                                Report Summary: ${allData.length} sub categories exported from PIMS Asset Management System
                            </div>
                            <div class="user-info">
                                Printed by: System Administrator | 
                                Date: ${new Date().toLocaleDateString()} | 
                                Page: <span class="page-number"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bootstrap Icons -->
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
                </body>
                </html>
            `);
            printWindow.document.close();
            
            // Add page numbering
            printWindow.onload = function() {
                // Add page numbers
                const pageNumbers = printWindow.document.querySelectorAll('.page-number');
                pageNumbers.forEach((element, index) => {
                    element.textContent = index + 1;
                });
            };
        }
        
        // Show alert function
        function showAlert(message, type) {
            // Remove existing alerts
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            
            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert after page header
            const pageHeader = document.querySelector('.page-header');
            pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }
    </script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
