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
                    $borrow_id = $stmt->insert_id;
                    $stmt->close();

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
    $stmt = $conn->prepare("SELECT ai.id, ai.description, ai.property_no, ai.inventory_tag,
                           c.category_name
                           FROM asset_items ai
                           LEFT JOIN assets a ON ai.asset_id = a.id
                           LEFT JOIN asset_categories c ON a.asset_categories_id = c.id
                           WHERE ai.status = 'serviceable'
                           AND c.category_name NOT IN ('LND', 'Buildings', 'OInfra', 'Land Imp')
                           ORDER BY c.category_name, ai.description");
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

// Get unique barangays for filter
$barangays = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT barangay FROM borrow_form_submissions WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row['barangay'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error loading barangays: " . $e->getMessage());
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
     <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    
    <style>
        /* Status Badge Styles */
        .badge {
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-approved {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #b8dacc;
        }
        
        .badge-returned {
            background-color: #cff4fc;
            color: #055160;
            border: 1px solid #b6effb;
        }
        
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .badge-rejected {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c6cb;
        }
        
        /* Ensure badges are visible in table */
        .table td .badge {
            display: inline-block;
            white-space: nowrap;
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
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <button class="dropdown-item" onclick="window.location.href='borrow_request.php'">
                                    <i class="bi bi-plus-circle"></i> New Borrow Request
                                </button>
                            </li>
                                                        <li>
                                <button class="dropdown-item" onclick="refreshBorrowRequests()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportData()">
                                    <i class="bi bi-download"></i> Export Requests
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="printActiveRequests()">
                                    <i class="bi bi-printer"></i> Print Active Requests
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>



        <!-- Borrow Requests Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-table me-2"></i>
                    Borrow Requests
                </h5>
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
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="printBorrowForm(<?php echo $request['id']; ?>)">
                                                <i class="bi bi-printer"></i> Print
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    
    <script>
        // View borrow slip
        function viewBorrowSlip(borrowId) {
            // Redirect to the borrow slip page
            window.location.href = `borrow_slip.php?id=${borrowId}`;
        }

        // Print borrow form
        function printBorrowForm(borrowId) {
            // Open print form in new window
            const printWindow = window.open(`print_borrow_form.php?id=${borrowId}`, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
            printWindow.focus();
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

        // Initialize DataTables
        let borrowRequestsTable;
        
        $(document).ready(function() {
            // Check if table has data rows before initializing DataTables
            const tableBody = $('#borrowRequestsTable tbody');
            const hasData = tableBody.find('tr').length > 0 && !tableBody.find('td[colspan]').length;
            
            console.log('Table has data:', hasData);
            console.log('Table rows found:', tableBody.find('tr').length);
            
            // Initialize DataTable with error handling
            try {
                if (hasData) {
                    // Only initialize DataTables if there's actual data
                    borrowRequestsTable = $('#borrowRequestsTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        order: [[6, 'desc']], // Sort by Submitted date column (index 6) by default
                        columnDefs: [
                            {
                                targets: -1, // Actions column (last column)
                                orderable: false,
                                searchable: false
                            }
                        ],
                        dom: '<"row"<"col-md-3"l><"col-md-3 barangay-filter-container"><"col-md-6"f>>rtip',
                        language: {
                            search: "Search requests:",
                            lengthMenu: "Show _MENU_ requests per page",
                            info: "Showing _START_ to _END_ of _TOTAL_ requests",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            },
                            emptyTable: "No borrow requests available",
                            zeroRecords: "No matching borrow requests found"
                        },
                        initComplete: function(settings, json) {
                            console.log('DataTables initialized successfully');
                            
                            // Add barangay filter to DataTables
                            $('.barangay-filter-container').html(`
                                <select id="barangayFilter" class="form-select form-select-sm">
                                    <option value="">All Barangays</option>
                                    <?php foreach ($barangays as $barangay): ?>
                                        <option value="<?php echo htmlspecialchars($barangay); ?>">
                                            <?php echo htmlspecialchars($barangay); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            `);
                            
                            // Barangay filter event handler
                            $('#barangayFilter').on('change', function() {
                                const barangayValue = $(this).val();
                                
                                if (barangayValue) {
                                    borrowRequestsTable.column(3).search(barangayValue); // Barangay column (index 3)
                                } else {
                                    borrowRequestsTable.column(3).search('');
                                }
                                
                                // Redraw the table with applied filter
                                borrowRequestsTable.draw();
                            });
                        }
                    });
                } else {
                    // No data - don't initialize DataTables, just add basic styling
                    $('#borrowRequestsTable').addClass('table-striped');
                    console.log('No data found - DataTables not initialized');
                }
            } catch (error) {
                console.error('DataTables initialization error:', error);
                // Fallback: make table work without DataTables
                $('#borrowRequestsTable').addClass('table-striped');
            }
        });

        // Export functionality (updated for DataTables)
        function exportData() {
            console.log('Export function called');
            
            if (borrowRequestsTable) {
                // Use DataTables export functionality if DataTables is initialized
                try {
                    const data = borrowRequestsTable.data().toArray();
                    let csv = 'Guest Name,Date Borrowed,Return Date,Barangay,Contact,Status,Submitted\n';
                    
                    data.forEach(row => {
                        const rowData = [
                            row[0].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Guest Name
                            row[1].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Date Borrowed
                            row[2].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Return Date
                            row[3].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Barangay
                            row[4].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Contact
                            row[5].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(), // Status
                            row[6].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()  // Submitted
                        ];
                        csv += rowData.map(cell => `"${cell.trim()}"`).join(',') + '\n';
                    });
                    
                    // Download CSV
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'borrow_requests_export.csv';
                    a.click();
                    window.URL.revokeObjectURL(url);
                } catch (error) {
                    console.error('DataTables export error:', error);
                    // Fallback to manual table export
                    exportTableManually();
                }
            } else {
                // DataTables not initialized, use manual export
                exportTableManually();
            }
        }
        
        // Manual export function for when DataTables is not available
        function exportTableManually() {
            console.log('Using manual table export');
            let csv = 'Guest Name,Date Borrowed,Return Date,Barangay,Contact,Status,Submitted\n';
            
            $('#borrowRequestsTable tbody tr').each(function() {
                const $row = $(this);
                // Skip empty state rows
                if ($row.find('td[colspan]').length > 0) {
                    return;
                }
                
                const rowData = [];
                $row.find('td').each(function(index) {
                    let cellText = $(this).text().trim();
                    // Only include first 7 columns (exclude Actions column)
                    if (index < 7) {
                        rowData.push(cellText);
                    }
                });
                
                if (rowData.length > 0) {
                    csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
                }
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'borrow_requests_export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>

    <?php include 'includes/sidebar-scripts.php'; ?>
</body>
</html>
