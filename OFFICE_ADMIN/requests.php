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

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

// Set page title for topbar
$page_title = 'Requests Management';

// Get office ID from session
$office_id = $_SESSION['office_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'approve_request':
            $request_id = $_POST['request_id'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            
            $update_query = "UPDATE borrow_requests SET 
                             status = 'approved', 
                             approved_by = ?, 
                             approved_at = NOW(), 
                             approval_notes = ? 
                             WHERE id = ? AND status = 'pending'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isi", $_SESSION['user_id'], $notes, $request_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Request approved successfully";
                logSystemAction($_SESSION['user_id'], 'approve', 'borrow_request', "Approved borrow request #$request_id");
            } else {
                $_SESSION['error'] = "Error approving request";
            }
            break;
            
        case 'deny_request':
            $request_id = $_POST['request_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            
            $update_query = "UPDATE borrow_requests SET 
                             status = 'denied', 
                             denied_by = ?, 
                             denied_at = NOW(), 
                             denial_reason = ? 
                             WHERE id = ? AND status = 'pending'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isi", $_SESSION['user_id'], $reason, $request_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Request denied successfully";
                logSystemAction($_SESSION['user_id'], 'deny', 'borrow_request', "Denied borrow request #$request_id");
            } else {
                $_SESSION['error'] = "Error denying request";
            }
            break;
            
        case 'return_asset':
            $request_id = $_POST['request_id'] ?? 0;
            $condition = $_POST['return_condition'] ?? 'good';
            $notes = $_POST['return_notes'] ?? '';
            
            $update_query = "UPDATE borrow_requests SET 
                             status = 'returned', 
                             returned_at = NOW(), 
                             return_condition = ?, 
                             return_notes = ? 
                             WHERE id = ? AND status = 'approved'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $condition, $notes, $request_id);
            
            if ($stmt->execute()) {
                // Update asset status back to available
                $asset_update = "UPDATE asset_items SET status = 'available' 
                                WHERE id = (SELECT asset_id FROM borrow_requests WHERE id = ?)";
                $stmt2 = $conn->prepare($asset_update);
                $stmt2->bind_param("i", $request_id);
                $stmt2->execute();
                
                $_SESSION['success'] = "Asset returned successfully";
                logSystemAction($_SESSION['user_id'], 'return', 'borrow_request', "Returned asset for request #$request_id");
            } else {
                $_SESSION['error'] = "Error returning asset";
            }
            break;
    }
    
    header('Location: requests.php');
    exit();
}

// Fetch requests data
$incoming_requests = [];
$outgoing_requests = [];
$request_stats = [
    'pending_incoming' => 0,
    'approved_incoming' => 0,
    'pending_outgoing' => 0,
    'approved_outgoing' => 0
];

if ($office_id && $conn) {
    try {
        // Incoming requests (other offices requesting from this office)
        $incoming_query = "SELECT br.*, u.first_name, u.last_name, u.email, 
                          o.office_name as requester_office, ai.description as asset_description,
                          ai.asset_code, ac.category_name
                          FROM borrow_requests br
                          JOIN users u ON br.requested_by = u.id
                          JOIN offices o ON u.office_id = o.id
                          JOIN asset_items ai ON br.asset_id = ai.id
                          LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                          WHERE br.requested_to_office = ? 
                          ORDER BY br.created_at DESC";
        $stmt = $conn->prepare($incoming_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $incoming_requests[] = $row;
            
            // Calculate stats
            if ($row['status'] === 'pending') {
                $request_stats['pending_incoming']++;
            } elseif ($row['status'] === 'approved') {
                $request_stats['approved_incoming']++;
            }
        }
        
        // Outgoing requests (this office requesting from other offices)
        $outgoing_query = "SELECT br.*, u.first_name, u.last_name, u.email, 
                          o.office_name as approver_office, ai.description as asset_description,
                          ai.asset_code, ac.category_name
                          FROM borrow_requests br
                          JOIN users u ON br.requested_by = u.id
                          JOIN offices o ON br.requested_to_office = o.id
                          JOIN asset_items ai ON br.asset_id = ai.id
                          LEFT JOIN asset_categories ac ON ai.asset_category_id = ac.id
                          WHERE br.requested_by_office = ? 
                          ORDER BY br.created_at DESC";
        $stmt = $conn->prepare($outgoing_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $outgoing_requests[] = $row;
            
            // Calculate stats
            if ($row['status'] === 'pending') {
                $request_stats['pending_outgoing']++;
            } elseif ($row['status'] === 'approved') {
                $request_stats['approved_outgoing']++;
            }
        }
        
    } catch (Exception $e) {
        error_log("Error fetching requests: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests Management - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
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
            border-left: 4px solid #5CC2F2;
        }
        
        .request-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.1);
            margin-bottom: 1rem;
        }
        
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-denied {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .status-returned {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
            margin: 0.125rem;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .nav-tabs .nav-link {
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            border: none;
            background: rgba(255, 255, 255, 0.5);
            color: #666;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-tabs .nav-link:hover {
            background: rgba(255, 255, 255, 0.8);
        }
        
        .tab-content {
            background: white;
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-send"></i> Requests Management
                    </h1>
                    <p class="text-muted mb-0">Manage asset borrow requests - incoming and outgoing</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="bi bi-plus-circle"></i> New Request
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Request Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $request_stats['pending_incoming']; ?></div>
                            <div class="text-muted">Pending Incoming</div>
                            <small class="text-warning">Awaiting your action</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-inbox fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $request_stats['approved_incoming']; ?></div>
                            <div class="text-muted">Approved Incoming</div>
                            <small class="text-success">Active borrows</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $request_stats['pending_outgoing']; ?></div>
                            <div class="text-muted">Pending Outgoing</div>
                            <small class="text-info">Awaiting approval</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-send fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $request_stats['approved_outgoing']; ?></div>
                            <div class="text-muted">Approved Outgoing</div>
                            <small class="text-primary">Your active borrows</small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-box-seam fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Request Tabs -->
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-white border-bottom-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#incoming" role="tab">
                            <i class="bi bi-inbox"></i> Incoming Requests
                            <?php if ($request_stats['pending_incoming'] > 0): ?>
                                <span class="badge bg-warning text-dark ms-1"><?php echo $request_stats['pending_incoming']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#outgoing" role="tab">
                            <i class="bi bi-send"></i> Outgoing Requests
                            <?php if ($request_stats['pending_outgoing'] > 0): ?>
                                <span class="badge bg-info ms-1"><?php echo $request_stats['pending_outgoing']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    <!-- Incoming Requests Tab -->
                    <div class="tab-pane fade show active" id="incoming" role="tabpanel">
                        <?php if (!empty($incoming_requests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Requester</th>
                                            <th>Office</th>
                                            <th>Asset</th>
                                            <th>Purpose</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incoming_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($request['email']); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['requester_office']); ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($request['asset_description']); ?></strong>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($request['asset_code']); ?></div>
                                                        <div class="small text-info"><?php echo htmlspecialchars($request['category_name'] ?? 'Uncategorized'); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                                                <td>
                                                    <div class="small">
                                                        <div>From: <?php echo date('M j, Y', strtotime($request['start_date'])); ?></div>
                                                        <div>To: <?php echo date('M j, Y', strtotime($request['end_date'])); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success action-btn" 
                                                                onclick="approveRequest(<?php echo $request['id']; ?>)">
                                                            <i class="bi bi-check-circle"></i> Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-danger action-btn" 
                                                                onclick="denyRequest(<?php echo $request['id']; ?>)">
                                                            <i class="bi bi-x-circle"></i> Deny
                                                        </button>
                                                    <?php elseif ($request['status'] === 'approved'): ?>
                                                        <button class="btn btn-sm btn-primary action-btn" 
                                                                onclick="returnAsset(<?php echo $request['id']; ?>)">
                                                            <i class="bi bi-arrow-return-left"></i> Return
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-info action-btn" 
                                                            onclick="viewDetails(<?php echo $request['id']; ?>)">
                                                        <i class="bi bi-eye"></i> Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h5>No Incoming Requests</h5>
                                <p>Other offices haven't requested to borrow assets from your office yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Outgoing Requests Tab -->
                    <div class="tab-pane fade" id="outgoing" role="tabpanel">
                        <?php if (!empty($outgoing_requests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Requested To</th>
                                            <th>Asset</th>
                                            <th>Purpose</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($outgoing_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($request['approver_office']); ?></strong>
                                                        <div class="small text-muted">
                                                            Processed by: <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($request['asset_description']); ?></strong>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($request['asset_code']); ?></div>
                                                        <div class="small text-info"><?php echo htmlspecialchars($request['category_name'] ?? 'Uncategorized'); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                                                <td>
                                                    <div class="small">
                                                        <div>From: <?php echo date('M j, Y', strtotime($request['start_date'])); ?></div>
                                                        <div>To: <?php echo date('M j, Y', strtotime($request['end_date'])); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info action-btn" 
                                                            onclick="viewDetails(<?php echo $request['id']; ?>)">
                                                        <i class="bi bi-eye"></i> Details
                                                    </button>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-outline-warning action-btn" 
                                                                onclick="cancelRequest(<?php echo $request['id']; ?>)">
                                                            <i class="bi bi-x-circle"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-send"></i>
                                <h5>No Outgoing Requests</h5>
                                <p>You haven't requested to borrow assets from other offices yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Approve Request Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Approve Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="approve_request">
                    <input type="hidden" name="request_id" id="approveRequestId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                            <textarea class="form-control" id="approval_notes" name="notes" rows="3" 
                                    placeholder="Add any notes or conditions for this approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Approve Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Deny Request Modal -->
    <div class="modal fade" id="denyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle"></i> Deny Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="deny_request">
                    <input type="hidden" name="request_id" id="denyRequestId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="denial_reason" class="form-label">Reason for Denial <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="denial_reason" name="reason" rows="3" 
                                    placeholder="Please provide a reason for denying this request..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Deny Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Return Asset Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-return-left"></i> Return Asset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="return_asset">
                    <input type="hidden" name="request_id" id="returnRequestId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="return_condition" class="form-label">Asset Condition</label>
                            <select class="form-control" id="return_condition" name="return_condition" required>
                                <option value="excellent">Excellent - Like new</option>
                                <option value="good" selected>Good - Minor wear</option>
                                <option value="fair">Fair - Noticeable wear</option>
                                <option value="poor">Poor - Significant damage</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="return_notes" class="form-label">Return Notes (Optional)</label>
                            <textarea class="form-control" id="return_notes" name="return_notes" rows="3" 
                                    placeholder="Add any notes about the asset condition..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-return-left"></i> Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Approve Request
        function approveRequest(requestId) {
            document.getElementById('approveRequestId').value = requestId;
            new bootstrap.Modal(document.getElementById('approveModal')).show();
        }
        
        // Deny Request
        function denyRequest(requestId) {
            document.getElementById('denyRequestId').value = requestId;
            new bootstrap.Modal(document.getElementById('denyModal')).show();
        }
        
        // Return Asset
        function returnAsset(requestId) {
            document.getElementById('returnRequestId').value = requestId;
            new bootstrap.Modal(document.getElementById('returnModal')).show();
        }
        
        // View Details (placeholder function)
        function viewDetails(requestId) {
            // This would open a detailed view modal
            console.log('View details for request:', requestId);
            alert('Detailed view would be implemented here');
        }
        
        // Cancel Request (placeholder function)
        function cancelRequest(requestId) {
            if (confirm('Are you sure you want to cancel this request?')) {
                console.log('Cancel request:', requestId);
                // This would implement the cancellation logic
            }
        }
    </script>
    
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
