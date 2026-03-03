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

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($item_id <= 0) {
    header('Location: assets.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'user_view_asset_item', 'User accessed asset item details for item ID: ' . $item_id);

$user_office_id = null;
$user_office_name = null;
$item = null;
$other_items = [];

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

    $item_sql = "SELECT ai.*,
                       a.description as asset_description, a.unit, a.quantity as asset_quantity, a.unit_cost,
                       ac.category_name, ac.category_code,
                       o.office_name
                FROM asset_items ai
                LEFT JOIN assets a ON ai.asset_id = a.id
                LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id
                LEFT JOIN offices o ON ai.office_id = o.id
                WHERE ai.id = ? AND ai.office_id = ?";

    $item_stmt = $conn->prepare($item_sql);
    $item_stmt->bind_param('ii', $item_id, $user_office_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    if ($item_row = $item_result->fetch_assoc()) {
        $item = $item_row;
    }
    $item_stmt->close();

    if (!$item) {
        header('Location: assets.php');
        exit();
    }

    $asset_id = (int)($item['asset_id'] ?? 0);

    if ($asset_id > 0) {
        $other_items_sql = "SELECT id, description, status, property_no
                            FROM asset_items
                            WHERE asset_id = ? AND office_id = ? AND id != ?
                            ORDER BY id";
        $other_items_stmt = $conn->prepare($other_items_sql);
        $other_items_stmt->bind_param('iii', $asset_id, $user_office_id, $item_id);
        $other_items_stmt->execute();
        $other_items_result = $other_items_stmt->get_result();
        while ($other_row = $other_items_result->fetch_assoc()) {
            $other_items[] = $other_row;
        }
        $other_items_stmt->close();
    }
} catch (Exception $e) {
    error_log('User View Asset Item Error: ' . $e->getMessage());
    header('Location: assets.php');
    exit();
}

function formatStatusUser($status) {
    $status_map = [
        'serviceable' => ['Serviceable', 'status-serviceable'],
        'unserviceable' => ['Unserviceable', 'status-unserviceable'],
        'red_tagged' => ['Red Tagged', 'status-red-tagged'],
        'borrowed' => ['Borrowed', 'status-borrowed'],
        'no_tag' => ['No Tag', 'status-no-tag']
    ];
    return $status_map[$status] ?? [$status ?: 'N/A', 'status-default'];
}

$status_display = formatStatusUser($item['status'] ?? '');
$asset_id_for_nav = (int)($item['asset_id'] ?? 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Item Details - <?php echo htmlspecialchars($item['description'] ?? ''); ?> | PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
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

        .detail-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .detail-section {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .btn-back {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 27, 169, 0.3);
            color: white;
        }

        .related-item {
            transition: var(--transition);
        }

        .related-item:hover {
            border-color: #191BA9 !important;
            background-color: rgba(25, 27, 169, 0.05);
        }

        .qr-code {
            width: 150px;
            height: 150px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .asset-image-container {
            position: relative;
            display: inline-block;
        }

        .asset-image-container img {
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius-md);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .asset-image-container img:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .no-image-placeholder {
            border: 2px dashed #dee2e6;
            border-radius: var(--border-radius-md);
            padding: 20px;
            background-color: #f8f9fa;
            display: inline-block;
        }

        .no-image-placeholder svg {
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php $page_title = 'Asset Item Details - ' . htmlspecialchars($item['description'] ?? ''); ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2"><i class="bi bi-box"></i> Asset Item Details</h1>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="view_asset_items.php?asset_id=<?php echo $asset_id_for_nav; ?>" class="btn btn-back me-2">
                            <i class="bi bi-arrow-left"></i> Back to Items
                        </a>
                        <a href="assets.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-seam"></i> Assets
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="detail-card">
                        <div class="detail-section">
                            <h5 class="mb-3"><i class="bi bi-info-circle"></i> Item Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="detail-label">Property No</div>
                                        <div class="detail-value"><?php echo !empty($item['property_no']) ? htmlspecialchars($item['property_no']) : '<span class="text-muted">Not assigned</span>'; ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Description</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Status</div>
                                        <div class="detail-value">
                                            <span class="status-badge <?php echo htmlspecialchars($status_display[1]); ?>">
                                                <?php echo htmlspecialchars($status_display[0]); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="detail-label">Value</div>
                                        <div class="detail-value text-value">₱0.00</div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Acquisition Date</div>
                                        <div class="detail-value">
                                            <?php echo !empty($item['acquisition_date']) ? htmlspecialchars(date('F j, Y', strtotime($item['acquisition_date']))) : '<span class="text-muted">N/A</span>'; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Last Updated</div>
                                        <div class="detail-value">
                                            <?php echo !empty($item['last_updated']) ? htmlspecialchars(date('F j, Y g:i A', strtotime($item['last_updated']))) : '<span class="text-muted">N/A</span>'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h5 class="mb-3"><i class="bi bi-archive"></i> Asset Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="detail-label">Asset Description</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($item['asset_description'] ?? ''); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Category</div>
                                        <div class="detail-value"><?php echo htmlspecialchars(($item['category_code'] ?? '') . ' - ' . ($item['category_name'] ?? '')); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="detail-label">Unit</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($item['unit'] ?? ''); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="detail-label">Unit Cost</div>
                                        <div class="detail-value">₱0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Location</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="detail-label">Office</div>
                                        <div class="detail-value"><?php echo !empty($item['office_name']) ? htmlspecialchars($item['office_name']) : htmlspecialchars($user_office_name ?? ''); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="detail-card text-center">
                        <h5 class="mb-3"><i class="bi bi-image"></i> Asset Image</h5>
                        <div class="asset-image-container mb-3">
                            <?php if (!empty($item['image'])): ?>
                                <img src="../uploads/asset_images/<?php echo htmlspecialchars($item['image']); ?>"
                                     alt="Asset Image"
                                     class="img-fluid rounded shadow-sm"
                                     style="max-height: 300px; width: auto; object-fit: cover;"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0xMjUgMTIwSDE3NVYxNzVIMTI1VjEyMFoiIGZpbGw9IiNEMUQ1REIiLz4KPHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDIwQzEwIDIyLjIwOTEgMTEuNzkwOSAyNCAxNCAyNEgyNkMyOC4yMDkxIDI0IDMwIDIyLjIwOTEgMzAgMjBWMzBIMTBWMjBaTTEwIDEwQzEwIDEyLjIwOTEgMTEuNzkwOSAxNCAxNCAxNEgyNkMyOC4yMDkxIDE0IDMwIDEyLjIwOTEgMzAgMTBWMTBIMTBaIiBmaWxsPSIjRDRERDREIi8+Cjwvc3ZnPgo8L3N2Zz4K';">
                                <div class="mt-2">
                                    <small class="text-muted">Image uploaded</small>
                                </div>
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <svg width="150" height="150" viewBox="0 0 150 150" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="150" height="150" fill="#F5F5F5"/>
                                        <path d="M62.5 60H87.5V87.5H62.5V60Z" fill="#D1D5DB"/>
                                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" x="55" y="55">
                                            <path d="M10 20C10 22.2091 11.7909 24 14 24H26C28.2091 24 30 22.2091 30 20V30H10V20ZM10 10C10 12.2091 11.7909 14 14 14H26C28.2091 14 30 12.2091 30 10V10H10V10Z" fill="#D4D4D4"/>
                                        </svg>
                                    </svg>
                                    <div class="mt-2">
                                        <small class="text-muted">No image available</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-card text-center">
                        <h5 class="mb-3"><i class="bi bi-qr-code"></i> QR Code</h5>
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
                        <p class="mt-2 mb-0 text-muted">Property No: <?php echo !empty($item['property_no']) ? htmlspecialchars($item['property_no']) : 'Not assigned'; ?></p>
                    </div>

                    <?php if (!empty($other_items)): ?>
                        <div class="detail-card">
                            <h5 class="mb-3"><i class="bi bi-collection"></i> Other Items</h5>
                            <?php foreach ($other_items as $other_item): ?>
                                <?php $other_status = formatStatusUser($other_item['status'] ?? ''); ?>
                                <a class="d-block text-decoration-none related-item" style="border: 1px solid #e9ecef; border-radius: var(--border-radius-md); padding: 0.75rem; margin-bottom: 0.5rem;" href="view_asset_item.php?id=<?php echo (int)$other_item['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($other_item['description'] ?? ''); ?></div>
                                            <small class="text-muted">Property No: <?php echo !empty($other_item['property_no']) ? htmlspecialchars($other_item['property_no']) : 'Not assigned'; ?></small>
                                        </div>
                                        <span class="status-badge <?php echo htmlspecialchars($other_status[1]); ?> small">
                                            <?php echo htmlspecialchars($other_status[0]); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>

    <style>
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: #212529;
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-serviceable {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: 1px solid #28a745;
        }
        .status-unserviceable {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: 1px solid #dc3545;
        }
        .status-red-tagged {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
            color: white;
            border: 1px solid #fd7e14;
        }
        .status-borrowed {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            border: 1px solid #e0a800;
        }
        .status-no-tag {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
            border: 1px solid #6c757d;
        }
        .status-default {
            background: linear-gradient(135deg, #e9ecef 0%, #ced4da 100%);
            color: #495057;
            border: 1px solid #e9ecef;
        }
        .text-value {
            font-weight: 700;
            color: #191BA9;
            font-size: 1.2rem;
        }
    </style>
</body>
</html>
