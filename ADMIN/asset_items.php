<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get asset ID from URL
$asset_id = isset($_GET['asset_id']) ? intval($_GET['asset_id']) : 0;

if ($asset_id === 0) {
    header('Location: assets.php');
    exit();
}

// Get asset details
$asset = null;
$asset_sql = "SELECT a.*, ac.category_name, ac.category_code, o.office_name 
              FROM assets a 
              LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
              LEFT JOIN offices o ON a.office_id = o.id 
              WHERE a.id = ?";
$asset_stmt = $conn->prepare($asset_sql);
$asset_stmt->bind_param("i", $asset_id);
$asset_stmt->execute();
$asset_result = $asset_stmt->get_result();
if ($asset_row = $asset_result->fetch_assoc()) {
    $asset = $asset_row;
}
$asset_stmt->close();

if (!$asset) {
    header('Location: assets.php');
    exit();
}

// Get asset items
$items = [];
$items_sql = "SELECT ai.*, CONCAT(e.firstname, ' ', e.lastname) as employee_name, e.employee_no 
              FROM asset_items ai 
              LEFT JOIN employees e ON ai.employee_id = e.id
              WHERE ai.asset_id = ? ORDER BY ai.id";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $asset_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
while ($item_row = $items_result->fetch_assoc()) {
    $items[] = $item_row;
}
$items_stmt->close();

// Calculate statistics
$total_items = count($items);
$serviceable_items = count(array_filter($items, function($item) { return $item['status'] === 'serviceable'; }));
$unserviceable_items = count(array_filter($items, function($item) { return $item['status'] === 'unserviceable'; }));
$redtagged_items = count(array_filter($items, function($item) { return $item['status'] === 'red_tagged'; }));
$borrowed_items = count(array_filter($items, function($item) { return $item['status'] === 'borrowed'; }));
$notag_items = count(array_filter($items, function($item) { return $item['status'] === 'no_tag'; }));
$maintenance_items = count(array_filter($items, function($item) { return $item['status'] === 'maintenance'; }));
$disposed_items = count(array_filter($items, function($item) { return $item['status'] === 'disposed'; }));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Items - <?php echo htmlspecialchars($asset['description']); ?> | PIMS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Unified CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
        }
        #assetStatusChart {
            max-height: 200px;
            max-width: 200px;
        }
    </style>
</head>
<body>
    <?php $page_title = 'Asset Items'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-box"></i> Asset Items
                    </h1>
                    <p class="text-muted mb-0">Individual items for: <?php echo htmlspecialchars($asset['description']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="assets.php" class="dropdown-item">
                                    <i class="bi bi-box"></i> Assets
                                </a>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="exportAssetItems()">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="section-card glass-morphism h-100">
                            <div class="section-title">
                                <i class="bi bi-pie-chart"></i> Asset Status Distribution
                            </div>
                            <div class="chart-container">
                                <canvas id="assetStatusChart"></canvas>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-4">
                                    <div class="small text-muted">Serviceable</div>
                                    <div class="fw-bold text-success"><?php echo $serviceable_items; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Red Tagged</div>
                                    <div class="fw-bold text-danger"><?php echo $redtagged_items; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Maintenance</div>
                                    <div class="fw-bold text-warning"><?php echo $maintenance_items; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Borrowed</div>
                                    <div class="fw-bold text-primary"><?php echo $borrowed_items; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Disposed</div>
                                    <div class="fw-bold text-danger"><?php echo $disposed_items; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Unserviceable</div>
                                    <div class="fw-bold text-secondary"><?php echo $unserviceable_items; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="section-card h-100">
                            <div class="section-title">
                                <i class="bi bi-info-circle"></i> Asset Information
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($asset['category_code'] . ' - ' . $asset['category_name']); ?></p>
                                    <p><strong>Unit:</strong> <?php echo htmlspecialchars($asset['unit']); ?></p>
                                    <p><strong>Office:</strong> <?php echo htmlspecialchars($asset['office_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Quantity:</strong> <?php echo $asset['quantity']; ?></p>
                                    <p><strong>Unit Cost:</strong> <?php echo number_format($asset['unit_cost'], 2); ?></p>
                                    <p><strong>Total Value:</strong> <?php echo number_format($asset['quantity'] * $asset['unit_cost'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-list-ul"></i> Individual Asset Items
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                            <option value="">All Statuses</option>
                            <option value="Serviceable">Serviceable</option>
                            <option value="Unserviceable">Unserviceable</option>
                            <option value="Red-Tagged">Red-Tagged</option>
                            <option value="Borrowed">Borrowed</option>
                            <option value="No Tag">No Tag</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Disposed">Disposed</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <?php if (!empty($items)): ?>
                    <table class="table table-hover" id="assetItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Property No</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Value</th>
                                <th>Acquisition Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['property_no'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($item['status']) {
                                            case 'serviceable':
                                                $status_class = 'status-serviceable';
                                                $display_status = 'Serviceable';
                                                break;
                                            case 'unserviceable':
                                                $status_class = 'status-unserviceable';
                                                $display_status = 'Unserviceable';
                                                break;
                                            case 'red_tagged':
                                                $status_class = 'status-red-tagged';
                                                $display_status = 'Red-Tagged';
                                                break;
                                            case 'borrowed':
                                                $status_class = 'status-borrowed';
                                                $display_status = 'Borrowed';
                                                break;
                                            case 'no_tag':
                                                $status_class = 'status-notag';
                                                $display_status = 'No Tag';
                                                break;
                                            case 'maintenance':
                                                $status_class = 'status-maintenance';
                                                $display_status = 'Maintenance';
                                                break;
                                            case 'disposed':
                                                $status_class = 'status-disposed';
                                                $display_status = 'Disposed';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo isset($display_status) ? $display_status : ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-value"><?php echo number_format($item['value'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($item['acquisition_date'])); ?></td>
                                    <td>
                                        <?php if ($item['status'] === 'no_tag'): ?>
                                            <a href="create_tag.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-warning btn-action" title="Create Tag">
                                                <i class="bi bi-tag"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="view_asset_item.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-info btn-action" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>No Items Found</h4>
                        <p class="text-muted">No individual items found for this asset.</p>
                    </div>
                <?php endif; ?>
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
        // Initialize DataTable
        let assetItemsTable;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Asset Status Donut Chart
            const ctx = document.getElementById('assetStatusChart').getContext('2d');
            const assetStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Serviceable', 'Red Tagged', 'Maintenance', 'Borrowed', 'Disposed', 'Unserviceable', 'No Tag'],
                    datasets: [{
                        data: [
                            <?php echo $serviceable_items; ?>,
                            <?php echo $redtagged_items; ?>,
                            <?php echo $maintenance_items; ?>,
                            <?php echo $borrowed_items; ?>,
                            <?php echo $disposed_items; ?>,
                            <?php echo $unserviceable_items; ?>,
                            <?php echo $notag_items; ?>
                        ],
                        backgroundColor: [
                            '#28a745', // Success (Serviceable)
                            '#dc3545', // Danger (Red Tagged)
                            '#ffc107', // Warning (Maintenance)
                            '#007bff', // Primary (Borrowed)
                            '#dc3545', // Danger (Disposed)
                            '#6c757d', // Secondary (Unserviceable)
                            '#17a2b8'  // Info (No Tag)
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1, // Force square aspect ratio
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    elements: {
                        arc: {
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }
                    }
                }
            });
            
            // Initialize DataTable
            assetItemsTable = $('#assetItemsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[4, 'desc']], // Sort by Acquisition Date by default (now index 4)
                columnDefs: [
                    {
                        targets: 0, // Property No column
                        orderable: true
                    },
                    {
                        targets: 1, // Description column
                        orderable: true
                    },
                    {
                        targets: 2, // Status column
                        orderable: true,
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return data;
                            }
                            // Extract text from span for sorting
                            return data.replace(/<[^>]*>/g, '').trim();
                        }
                    },
                    {
                        targets: 3, // Value column
                        orderable: true,
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                // Remove formatting and convert to number for sorting
                                return parseFloat(data.replace(/[^0-9.-]+/g, ''));
                            }
                            return data;
                        }
                    },
                    {
                        targets: 4, // Acquisition Date column
                        orderable: true,
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                // Convert date string to timestamp for sorting
                                return new Date(data).getTime();
                            }
                            return data;
                        }
                    },
                    {
                        targets: 5, // Actions column
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                dom: '<"row"<"col-md-6"l><"col-md-6 text-end"f>>rtip',
                language: {
                    search: "Search items:",
                    lengthMenu: "Show _MENU_ items per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ items",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No items available",
                    zeroRecords: "No matching items found"
                }
            });
            
            // Status filter
            $('#statusFilter').on('change', function() {
                const statusValue = this.value;
                if (statusValue) {
                    assetItemsTable.column(2).search(statusValue).draw();
                } else {
                    assetItemsTable.column(2).search('').draw();
                }
            });
        });
        
        // Export asset items function
        function exportAssetItems() {
            // Use DataTables export functionality
            const data = assetItemsTable.data().toArray();
            let csv = 'Property No,Description,Status,Value,Acquisition Date\n';
            
            data.forEach(row => {
                const rowData = [
                    row[0], // Property No
                    row[1], // Description
                    row[2].replace(/<[^>]*>/g, '').trim(), // Status
                    row[3].replace(/[^0-9.-]+/g, ''), // Value
                    row[4]  // Acquisition Date
                ];
                csv += rowData.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `asset_items_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
