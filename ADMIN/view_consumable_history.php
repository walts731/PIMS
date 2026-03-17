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

// Handle filter parameters
$transaction_type = isset($_GET['transaction_type']) ? trim($_GET['transaction_type']) : 'addition'; // Default to addition tab

// Get consumable information and transaction history
$consumable_info = [];
$transaction_history = [];
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
    
    // Get consumable transaction history (additions, releases, and lends) for specific consumable
    // Union query to get additions, releases, and lends for this consumable
    $sql = "(SELECT 
                'addition' as transaction_type,
                h.id,
                c.description,
                h.quantity_added as quantity,
                h.units,
                h.unit_cost,
                h.total_value,
                h.office_id as from_office_id,
                h.office_id as to_office_id, -- Same office for additions
                h.added_by as released_by,
                CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                NULL as received_by,
                h.add_date as transaction_date,
                'Consumable added to inventory' as notes,
                h.add_date as created_at,
                fo.office_name as from_office_name,
                fo.office_name as to_office_name, -- Same office for additions
                NULL as expected_return_date,
                NULL as actual_return_date,
                NULL as lend_status
            FROM consumable_add_history h
            LEFT JOIN consumables c ON h.consumable_id = c.id
            LEFT JOIN users u ON h.added_by = u.id
            LEFT JOIN offices fo ON h.office_id = fo.id
            WHERE h.consumable_id = ? AND h.add_date IS NOT NULL) 
            
            UNION ALL
            
            (SELECT 
                'release' as transaction_type,
                h.id,
                h.description,
                h.quantity_released as quantity,
                c.units,
                h.unit_cost,
                h.total_value,
                h.from_office_id,
                h.to_office_id,
                h.released_by,
                CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                h.received_by,
                h.release_date as transaction_date,
                h.notes,
                h.created_at,
                fo.office_name as from_office_name,
                to_off.office_name as to_office_name,
                NULL as expected_return_date,
                NULL as actual_return_date,
                NULL as lend_status
            FROM consumable_release_history h
            LEFT JOIN users u ON h.released_by = u.id
            LEFT JOIN consumables c ON h.consumable_id = c.id
            LEFT JOIN offices fo ON h.from_office_id = fo.id
            LEFT JOIN offices to_off ON h.to_office_id = to_off.id
            WHERE h.consumable_id = ?)
            
            UNION ALL
            
            (SELECT 
                'lend' as transaction_type,
                l.id,
                l.description,
                l.quantity_lent as quantity,
                c.units,
                l.unit_cost,
                l.total_value,
                l.from_office_id,
                l.to_office_id,
                l.lent_by,
                CONCAT(u.first_name, ' ', u.last_name) as released_by_name,
                l.received_by,
                l.date_lent as transaction_date,
                l.notes,
                l.created_at,
                fo.office_name as from_office_name,
                to_off.office_name as to_office_name,
                l.expected_return_date,
                l.actual_return_date,
                l.status as lend_status
            FROM lend_consumables l
            LEFT JOIN users u ON l.lent_by = u.id
            LEFT JOIN consumables c ON l.consumable_id = c.id
            LEFT JOIN offices fo ON l.from_office_id = fo.id
            LEFT JOIN offices to_off ON l.to_office_id = to_off.id
            WHERE l.consumable_id = ?)
            
            ORDER BY transaction_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $consumable_id, $consumable_id, $consumable_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Filter by transaction type
        if ($row['transaction_type'] === $transaction_type) {
            $transaction_history[] = $row;
        }
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
                            <i class="bi bi-clock-history"></i> <?php echo count($transaction_history); ?> History Records
                        </span>
                    </div>
                </div>
            </div>

            <!-- Transaction Type Tabs -->
            <div class="table-container">
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="bi bi-clock-history"></i> Transaction History</h5>
                        
                        <!-- Tabs -->
                        <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($transaction_type == 'addition') ? 'active' : ''; ?>" 
                                        id="additions-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#additions" 
                                        type="button" 
                                        role="tab" 
                                        onclick="switchTab('addition')">
                                    <i class="bi bi-plus-circle"></i> Additions
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($transaction_type == 'release') ? 'active' : ''; ?>" 
                                        id="releases-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#releases" 
                                        type="button" 
                                        role="tab" 
                                        onclick="switchTab('release')">
                                    <i class="bi bi-box-arrow-right"></i> Releases
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($transaction_type == 'lend') ? 'active' : ''; ?>" 
                                        id="lends-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#lends" 
                                        type="button" 
                                        role="tab" 
                                        onclick="switchTab('lend')">
                                    <i class="bi bi-arrow-up-right"></i> Lends
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="history-container">
                <?php if (!empty($transaction_history)): ?>
                    <div class="table-responsive">
                        <table class="table history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Units</th>
                                    <th>Unit Cost</th>
                                    <th>Total Value</th>
                                    <th>From Office</th>
                                    <th>To Office</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaction_history as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($record['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['description']); ?></td>
                                        <td class="fw-bold"><?php echo $record['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($record['units'] ?: 'N/A'); ?></td>
                                        <td>₱<?php echo number_format($record['unit_cost'], 2); ?></td>
                                        <td class="text-success fw-bold">₱<?php echo number_format($record['quantity'] * $record['unit_cost'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($record['from_office_name'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($record['to_office_name'] ?: 'N/A'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewTransactionDetails(<?php echo $record['id']; ?>, '<?php echo $record['transaction_type']; ?>')">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-clock-history"></i>
                        <h5>No <?php echo ucfirst($transaction_type); ?> History Records Found</h5>
                        <p>This consumable has no <?php echo $transaction_type; ?> history records yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- View Transaction Details Modal -->
    <div class="modal fade" id="viewTransactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Transaction Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="transactionDetails">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading transaction details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Tab switching function
        function switchTab(transactionType) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('transaction_type', transactionType);
            window.location.href = currentUrl.toString();
        }
        
        // View transaction details function
        function viewTransactionDetails(transactionId, transactionType) {
            const modal = new bootstrap.Modal(document.getElementById('viewTransactionModal'));
            const detailsContainer = document.getElementById('transactionDetails');
            
            // Show loading state
            detailsContainer.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading transaction details...</p>
                </div>
            `;
            
            // Show modal
            modal.show();
            
            // Fetch transaction details
            fetch(`get_transaction_details.php?id=${transactionId}&type=${transactionType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTransactionDetails(data.data);
                    } else {
                        detailsContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> 
                                Error: ${data.error}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    detailsContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Error loading transaction details. Please try again.
                        </div>
                    `;
                });
        }
        
        // Display transaction details function
        function displayTransactionDetails(transaction) {
            const detailsContainer = document.getElementById('transactionDetails');
            
            let typeBadge = '';
            let typeIcon = '';
            let typeTitle = '';
            
            switch(transaction.transaction_type) {
                case 'addition':
                    typeBadge = '<span class="badge bg-success">Addition</span>';
                    typeIcon = '<i class="bi bi-plus-circle text-success"></i>';
                    typeTitle = 'Consumable Addition';
                    break;
                case 'release':
                    typeBadge = '<span class="badge bg-primary">Release</span>';
                    typeIcon = '<i class="bi bi-box-arrow-right text-primary"></i>';
                    typeTitle = 'Consumable Release';
                    break;
                case 'lend':
                    typeBadge = '<span class="badge bg-warning text-dark">Lend</span>';
                    typeIcon = '<i class="bi bi-arrow-up-right text-warning"></i>';
                    typeTitle = 'Consumable Lend';
                    break;
            }
            
            let detailsHTML = `
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center mb-3">
                            ${typeIcon}
                            <h5 class="mb-0 ms-2">${typeTitle}</h5>
                            <div class="ms-auto">${typeBadge}</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Transaction Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Date:</strong></div>
                                    <div class="col-sm-8">${transaction.transaction_date_formatted}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Description:</strong></div>
                                    <div class="col-sm-8">${transaction.description}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Quantity:</strong></div>
                                    <div class="col-sm-8">${transaction.quantity}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Units:</strong></div>
                                    <div class="col-sm-8">${transaction.units || 'N/A'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Unit Cost:</strong></div>
                                    <div class="col-sm-8">₱${parseFloat(transaction.unit_cost || 0).toFixed(2)}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Total Value:</strong></div>
                                    <div class="col-sm-8">₱${(parseFloat(transaction.quantity || 0) * parseFloat(transaction.unit_cost || 0)).toFixed(2)}</div>
                                </div>
                                ${transaction.notes || (transaction.transaction_type === 'addition' ? 'Consumable added to inventory' : '') ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Notes:</strong></div>
                                    <div class="col-sm-8">${transaction.notes || (transaction.transaction_type === 'addition' ? 'Consumable added to inventory' : '')}</div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-people"></i> People & Offices</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>From Office:</strong></div>
                                    <div class="col-sm-8">${transaction.from_office_name || 'N/A'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>To Office:</strong></div>
                                    <div class="col-sm-8">${transaction.to_office_name || 'N/A'}</div>
                                </div>
                                ${transaction.received_by || (transaction.transaction_type === 'addition' ? 'N/A' : '') ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Received By:</strong></div>
                                    <div class="col-sm-8">${transaction.received_by || (transaction.transaction_type === 'addition' ? 'N/A' : '')}</div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        ${transaction.transaction_type === 'lend' ? `
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Lend Information</h6>
                            </div>
                            <div class="card-body">
                                ${transaction.expected_return_date ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Expected Return:</strong></div>
                                    <div class="col-sm-8">${transaction.expected_return_date_formatted}</div>
                                </div>
                                ` : ''}
                                ${transaction.actual_return_date ? `
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Actual Return:</strong></div>
                                    <div class="col-sm-8">${transaction.actual_return_date_formatted}</div>
                                </div>
                                ` : ''}
                                <div class="row mb-2">
                                    <div class="col-sm-4"><strong>Status:</strong></div>
                                    <div class="col-sm-8">
                                        ${transaction.status === 'lent' ? '<span class="badge bg-warning text-dark">Lent</span>' : ''}
                                        ${transaction.status === 'returned' ? '<span class="badge bg-success">Returned</span>' : ''}
                                        ${transaction.status === 'overdue' ? '<span class="badge bg-danger">Overdue</span>' : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            detailsContainer.innerHTML = detailsHTML;
        }
    </script>
</body>
</html>
