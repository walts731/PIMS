<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications Sidebar - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #5CC2F2;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-lg: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar:not(.show) {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }
        
        .sidebar-logo {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
            border-radius: 8px;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .menu-item {
            margin-bottom: 0.5rem;
        }
        
        .menu-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .menu-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
        }
        
        .menu-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .menu-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .menu-text {
            font-weight: 500;
        }
        
        /* Main Content Layout */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
        }
        
        /* Topbar Styles */
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 60px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 999;
        }
        
        .main-content {
            margin-top: 60px;
            padding-top: 2rem;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #5CC2F2;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .sidebar-toggle:hover {
            background-color: rgba(92, 194, 242, 0.1);
        }
        
        .sidebar-toggle.active {
            background-color: #5CC2F2;
            color: white;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            display: none;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar {
                left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <?php require_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <?php require_once 'includes/topbar.php'; ?>
            
            <!-- Test Content -->
            <div class="page-header">
                <h2>Test Notifications Page</h2>
                <p>This page tests the sidebar toggle functionality on the notifications page.</p>
                
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle"></i> Sidebar Toggle Test</h5>
                    <p>Click the hamburger menu icon (☰) in the topbar to toggle the sidebar.</p>
                    <ul>
                        <li>The sidebar should slide in/out from the left</li>
                        <li>On mobile/tablet, an overlay should appear when sidebar is open</li>
                        <li>On desktop, the main content should adjust its margin</li>
                        <li>The toggle button should change appearance when active</li>
                    </ul>
                </div>
                
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle"></i> Expected Behavior</h5>
                    <ul>
                        <li><strong>Desktop:</strong> Sidebar slides, content margin adjusts</li>
                        <li><strong>Mobile:</strong> Sidebar slides with overlay</li>
                        <li><strong>Toggle Button:</strong> Changes color when active</li>
                        <li><strong>Click Outside:</strong> Closes sidebar on mobile</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap-based Notification Script -->
    <?php require_once 'includes/notification_script_bootstrap.php'; ?>
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
