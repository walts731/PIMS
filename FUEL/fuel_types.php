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

// Handle form submission for adding new fuel type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_fuel_type') {
    $name = $_POST['name'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "INSERT INTO fuel_types (name, is_active) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $is_active);
    
    if ($stmt->execute()) {
        $success_message = "Fuel type added successfully!";
    } else {
        $error_message = "Error adding fuel type: " . $stmt->error;
    }
    $stmt->close();
}

// Handle form submission for adding new oil type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_oil_type') {
    $name = $_POST['oil_name'];
    $is_active = isset($_POST['oil_is_active']) ? 1 : 0;

    // Add oil type without tank capacity (open capacity system)
    $sql = "INSERT INTO oil_types (name, is_active) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $is_active);
    $success_message = "Oil type added successfully!";
    if ($stmt && isset($success_message)) {
        if (!$stmt->execute()) {
            $error_message = "Error adding oil type: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle form submission for updating fuel type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_fuel_type') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "UPDATE fuel_types SET name = ?, is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $name, $is_active, $id);
    
    if ($stmt->execute()) {
        $success_message = "Fuel type updated successfully!";
    } else {
        $error_message = "Error updating fuel type: " . $stmt->error;
    }
    $stmt->close();
}

// Handle form submission for updating oil type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_oil_type') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "UPDATE oil_types SET name = ?, is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $name, $is_active, $id);
    
    if ($stmt->execute()) {
        $success_message = "Oil type updated successfully!";
    } else {
        $error_message = "Error updating oil type: " . $stmt->error;
    }
    $stmt->close();
}

// Handle deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $sql = "DELETE FROM fuel_types WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success_message = "Fuel type deleted successfully!";
    } else {
        $error_message = "Error deleting fuel type: " . $stmt->error;
    }
    $stmt->close();
}

// Handle oil type deletion
if (isset($_GET['delete_oil']) && is_numeric($_GET['delete_oil'])) {
    $id = $_GET['delete_oil'];
    
    $sql = "DELETE FROM oil_types WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success_message = "Oil type deleted successfully!";
    } else {
        $error_message = "Error deleting oil type: " . $stmt->error;
    }
    $stmt->close();
}

// Get fuel types data
$sql = "SELECT id, name, is_active, created_at FROM fuel_types ORDER BY name ASC";
$result = $conn->query($sql);
$fuelTypesData = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get oil types data
$sql = "SELECT id, name, is_active, created_at FROM oil_types ORDER BY name ASC";
$result = $conn->query($sql);
$oilTypesData = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel & Oil Types Management</title>
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
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

        .section {
            background: white;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
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
            background: linear-gradient(90deg, #191BA9 0%, #5CC2F2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
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
        .form-group select {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
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
            margin-right: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }

        .table-container {
            overflow-x: auto;
            padding: 20px;
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

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
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
                ⚡ Fuel & Oil Types Management
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
                        <a href="fuel_types.php" class="active">
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
                    <h1>⚡ Fuel & Oil Types Management</h1>
                </div>
            </header>

            <div class="container">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Add Fuel Type Form -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Add New Fuel Type</h2>
                    </div>
                    <div class="form-container">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_fuel_type">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Fuel Type Name</label>
                                    <input type="text" name="name" id="name" required>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="is_active" id="is_active" checked>
                                        <label for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-success">Add Fuel Type</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Fuel Types Table -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Fuel Types</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fuel Type Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuelTypesData as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td>
                                        <?php if ($row['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-warning btn-sm" onclick="editFuelType(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>', <?php echo $row['is_active']; ?>)">Edit</button>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this fuel type?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Oil Type Form -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Add New Oil Type</h2>
                    </div>
                    <div class="form-container">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_oil_type">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="oil_name">Oil Type Name</label>
                                    <input type="text" name="oil_name" id="oil_name" required>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="oil_is_active" id="oil_is_active" checked>
                                        <label for="oil_is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-success">Add Oil Type</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Oil Types Table -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Oil Types</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                            <tr>
                                <th>Oil Type Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                            <tbody>
                                <?php foreach ($oilTypesData as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td>
                                        <?php if ($row['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-warning btn-sm" onclick="editOilType(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>', <?php echo $row['is_active']; ?>)">Edit</button>
                                            <a href="?delete_oil=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this oil type?')">Delete</a>
                                        </div>
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

    <!-- Edit Modal (Hidden by default) -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3 id="modalTitle" style="margin-bottom: 20px;">Edit Type</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" id="modal_action">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_name" id="modalLabel">Type Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        <label for="edit_is_active">Active</label>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" id="modalSubmit">Update</button>
                    <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editFuelType(id, name, isActive) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_is_active').checked = isActive;
            document.getElementById('modal_action').value = 'update_fuel_type';
            document.getElementById('modalTitle').textContent = 'Edit Fuel Type';
            document.getElementById('modalLabel').textContent = 'Fuel Type Name';
            document.getElementById('modalSubmit').textContent = 'Update Fuel Type';
            document.getElementById('editModal').style.display = 'block';
        }

        function editOilType(id, name, isActive) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_is_active').checked = isActive;
            document.getElementById('modal_action').value = 'update_oil_type';
            document.getElementById('modalTitle').textContent = 'Edit Oil Type';
            document.getElementById('modalLabel').textContent = 'Oil Type Name';
            document.getElementById('modalSubmit').textContent = 'Update Oil Type';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
        
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
