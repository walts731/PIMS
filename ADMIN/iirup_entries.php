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

logSystemAction($_SESSION['user_id'], 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php');

// Get all IIRUP forms with items
$iirup_forms = [];
$result = $conn->query("
    SELECT 
        f.*,
        COUNT(i.id) as item_count,
        SUM(i.total_cost) as total_value
    FROM iirup_forms f 
    LEFT JOIN iirup_items i ON f.id = i.form_id 
    GROUP BY f.id 
    ORDER BY f.created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $iirup_forms[] = $row;
    }
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'IIRUP'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IIRUP Entries - PIMS</title>
    
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
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php $page_title = 'IIRUP Entries'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-file-earmark-text"></i> IIRUP Entries
                    </h1>
                    <p class="text-muted mb-0">View and manage Inventory and Inspection Report of Unserviceable Property entries</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                            <li>
                                <a href="iirup_form.php" class="dropdown-item">
                                    <i class="bi bi-plus-circle"></i> New IIRUP
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
                <i class="bi bi-file-earmark-text"></i> IIRUP Forms Management
            </div>
            
            <?php if (empty($iirup_forms)): ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark-text"></i>
                    <h4>No IIRUP Entries Found</h4>
                    <p class="text-muted">Start by creating your first IIRUP form.</p>
                    <a href="iirup_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create IIRUP Form
                    </a>
                </div>
            <?php else: ?>
                <!-- IIRUP Forms Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="iirupTable">
                        <thead class="table-primary">
                            <tr>
                                <th>IIRUP Number</th>
                                <th>Accountable Officer</th>
                                <th>Department/Office</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($iirup_forms as $iirup): ?>
                                <tr>
                                    <td>
                                        <div class="iirup-number">
                                            <i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($iirup['form_number']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($iirup['accountable_officer']); ?></strong>
                                        <div><small class="text-muted"><?php echo htmlspecialchars($iirup['accountable_officer_designation']); ?></small></div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($iirup['department_office']); ?></strong>
                                        <div><small class="text-muted">As of Year: <?php echo htmlspecialchars($iirup['as_of_year']); ?></small></div>
                                    </td>
                                    <td data-order="<?php echo strtotime($iirup['created_at']); ?>">
                                        <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($iirup['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewIIRUP(<?php echo $iirup['id']; ?>)" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="printIIRUP(<?php echo $iirup['id']; ?>)" title="Print">
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
            $('#iirupTable').DataTable({
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
        function viewIIRUP(id) {
            window.open('iirup_view.php?id=' + id, '_blank');
        }
        
        function printIIRUP(id) {
            window.open('print_iirup.php?id=' + id, '_blank');
        }
        
        function searchIIRUPForms() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const iirupCards = document.querySelectorAll('.iirup-card');
            
            iirupCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                const iirupNumber = card.querySelector('.iirup-number')?.textContent.toLowerCase() || '';
                const accountableOfficer = card.querySelector('h5')?.textContent.toLowerCase() || '';
                const department = card.querySelector('.text-muted')?.textContent.toLowerCase() || '';
                
                // Check if search term matches any field
                const matches = text.includes(searchTerm) || 
                               iirupNumber.includes(searchTerm) || 
                               accountableOfficer.includes(searchTerm) || 
                               department.includes(searchTerm);
                
                // Show/hide card based on search
                if (matches || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Add search on input change
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', searchIIRUPForms);
            }
        });
        
        function exportIIRUPData() {
            // Create export modal
            const modalHtml = `
                <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exportModalLabel">
                                    <i class="bi bi-download"></i> Export IIRUP Entries
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="exportForm" method="POST" action="export_iirup.php">
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
                                            <strong>Excel (CSV) - Summary:</strong> Summary of IIRUP forms only
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
                form.action = 'export_iirup.php';
                
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
