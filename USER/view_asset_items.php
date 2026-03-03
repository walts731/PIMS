<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit();
}

$asset_id = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
if ($asset_id <= 0) {
    header('Location: assets.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'user_view_asset_items', 'User accessed asset items list for asset ID: ' . $asset_id);

$user_office_id = null;
$user_office_name = null;
$asset = null;
$items = [];

if (!$conn || $conn->connect_error) {
    header('Location: assets.php');
    exit();
}

try {
    $user_office_value = null;

    $stmt = $conn->prepare("SELECT office FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_office_value = $row['office'] ?? null;
    }
    $stmt->close();

    if ($user_office_value !== null && $user_office_value !== '') {
        if (ctype_digit((string)$user_office_value)) {
            $user_office_id = (int)$user_office_value;
            $stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
            $stmt->bind_param('i', $user_office_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $user_office_name = $result->fetch_assoc()['office_name'];
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT id, office_name FROM offices WHERE office_name = ? OR office_code = ? LIMIT 1");
            $stmt->bind_param('ss', $user_office_value, $user_office_value);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_office_id = (int)$row['id'];
                $user_office_name = $row['office_name'];
            }
            $stmt->close();
        }
    }

    if (!$user_office_id) {
        header('Location: assets.php');
        exit();
    }

    $asset_sql = "SELECT a.*, ac.category_name, ac.category_code, o.office_name
                  FROM assets a
                  LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id
                  LEFT JOIN offices o ON a.office_id = o.id
                  WHERE a.id = ? AND a.office_id = ?";
    $asset_stmt = $conn->prepare($asset_sql);
    $asset_stmt->bind_param('ii', $asset_id, $user_office_id);
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

    $items_sql = "SELECT ai.*
                  FROM asset_items ai
                  WHERE ai.asset_id = ? AND ai.office_id = ?
                  ORDER BY ai.id";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param('ii', $asset_id, $user_office_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    while ($item_row = $items_result->fetch_assoc()) {
        $items[] = $item_row;
    }
    $items_stmt->close();
} catch (Exception $e) {
    error_log('User View Asset Items Error: ' . $e->getMessage());
    header('Location: assets.php');
    exit();
}

$total_items = count($items);
$serviceable_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'serviceable'));
$unserviceable_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'unserviceable'));
$redtagged_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'red_tagged'));
$borrowed_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'borrowed'));
$notag_items = count(array_filter($items, fn($item) => ($item['status'] ?? '') === 'no_tag'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Items - <?php echo htmlspecialchars($asset['description'] ?? ''); ?> | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Asset Items - ' . htmlspecialchars($asset['description'] ?? ''); ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="page-header" style="background: white; border-radius: var(--border-radius-xl); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow); border-left: 4px solid var(--primary-color);">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2"><i class="bi bi-box"></i> Asset Items</h1>
                        <p class="text-muted mb-0">Individual items for: <?php echo htmlspecialchars($asset['description'] ?? ''); ?></p>
                        <p class="text-muted mb-0"><small>Office: <?php echo htmlspecialchars($user_office_name ?? ($asset['office_name'] ?? '')); ?></small></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="assets.php" class="btn btn-outline-primary btn-sm me-2">
                            <i class="bi bi-arrow-left"></i> Back to Assets
                        </a>
                        <button class="btn btn-outline-success btn-sm" onclick="exportAssetItems()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <div class="section-card mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Asset Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Category:</strong> <?php echo htmlspecialchars(($asset['category_code'] ?? '') . ' - ' . ($asset['category_name'] ?? '')); ?></p>
                                <p><strong>Unit:</strong> <?php echo htmlspecialchars($asset['unit'] ?? ''); ?></p>
                                <p><strong>Office:</strong> <?php echo htmlspecialchars($asset['office_name'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Quantity:</strong> <?php echo htmlspecialchars((string)($asset['quantity'] ?? '')); ?></p>
                                <p><strong>Unit Cost:</strong> ₱0.00</p>
                                <p><strong>Total Value:</strong> ₱0.00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card text-center" style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; border-radius: var(--border-radius-lg); padding: 1.5rem; height: 100%;">
                            <div class="stats-number" style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                                <?php echo $total_items; ?>
                            </div>
                            <div class="stats-label" style="font-size: 0.9rem; opacity: 0.9;">
                                <i class="bi bi-box"></i> Total Items
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; border-radius: var(--border-radius-lg); padding: 1.5rem; text-align: center;">
                        <div class="stats-number" style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $serviceable_items; ?></div>
                        <div class="stats-label" style="font-size: 0.9rem; opacity: 0.9;"><i class="bi bi-check-circle"></i> Serviceable</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; border-radius: var(--border-radius-lg); padding: 1.5rem; text-align: center;">
                        <div class="stats-number" style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $unserviceable_items; ?></div>
                        <div class="stats-label" style="font-size: 0.9rem; opacity: 0.9;"><i class="bi bi-x-circle"></i> Unserviceable</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; border-radius: var(--border-radius-lg); padding: 1.5rem; text-align: center;">
                        <div class="stats-number" style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $redtagged_items; ?></div>
                        <div class="stats-label" style="font-size: 0.9rem; opacity: 0.9;"><i class="bi bi-exclamation-triangle"></i> Red-Tagged</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; border-radius: var(--border-radius-lg); padding: 1.5rem; text-align: center;">
                        <div class="stats-number" style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $notag_items; ?></div>
                        <div class="stats-label" style="font-size: 0.9rem; opacity: 0.9;"><i class="bi bi-dash-circle"></i> No Tag</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                    <div class="stats-card" style="background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%); color: white; border-radius: var(--border-radius-lg); padding: 1.5rem; text-align: center;">
                        <div class="stats-number" style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $borrowed_items; ?></div>
                        <div class="stats-label" style="font-size: 0.9rem; opacity: 0.9;"><i class="bi bi-arrow-left-right"></i> Borrowed</div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Individual Asset Items</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <select class="form-select form-select-sm" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="Serviceable">Serviceable</option>
                                    <option value="Unserviceable">Unserviceable</option>
                                    <option value="Red-Tagged">Red-Tagged</option>
                                    <option value="Borrowed">Borrowed</option>
                                    <option value="No Tag">No Tag</option>
                                </select>
                            </div>
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
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(($item['property_no'] ?? '') !== '' ? $item['property_no'] : 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $status = $item['status'] ?? '';
                                            $status_class = '';
                                            $display_status = '';
                                            switch($status) {
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
                                                default:
                                                    $display_status = $status !== '' ? ucfirst($status) : 'N/A';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo htmlspecialchars($status_class); ?>">
                                                <?php echo htmlspecialchars($display_status); ?>
                                            </span>
                                        </td>
                                        <td class="text-value">₱0.00</td>
                                        <td>
                                            <?php
                                            $acq = $item['acquisition_date'] ?? null;
                                            echo $acq ? htmlspecialchars(date('M j, Y', strtotime($acq))) : 'N/A';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $lu = $item['last_updated'] ?? null;
                                            echo $lu ? htmlspecialchars(date('M j, Y', strtotime($lu))) : 'N/A';
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="view_asset_item.php?id=<?php echo (int)($item['id'] ?? 0); ?>" class="btn btn-outline-info btn-action" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-3 text-muted">No individual items found for this asset.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
        let assetItemsTable;

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('assetItemsTable')) {
                assetItemsTable = $('#assetItemsTable').DataTable({
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    order: [[3, 'desc']],
                    columnDefs: [
                        {
                            targets: 2,
                            orderable: true,
                            render: function(data, type, row) {
                                if (type === 'display') {
                                    return data;
                                }
                                return data.replace(/<[^>]*>/g, '').trim();
                            }
                        },
                        {
                            targets: 3,
                            orderable: true,
                            render: function(data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    return parseFloat(data.replace(/[^0-9.-]+/g, ''));
                                }
                                return data;
                            }
                        },
                        {
                            targets: 4,
                            orderable: true,
                            render: function(data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    return new Date(data).getTime();
                                }
                                return data;
                            }
                        },
                        {
                            targets: 5,
                            orderable: true,
                            render: function(data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    return new Date(data).getTime();
                                }
                                return data;
                            }
                        },
                        {
                            targets: -1,
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

                $('#statusFilter').on('change', function() {
                    const statusValue = this.value;
                    if (statusValue) {
                        assetItemsTable.column(2).search(statusValue).draw();
                    } else {
                        assetItemsTable.column(2).search('').draw();
                    }
                });
            }
        });

        function exportAssetItems() {
            if (!assetItemsTable) return;

            const data = assetItemsTable.data().toArray();
            let csv = 'Property No,Description,Status,Value,Acquisition Date,Last Updated\n';

            data.forEach(row => {
                const rowData = [
                    row[0],
                    row[1],
                    row[2].replace(/<[^>]*>/g, '').trim(),
                    row[3].replace(/[^0-9.-]+/g, ''),
                    row[4],
                    row[5]
                ];
                csv += rowData.map(cell => `"${String(cell).trim()}"`).join(',') + '\n';
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

    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-serviceable {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: 1px solid #20c997;
        }
        .status-unserviceable {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: 1px solid #0056b3;
        }
        .status-red-tagged {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: 1px solid #c82333;
        }
        .status-borrowed {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            border: 1px solid #e0a800;
        }
        .status-notag {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
            border: 1px solid #545b62;
        }
        .text-value {
            font-weight: 600;
            color: #191BA9;
        }
    </style>
</body>
</html>
