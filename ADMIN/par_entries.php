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

logSystemAction($_SESSION['user_id'], 'Accessed PAR Entries', 'forms', 'par_entries.php');

// Get all PAR forms with items
$par_forms = [];
$result = $conn->query("
    SELECT 
        f.*,
        SUM(i.quantity) as item_count,
        SUM(i.quantity * i.amount) as total_value
    FROM par_forms f 
    LEFT JOIN par_items i ON f.id = i.form_id 
    GROUP BY f.id 
    ORDER BY f.created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $par_forms[] = $row;
    }
}

// Get header image from forms table
$header_image = '';
$result = $conn->query("SELECT header_image FROM forms WHERE form_code = 'PAR'");
if ($result && $row = $result->fetch_assoc()) {
    $header_image = $row['header_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAR Entries - PIMS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'PAR Entries'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-file-earmark-text"></i> PAR Entries
                    </h1>
                    <p class="text-muted mb-0">View and manage Property Acknowledgment Receipt entries</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end flex-column">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search PAR forms..." onkeypress="handleSearchKeyPress(event)" oninput="toggleClearButton()">
                            <button type="button" id="clearSearchBtn" onclick="clearSearch()" style="display: none;" title="Clear search">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="par_form.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> New PAR
                            </a>
                            <button class="btn btn-success btn-sm" onclick="exportPARData()">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($par_forms); ?></div>
                    <div class="stats-label"><i class="bi bi-file-earmark-text"></i> Total PAR Forms</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        <?php 
                        $total_items = array_sum(array_column($par_forms, 'item_count'));
                        echo $total_items; 
                        ?>
                    </div>
                    <div class="stats-label"><i class="bi bi-list-check"></i> Total Items</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        ₱<?php 
                        $total_value = array_sum(array_column($par_forms, 'total_value'));
                        echo number_format($total_value, 2); 
                        ?>
                    </div>
                    <div class="stats-label"><i class="bi bi-currency-dollar"></i> Total Value</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo date('M Y'); ?></div>
                    <div class="stats-label"><i class="bi bi-calendar"></i> Current Period</div>
                </div>
            </div>
        </div>

        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-file-earmark-text"></i> PAR Forms Management
            </div>
            
            <?php if (empty($par_forms)): ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark-text"></i>
                    <h4>No PAR Entries Found</h4>
                    <p class="text-muted">Start by creating your first PAR form.</p>
                    <a href="par_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create PAR Form
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($par_forms as $par): ?>
                        <div class="col-12">
                            <div class="par-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <div class="par-number">
                                                    <i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($par['par_no']); ?>
                                                </div>
                                                <h5 class="mb-2"><?php echo htmlspecialchars($par['entity_name']); ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="bi bi-cash-stack"></i> Fund Cluster: <?php echo htmlspecialchars($par['fund_cluster']); ?>
                                                </p>
                                                <?php if (!empty($par['office_location'])): ?>
                                                    <p class="text-muted mb-2">
                                                        <i class="bi bi-geo-alt"></i> Office: <?php echo htmlspecialchars($par['office_location']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">Received By:</small>
                                                <p class="mb-1"><?php echo htmlspecialchars($par['received_by_name']); ?></p>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($par['received_by_position']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">Issued By:</small>
                                                <p class="mb-1"><?php echo htmlspecialchars($par['issued_by_name']); ?></p>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($par['issued_by_position']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-end">
                                        <div class="mb-3">
                                            <div class="text-muted small">Items Count</div>
                                            <div class="h4"><?php echo $par['item_count']; ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="text-muted small">Total Value</div>
                                            <div class="h4">₱<?php echo number_format($par['total_value'], 2); ?></div>
                                        </div>
                                        <div class="text-muted small mb-3">
                                            <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($par['created_at'])); ?>
                                        </div>
                                        <div class="no-print">
                                            <button class="btn btn-sm btn-outline-primary btn-action me-2" onclick="viewPAR(<?php echo $par['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-outline-info btn-action" onclick="printPAR(<?php echo $par['id']; ?>)">
                                                <i class="bi bi-printer"></i> Print
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    
    <?php include 'includes/logout-modal.php'; ?>
    <?php include 'includes/change-password-modal.php'; ?>
    <?php include 'includes/sidebar-scripts.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewPAR(id) {
            window.open('par_view.php?id=' + id, '_blank');
        }
        
        function printPAR(id) {
            window.open('print_par.php?id=' + id, '_blank');
        }
        
        function searchPARForms() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const parCards = document.querySelectorAll('.par-card');
            let visibleCount = 0;
            
            parCards.forEach(card => {
                // Get specific fields for more targeted search
                const parNumberElement = card.querySelector('.par-number');
                const parNumber = parNumberElement ? parNumberElement.textContent.toLowerCase() : '';
                const entityName = card.querySelector('h5')?.textContent.toLowerCase() || '';
                
                // More specific selectors for fund cluster
                const fundClusterElement = card.querySelector('.bi-cash-stack')?.parentElement;
                const fundCluster = fundClusterElement ? fundClusterElement.textContent.toLowerCase() : '';
                
                // More specific selectors for received by and issued by
                const receivedByNameElement = card.querySelector('.col-md-6:nth-child(1) p:nth-child(2)');
                const receivedByName = receivedByNameElement ? receivedByNameElement.textContent.toLowerCase() : '';
                
                const issuedByNameElement = card.querySelector('.col-md-6:nth-child(2) p:nth-child(2)');
                const issuedByName = issuedByNameElement ? issuedByNameElement.textContent.toLowerCase() : '';
                
                // Office location (optional)
                const officeElement = card.querySelector('.bi-geo-alt')?.parentElement;
                const office = officeElement ? officeElement.textContent.toLowerCase() : '';
                
                // Date element
                const dateElement = card.querySelector('.bi-calendar')?.parentElement;
                const date = dateElement ? dateElement.textContent.toLowerCase() : '';
                
                // Prioritize PAR number matching - exact match gets highest priority
                let matches = false;
                
                if (searchTerm === '') {
                    matches = true;
                } else if (parNumber.includes(searchTerm)) {
                    // PAR number match - prioritize this
                    matches = true;
                    // Highlight the PAR number if it's a close match
                    if (searchTerm.length >= 3) {
                        parNumberElement.style.backgroundColor = '#fff3cd';
                        parNumberElement.style.borderRadius = '4px';
                        parNumberElement.style.padding = '2px 6px';
                    }
                } else {
                    // Check other fields
                    const otherFields = [
                        entityName,
                        fundCluster,
                        receivedByName,
                        issuedByName,
                        office,
                        date
                    ];
                    
                    matches = otherFields.some(field => field.includes(searchTerm));
                }
                
                // Show/hide card based on search
                if (matches) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                    // Reset highlight
                    const parNumberElement = card.querySelector('.par-number');
                    if (parNumberElement) {
                        parNumberElement.style.backgroundColor = '';
                        parNumberElement.style.borderRadius = '';
                        parNumberElement.style.padding = '';
                    }
                }
            });
            
            // Show/hide "no results" message
            showNoResultsMessage(visibleCount === 0 && searchTerm !== '');
        }
        
        function toggleClearButton() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            
            // Show clear button if there's text, hide if empty
            if (searchInput.value.trim()) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
                // Trigger search to show all results when cleared
                searchPARForms();
            }
        }
        
        function handleSearchKeyPress(event) {
            // Trigger search when Enter key is pressed
            if (event.key === 'Enter') {
                event.preventDefault();
                searchPARForms();
            }
        }
        
        function showNoResultsMessage(show) {
            // Remove existing no results message if present
            const existingMessage = document.getElementById('noResultsMessage');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            if (show) {
                const noResultsHtml = `
                    <div id="noResultsMessage" class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-search display-1 text-muted"></i>
                            <h4 class="mt-3 text-muted">No Results Found</h4>
                            <p class="text-muted">Try searching by PAR number, entity name, or other details.</p>
                            <button class="btn btn-outline-secondary" onclick="clearSearch()">
                                <i class="bi bi-x-circle"></i> Clear Search
                            </button>
                        </div>
                    </div>
                `;
                
                // Insert after the statistics row
                const statsRow = document.querySelector('.row.mb-4');
                if (statsRow) {
                    statsRow.insertAdjacentHTML('afterend', noResultsHtml);
                }
            }
        }
        
        function clearSearch() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            
            searchInput.value = '';
            clearBtn.style.display = 'none';
            
            // Remove all highlights
            const parNumbers = document.querySelectorAll('.par-number');
            parNumbers.forEach(element => {
                element.style.backgroundColor = '';
                element.style.borderRadius = '';
                element.style.padding = '';
            });
            
            searchPARForms();
        }
        
        // Add search on input change
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', searchPARForms);
            }
        });
        
        function exportPARData() {
            // Create export modal
            const modalHtml = `
                <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exportModalLabel">
                                    <i class="bi bi-download"></i> Export PAR Entries
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="exportForm" method="POST" action="export_par.php">
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
                                            <strong>Excel (CSV) - Summary:</strong> Summary of PAR forms only
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
                form.action = 'export_par.php';
                
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
