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

// Log fuel IN page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_in_page', 'User accessed fuel IN page');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_fuel_in') {
        $fuel_type = $_POST['fuel_type'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
        $source = $_POST['source'] ?? '';
        $supplier = $_POST['supplier'] ?? '';
        $recipient_name = $_POST['recipient_name'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        
        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/fuel_transactions/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $file_name;
            }
        }
        
        if (!empty($fuel_type) && $quantity > 0 && !empty($purpose)) {
            // Validate quantity range (DECIMAL(10,2) allows max 99999999.99)
            if ($quantity > 99999999.99) {
                $_SESSION['fuel_error'] = 'Quantity is too large. Maximum allowed value is 99,999,999.99 liters.';
                header('Location: fuel_in.php');
                exit();
            }
            
            // Define variables for bind_param
            $transaction_type = 'IN';
            $user_id = $_SESSION['user_id'];
            
            // Add source and supplier fields
            $insert_sql = "INSERT INTO fuel_transactions 
                          (transaction_type, fuel_type, quantity, transaction_date, user_id, image, tank_number, source, supplier, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($insert_sql);
            if ($stmt === false) {
                $error_msg = 'Database prepare error: ' . $conn->error;
                error_log('Prepare error: ' . $conn->error);
                error_log('SQL: ' . $insert_sql);
                $_SESSION['fuel_error'] = $error_msg;
                header('Location: fuel_in.php');
                exit();
            }
            
            $tank_number = 0; // Use 0 since tank_number is INTEGER and NOT NULL
            $stmt->bind_param('ssdsisiss', 
                $transaction_type,
                $fuel_type,
                $quantity,
                $transaction_date,
                $user_id,
                $image_path,
                $tank_number,
                $source,
                $supplier
            );
            
            if ($stmt->execute()) {
                $_SESSION['fuel_success'] = 'Fuel IN transaction added successfully!';
                logSystemAction($_SESSION['user_id'], 'create', 'fuel_in', "Added fuel IN: {$quantity}L of {$fuel_type}");
            } else {
                $_SESSION['fuel_error'] = 'Error adding fuel IN transaction: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['fuel_error'] = 'Please fill all required fields.';
        }
        
        header('Location: fuel_in.php');
        exit();
    }
}

// Get recent fuel IN transactions
$fuel_in_records = [];
try {
    $fuel_in_sql = "SELECT 
                      id,
                      transaction_type,
                      fuel_type,
                      quantity,
                      transaction_date,
                      source,
                      supplier,
                      tank_number,
                      recipient_name,
                      purpose,
                      user_id,
                      image,
                      created_at,
                      updated_at
                   FROM fuel_transactions 
                   WHERE transaction_type = 'IN' 
                   ORDER BY transaction_date DESC 
                   LIMIT 50";
    $fuel_in_result = $conn->query($fuel_in_sql);
    if ($fuel_in_result) {
        while ($row = $fuel_in_result->fetch_assoc()) {
            $fuel_in_records[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Fuel IN Error: ' . $e->getMessage());
}

// Get available tanks
$available_tanks = [];
try {
    $tanks_sql = "SELECT tank_number, fuel_type, current_level, capacity FROM fuel_inventory WHERE status = 'active' ORDER BY tank_number";
    $tanks_result = $conn->query($tanks_sql);
    if ($tanks_result) {
        while ($row = $tanks_result->fetch_assoc()) {
            $available_tanks[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Tanks Error: ' . $e->getMessage());
}

// Calculate statistics
$today_total = array_sum(array_filter(array_column($fuel_in_records, 'quantity'), function($q) {
    return date('Y-m-d') === date('Y-m-d', strtotime($q));
}));
$week_total = array_sum(array_filter(array_column($fuel_in_records, 'quantity'), function($q) {
    return date('W') === date('W');
}));
$total_transactions = count($fuel_in_records);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel In - PIMS</title>
    
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
        .fuel-in-page {
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
        
        .stat-card::before {
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
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
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
        
        .btn-fuel-in {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-fuel-in:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .fuel-in-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
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
<body class="fuel-in-page">
    <?php
    // Set page title for topbar
    $page_title = 'Fuel In Management';
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
                            <i class="bi bi-arrow-down-circle me-3"></i>
                            Fuel In Management
                        </h1>
                        <p class="mb-0 opacity-75">Record fuel deliveries and refueling operations</p>
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
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-today"></i>
                        </div>
                        <h6 class="text-muted mb-2">Today's Fuel IN</h6>
                        <h3 class="mb-0 text-success"><?php echo number_format($today_total, 2); ?> L</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-week"></i>
                        </div>
                        <h6 class="text-muted mb-2">This Week</h6>
                        <h3 class="mb-0 text-info"><?php echo number_format($week_total, 2); ?> L</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total Transactions</h6>
                        <h3 class="mb-0 text-warning"><?php echo $total_transactions; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Add Fuel IN Form -->
            <div class="form-card">
                <h5 class="mb-4">
                    <i class="bi bi-plus-circle me-2 text-success"></i>
                    Add Fuel IN Transaction
                </h5>
                <form method="POST" action="fuel_in.php" enctype="multipart/form-data" onsubmit="return validateFuelInForm()">
                    <input type="hidden" name="action" value="add_fuel_in">
                    <div class="row">
                        <div class="col-md-2">
                            <label for="transaction_date" class="form-label">Date/Time</label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="fuel_type" class="form-label">Fuel Type *</label>
                            <select class="form-select" id="fuel_type" name="fuel_type" required>
                                <option value="">Select fuel type</option>
                                <option value="diesel">Diesel</option>
                                <option value="gasoline">Gasoline</option>
                                <option value="premium">Premium Gasoline</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="quantity" class="form-label">Quantity (L) *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" step="0.01" min="0.01" max="99999999.99" required>
                            <div class="form-text">Max: 99,999,999.99 liters</div>
                        </div>
                        <div class="col-md-2">
                            <label for="source" class="form-label">Source</label>
                            <input type="text" class="form-control" id="source" name="source" placeholder="Source of fuel">
                        </div>
                        <div class="col-md-2">
                            <label for="supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="supplier" name="supplier" placeholder="Supplier name">
                        </div>
                        <div class="col-md-2">
                            <label for="recipient_name" class="form-label">Recipient Name</label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" placeholder="Recipient name">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="purpose" class="form-label">Purpose *</label>
                            <input type="text" class="form-control" id="purpose" name="purpose" placeholder="Purpose of fuel addition" required>
                        </div>
                        <div class="col-md-3">
                            <label for="image" class="form-label">Image/Document</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*,.pdf">
                            <div class="form-text">Optional: Upload receipt or related document</div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-fuel-in w-100">
                                <i class="bi bi-plus-circle me-2"></i>Add Fuel IN
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recent Fuel IN Transactions -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Recent Fuel IN Transactions
                        <span class="fuel-in-badge ms-2"><?php echo count($fuel_in_records); ?></span>
                    </h5>
                    <button class="btn btn-outline-success btn-sm" onclick="exportFuelInData()">
                        <i class="bi bi-download me-1"></i>Export
                    </button>
                </div>
                
                <?php if (!empty($fuel_in_records)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar me-1"></i>Date</th>
                                    <th><i class="bi bi-tag me-1"></i>Fuel Type</th>
                                    <th><i class="bi bi-droplet me-1"></i>Quantity</th>
                                    <th><i class="bi bi-box me-1"></i>Source</th>
                                    <th><i class="bi bi-building me-1"></i>Supplier</th>
                                    <th><i class="bi bi-person me-1"></i>Recipient</th>
                                    <th><i class="bi bi-chat-left-text me-1"></i>Purpose</th>
                                    <th><i class="bi bi-image me-1"></i>Image</th>
                                    <th><i class="bi bi-person me-1"></i>User</th>
                                    <th><i class="bi bi-gear me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuel_in_records as $record): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('M d, Y H:i', strtotime($record['transaction_date'])); ?>
                                            <br><small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($record['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                <?php echo htmlspecialchars(ucfirst($record['fuel_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?php echo number_format($record['quantity'], 2); ?> L</strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['source'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($record['supplier'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($record['recipient_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                                        <td>
                                            <?php if (!empty($record['image'])): ?>
                                                <a href="../uploads/fuel_transactions/<?php echo htmlspecialchars($record['image']); ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-image"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">ID: <?php echo $record['user_id']; ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-info" onclick="viewTransaction(<?php echo $record['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteTransaction(<?php echo $record['id']; ?>)">
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
                    <div class="text-center py-4">
                        <i class="bi bi-arrow-down-circle text-muted" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mt-3">No Fuel IN Transactions Found</h6>
                        <p class="text-muted">Start by adding your first fuel IN transaction using the form above.</p>
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
    function validateFuelInForm() {
        const quantity = parseFloat(document.getElementById('quantity').value);
        const maxQuantity = 99999999.99;
        
        if (quantity > maxQuantity) {
            alert('Quantity is too large. Maximum allowed value is ' + maxQuantity.toLocaleString() + ' liters.');
            document.getElementById('quantity').focus();
            return false;
        }
        
        if (quantity <= 0) {
            alert('Quantity must be greater than 0.');
            document.getElementById('quantity').focus();
            return false;
        }
        
        return true;
    }

    $(document).ready(function() {
        // Initialize DataTables
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    function refreshPage() {
        location.reload();
    }

    function exportFuelInData() {
        window.open('pages/export_fuel_report.php?export=1&type=fuel_in', '_blank');
    }

    function viewTransaction(transactionId) {
        fetch('pages/get_transaction.php?id=' + transactionId)
            .then(response => response.json())
            .then(data => {
                alert('Transaction Details:\n\nID: ' + data.id + '\nType: ' + data.transaction_type + '\nFuel Type: ' + data.fuel_type + '\nQuantity: ' + data.quantity + 'L\nPurpose: ' + data.purpose);
            })
            .catch(error => {
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
