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

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../SYSTEM_ADMIN/assets/css/admin-unified.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php require_once 'includes/dark-mode-init.php'; ?>
    
    <style>
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
            border-radius: 0 5px 5px 0;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
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
        
        /* Dark mode styles */
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
    </style>
</head>
<body>
    <?php $page_title = 'User Manual'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php
        // Include sidebar and topbar from ADMIN directory
        require_once 'includes/sidebar-toggle.php';
        require_once 'includes/sidebar.php';
        require_once 'includes/topbar.php';
        ?>
    
        <div class="main-content">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-book"></i> User Manual
                        </h1>
                        <p class="text-muted mb-0">Complete guide for the Pilar Inventory Management System</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <form id="pdfForm" action="../generate_user_manual_pdf.php" method="POST" target="_blank" style="display: inline;">
                                <input type="hidden" name="action" value="generate_pdf">
                                <input type="hidden" name="user_role" value="<?php echo $userRole; ?>">
                                <input type="hidden" name="system_name" value="<?php echo $system_name; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-pdf"></i> Generate PDF
                                </button>
                            </form>
                            <button class="btn btn-info btn-sm" onclick="openHelpModal()">
                                <i class="bi bi-question-circle"></i> Help & Support
                            </button>
                        </div>
                    </div>
                </div>
            </div>

                
        <div class="row">
            <div class="col-lg-12">
                <div class="row g-3 mb-4">
                    <div class="col-lg-12">
                        <div class="section-card">
                            <div class="section-title">
                                <i class="bi bi-book"></i> User Manual Contents
                            </div>
                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="section-card mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-list"></i> Table of Contents
                                        </div>
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
                                
                                <div class="col-lg-9">
                                <div class="section-card mb-4">
                                    <!-- System Overview -->
                                    <section id="overview" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-info-circle"></i> System Overview
                                        </div>
                        
                        <h3>What is PIMS?</h3>
                        <p>The Pilar Inventory Management System (PIMS) is a comprehensive solution designed to help Municipality of Pilar efficiently track, manage, and report on their physical assets and inventory items. This system provides real-time visibility into asset locations, conditions, and lifecycle management.</p>
                        
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
                                    <section id="getting-started" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-rocket-takeoff"></i> Getting Started
                                        </div>
                        
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
                            <li>Open your web browser and navigate to PIMS URL</li>
                            <li>Enter your username and password provided by system administrator</li>
                            <li>Click "Login" button</li>
                            <li>You will be redirected to dashboard based on your user role</li>
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
                                    <section id="dashboard" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-speedometer2"></i> Dashboard
                                        </div>
                        
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
                                    <section id="asset-management" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-box"></i> Asset Management
                                        </div>
                        
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
                                    <section id="inventory" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-archive"></i> Inventory Management
                                        </div>
                        
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
                                    <section id="employees" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-people"></i> Employee Management
                                        </div>
                        
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
                                    <section id="reports" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-file-text"></i> Reports
                                        </div>
                        
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
                                    <li>Filters (category, office, etc.)</li>
                                    <li>Output format (PDF, Excel, CSV)</li>
                                </ul>
                            </li>
                            <li>Click "Generate Report"</li>
                            <li>Download or print the generated report</li>
                        </ol>
                    </section>
                    <?php endif; ?>

                    <?php if ($isSystemAdmin): ?>
                                    <!-- System Administration -->
                                    <section id="system-admin" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-gear"></i> System Administration
                                        </div>
                        
                        <h3>System Administration Overview</h3>
                        <p>System Administration provides tools for managing the PIMS system configuration and users.</p>
                        
                        <h3>System Health</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-activity"></i> Database status monitoring</li>
                            <li><i class="bi bi-hdd"></i> Storage usage tracking</li>
                            <li><i class="bi bi-speedometer"></i> Performance metrics</li>
                            <li><i class="bi bi-shield-check"></i> Security audit logs</li>
                        </ul>
                        
                        <h3>Backup Management</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-cloud-upload"></i> Automated backup scheduling</li>
                            <li><i class="bi bi-download"></i> Manual backup creation</li>
                            <li><i class="bi bi-arrow-repeat"></i> Backup restoration</li>
                            <li><i class="bi bi-clock-history"></i> Backup retention policies</li>
                        </ul>
                    </section>

                                    <!-- Office Management -->
                                    <section id="offices" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-building"></i> Office Management
                                        </div>
                        
                        <h3>Office Structure</h3>
                        <p>Organize your physical locations and organizational hierarchy through office management.</p>
                        
                        <h3>Office Types</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-house"></i> <strong>Main Offices:</strong> Primary organizational units (HO, 001, 002, etc.)</li>
                            <li><i class="bi bi-door-open"></i> <strong>Sub-offices:</strong> Smaller units under main offices</li>
                            <li><i class="bi bi-archive"></i> <strong>Storage Areas:</strong> Specific locations for inventory storage</li>
                        </ul>
                    </section>

                                    <!-- Category Management -->
                                    <section id="categories" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-tags"></i> Category Management
                                        </div>
                        
                        <h3>Category Organization</h3>
                        <p>Categories help organize assets and inventory into logical groups for better management.</p>
                        
                        <h3>Category Structure</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-folder"></i> <strong>Main Categories:</strong> High-level groupings</li>
                            <li><i class="bi bi-folder2"></i> <strong>Subcategories:</strong> More specific classifications</li>
                            <li><i class="bi bi-tag"></i> <strong>Tags:</strong> Additional classification options</li>
                        </ul>
                        
                        <h3>Default Categories</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-laptop"></i> Information Technology Equipment</li>
                            <li><i class="bi bi-chair"></i> Office Equipment & Furniture</li>
                            <li><i class="bi bi-truck"></i> Motor Vehicles</li>
                            <li><i class="bi bi-tools"></i> Tools & Equipment</li>
                            <li><i class="bi bi-box"></i> Office Supplies</li>
                            <li><i class="bi bi-three-dots"></i> Other Assets</li>
                        </ul>
                    </section>

                                    <!-- User Management -->
                                    <section id="users" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-people"></i> User Management
                                        </div>
                        
                        <h3>User Accounts</h3>
                        <p>Manage system user accounts and permissions.</p>
                        
                        <h3>User Roles</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5><i class="bi bi-person"></i> Regular User</h5>
                                    <ul>
                                        <li>View assigned assets</li>
                                        <li>Basic reporting</li>
                                        <li>No administrative privileges</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5><i class="bi bi-person-badge"></i> Admin</h5>
                                    <ul>
                                        <li>Asset and inventory management</li>
                                        <li>Employee management</li>
                                        <li>Report generation</li>
                                        <li>Limited system settings</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="highlight-box">
                                    <h5><i class="bi bi-person-gear"></i> System Administrator</h5>
                                    <ul>
                                        <li>Full system access</li>
                                        <li>User management</li>
                                        <li>System configuration</li>
                                        <li>Backup and maintenance</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <h3>Security Best Practices</h3>
                        <div class="warning-box">
                            <h5><i class="bi bi-shield-check"></i> Important Security Measures:</h5>
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
                                    <section id="settings" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-gear"></i> System Settings
                                        </div>
                        
                        <h3>Settings Overview</h3>
                        <p>Configure system-wide settings to customize PIMS for your organization.</p>
                        
                        <h3>General Settings</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-info-circle"></i> <strong>System Information:</strong> Name, logo, email</li>
                            <li><i class="bi bi-clock"></i> <strong>Session Settings:</strong> Timeout, auto-save</li>
                            <li><i class="bi bi-palette"></i> <strong>Appearance:</strong> Dark mode, items per page</li>
                            <li><i class="bi bi-calendar"></i> <strong>Date/Time:</strong> Format preferences</li>
                        </ul>
                        
                        <h3>Backup Settings</h3>
                        <ul class="feature-list">
                            <li><i class="bi bi-clock-history"></i> <strong>Automatic Backups:</strong> Schedule configuration</li>
                            <li><i class="bi bi-hdd"></i> <strong>Storage Location:</strong> Backup destination</li>
                            <li><i class="bi bi-trash"></i> <strong>Retention Policy:</strong> How long to keep backups</li>
                            <li><i class="bi bi-bell"></i> <strong>Notifications:</strong> Backup status alerts</li>
                        </ul>
                    </section>
                    <?php endif; ?>

                                    <!-- Troubleshooting -->
                                    <section id="troubleshooting" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-exclamation-triangle"></i> Troubleshooting
                                        </div>
                        
                        <h3>Common Issues</h3>
                        
                        <h4>Login Problems</h4>
                        <div class="warning-box">
                            <h5><i class="bi bi-x-circle"></i> Cannot Login</h5>
                            <ol>
                                <li>Check username and password spelling</li>
                                <li>Verify Caps Lock is off</li>
                                <li>Clear browser cache and cookies</li>
                                <li>Try a different browser</li>
                                <li>Contact system administrator if issue persists</li>
                            </ol>
                        </div>
                        
                        <h4>Performance Issues</h4>
                        <div class="warning-box">
                            <h5><i class="bi bi-speedometer2"></i> System Running Slow</h5>
                            <ol>
                                <li>Check internet connection speed</li>
                                <li>Close unnecessary browser tabs</li>
                                <li>Clear browser cache</li>
                                <li>Restart browser</li>
                                <li>Check if system maintenance is scheduled</li>
                            </ol>
                        </div>
                        
                        <h3>Getting Help</h3>
                        <div class="highlight-box">
                            <h5><i class="bi bi-question-circle"></i> Support Channels</h5>
                            <ul>
                                <li><strong>System Administrator:</strong> Primary contact for issues</li>
                                <li><strong>Help Desk:</strong> For routine problems</li>
                                <li><strong>IT Department:</strong> For technical issues</li>
                                <li><strong>User Manual:</strong> This document for reference</li>
                            </ul>
                        </div>
                    </section>

                                    <!-- FAQ -->
                                    <section id="faq" class="mb-4">
                                        <div class="section-title">
                                            <i class="bi bi-question-circle"></i> Frequently Asked Questions
                                        </div>
                        
                        <h3>Q: How do I reset my password?</h3>
                        <p><strong>A:</strong> Contact your system administrator to reset your password. For security reasons, password resets must be handled by authorized personnel.</p>
                        
                        <h3>Q: Can I access PIMS from my mobile device?</h3>
                        <p><strong>A:</strong> Yes, PIMS is fully responsive and can be accessed from any device with internet connectivity and a modern web browser.</p>
                        
                        <h3>Q: How often should I update asset information?</h3>
                        <p><strong>A:</strong> Asset information should be updated whenever there are changes in location, condition, assignment, or maintenance status. Regular reviews are recommended.</p>
                        
                        <h3>Q: What happens to assets when an employee leaves?</h3>
                        <p><strong>A:</strong> Assets should be reassigned or returned before the employee's departure. The system tracks clearance status to ensure all assets are properly handled.</p>
                        
                        <h3>Q: How do I generate reports for specific time periods?</h3>
                        <p><strong>A:</strong> In the Reports module, select the desired report type and set the date range parameters before generating the report.</p>
                        
                        <h3>Q: Is my data secure in PIMS?</h3>
                        <p><strong>A:</strong> PIMS includes multiple security layers: encrypted passwords, role-based access control, audit logging, and secure session management. Regular backups and security updates are recommended.</p>
                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php
    // Include sidebar scripts from ADMIN directory
    require_once 'includes/sidebar-scripts.php';
    ?>
    
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
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (window.pageYOffset >= (sectionTop - 200)) {
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
        
        // Simple notification function for form submission feedback
        document.getElementById('pdfForm').addEventListener('submit', function() {
            showNotification('Opening PDF generation in new window...', 'info');
        });
        
        // Function to open Help & Support modal
        function openHelpModal() {
            const helpModal = new bootstrap.Modal(document.getElementById('helpModal'));
            helpModal.show();
        }
        
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
    </script>
    
    <!-- Help & Support Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">
                        <i class="bi bi-question-circle"></i> Help & Support
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-telephone"></i> Contact Support
                                    </h6>
                                    <div class="mb-3">
                                        <strong>System Administrator:</strong><br>
                                        <span class="text-muted">For system-wide issues and user management</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>IT Department:</strong><br>
                                        <span class="text-muted">For technical problems and bug reports</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Help Desk:</strong><br>
                                        <span class="text-muted">For routine assistance and training</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-book"></i> Quick Links
                                    </h6>
                                    <div class="list-group list-group-flush">
                                        <a href="#overview" class="list-group-item list-group-item-action">
                                            <i class="bi bi-info-circle"></i> System Overview
                                        </a>
                                        <a href="#getting-started" class="list-group-item list-group-item-action">
                                            <i class="bi bi-rocket-takeoff"></i> Getting Started
                                        </a>
                                        <a href="#dashboard" class="list-group-item list-group-item-action">
                                            <i class="bi bi-speedometer2"></i> Dashboard Guide
                                        </a>
                                        <?php if ($isAdmin): ?>
                                        <a href="#asset-management" class="list-group-item list-group-item-action">
                                            <i class="bi bi-box"></i> Asset Management
                                        </a>
                                        <a href="#inventory" class="list-group-item list-group-item-action">
                                            <i class="bi bi-archive"></i> Inventory Management
                                        </a>
                                        <a href="#employees" class="list-group-item list-group-item-action">
                                            <i class="bi bi-people"></i> Employee Management
                                        </a>
                                        <a href="#reports" class="list-group-item list-group-item-action">
                                            <i class="bi bi-file-text"></i> Reports Guide
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($isSystemAdmin): ?>
                                        <a href="#system-admin" class="list-group-item list-group-item-action">
                                            <i class="bi bi-gear"></i> System Administration
                                        </a>
                                        <a href="#offices" class="list-group-item list-group-item-action">
                                            <i class="bi bi-building"></i> Office Management
                                        </a>
                                        <a href="#categories" class="list-group-item list-group-item-action">
                                            <i class="bi bi-tags"></i> Category Management
                                        </a>
                                        <a href="#users" class="list-group-item list-group-item-action">
                                            <i class="bi bi-people"></i> User Management
                                        </a>
                                        <a href="#settings" class="list-group-item list-group-item-action">
                                            <i class="bi bi-gear"></i> System Settings
                                        </a>
                                        <?php endif; ?>
                                        <a href="#troubleshooting" class="list-group-item list-group-item-action">
                                            <i class="bi bi-exclamation-triangle"></i> Troubleshooting
                                        </a>
                                        <a href="#faq" class="list-group-item list-group-item-action">
                                            <i class="bi bi-question-circle"></i> FAQ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
