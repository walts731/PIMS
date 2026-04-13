<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pims_final';

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

// Simple SQL for fuel and oil transactions
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
        fi.created_at,
        fi.image
    FROM fuel_in fi
    LEFT JOIN fuel_types ft ON fi.fuel_type = ft.id
    WHERE DATE(fi.date_time) BETWEEN ? AND ?
    
    UNION ALL
    
    SELECT 
        'FUEL OUT' as transaction_type,
        fo.id,
        CONCAT(fo.fo_date, ' ', fo.fo_time_in) as transaction_date,
        ft.name as fuel_type_name,
        fo.fo_liters as quantity,
        fo.office_name as source,
        '-' as supplier,
        fo.fo_receiver as recipient_name,
        fo.fo_request as purpose,
        fo.fo_vehicle_type as vehicle_equipment,
        '-' as odometer_reading,
        '-' as odometer_unit,
        '-' as tank_number,
        fo.created_by as user_id,
        fo.created_at,
        fo.image
    FROM fuel_out fo
    LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
    WHERE DATE(fo.fo_date) BETWEEN ? AND ?";

$params = [$start_date, $end_date, $start_date, $end_date];
$types = "ssss";

// Check if oil_in table exists and add oil in transactions
$oil_in_check = $conn->query("SHOW TABLES LIKE 'oil_in'");
if ($oil_in_check && $oil_in_check->num_rows > 0) {
    $sql .= "
        
        UNION ALL
        
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
        
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
}

// Check if oil_out table exists and add oil out transactions
$oil_out_check = $conn->query("SHOW TABLES LIKE 'oil_out'");
if ($oil_out_check && $oil_out_check->num_rows > 0) {
    $sql .= "
        
        UNION ALL
        
        SELECT 
            'OIL OUT' as transaction_type,
            oo.id,
            CONCAT(oo.oo_date, ' ', oo.oo_time_in) as transaction_date,
            ot.name as fuel_type_name,
            oo.oo_liters as quantity,
            oo.office_name as source,
            '-' as supplier,
            oo.oo_receiver as recipient_name,
            oo.oo_request as purpose,
            oo.oo_vehicle_type as vehicle_equipment,
            '-' as odometer_reading,
            '-' as odometer_unit,
            '-' as tank_number,
            oo.created_by as user_id,
            oo.created_at,
            oo.image
        FROM oil_out oo
        LEFT JOIN oil_types ot ON oo.oo_oil_type = ot.id
        WHERE DATE(oo.oo_date) BETWEEN ? AND ?";
        
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
}

$sql .= " ORDER BY transaction_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt && $sql) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $fuelTransactionsData = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $fuelTransactionsData = [];
}

// Get fuel types for dropdown
$fuelTypesResult = $conn->query("SELECT id, name FROM fuel_types WHERE is_active = 1 ORDER BY name");
$fuelTypes = $fuelTypesResult ? $fuelTypesResult->fetch_all(MYSQLI_ASSOC) : [];

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
            padding: 30px 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
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
        }

        .section-title {
            font-size: 1.5rem;
            color: #495057;
            font-weight: 500;
        }

        .filter-container {
            padding: 30px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
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
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
            font-size: 1rem;
            margin-right: 10px;
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

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
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
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .filter-form {
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⛽ Fuel System</h2>
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
        <main class="main-content">
            <header>
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
                            <div class="filter-group">
                                <label for="transaction_type">Transaction Type</label>
                                <select name="transaction_type" id="transaction_type" onchange="this.form.submit()">
                                    <option value="">All Transactions</option>
                                    <option value="FUEL IN" <?php echo $transaction_type_filter === 'FUEL IN' ? 'selected' : ''; ?>>Fuel In</option>
                                    <option value="FUEL OUT" <?php echo $transaction_type_filter === 'FUEL OUT' ? 'selected' : ''; ?>>Fuel Out</option>
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
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="fuel_transactions.php" class="btn btn-success">Reset</a>
                        </form>
                    </div>
                </div>

                <!-- Fuel Transactions Table -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Fuel Transactions</h2>
                    </div>
                    <div class="table-container">
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
                                    <th>Purpose</th>
                                    <th>Vehicle/Equipment</th>
                                    <th>Odometer</th>
                                    <th>User ID</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelTransactionsData as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['transaction_type']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fuel_type_name']); ?></td>
                                    <td><?php echo number_format($row['quantity'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['source']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                                    <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($row['vehicle_equipment']); ?></td>
                                    <td><?php echo htmlspecialchars($row['odometer_reading']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
