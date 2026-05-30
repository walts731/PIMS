<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';
require_once '../includes/asset_specific_manager.php';

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

// Log no inventory tag page access
logSystemAction($_SESSION['user_id'], 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page');

// Flash messages from redirects (e.g. after delete)
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Initialize asset specific manager
$assetManager = new AssetSpecificManager($conn);

// Handle filter parameters
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$office_filter = isset($_GET['office']) ? intval($_GET['office']) : 0;

// Get asset items without inventory tags
$asset_items = [];
try {
    $sql = "SELECT ai.*, ai.description as item_description, a.description as asset_description, ac.category_name, ac.category_code, o.office_name
            FROM asset_items ai 
            LEFT JOIN assets a ON ai.asset_id = a.id 
            LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id 
            LEFT JOIN offices o ON ai.office_id = o.id 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Filter by asset items that might need inventory tags
    $sql .= " AND ai.status = 'no_tag'";
    
    if ($office_filter > 0) {
        $sql .= " AND ai.office_id = ?";
        $params[] = $office_filter;
        $types .= 'i';
    }
    
    if (!empty($search_filter)) {
        $sql .= " AND (ai.description LIKE ? OR ac.category_name LIKE ? OR o.office_name LIKE ? OR a.description LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ssss';
    }
    
    $sql .= " ORDER BY ai.last_updated DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $asset_items[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    $message = "Error fetching asset items: " . $e->getMessage();
    $message_type = "danger";
}

// Get offices for dropdown
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

// Get statistics
$stats = [];
try {
    $sql = "SELECT 
                COUNT(*) as total_untagged,
                COALESCE(SUM(value), 0) as total_value
            FROM asset_items 
            WHERE status = 'no_tag'";
    $result = $conn->query($sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Inventory Tag - PIMS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php $page_title = 'No Inventory Tag'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-exclamation-triangle"></i> No Inventory Tag
                    </h1>
                    <p class="text-muted mb-0">Assets that require inventory tagging</p>
                    <?php if (isset($message) && $message): ?>
                        <div class="alert alert-<?php echo $message_type ?? 'danger'; ?> mt-2" role="alert">
                            <i class="bi bi-<?php echo ($message_type ?? 'danger') == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
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
                                <a href="create_tag.php" class="dropdown-item">
                                    <i class="bi bi-tag"></i> Create Tag
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="exportUntagged()">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Table Card -->
        <div class="section-card mb-4">
            <div class="section-title">
                <i class="bi bi-table"></i> Asset Items
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="untaggedTable">
                <thead>
                    <tr>
                        <th>Asset Description</th>
                        <th>Status</th>
                        <th>Value</th>
                        <th>Office</th>
                        <th style="display: none;">Office ID</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($asset_items)): ?>
                        <?php foreach ($asset_items as $item): ?>
                            <?php
                            $display_description = $item['item_description'] ?: ($item['asset_description'] ?? '');
                            ?>
                            <tr data-item-id="<?php echo (int) $item['id']; ?>">
                                <td><?php echo htmlspecialchars($display_description); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($item['status']) {
                                        case 'available':
                                            $status_class = 'bg-success';
                                            $status_icon = 'bi-check-circle';
                                            break;
                                        case 'in_use':
                                            $status_class = 'bg-primary';
                                            $status_icon = 'bi-person';
                                            break;
                                        case 'maintenance':
                                            $status_class = 'bg-warning';
                                            $status_icon = 'bi-tools';
                                            break;
                                        case 'disposed':
                                            $status_class = 'bg-danger';
                                            $status_icon = 'bi-trash';
                                            break;
                                        case 'no_tag':
                                            $status_class = 'bg-danger';
                                            $status_icon = 'bi-exclamation-triangle';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                            $status_icon = 'bi-question-circle';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="bi <?php echo $status_icon; ?>"></i> <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                    </span>
                                </td>
                                <td data-order="<?php echo $item['value']; ?>"><?php echo number_format($item['value'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                                <td style="display: none;"><?php echo $item['office_id']; ?></td>
                                <td data-order="<?php echo strtotime($item['last_updated'] ?? 'now'); ?>"><small><?php echo date('M j, Y', strtotime($item['last_updated'] ?? 'now')); ?></small></td>
                                <td class="text-nowrap">
                                    <a href="create_tag.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-warning btn-action" title="Create Tag">
                                        <i class="bi bi-tag"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-outline-danger btn-action btn-delete-item"
                                            title="Delete permanently"
                                            data-item-id="<?php echo (int) $item['id']; ?>"
                                            data-item-description="<?php echo htmlspecialchars($display_description, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-property-no="<?php echo htmlspecialchars($item['property_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle fs-1"></i>
                                <p class="mt-2">No asset items requiring inventory tags found.</p>
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
    <?php require_once 'includes/footer.php'; ?>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteItemModalLabel">
                        <i class="bi bi-trash text-danger"></i> Delete Asset Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> This will permanently delete the asset item and cannot be undone.
                    </div>
                    <p class="mb-1"><strong>Description:</strong> <span id="deleteItemDescription"></span></p>
                    <p class="mb-0"><strong>Property No:</strong> <span id="deleteItemPropertyNo"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteItemBtn">
                        <i class="bi bi-trash"></i> Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        $(document).ready(function() {
            let deleteItemId = null;
            let deleteButton = null;
            const deleteModalEl = document.getElementById('deleteItemModal');
            const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

            // Initialize DataTable with simplified DOM (search stays inside)
            var table = $('#untaggedTable').DataTable({
                pageLength: 25,
                responsive: true,
                ordering: true,
                searching: true, // Keep search functionality inside DataTables
                paging: true,
                info: true,
                language: {
                    search: "Search assets:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ assets",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No asset items requiring inventory tags found."
                },
                dom: '<"row"<"col-md-3"l><"col-md-3 office-filter-container"><"col-md-6"f>><"row"<"col-12"rt>><"row"<"col-md-6"i><"col-md-6"p>>',
                initComplete: function() {
                    // Apply initial search from URL parameters
                    var initialSearch = '<?php echo htmlspecialchars($search_filter); ?>';
                    if (initialSearch !== '') {
                        table.search(initialSearch).draw();
                    }
                    
                    // Add office filter to DataTables
                    $('.office-filter-container').html(`
                        <select id="officeFilter" class="form-select form-select-sm">
                            <option value="">All Offices</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo $office['id']; ?>"><?php echo htmlspecialchars($office['office_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    `);
                    
                    // Office filter functionality
                    $('#officeFilter').on('change', function() {
                        var officeValue = $(this).val();
                        table.column(4).search(officeValue).draw(); // Search in the hidden Office ID column (index 4)
                    });
                }
            });

            $(document).on('click', '.btn-delete-item', function() {
                deleteItemId = $(this).data('item-id');
                deleteButton = $(this);
                $('#deleteItemDescription').text($(this).data('item-description') || 'N/A');
                const propertyNo = $(this).data('property-no');
                $('#deleteItemPropertyNo').text(propertyNo ? propertyNo : 'Not assigned');
                if (deleteModal) {
                    deleteModal.show();
                }
            });

            $('#confirmDeleteItemBtn').on('click', function() {
                if (!deleteItemId || !deleteButton) {
                    return;
                }

                const $confirmBtn = $(this);
                $confirmBtn.prop('disabled', true);

                $.ajax({
                    url: 'process_delete_no_tag_item.php',
                    method: 'POST',
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    data: {
                        action: 'delete',
                        item_id: deleteItemId
                    }
                }).done(function(response) {
                    if (response.success) {
                        if (deleteModal) {
                            deleteModal.hide();
                        }
                        table.row(deleteButton.closest('tr')).remove().draw(false);
                        deleteItemId = null;
                        deleteButton = null;

                        const alertHtml = '<div class="alert alert-success alert-dismissible fade show mt-2" role="alert">' +
                            '<i class="bi bi-check-circle"></i> ' + $('<div>').text(response.message).html() +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('.page-header .col-md-8').find('.alert').remove();
                        $('.page-header .col-md-8').append(alertHtml);
                    } else {
                        alert(response.message || 'Failed to delete asset item.');
                    }
                }).fail(function(xhr) {
                    let message = 'Failed to delete asset item.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert(message);
                }).always(function() {
                    $confirmBtn.prop('disabled', false);
                });
            });
        });
    </script>
</body>
</html>
