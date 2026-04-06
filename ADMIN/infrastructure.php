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

// Log infrastructure page access
logSystemAction($_SESSION['user_id'], 'access', 'infrastructure', 'Admin accessed infrastructure page');

// Handle CRUD operations
$message = '';
$message_type = '';

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $classification = trim($_POST['classification'] ?? '');
        $item_description = trim($_POST['item_description'] ?? '');
        $nature_occupancy = trim($_POST['nature_occupancy'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $date_constructed = $_POST['date_constructed'] ?? '';
        $property_no = trim($_POST['property_no'] ?? '');
        $acquisition_cost = floatval($_POST['acquisition_cost'] ?? 0);
        $market_value = floatval($_POST['market_value'] ?? 0);
        $date_appraisal = $_POST['date_appraisal'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');
        
        // Handle image uploads
        $additional_images = [];
        if (isset($_FILES['additional_images'])) {
            $upload_dir = '../uploads/infrastructure/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['additional_images']['name'][$key];
                    $file_tmp = $_FILES['additional_images']['tmp_name'][$key];
                    $file_size = $_FILES['additional_images']['size'][$key];
                    $file_type = $_FILES['additional_images']['type'][$key];
                    
                    // Validate file type (images only)
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
                    if (in_array($file_type, $allowed_types)) {
                        // Generate unique filename
                        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                        $unique_filename = time() . '_' . rand(1000, 9999) . '_' . $key . '.' . $file_extension;
                        $file_path = $upload_dir . $unique_filename;
                        
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            $additional_images[] = $unique_filename;
                        }
                    }
                }
            }
        }
        
        // Validation
        if (empty($classification)) {
            $message = "Classification is required.";
            $message_type = "danger";
        } elseif (empty($item_description)) {
            $message = "Item description is required.";
            $message_type = "danger";
        } elseif (empty($location)) {
            $message = "Location is required.";
            $message_type = "danger";
        } elseif (empty($date_constructed)) {
            $message = "Date constructed is required.";
            $message_type = "danger";
        } elseif ($acquisition_cost <= 0) {
            $message = "Acquisition cost must be greater than 0.";
            $message_type = "danger";
        } else {
            try {
                $additional_images_json = json_encode($additional_images);
                $sql = "INSERT INTO infrastructure (classification, item_description, nature_occupancy, location, date_constructed, property_no, acquisition_cost, market_value, date_appraisal, remarks, additional_images, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssddsssi", $classification, $item_description, $nature_occupancy, $location, $date_constructed, $property_no, $acquisition_cost, $market_value, $date_appraisal, $remarks, $additional_images_json, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $message = "Infrastructure item added successfully!";
                    $message_type = "success";
                    logSystemAction($_SESSION['user_id'], 'infrastructure_added', 'infrastructure', "Added infrastructure: $item_description");
                } else {
                    throw new Exception("Failed to add infrastructure item: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = "Error adding infrastructure item: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$classification_filter = isset($_GET['classification']) ? trim($_GET['classification']) : '';
$location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';

// Build WHERE conditions
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(item_description LIKE ? OR property_no LIKE ? OR location LIKE ? OR remarks LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types = 'ssss';
}

if (!empty($classification_filter)) {
    $where_conditions[] = "classification = ?";
    $params[] = $classification_filter;
    $types .= 's';
}

if (!empty($location_filter)) {
    $where_conditions[] = "location = ?";
    $params[] = $location_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get infrastructure data
$infrastructure_data = [];
$total_value = 0;
$total_count = 0;

$sql = "SELECT * FROM infrastructure $where_clause ORDER BY date_constructed DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $infrastructure_data[] = $row;
    $total_value += $row['acquisition_cost'];
    $total_count++;
}
$stmt->close();

// Get unique classifications and locations for filters
$classifications = [];
$locations = [];

$class_sql = "SELECT DISTINCT classification FROM infrastructure ORDER BY classification";
$class_result = $conn->query($class_sql);
while ($row = $class_result->fetch_assoc()) {
    $classifications[] = $row['classification'];
}

$loc_sql = "SELECT DISTINCT location FROM infrastructure ORDER BY location";
$loc_result = $conn->query($loc_sql);
while ($row = $loc_result->fetch_assoc()) {
    $locations[] = $row['location'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infrastructure - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
    $page_title = 'Infrastructure';
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
                        <i class="bi bi-building"></i> Infrastructure
                    </h1>
                    <p class="text-muted mb-0">Manage infrastructure and building assets</p>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addInfrastructureModal">
                                    <i class="bi bi-plus-circle"></i> Add Infrastructure
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="exportInfrastructure()">
                                    <i class="bi bi-download"></i> Export
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
        
        
        <!-- Infrastructure Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Infrastructure Records</h5>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="classificationFilter" onchange="applyFilters()">
                                <option value="">All Classifications</option>
                                <?php foreach ($classifications as $classification): ?>
                                    <option value="<?php echo htmlspecialchars($classification); ?>" <?php echo $classification_filter == $classification ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($classification); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="locationFilter" onchange="applyFilters()">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location); ?>" <?php echo $location_filter == $location ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search infrastructure..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="infrastructureTable">
                    <thead class="table-light">
                        <tr>
                            <th>Classification</th>
                            <th>Item Description</th>
                            <th>Location</th>
                            <th>Date Constructed</th>
                            <th>Acquisition Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($infrastructure_data)): ?>
                            <?php foreach ($infrastructure_data as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['classification']); ?></td>
                                    <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td><small><?php echo date('M j, Y', strtotime($item['date_constructed'])); ?></small></td>
                                    <td>₱<?php echo number_format($item['acquisition_cost'], 2); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-info" onclick="viewInfrastructure(<?php echo $item['id']; ?>)" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="editInfrastructure(<?php echo $item['id']; ?>)" title="Edit Item">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteInfrastructure(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['item_description'])); ?>')" title="Delete Item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No infrastructure items found. Click "Add Infrastructure" to create your first item.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div> <!-- Close main-content -->
</div> <!-- Close main-wrapper -->

<?php require_once 'includes/logout-modal.php'; ?>
<?php require_once 'includes/change-password-modal.php'; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Delete Infrastructure Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle"></i> Warning: This action cannot be undone!</h6>
                    <p class="mb-2">You are about to permanently delete:</p>
                    <p class="fw-bold text-danger mb-2" id="deleteItemName"></p>
                    <p class="mb-0">This will remove the infrastructure item and all associated data from the system.</p>
                </div>
                <p class="text-muted mb-0">Are you sure you want to continue?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="addInfrastructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Infrastructure Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="infrastructure.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Classification/Type *</label>
                            <input type="text" name="classification" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nature Occupancy</label>
                            <input type="text" name="nature_occupancy" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Item Description *</label>
                        <textarea name="item_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Location *</label>
                            <input type="text" name="location" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Constructed *</label>
                            <input type="date" name="date_constructed" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Property No./Other Reference</label>
                            <input type="text" name="property_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Acquisition Cost *</label>
                            <input type="number" name="acquisition_cost" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Market/Appraisal Value</label>
                            <input type="number" name="market_value" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Appraisal</label>
                            <input type="date" name="date_appraisal" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Additional Images</label>
                        <input type="file" name="additional_images[]" class="form-control" multiple accept="image/*">
                        <small class="text-muted">You can select multiple images (JPG, PNG, GIF, etc.)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Infrastructure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Infrastructure Modal -->
<div class="modal fade" id="viewInfrastructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Infrastructure Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewInfrastructureContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Infrastructure Modal -->
<div class="modal fade" id="editInfrastructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Infrastructure Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editInfrastructureForm" method="POST" action="process_infrastructure.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editInfrastructureId">
                    <input type="hidden" name="removed_images" id="removedImages" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Classification/Type *</label>
                            <input type="text" name="classification" id="editClassification" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nature Occupancy</label>
                            <input type="text" name="nature_occupancy" id="editNatureOccupancy" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Item Description *</label>
                        <textarea name="item_description" id="editItemDescription" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Location *</label>
                            <input type="text" name="location" id="editLocation" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Constructed *</label>
                            <input type="date" name="date_constructed" id="editDateConstructed" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Property No./Other Reference</label>
                            <input type="text" name="property_no" id="editPropertyNo" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Acquisition Cost *</label>
                            <input type="number" name="acquisition_cost" id="editAcquisitionCost" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Market/Appraisal Value</label>
                            <input type="number" name="market_value" id="editMarketValue" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Appraisal</label>
                            <input type="date" name="date_appraisal" id="editDateAppraisal" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="editRemarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Additional Images</label>
                        <div id="existingImages" class="row g-2 mb-3">
                            <!-- Existing images will be loaded here -->
                        </div>
                        <input type="file" name="additional_images[]" id="editAdditionalImages" class="form-control" multiple accept="image/*">
                        <small class="text-muted">You can add more images or remove existing ones. Supported formats: JPG, PNG, GIF, WebP</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Infrastructure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<?php require_once 'includes/sidebar-scripts.php'; ?>

<script>
    // Filter functions
    function applyFilters() {
        const classification = document.getElementById('classificationFilter').value;
        const location = document.getElementById('locationFilter').value;
        const search = document.getElementById('searchInput').value;
        
        const params = new URLSearchParams();
        if (classification) params.set('classification', classification);
        if (location) params.set('location', location);
        if (search) params.set('search', search);
        
        const url = 'infrastructure.php' + (params.toString() ? '?' + params.toString() : '');
        window.location.href = url;
    }
    
    function clearFilters() {
        window.location.href = 'infrastructure.php';
    }
    
    // Export function
    function exportInfrastructure() {
        let csv = 'Classification,Item Description,Location,Date Constructed,Acquisition Cost,Market Value\n';
        
        <?php if (!empty($infrastructure_data)): ?>
            <?php foreach ($infrastructure_data as $item): ?>
                csv += `<?php echo htmlspecialchars($item['classification']); ?>,<?php echo htmlspecialchars($item['item_description']); ?>,<?php echo htmlspecialchars($item['location']); ?>,<?php echo $item['date_constructed']; ?>,<?php echo $item['acquisition_cost']; ?>,<?php echo $item['market_value']; ?>\n`;
            <?php endforeach; ?>
        <?php endif; ?>
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'infrastructure_export.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // View Infrastructure function
    function viewInfrastructure(id) {
        $.ajax({
            url: 'api/infrastructure.php?action=get&id=' + id,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Classification</h6>
                                <p>${data.classification || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Nature of Occupancy</h6>
                                <p>${data.nature_occupancy || 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Item Description</h6>
                                <p>${data.item_description || 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Location</h6>
                                <p>${data.location || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Date Constructed</h6>
                                <p>${data.date_constructed ? new Date(data.date_constructed).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Property No.</h6>
                                <p>${data.property_no || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Acquisition Cost</h6>
                                <p>₱${parseFloat(data.acquisition_cost || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Market Value</h6>
                                <p>₱${parseFloat(data.market_value || 0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Date of Appraisal</h6>
                                <p>${data.date_appraisal ? new Date(data.date_appraisal).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Remarks</h6>
                                <p>${data.remarks || 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Record Information</h6>
                                <p><small class="text-muted">
                                    Created: ${data.created_at ? new Date(data.created_at).toLocaleString() : 'N/A'}<br>
                                    Last Updated: ${data.updated_at ? new Date(data.updated_at).toLocaleString() : 'Never'}
                                </small></p>
                            </div>
                        </div>
                    `;
                    
                    // Add images if they exist
                    if (data.additional_images && data.additional_images.length > 0) {
                        html += `
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6><i class="bi bi-images"></i> Additional Images (${data.additional_images.length})</h6>
                                    <div class="row g-3">
                        `;
                        
                        data.additional_images.forEach(function(image, index) {
                            html += `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card">
                                        <div class="position-relative">
                                            <img src="../uploads/infrastructure/${image}" class="card-img-top gallery-image" alt="Infrastructure Image ${index + 1}" 
                                                 style="height: 200px; object-fit: cover; cursor: pointer;" 
                                                 onclick="openImageModal('../uploads/infrastructure/${image}', ${index + 1})"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2VkZSIvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZSBOZXQgRm91bmQ8L3RleHQ+PC9zdmc+';">
                                            <div class="card-img-overlay d-flex align-items-center justify-content-center opacity-0 hover-opacity-100 bg-dark bg-opacity-50 transition-opacity">
                                                <button type="button" class="btn btn-light btn-sm" onclick="openImageModal('../uploads/infrastructure/${image}', ${index + 1})" title="View Full Size">
                                                    <i class="bi bi-zoom-in"></i> View
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body p-2">
                                            <small class="text-muted">Image ${index + 1}</small>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `
                                        </div>
                                    </div>
                                </div>
                            `;
                        }                  
                    $('#viewInfrastructureContent').html(html);
                    $('#viewInfrastructureModal').modal('show');
                } else {
                    alert('Error loading infrastructure details: ' + response.message);
                }
            },
            error: function() {
                alert('Error loading infrastructure details. Please try again.');
            }
        });
    }
    
    // Edit Infrastructure function
    function editInfrastructure(id) {
        $.ajax({
            url: 'api/infrastructure.php?action=get&id=' + id,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Populate form fields
                    $('#editInfrastructureId').val(data.id);
                    $('#editClassification').val(data.classification || '');
                    $('#editNatureOccupancy').val(data.nature_occupancy || '');
                    $('#editItemDescription').val(data.item_description || '');
                    $('#editLocation').val(data.location || '');
                    $('#editDateConstructed').val(data.date_constructed || '');
                    $('#editPropertyNo').val(data.property_no || '');
                    $('#editAcquisitionCost').val(data.acquisition_cost || '');
                    $('#editMarketValue').val(data.market_value || '');
                    $('#editDateAppraisal').val(data.date_appraisal || '');
                    $('#editRemarks').val(data.remarks || '');
                    
                    // Load existing images
                    let imagesHtml = '';
                    if (data.additional_images && data.additional_images.length > 0) {
                        data.additional_images.forEach(function(image, index) {
                            imagesHtml += `
                                <div class="col-md-3 col-sm-4 col-6 position-relative" id="existingImage_${index}">
                                    <div class="card h-100">
                                        <img src="../uploads/infrastructure/${image}" class="card-img-top" alt="Image ${index + 1}" 
                                             style="height: 100px; object-fit: cover;" 
                                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2VkZCIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEwIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SW1hZ2UgTm90IEZvdW5kPC90ZXh0Pjwvc3ZnPg==';">
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" 
                                                onclick="removeExistingImage('${image}', ${index})" 
                                                title="Remove this image">
                                            <i class="bi bi-x"></i>
                                        </button>
                                        <div class="card-body p-1">
                                            <small class="text-muted text-center d-block">Image ${index + 1}</small>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        imagesHtml = '<div class="col-12"><small class="text-muted">No existing images</small></div>';
                    }
                    
                    $('#existingImages').html(imagesHtml);
                    
                    // Reset removed images field
                    $('#removedImages').val('');
                    
                    $('#editInfrastructureModal').modal('show');
                } else {
                    alert('Error loading infrastructure data: ' + response.message);
                }
            },
            error: function() {
                alert('Error loading infrastructure data. Please try again.');
            }
        });
    }
    
    // Handle edit form submission via AJAX
    $('#editInfrastructureForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close modal and show success message
                    $('#editInfrastructureModal').modal('hide');
                    
                    // Show success message
                    const successHtml = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    
                    // Insert success message after page header
                    $('.page-header .row').first().after(successHtml);
                    
                    // Reload page to show updated data
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                alert('Error updating infrastructure item. Please try again.');
                console.error('AJAX Error:', error);
            }
        });
    });
    
    // Search on Enter key
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
    
    // Remove existing image function
    function removeExistingImage(imageName, index) {
        if (confirm('Are you sure you want to remove this image?')) {
            // Hide the image card
            $('#existingImage_' + index).fadeOut(300, function() {
                $(this).remove();
                
                // Update the removed images hidden field
                let removedImages = $('#removedImages').val();
                if (removedImages) {
                    removedImages += ',' + imageName;
                } else {
                    removedImages = imageName;
                }
                $('#removedImages').val(removedImages);
            });
        }
    }
    
    // Delete Infrastructure function
    function deleteInfrastructure(id, itemName) {
        // Set the item name in the modal
        $('#deleteItemName').text(itemName);
        
        // Store the ID for the confirm button
        $('#confirmDeleteBtn').data('itemId', id);
        
        // Show the modal
        $('#deleteConfirmationModal').modal('show');
    }
    
    // Handle delete confirmation
    $('#confirmDeleteBtn').on('click', function() {
        const id = $(this).data('itemId');
        
        // Hide the modal
        $('#deleteConfirmationModal').modal('hide');
        
        // Show loading state
        const deleteBtn = $('button[onclick*="deleteInfrastructure(' + id + '"]');
        const originalText = deleteBtn.html();
        deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Deleting...');
        
        // Send AJAX request
        $.ajax({
            url: 'process_infrastructure.php',
            method: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const successHtml = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    
                    // Insert success message after page header
                    $('.page-header .row').first().after(successHtml);
                    
                    // Animate row removal and reload page
                    const row = deleteBtn.closest('tr');
                    row.fadeOut(500, function() {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    });
                    
                } else {
                    alert('Error: ' + response.message);
                    // Restore button state
                    deleteBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                alert('Error deleting infrastructure item. Please try again.');
                console.error('AJAX Error:', error);
                // Restore button state
                deleteBtn.prop('disabled', false).html(originalText);
            }
        });
    });
</script>
</body>
</html>
