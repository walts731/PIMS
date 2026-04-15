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

logSystemAction($_SESSION['user_id'], 'Accessed ITR Entries', 'forms', 'itr_entries.php');

// Get all ITR forms with items
$itr_forms = [];
$result = $conn->query("
    SELECT 
        f.*,
        COUNT(i.id) as item_count,
        SUM(i.total_amount) as total_value,
        from_emp.firstname as from_firstname,
        from_emp.lastname as from_lastname,
        from_emp.position as from_position,
        to_emp.firstname as to_firstname,
        to_emp.lastname as to_lastname,
        to_emp.position as to_position
    FROM itr_forms f 
    LEFT JOIN itr_items i ON f.id = i.form_id 
    LEFT JOIN employees from_emp ON f.from_office = from_emp.id
    LEFT JOIN employees to_emp ON f.to_office = to_emp.id
    GROUP BY f.id 
    ORDER BY f.created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $itr_forms[] = $row;
    }
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'ITR'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITR Entries - PIMS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php $page_title = 'ITR Entries'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-arrow-left-right"></i> ITR Entries
                    </h1>
                    <p class="text-muted mb-0">View and manage Inventory Transfer Request entries</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <a href="itr_form.php" class="dropdown-item">
                                    <i class="bi bi-plus-circle"></i> New ITR
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh Page
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-arrow-left-right"></i> ITR Forms Management
            </div>
            
            <?php if (empty($itr_forms)): ?>
                <div class="empty-state">
                    <i class="bi bi-arrow-left-right"></i>
                    <h4>No ITR Entries Found</h4>
                    <p class="text-muted">Start by creating your first ITR form.</p>
                    <a href="itr_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create ITR Form
                    </a>
                </div>
            <?php else: ?>
                <!-- ITR Forms Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="itrTable">
                        <thead class="table-primary">
                            <tr>
                                <th>ITR Number</th>
                                <th>From Office</th>
                                <th>To Office</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itr_forms as $itr): ?>
                                <tr>
                                    <td>
                                        <div class="itr-number">
                                            <i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($itr['itr_no']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($itr['from_firstname'] . ' ' . $itr['from_lastname']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($itr['to_firstname'] . ' ' . $itr['to_lastname']); ?></strong>
                                    </td>
                                    <td data-order="<?php echo strtotime($itr['created_at']); ?>">
                                        <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($itr['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewITR(<?php echo $itr['id']; ?>)" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="printITR(<?php echo $itr['id']; ?>)" title="Print">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    
    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTables when document is ready
        $(document).ready(function() {
            $('#itrTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[3, 'desc']], // Sort by Date Created descending by default
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                columnDefs: [
                    {
                        targets: 4, // Actions column
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });
        
        function viewITR(id) {
            window.open('itr_view.php?id=' + id, '_blank');
        }
        
        function printITR(id) {
            window.open('print_itr.php?id=' + id, '_blank');
        }
        
        function exportITRData() {
            // Create export modal
            const modalHtml = `
                <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exportModalLabel">
                                    <i class="bi bi-download"></i> Export ITR Entries
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="exportForm" method="POST" action="export_itr.php">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="exportFormat" class="form-label">Export Format</label>
                                        <select class="form-select" id="exportFormat" name="format" required>
                                            <option value="excel">Excel (CSV) - Detailed</option>
                                            <option value="summary">Excel (CSV) - Summary</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="dateFrom" class="form-label">Date From (Optional)</label>
                                        <input type="date" class="form-control" id="dateFrom" name="date_from">
                                    </div>
                                    <div class="mb-3">
                                        <label for="dateTo" class="form-label">Date To (Optional)</label>
                                        <input type="date" class="form-control" id="dateTo" name="date_to">
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <small>
                                            <strong>Excel (CSV) - Detailed:</strong> All items with complete details<br>
                                            <strong>Excel (CSV) - Summary:</strong> Summary of ITR forms only
                                        </small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-download"></i> Export
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if present
            const existingModal = document.getElementById('exportModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body and show it
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('exportModal'));
            modal.show();
            
            // Handle form submission
            document.getElementById('exportForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const format = formData.get('format');
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
                submitBtn.disabled = true;
                
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'export_itr.php';
                
                // Add form data
                for (const [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
                
                // Reset button after delay
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
                }, 1000);
            });
        }
    </script>
</body>
</html>
