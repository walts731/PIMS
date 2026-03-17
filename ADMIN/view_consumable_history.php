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

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get consumable ID from URL parameter
$consumable_id = intval($_GET['id'] ?? 0);

if ($consumable_id <= 0) {
    die('Invalid consumable ID');
}

// Get consumable information
$consumable_info = [];
$history_records = [];

try {
    // Get consumable basic info
    $stmt = $conn->prepare("SELECT c.*, o.office_name FROM consumables c LEFT JOIN offices o ON c.office_id = o.id WHERE c.id = ?");
    $stmt->bind_param("i", $consumable_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $consumable_info = $result->fetch_assoc();
    } else {
        die('Consumable not found');
    }
    $stmt->close();
    
    // Get history records
    $stmt = $conn->prepare("
        SELECT h.*, u.first_name, u.last_name, o.office_name 
        FROM consumable_release_history h 
        LEFT JOIN users u ON h.released_by = u.id 
        LEFT JOIN offices o ON h.to_office_id = o.id 
        WHERE h.consumable_id = ? 
        ORDER BY h.release_date DESC
    ");
    $stmt->bind_param("i", $consumable_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $history_records[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    die('Error fetching history: ' . htmlspecialchars($e->getMessage()));
}

// Log history view access
logSystemAction($_SESSION['user_id'], 'access', 'consumable_history', "Viewed history for consumable: " . ($consumable_info['description'] ?? 'Unknown'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumable History - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, var(--light-accent) 100%);
            min-height: 100vh;
            overflow-x: hidden;
            margin: 0;
            padding: 20px;
        }

        .history-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .consumable-header {
            background: linear-gradient(135deg, #1E56A0 0%, #2E86C1 100%);
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
        }

        .history-table {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .history-table thead th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .history-table tbody td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .history-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-source {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .source-new {
            background-color: #28a745;
        }

        .source-addition {
            background-color: #007bff;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        :root {
            --primary-color: #1E56A0;
            --primary-rgb: 30, 86, 160;
            --light-color: #f8f9fa;
            --light-accent: #e9ecef;
            --border-radius: 0.375rem;
            --border-radius-lg: 0.5rem;
            --border-radius-xl: 1rem;
            --shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
            --transition: all 0.15s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Consumable Header -->
        <div class="consumable-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1">
                        <i class="bi bi-box-seam"></i> <?php echo htmlspecialchars($consumable_info['description']); ?>
                    </h4>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($consumable_info['office_name'] ?? 'N/A'); ?> • 
                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($consumable_info['units']); ?> • 
                        Current Stock: <?php echo $consumable_info['quantity']; ?> • 
                        Unit Cost: ₱<?php echo number_format($consumable_info['unit_cost'], 2); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="bi bi-clock-history"></i> <?php echo count($history_records); ?> History Records
                    </span>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="history-container">
            <?php if (!empty($history_records)): ?>
                <div class="table-responsive">
                    <table class="table history-table">
                        <thead>
                            <tr>
                                <th>Release Date</th>
                                <th>Released By</th>
                                <th>To Office</th>
                                <th>Received By</th>
                                <th>Quantity Released</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($record['release_date'])); ?></td>
                                    <td><?php echo htmlspecialchars(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($record['office_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($record['received_by'] ?? ''); ?></td>
                                    <td class="fw-bold"><?php echo $record['quantity_released']; ?></td>
                                    <td>₱<?php echo number_format($record['unit_cost'], 2); ?></td>
                                    <td class="text-success fw-bold">₱<?php echo number_format($record['total_value'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-clock-history"></i>
                        <h5>No Release History Records Found</h5>
                        <p>This consumable has no release history records yet.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
