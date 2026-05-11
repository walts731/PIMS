<?php
session_start();
require_once 'config.php';
require_once 'includes/system_functions.php';
require_once 'includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Allow both GET and POST for better compatibility
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request method');
}

// Get user role and system settings from session as fallback
$userRole = $_POST['user_role'] ?? $_SESSION['role'] ?? 'user';
$system_name = $_POST['system_name'] ?? 'PIMS';

// Determine permissions
$isAdmin = ($userRole === 'admin' || $userRole === 'system_admin');
$isSystemAdmin = ($userRole === 'system_admin');

// Get system settings
$system_settings = [];
try {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $system_settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    // Fallback to defaults
    $system_settings['system_name'] = $system_name;
    $system_settings['system_logo'] = '';
}

// Log PDF generation
logSystemAction($_SESSION['user_id'], 'generate', 'user_manual_pdf', 'User generated PDF user manual');

// Debug information
error_log("PDF Generation - User Role: " . $userRole);
error_log("PDF Generation - Is Admin: " . ($isAdmin ? 'true' : 'false'));
error_log("PDF Generation - Is System Admin: " . ($isSystemAdmin ? 'true' : 'false'));
error_log("PDF Generation - System Settings Count: " . count($system_settings));

// Generate HTML content
$html = generateUserManualHTML($isAdmin, $isSystemAdmin, $system_settings);

// Check if HTML was generated
if (empty($html)) {
    error_log("PDF Generation Error: HTML content is empty");
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error generating HTML content');
}

// Set headers for HTML print view
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Use a simple HTML to PDF conversion approach
// For now, we'll output the HTML as a printable version
// In a production environment, you would use a proper PDF library

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $system_name . ' User Manual</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        h1 {
            color: #1E56A0;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            page-break-after: avoid;
        }
        
        h2 {
            color: #1E56A0;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            margin-top: 20px;
            page-break-after: avoid;
        }
        
        h3 {
            color: #163172;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 6px;
            margin-top: 15px;
            page-break-after: avoid;
        }
        
        h4 {
            color: #163172;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            margin-top: 10px;
            page-break-after: avoid;
        }
        
        ul, ol {
            margin-left: 20px;
            margin-bottom: 10px;
        }
        
        li {
            margin-bottom: 5px;
        }
        
        .highlight {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #1E56A0;
            margin: 10px 0;
            page-break-inside: avoid;
        }
        
        .warning {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin: 10px 0;
            page-break-inside: avoid;
        }
        
        .success {
            background-color: #d4edda;
            padding: 10px;
            border-left: 4px solid #28a745;
            margin: 10px 0;
            page-break-inside: avoid;
        }
        
        .toc {
            margin-bottom: 30px;
            page-break-after: always;
        }
        
        .toc ul {
            list-style-type: none;
            margin-left: 0;
        }
        
        .toc li {
            margin-bottom: 5px;
        }
        
        .toc a {
            text-decoration: none;
            color: #1E56A0;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-break {
            page-break-inside: avoid;
        }
        
        @media print {
            body {
                font-size: 10px;
            }
            
            h1 { font-size: 20px; }
            h2 { font-size: 16px; }
            h3 { font-size: 14px; }
            h4 { font-size: 12px; }
        }
    </style>
</head>
<body>' . $html . '
    <script>
        // Auto-trigger print dialog when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Close window after printing (optional)
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>';

exit;

// Function to generate user manual HTML
function generateUserManualHTML($isAdmin, $isSystemAdmin, $system_settings) {
    $system_name = $system_settings['system_name'] ?? 'PIMS';
    
    ob_start();
    ?>
    
    <!-- Title Page -->
    <div style="text-align: center; margin-bottom: 50px;">
        <h1 style="color: #1E56A0; font-size: 32px; margin-bottom: 20px;"><?php echo htmlspecialchars($system_name); ?></h1>
        <h2 style="color: #163172; font-size: 24px; margin-bottom: 30px;">User Manual</h2>
        <p style="font-size: 16px; color: #666; margin-bottom: 10px;">Complete guide for the Pilar Inventory Management System</p>
        <p style="font-size: 14px; color: #999;">Generated on <?php echo date('F j, Y'); ?></p>
    </div>
    
    <div class="page-break">
        <h2 style="color: #1E56A0; font-size: 20px; margin-bottom: 20px;">Table of Contents</h2>
        <ol style="line-height: 1.8;">
            <li><a href="#overview" style="color: #1E56A0; text-decoration: none;">System Overview</a></li>
            <li><a href="#getting-started" style="color: #1E56A0; text-decoration: none;">Getting Started</a></li>
            <li><a href="#dashboard" style="color: #1E56A0; text-decoration: none;">Dashboard</a></li>
            <?php if ($isAdmin): ?>
            <li><a href="#asset-management" style="color: #1E56A0; text-decoration: none;">Asset Management</a></li>
            <li><a href="#inventory" style="color: #1E56A0; text-decoration: none;">Inventory Management</a></li>
            <li><a href="#employees" style="color: #1E56A0; text-decoration: none;">Employee Management</a></li>
            <li><a href="#reports" style="color: #1E56A0; text-decoration: none;">Reports</a></li>
            <?php endif; ?>
            <?php if ($isSystemAdmin): ?>
            <li><a href="#system-admin" style="color: #1E56A0; text-decoration: none;">System Administration</a></li>
            <li><a href="#offices" style="color: #1E56A0; text-decoration: none;">Office Management</a></li>
            <li><a href="#categories" style="color: #1E56A0; text-decoration: none;">Category Management</a></li>
            <li><a href="#users" style="color: #1E56A0; text-decoration: none;">User Management</a></li>
            <li><a href="#settings" style="color: #1E56A0; text-decoration: none;">System Settings</a></li>
            <?php endif; ?>
            <li><a href="#troubleshooting" style="color: #1E56A0; text-decoration: none;">Troubleshooting</a></li>
            <li><a href="#faq" style="color: #1E56A0; text-decoration: none;">Frequently Asked Questions</a></li>
        </ol>
    </div>
    
    <div class="page-break">
        <h1 id="overview" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">1. System Overview</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">What is PIMS?</h3>
        <p style="margin-bottom: 15px;">The Pilar Inventory Management System (PIMS) is a comprehensive solution designed to help the Municipality of Pilar efficiently track, manage, and report on their physical assets and inventory items. This system provides real-time visibility into asset locations, conditions, and lifecycle management.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Key Features</h3>
        <ul style="margin-bottom: 15px;">
            <li><strong>Asset Tracking:</strong> Complete lifecycle management of physical assets</li>
            <li><strong>Inventory Management:</strong> Real-time inventory tracking and stock management</li>
            <li><strong>Employee Assignment:</strong> Track asset assignments to employees</li>
            <li><strong>QR Code Support:</strong> Generate and scan QR codes for quick asset identification</li>
            <li><strong>Reporting:</strong> Comprehensive reports for analysis and decision-making</li>
            <li><strong>User Management:</strong> Role-based access control and permissions</li>
            <li><strong>Mobile Responsive:</strong> Access from any device with internet connectivity</li>
        </ul>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">System Architecture</h3>
        <p>PIMS is built on a modern web architecture with:</p>
        <ul>
            <li>MySQL database for data storage</li>
            <li>PHP backend for business logic</li>
            <li>Bootstrap 5 for responsive design</li>
            <li>Secure authentication and authorization</li>
        </ul>
    </div>
    
    <div class="page-break">
        <h1 id="getting-started" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">2. Getting Started</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">System Requirements</h3>
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">Browser Requirements:</h4>
            <ul>
                <li>Chrome 90+ or Firefox 88+ or Safari 14+ or Edge 90+</li>
                <li>JavaScript must be enabled</li>
                <li>Cookies must be enabled for session management</li>
            </ul>
        </div>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">First Time Login</h3>
        <ol style="margin-bottom: 15px;">
            <li>Open your web browser and navigate to the PIMS URL</li>
            <li>Enter your username and password provided by your administrator</li>
            <li>Click "Sign In" to access the system</li>
            <li>You will be redirected to the dashboard based on your role</li>
        </ol>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">User Roles and Permissions</h3>
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">Regular User:</h4>
            <ul>
                <li>View assigned assets</li>
                <li>View inventory items</li>
                <li>Generate basic reports</li>
            </ul>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">Admin:</h4>
            <ul>
                <li>All regular user permissions</li>
                <li>Manage assets and inventory</li>
                <li>Manage employees</li>
                <li>Generate comprehensive reports</li>
            </ul>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">System Administrator:</h4>
            <ul>
                <li>All admin permissions</li>
                <li>System configuration</li>
                <li>User management</li>
            </ul>
        </div>
    </div>
    
    <div class="page-break">
        <h1 id="dashboard" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">3. Dashboard</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Dashboard Overview</h3>
        <p style="margin-bottom: 15px;">The dashboard provides a comprehensive overview of your system's status and key metrics. It's the first page you see after logging in.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Key Components</h3>
        <ul style="margin-bottom: 15px;">
            <li><strong>Statistics Cards:</strong> Display key metrics like total assets, inventory value, etc.</li>
            <li><strong>Asset Categories:</strong> Quick overview of asset distribution by category</li>
            <li><strong>Recent Activities:</strong> Timeline of recent system actions</li>
            <li><strong>Quick Actions:</strong> Fast access to common tasks</li>
        </ul>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Navigating the Dashboard</h3>
        <p>The dashboard is your central hub for accessing all system features:</p>
        <ul>
            <li>Use the sidebar menu to navigate to different modules</li>
            <li>Click on statistics cards for detailed views</li>
            <li>Use the search bar for quick access to specific items</li>
            <li>Access user profile and settings from the top bar</li>
        </ul>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="page-break">
        <h1 id="asset-management" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">4. Asset Management</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Asset Overview</h3>
        <p style="margin-bottom: 15px;">Asset Management allows you to track all physical assets within your organization, from acquisition to disposal.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Adding New Assets</h3>
        <ol style="margin-bottom: 15px;">
            <li>Navigate to Assets → Add Asset</li>
            <li>Fill in the asset information form:
                <ul>
                    <li>Asset name and description</li>
                    <li>Category and subcategory</li>
                    <li>Purchase details (date, cost, vendor)</li>
                    <li>Location and assignment information</li>
                    <li>Warranty and maintenance details</li>
                </ul>
            </li>
            <li>Upload asset photo (optional)</li>
            <li>Click "Save Asset"</li>
        </ol>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Asset Status Management</h3>
        <ul>
            <li><strong>Serviceable:</strong> Asset is in good working condition</li>
            <li><strong>Borrowed:</strong> Asset is currently assigned to someone</li>
            <li><strong>Red Tagged:</strong> Asset needs maintenance or replacement</li>
            <li><strong>Disposed:</strong> Asset is no longer in use</li>
        </ul>
    </div>
    
    <div class="page-break">
        <h1 id="inventory" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">5. Inventory Management</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Inventory Overview</h3>
        <p style="margin-bottom: 15px;">Inventory Management handles consumable and non-consumable items that need stock tracking.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Adding Inventory Items</h3>
        <ol style="margin-bottom: 15px;">
            <li>Navigate to Inventory → Add Item</li>
            <li>Enter item details:
                <ul>
                    <li>Item name and description</li>
                    <li>Category and unit of measure</li>
                    <li>Initial quantity and reorder level</li>
                    <li>Storage location</li>
                    <li>Supplier information</li>
                </ul>
            </li>
            <li>Set minimum stock levels for alerts</li>
            <li>Click "Save Item"</li>
        </ol>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Stock Management</h3>
        <ul>
            <li>Monitor stock levels in real-time</li>
            <li>Receive automatic low-stock alerts</li>
            <li>Track stock movements and consumption</li>
            <li>Generate inventory reports</li>
        </ul>
    </div>
    
    <div class="page-break">
        <h1 id="employees" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">6. Employee Management</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Employee Records</h3>
        <p style="margin-bottom: 15px;">Manage employee information and track asset assignments to individuals.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Adding Employees</h3>
        <ol style="margin-bottom: 15px;">
            <li>Navigate to Employees → Add Employee</li>
            <li>Fill in employee information:
                <ul>
                    <li>Personal details (name, contact info)</li>
                    <li>Employee ID and department</li>
                    <li>Position and employment details</li>
                    <li>Emergency contact information</li>
                </ul>
            </li>
            <li>Click "Save Employee"</li>
        </ol>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Asset Assignment</h3>
        <ol>
            <li>Select an employee from the employee list</li>
            <li>Click "Assign to Employee"</li>
            <li>Choose the employee from dropdown</li>
            <li>Set assignment date and notes</li>
            <li>Click "Assign Asset"</li>
        </ol>
    </div>
    
    <div class="page-break">
        <h1 id="reports" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">7. Reports</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Report Overview</h3>
        <p style="margin-bottom: 15px;">The Reports module provides comprehensive analytics and reporting capabilities for informed decision-making.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Available Reports</h3>
        <ul style="margin-bottom: 15px;">
            <li><strong>Asset Reports:</strong> Asset inventory list, depreciation reports, asset lifecycle</li>
            <li><strong>Inventory Reports:</strong> Stock status, consumption reports, value analysis</li>
            <li><strong>Employee Reports:</strong> Asset assignments, clearance status</li>
            <li><strong>Financial Reports:</strong> Asset value, depreciation, purchase history</li>
        </ul>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Generating Reports</h3>
        <ol>
            <li>Navigate to Reports → Select Report Type</li>
            <li>Set report parameters (date range, filters, etc.)</li>
            <li>Choose output format (PDF, Excel, CSV)</li>
            <li>Click "Generate Report"</li>
            <li>Download or print the report</li>
        </ol>
    </div>
    <?php endif; ?>
    
    <?php if ($isSystemAdmin): ?>
    <div class="page-break">
        <h1 id="system-admin" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">System Administration</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Admin Overview</h3>
        <p style="margin-bottom: 15px;">System Administration provides tools for managing the PIMS system configuration and users.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">System Health</h3>
        <ul style="margin-bottom: 15px;">
            <li>Database status monitoring</li>
            <li>Storage usage tracking</li>
            <li>Performance metrics</li>
            <li>Security audit logs</li>
        </ul>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Backup Management</h3>
        <ul>
            <li>Automated backup scheduling</li>
            <li>Manual backup creation</li>
            <li>Backup restoration</li>
            <li>Backup retention policies</li>
        </ul>
    </div>
    
    <div class="page-break">
        <h1 id="offices" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">Office Management</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Office Structure</h3>
        <p style="margin-bottom: 15px;">Organize your physical locations and organizational hierarchy through office management.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Office Types</h3>
        <ul>
            <li><strong>Main Offices:</strong> Primary organizational units (HO, 001, 002, etc.)</li>
            <li><strong>Sub-offices:</strong> Smaller units under main offices</li>
            <li><strong>Storage Areas:</strong> Specific locations for inventory storage</li>
        </ul>
    </div>
    
    <div class="page-break">
        <h1 id="categories" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">Category Management</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Category Organization</h3>
        <p style="margin-bottom: 15px;">Categories help organize assets and inventory into logical groups for better management.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Category Structure</h3>
        <ul style="margin-bottom: 15px;">
            <li><strong>Main Categories:</strong> High-level groupings</li>
            <li><strong>Subcategories:</strong> More specific classifications</li>
            <li><strong>Tags:</strong> Additional classification options</li>
        </ul>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Default Categories</h3>
        <ul>
            <li>Information Technology Equipment</li>
            <li>Office Equipment & Furniture</li>
            <li>Motor Vehicles</li>
            <li>Tools & Equipment</li>
            <li>Office Supplies</li>
            <li>Other Assets</li>
        </ul>
    </div>
    
    <div class="page-break">
        <h1 id="users" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">User Management</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">User Accounts</h3>
        <p style="margin-bottom: 15px;">Manage system user accounts and permissions.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">User Roles</h3>
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">Regular User</h4>
            <ul>
                <li>View assigned assets</li>
                <li>Basic reporting</li>
                <li>No administrative privileges</li>
            </ul>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">Admin</h4>
            <ul>
                <li>Asset and inventory management</li>
                <li>Employee management</li>
                <li>Report generation</li>
                <li>Limited system settings</li>
            </ul>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">System Administrator</h4>
            <ul>
                <li>Full system access</li>
                <li>User management</li>
                <li>System configuration</li>
                <li>Backup and maintenance</li>
            </ul>
        </div>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Security Best Practices</h3>
        <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">
            <h4 style="color: #856404; font-size: 16px; margin-bottom: 10px;">Important Security Measures:</h4>
            <ul>
                <li>Enforce strong password policies</li>
                <li>Set appropriate session timeouts</li>
                <li>Monitor login attempts</li>
                <li>Regular password changes</li>
                <li>Disable inactive accounts</li>
            </ul>
        </div>
    </div>
    
    <div class="page-break">
        <h1 id="settings" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">System Settings</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Settings Overview</h3>
        <p style="margin-bottom: 15px;">Configure system-wide settings to customize PIMS for your organization.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">General Settings</h3>
        <ul style="margin-bottom: 15px;">
            <li><strong>System Information:</strong> Name, logo, email</li>
            <li><strong>Session Settings:</strong> Timeout, auto-save</li>
            <li><strong>Appearance:</strong> Dark mode, items per page</li>
            <li><strong>Date/Time:</strong> Format preferences</li>
        </ul>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Backup Settings</h3>
        <ul>
            <li><strong>Automatic Backups:</strong> Schedule configuration</li>
            <li><strong>Storage Location:</strong> Backup destination</li>
            <li><strong>Retention Policy:</strong> How long to keep backups</li>
            <li><strong>Notifications:</strong> Backup status alerts</li>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="page-break">
        <h1 id="troubleshooting" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">Troubleshooting</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Common Issues</h3>
        
        <h4 style="color: #163172; font-size: 16px; margin-bottom: 8px;">Login Problems</h4>
        <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">
            <h4 style="color: #856404; font-size: 16px; margin-bottom: 10px;">Cannot Login</h4>
            <ol>
                <li>Check username and password spelling</li>
                <li>Verify Caps Lock is off</li>
                <li>Clear browser cache and cookies</li>
                <li>Try a different browser</li>
                <li>Contact system administrator if issue persists</li>
            </ol>
        </div>
        
        <h4 style="color: #163172; font-size: 16px; margin-bottom: 8px;">Performance Issues</h4>
        <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">
            <h4 style="color: #856404; font-size: 16px; margin-bottom: 10px;">System Running Slow</h4>
            <ol>
                <li>Check internet connection speed</li>
                <li>Close unnecessary browser tabs</li>
                <li>Clear browser cache</li>
                <li>Restart browser</li>
                <li>Check if system maintenance is scheduled</li>
            </ol>
        </div>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Getting Help</h3>
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #1E56A0; margin: 15px 0;">
            <h4 style="color: #163172; font-size: 16px; margin-bottom: 10px;">Support Channels</h4>
            <ul>
                <li><strong>System Administrator:</strong> Primary contact for issues</li>
                <li><strong>Help Desk:</strong> For routine problems</li>
                <li><strong>IT Department:</strong> For technical issues</li>
                <li><strong>User Manual:</strong> This document for reference</li>
            </ul>
        </div>
    </div>
    
    <div class="page-break">
        <h1 id="faq" style="color: #1E56A0; font-size: 24px; margin-bottom: 20px;">Frequently Asked Questions</h1>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Q: How do I reset my password?</h3>
        <p style="margin-bottom: 15px;"><strong>A:</strong> Contact your system administrator to reset your password. For security reasons, password resets must be handled by authorized personnel.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Q: Can I access PIMS from my mobile device?</h3>
        <p style="margin-bottom: 15px;"><strong>A:</strong> Yes, PIMS is fully responsive and can be accessed from any device with internet connectivity and a modern web browser.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Q: How often should I update asset information?</h3>
        <p style="margin-bottom: 15px;"><strong>A:</strong> Asset information should be updated whenever there are changes in location, condition, assignment, or maintenance status. Regular reviews are recommended.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Q: What happens to assets when an employee leaves?</h3>
        <p style="margin-bottom: 15px;"><strong>A:</strong> Assets should be reassigned or returned before the employee's departure. The system tracks clearance status to ensure all assets are properly handled.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Q: How do I generate reports for specific time periods?</h3>
        <p style="margin-bottom: 15px;"><strong>A:</strong> In the Reports module, select the desired report type and set the date range parameters before generating the report.</p>
        
        <h3 style="color: #163172; font-size: 18px; margin-bottom: 10px;">Q: Is my data secure in PIMS?</h3>
        <p><strong>A:</strong> PIMS includes multiple security layers: encrypted passwords, role-based access control, audit logging, and secure session management. Regular backups and security updates are recommended.</p>
    </div>
    
    <?php
    return ob_get_clean();
}
?>
