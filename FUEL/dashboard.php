<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pims';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Query functions
function getFuelInData($conn) {
    $sql = "SELECT fi.id, fi.date_time, fi.fuel_type, fi.quantity, fi.unit_price, fi.total_cost, fi.storage_location, 
                   fi.delivery_receipt, fi.supplier_name, fi.received_by, fi.remarks, fi.created_by, fi.created_at,
                   ft.name as fuel_type_name
            FROM fuel_in fi
            LEFT JOIN fuel_types ft ON fi.fuel_type = ft.id
            ORDER BY fi.date_time DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getFuelOutData($conn) {
    $sql = "SELECT fo.id, fo.fo_date, fo.fo_time_in, fo.fo_fuel_no, fo.fo_plate_no, fo.fo_request, fo.fo_fuel_type, 
                   fo.fo_liters, fo.fo_vehicle_type, fo.fo_receiver, fo.fo_time_out, fo.created_by, fo.created_at,
                   ft.name as fuel_type_name
            FROM fuel_out fo
            LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
            ORDER BY fo.fo_date DESC, fo.fo_time_in DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}


function getFuelTypesData($conn) {
    $sql = "SELECT id, name, is_active, created_at 
            FROM fuel_types 
            WHERE is_active = 1 
            ORDER BY name ASC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getDashboardStats($conn) {
    $stats = [];
    
    // Total fuel in
    $result = $conn->query("SELECT SUM(quantity) as total_fuel_in FROM fuel_in");
    $stats['total_fuel_in'] = $result->fetch_assoc()['total_fuel_in'] ?? 0;
    
    // Total fuel out
    $result = $conn->query("SELECT SUM(fo_liters) as total_fuel_out FROM fuel_out");
    $stats['total_fuel_out'] = $result->fetch_assoc()['total_fuel_out'] ?? 0;
    
    // Total oil in
    $result = $conn->query("SELECT SUM(quantity) as total_oil_in FROM oil_in");
    $stats['total_oil_in'] = $result->fetch_assoc()['total_oil_in'] ?? 0;
    
    // Total oil out
    $result = $conn->query("SELECT SUM(oil_liters) as total_oil_out FROM oil_out");
    $stats['total_oil_out'] = $result->fetch_assoc()['total_oil_out'] ?? 0;
    
    // Available oil balance
    $stats['available_oil'] = $stats['total_oil_in'] - $stats['total_oil_out'];
    
    // Active fuel types
    $result = $conn->query("SELECT COUNT(*) as active_fuel_types FROM fuel_types WHERE is_active = 1");
    $stats['active_fuel_types'] = $result->fetch_assoc()['active_fuel_types'] ?? 0;
    
    // Total cost
    $result = $conn->query("SELECT SUM(total_cost) as total_cost FROM fuel_in");
    $stats['total_cost'] = $result->fetch_assoc()['total_cost'] ?? 0;
    
    // Calculate remaining balance
    $stats['remaining_balance'] = $stats['total_fuel_in'] - $stats['total_fuel_out'];
    
    // Open capacity system - using actual fuel in amounts
    
    // Get balance by fuel type (open capacity system)
    $balance_sql = "
        SELECT 
            ft.name as fuel_type_name,
            COALESCE(SUM(CASE WHEN source = 'fuel_in' THEN quantity ELSE 0 END), 0) as fuel_in,
            COALESCE(SUM(CASE WHEN source = 'fuel_out' THEN quantity ELSE 0 END), 0) as fuel_out,
            (COALESCE(SUM(CASE WHEN source = 'fuel_in' THEN quantity ELSE 0 END), 0) - 
             COALESCE(SUM(CASE WHEN source = 'fuel_out' THEN quantity ELSE 0 END), 0)) as balance
        FROM (
            SELECT fuel_type, quantity, 'fuel_in' as source FROM fuel_in
            UNION ALL
            SELECT fo_fuel_type as fuel_type, fo_liters as quantity, 'fuel_out' as source FROM fuel_out
        ) combined
        LEFT JOIN fuel_types ft ON combined.fuel_type = ft.id
        GROUP BY combined.fuel_type, ft.name
        ORDER BY balance DESC
    ";
    $balance_result = $conn->query($balance_sql);
    $fuel_balance_data = $balance_result ? $balance_result->fetch_all(MYSQLI_ASSOC) : [];
    $stats['fuel_balance_by_type'] = $fuel_balance_data;
    
    // Get oil balance by type
    $oil_balance_sql = "
        SELECT 
            ot.id,
            ot.name as fuel_type_name,
            COALESCE(oi.fuel_in, 0) as fuel_in,
            COALESCE(oo.fuel_out, 0) as fuel_out,
            COALESCE(oi.fuel_in, 0) - COALESCE(oo.fuel_out, 0) as balance
        FROM oil_types ot
        LEFT JOIN (
            SELECT oil_type, SUM(quantity) as fuel_in 
            FROM oil_in 
            WHERE oil_type IS NOT NULL 
            GROUP BY oil_type
        ) oi ON ot.id = oi.oil_type
        LEFT JOIN (
            SELECT all_oil_type as oil_type, SUM(oil_liters) as fuel_out 
            FROM oil_out 
            WHERE all_oil_type IS NOT NULL 
            GROUP BY all_oil_type
        ) oo ON ot.id = oo.oil_type
        WHERE ot.is_active = 1
        ORDER BY ot.name ASC
    ";
    $oil_balance_result = $conn->query($oil_balance_sql);
    $oil_balance_data = $oil_balance_result ? $oil_balance_result->fetch_all(MYSQLI_ASSOC) : [];
    $stats['oil_balance_by_type'] = $oil_balance_data;
    
    // Debug: Check the oil balance query and results
    $stats['debug_oil_balance_sql'] = $oil_balance_sql;
    $stats['debug_oil_balance_count'] = count($oil_balance_data);
    $stats['debug_oil_balance_error'] = $conn->error;
    
    // Debug: Check if oil tables have data
    $oil_in_count = $conn->query("SELECT COUNT(*) as count FROM oil_in")->fetch_assoc()['count'] ?? 0;
    $oil_out_count = $conn->query("SELECT COUNT(*) as count FROM oil_out")->fetch_assoc()['count'] ?? 0;
    $oil_types_count = $conn->query("SELECT COUNT(*) as count FROM oil_types")->fetch_assoc()['count'] ?? 0;
    
    // Debug: Check oil_type values in records
    $oil_in_types = $conn->query("SELECT DISTINCT oil_type FROM oil_in WHERE oil_type IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
    $oil_out_types = $conn->query("SELECT DISTINCT all_oil_type FROM oil_out WHERE all_oil_type IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
    
    // Add debug info to stats
    $stats['oil_in_count'] = $oil_in_count;
    $stats['oil_out_count'] = $oil_out_count;
    $stats['oil_types_count'] = $oil_types_count;
    $stats['debug_oil_in_types'] = $oil_in_types;
    $stats['debug_oil_out_types'] = $oil_out_types;
    
    // Calculate total remaining from balance data
    $stats['total_remaining'] = 0;
    foreach ($fuel_balance_data as $balance) {
        $stats['total_remaining'] += $balance['balance'];
    }
    
    // Calculate total oil remaining
    $stats['total_oil_remaining'] = 0;
    foreach ($oil_balance_data as $balance) {
        $stats['total_oil_remaining'] += $balance['balance'];
    }
    
    return $stats;
}

// Get data
$fuelInData = getFuelInData($conn);
$fuelOutData = getFuelOutData($conn);
$fuelTypesData = getFuelTypesData($conn);
$stats = getDashboardStats($conn);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 35px 20px;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%);
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .sidebar-header h2 {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }

        .sidebar-menu .icon {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            position: relative;
        }

        .main-content::before {
            content: '';
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(102, 126, 234, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(102, 126, 234, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 25px;
            position: relative;
            z-index: 1;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 0;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 
                0 10px 30px rgba(0,0,0,0.1),
                0 1px 8px rgba(0,0,0,0.06);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.8);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(0,0,0,0.15),
                0 1px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .stat-progress {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            margin-bottom: 35px;
            border-radius: 15px;
            box-shadow: 
                0 10px 30px rgba(0,0,0,0.08),
                0 1px 8px rgba(0,0,0,0.04);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.8);
            transition: all 0.3s ease;
        }

        .section:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 15px 35px rgba(0,0,0,0.12),
                0 1px 12px rgba(0,0,0,0.06);
        }

        .section-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .section-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25px;
            right: 25px;
            height: 2px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .section:hover .section-header::before {
            transform: scaleX(1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #495057;
            font-weight: 500;
        }

        .table-container {
            overflow-x: auto;
            padding: 25px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.2);
        }

        tr:hover td {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            transform: scale(1.01);
        }

        tr:hover td:first-child {
            border-radius: 10px 0 0 10px;
        }

        tr:hover td:last-child {
            border-radius: 0 10px 10px 0;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .pagination {
            display: flex;
            justify-content: center;
            padding: 20px;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background-color: #5a6fd8;
        }

        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #667eea;
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background-color: #5a6fd8;
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .container {
                padding: 10px;
            }

            h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                font-size: 0.875rem;
            }

            th, td {
                padding: 8px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                width: 60px;
            }
            
            .sidebar-header h2,
            .sidebar-header p {
                display: none;
            }
            
            .sidebar-menu .icon {
                margin-right: 0;
            }
            
            .sidebar-menu a span {
                display: none;
            }
            
            .main-content {
                margin-left: 60px;
            }
            
            .container {
                padding: 10px;
            }
        }

        /* Navigation Bar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .navbar-toggle {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 20px;
        }

        .navbar-toggle:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .navbar-title {
            font-size: 1.3rem;
            font-weight: 500;
            flex: 1;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Sidebar Toggle States */
        .sidebar.hidden {
            transform: translateX(-100%);
            margin-left: -250px;
        }

        .main-content.sidebar-hidden {
            margin-left: 0;
        }

        .navbar.sidebar-expanded {
            left: 250px;
        }

        .navbar.sidebar-collapsed {
            left: 0;
        }

        /* Responsive adjustments for navbar */
        @media (max-width: 768px) {
            .sidebar.hidden {
                margin-left: -200px;
            }
            
            .navbar.sidebar-expanded {
                left: 200px;
            }
        }

        @media (max-width: 600px) {
            .sidebar.hidden {
                margin-left: -60px;
            }
            
            .navbar.sidebar-expanded {
                left: 60px;
            }
            
            .navbar-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation Bar -->
        <nav class="navbar sidebar-expanded" id="navbar">
            <button class="navbar-toggle" onclick="toggleSidebar()">
                &#9776;
            </button>
            <div class="navbar-title">
                ⛽ Fuel & Oil Management Dashboard
            </div>
            <div class="navbar-actions">
                <!-- Add actions here if needed -->
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Fuel & Oil Management System</h2>
                <p>Management Portal</p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li>
                        <a href="dashboard.php">
                            <span class="icon">📊</span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="fuel_in.php">
                            <span class="icon">📥</span>
                            <span>Fuel In</span>
                        </a>
                    </li>
                    <li>
                        <a href="fuel_out.php">
                            <span class="icon">📤</span>
                            <span>Fuel Out</span>
                        </a>
                    </li>
                    <li>
                        <a href="oil_in.php">
                            <span class="icon">🛢️</span>
                            <span>Oil In</span>
                        </a>
                    </li>
                    <li>
                        <a href="oil.php">
                            <span class="icon">🛢</span>
                            <span>Oil Out</span>
                        </a>
                    </li>
                    <li>
                        <a href="fuel_types.php">
                            <span class="icon">⚡</span>
                            <span>Types</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <span class="icon">📈</span>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="fuel_transactions.php">
                            <span class="icon">🔄</span>
                            <span>Transactions</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
                            <span class="icon">🚪</span>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <header style="margin-top: 60px;">
                <div class="container">
                    <h1>⛽ Fuel & Oil Management Dashboard</h1>
                </div>
            </header>

            <div class="container">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">📥</div>
                        <div class="stat-number"><?php echo number_format($stats['total_fuel_in'], 2); ?></div>
                        <div class="stat-label">Total Fuel In (Liters)</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: 100%; background: linear-gradient(90deg, #28a745 0%, #20c997 100%);"></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">📤</div>
                        <div class="stat-number"><?php echo number_format($stats['total_fuel_out'], 2); ?></div>
                        <div class="stat-label">Total Fuel Out (Liters)</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo $stats['total_fuel_in'] > 0 ? min(($stats['total_fuel_out'] / $stats['total_fuel_in']) * 100, 100) : 0; ?>%; background: linear-gradient(90deg, #dc3545 0%, #fd7e14 100%);"></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">⚖️</div>
                        <div class="stat-number" style="color: <?php echo $stats['remaining_balance'] >= 0 ? '#28a745' : '#dc3545'; ?>;"><?php echo number_format($stats['remaining_balance'], 2); ?></div>
                        <div class="stat-label">Remaining Balance (Liters)</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo $stats['total_fuel_in'] > 0 ? min(($stats['remaining_balance'] / $stats['total_fuel_in']) * 100, 100) : 0; ?>%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);"></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">💰</div>
                        <div class="stat-number">₱<?php echo number_format($stats['total_cost'], 2); ?></div>
                        <div class="stat-label">Total Cost</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: 100%; background: linear-gradient(90deg, #17a2b8 0%, #138496 100%);"></div>
                        </div>
                    </div>
                </div>

                <!-- Oil Stats Section -->
                <div class="stats-grid" style="margin-top: 20px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);">🛢️</div>
                        <div class="stat-number"><?php echo number_format($stats['total_oil_in'], 2); ?></div>
                        <div class="stat-label">Total Oil In (Liters)</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: 100%; background: linear-gradient(90deg, #8B4513 0%, #A0522D 100%);"></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #CD853F 0%, #D2691E 100%);">📤</div>
                        <div class="stat-number"><?php echo number_format($stats['total_oil_out'], 2); ?></div>
                        <div class="stat-label">Total Oil Out (Liters)</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo $stats['total_oil_in'] > 0 ? min(($stats['total_oil_out'] / $stats['total_oil_in']) * 100, 100) : 0; ?>%; background: linear-gradient(90deg, #CD853F 0%, #D2691E 100%);"></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #228B22 0%, #32CD32 100%);">⚖️</div>
                        <div class="stat-number" style="color: <?php echo $stats['available_oil'] >= 0 ? '#28a745' : '#dc3545'; ?>;"><?php echo number_format($stats['available_oil'], 2); ?></div>
                        <div class="stat-label">Available Oil (Liters)</div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?php echo $stats['total_oil_in'] > 0 ? min(($stats['available_oil'] / $stats['total_oil_in']) * 100, 100) : 0; ?>%; background: linear-gradient(90deg, #228B22 0%, #32CD32 100%);"></div>
                        </div>
                    </div>
                </div>

        <div class="section">
            <div class="section-header">
                <h2 class="section-title">⚖️ Fuel & Oil Balance</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Fuel/Oil Type</th>
                            <th>In (Liters)</th>
                            <th>Out (Liters)</th>
                            <th>Balance (Liters)</th>
                            <th>Fuel Type Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['fuel_balance_by_type'] as $balance): ?>
                        <?php 
                        $fuel_type_lower = strtolower($balance['fuel_type_name'] ?? '');
                        $balance_amount = $balance['balance'];
                        $fuel_in_amount = $balance['fuel_in'];
                        
                        // Calculate percentage remaining for open capacity system
                        $percentage_remaining = 0;
                        if ($fuel_in_amount > 0) {
                            $percentage_remaining = ($balance_amount / $fuel_in_amount) * 100;
                        }
                        
                        // Define status thresholds based on percentage remaining
                        if ($percentage_remaining <= 20) {
                            $fuel_status = 'Critical / Empty';
                            $status_color = '#dc3545'; // Red
                        } elseif ($percentage_remaining <= 40) {
                            $fuel_status = 'Low';
                            $status_color = '#fd7e14'; // Orange
                        } elseif ($percentage_remaining <= 70) {
                            $fuel_status = 'Good';
                            $status_color = '#28a745'; // Green
                        } else {
                            $fuel_status = 'Full';
                            $status_color = '#007bff'; // Blue
                        }
                        ?>
                        <tr>
                            <td><span class="badge badge-info">Fuel</span></td>
                            <td><strong><?php echo htmlspecialchars($balance['fuel_type_name'] ?? 'Unknown'); ?></strong></td>
                            <td><?php echo number_format($balance['fuel_in'], 2); ?></td>
                            <td><?php echo number_format($balance['fuel_out'], 2); ?></td>
                            <td>
                                <span style="color: <?php echo $balance['balance'] >= 0 ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                    <?php echo number_format($balance['balance'], 2); ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: <?php echo $status_color; ?>; font-weight: bold;">
                                    <?php echo $fuel_status; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($stats['oil_balance_by_type'])): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #6c757d;">
                                <em>No oil data available. 
                                <?php if ($stats['oil_in_count'] == 0 && $stats['oil_out_count'] == 0): ?>
                                    No oil records found in database. Add oil in/out records to see balance information.
                                <?php else: ?>
                                    Found <?php echo $stats['oil_in_count']; ?> oil in records and <?php echo $stats['oil_out_count']; ?> oil out records, but no balance data calculated.
                                    <br>Debug: Oil In Types: [<?php echo implode(', ', array_column($stats['debug_oil_in_types'], 'oil_type')); ?>]
                                    <br>Debug: Oil Out Types: [<?php echo implode(', ', array_column($stats['debug_oil_out_types'], 'all_oil_type')); ?>]
                                    <br>Debug: Balance Query Count: <?php echo $stats['debug_oil_balance_count']; ?> results
                                    <br>Debug: SQL Error: <?php echo htmlspecialchars($stats['debug_oil_balance_error'] ?? 'None'); ?>
                                    (Oil types: <?php echo $stats['oil_types_count']; ?> available)
                                <?php endif; ?>
                                </em>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($stats['oil_balance_by_type'] as $balance): ?>
                        <?php 
                        $oil_balance_amount = $balance['balance'];
                        $oil_in_amount = $balance['fuel_in'];
                        
                        // Calculate percentage remaining for open capacity system
                        $oil_percentage_remaining = 0;
                        if ($oil_in_amount > 0) {
                            $oil_percentage_remaining = ($oil_balance_amount / $oil_in_amount) * 100;
                        }
                        
                        // Define status thresholds based on percentage remaining
                        if ($oil_percentage_remaining <= 20) {
                            $oil_status = 'Critical / Empty';
                            $oil_status_color = '#dc3545'; // Red
                        } elseif ($oil_percentage_remaining <= 40) {
                            $oil_status = 'Low';
                            $oil_status_color = '#fd7e14'; // Orange
                        } elseif ($oil_percentage_remaining <= 70) {
                            $oil_status = 'Good';
                            $oil_status_color = '#28a745'; // Green
                        } else {
                            $oil_status = 'Full';
                            $oil_status_color = '#007bff'; // Blue
                        }
                        ?>
                        <tr>
                            <td><span class="badge badge-warning">Oil</span></td>
                            <td><strong><?php echo htmlspecialchars($balance['fuel_type_name'] ?? 'Unknown'); ?></strong></td>
                            <td><?php echo number_format($balance['fuel_in'], 2); ?></td>
                            <td><?php echo number_format($balance['fuel_out'], 2); ?></td>
                            <td>
                                <span style="color: <?php echo $balance['balance'] >= 0 ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                    <?php echo number_format($balance['balance'], 2); ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: <?php echo $oil_status_color; ?>; font-weight: bold;">
                                    <?php echo $oil_status; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fuel In Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📥 Fuel In Records</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Fuel Type</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Cost</th>
                            <th>Storage Location</th>
                            <th>Supplier</th>
                            <th>Received By</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($fuelInData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['date_time']); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['fuel_type_name'] ?? 'Unknown'); ?></span></td>
                            <td><?php echo number_format($row['quantity'], 2); ?></td>
                            <td>₱<?php echo number_format($row['unit_price'], 2); ?></td>
                            <td>₱<?php echo number_format($row['total_cost'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['storage_location']); ?></td>
                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['received_by']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fuel Out Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📤 Fuel Out Records</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Fuel No</th>
                            <th>Plate No</th>
                            <th>Fuel Type</th>
                            <th>Liters</th>
                            <th>Vehicle Type</th>
                            <th>Receiver</th>
                            <th>Time Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($fuelOutData, 0, 10) as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['fo_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['fo_time_in']); ?></td>
                            <td><?php echo htmlspecialchars($row['fo_fuel_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['fo_plate_no']); ?></td>
                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($row['fuel_type_name'] ?? 'Unknown'); ?></span></td>
                            <td><?php echo number_format($row['fo_liters'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['fo_vehicle_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['fo_receiver']); ?></td>
                            <td><?php echo htmlspecialchars($row['fo_time_out']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
        </main>
    </div>

    <button class="refresh-btn" onclick="location.reload()" title="Refresh Dashboard">
        🔄
    </button>

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Add real-time clock
        function updateClock() {
            const now = new Date();
            const clock = document.createElement('div');
            clock.style.cssText = 'position: fixed; top: 20px; right: 20px; background: rgba(255,255,255,0.9); padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);';
            clock.innerHTML = now.toLocaleString();
            document.body.appendChild(clock);
            
            setInterval(() => {
                clock.innerHTML = new Date().toLocaleString();
            }, 1000);
        }
        
        updateClock();
        
        // Sidebar Toggle Function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const navbar = document.getElementById('navbar');
            const mainContent = document.getElementById('main-content');
            
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('sidebar-hidden');
            
            if (sidebar.classList.contains('hidden')) {
                navbar.classList.remove('sidebar-expanded');
                navbar.classList.add('sidebar-collapsed');
            } else {
                navbar.classList.remove('sidebar-collapsed');
                navbar.classList.add('sidebar-expanded');
            }
            
            // Save sidebar state to localStorage
            localStorage.setItem('sidebarHidden', sidebar.classList.contains('hidden'));
        }
        
        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
            if (sidebarHidden) {
                toggleSidebar();
            }
        });
    </script>
</body>
</html>
