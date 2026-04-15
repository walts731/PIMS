<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pims';

try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8
    $conn->set_charset("utf8");
} catch (Exception $e) {
    error_log("Database connection error in oil_in.php: " . $e->getMessage());
    die("A database error occurred. Please contact the administrator.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $date_time = $_POST['date_time'];
        $oil_type = $_POST['oil_type'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        $total_cost = $quantity * $unit_price;
        $storage_location = $_POST['storage_location'];
        $delivery_receipt = $_POST['delivery_receipt'];
        $supplier_name = $_POST['supplier_name'];
        $received_by = $_POST['received_by'];
        $remarks = $_POST['remarks'];
        $created_by = 'admin'; // You can modify this to get current user
        $transaction_id = 'OIL-' . date('YmdHis') . '-' . rand(1000, 9999);

        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = 'uploads/oil_in/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    error_log("Failed to create directory: " . $upload_dir);
                }
            }

            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                error_log("Failed to upload file to: " . $target_file);
            }
        }

        // Insert into database (total_cost is a generated column, don't insert it)
        $sql = "INSERT INTO oil_in (date_time, oil_type, quantity, unit_price, storage_location,
                delivery_receipt, supplier_name, received_by, remarks, created_by, transaction_id, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sidsssssssss", $date_time, $oil_type, $quantity, $unit_price,
                         $storage_location, $delivery_receipt, $supplier_name, $received_by, $remarks,
                         $created_by, $transaction_id, $image_path);

        if ($stmt->execute()) {
            $success_message = "Oil In record added successfully!";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error in oil_in.php form submission: " . $e->getMessage());
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get oil types for dropdown
try {
    $oilTypesResult = $conn->query("SELECT id, name FROM oil_types WHERE is_active = 1 ORDER BY name");
    $oilTypes = $oilTypesResult ? $oilTypesResult->fetch_all(MYSQLI_ASSOC) : [];

    // Get oil in records with oil type names
    $sql = "SELECT oi.*, ot.name as oil_type_name
            FROM oil_in oi
            LEFT JOIN oil_types ot ON oi.oil_type = ot.id
            ORDER BY oi.date_time DESC";
    $result = $conn->query($sql);
    $oilInData = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    error_log("Error in oil_in.php data retrieval: " . $e->getMessage());
    die("Data retrieval error: " . $e->getMessage());
    $oilTypes = [];
    $oilInData = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oil In Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            color: #333;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 35px 20px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.3);
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
            background: rgba(255,255,255,0.2);
            border-left-color: #C1EAF2;
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
            background: transparent;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 25px;
            position: relative;
            z-index: 1;
        }

        header {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
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


        .form-container {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
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

        .form-actions {
            margin-top: 20px;
            text-align: center;
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
            font-size: 0.9rem;
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

        /* Badge Styles */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
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

        h3 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .btn-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 20px;
        }

        .btn-toggle:hover {
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
            
            .table-container {
                font-size: 0.8rem;
            }
            
            th, td {
                padding: 8px;
            }
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
                🛢️ Oil In Management
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
                        <a href="oil_in.php" class="active">
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
                    <h1>🛢️ Oil In Management</h1>
                </div>
            </header>

            <div class="container">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Toggle Button -->
                <button type="button" class="btn btn-toggle" onclick="toggleForm()">
                    ➕ Add Oil In Record
                </button>

                <!-- Oil In Form -->
                <div id="add-oil-form" class="<?php echo (isset($success_message) || isset($error_message)) ? 'active' : ''; ?>">
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="date_time">Date & Time</label>
                                    <input type="datetime-local" name="date_time" id="date_time" required>
                                </div>
                                <div class="form-group">
                                    <label for="oil_type">Oil Type</label>
                                    <select name="oil_type" id="oil_type" required>
                                        <option value="">Select Oil Type</option>
                                        <?php foreach ($oilTypes as $type): ?>
                                            <option value="<?php echo $type['id']; ?>">
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="quantity">Quantity (Liters)</label>
                                    <input type="number" name="quantity" id="quantity" step="0.01" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="unit_price">Unit Price (₱)</label>
                                    <input type="number" name="unit_price" id="unit_price" step="0.01" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="storage_location">Storage Location</label>
                                    <input type="text" name="storage_location" id="storage_location" required>
                                </div>
                                <div class="form-group">
                                    <label for="delivery_receipt">Delivery Receipt</label>
                                    <input type="text" name="delivery_receipt" id="delivery_receipt">
                                </div>
                                <div class="form-group">
                                    <label for="supplier_name">Supplier Name</label>
                                    <input type="text" name="supplier_name" id="supplier_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="received_by">Received By</label>
                                    <input type="text" name="received_by" id="received_by" required>
                                </div>
                                <div class="form-group">
                                    <label for="image">Image</label>
                                    <input type="file" name="image" id="image" accept="image/*">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="remarks">Remarks</label>
                                <textarea name="remarks" id="remarks" rows="3"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Oil In Record</button>
                                <a href="oil_in.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Oil In Records -->
                <div class="table-container">
                    <h3>Oil In Records</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Oil Type</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Cost</th>
                                    <th>Storage Location</th>
                                    <th>Supplier</th>
                                    <th>Received By</th>
                                    <th>Image</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($oilInData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['date_time']); ?></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($row['oil_type_name'] ?? 'Unknown'); ?></span></td>
                                    <td><?php echo number_format($row['quantity'], 2); ?></td>
                                    <td>₱<?php echo number_format($row['unit_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['storage_location']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['received_by']); ?></td>
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
            </div>
        </main>
    </div>

    <script>
        // Toggle form visibility
        function toggleForm() {
            const form = document.getElementById('add-oil-form');
            form.classList.toggle('active');
        }

        // Set current datetime as default
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const dateTimeLocal = now.toISOString().slice(0, 16);
            document.getElementById('date_time').value = dateTimeLocal;
            
            // Add event listener for oil type selection
            const oilTypeSelect = document.getElementById('oil_type');
            const storageLocationInput = document.getElementById('storage_location');
            
            // Remove automatic storage location assignment - user will manually set storage location
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
