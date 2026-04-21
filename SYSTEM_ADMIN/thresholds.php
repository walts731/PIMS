<?php
ob_start();
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

// Check if user has correct role (system_admin only)
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Log page access
logSystemAction($_SESSION['user_id'], 'Accessed Thresholds Management', 'system_admin', 'thresholds.php');

// Get system settings for logo and name
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_name']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to default if database fails
    $system_settings['system_logo'] = '';
    $system_settings['system_name'] = 'PIMS';
}

$logo_path = !empty($system_settings['system_logo']) ? '../' . htmlspecialchars($system_settings['system_logo']) : '../img/trans_logo.png';
$system_name = htmlspecialchars($system_settings['system_name'] ?? 'PIMS');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_threshold':
                $threshold_type = $_POST['threshold_type'];
                $threshold_value = floatval($_POST['threshold_value']);
                $description = $_POST['description'] ?? '';
                
                try {
                    // Check if threshold exists
                    $check_stmt = $conn->prepare("SELECT id FROM thresholds WHERE threshold_type = ?");
                    $check_stmt->bind_param("s", $threshold_type);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        // Update existing threshold
                        $stmt = $conn->prepare("UPDATE thresholds SET threshold_value = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE threshold_type = ?");
                        $stmt->bind_param("dss", $threshold_value, $description, $threshold_type);
                    } else {
                        // Insert new threshold
                        $stmt = $conn->prepare("INSERT INTO thresholds (threshold_type, threshold_value, description) VALUES (?, ?, ?)");
                        $stmt->bind_param("sds", $threshold_type, $threshold_value, $description);
                    }
                    
                    $stmt->execute();
                    
                    $_SESSION['success'] = "Threshold updated successfully!";
                    logSystemAction($_SESSION['user_id'], "Updated threshold: $threshold_type to $threshold_value", 'thresholds', 'thresholds.php');
                    
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error updating threshold: " . $e->getMessage();
                    error_log("Error updating threshold: " . $e->getMessage());
                }
                
                header("Location: thresholds.php");
                exit();
                break;
                
            case 'delete_threshold':
                $threshold_id = intval($_POST['threshold_id']);
                
                try {
                    $stmt = $conn->prepare("DELETE FROM thresholds WHERE id = ?");
                    $stmt->bind_param("i", $threshold_id);
                    $stmt->execute();
                    
                    $_SESSION['success'] = "Threshold deleted successfully!";
                    logSystemAction($_SESSION['user_id'], "Deleted threshold ID: $threshold_id", 'thresholds', 'thresholds.php');
                    
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error deleting threshold: " . $e->getMessage();
                    error_log("Error deleting threshold: " . $e->getMessage());
                }
                
                header("Location: thresholds.php");
                exit();
                break;
        }
    }
}

// Get all thresholds from database
$thresholds = [];
try {
    $result = $conn->query("SELECT * FROM thresholds ORDER BY threshold_type");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $thresholds[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching thresholds: " . $e->getMessage());
    $_SESSION['error'] = "Error loading thresholds: " . $e->getMessage();
}

// Define common threshold types with descriptions
$common_thresholds = [
    'unit_cost_max' => [
        'name' => 'Maximum Unit Cost',
        'description' => 'Maximum allowed unit cost for items in PAR and ICS forms',
        'default' => 50000.00,
        'icon' => 'bi-currency-dollar'
    ],
    'low_stock_threshold' => [
        'name' => 'Low Stock Threshold',
        'description' => 'Minimum quantity before item is considered low stock',
        'default' => 5.00,
        'icon' => 'bi-exclamation-triangle'
    ],
    'reorder_level' => [
        'name' => 'Reorder Level',
        'description' => 'Quantity at which items should be reordered',
        'default' => 10.00,
        'icon' => 'bi-cart-plus'
    ],
    'max_inventory_value' => [
        'name' => 'Maximum Inventory Value',
        'description' => 'Maximum total value allowed for inventory items',
        'default' => 1000000.00,
        'icon' => 'bi-piggy-bank'
    ],
    'depreciation_years' => [
        'name' => 'Depreciation Period (Years)',
        'description' => 'Default number of years for asset depreciation',
        'default' => 5.00,
        'icon' => 'bi-graph-down'
    ]
];

// Get existing thresholds as associative array for easy lookup
$existing_thresholds = [];
foreach ($thresholds as $threshold) {
    $existing_thresholds[$threshold['threshold_type']] = $threshold;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thresholds Management - PIMS</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, var(--light-accent) 100%);
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
        
        .threshold-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }
        
        .threshold-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .threshold-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-gradient);
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .threshold-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .threshold-type {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .threshold-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background: var(--primary-gradient);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        @media print {
            .no-print { display: none !important; }
            .threshold-card { box-shadow: none; break-inside: avoid; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar-toggle.php'; ?>
    
    <div class="main-wrapper">
        <?php include 'includes/topbar.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="container-fluid p-4">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                    <h1 class="h3 mb-2">
                        <i class="bi bi-sliders me-2"></i>
                        Thresholds Management
                    </h1>
                    <p class="text-muted mb-0">Configure system thresholds and limits for forms and inventory management</p>
                </div>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                                
                <!-- Existing Thresholds -->
                <div class="row">
                    <div class="col-12">
                        <h4 class="mb-3">Current Thresholds</h4>
                    </div>
                </div>
                
                <div class="row">
                    <?php if (empty($thresholds)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="bi bi-sliders text-muted" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">No Thresholds Configured</h5>
                                <p class="text-muted">Start by adding your first threshold using the "Add Threshold" button above.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($thresholds as $threshold): ?>
                            <div class="col-lg-6 col-xl-4">
                                <div class="threshold-card">
                                    <div class="threshold-icon">
                                        <i class="<?php echo $common_thresholds[$threshold['threshold_type']]['icon'] ?? 'bi-gear'; ?>"></i>
                                    </div>
                                    
                                    <div class="threshold-value">
                                        <?php echo number_format($threshold['threshold_value'], 2); ?>
                                    </div>
                                    
                                    <div class="threshold-type">
                                        <?php echo htmlspecialchars($common_thresholds[$threshold['threshold_type']]['name'] ?? ucfirst(str_replace('_', ' ', $threshold['threshold_type']))); ?>
                                    </div>
                                    
                                    <div class="threshold-description">
                                        <?php echo htmlspecialchars($threshold['description'] ?? $common_thresholds[$threshold['threshold_type']]['description'] ?? 'No description available'); ?>
                                    </div>
                                    
                                    <div class="text-muted small mb-3">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        Updated: <?php echo date('M j, Y', strtotime($threshold['updated_at'])); ?>
                                    </div>
                                    
                                    <div class="action-buttons no-print">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editThreshold('<?php echo $threshold['threshold_type']; ?>', <?php echo $threshold['threshold_value']; ?>, '<?php echo htmlspecialchars($threshold['description'] ?? ''); ?>')">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                            </div>
        </main>
    </div>
    
    <!-- Add/Edit Threshold Modal -->
    <div class="modal fade" id="thresholdModal" tabindex="-1" aria-labelledby="thresholdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="thresholdModalLabel">
                        <i class="bi bi-sliders me-2"></i>
                        <span id="modalTitle">Add Threshold</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="thresholds.php">
                    <input type="hidden" name="action" value="update_threshold">
                    <input type="hidden" name="threshold_type" id="threshold_type">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="threshold_value" class="form-label">Threshold Value</label>
                            <div class="input-group">
                                <span class="input-group-text">50,000</span>
                                <input type="number" step="0.01" class="form-control" id="threshold_value" name="threshold_value" required>
                            </div>
                            <div class="form-text">Enter the numerical value for this threshold</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe what this threshold controls..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Save Threshold
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
        
    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let thresholdModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            thresholdModal = new bootstrap.Modal(document.getElementById('thresholdModal'));
        });
        
        function editThreshold(type, value, description) {
            document.getElementById('modalTitle').textContent = 'Edit Threshold';
            document.getElementById('threshold_type').value = type;
            document.getElementById('threshold_value').value = value;
            document.getElementById('description').value = description;
            thresholdModal.show();
        }
        
            </script>
</body>
</html>
