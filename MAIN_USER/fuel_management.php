<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_management', 'Main user accessed fuel management');

$fuel_records = [];
$vehicles = [];
$error = null;

// Filter parameters
$vehicle_filter = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Get all vehicles
        $vehicle_query = "SELECT id, vehicle_name, plate_number FROM vehicles ORDER BY vehicle_name ASC";
        $vehicle_result = $conn->query($vehicle_query);
        if ($vehicle_result) {
            while ($row = $vehicle_result->fetch_assoc()) {
                $vehicles[] = $row;
            }
        }
        
        // Build fuel records query with filters
        $fuel_sql = "SELECT 
                        fr.id,
                        fr.liters,
                        fr.cost,
                        fr.cost_per_liter,
                        fr.date_filled,
                        fr.odometer_reading,
                        fr.notes,
                        fr.vehicle_id,
                        v.vehicle_name,
                        v.plate_number,
                        u.username as created_by_user
                    FROM fuel_records fr
                    LEFT JOIN vehicles v ON fr.vehicle_id = v.id
                    LEFT JOIN users u ON fr.created_by = u.id";
        
        $params = [];
        $types = '';
        $where_clauses = [];
        
        if ($vehicle_filter > 0) {
            $where_clauses[] = "fr.vehicle_id = ?";
            $params[] = $vehicle_filter;
            $types .= 'i';
        }
        
        if ($start_date !== '') {
            $where_clauses[] = "DATE(fr.date_filled) >= ?";
            $params[] = $start_date;
            $types .= 's';
        }
        
        if ($end_date !== '') {
            $where_clauses[] = "DATE(fr.date_filled) <= ?";
            $params[] = $end_date;
            $types .= 's';
        }
        
        if (!empty($where_clauses)) {
            $fuel_sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $fuel_sql .= " ORDER BY fr.date_filled DESC";
        
        $stmt = $conn->prepare($fuel_sql);
        if (!$stmt) {
            $error = 'Failed to prepare query.';
        } else {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $fuel_records[] = $row;
            }
            $stmt->close();
        }
        
    } catch (Exception $e) {
        $error = 'Error loading fuel data: ' . $e->getMessage();
        error_log('Main User Fuel Management Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Management - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
        .fuel-stat {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .fuel-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #191BA9;
            display: block;
        }
        
        .fuel-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .fuel-item {
            border-left: 3px solid #28a745;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }
        
        .fuel-info {
            margin-bottom: 0.5rem;
        }
        
        .fuel-vehicle {
            font-weight: 600;
            color: #191BA9;
        }
        
        .fuel-details {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .fuel-amount {
            font-weight: 600;
            color: #28a745;
        }
        
        .fuel-cost {
            font-weight: 600;
            color: #dc3545;
        }
        
        .fuel-date {
            font-size: 0.875rem;
            color: #6c757d;
            text-align: right;
        }
        
        .fuel-list {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php $page_title = 'Fuel Management'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-fuel-pump me-2"></i>Fuel Management
                        </h1>
                        <p class="text-muted mb-0">Manage fuel consumption and vehicle refueling records.</p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <a class="btn btn-outline-success btn-sm" href="fuel_management.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                            <div class="d-inline-block" style="min-width: 200px;">
                                <select class="form-select form-select-sm" id="vehicleFilter">
                                    <option value="0" <?php echo $vehicle_filter === 0 ? 'selected' : ''; ?>>All Vehicles</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo (int)$vehicle['id']; ?>" <?php echo $vehicle_filter === (int)$vehicle['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vehicle['vehicle_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Liters</th>
                                <th>Cost</th>
                                <th>Cost/Liter</th>
                                <th>Odometer</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$error && !empty($fuel_records)): ?>
                                <?php foreach ($fuel_records as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['date_filled'] ?? ''); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($record['vehicle_name'] ?? ''); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['plate_number'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo number_format((float)($record['liters'] ?? 0), 2); ?></td>
                                        <td>₱<?php echo number_format((float)($record['cost'] ?? 0), 2); ?></td>
                                        <td>₱<?php echo number_format((float)($record['cost_per_liter'] ?? 0), 2); ?></td>
                                        <td><?php echo htmlspecialchars($record['odometer_reading'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($record['created_by_user'] ?? ''); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewFuelRecord(<?php echo (int)$record['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No fuel records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vehicleFilter = document.getElementById('vehicleFilter');

            function applyFilters() {
                const currentUrl = new URL(window.location.href);
                
                const vehicleValue = parseInt(vehicleFilter.value || '0', 10);
                if (vehicleValue > 0) {
                    currentUrl.searchParams.set('vehicle_id', String(vehicleValue));
                } else {
                    currentUrl.searchParams.delete('vehicle_id');
                }

                window.location.href = currentUrl.toString();
            }

            if (vehicleFilter) {
                vehicleFilter.addEventListener('change', applyFilters);
            }

            function viewFuelRecord(recordId) {
                // This could open a modal with record details
                console.log('View fuel record:', recordId);
            }
        });
    </script>
</body>
</html>
