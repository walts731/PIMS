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

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_oil_out') {
    $oil_date = $_POST['oil_date'];
    $oil_time_in = $_POST['oil_time_in'];
    $oil_oil_no = $_POST['oil_oil_no'];
    $oil_plate_no = $_POST['oil_plate_no'];
    $oil_request = $_POST['oil_request'];
    $all_oil_type = $_POST['all_oil_type'];
    $oil_liters = $_POST['oil_liters'];
    $oil_vehicle_type = $_POST['oil_vehicle_type'];
    $oil_receiver = $_POST['oil_receiver'];
    $oil_time_out = $_POST['oil_time_out'];
    $created_by = $_POST['created_by'];
    $office_name = $_POST['office_name'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Check if image file is actual image
        $image_info = getimagesize($_FILES['image']['tmp_name']);
        if ($image_info !== false) {
            // Allow certain file formats
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $error_message = "Error uploading image file.";
                }
            } else {
                $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        } else {
            $error_message = "File is not an image.";
        }
    }
    
    if (empty($error_message)) {
        $sql = "INSERT INTO oil_out (oil_date, oil_time_in, oil_oil_no, oil_plate_no, oil_request, all_oil_type, 
                oil_liters, oil_vehicle_type, oil_receiver, oil_time_out, created_by, office_name, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssss", $oil_date, $oil_time_in, $oil_oil_no, $oil_plate_no, $oil_request, 
                          $all_oil_type, $oil_liters, $oil_vehicle_type, $oil_receiver, $oil_time_out, $created_by, $office_name, $image_path);
        
        if ($stmt->execute()) {
            $success_message = "Oil Out record added successfully!";
        } else {
            $error_message = "Error adding record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get oil types for dropdown
$oilTypesResult = $conn->query("SELECT id, name FROM oil_types WHERE is_active = 1 ORDER BY name");
$oilTypes = $oilTypesResult ? $oilTypesResult->fetch_all(MYSQLI_ASSOC) : [];

// Calculate available oil balance
$oilInResult = $conn->query("SELECT SUM(quantity) as total_in FROM oil_in");
$totalOilIn = $oilInResult->fetch_assoc()['total_in'] ?? 0;

$oilOutResult = $conn->query("SELECT SUM(oil_liters) as total_out FROM oil_out");
$totalOilOut = $oilOutResult->fetch_assoc()['total_out'] ?? 0;

$availableOil = $totalOilIn - $totalOilOut;

// Get oil out records with oil type names
$sql = "SELECT oo.*, ot.name as oil_type_name 
        FROM oil_out oo 
        LEFT JOIN oil_types ot ON oo.all_oil_type = ot.id 
        ORDER BY oo.oil_date DESC, oo.oil_time_in DESC";
$result = $conn->query($sql);
$oilOutData = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oil Out Management</title>
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

        h3 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 1.3rem;
            font-weight: 600;
            padding: 20px 20px 0 20px;
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
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #667eea 100%);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 20px;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #667eea 100%);
            transform: translateY(-2px);
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

        #add-oil-form {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        #add-oil-form.active {
            display: block;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 20px;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
        }


        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
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

        .table-container {
            overflow-x: auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        td {
            color: #495057;
            font-size: 0.95rem;
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
                🛢️ Oil Management
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
                        <a href="oil.php" class="active">
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
                    <h1>🛢 Oil Out Management</h1>
                </div>
            </header>

            <div class="container">
                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>


                <!-- Toggle Button -->
                <button type="button" class="btn btn-secondary" onclick="toggleForm()">
                    ➕ Add New Oil Out Record
                </button>

                <!-- Add Oil Out Form -->
                <div id="add-oil-form" class="section <?php echo ($success_message || $error_message) ? 'active' : ''; ?>">
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_oil_out">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="oil_date">Date</label>
                                    <input type="date" name="oil_date" id="oil_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_time_in">Time In</label>
                                    <input type="time" name="oil_time_in" id="oil_time_in" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_oil_no">Oil No</label>
                                    <input type="text" name="oil_oil_no" id="oil_oil_no" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_plate_no">Plate No</label>
                                    <input type="text" name="oil_plate_no" id="oil_plate_no" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_request">Request</label>
                                    <input type="text" name="oil_request" id="oil_request" required>
                                </div>
                                <div class="form-group">
                                    <label for="all_oil_type">Oil Type</label>
                                    <select name="all_oil_type" id="all_oil_type" required>
                                        <option value="">Select Oil Type</option>
                                        <?php foreach ($oilTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['id']); ?>">
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="oil_liters">Liters</label>
                                    <input type="number" step="0.01" name="oil_liters" id="oil_liters" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_vehicle_type">Vehicle Type</label>
                                    <input type="text" name="oil_vehicle_type" id="oil_vehicle_type" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_receiver">Receiver</label>
                                    <input type="text" name="oil_receiver" id="oil_receiver" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_time_out">Time Out</label>
                                    <input type="time" name="oil_time_out" id="oil_time_out" required>
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
                                <button type="submit" class="btn btn-primary">Add Oil Out Record</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Oil Out Records -->
                <div class="table-container">
                    <h3>🛢️ Oil Out Records (<?php echo count($oilOutData); ?>)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Oil No</th>
                                <th>Plate No</th>
                                <th>Request</th>
                                <th>Oil Type</th>
                                <th>Liters</th>
                                <th>Vehicle Type</th>
                                <th>Receiver</th>
                                <th>Time Out</th>
                                <th>Created By</th>
                                <th>Office Name</th>
                                <th>Image</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($oilOutData as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['oil_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['oil_time_in']); ?></td>
                                <td><?php echo htmlspecialchars($row['oil_oil_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['oil_plate_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['oil_request']); ?></td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($row['oil_type_name'] ?? 'Unknown'); ?></span></td>
                                <td><?php echo number_format($row['oil_liters'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['oil_vehicle_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['oil_receiver']); ?></td>
                                <td><?php echo htmlspecialchars($row['oil_time_out']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                <td><?php echo htmlspecialchars($row['office_name']); ?></td>
                                <td>
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Oil Image" style="max-width: 80px; max-height: 60px; border-radius: 4px; cursor: pointer;" onclick="window.open('<?php echo htmlspecialchars($row['image']); ?>', '_blank');">
                                    <?php else: ?>
                                        <span style="color: #6c757d;">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle form visibility
        function toggleForm() {
            const form = document.getElementById('add-oil-form');
            form.classList.toggle('active');
        }

        // Set default date and time
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const timeStr = now.toTimeString().slice(0, 5);
            
            document.getElementById('oil_date').value = dateStr;
            document.getElementById('oil_time_in').value = timeStr;
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
