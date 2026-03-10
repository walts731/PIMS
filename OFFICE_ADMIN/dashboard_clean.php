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
if ($_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once '../includes/logger.php';

// Log dashboard access
logSystemAction($_SESSION['user_id'], 'access', 'office_dashboard', 'Office admin accessed dashboard');

// Get office-specific statistics
$stats = [];
$user_office_id = $_SESSION['office_id'] ?? null;

// Check database connection first
if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // ===== OFFICE-SPECIFIC ASSETS =====
        if ($user_office_id) {
            // Check if status column exists
            $check_item_status = $conn->query("SHOW COLUMNS FROM asset_items LIKE 'status'");
            $item_has_status = $check_item_status && $check_item_status->num_rows > 0;
            
            // Office asset items - Enhanced with comprehensive status breakdown
            $office_assets_query = "SELECT 
                COUNT(*) as total_office_items" .
                ($item_has_status ? ",
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_assets,
                SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                SUM(CASE WHEN status = 'serviceable' THEN 1 ELSE 0 END) as serviceable_items,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                SUM(CASE WHEN status = 'unserviceable' THEN 1 ELSE 0 END) as unserviceable_items,
                SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_assets,
                SUM(CASE WHEN status = 'no_tag' THEN 1 ELSE 0 END) as no_tag_assets,
                SUM(CASE WHEN status = 'pending_tag' THEN 1 ELSE 0 END) as pending_tag_assets,
                SUM(CASE WHEN status = 'red_tagged' THEN 1 ELSE 0 END) as red_tagged_assets" : ",
                0 as available_assets,
                0 as in_use_assets,
                0 as serviceable_items,
                0 as maintenance_assets,
                0 as unserviceable_items,
                0 as disposed_assets,
                0 as no_tag_assets,
                0 as pending_tag_assets,
                0 as red_tagged_assets") . ",
                COALESCE(SUM(value), 0) as total_office_value
                FROM asset_items 
                WHERE office_id = ?";
            $stmt = $conn->prepare($office_assets_query);
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $office_assets_result = $stmt->get_result();
            if ($office_assets_result) {
                $office_asset_data = $office_assets_result->fetch_assoc();
                $stats = array_merge($stats, $office_asset_data);
            }
            
            // ===== OFFICE CONSUMABLES =====
            $consumables_query = "SELECT 
                COUNT(*) as office_consumables_count,
                SUM(quantity) as total_consumable_quantity,
                SUM(quantity * unit_cost) as total_consumable_value,
                SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_items
                FROM consumables 
                WHERE office_id = ?";
            $stmt = $conn->prepare($consumables_query);
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $consumables_result = $stmt->get_result();
            if ($consumables_result) {
                $stats = array_merge($stats, $consumables_result->fetch_assoc());
            }
        }
        
    } catch (Exception $e) {
        $stats['error'] = "Error fetching office stats: " . $e->getMessage();
        error_log("Office Dashboard Error: " . $e->getMessage());
    }
}

// Set default values if not set
$defaults = [
    'total_office_items' => 0, 'serviceable_items' => 0, 'unserviceable_items' => 0,
    'total_office_value' => 0, 'office_consumables_count' => 0, 'total_consumable_quantity' => 0,
    'total_consumable_value' => 0, 'low_stock_items' => 0, 'pending_requests' => 0,
    'consumable_requests' => 0, 'asset_requests' => 0, 'total_forms' => 0,
    'ics_forms' => 0, 'ris_forms' => 0,
    'total_incoming_requests' => 0, 'pending_incoming_requests' => 0, 'approved_incoming_requests' => 0,
    'denied_incoming_requests' => 0, 'returned_incoming_requests' => 0,
    'total_outgoing_requests' => 0, 'pending_outgoing_requests' => 0, 'approved_outgoing_requests' => 0,
    'denied_outgoing_requests' => 0, 'returned_outgoing_requests' => 0,
    'low_stock_details' => []
];

foreach ($defaults as $key => $value) {
    if (!isset($stats[$key])) {
        $stats[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Admin Dashboard - PIMS</title>
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
            border-left: 4px solid #5CC2F2;
        }
        
        .office-metric-card {
            background: linear-gradient(135deg, #5CC2F2 0%, #C1EAF2 100%);
            color: var(--dark-color);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(92, 194, 242, 0.3);
        }
        
        .office-metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(92, 194, 242, 0.3);
        }
        
        .office-metric-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #191BA9;
        }
        
        .office-metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.9);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .notification-dropdown {
            width: 350px;
            max-height: 400px;
            border: none;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            border-radius: var(--border-radius);
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .notification-item {
            border-bottom: 1px solid #f8f9fa;
        }
        
        .notification-item.unread {
            background-color: #e3f2fd;
            border-left: 3px solid #5CC2F2;
        }
        
        .notification-item .dropdown-item {
            white-space: normal;
            padding: 0.75rem 1rem;
            border-radius: 0;
        }
        
        .notification-loading {
            padding: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Office Dashboard';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-building"></i> Office Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0">Manage your office assets, consumables, and requests</p>
                    <?php if (isset($stats['error'])): ?>
                        <div class="alert alert-warning mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Database Warning:</strong> <?php echo htmlspecialchars($stats['error']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportData()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Office Overview Cards -->
        <div class="row mb-4 justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['total_office_items']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-box-seam"></i> Office Assets</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['office_consumables_count']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-archive"></i> Consumables</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['pending_requests']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-send"></i> Pending Requests</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mb-4 justify-content-center">
            <div class="col-12">
                <h5 class="mb-3">Quick Actions</h5>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="office_consumables.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-archive"></i>
                    </div>
                    <div class="quick-action-title">Consumables</div>
                    <div class="quick-action-desc">Track consumable usage</div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="office_assets.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="quick-action-title">View Assets</div>
                    <div class="quick-action-desc">Browse office assets</div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="office_reports.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="quick-action-title">Generate Report</div>
                    <div class="quick-action-desc">Office inventory reports</div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Sidebar Scripts -->
<script src="../assets/js/sidebar.js"></script>

<!-- Clean Notification Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Clean dashboard loaded - initializing notifications...');
    
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    
    if (!notificationBadge) {
        console.error('Notification badge not found!');
        return;
    }
    
    console.log('Notification elements found:', {
        badge: !!notificationBadge,
        dropdown: !!notificationDropdown,
        list: !!notificationList
    });
    
    // Update notification badge
    function updateNotificationBadge() {
        fetch('notifications_handler.php?action=get_count', {
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification count response:', data);
            const count = data.unread_count || 0;
            
            if (count > 0) {
                notificationBadge.textContent = count > 99 ? '99+' : count;
                notificationBadge.style.display = 'block';
                console.log('Badge updated to show:', count);
            } else {
                notificationBadge.style.display = 'none';
                console.log('Badge hidden (0 unread)');
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
            notificationBadge.textContent = '?';
            notificationBadge.style.display = 'block';
        });
    }
    
    // Load notifications when dropdown is clicked
    if (notificationDropdown && notificationList) {
        notificationDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Notification dropdown clicked');
            
            const dropdownMenu = notificationDropdown.nextElementSibling;
            if (dropdownMenu) {
                const isVisible = dropdownMenu.style.display !== 'none';
                dropdownMenu.style.display = isVisible ? 'none' : 'block';
                
                if (!isVisible) {
                    loadNotifications();
                }
            }
        });
    }
    
    function loadNotifications() {
        console.log('Loading notifications...');
        
        notificationList.innerHTML = '<div class="notification-loading"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        fetch('notifications_handler.php?action=get_notifications&limit=5', {
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications response:', data);
            
            if (!data.notifications || data.notifications.length === 0) {
                notificationList.innerHTML = '<li><a class="dropdown-item text-muted">No notifications</a></li>';
                return;
            }
            
            let html = '';
            data.notifications.forEach(notification => {
                const unreadClass = notification.is_read ? '' : 'unread';
                html += '<li class="notification-item ' + unreadClass + '"><a class="dropdown-item" href="' + notification.action_url + '"><div class="fw-bold">' + notification.title + '</div><div class="small text-muted">' + notification.message + '</div><div class="small text-muted">' + notification.time_ago + '</div>' + (!notification.is_read ? '<span class="badge bg-primary ms-2">New</span>' : '') + '</a></li>';
            });
            
            notificationList.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            notificationList.innerHTML = '<li><a class="dropdown-item text-muted">Error loading notifications</a></li>';
        });
    }
    
    // Initial update
    updateNotificationBadge();
    
    // Auto-refresh every 30 seconds
    setInterval(updateNotificationBadge, 30000);
    
    console.log('Clean notification system initialized');
});

function refreshDashboard() {
    location.reload();
}

function exportData() {
    window.open('export_office_data.php', '_blank');
}
</script>
</body>
</html>
