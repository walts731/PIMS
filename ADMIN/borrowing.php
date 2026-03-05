<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

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

// Log borrowing page access
logSystemAction($_SESSION['user_id'], 'access', 'borrowing', 'Admin accessed borrowing page');

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'submit_borrow_request') {
            // Validate and sanitize input
            $guest_name = trim($_POST['guest_name']);
            $barangay = trim($_POST['barangay']);
            $contact = trim($_POST['contact']);
            $date_borrowed = trim($_POST['date_borrowed']);
            $schedule_return = trim($_POST['schedule_return']);
            $releasing_officer = trim($_POST['releasing_officer']);
            $approved_by = trim($_POST['approved_by']);
            
            // Parse items from simple JSON format
            $items_json = $_POST['items_json'];
            $items = [];
            
            if (!empty($items_json)) {
                try {
                    $items = json_decode($items_json, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("JSON decode error: " . json_last_error_msg());
                        $items = [];
                    }
                } catch (Exception $e) {
                    error_log("Items decode error: " . $e->getMessage());
                    $items = [];
                }
            }

            // Validate required fields
            if (empty($guest_name) || empty($barangay) || empty($contact) || empty($date_borrowed) || 
                empty($schedule_return) || empty($releasing_officer) || empty($approved_by) || empty($items_json)) {
                $_SESSION['error_message'] = "All fields are required!";
            } else {
                try {
                    $conn->begin_transaction();

                    // Insert borrow request
                    $stmt = $conn->prepare("INSERT INTO borrow_form_submissions 
                        (guest_name, barangay, contact, date_borrowed, schedule_return, releasing_officer, approved_by, items, status, submitted_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())");
                    
                    $stmt->bind_param("ssssssss", $guest_name, $barangay, $contact, $date_borrowed, 
                                     $schedule_return, $releasing_officer, $approved_by, $items_json);
                    $stmt->execute();
                    $borrow_id = $stmt->close();

                    // Update asset items status to borrowed (only if items exist and have asset_item_id)
                    if (!empty($items) && is_array($items)) {
                        foreach ($items as $item) {
                            if (isset($item['asset_item_id'])) {
                                $asset_item_id = $item['asset_item_id'];
                                
                                // Update the specific asset item to borrowed status
                                $update_stmt = $conn->prepare("UPDATE asset_items SET status = 'borrowed' WHERE id = ?");
                                $update_stmt->bind_param("i", $asset_item_id);
                                $update_stmt->execute();
                                $update_stmt->close();
                                
                                error_log("Updated asset item ID {$asset_item_id} to borrowed status");
                            }
                        }
                    }

                    $conn->commit();
                    $_SESSION['success_message'] = "Borrow request submitted successfully!";
                    logSystemAction($_SESSION['user_id'], 'borrow_request_submit', 'borrowing', "Borrow ID: $borrow_id, Guest: $guest_name");

                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error submitting borrow request: " . $e->getMessage();
                    logSystemAction($_SESSION['user_id'], 'borrow_request_submit_failed', 'borrowing', "Error: " . $e->getMessage());
                }
            }
            header("Location: borrowing.php");
            exit();
        } elseif ($_POST['action'] === 'mark_returned') {
            $borrow_id = $_POST['borrow_id'];
            
            try {
                $conn->begin_transaction();

                // Get borrow details
                $stmt = $conn->prepare("SELECT items FROM borrow_form_submissions WHERE id = ?");
                $stmt->bind_param("i", $borrow_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $borrow_data = $result->fetch_assoc();
                
                // Parse items from simple JSON format
                $items_json = $borrow_data['items'];
                $items = [];
                
                if (!empty($items_json)) {
                    try {
                        $items = json_decode($items_json, true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            error_log("JSON decode error: " . json_last_error_msg());
                            $items = [];
                        }
                    } catch (Exception $e) {
                        error_log("Items decode error: " . $e->getMessage());
                        $items = [];
                    }
                }
                $stmt->close();

                // Update borrow status
                $update_stmt = $conn->prepare("UPDATE borrow_form_submissions SET status = 'returned', updated_at = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $borrow_id);
                $update_stmt->execute();
                $update_stmt->close();

                // Update asset items status back to serviceable (if asset_item_id exists)
                foreach ($items as $item) {
                    if (isset($item['asset_item_id'])) {
                        $asset_item_id = $item['asset_item_id'];
                        
                        // Update the specific asset item back to serviceable status
                        $update_asset_stmt = $conn->prepare("UPDATE asset_items SET status = 'serviceable' WHERE id = ?");
                        $update_asset_stmt->bind_param("i", $asset_item_id);
                        $update_asset_stmt->execute();
                        $update_asset_stmt->close();
                        
                        error_log("Updated asset item ID {$asset_item_id} back to serviceable status");
                    }
                }

                $conn->commit();
                $_SESSION['success_message'] = "Items marked as returned successfully!";
                logSystemAction($_SESSION['user_id'], 'borrow_items_returned', 'borrowing', "Borrow ID: $borrow_id");

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error marking items as returned: " . $e->getMessage();
                logSystemAction($_SESSION['user_id'], 'borrow_return_failed', 'borrowing', "Error: " . $e->getMessage());
            }
            header("Location: borrowing.php");
            exit();
        }
    }
}

// Get serviceable assets for dropdown
$serviceable_assets = [];
try {
    $stmt = $conn->prepare("SELECT a.description, ai.description as item_description, COUNT(ai.id) as available_count, GROUP_CONCAT(ai.id) as asset_ids
                           FROM asset_items ai 
                           JOIN assets a ON ai.asset_id = a.id 
                           WHERE ai.status = 'serviceable' 
                           GROUP BY a.description, ai.description 
                           ORDER BY a.description, ai.description");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $serviceable_assets[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error_message = "Error loading serviceable assets: " . $e->getMessage();
}

// Get borrow requests
$borrow_requests = [];
try {
    $stmt = $conn->prepare("SELECT id, guest_name, date_borrowed, schedule_return, barangay, contact, status, submitted_at 
                           FROM borrow_form_submissions 
                           ORDER BY submitted_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $borrow_requests[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error_message = "Error loading borrow requests: " . $e->getMessage();
}

// Display messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing Management - PIMS</title>
    
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
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Additional custom styles for borrowing page */
        .badge-approved {
            background-color: #28a745;
        }
        .badge-returned {
            background-color: #17a2b8;
        }
        .items-table {
            max-height: 300px;
            overflow-y: auto;
        }
        .items-table th {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .form-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .form-section h6 {
            margin-bottom: 1rem;
            color: #191ba9;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php 
    $page_title = 'Borrowing Management';
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
                        <i class="bi bi-arrow-left-right"></i> Borrowing Management
                    </h1>
                    <p class="text-muted mb-0">Manage asset borrowing requests and returns</p>
                    <?php if (isset($error_message) && $error_message): ?>
                        <div class="alert alert-danger mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success_message) && $success_message): ?>
                        <div class="alert alert-success mt-2" role="alert">
                            <i class="bi bi-check-circle"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="new_borrow_request.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Borrow Request
                    </a>
                </div>
            </div>
        </div>

            <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($borrow_requests); ?></div>
                    <div class="stats-label"><i class="bi bi-clipboard-check"></i> Total Requests</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count(array_filter($borrow_requests, fn($r) => $r['status'] === 'approved')); ?></div>
                    <div class="stats-label"><i class="bi bi-clock"></i> Active Borrowed</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count(array_filter($borrow_requests, fn($r) => $r['status'] === 'returned')); ?></div>
                    <div class="stats-label"><i class="bi bi-check-circle"></i> Returned</div>
                </div>
            </div>
        </div>

        <!-- Borrow Requests Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-table me-2"></i>
                    Borrow Requests
                </h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search requests..." style="width: 200px;">
                    <button class="btn btn-outline-primary btn-sm" onclick="exportData()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="borrowRequestsTable">
                        <thead>
                            <tr>
                                <th>Guest Name</th>
                                <th>Date Borrowed</th>
                                <th>Return Date</th>
                                <th>Barangay</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($borrow_requests)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No borrow requests found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($borrow_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['guest_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($request['date_borrowed'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($request['schedule_return'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['barangay']); ?></td>
                                        <td><?php echo htmlspecialchars($request['contact']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($request['submitted_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="viewBorrowSlip(<?php echo $request['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <?php if ($request['status'] === 'approved'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAsReturned(<?php echo $request['id']; ?>)">
                                                    <i class="bi bi-check-circle"></i> Return
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <!-- Return Confirmation Modal -->
    <div class="modal fade" id="returnConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle"></i>
                        Confirm Return
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this borrow request as returned?</p>
                    <p>This will update the status of all borrowed items back to "serviceable".</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmReturnBtn">
                        <i class="bi bi-check-circle"></i> Confirm Return
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // View borrow slip
        function viewBorrowSlip(borrowId) {
            // Redirect to the borrow slip page
            window.location.href = `borrow_slip.php?id=${borrowId}`;
        }

        // Mark as returned
        let currentBorrowId = null;
        
        function markAsReturned(borrowId) {
            currentBorrowId = borrowId;
            const modal = new bootstrap.Modal(document.getElementById('returnConfirmModal'));
            modal.show();
        }

        document.getElementById('confirmReturnBtn').addEventListener('click', function() {
            if (currentBorrowId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="mark_returned">
                    <input type="hidden" name="borrow_id" value="${currentBorrowId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('borrowRequestsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length - 1; j++) { // Exclude actions column
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        });

        // Export functionality
        function exportData() {
            const table = document.getElementById('borrowRequestsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            let csv = 'Guest Name,Date Borrowed,Return Date,Barangay,Contact,Status,Submitted\n';
            
            for (let i = 0; i < rows.length; i++) {
                if (rows[i].style.display !== 'none') {
                    const cells = rows[i].getElementsByTagName('td');
                    const rowData = [
                        cells[0].textContent,
                        cells[1].textContent,
                        cells[2].textContent,
                        cells[3].textContent,
                        cells[4].textContent,
                        cells[5].textContent.trim(),
                        cells[6].textContent
                    ];
                    csv += rowData.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',') + '\n';
                }
            }
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `borrow_requests_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>

    <?php include 'includes/sidebar-scripts.php'; ?>
</body>
</html>
