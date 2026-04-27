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

// Check if user has correct role (system_admin)
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log locations page access
logSystemAction($_SESSION['user_id'], 'access', 'locations', 'User accessed Locations page');

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $office_name = $_POST['office_name'] ?? '';
                $office_code = $_POST['office_code'] ?? '';
                $location = $_POST['location'] ?? '';
                $address = $_POST['address'] ?? '';
                $city = $_POST['city'] ?? '';
                $state = $_POST['state'] ?? '';
                $postal_code = $_POST['postal_code'] ?? '';
                $country = $_POST['country'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $email = $_POST['email'] ?? '';
                $manager_name = $_POST['manager_name'] ?? '';
                $manager_contact = $_POST['manager_contact'] ?? '';
                $capacity = $_POST['capacity'] ?? 0;
                $status = $_POST['status'] ?? 'active';
                
                try {
                    // Auto-generate office code starting with L if not provided
                    if (empty($office_code)) {
                        $prefix = 'L';
                        $result = $conn->query("SELECT MAX(CAST(SUBSTRING(office_code, 2) AS UNSIGNED)) as max_code FROM offices WHERE office_code LIKE 'L%'");
                        $row = $result->fetch_assoc();
                        $next_num = ($row['max_code'] ?? 0) + 1;
                        $office_code = $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);
                    } else {
                        // Ensure office code starts with L
                        if (strtoupper(substr($office_code, 0, 1)) !== 'L') {
                            $office_code = 'L' . $office_code;
                        }
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO offices (office_name, office_code, location, address, city, state, postal_code, country, phone, email, manager_name, manager_contact, capacity, status, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->bind_param("sssssssssssisii", $office_name, $office_code, $location, $address, $city, $state, $postal_code, $country, $phone, $email, $manager_name, $manager_contact, $capacity, $status, $_SESSION['user_id'], $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $message = "Location added successfully!";
                        $message_type = "success";
                        logSystemAction($_SESSION['user_id'], 'create', 'location', "Added location: $office_name ($office_code)");
                    } else {
                        $message = "Error adding location: " . $conn->error;
                        $message_type = "danger";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $office_name = $_POST['office_name'] ?? '';
                $office_code = $_POST['office_code'] ?? '';
                $location = $_POST['location'] ?? '';
                $address = $_POST['address'] ?? '';
                $city = $_POST['city'] ?? '';
                $state = $_POST['state'] ?? '';
                $postal_code = $_POST['postal_code'] ?? '';
                $country = $_POST['country'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $email = $_POST['email'] ?? '';
                $manager_name = $_POST['manager_name'] ?? '';
                $manager_contact = $_POST['manager_contact'] ?? '';
                $capacity = $_POST['capacity'] ?? 0;
                $status = $_POST['status'] ?? 'active';
                
                try {
                    // Ensure office code starts with L
                    if (strtoupper(substr($office_code, 0, 1)) !== 'L') {
                        $office_code = 'L' . $office_code;
                    }
                    
                    $stmt = $conn->prepare("UPDATE offices SET office_name = ?, office_code = ?, location = ?, address = ?, city = ?, state = ?, postal_code = ?, country = ?, phone = ?, email = ?, manager_name = ?, manager_contact = ?, capacity = ?, status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("sssssssssssisii", $office_name, $office_code, $location, $address, $city, $state, $postal_code, $country, $phone, $email, $manager_name, $manager_contact, $capacity, $status, $_SESSION['user_id'], $id);
                    
                    if ($stmt->execute()) {
                        $message = "Location updated successfully!";
                        $message_type = "success";
                        logSystemAction($_SESSION['user_id'], 'update', 'location', "Updated location: $office_name ($office_code)");
                    } else {
                        $message = "Error updating location: " . $conn->error;
                        $message_type = "danger";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                try {
                    // Check if location is being used
                    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM asset_items WHERE office_id = ?");
                    $check_stmt->bind_param("i", $id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] > 0) {
                        $message = "Cannot delete location. It is being used by " . $row['count'] . " asset items.";
                        $message_type = "warning";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM offices WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        
                        if ($stmt->execute()) {
                            $message = "Location deleted successfully!";
                            $message_type = "success";
                            logSystemAction($_SESSION['user_id'], 'delete', 'location', "Deleted location with ID: $id");
                        } else {
                            $message = "Error deleting location: " . $conn->error;
                            $message_type = "danger";
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
                
            case 'toggle_status':
                $id = $_POST['id'] ?? 0;
                $status = $_POST['status'] ?? 'active';
                
                try {
                    $stmt = $conn->prepare("UPDATE offices SET status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("sii", $status, $_SESSION['user_id'], $id);
                    
                    if ($stmt->execute()) {
                        $message = "Location status updated successfully!";
                        $message_type = "success";
                        logSystemAction($_SESSION['user_id'], 'update', 'location', "Updated location status to: $status");
                    } else {
                        $message = "Error updating location status: " . $conn->error;
                        $message_type = "danger";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get locations data
$locations = [];
$stats = [
    'total_locations' => 0,
    'active_locations' => 0,
    'inactive_locations' => 0,
    'total_capacity' => 0
];

if ($conn && !$conn->connect_error) {
    try {
        // Get locations with office codes starting with L
        $query = "SELECT o.*, 
                    u1.username as created_by_name, 
                    u2.username as updated_by_name,
                    (SELECT COUNT(*) FROM asset_items WHERE office_id = o.id) as asset_count
                  FROM offices o 
                  LEFT JOIN users u1 ON o.created_by = u1.id 
                  LEFT JOIN users u2 ON o.updated_by = u2.id 
                  WHERE o.office_code LIKE 'L%'
                  ORDER BY o.office_name";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $locations[] = $row;
                
                // Update stats
                $stats['total_locations']++;
                if ($row['status'] === 'active') {
                    $stats['active_locations']++;
                } else {
                    $stats['inactive_locations']++;
                }
                $stats['total_capacity'] += $row['capacity'];
            }
        }
    } catch (Exception $e) {
        error_log("Locations Query Error: " . $e->getMessage());
    }
}

// Get location data for edit modal
$location_data = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM offices WHERE id = ? AND office_code LIKE 'L%'");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $location_data = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Edit Location Error: " . $e->getMessage());
    }
}
?>
<?php
// Get system settings for theme
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback to default
    $system_settings['system_name'] = 'PIMS';
}

// Set page title for topbar
$page_title = 'Locations';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locations - <?php echo htmlspecialchars($system_settings['system_name'] ?? 'PIMS'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        }
        
        /* Sidebar Toggle Styles */
        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1051;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar-toggle:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .sidebar-toggle.sidebar-active {
            left: 300px;
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
        
        .location-badge {
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
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .main-wrapper.sidebar-active {
            margin-left: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle.sidebar-active {
                left: 20px;
            }
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
        }
    </style>
</head>
<body>
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
                        <i class="bi bi-geo-alt-fill"></i> Locations
                    </h1>
                    <p class="text-muted mb-0">Manage location records for the LGU</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="locationActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="locationActionsDropdown">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                    <i class="bi bi-plus-circle text-primary"></i> Add Location
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportLocations()">
                                    <i class="bi bi-download text-success"></i> Export Locations
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="refreshLocations()">
                                    <i class="bi bi-arrow-clockwise text-warning"></i> Refresh Data
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="printLocations()">
                                    <i class="bi bi-printer text-secondary"></i> Print List
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Locations Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Locations List</h5>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="locationsTable">
                                <thead>
                                    <tr>
                                        <th>Location Name</th>
                                        <th>Code</th>
                                        <th>Address</th>
                                        <th>Contact</th>
                                        <th>Manager</th>
                                        <th>Capacity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($locations as $location): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($location['office_name']); ?></strong>
                                                <?php if (!empty($location['location'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($location['location']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="location-badge">
                                                    <?php echo htmlspecialchars($location['office_code']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $address_parts = [];
                                                if (!empty($location['address'])) $address_parts[] = $location['address'];
                                                if (!empty($location['city'])) $address_parts[] = $location['city'];
                                                if (!empty($location['state'])) $address_parts[] = $location['state'];
                                                echo !empty($address_parts) ? htmlspecialchars(implode(', ', $address_parts)) : '<span class="text-muted">No address</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($location['phone'])): ?>
                                                    <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($location['phone']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($location['email'])): ?>
                                                    <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($location['email']); ?></div>
                                                <?php endif; ?>
                                                <?php if (empty($location['phone']) && empty($location['email'])): ?>
                                                    <span class="text-muted">No contact</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($location['manager_name'])): ?>
                                                    <strong><?php echo htmlspecialchars($location['manager_name']); ?></strong>
                                                    <?php if (!empty($location['manager_contact'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($location['manager_contact']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No manager</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-people"></i> <?php echo number_format($location['capacity']); ?> capacity
                                                </span>
                                                <?php if ($location['asset_count'] > 0): ?>
                                                    <br><span class="badge bg-secondary">
                                                        <i class="bi bi-box"></i> <?php echo $location['asset_count']; ?> assets
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?php echo $location['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo !empty($location['status']) && $location['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="status_<?php echo $location['id']; ?>" 
                                                               onchange="this.form.submit()"
                                                               <?php echo (!empty($location['status']) && $location['status'] == 'active') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="status_<?php echo $location['id']; ?>">
                                                            <span class="status-badge status-<?php echo !empty($location['status']) && $location['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                                                <?php echo !empty($location['status']) && $location['status'] == 'active' ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="window.location.href='locations.php?action=edit&id=<?php echo $location['id']; ?>'">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
            </div>
        </div>
    </div>
    
    <!-- Add Location Modal -->
    <div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLocationModalLabel">
                        <i class="bi bi-plus-circle"></i> Add New Location
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="office_name" class="form-label">Location Name *</label>
                                    <input type="text" class="form-control" id="office_name" name="office_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="office_code" class="form-label">Office Code</label>
                                    <input type="text" class="form-control" id="office_code" name="office_code" placeholder="Auto-generated (starts with L)">
                                </div>
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location/Area</label>
                                    <input type="text" class="form-control" id="location" name="location">
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city">
                                </div>
                                <div class="mb-3">
                                    <label for="state" class="form-label">State/Province</label>
                                    <input type="text" class="form-control" id="state" name="state">
                                </div>
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code">
                                </div>
                                <div class="mb-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" value="Philippines">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="manager_name" class="form-label">Manager Name</label>
                                    <input type="text" class="form-control" id="manager_name" name="manager_name">
                                </div>
                                <div class="mb-3">
                                    <label for="manager_contact" class="form-label">Manager Contact</label>
                                    <input type="text" class="form-control" id="manager_contact" name="manager_contact">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="capacity" class="form-label">Capacity</label>
                                    <input type="number" class="form-control" id="capacity" name="capacity" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
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
                            <i class="bi bi-plus-circle me-1"></i> Add Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Location Modal -->
    <div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLocationModalLabel">
                        <i class="bi bi-pencil"></i> Edit Location
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_office_name" class="form-label">Location Name *</label>
                                    <input type="text" class="form-control" id="edit_office_name" name="office_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_office_code" class="form-label">Office Code</label>
                                    <input type="text" class="form-control" id="edit_office_code" name="office_code">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_location" class="form-label">Location/Area</label>
                                    <input type="text" class="form-control" id="edit_location" name="location">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="edit_address" name="address">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="edit_city" name="city">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_state" class="form-label">State/Province</label>
                                    <input type="text" class="form-control" id="edit_state" name="state">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="edit_postal_code" name="postal_code">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="edit_country" name="country">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="edit_phone" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_manager_name" class="form-label">Manager Name</label>
                                    <input type="text" class="form-control" id="edit_manager_name" name="manager_name">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_manager_contact" class="form-label">Manager Contact</label>
                                    <input type="text" class="form-control" id="edit_manager_contact" name="manager_contact">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_capacity" class="form-label">Capacity</label>
                                    <input type="number" class="form-control" id="edit_capacity" name="capacity" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-select" id="edit_status" name="status">
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
                            <i class="bi bi-save me-1"></i> Update Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Show edit modal if editing
            <?php if ($location_data): ?>
                const editModal = new bootstrap.Modal(document.getElementById('editLocationModal'));
                editModal.show();
            <?php endif; ?>
            
            // Initialize DataTable
            $('#locationsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: "Search locations:",
                    lengthMenu: "Show _MENU_ locations per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ locations",
                    infoEmpty: "Showing 0 to 0 of 0 locations",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
        
        function editLocation(id) {
            window.location.href = '?edit=' + id;
        }
        
        function toggleStatus(id, status) {
            if (confirm('Are you sure you want to ' + (status === 'active' ? 'activate' : 'deactivate') + ' this location?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="status" value="${status}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteLocation(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function exportLocations() {
            window.location.href = 'export_locations_csv.php';
        }
        
        function refreshLocations() {
            location.reload();
        }
        
        function printLocations() {
            window.print();
        }
        
        // Auto-fill edit modal if editing
        <?php if ($location_data): ?>
        $(document).ready(function() {
            $('#editLocationModal').modal('show');
            $('#edit_id').val('<?php echo $location_data['id']; ?>');
            $('#edit_office_name').val('<?php echo htmlspecialchars($location_data['office_name']); ?>');
            $('#edit_office_code').val('<?php echo htmlspecialchars($location_data['office_code']); ?>');
            $('#edit_location').val('<?php echo htmlspecialchars($location_data['location']); ?>');
            $('#edit_address').val('<?php echo htmlspecialchars($location_data['address']); ?>');
            $('#edit_city').val('<?php echo htmlspecialchars($location_data['city']); ?>');
            $('#edit_state').val('<?php echo htmlspecialchars($location_data['state']); ?>');
            $('#edit_postal_code').val('<?php echo htmlspecialchars($location_data['postal_code']); ?>');
            $('#edit_country').val('<?php echo htmlspecialchars($location_data['country']); ?>');
            $('#edit_phone').val('<?php echo htmlspecialchars($location_data['phone']); ?>');
            $('#edit_email').val('<?php echo htmlspecialchars($location_data['email']); ?>');
            $('#edit_manager_name').val('<?php echo htmlspecialchars($location_data['manager_name']); ?>');
            $('#edit_manager_contact').val('<?php echo htmlspecialchars($location_data['manager_contact']); ?>');
            $('#edit_capacity').val('<?php echo $location_data['capacity']; ?>');
            $('#edit_status').val('<?php echo $location_data['status']; ?>');
        });
        <?php endif; ?>
    </script>
</body>
</html>
