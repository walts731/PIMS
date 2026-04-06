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

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'day';
$fuel_type_filter = $_GET['fuel_type'] ?? '';
$transaction_type_filter = $_GET['transaction_type'] ?? '';
$oil_type_filter = $_GET['oil_type'] ?? '';
$view_mode = $_GET['view_mode'] ?? 'excel'; // 'excel' or 'standard'

// Initialize variables to prevent undefined errors
$sql = "";
$params = [];
$types = "";

// Calculate date range based on period
switch($period) {
    case 'day':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    default:
        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        break;
}

// Get all transactions from fuel_in, fuel_out, oil_in, and oil_out tables with filters
if (!empty($transaction_type_filter)) {
    // With transaction type filter only
    if ($transaction_type_filter === 'FUEL IN') {
        $sql = "
            SELECT 
                'FUEL IN' as transaction_type,
                fi.id,
                fi.date_time as transaction_date,
                ft.name as fuel_type_name,
                fi.quantity,
                fi.storage_location as source,
                fi.supplier_name as supplier,
                fi.received_by as recipient_name,
                fi.remarks as purpose,
                '-' as vehicle_equipment,
                '-' as odometer_reading,
                '-' as odometer_unit,
                '-' as tank_number,
                fi.created_by as user_id,
                fi.created_at
            FROM fuel_in fi
            LEFT JOIN fuel_types ft ON fi.fuel_type = ft.id
            WHERE DATE(fi.date_time) BETWEEN ? AND ?";
        
        $params = [$start_date, $end_date];
        $types = "ss";
        
        // Add fuel type filter if specified
        if (!empty($fuel_type_filter)) {
            $sql .= " AND ft.name = ?";
            $params[] = $fuel_type_filter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY transaction_date DESC";
        
    } elseif ($transaction_type_filter === 'FUEL OUT') {
        $sql = "
            SELECT 
                'FUEL OUT' as transaction_type,
                fo.id,
                fo.fo_date as transaction_date,
                ft.name as fuel_type_name,
                fo.fo_liters as quantity,
                '-' as source,
                '-' as supplier,
                fo.fo_receiver as recipient_name,
                fo.fo_request as purpose,
                fo.fo_vehicle_type as vehicle_equipment,
                '-' as odometer_reading,
                '-' as odometer_unit,
                '-' as tank_number,
                fo.created_by as user_id,
                fo.created_at
            FROM fuel_out fo
            LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
            WHERE DATE(fo.fo_date) BETWEEN ? AND ?";
        
        $params = [$start_date, $end_date];
        $types = "ss";
        
        // Add fuel type filter if specified
        if (!empty($fuel_type_filter)) {
            $sql .= " AND ft.name = ?";
            $params[] = $fuel_type_filter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY fo.fo_date ASC, fo.fo_time_in ASC";
        
    } elseif ($transaction_type_filter === 'OIL IN') {
        $sql = "
            SELECT 
                'OIL IN' as transaction_type,
                oi.id,
                oi.date_time as transaction_date,
                ot.name as fuel_type_name,
                oi.quantity,
                oi.storage_location as source,
                oi.supplier_name as supplier,
                oi.received_by as recipient_name,
                oi.remarks as purpose,
                '-' as vehicle_equipment,
                '-' as odometer_reading,
                '-' as odometer_unit,
                '-' as tank_number,
                oi.created_by as user_id,
                oi.created_at,
                oi.image
            FROM oil_in oi
            LEFT JOIN oil_types ot ON oi.oil_type = ot.id
            WHERE DATE(oi.date_time) BETWEEN ? AND ?";
        
        $params = [$start_date, $end_date];
        $types = "ss";
        
        // Add oil type filter if specified
        if (!empty($oil_type_filter)) {
            $sql .= " AND ot.name = ?";
            $params[] = $oil_type_filter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY transaction_date DESC";
        
    } elseif ($transaction_type_filter === 'OIL OUT') {
        $sql = "
            SELECT 
                'OIL OUT' as transaction_type,
                oo.id,
                CONCAT(oo.oil_date, ' ', oo.oil_time_in) as transaction_date,
                ot.name as fuel_type_name,
                oo.oil_liters as quantity,
                oo.office_name as source,
                '-' as supplier,
                oo.oil_receiver as recipient_name,
                oo.oil_request as purpose,
                oo.oil_vehicle_type as vehicle_equipment,
                '-' as odometer_reading,
                '-' as odometer_unit,
                '-' as tank_number,
                oo.created_by as user_id,
                oo.created_at,
                oo.image
            FROM oil_out oo
            LEFT JOIN oil_types ot ON oo.all_oil_type = ot.id
            WHERE DATE(oo.oil_date) BETWEEN ? AND ?";
        
        $params = [$start_date, $end_date];
        $types = "ss";
        
        // Add oil type filter if specified
        if (!empty($oil_type_filter)) {
            $sql .= " AND ot.name = ?";
            $params[] = $oil_type_filter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY transaction_date DESC";
        
    } else {
        // Invalid transaction type
        $sql = "";
        $params = [];
        $types = "";
    }
} else {
    // No transaction type filter - show all applicable transactions
    $sql_parts = [];
    $all_params = [];
    $all_types = "";
    
    // Add Fuel In transactions (if fuel type filter is not set or matches)
    if (empty($fuel_type_filter)) {
        $sql_parts[] = "
            SELECT 
                'FUEL IN' as transaction_type,
                fi.id,
                fi.date_time as transaction_date,
                ft.name as fuel_type_name,
                fi.quantity,
                fi.storage_location as source,
                fi.supplier_name as supplier,
                fi.received_by as recipient_name,
                fi.remarks as purpose,
                '-' as vehicle_equipment,
                '-' as odometer_reading,
                '-' as odometer_unit,
                '-' as tank_number,
                fi.created_by as user_id,
                fi.created_at
            FROM fuel_in fi
            LEFT JOIN fuel_types ft ON fi.fuel_type = ft.id
            WHERE DATE(fi.date_time) BETWEEN ? AND ?";
        $all_params = array_merge($all_params, [$start_date, $end_date]);
        $all_types .= "ss";
    }
    
    // Add Fuel Out transactions (if fuel type filter is not set or matches)
    if (empty($fuel_type_filter)) {
        $sql_parts[] = "
            SELECT 
                'FUEL OUT' as transaction_type,
                fo.id,
                CONCAT(fo.fo_date, ' ', fo.fo_time_in) as transaction_date,
                ft.name as fuel_type_name,
                fo.fo_liters as quantity,
                '-' as source,
                '-' as supplier,
                fo.fo_receiver as recipient_name,
                fo.fo_request as purpose,
                fo.fo_vehicle_type as vehicle_equipment,
                '-' as odometer_reading,
                '-' as odometer_unit,
                '-' as tank_number,
                fo.created_by as user_id,
                fo.created_at
            FROM fuel_out fo
            LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
            WHERE DATE(fo.fo_date) BETWEEN ? AND ?";
        $all_params = array_merge($all_params, [$start_date, $end_date]);
        $all_types .= "ss";
    }
    
    // Oil In transactions DISABLED (oil_in table doesn't exist)
    
    // Oil Out transactions DISABLED (oil_out table doesn't exist)
    
    // Combine all SQL parts with UNION ALL
    if (!empty($sql_parts)) {
        $sql = implode(" UNION ALL ", $sql_parts) . " ORDER BY transaction_date DESC";
        $params = $all_params;
        $types = $all_types;
    } else {
        $sql = "";
        $params = [];
        $types = "";
    }
}

$stmt = $conn->prepare($sql);
if ($stmt && $sql) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $fuelTransactionsData = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $fuelTransactionsData = [];
    // Debug: Show error if SQL preparation failed
    if (!$sql) {
        error_log("SQL Error: Empty SQL query");
    } elseif (!$stmt) {
        error_log("SQL Error: " . $conn->error);
        error_log("SQL Query: " . $sql);
    }
}

// Get fuel types for dropdown
$fuelTypesResult = $conn->query("SELECT id, name FROM fuel_types WHERE is_active = 1 ORDER BY name");
$fuelTypes = $fuelTypesResult ? $fuelTypesResult->fetch_all(MYSQLI_ASSOC) : [];

// Get oil types for dropdown
$oilTypesResult = $conn->query("SELECT id, name FROM oil_types ORDER BY name");
if (!$oilTypesResult) {
    // If the query fails, try without any WHERE clause (in case table structure is different)
    $oilTypesResult = $conn->query("SELECT id, name FROM oil_types");
}
$oilTypes = $oilTypesResult ? $oilTypesResult->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel & Oil Transactions Management</title>
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
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
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

        .section {
            background: white;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.5rem;
            color: #495057;
            font-weight: 500;
        }

        .form-container {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background-color: #5a6fd8;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        #voiceCommandBtn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
        }

        #voiceCommandBtn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        #voiceCommandBtn.listening {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
            100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
        }

        #voiceStatus {
            font-size: 0.9rem;
            font-weight: 500;
        }

        #voiceStatus.listening {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        #voiceStatus.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        #voiceStatus.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Excel Table Styles */
        .excel-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            background: white;
        }

        .excel-table th,
        .excel-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .excel-table .excel-header {
            background: #4472C4;
            color: white;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        .excel-table .excel-header th {
            background: #4472C4;
            color: white;
            border: 1px solid #000;
            white-space: nowrap;
        }

        .excel-table .excel-row:nth-child(even) {
            background-color: #f9f9f9;
        }

        .excel-table .excel-row:hover {
            background-color: #e8f4f8;
        }

        .excel-table .cell-center {
            text-align: center;
        }

        .excel-table .cell-liters {
            background-color: #FFF2CC;
            text-align: center;
            font-weight: 600;
        }

        .excel-table .col-no {
            width: 40px;
            background: #4472C4;
        }

        .excel-table .col-date {
            width: 70px;
        }

        .excel-table .col-time {
            width: 60px;
        }

        .excel-table .col-fuel-no {
            width: 90px;
        }

        .excel-table .col-plate {
            width: 90px;
        }

        .excel-table .col-request {
            width: 70px;
        }

        .excel-table .col-liters {
            width: 90px;
        }

        .excel-table .col-vehicle {
            width: 130px;
        }

        .excel-table .col-receiver {
            width: 140px;
        }

        .excel-table .col-timeout {
            width: 70px;
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

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .filter-container {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
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
                🔄 Fuel Transactions Management
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
                        <a href="fuel_transactions.php" class="active">
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
                    <h1>🔄 Fuel & Oil Transactions Management</h1>
                </div>
            </header>

            <div class="container">
                <!-- Filters -->
                <div class="section">
                    <div class="filter-container">
                        <form method="GET" class="filter-form">
                            <div class="filter-group">
                                <label for="period">Period</label>
                                <select name="period" id="period" onchange="this.form.submit()">
                                    <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                            <div class="filter-group" id="custom-date-range" style="<?php echo $period === 'custom' ? 'display: block;' : 'display: none;' ?>">
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="filter-group" id="custom-end-date" style="<?php echo $period === 'custom' ? 'display: block;' : 'display: none;' ?>">
                                <label for="end_date">End Date</label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="transaction_type">Transaction Type</label>
                                <select name="transaction_type" id="transaction_type" onchange="this.form.submit()">
                                    <option value="">All Transactions</option>
                                    <option value="FUEL IN" <?php echo $transaction_type_filter === 'FUEL IN' ? 'selected' : ''; ?>>Fuel In</option>
                                    <option value="FUEL OUT" <?php echo $transaction_type_filter === 'FUEL OUT' ? 'selected' : ''; ?>>Fuel Out</option>
                                    <option value="OIL IN" <?php echo $transaction_type_filter === 'OIL IN' ? 'selected' : ''; ?>>Oil In</option>
                                    <option value="OIL OUT" <?php echo $transaction_type_filter === 'OIL OUT' ? 'selected' : ''; ?>>Oil Out</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="fuel_type">Fuel Type</label>
                                <select name="fuel_type" id="fuel_type" onchange="this.form.submit()">
                                    <option value="">All Fuel Types</option>
                                    <?php foreach ($fuelTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['name']); ?>" 
                                                <?php echo $fuel_type_filter === $type['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="oil_type">Oil Type</label>
                                <select name="oil_type" id="oil_type" onchange="this.form.submit()">
                                    <option value="">All Oil Types</option>
                                    <?php foreach ($oilTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['name']); ?>" 
                                                <?php echo $oil_type_filter === $type['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="fuel_transactions.php" class="btn btn-success">Reset</a>
                            <button type="button" class="btn btn-info" id="voiceCommandBtn" onclick="toggleVoiceCommand()" title="Voice Command">
                                🎤 Voice
                            </button>
                        </form>
                        <div id="voiceStatus" style="margin-top: 10px; padding: 8px 12px; border-radius: 4px; display: none;"></div>
                    </div>
                </div>

                <!-- Fuel Transactions Table -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><?php echo ($transaction_type_filter === 'FUEL OUT' || empty($transaction_type_filter)) ? '📋 Fuel Out Transactions (Excel Format)' : 'Fuel & Oil Transactions'; ?></h2>
                        <?php if ($transaction_type_filter === 'FUEL OUT' || empty($transaction_type_filter)): ?>
                        <div style="display: flex; gap: 10px;">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['view_mode' => 'excel'])); ?>" class="btn <?php echo $view_mode === 'excel' ? 'btn-primary' : 'btn-info'; ?>">Excel View</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['view_mode' => 'standard'])); ?>" class="btn <?php echo $view_mode === 'standard' ? 'btn-primary' : 'btn-info'; ?>">Standard View</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="table-container">
                        <?php if (($transaction_type_filter === 'FUEL OUT' || empty($transaction_type_filter)) && $view_mode === 'excel'): ?>
                        <!-- Excel Style Table for Fuel Out -->
                        <table class="excel-table">
                            <thead>
                                <tr class="excel-header">
                                    <th class="col-no">NO.</th>
                                    <th class="col-date">DATE</th>
                                    <th class="col-time">TIME-IN</th>
                                    <th class="col-fuel-no">FUEL NO.</th>
                                    <th class="col-plate">PLATE NO.</th>
                                    <th class="col-request">REQUEST</th>
                                    <th class="col-liters">NO.OF LITERS</th>
                                    <th class="col-vehicle">TYPE OF VEHICLE</th>
                                    <th class="col-receiver">NAME OF RECEIVER</th>
                                    <th class="col-timeout">TIME-OUT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                foreach ($fuelTransactionsData as $row): 
                                    if ($row['transaction_type'] !== 'FUEL OUT' && !empty($transaction_type_filter)) continue;
                                    if ($row['transaction_type'] !== 'FUEL OUT') continue;
                                ?>
                                <tr class="excel-row">
                                    <td class="cell-center"><?php echo $counter++; ?></td>
                                    <td class="cell-center"><?php echo date('n/j/Y', strtotime($row['transaction_date'])); ?></td>
                                    <td class="cell-center"><?php echo htmlspecialchars($row['fo_time_in']); ?></td>
                                    <td class="cell-center"><?php echo htmlspecialchars($row['fo_fuel_no']); ?></td>
                                    <td class="cell-center"><?php echo htmlspecialchars($row['fo_plate_no']); ?></td>
                                    <td class="cell-center"><?php echo htmlspecialchars($row['fo_request']); ?></td>
                                    <td class="cell-liters"><?php echo number_format($row['quantity'], 1); ?></td>
                                    <td class="cell-center"><?php echo htmlspecialchars($row['fo_vehicle_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fo_receiver']); ?></td>
                                    <td class="cell-center"><?php echo htmlspecialchars($row['fo_time_out']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <!-- Standard Table -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Transaction Type</th>
                                    <th>Date & Time</th>
                                    <th>Fuel Type</th>
                                    <th>Quantity</th>
                                    <th>Source/Location</th>
                                    <th>Supplier</th>
                                    <th>Recipient/Receiver</th>
                                    <th>Purpose/Request</th>
                                    <th>Vehicle/Equipment</th>
                                    <th>Created By</th>
                                    <th>Image</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelTransactionsData as $row): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-info';
                                        switch($row['transaction_type']) {
                                            case 'FUEL IN': $badge_class = 'badge-success'; break;
                                            case 'FUEL OUT': $badge_class = 'badge-warning'; break;
                                            case 'OIL IN': $badge_class = 'badge-info'; break;
                                            case 'OIL OUT': $badge_class = 'badge-danger'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['transaction_type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fuel_type_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo number_format($row['quantity'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['source']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                                    <td><?php echo htmlspecialchars($row['recipient_name'] ?? $row['fo_receiver']); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose'] ?? $row['fo_request']); ?></td>
                                    <td><?php echo htmlspecialchars($row['vehicle_equipment'] ?? $row['fo_vehicle_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Fuel Image" style="max-width: 60px; max-height: 40px; border-radius: 4px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($row['image']); ?>', '_blank');">
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-size: 0.8em;">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Handle period filter visibility
        document.getElementById('period').addEventListener('change', function() {
            const customDateRange = document.getElementById('custom-date-range');
            const customEndDate = document.getElementById('custom-end-date');
            
            if (this.value === 'custom') {
                customDateRange.style.display = 'block';
                customEndDate.style.display = 'block';
            } else {
                customDateRange.style.display = 'none';
                customEndDate.style.display = 'none';
            }
        });

        // Voice Command Functionality
        let recognition = null;
        let isListening = false;

        // Initialize speech recognition
        function initVoiceCommand() {
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                recognition = new SpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.lang = 'en-US';

                recognition.onstart = function() {
                    isListening = true;
                    updateVoiceStatus('Listening... Speak now', 'listening');
                    document.getElementById('voiceCommandBtn').classList.add('listening');
                };

                recognition.onend = function() {
                    isListening = false;
                    document.getElementById('voiceCommandBtn').classList.remove('listening');
                };

                recognition.onresult = function(event) {
                    const command = event.results[0][0].transcript.toLowerCase().trim();
                    updateVoiceStatus('Heard: "' + command + '"', 'success');
                    processVoiceCommand(command);
                };

                recognition.onerror = function(event) {
                    updateVoiceStatus('Error: ' + event.error, 'error');
                    isListening = false;
                    document.getElementById('voiceCommandBtn').classList.remove('listening');
                };
            } else {
                updateVoiceStatus('Voice commands not supported in this browser', 'error');
                document.getElementById('voiceCommandBtn').style.display = 'none';
            }
        }

        function toggleVoiceCommand() {
            if (!recognition) {
                initVoiceCommand();
            }
            
            if (isListening) {
                recognition.stop();
                updateVoiceStatus('Stopped listening', '');
            } else {
                recognition.start();
            }
        }

        function updateVoiceStatus(message, type) {
            const statusDiv = document.getElementById('voiceStatus');
            statusDiv.textContent = message;
            statusDiv.style.display = 'block';
            statusDiv.className = type;
            
            if (type === 'success') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);
            }
        }

        function processVoiceCommand(command) {
            let filterApplied = false;
            
            // Period filter commands
            const periodMap = {
                'today': 'day',
                'day': 'day',
                'week': 'week',
                'this week': 'week',
                'month': 'month',
                'this month': 'month',
                'year': 'year',
                'this year': 'year',
                'custom': 'custom'
            };
            
            for (const [key, value] of Object.entries(periodMap)) {
                if (command.includes(key)) {
                    document.getElementById('period').value = value;
                    // Trigger change event to show/hide custom date fields
                    document.getElementById('period').dispatchEvent(new Event('change'));
                    filterApplied = true;
                    updateVoiceStatus('Set Period to: ' + key, 'success');
                    break;
                }
            }
            
            // Transaction Type filter commands
            if (command.includes('fuel in') || command.includes('in only') || command.includes('show in') || command.includes('incoming')) {
                document.getElementById('transaction_type').value = 'IN';
                filterApplied = true;
                updateVoiceStatus('Set Transaction Type to: Fuel In', 'success');
            } else if (command.includes('fuel out') || command.includes('out only') || command.includes('show out') || command.includes('outgoing')) {
                document.getElementById('transaction_type').value = 'OUT';
                filterApplied = true;
                updateVoiceStatus('Set Transaction Type to: Fuel Out', 'success');
            } else if (command.includes('all transactions') || command.includes('show all') || command.includes('clear transaction')) {
                document.getElementById('transaction_type').value = '';
                filterApplied = true;
                updateVoiceStatus('Set Transaction Type to: All', 'success');
            }
            
            // Fuel Type filter commands - dynamically check available options
            const fuelTypeSelect = document.getElementById('fuel_type');
            const fuelOptions = Array.from(fuelTypeSelect.options).map(opt => opt.value.toLowerCase());
            
            for (const option of fuelOptions) {
                if (option && command.includes(option)) {
                    fuelTypeSelect.value = option.charAt(0).toUpperCase() + option.slice(1);
                    filterApplied = true;
                    updateVoiceStatus('Set Fuel Type to: ' + option, 'success');
                    break;
                }
            }
            
            // Oil Type filter commands - dynamically check available options
            const oilTypeSelect = document.getElementById('oil_type');
            const oilOptions = Array.from(oilTypeSelect.options).map(opt => opt.value.toLowerCase());
            
            for (const option of oilOptions) {
                if (option && command.includes(option)) {
                    oilTypeSelect.value = option.charAt(0).toUpperCase() + option.slice(1);
                    filterApplied = true;
                    updateVoiceStatus('Set Oil Type to: ' + option, 'success');
                    break;
                }
            }
            
            // Clear all filters command
            if (command.includes('clear all') || command.includes('reset all') || command.includes('show everything')) {
                document.getElementById('period').value = 'day';
                document.getElementById('transaction_type').value = '';
                document.getElementById('fuel_type').value = '';
                document.getElementById('oil_type').value = '';
                filterApplied = true;
                updateVoiceStatus('All filters cleared', 'success');
            }
            
            // Apply filters command
            if (filterApplied || command.includes('apply') || command.includes('filter') || command.includes('search')) {
                setTimeout(() => {
                    document.querySelector('.filter-form').submit();
                }, 1000);
            }
            
            if (!filterApplied) {
                updateVoiceStatus('No filter matched. Try: "today", "fuel in", "diesel", "clear all"', 'error');
            }
        }

        // Initialize voice command on page load
        document.addEventListener('DOMContentLoaded', function() {
            initVoiceCommand();
            
            // Restore sidebar state on page load
            const sidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
            if (sidebarHidden) {
                toggleSidebar();
            }
        });
        
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
    </script>
</body>
</html>
