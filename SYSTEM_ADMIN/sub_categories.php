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
            $stmt = $conn->prepare("INSERT INTO asset_sub_categories (sub_category_name, sub_category_code, asset_categories_id, useful_life, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiis", $sub_category_name, $sub_category_code, $asset_categories_id, $useful_life, $status, $_SESSION['user_id']);
            $stmt->execute();
            
            $message = "Sub category added successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'sub_category_added', 'asset_management', "Added sub category: {$sub_category_name} ({$sub_category_code})");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Sub category code already exists.";
            } else {
                $message = "Error adding sub category: " . $e->getMessage();
            }
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
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "Sub category code already exists.";
            } else {
                $message = "Error updating sub category: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

// DELETE - Delete sub category
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Get sub category info before deletion
        $stmt = $conn->prepare("SELECT sub_category_name, sub_category_code FROM asset_sub_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sub_category = $result->fetch_assoc();
        
        if ($sub_category) {
            $stmt = $conn->prepare("DELETE FROM asset_sub_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Sub category deleted successfully!";
            $message_type = "success";
            
            logSystemAction($_SESSION['user_id'], 'sub_category_deleted', 'asset_management', "Deleted sub category: {$sub_category['sub_category_name']} ({$sub_category['sub_category_code']})");
        }
    } catch (Exception $e) {
        $message = "Error deleting sub category: " . $e->getMessage();
        $message_type = "danger";
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

// Get statistics
$stats = [];
try {
    $stmt = $conn->prepare("SELECT 
                          COUNT(*) as total_sub_categories,
                          SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_sub_categories,
                          SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_sub_categories,
                          AVG(useful_life) as avg_useful_life
                          FROM asset_sub_categories");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
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
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .stats-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                        <i class="bi bi-plus-circle"></i> Add Sub Category
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportSubCategories()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_sub_categories'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-tags-fill"></i> Total Sub Categories</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['active_sub_categories'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-check-circle"></i> Active</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['inactive_sub_categories'] ?? 0; ?></div>
                    <div class="stats-label"><i class="bi bi-x-circle"></i> Inactive</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo round($stats['avg_useful_life'] ?? 0, 1); ?> yrs</div>
                    <div class="stats-label"><i class="bi bi-clock"></i> Avg Useful Life</div>
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
                            <th>Created</th>
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
                                    <td><small><?php echo date('M j, Y', strtotime($sub_category['created_at'])); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action" onclick="editSubCategory(<?php echo $sub_category['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteSubCategory(<?php echo $sub_category['id']; ?>, '<?php echo htmlspecialchars($sub_category['sub_category_name']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
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
    
    <?php require_once 'includes/logout-modal.php'; ?>
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
    <script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    </script>
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
        
        // Delete sub category function
        function deleteSubCategory(id, name) {
            if (confirm(`Are you sure you want to delete the sub category "${name}"? This action cannot be undone.`)) {
                window.location.href = `sub_categories.php?action=delete&id=${id}`;
            }
        }
        
        // Export sub categories function
        function exportSubCategories() {
            const data = subCategoriesTable.data().toArray();
            let csv = 'Sub Category Code,Sub Category Name,Parent Category,Useful Life,Status,Created\n';
            
            data.forEach(row => {
                const rowData = [
                    row[0].replace(/<[^>]*>/g, '').trim(), // Sub Category Code
                    row[1], // Sub Category Name
                    row[2].replace(/<[^>]*>/g, '').trim(), // Parent Category
                    row[3], // Useful Life
                    row[4].replace(/<[^>]*>/g, '').trim(), // Status
                    row[5]  // Created
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
    </script>
</body>
</html>
