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

logSystemAction($_SESSION['user_id'], 'Accessed ICS Entries', 'forms', 'ics_entries.php');

// Get all ICS forms with items
$ics_forms = [];
$result = $conn->query("
    SELECT 
        f.*,
        COUNT(i.item_id) as item_count,
        SUM(i.total_cost) as total_value
    FROM ics_forms f 
    LEFT JOIN ics_items i ON f.id = i.form_id 
    GROUP BY f.id 
    ORDER BY f.created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ics_forms[] = $row;
    }
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'ICS'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICS Entries - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
        
        .ics-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid #5CC2F2;
        }
        
        .ics-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .ics-number {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        @media print {
            .no-print { display: none !important; }
            .ics-card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'ICS Entries';
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
                        <i class="bi bi-file-earmark-text"></i> ICS Entries
                    </h1>
                    <p class="text-muted mb-0">View and manage Inventory Custodian Slip entries</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="ics_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New ICS
                    </a>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportICSData()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($ics_forms); ?></div>
                    <div class="text-muted">Total ICS Forms</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number">
                        <?php 
                        $total_items = array_sum(array_column($ics_forms, 'item_count'));
                        echo $total_items; 
                        ?>
                    </div>
                    <div class="text-muted">Total Items</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number">
                        ₱<?php 
                        $total_value = array_sum(array_column($ics_forms, 'total_value'));
                        echo number_format($total_value, 2); 
                        ?>
                    </div>
                    <div class="text-muted">Total Value</div>
                </div>
            </div>
        </div>

        <!-- ICS Forms List -->
        <div class="row">
            <?php if (empty($ics_forms)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="mt-3 text-muted">No ICS Entries Found</h4>
                        <p class="text-muted">Start by creating your first ICS form.</p>
                        <a href="ics_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create ICS Form
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($ics_forms as $ics): ?>
                    <div class="col-12">
                        <div class="ics-card">
                            <div class="row align-items-start">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <div class="ics-number">
                                                <i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($ics['ics_no']); ?>
                                            </div>
                                            <h5 class="mb-2"><?php echo htmlspecialchars($ics['entity_name']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-cash-stack"></i> Fund Cluster: <?php echo htmlspecialchars($ics['fund_cluster']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Received From:</small>
                                            <p class="mb-1"><?php echo htmlspecialchars($ics['received_from']); ?></p>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($ics['received_from_position']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Received By:</small>
                                            <p class="mb-1"><?php echo htmlspecialchars($ics['received_by']); ?></p>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($ics['received_by_position']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 text-end">
                                    <div class="mb-3">
                                        <div class="text-muted small">Items Count</div>
                                        <div class="h4"><?php echo $ics['item_count']; ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="text-muted small">Total Value</div>
                                        <div class="h4">₱<?php echo number_format($ics['total_value'], 2); ?></div>
                                    </div>
                                    <div class="text-muted small mb-3">
                                        <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($ics['created_at'])); ?>
                                    </div>
                                    <div class="no-print">
                                        <button class="btn btn-sm btn-outline-primary btn-action me-2" onclick="viewICS(<?php echo $ics['id']; ?>)">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-outline-info btn-action" onclick="printICS(<?php echo $ics['id']; ?>)">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewICS(id) {
            window.open('ics_view.php?id=' + id, '_blank');
        }
        
        function printICS(id) {
            window.open('ics_print.php?id=' + id, '_blank');
        }
        
        function exportICSData() {
            // TODO: Implement export functionality
            alert('Export functionality will be implemented');
        }
    </script>
</body>
</html>
