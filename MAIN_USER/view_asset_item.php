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

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($item_id <= 0) {
    header('Location: asset_items.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_view_asset_item', 'Main user viewed asset item ID: ' . $item_id);

$item = null;
$related_items = [];

if (!$conn || $conn->connect_error) {
    header('Location: asset_items.php');
    exit();
}

try {
    $item_sql = "SELECT ai.*, 
                       a.description as asset_description, a.unit, a.quantity as asset_quantity, a.unit_cost,
                       ac.category_name, ac.category_code,
                       o.office_name
                FROM asset_items ai
                LEFT JOIN assets a ON ai.asset_id = a.id
                LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id
                LEFT JOIN offices o ON ai.office_id = o.id
                WHERE ai.id = ?";

    $stmt = $conn->prepare($item_sql);
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
    }
    $stmt->close();

    if (!$item) {
        header('Location: asset_items.php');
        exit();
    }

    $related_sql = "SELECT ai.id, ai.description, ai.status, ai.value, ai.property_no
                    FROM asset_items ai
                    WHERE ai.asset_id = ?
                    ORDER BY ai.id DESC
                    LIMIT 5";
    $stmt = $conn->prepare($related_sql);
    $stmt->bind_param('i', $item['asset_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $related_items[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('Main User View Asset Item Error: ' . $e->getMessage());
    header('Location: asset_items.php');
    exit();
}

$page_title = 'View Asset Item';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Item Details - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-eye me-2"></i>Asset Item Details
                        </h1>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                        <p class="text-muted mb-0"><small>Office: <?php echo htmlspecialchars($item['office_name'] ?? ''); ?></small></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="asset_items.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Asset Items
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="section-card mb-4">
                        <div class="section-title">
                            <i class="bi bi-info-circle"></i> Item Information
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Asset:</strong> <?php echo htmlspecialchars($item['asset_description'] ?? ''); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars(($item['category_code'] ?? '') . ' - ' . ($item['category_name'] ?? '')); ?></p>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($item['status'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Property No:</strong> <?php echo htmlspecialchars(($item['property_no'] ?? '') !== '' ? (string)$item['property_no'] : (($item['property_number'] ?? '') !== '' ? (string)$item['property_number'] : 'N/A')); ?></p>
                                <p><strong>Value:</strong> <?php echo number_format((float)($item['value'] ?? 0), 2); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo !empty($item['last_updated']) ? htmlspecialchars(date('M j, Y', strtotime((string)$item['last_updated']))) : 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="section-card">
                        <div class="section-title">
                            <i class="bi bi-link-45deg"></i> Related Items
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Property No</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th class="text-end">Value</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($related_items)): ?>
                                        <?php foreach ($related_items as $r): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(($r['property_no'] ?? '') !== '' ? (string)$r['property_no'] : 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                                <td class="text-end"><?php echo number_format((float)($r['value'] ?? 0), 2); ?></td>
                                                <td class="text-end">
                                                    <a href="view_asset_item.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-outline-info btn-sm">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No related items found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="section-card mb-4">
                        <div class="section-title">
                            <i class="bi bi-qr-code"></i> QR Code
                        </div>
                        <div class="text-center">
                            <div class="qr-code">
                                <?php if (!empty($item['qr_code'])): ?>
                                    <img src="../uploads/qr_codes/<?php echo htmlspecialchars($item['qr_code']); ?>" 
                                         alt="QR Code" 
                                         class="img-fluid rounded"
                                         style="max-width: 150px; max-height: 150px;">
                                <?php else: ?>
                                    <i class="bi bi-qr-code-scan fs-1 text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <p class="mt-2 mb-0 text-muted">Property No: <?php echo ($item['property_no'] ?? '') !== '' ? htmlspecialchars((string)$item['property_no']) : 'Not assigned'; ?></p>
                        </div>
                    </div>

                    <div class="section-card">
                        <div class="section-title">
                            <i class="bi bi-image"></i> Asset Image
                        </div>
                        <div class="text-center">
                            <div class="asset-image-container mb-3">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="../uploads/asset_images/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="Asset Image" 
                                         class="img-fluid rounded shadow-sm"
                                         style="max-height: 300px; width: auto; object-fit: cover;">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                        <div class="mt-2">
                                            <small class="text-muted">No image available</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
