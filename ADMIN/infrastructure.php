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
                $sql = "INSERT INTO infrastructure (classification, item_description, nature_occupancy, location, date_constructed, property_no, acquisition_cost, market_value, date_appraisal, remarks, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssddss", $classification, $item_description, $nature_occupancy, $location, $date_constructed, $property_no, $acquisition_cost, $market_value, $date_appraisal, $remarks);
                
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInfrastructureModal">
                        <i class="bi bi-plus-circle"></i> Add Infrastructure
                    </button>
                    <button class="btn btn-success btn-sm ms-2" onclick="exportInfrastructure()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_count; ?></div>
                    <div class="stats-label"><i class="bi bi-building"></i> Total Infrastructure</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number">₱<?php echo number_format($total_value, 2); ?></div>
                    <div class="stats-label"><i class="bi bi-cash"></i> Total Acquisition Cost</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($classifications); ?></div>
                    <div class="stats-label"><i class="bi bi-tags"></i> Classifications</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($locations); ?></div>
                    <div class="stats-label"><i class="bi bi-geo-alt"></i> Locations</div>
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
                                        <button class="btn btn-sm btn-outline-info" onclick="viewInfrastructure(<?php echo $item['id']; ?>)">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="editInfrastructure(<?php echo $item['id']; ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
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
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Add Infrastructure Modal -->
    <div class="modal fade" id="addInfrastructureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Infrastructure Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="infrastructure.php">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Infrastructure</button>
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
        
        // View and Edit functions (placeholders)
        function viewInfrastructure(id) {
            alert('View functionality coming soon for ID: ' + id);
        }
        
        function editInfrastructure(id) {
            alert('Edit functionality coming soon for ID: ' + id);
        }
        
        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
</body>
</html>
