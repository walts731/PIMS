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

// Log release history page access
logSystemAction($_SESSION['user_id'], 'access', 'release_history', 'Admin accessed release history page');

// Handle filter parameters
$from_office_filter = isset($_GET['from_office']) ? intval($_GET['from_office']) : 0;
$to_office_filter = isset($_GET['to_office']) ? intval($_GET['to_office']) : 0;
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Get release history with filters
$release_history = [];
try {
    $sql = "SELECT h.*, 
                    fo.office_name as from_office_name, 
                    to_off.office_name as to_office_name,
                    CONCAT(u.first_name, ' ', u.last_name) as released_by_name
             FROM consumable_release_history h
             LEFT JOIN offices fo ON h.from_office_id = fo.id
             LEFT JOIN offices to_off ON h.to_office_id = to_off.id
             LEFT JOIN users u ON h.released_by = u.id
             WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($from_office_filter > 0) {
        $sql .= " AND h.from_office_id = ?";
        $params[] = $from_office_filter;
        $types .= 'i';
    }
    
    if ($to_office_filter > 0) {
        $sql .= " AND h.to_office_id = ?";
        $params[] = $to_office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $sql .= " AND (h.description LIKE ? OR fo.office_name LIKE ? OR to_off.office_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sssss';
    }
    
    if (!empty($date_from)) {
        $sql .= " AND DATE(h.release_date) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if (!empty($date_to)) {
        $sql .= " AND DATE(h.release_date) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $sql .= " ORDER BY h.release_date DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $release_history[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching release history: " . $e->getMessage());
}

// Get offices for dropdown filters
$offices = [];
try {
    $result = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching offices: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release History - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .stats-number {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            word-wrap: break-word;
            line-height: 1.2;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.125rem;
        }
        
        .text-value {
            font-weight: 600;
            color: #191BA9;
        }
        
        .quantity-badge {
            background-color: #191BA9;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .value-badge {
            background-color: #198754;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .filter-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-weight: 600;
            color: #191BA9;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 27, 169, 0.05);
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Release History';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-clock-history"></i> Release History
                    </h1>
                    <p class="text-muted mb-0">Track all consumable release transactions</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="consumables.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Consumables
                    </a>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportReleaseHistory()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($release_history); ?></div>
                    <div class="stats-label"><i class="bi bi-clock-history"></i> Total Releases</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php 
                        $total_quantity = array_sum(array_column($release_history, 'quantity_released'));
                        echo number_format($total_quantity);
                    ?></div>
                    <div class="stats-label"><i class="bi bi-box-seam"></i> Total Items Released</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php 
                        $total_value = array_sum(array_column($release_history, 'total_value'));
                        echo number_format($total_value, 2);
                    ?></div>
                    <div class="stats-label"><i class="bi bi-currency-dollar"></i> Total Value</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php 
                        $unique_offices = count(array_unique(array_column($release_history, 'to_office_id')));
                        echo $unique_offices;
                    ?></div>
                    <div class="stats-label"><i class="bi bi-building"></i> Offices Served</div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
                </div>
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="from_office">
                        <option value="">From Office</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo ($from_office_filter == $office['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="to_office">
                        <option value="">To Office</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo $office['id']; ?>" <?php echo ($to_office_filter == $office['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($office['office_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control form-control-sm" name="date_from" placeholder="Date From" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control form-control-sm" name="date_to" placeholder="Date To" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search_filter); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm me-2">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="release_history.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Clear
                    </a>
                </div>
            </form>
        </div>
        
        <!-- History Table -->
        <div class="table-container">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Release History</h5>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-secondary"><?php echo count($release_history); ?> records</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="releaseHistoryTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Value</th>
                            <th>From Office</th>
                            <th>To Office</th>
                            <th>Released By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($release_history)): ?>
                            <?php foreach ($release_history as $release): ?>
                                <tr>
                                    <td><small><?php echo date('M j, Y H:i', strtotime($release['release_date'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($release['description']); ?></td>
                                    <td><span class="quantity-badge"><?php echo $release['quantity_released']; ?></span></td>
                                    <td><?php echo number_format($release['unit_cost'], 2); ?></td>
                                    <td><span class="value-badge"><?php echo number_format($release['total_value'], 2); ?></span></td>
                                    <td><?php echo htmlspecialchars($release['from_office_name']); ?></td>
                                    <td><?php echo htmlspecialchars($release['to_office_name']); ?></td>
                                    <td><?php echo htmlspecialchars($release['released_by_name']); ?></td>
                                    <td><small><?php echo htmlspecialchars($release['notes'] ?: 'No notes'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-1"></i>
                                    <p class="mt-2">No release history found.</p>
                                </td>
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
    <script>
        function exportReleaseHistory() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            
            // Create CSV content
            let csvContent = "Date,Description,Quantity,Unit Cost,Total Value,From Office,To Office,Released By,Notes\n";
            
            <?php if (!empty($release_history)): ?>
                <?php foreach ($release_history as $release): ?>
                    csvContent += "<?php echo date('Y-m-d H:i', strtotime($release['release_date'])); ?>","<?php echo addslashes($release['description']); ?>","<?php echo $release['quantity_released']; ?>","<?php echo $release['unit_cost']; ?>","<?php echo $release['total_value']; ?>","<?php echo addslashes($release['from_office_name']); ?>","<?php echo addslashes($release['to_office_name']); ?>","<?php echo addslashes($release['released_by_name']); ?>","<?php echo addslashes($release['notes'] ?: 'No notes'); ?>"\n";
                <?php endforeach; ?>
            <?php endif; ?>
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'release_history_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
