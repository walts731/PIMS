<?php
session_start();
require_once 'config.php';
require_once 'includes/system_functions.php';
require_once 'includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Get system settings for branding
$system_name = "PIMS";
$system_logo = "assets/images/logo.png";

try {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_name'");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $system_name = $row['setting_value'];
    }
    
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_logo'");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $system_logo = $row['setting_value'];
    }
} catch (Exception $e) {
    // Use defaults if settings not found
}

$pageTitle = "User Manual";
$currentPage = 'user_manual';

// Determine which section to include based on user role
$userRole = $_SESSION['role'] ?? '';
$isAdmin = ($userRole === 'admin' || $userRole === 'system_admin');
$isSystemAdmin = ($userRole === 'system_admin');

// Log user manual access
logSystemAction($_SESSION['user_id'], 'access', 'user_manual', 'User accessed manual');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo $system_name; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php require_once 'ADMIN/includes/dark-mode-init.php'; ?>
    
    <style>
        .manual-sidebar {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .manual-sidebar h5 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .manual-content {
            padding: 0;
        }
        
        .manual-section {
            margin-bottom: 30px;
            padding: 30px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .manual-section h2 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .manual-section h3 {
            color: #34495e;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        
        .manual-section h4 {
            color: #5d6d7e;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            color: #3498db;
            margin-right: 10px;
        }
        
        .step-list {
            counter-reset: step-counter;
            list-style: none;
            padding: 0;
        }
        
        .step-list li {
            counter-increment: step-counter;
            position: relative;
            padding: 15px 0 15px 50px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .step-list li::before {
            content: counter(step-counter);
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #3498db;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        
        .highlight-box {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
        
        .nav-link {
            color: #495057;
            padding: 8px 15px;
            margin: 2px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: #e9ecef;
            color: #3498db;
        }
        
        .nav-link.active {
            background: #3498db;
            color: white;
        }
        
        .screenshot-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            padding: 40px;
            text-align: center;
            color: #6c757d;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .manual-sidebar {
                position: static;
                margin-bottom: 20px;
            }
            
            .manual-content {
                padding-left: 0;
            }
        }
        
        /* Dark mode styles */
        body.dark-mode .manual-sidebar {
            background: #1f2937;
            border-color: #374151;
        }
        
        body.dark-mode .manual-sidebar h5 {
            color: #f3f4f6;
            border-bottom-color: #3b82f6;
        }
        
        body.dark-mode .manual-section {
            background: #1f2937;
            color: #e5e7eb;
            border-color: #374151;
        }
        
        body.dark-mode .manual-section h2 {
            color: #60a5fa;
            border-bottom-color: #3b82f6;
        }
        
        body.dark-mode .manual-section h3 {
            color: #93c5fd;
        }
        
        body.dark-mode .manual-section h4 {
            color: #cbd5e1;
        }
        
        body.dark-mode .feature-list li {
            border-bottom-color: #374151;
        }
        
        body.dark-mode .step-list li {
            background: #374151;
            color: #e5e7eb;
        }
        
        body.dark-mode .highlight-box {
            background: #1e3a8a;
            border-left-color: #3b82f6;
        }
        
        body.dark-mode .warning-box {
            background: #92400e;
            border-left-color: #f59e0b;
        }
        
        body.dark-mode .success-box {
            background: #14532d;
            border-left-color: #10b981;
        }
        
        body.dark-mode .screenshot-placeholder {
            background: #374151;
            border-color: #4b5563;
            color: #9ca3af;
        }
        
        body.dark-mode .nav-link {
            color: #d1d5db;
        }
        
        body.dark-mode .nav-link:hover {
            background: #374151;
            color: #60a5fa;
        }
        
        body.dark-mode .nav-link.active {
            background: #3b82f6;
            color: white;
        }
    </style>
</head>
<body>
    <?php $page_title = 'User Manual'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <!-- Sidebar Toggle -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Custom Sidebar for User Manual -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="<?php echo $system_logo; ?>" alt="<?php echo $system_name; ?>" class="logo-img">
                    <span class="logo-text"><?php echo $system_name; ?></span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link active">
                            <i class="bi bi-book"></i>
                            <span>User Manual</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <!-- Custom Topbar -->
        <nav class="topbar">
            <div class="topbar-left">
                <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-item">
                    <button class="btn btn-icon dark-mode-toggle" id="darkModeToggle">
                        <i class="bi bi-moon"></i>
                    </button>
                </div>
                
                <div class="topbar-item dropdown">
                    <button class="user-dropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span><?php echo $_SESSION['username']; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    
        <div class="main-content">
            <div class="page-header">
                <div class="page-header-content">
                    <div class="page-title">
                        <h1 class="h2 mb-0">
                            <i class="bi bi-book me-2"></i>
                            User Manual
                        </h1>
                        <p class="text-muted mb-0">Complete guide for the Pilar Inventory Management System</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Manual
                        </button>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <!-- Sidebar Navigation -->
                    <div class="col-md-3">
                        <div class="manual-sidebar">
                            <h5 class="mb-3">Table of Contents</h5>
                            <nav class="nav flex-column">
                                <a class="nav-link active" href="#overview">System Overview</a>
                                <a class="nav-link" href="#getting-started">Getting Started</a>
                                <a class="nav-link" href="#dashboard">Dashboard</a>
                                <?php if ($isAdmin): ?>
                                <a class="nav-link" href="#asset-management">Asset Management</a>
                                <a class="nav-link" href="#inventory">Inventory Management</a>
                                <a class="nav-link" href="#employees">Employee Management</a>
                                <a class="nav-link" href="#reports">Reports</a>
                                <?php endif; ?>
                                <?php if ($isSystemAdmin): ?>
                                <a class="nav-link" href="#system-admin">System Administration</a>
                                <a class="nav-link" href="#offices">Office Management</a>
                                <a class="nav-link" href="#categories">Category Management</a>
                                <a class="nav-link" href="#users">User Management</a>
                                <a class="nav-link" href="#settings">System Settings</a>
                                <?php endif; ?>
                                <a class="nav-link" href="#troubleshooting">Troubleshooting</a>
                                <a class="nav-link" href="#faq">FAQ</a>
                            </nav>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="col-md-9">
                        <div class="manual-content">
                    <!-- System Overview -->
                    <section id="overview" class="manual-section">
                        <h2><i class="bi bi-info-circle"></i> System Overview</h2>
                        
                        <h3>What is PIMS?</h3>
                        <p>The Pilar Inventory Management System (PIMS) is a comprehensive solution designed to help the Municipality of Pilar efficiently track, manage, and report on their physical assets and inventory items. This system provides real-time visibility into asset locations, conditions, and lifecycle management.</p>
                        
                        <h3>Key Features</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle-fill"></i> <strong>Asset Tracking:</strong> Complete lifecycle management of physical assets</li>
                            <li><i class="bi bi-check-circle-fill"></i> <strong>Inventory Management:</strong> Real-time inventory tracking and stock management</li>
                            <li><i class="bi bi-check-circle-fill"></i> <strong>Employee Assignment:</strong> Track asset assignments to employees</li>
                            <li><i class="bi bi-check-circle-fill"></i> <strong>QR Code Support:</strong> Generate and scan QR codes for quick asset identification</li>
                            <li><i class="bi bi-check-circle-fill"></i> <strong>Reporting:</strong> Comprehensive reports and analytics</li>
                            <li><i class="bi bi-check-circle-fill"></i> <strong>Multi-User Support:</strong> Role-based access control</li>
                            <li><i class="bi bi-check-circle-fill"></i> <strong>Dark Mode:</strong> Eye-friendly interface option</li>
                        </ul>
                        
                        <h3>System Architecture</h3>
                        <p>PIMS is built on a modern web architecture with:</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-database"></i> MySQL database for data storage</li>
                            <li><i class="bi bi-code-slash"></i> PHP backend for business logic</li>
                            <li><i class="bi bi-palette"></i> Bootstrap 5 for responsive design</li>
                            <li><i class="bi bi-shield-check"></i> Secure authentication and authorization</li>
                        </ul>
                    </section>

                    <!-- Getting Started -->
                    <section id="getting-started" class="manual-section">
                        <h2><i class="bi bi-rocket-takeoff"></i> Getting Started</h2>
                        
                        <h3>System Requirements</h3>
                        <div class="highlight-box">
                            <h5>Browser Requirements:</h5>
                            <ul>
                                <li>Chrome 90+ or Firefox 88+ or Safari 14+ or Edge 90+</li>
                                <li>JavaScript must be enabled</li>
                                <li>Cookies must be enabled for session management</li>
                            </ul>
                        </div>
                        
                        <h3>First-Time Login</h3>
                        <ol class="step-list">
                            <li>Open your web browser and navigate to the PIMS URL</li>
                            <li>Enter your username and password provided by the system administrator</li>
                            <li>Click the "Login" button</li>
                            <li>You will be redirected to the dashboard based on your user role</li>
                            <li>Change your password if prompted for security reasons</li>
                        </ol>
                        
                        <h3>Navigation Basics</h3>
                        <div class="screenshot-placeholder">
                            <i class="bi bi-image" style="font-size: 3rem;"></i>
                            <p>Navigation Menu Screenshot</p>
                        </div>
                        
                        <ul class="feature-list">
                            <li><i class="bi bi-house"></i> <strong>Dashboard:</strong> Main overview page with statistics and quick access</li>
                            <li><i class="bi bi-box"></i> <strong>Assets:</strong> Manage physical assets and equipment</li>
                            <li><i class="bi bi-archive"></i> <strong>Inventory:</strong> Track consumable and non-consumable items</li>
                            <li><i class="bi bi-people"></i> <strong>Employees:</strong> Manage employee records and assignments</li>
                            <li><i class="bi bi-file-text"></i> <strong>Reports:</strong> Generate various reports and analytics</li>
                        </ul>
                        
                        <h3>User Roles</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5><i class="bi bi-person"></i> Regular User</h5>
                                    <ul>
                                        <li>View assigned assets</li>
                                        <li>View asset details</li>
                                        <li>View basic reports</li>
                                        <li><strong>Viewing only - No editing permissions</strong></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5><i class="bi bi-person-badge"></i> Admin</h5>
                                    <ul>
                                        <li>All user permissions</li>
                                        <li>Manage assets and inventory</li>
                                        <li>Manage employees</li>
                                        <li>Generate reports</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5><i class="bi bi-person-gear"></i> System Admin</h5>
                                    <ul>
                                        <li>All admin permissions</li>
                                        <li>System configuration</li>
                                        <li>User management</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Dashboard -->
                    <section id="dashboard" class="manual-section">
                        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
                        
                        <h3>Dashboard Overview</h3>
                        <p>The dashboard provides a comprehensive overview of your system's status and key metrics. It's the first page you see after logging in.</p>
                        
                        <h3>Key Components</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-graph-up"></i> <strong>Statistics Cards:</strong> Display key metrics like total assets, inventory value, etc.</li>
                            <li><i class="bi bi-box-seam"></i> <strong>Asset Categories:</strong> Quick overview of asset distribution by category</li>
                            <li><i class="bi bi-clock-history"></i> <strong>Recent Activities:</strong> Timeline of recent system actions</li>
                            <li><i class="bi bi-grid-3x3-gap"></i> <strong>Quick Actions:</strong> Fast access to common tasks</li>
                        </ul>
                        
                        <h3>Understanding Statistics</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="highlight-box">
                                    <h5>Asset Statistics</h5>
                                    <ul>
                                        <li><strong>Total Assets:</strong> Count of all registered assets</li>
                                        <li><strong>Active Assets:</strong> Currently in-use assets</li>
                                        <li><strong>Unserviceable:</strong> Decommissioned assets</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="highlight-box">
                                    <h5>Inventory Statistics</h5>
                                    <ul>
                                        <li><strong>Total Items:</strong> All inventory items count</li>
                                        <li><strong>In Stock:</strong> Available items</li>
                                        <li><strong>Low Stock:</strong> Items below reorder level</li>
                                        <li><strong>Total Value:</strong> Current inventory value</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <h3>Recent Activities</h3>
                        <p>The activity feed shows the most recent actions performed in the system, including:</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-plus-circle"></i> New asset additions</li>
                            <li><i class="bi bi-pencil"></i> Asset modifications</li>
                            <li><i class="bi bi-arrow-left-right"></i> Asset transfers</li>
                            <li><i class="bi bi-person-plus"></i> Employee assignments</li>
                            <li><i class="bi bi-download"></i> Report generations</li>
                        </ul>
                    </section>

                    <?php if ($isAdmin): ?>
                    <!-- Asset Management -->
                    <section id="asset-management" class="manual-section">
                        <h2><i class="bi bi-box"></i> Asset Management</h2>
                        
                        <h3>Asset Overview</h3>
                        <p>Asset Management allows you to track all physical assets within your organization, from acquisition to disposal.</p>
                        
                        <h3>Adding New Assets</h3>
                        <ol class="step-list">
                            <li>Navigate to <strong>Assets → Add Asset</strong></li>
                            <li>Fill in the asset information form:
                                <ul>
                                    <li>Asset name and description</li>
                                    <li>Category and subcategory</li>
                                    <li>Serial number and property number</li>
                                    <li>Purchase information</li>
                                    <li>Location and assignment</li>
                                </ul>
                            </li>
                            <li>Upload asset photos (optional)</li>
                            <li>Click "Save Asset" to create the record</li>
                        </ol>
                        
                        <h3>Asset Categories</h3>
                        <p>Assets are organized into categories for better management:</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-laptop"></i> <strong>IT Equipment:</strong> Computers, laptops, servers</li>
                            <li><i class="bi bi-building"></i> <strong>Furniture:</strong> Desks, chairs, cabinets</li>
                            <li><i class="bi bi-truck"></i> <strong>Vehicles:</strong> Cars, trucks, equipment</li>
                            <li><i class="bi bi-tools"></i> <strong>Tools:</strong> Hand tools, power tools</li>
                            <li><i class="bi bi-other"></i> <strong>Other:</strong> Miscellaneous assets</li>
                        </ul>
                        
                        <h3>Asset Lifecycle</h3>
                        <div class="success-box">
                            <h5>Asset Status Flow:</h5>
                            <p><strong>No Tag → Serviceable → In Use → Serviceable</strong></p>
                            <p><strong>Serviceable → Borrowed → Serviceable</strong></p>
                            <p>Or: <strong>Serviceable → Unserviceable → Red Tagged → Disposed</strong></p>
                        </div>
                        
                        <h3>QR Code Generation</h3>
                        <p>Each asset automatically gets a QR code for easy identification:</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-qr-code"></i> QR codes are generated automatically when assets are created</li>
                            <li><i class="bi bi-phone"></i> Use mobile device to scan QR codes</li>
                            <li><i class="bi bi-link"></i> QR codes link directly to asset details</li>
                            <li><i class="bi bi-printer"></i> Print QR codes for physical tagging</li>
                        </ul>
                        
                        <h3>Asset Assignment</h3>
                        <ol class="step-list">
                            <li>Select an asset from the asset list</li>
                            <li>Click "Assign to Employee"</li>
                            <li>Choose the employee from dropdown</li>
                            <li>Set assignment date and notes</li>
                            <li>Click "Assign Asset"</li>
                        </ol>
                        
                    </section>

                    <!-- Inventory Management -->
                    <section id="inventory" class="manual-section">
                        <h2><i class="bi bi-archive"></i> Inventory Management</h2>
                        
                        <h3>Inventory Overview</h3>
                        <p>Inventory Management handles consumable and non-consumable items that need stock tracking.</p>
                        
                        <h3>Adding Inventory Items</h3>
                        <ol class="step-list">
                            <li>Navigate to <strong>Inventory → Add Item</strong></li>
                            <li>Enter item details:
                                <ul>
                                    <li>Item name and description</li>
                                    <li>Category and unit of measure</li>
                                    <li>Current quantity and reorder level</li>
                                    <li>Unit cost and supplier information</li>
                                </ul>
                            </li>
                            <li>Save the item to inventory</li>
                        </ol>
                        
                        <h3>Stock Management</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="highlight-box">
                                    <h5>Stock In Operations</h5>
                                    <ul>
                                        <li>Purchase receipts</li>
                                        <li>Return from projects</li>
                                        <li>Transfer from other locations</li>
                                        <li>Physical count adjustments</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="highlight-box">
                                    <h5>Stock Out Operations</h5>
                                    <ul>
                                        <li>Issuance to departments</li>
                                        <li>Project allocations</li>
                                        <li>Damaged/lost items</li>
                                        <li>Transfers to other locations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <h3>Low Stock Alerts</h3>
                        <div class="warning-box">
                            <h5><i class="bi bi-exclamation-triangle"></i> Low Stock Management</h5>
                            <p>The system automatically alerts when items fall below their reorder level:</p>
                            <ul>
                                <li>Visual indicators on inventory list</li>
                                <li>Email notifications (if configured)</li>
                                <li>Dashboard alerts</li>
                                <li>Reorder reports</li>
                            </ul>
                        </div>
                        
                        <h3>Inventory Reports</h3>
                        <p>Generate various inventory reports:</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-file-earmark-text"></i> Stock status reports</li>
                            <li><i class="bi bi-graph-down"></i> Consumption reports</li>
                            <li><i class="bi bi-currency-dollar"></i> Value analysis reports</li>
                            <li><i class="bi bi-arrow-repeat"></i> Movement history reports</li>
                        </ul>
                    </section>

                    <!-- Employee Management -->
                    <section id="employees" class="manual-section">
                        <h2><i class="bi bi-people"></i> Employee Management</h2>
                        
                        <h3>Employee Records</h3>
                        <p>Manage employee information and track asset assignments to individuals.</p>
                        
                        <h3>Adding Employees</h3>
                        <ol class="step-list">
                            <li>Navigate to <strong>Employees → Add Employee</strong></li>
                            <li>Fill in employee information:
                                <ul>
                                    <li>Personal details (name, contact info)</li>
                                    <li>Employee details (ID, department, position)</li>
                                    <li>Employment information (status, hire date)</li>
                                </ul>
                            </li>
                            <li>Upload employee photo (optional)</li>
                            <li>Save the employee record</li>
                        </ol>
                        
                        <h3>Asset Assignment Tracking</h3>
                        <p>Track which assets are assigned to each employee:</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-person-box"></i> View current assignments</li>
                            <li><i class="bi bi-clock-history"></i> Assignment history</li>
                            <li><i class="bi bi-check-circle"></i> Return processing</li>
                            <li><i class="bi bi-file-text"></i> Assignment reports</li>
                        </ul>
                        
                        <h3>Employee Status</h3>
                        <div class="highlight-box">
                            <h5>Employment Status Types:</h5>
                            <ul>
                                <li><strong>Cleared:</strong> Employee has no assigned assets</li>
                                <li><strong>Uncleared:</strong> Employee has assigned assets</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Reports -->
                    <section id="reports" class="manual-section">
                        <h2><i class="bi bi-file-text"></i> Reports</h2>
                        
                        <h3>Report Overview</h3>
                        <p>The Reports module provides comprehensive analytics and reporting capabilities for informed decision-making.</p>
                        
                        <h3>Available Reports</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Asset Reports</h5>
                                <ul class="feature-list">
                                    <li><i class="bi bi-list-ul"></i> Asset Inventory List</li>
                                    <li><i class="bi bi-geo-alt"></i> Asset Location Report</li>
                                    <li><i class="bi bi-person"></i> Asset Assignment Report</li>
                                    <li><i class="bi bi-graph-up"></i> Asset Value Report</li>
                                    <li><i class="bi bi-calendar"></i> Asset Age Analysis</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Inventory Reports</h5>
                                <ul class="feature-list">
                                    <li><i class="bi bi-boxes"></i> Stock Status Report</li>
                                    <li><i class="bi bi-exclamation-triangle"></i> Low Stock Report</li>
                                    <li><i class="bi bi-arrow-down"></i> Consumption Report</li>
                                    <li><i class="bi bi-currency-dollar"></i> Inventory Value Report</li>
                                    <li><i class="bi bi-clock-history"></i> Movement History</li>
                                </ul>
                            </div>
                        </div>
                        
                        <h3>Generating Reports</h3>
                        <ol class="step-list">
                            <li>Select the report type from the Reports menu</li>
                            <li>Configure report parameters:
                                <ul>
                                    <li>Date range</li>
                                    <li>Department/Office filter</li>
                                    <li>Category filter</li>
                                    <li>Status filter</li>
                                </ul>
                            </li>
                            <li>Choose output format (PDF, Excel, CSV)</li>
                            <li>Click "Generate Report"</li>
                            <li>Download or print the report</li>
                        </ol>
                        
                    </section>
                    <?php endif; ?>

                    <?php if ($isSystemAdmin): ?>
                    <!-- System Administration -->
                    <section id="system-admin" class="manual-section">
                        <h2><i class="bi bi-gear"></i> System Administration</h2>
                        
                        <h3>Admin Overview</h3>
                        <p>System Administration provides tools for managing the PIMS system configuration and users.</p>
                        
                        <h3>System Health</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-heart-pulse"></i> Database status monitoring</li>
                            <li><i class="bi bi-hdd"></i> Storage usage tracking</li>
                            <li><i class="bi bi-speedometer2"></i> Performance metrics</li>
                            <li><i class="bi bi-shield-check"></i> Security audit logs</li>
                        </ul>
                        
                        <h3>Backup Management</h3>
                        <ol class="step-list">
                            <li>Navigate to <strong>System Admin → Backup</strong></li>
                            <li>Choose backup type:
                                <ul>
                                    <li>Full backup (database + files)</li>
                                    <li>Database only</li>
                                    <li>Files only</li>
                                </ul>
                            </li>
                            <li>Set backup schedule (optional)</li>
                            <li>Click "Create Backup"</li>
                            <li>Download backup file when ready</li>
                        </ol>
                        
                        <h3>System Logs</h3>
                        <div class="highlight-box">
                            <h5>Log Categories:</h5>
                            <ul>
                                <li><strong>System Logs:</strong> General system activities</li>
                                <li><strong>Login Logs:</strong> User authentication attempts</li>
                                <li><strong>Security Logs:</strong> Security-related events</li>
                                <li><strong>Error Logs:</strong> System errors and exceptions</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Office Management -->
                    <section id="offices" class="manual-section">
                        <h2><i class="bi bi-building"></i> Office Management</h2>
                        
                        <h3>Office Structure</h3>
                        <p>Organize your physical locations and organizational hierarchy through office management.</p>
                        
                        <h3>Office Types</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5>Main Offices</h5>
                                    <p>Primary organizational units (HO, 001, 002, etc.)</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5>Barangays</h5>
                                    <p>Local government units (B001, B002, etc.)</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5>Locations</h5>
                                    <p>Physical locations (L001, L002, etc.)</p>
                                </div>
                            </div>
                        </div>
                        
                        <h3>Office Hierarchy</h3>
                        <p>Offices can be organized in a hierarchical structure:</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-diagram-3"></i> Parent-child relationships</li>
                            <li><i class="bi bi-arrow-down"></i> Multi-level nesting</li>
                            <li><i class="bi bi-diagram-2"></i> Organizational chart view</li>
                        </ul>
                        
                        <h3>Import/Export Offices</h3>
                        <div class="success-box">
                            <h5>Bulk Operations</h5>
                            <p>Import offices from CSV files with complete data including:</p>
                            <ul>
                                <li>Office details (name, code, address)</li>
                                <li>Contact information (phone, email)</li>
                                <li>Capacity and status</li>
                                <li>Parent office relationships</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Category Management -->
                    <section id="categories" class="manual-section">
                        <h2><i class="bi bi-tags"></i> Category Management</h2>
                        
                        <h3>Category Organization</h3>
                        <p>Categories help organize assets and inventory into logical groups for better management.</p>
                        
                        <h3>Category Structure</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-folder"></i> <strong>Main Categories:</strong> High-level groupings</li>
                            <li><i class="bi bi-folder2"></i> <strong>Subcategories:</strong> More specific classifications</li>
                            <li><i class="bi bi-tags"></i> <strong>Tags:</strong> Additional classification options</li>
                        </ul>
                        
                        <h3>Default Categories</h3>
                        <p>System comes with pre-configured categories:</p>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="feature-list">
                                    <li>IT Equipment</li>
                                    <li>Furniture & Fixtures</li>
                                    <li>Vehicles</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="feature-list">
                                    <li>Tools & Equipment</li>
                                    <li>Office Supplies</li>
                                    <li>Other Assets</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- User Management -->
                    <section id="users" class="manual-section">
                        <h2><i class="bi bi-people"></i> User Management</h2>
                        
                        <h3>User Accounts</h3>
                        <p>Manage system user accounts and permissions.</p>
                        
                        <h3>User Roles</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5>Regular User</h5>
                                    <ul>
                                        <li>View assigned assets</li>
                                        <li>View asset details</li>
                                        <li>View basic reports</li>
                                        <li><strong>Viewing only - No editing permissions</strong></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5>Admin</h5>
                                    <ul>
                                        <li>Asset management</li>
                                        <li>Inventory control</li>
                                        <li>Employee management</li>
                                        <li>Reporting</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5>System Admin</h5>
                                    <ul>
                                        <li>All admin functions</li>
                                        <li>User management</li>
                                        <li>System settings</li>
                                        <li>Security management</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <h3>Creating Users</h3>
                        <ol class="step-list">
                            <li>Navigate to <strong>System Admin → User Management</strong></li>
                            <li>Click "Add New User"</li>
                            <li>Fill in user information:
                                <ul>
                                    <li>Personal details</li>
                                    <li>Login credentials</li>
                                    <li>Role assignment</li>
                                    <li>Department/office assignment</li>
                                </ul>
                            </li>
                            <li>Set initial password</li>
                                    <li>Send account details to user</li>
                            <li>Save user account</li>
                        </ol>
                        
                        <h3>Security Settings</h3>
                        <div class="warning-box">
                            <h5>Security Best Practices</h5>
                            <ul>
                                <li>Enforce strong password policies</li>
                                <li>Set appropriate session timeouts</li>
                                <li>Monitor login attempts</li>
                                <li>Regular password changes</li>
                                <li>Disable inactive accounts</li>
                            </ul>
                        </div>
                    </section>

                    <!-- System Settings -->
                    <section id="settings" class="manual-section">
                        <h2><i class="bi bi-gear"></i> System Settings</h2>
                        
                        <h3>Settings Overview</h3>
                        <p>Configure system-wide settings to customize PIMS for your organization.</p>
                        
                        <h3>General Settings</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-building"></i> <strong>System Information:</strong> Name, logo, email</li>
                            <li><i class="bi bi-clock"></i> <strong>Session Settings:</strong> Timeout, auto-save</li>
                            <li><i class="bi bi-palette"></i> <strong>Appearance:</strong> Dark mode, items per page</li>
                            <li><i class="bi bi-calendar"></i> <strong>Date/Time:</strong> Format preferences</li>
                        </ul>
                        
                        <h3>Security Settings</h3>
                        <div class="highlight-box">
                            <h5>Security Configuration</h5>
                            <ul>
                                <li><strong>Password Policy:</strong> Length, complexity requirements</li>
                                <li><strong>Login Attempts:</strong> Maximum failed attempts</li>
                                <li><strong>Session Timeout:</strong> Inactivity timeout duration</li>
                                <li><strong>Two-Factor Auth:</strong> Additional security layer</li>
                            </ul>
                        </div>
                        
                        <h3>Backup Settings</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-clock-history"></i> <strong>Automatic Backups:</strong> Schedule configuration</li>
                            <li><i class="bi bi-hdd"></i> <strong>Storage Location:</strong> Backup destination</li>
                            <li><i class="bi bi-calendar-check"></i> <strong>Retention Policy:</strong> How long to keep backups</li>
                            <li><i class="bi bi-envelope"></i> <strong>Notifications:</strong> Backup status alerts</li>
                        </ul>
                    </section>
                    <?php endif; ?>

                    <!-- Troubleshooting -->
                    <section id="troubleshooting" class="manual-section">
                        <h2><i class="bi bi-tools"></i> Troubleshooting</h2>
                        
                        <h3>Common Issues</h3>
                        
                        <h4>Login Problems</h4>
                        <div class="warning-box">
                            <h5>Cannot Login</h5>
                            <ol class="step-list">
                                <li>Check username and password spelling</li>
                                <li>Verify Caps Lock is off</li>
                                <li>Clear browser cache and cookies</li>
                                <li>Try a different browser</li>
                                <li>Contact system administrator</li>
                            </ol>
                        </div>
                        
                        <h4>Performance Issues</h4>
                        <div class="highlight-box">
                            <h5>System Running Slow</h5>
                            <ul>
                                <li>Check internet connection speed</li>
                                <li>Clear browser cache</li>
                                <li>Close unnecessary browser tabs</li>
                                <li>Restart browser</li>
                            </ul>
                        </div>
                        
                        <h4>Data Issues</h4>
                        <div class="warning-box">
                            <h5>Missing or Incorrect Data</h5>
                            <ol class="step-list">
                                <li>Verify data entry was saved</li>
                                <li>Check for duplicate entries</li>
                                <li>Review import/export logs</li>
                                <li>Contact admin for data verification</li>
                            </ol>
                        </div>
                        
                        <h3>Error Messages</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="highlight-box">
                                    <h5>Common Error Codes</h5>
                                    <ul>
                                        <li><strong>401 Unauthorized:</strong> Login required</li>
                                        <li><strong>403 Forbidden:</strong> Insufficient permissions</li>
                                        <li><strong>404 Not Found:</strong> Page doesn't exist</li>
                                        <li><strong>500 Server Error:</strong> Contact admin</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="highlight-box">
                                    <h5>Database Errors</h5>
                                    <ul>
                                        <li><strong>Connection Failed:</strong> Check network</li>
                                        <li><strong>Query Failed:</strong> Contact admin</li>
                                        <li><strong>Timeout:</strong> Try again later</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <h3>Getting Help</h3>
                        <div class="success-box">
                            <h5>Support Channels</h5>
                            <ul>
                                <li><strong>System Administrator:</strong> Primary contact for issues</li>
                                <li><strong>Help Desk:</strong> For routine problems</li>
                                <li><strong>IT Department:</strong> For technical issues</li>
                                <li><strong>User Manual:</strong> This document for reference</li>
                            </ul>
                        </div>
                    </section>

                    <!-- FAQ -->
                    <section id="faq" class="manual-section">
                        <h2><i class="bi bi-question-circle"></i> Frequently Asked Questions</h2>
                        
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        How do I reset my password?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        You can reset your password by accessing the Profile page. Navigate to your profile and use the password reset option. If you cannot access your account, contact your system administrator for assistance.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Can I access the system from mobile devices?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes, PIMS is fully responsive and works on mobile devices. However, some features like QR code scanning work best on mobile devices with cameras.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        How often should I back up the system?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        It's recommended to perform daily backups for critical data. Weekly full backups are suggested for comprehensive protection. Configure automatic backups in System Settings.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        What happens to assets when an employee leaves?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        When an employee leaves, the employee status will be updated to "Cleared" if the assets are transferred. Once all assets are transferred and the employee is cleared, the employment status can be updated to "Retired" or "Resigned". The system maintains a history of all assignments for audit purposes.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                        How do I generate reports?
                                    </button>
                                </h2>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Navigate to Reports → Generate Reports. You can select report type, set filters, choose date ranges, and download in various formats.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                        Is there a limit to the number of assets I can add?
                                    </button>
                                </h2>
                                <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        There is no built-in limit to the number of assets. However, system performance may be affected with very large datasets. Consider archiving old assets if needed.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                        How secure is my data in PIMS?
                                    </button>
                                </h2>
                                <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        PIMS includes multiple security layers: encrypted passwords, role-based access control, audit logging, and secure session management. Regular backups and security updates are recommended.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainWrapper = document.getElementById('mainWrapper');
            
            // Desktop sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    mainWrapper.classList.toggle('sidebar-collapsed');
                });
            }
            
            // Mobile sidebar toggle
            if (mobileSidebarToggle) {
                mobileSidebarToggle.addEventListener('click', function() {
                    mainWrapper.classList.toggle('sidebar-mobile-open');
                });
            }
            
            // Close mobile sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && 
                    !sidebar.contains(e.target) && 
                    !mobileSidebarToggle.contains(e.target)) {
                    mainWrapper.classList.remove('sidebar-mobile-open');
                }
            });
        });
    </script>
    
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update active nav link
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });
        
        // Update active navigation based on scroll position
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.manual-section');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
        
        // Print functionality
        function printManual() {
            window.print();
        }
        
        // Expand/Collapse all sections
        function toggleAllSections() {
            const accordions = document.querySelectorAll('.accordion-collapse');
            const allExpanded = Array.from(accordions).every(acc => acc.classList.contains('show'));
            
            accordions.forEach(accordion => {
                if (allExpanded) {
                    accordion.classList.remove('show');
                } else {
                    accordion.classList.add('show');
                }
            });
        }
    </script>
    
    <style>
        @media print {
            .sidebar, .topbar, .sidebar-toggle, .page-header, .no-print {
                display: none !important;
            }
            
            .main-wrapper {
                margin-left: 0 !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            .manual-sidebar {
                display: none !important;
            }
            
            .manual-content {
                padding-left: 0 !important;
            }
            
            .manual-section {
                break-inside: avoid;
                page-break-inside: avoid;
                margin-bottom: 20px;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .container-fluid {
                padding: 0;
                max-width: 100%;
            }
            
            .row {
                margin: 0;
            }
            
            .col-md-3 {
                display: none !important;
            }
            
            .col-md-9 {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
        }
    </style>
</body>
</html>
