<?php
ob_start();
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log infrastructure page access
logSystemAction($_SESSION['user_id'], 'infrastructure_accessed', 'infrastructure', 'Accessed infrastructure page');

// Check for redirect and handle it
if (isset($_SESSION['redirect_url'])) {
    $redirect_url = $_SESSION['redirect_url'];
    unset($_SESSION['redirect_url']);
    error_log("Handling stored redirect: " . $redirect_url);
    header("Location: " . $redirect_url);
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$classification_filter = isset($_GET['classification']) ? $_GET['classification'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';

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

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Debug: Log the form submission
    error_log("Form submitted - Action: " . $action);
    
    if ($action === 'add') {
        $classification = $_POST['classification'];
        $item_description = $_POST['item_description'];
        $nature_occupancy = $_POST['nature_occupancy'];
        $location = $_POST['location'];
        $date_constructed = $_POST['date_constructed'];
        $property_no = $_POST['property_no'];
        $acquisition_cost = floatval($_POST['acquisition_cost']);
        $market_value = floatval($_POST['market_value']);
        $date_appraisal = $_POST['date_appraisal'];
        $remarks = $_POST['remarks'];
        
        // Handle image uploads
        $image_paths = [];
        if (isset($_FILES['additional_images'])) {
            $upload_dir = '../uploads/infrastructure/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . $_FILES['additional_images']['name'][$key];
                    move_uploaded_file($tmp_name, $upload_dir . $filename);
                    $image_paths[] = $filename;
                }
            }
        }
        
        $images_json = json_encode($image_paths);
        
        $sql = "INSERT INTO infrastructure (classification, item_description, nature_occupancy, location, date_constructed, property_no, acquisition_cost, market_value, date_appraisal, remarks, additional_images, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssddsssi', $classification, $item_description, $nature_occupancy, $location, $date_constructed, $property_no, $acquisition_cost, $market_value, $date_appraisal, $remarks, $images_json, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Infrastructure item added successfully!";
            logSystemAction($_SESSION['user_id'], 'infrastructure_added', 'infrastructure', "Added infrastructure: $item_description");
        } else {
            $_SESSION['error'] = "Error adding infrastructure item.";
        }
        $stmt->close();
        
        // Ensure no output before redirect
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Debug: Log before redirect
        error_log("About to redirect to: " . $_SERVER['PHP_SELF']);
        
        // Use absolute URL for redirect
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = $_SERVER['PHP_SELF'];
        $redirect_url = $protocol . "://" . $host . $path;
        
        error_log("Full redirect URL: " . $redirect_url);
        
        // Store redirect URL in session for fallback
        $_SESSION['redirect_url'] = $redirect_url;
        
        header("Location: " . $redirect_url);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Infrastructure Management - PIMS</title>
    <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
    <script>
    // Fallback redirect if header redirect fails
    setTimeout(function() {
        window.location.reload();
    }, 1000);
    </script>
    <?php endif; ?>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, var(--light-accent) 100%);
            min-height: 100vh;
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
            background: linear-gradient(135deg, var(--primary-color) 0%, #4a5bf5 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .infrastructure-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .filter-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .table th {
            background: transparent;
            color: #333;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .table tbody tr:hover {
            background-color: rgba(var(--primary-rgb), 0.05);
        }
        
        .btn-infrastructure {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .btn-infrastructure:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3);
            color: white;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
            font-size: 0.875rem;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .btn-outline-secondary {
            border: 1px solid #6c757d;
            color: #6c757d;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
            font-size: 0.875rem;
            background: white;
        }
        
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: var(--border-radius-lg);
            border-bottom-left-radius: var(--border-radius-lg);
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: var(--border-radius-lg);
            border-bottom-right-radius: var(--border-radius-lg);
        }
        
        .image-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }
        
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            
            .page-header, .filter-card, .no-print {
                display: none !important;
            }
            
            .infrastructure-card {
                box-shadow: none;
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .table {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .table th {
                background: #f8f9fa !important;
                color: #000 !important;
                border: 1px solid #000;
            }
            
            .table td {
                border: 1px solid #000;
            }
            
            @page {
                size: landscape;
                margin: 0.5in;
            }
            
            html {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Infrastructure Management';
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
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?> alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-<?php echo ($_SESSION['message_type'] ?? 'success') == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInfrastructureModal">
                        <i class="bi bi-plus-circle"></i> Add Infrastructure
                    </button>
                    <button class="btn btn-success btn-sm ms-2" onclick="exportCSV()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Active Filters Display -->
        <?php if (!empty($search) || !empty($classification_filter) || !empty($location_filter)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>Active Filters:</strong>
                <?php if (!empty($search)): ?>
                    Search: "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
                <?php if (!empty($classification_filter)): ?>
                    Classification: <?php echo htmlspecialchars($classification_filter); ?>
                <?php endif; ?>
                <?php if (!empty($location_filter)): ?>
                    Location: <?php echo htmlspecialchars($location_filter); ?>
                <?php endif; ?>
                <a href="infrastructure.php" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="bi bi-x-circle"></i> Clear All
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
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
        
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search description, property no, location..." id="searchInput">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Classification</label>
                        <select name="classification" class="form-select" onchange="this.form.submit()">
                            <option value="">All Classifications</option>
                            <?php foreach ($classifications as $classification): ?>
                                <option value="<?php echo htmlspecialchars($classification); ?>" <?php echo $classification_filter === $classification ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($classification); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Location</label>
                        <select name="location" class="form-select" onchange="this.form.submit()">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location); ?>" <?php echo $location_filter === $location ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="infrastructure.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Infrastructure Table -->
        <div class="infrastructure-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Infrastructure Items (<?php echo $total_count; ?> found)</h5>
                <?php if (!empty($search) || !empty($classification_filter) || !empty($location_filter)): ?>
                    <small class="text-muted">Filtered results</small>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>Item Description</th>
                            <th>Nature Occupancy</th>
                            <th>Location</th>
                            <th>Date Constructed</th>
                            <th>Property No.</th>
                            <th>Acquisition Cost</th>
                            <th>Market Value</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($infrastructure_data)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No infrastructure items found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($infrastructure_data as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['classification']); ?></td>
                                    <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                    <td><?php echo htmlspecialchars($item['nature_occupancy']); ?></td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($item['date_constructed'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['property_no']); ?></td>
                                    <td>₱<?php echo number_format($item['acquisition_cost'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['market_value'], 2); ?></td>
                                    <td class="no-print">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewInfrastructure(<?php echo $item['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="editInfrastructure(<?php echo $item['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Add Infrastructure Modal -->
    <div class="modal fade" id="addInfrastructureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Infrastructure Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="infrastructure.php" enctype="multipart/form-data" id="addInfrastructureForm">
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
                            <label class="form-label">Additional Images (max 4)</label>
                            <input type="file" name="additional_images[]" class="form-control" multiple accept="image/*" max="4">
                            <small class="text-muted">You can upload up to 4 images</small>
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
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <script>
        // Import CSV function
        function importCSV() {
            // Create a hidden file input
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.csv';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Create FormData for file upload
                    const formData = new FormData();
                    formData.append('csv_file', file);
                    
                    // Show loading state
                    const originalText = e.target.textContent;
                    e.target.textContent = 'Uploading...';
                    e.target.disabled = true;
                    
                    // Send file to server (you'll need to create process_infrastructure_import.php)
                    fetch('process_infrastructure_import.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('CSV imported successfully! ' + data.message);
                            location.reload();
                        } else {
                            alert('Error importing CSV: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error uploading file: ' + error.message);
                    })
                    .finally(() => {
                        // Restore button state
                        e.target.textContent = originalText;
                        e.target.disabled = false;
                    });
                }
            };
            input.click();
        }
        
        // Export CSV function
        function exportCSV() {
            window.location.href = 'export_infrastructure_csv.php';
        }
        
        // Export PDF function
        function exportPDF() {
            window.open('export_infrastructure_pdf.php', '_blank');
        }
        
        // View infrastructure item
        function viewInfrastructure(id) {
            // Load view modal via AJAX
            fetch('view_infrastructure_modal.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    // Remove existing modal if any
                    const existingModal = document.getElementById('viewInfrastructureModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    // Add modal to body
                    document.body.insertAdjacentHTML('beforeend', html);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('viewInfrastructureModal'));
                    modal.show();
                    
                    // Clean up modal after hidden
                    document.getElementById('viewInfrastructureModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                })
                .catch(error => {
                    alert('Error loading infrastructure details: ' + error.message);
                });
        }
        
        // Edit infrastructure item
        function editInfrastructure(id) {
            loadEditModal(id);
        }
        
        // Load edit modal
        function loadEditModal(id) {
            // Load edit modal via AJAX
            fetch('edit_infrastructure_modal.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    // Remove existing modal if any
                    const existingModal = document.getElementById('editInfrastructureModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    // Add modal to body
                    document.body.insertAdjacentHTML('beforeend', html);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editInfrastructureModal'));
                    modal.show();
                    
                    // Clean up modal after hidden
                    document.getElementById('editInfrastructureModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                })
                .catch(error => {
                    alert('Error loading edit form: ' + error.message);
                });
        }
    </script>
    
    <script>
    // Handle add infrastructure form submission
    document.getElementById('addInfrastructureForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin"></i> Adding...';
        submitBtn.disabled = true;
        
        // Let the form submit normally
        setTimeout(() => {
            this.submit();
        }, 100);
    });
    
    // Auto-search functionality with debounce
    let searchTimeout;
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const value = this.value;
                
                // Debounce search - wait 500ms after user stops typing
                searchTimeout = setTimeout(function() {
                    // Submit form if search has 2+ characters or is empty
                    if (value.length >= 2 || value.length === 0) {
                        document.querySelector('form').submit();
                    }
                }, 500);
            });
            
            // Also search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    document.querySelector('form').submit();
                }
            });
        }
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
