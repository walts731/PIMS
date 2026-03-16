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

// Check if user has correct role (admin, system_admin, or fuel)
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel'])) {
    header('Location: ../index.php');
    exit();
}

// Log fuel OUT page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_out_page', 'User accessed fuel OUT page');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_fuel_out') {
        $transaction_type = 'OUT';
        $fuel_type = $_POST['fuel_type'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
        $source = $_POST['source'] ?? '';
        $employee_id = $_POST['employee_id'] ?? null;
        $recipient_name = $_POST['recipient_name'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $user_id = $_SESSION['user_id'];
        $image_path = '';
        
        // Validate inputs
        $errors = [];
        
        if (empty($fuel_type)) {
            $errors[] = 'Fuel type is required';
        }
        
        if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
            $errors[] = 'Valid quantity is required';
        }
        
        if (empty($recipient_name)) {
            $errors[] = 'Recipient name is required';
        }
        
        if (empty($purpose)) {
            $errors[] = 'Purpose is required';
        }
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/fuel_transactions/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = 'fuel_out_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = 'uploads/fuel_transactions/' . $file_name;
                } else {
                    $errors[] = 'Failed to upload image';
                }
            } else {
                $errors[] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
            }
        }
        
        if (empty($errors)) {
            try {
                // Insert fuel OUT transaction with correct database fields
                $insert_sql = "INSERT INTO fuel_transactions 
                              (transaction_type, fuel_type, quantity, transaction_date, source, employee_id, recipient_name, purpose, user_id, image, created_at, updated_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = $conn->prepare($insert_sql);
                if ($stmt === false) {
                    throw new Exception('Database prepare error: ' . $conn->error);
                }
                
                $stmt->bind_param('ssdsssssss', 
                    $transaction_type,
                    $fuel_type,
                    $quantity,
                    $transaction_date,
                    $source,
                    $employee_id,
                    $recipient_name,
                    $purpose,
                    $user_id,
                    $image_path
                );
                
                if ($stmt->execute()) {
                    $_SESSION['fuel_success'] = 'Fuel OUT transaction recorded successfully!';
                    logSystemAction($_SESSION['user_id'], 'create', 'fuel_out', 'Fuel OUT transaction created: ' . $quantity . 'L ' . $fuel_type);
                    header('Location: fuel_out.php');
                    exit();
                } else {
                    throw new Exception('Failed to record fuel OUT transaction');
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                $error_msg = 'Error: ' . $e->getMessage();
                error_log('Fuel OUT Error: ' . $e->getMessage());
                $_SESSION['fuel_error'] = $error_msg;
            }
        } else {
            $_SESSION['fuel_error'] = implode(', ', $errors);
        }
        
        header('Location: fuel_out.php');
        exit();
    }
}

// Get recent fuel OUT transactions
$fuel_out_records = [];
try {
    $fuel_out_sql = "SELECT 
                       id,
                       transaction_type,
                       transaction_date,
                       quantity,
                       fuel_type,
                       source,
                       employee_id,
                       recipient_name,
                       purpose,
                       user_id,
                       image,
                       created_at,
                       updated_at,
                       u.first_name,
                       u.last_name
                    FROM fuel_transactions ft 
                    LEFT JOIN users u ON ft.user_id = u.id 
                    WHERE ft.transaction_type = 'OUT' 
                    ORDER BY ft.transaction_date DESC 
                    LIMIT 50";
    
    error_log('Fuel OUT Query: ' . $fuel_out_sql);
    $fuel_out_result = $conn->query($fuel_out_sql);
    
    if ($fuel_out_result === false) {
        error_log('Fuel OUT Query Error: ' . $conn->error);
    } else {
        error_log('Fuel OUT Query Success: ' . $fuel_out_result->num_rows . ' rows found');
        while ($row = $fuel_out_result->fetch_assoc()) {
            $fuel_out_records[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Fuel OUT Error: ' . $e->getMessage());
}

// Get common employees
$employees = [];
try {
    $employees_sql = "SELECT DISTINCT employee_id FROM fuel_transactions WHERE employee_id IS NOT NULL ORDER BY employee_id LIMIT 20";
    $employees_result = $conn->query($employees_sql);
    if ($employees_result) {
        while ($row = $employees_result->fetch_assoc()) {
            $employees[] = $row['employee_id'];
        }
    }
} catch (Exception $e) {
    error_log('Employees Error: ' . $e->getMessage());
}

// Calculate statistics
$today_total = array_sum(array_filter($fuel_out_records, function($q) {
    return date('Y-m-d') === date('Y-m-d', strtotime($q['transaction_date']));
}));
$week_total = array_sum(array_filter($fuel_out_records, function($q) {
    return date('Y-m-d', strtotime($q['transaction_date'])) >= date('Y-m-d', strtotime('last monday'));
}));
$total_transactions = count($fuel_out_records);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Out - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <style>
        .fuel-out-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 1rem 1rem 1rem 5rem;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: calc(100vh - 76px);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin: -2rem -2rem 2rem -2rem;
            border-radius: 20px 20px 0 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card.danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        
        .stat-card.info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #17a2b8, #138496);
        }
        
        .stat-card.warning::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #e0a800);
        }
        
        .stat-card.success::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon.danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .stat-icon.info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .stat-icon.warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .btn-fuel-out {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-fuel-out:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .fuel-out-badge {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .tank-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .tank-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out;
        }
        
        /* Custom styles for fuel OUT table */
        .fuel-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #dc3545;
        }
        
        .fuel-table thead {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .fuel-table th {
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }
        
        .fuel-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .fuel-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.3s ease;
        }
        
        .fuel-table .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            margin-top: 2rem;
            border: 2px solid #e9ecef;
        }
        
        .table-container h4 {
            color: #dc3545;
            font-weight: 700;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .fuel-table {
                font-size: 0.875rem;
            }
            
            .fuel-table th,
            .fuel-table td {
                padding: 0.5rem;
            }
            
            .table-container {
                padding: 1rem;
                margin-top: 1rem;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin: 0.5rem;
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
        }
    </style>
</head>
<body class="fuel-out-page">
    <?php
    // Set page title for topbar
    $page_title = 'Fuel Out Management';
    ?>
    <!-- Main Content Wrapper -->
    <div class="fuel-main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once '../MAIN_USER/includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content animate-slide-up">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-arrow-up-circle me-3"></i>
                            Fuel Out Management
                        </h1>
                        <p class="mb-0 opacity-75">Record fuel dispensing and vehicle refueling</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-light btn-sm" onclick="refreshPage()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                        <a href="dashboard.php" class="btn btn-light btn-sm ms-2">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['fuel_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['fuel_success']); unset($_SESSION['fuel_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['fuel_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['fuel_error']); unset($_SESSION['fuel_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-today"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($today_total, 2); ?></div>
                            <div class="stat-label">Today's Fuel OUT</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-week"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($week_total, 2); ?></div>
                            <div class="stat-label">This Week</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_transactions; ?></div>
                            <div class="stat-label">Total Transactions</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php 
                                // Get total fuel available from fuel_transactions
                                $total_fuel_available = 0;
                                try {
                                    $available_query = "SELECT SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as available FROM fuel_transactions";
                                    $available_result = $conn->query($available_query);
                                    if ($available_result && $row = $available_result->fetch_assoc()) {
                                        $total_fuel_available = $row['available'] ?? 0;
                                    }
                                } catch (Exception $e) {
                                    error_log('Available fuel calculation error: ' . $e->getMessage());
                                }
                                echo number_format($total_fuel_available, 2); 
                            ?></div>
                            <div class="stat-label">Available Fuel</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dispense Fuel Form -->
            <div class="form-card">
                <h5 class="mb-4">
                    <i class="bi bi-dash-circle text-danger me-2"></i>
                    Dispense Fuel
                </h5>
                <form method="POST" action="fuel_out.php" onsubmit="return validateFuelOutForm()" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_fuel_out">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fuel_type" class="form-label fw-semibold">
                                    <i class="bi bi-fuel-pump me-1"></i>Fuel Type
                                </label>
                                <select class="form-select" id="fuel_type" name="fuel_type" required>
                                    <option value="">Select Fuel Type</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="gasoline">Gasoline</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label fw-semibold">
                                    <i class="bi bi-droplet me-1"></i>Quantity (Liters)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="quantity" 
                                       name="quantity" 
                                       step="0.01" 
                                       min="0.01" 
                                       max="99999999.99"
                                       placeholder="Enter quantity in liters"
                                       required>
                                <div class="form-text">Maximum: 99,999,999.99 liters</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transaction_date" class="form-label fw-semibold">
                                    <i class="bi bi-calendar3 me-1"></i>Transaction Date
                                </label>
                                <input type="datetime-local" 
                                       class="form-control" 
                                       id="transaction_date" 
                                       name="transaction_date" 
                                       value="<?php echo date('Y-m-d\TH:i'); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="source" class="form-label fw-semibold">
                                    <i class="bi bi-building me-1"></i>Source
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="source" 
                                       name="source" 
                                       placeholder="e.g., Main Tank, Generator"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label fw-semibold">
                                    <i class="bi bi-person-badge me-1"></i>Employee ID
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="employee_id" 
                                       name="employee_id" 
                                       placeholder="Enter employee ID (optional)"
                                       list="employees_list">
                                <datalist id="employees_list">
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo htmlspecialchars($employee); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="recipient_name" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>Recipient Name
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="recipient_name" 
                                       name="recipient_name" 
                                       placeholder="Enter recipient name"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="purpose" class="form-label fw-semibold">
                                    <i class="bi bi-chat-text me-1"></i>Purpose
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="purpose" 
                                       name="purpose" 
                                       placeholder="e.g., Vehicle refueling, Equipment use"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="image" class="form-label fw-semibold">
                                    <i class="bi bi-image me-1"></i>Upload Image (Optional)
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="image" 
                                       name="image" 
                                       accept="image/*">
                                <div class="form-text">Supported formats: JPG, JPEG, PNG, GIF (Max 5MB)</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-gradient btn-lg w-100">
                                <i class="bi bi-dash-circle me-2"></i>
                                Dispense Fuel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recent Fuel OUT Transactions -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">
                        <i class="bi bi-clock-history me-2 text-danger"></i>
                        <strong>Recent Fuel OUT Transactions</strong>
                        <span class="badge bg-danger text-white ms-2"><?php echo count($fuel_out_records); ?></span>
                    </h4>
                    <div>
                        <button class="btn btn-outline-danger btn-sm me-2" onclick="exportFuelOutData()">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshPage()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                
                <?php 
                error_log('Fuel OUT Records Count: ' . count($fuel_out_records));
                error_log('Fuel OUT Records: ' . print_r($fuel_out_records, true));
                ?>
                
                <?php if (!empty($fuel_out_records)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover fuel-table">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="bi bi-calendar me-1"></i>Date/Time</th>
                                    <th><i class="bi bi-tag me-1"></i>Fuel Type</th>
                                    <th><i class="bi bi-droplet me-1"></i>Quantity (L)</th>
                                    <th><i class="bi bi-building me-1"></i>Source</th>
                                    <th><i class="bi bi-person-badge me-1"></i>Employee ID</th>
                                    <th><i class="bi bi-person me-1"></i>Recipient</th>
                                    <th><i class="bi bi-chat-left-text me-1"></i>Purpose</th>
                                    <th><i class="bi bi-image me-1"></i>Image</th>
                                    <th><i class="bi bi-person me-1"></i>Recorded By</th>
                                    <th><i class="bi bi-gear me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuel_out_records as $record): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo date('M d, Y H:i', strtotime($record['transaction_date'])); ?></div>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-clock"></i> <?php echo date('M d, Y H:i', strtotime($record['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white fs-6">
                                                <?php echo htmlspecialchars(ucfirst($record['fuel_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-danger fs-5"><?php echo number_format($record['quantity'], 2); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($record['source'])): ?>
                                                <span class="badge bg-secondary text-white">
                                                    <?php echo htmlspecialchars($record['source']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($record['employee_id'])): ?>
                                                <span class="badge bg-primary text-white">
                                                    <?php echo htmlspecialchars($record['employee_id']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($record['recipient_name'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($record['purpose'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($record['purpose'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($record['image'])): ?>
                                                <a href="../<?php echo htmlspecialchars($record['image']); ?>" target="_blank" 
                                                   class="btn btn-sm btn-outline-primary" title="View Image">
                                                    <i class="bi bi-image"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="bi bi-image"></i> No Image
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-info" onclick="viewTransaction(<?php echo $record['id']; ?>)" 
                                                        title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteTransaction(<?php echo $record['id']; ?>)" 
                                                        title="Delete Transaction">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 bg-light rounded">
                        <i class="bi bi-arrow-up-circle text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">No Fuel OUT Transactions Found</h5>
                        <p class="text-muted mb-4">Start by dispensing fuel using the form above.</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Troubleshooting:</strong> If you expect to see transactions here, please check:
                            <ul class="mb-0 mt-2">
                                <li>Database connection is working</li>
                                <li>Fuel OUT transactions exist in the database</li>
                                <li>Transaction type is set to 'OUT'</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Form validation
        document.getElementById('fuelOutForm').addEventListener('submit', function(e) {
            const fuelType = document.getElementById('fuel_type').value;
            const quantity = parseFloat(document.getElementById('quantity').value);
            const transactionDate = document.getElementById('transaction_date').value;
            const source = document.getElementById('source').value;
            const recipientName = document.getElementById('recipient_name').value;
            const purpose = document.getElementById('purpose').value;
            
            if (!fuelType) {
                e.preventDefault();
                alert('Please select a fuel type');
                return false;
            }
            
            if (!quantity || quantity <= 0) {
                e.preventDefault();
                alert('Please enter a valid quantity');
                return false;
            }
            
            if (quantity > 99999999.99) {
                e.preventDefault();
                alert('Quantity cannot exceed 99,999,999.99 liters');
                return false;
            }
            
            if (!transactionDate.trim()) {
                e.preventDefault();
                alert('Please enter transaction date');
                return false;
            }
            
            if (!source.trim()) {
                e.preventDefault();
                alert('Please enter source');
                return false;
            }
            
            if (!recipientName.trim()) {
                e.preventDefault();
                alert('Please enter recipient name');
                return false;
            }
            
            if (!purpose.trim()) {
                e.preventDefault();
                alert('Please enter purpose');
                return false;
            }
            
            return true;
        });

        function refreshPage() {
            location.reload();
        function viewTransaction(id) {
            alert('View transaction details for ID: ' + id);
                console.error('Error fetching transaction:', error);
                alert('Error loading transaction details.');
            });
    }

    function deleteTransaction(transactionId) {
        if (confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
            fetch('pages/delete_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: transactionId,
                    action: 'delete'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting transaction: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting transaction:', error);
                alert('Error deleting transaction.');
            });
        }
    }
    </script>
</body>
</html>
