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

// Handle form submission for adding new fuel out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_fuel_out') {
    $fo_date = $_POST['fo_date'];
    $fo_time_in = $_POST['fo_time_in'];
    $fo_fuel_no = $_POST['fo_fuel_no'];
    $fo_plate_no = $_POST['fo_plate_no'];
    $fo_request = $_POST['fo_request'];
    $fo_fuel_type = $_POST['fo_fuel_type'];
    $fo_liters = $_POST['fo_liters'];
    $fo_vehicle_type = $_POST['fo_vehicle_type'];
    $fo_receiver = $_POST['fo_receiver'];
    $fo_time_out = $_POST['fo_time_out'];
    $created_by = $_POST['created_by'];
    
    if (!isset($error_message)) {

    $sql = "INSERT INTO fuel_out (fo_date, fo_time_in, fo_fuel_no, fo_plate_no, fo_request, fo_fuel_type, 
            fo_liters, fo_vehicle_type, fo_receiver, fo_time_out, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssdsssss", $fo_date, $fo_time_in, $fo_fuel_no, $fo_plate_no, $fo_request, 
                      $fo_fuel_type, $fo_liters, $fo_vehicle_type, $fo_receiver, $fo_time_out, $created_by);
    
    if ($stmt->execute()) {
        $success_message = "Fuel Out record added successfully!";
    } else {
        $error_message = "Error adding record: " . $stmt->error;
    }
    $stmt->close();
    }
}

// Get fuel out data with fuel type names
$sql = "SELECT fo.id, fo.fo_date, fo.fo_time_in, fo.fo_fuel_no, fo.fo_plate_no, fo.fo_request, fo.fo_fuel_type, 
               fo.fo_liters, fo.fo_vehicle_type, fo.fo_receiver, fo.fo_time_out, fo.created_by, fo.created_at,
               ft.name as fuel_type_name
        FROM fuel_out fo
        LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
        ORDER BY fo.fo_date DESC, fo.fo_time_in DESC";
$result = $conn->query($sql);
$fuelOutData = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

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
    <title>Fuel Out Management</title>
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
            flex: 1;
        }

        .section-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
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

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
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
                📤 Fuel Out Management
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
                        <a href="fuel_out.php" class="active">
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
                            <span class="icon">�</span>
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
                    <h1>📤 Fuel Out Management</h1>
                </div>
            </header>

            <div class="container">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Add Fuel Out Form -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-actions">
                            <button id="toggleFormBtn" class="btn btn-primary">+ Add New Fuel Out Record</button>
                        </div>
                    </div>
                    <div id="addFuelOutForm" class="form-container" style="display: none;">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_fuel_out">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="fo_date">Date</label>
                                    <input type="date" name="fo_date" id="fo_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="fo_time_in">Time In</label>
                                    <input type="time" name="fo_time_in" id="fo_time_in" required>
                                </div>
                                <div class="form-group">
                                    <label for="fo_fuel_no">Fuel No</label>
                                    <input type="text" name="fo_fuel_no" id="fo_fuel_no" required>
                                </div>
                                <div class="form-group">
                                    <label for="fo_plate_no">Plate No</label>
                                    <input type="text" name="fo_plate_no" id="fo_plate_no" required>
                                </div>
                                <div class="form-group">
                                    <label for="fo_request">Request</label>
                                    <input type="text" name="fo_request" id="fo_request">
                                </div>
                                <div class="form-group">
                                    <label for="fo_fuel_type">Fuel Type</label>
                                    <select name="fo_fuel_type" id="fo_fuel_type" required>
                                        <option value="">Select Fuel Type</option>
                                        <?php foreach ($fuelTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['id']); ?>">
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="fo_liters">Liters</label>
                                    <input type="number" step="0.01" name="fo_liters" id="fo_liters" required>
                                </div>
                                <div class="form-group">
                                    <label for="fo_vehicle_type">Vehicle Type</label>
                                    <input type="text" name="fo_vehicle_type" id="fo_vehicle_type" required>
                                </div>
                                <div class="form-group">
                                    <label for="fo_receiver">Receiver</label>
                                    <input type="text" name="fo_receiver" id="fo_receiver" required>
                                </div>
                                <div class="form-group">
                                    <label for="fo_time_out">Time Out</label>
                                    <input type="time" name="fo_time_out" id="fo_time_out">
                                </div>
                                <div class="form-group">
                                    <label for="created_by">Created By</label>
                                    <input type="text" name="created_by" id="created_by" required>
                                </div>
                                <div class="form-group">
                                    <label for="office_name">Office Name</label>
                                    <input type="text" name="office_name" id="office_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="image">Upload Image</label>
                                    <input type="file" name="image" id="image" accept="image/*">
                                    <small style="color: #6c757d; font-size: 0.875rem; margin-top: 5px; display: block;">Allowed formats: JPG, JPEG, PNG, GIF</small>
                                </div>
                            </div>
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-warning">Add Fuel Out Record</button>
                                <button type="button" id="cancelFormBtn" class="btn" style="background-color: #6c757d; color: white; margin-left: 10px;">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Fuel Out Records Table -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Fuel Out Records</h2>
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
                                    <th>Created By</th>
                                    <th>Office Name</th>
                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelOutData as $row): ?>
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
                                    <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                    <td><?php echo htmlspecialchars($row['office_name']); ?></td>
                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Fuel Image" style="max-width: 80px; max-height: 60px; border-radius: 4px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($row['image']); ?>', '_blank');">
                                        <?php else: ?>
                                            <span style="color: #6c757d;">No image</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Set current date as default
        document.getElementById('fo_date').value = new Date().toISOString().split('T')[0];
        
        // Set current time as default for time in
        document.getElementById('fo_time_in').value = new Date().toTimeString().slice(0, 5);
        
        // Toggle form visibility
        const toggleFormBtn = document.getElementById('toggleFormBtn');
        const addFuelOutForm = document.getElementById('addFuelOutForm');
        const cancelFormBtn = document.getElementById('cancelFormBtn');
        let isFormVisible = false;
        
        toggleFormBtn.addEventListener('click', function() {
            if (isFormVisible) {
                addFuelOutForm.style.display = 'none';
                toggleFormBtn.textContent = '+ Add New Fuel Out Record';
                isFormVisible = false;
            } else {
                addFuelOutForm.style.display = 'block';
                toggleFormBtn.textContent = '- Hide Form';
                isFormVisible = true;
            }
        });
        
        cancelFormBtn.addEventListener('click', function() {
            addFuelOutForm.style.display = 'none';
            toggleFormBtn.textContent = '+ Add New Fuel Out Record';
            isFormVisible = false;
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
