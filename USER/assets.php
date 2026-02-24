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

logSystemAction($_SESSION['user_id'], 'access', 'user_assets', 'User accessed assets list');

$user_office_id = null;
$user_office_name = null;
$assets = [];
$error = null;

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
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
            $error = 'Office not assigned to your account. Please contact administrator.';
        } else {
            $sql = "SELECT 
                        a.id,
                        a.description,
                        a.unit,
                        a.quantity,
                        a.unit_cost,
                        a.updated_at,
                        ac.category_name,
                        ac.category_code,
                        COUNT(ai.id) AS item_count,
                        COALESCE(SUM(ai.value), 0) AS items_total_value
                    FROM assets a
                    LEFT JOIN asset_categories ac ON ac.id = a.asset_categories_id
                    LEFT JOIN asset_items ai ON ai.asset_id = a.id AND ai.office_id = a.office_id
                    WHERE a.office_id = ?
                    GROUP BY a.id
                    ORDER BY a.updated_at DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_office_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $error = 'Error loading assets: ' . $e->getMessage();
        error_log('User Assets Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Assets'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-box-seam me-2"></i>Assets
                        </h1>
                        <p class="text-muted mb-0">
                            Showing asset items for your office<?php echo $user_office_name ? ': ' . htmlspecialchars($user_office_name) : ''; ?>.
                        </p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a class="btn btn-outline-primary btn-sm" href="assets.php">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </a>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Property No</th>
                                <th>Description</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th class="text-end">Value</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$error && !empty($assets)): ?>
                                <?php foreach ($assets as $row): ?>
                                    <tr>
                                        <td class="ps-3"><?php echo htmlspecialchars(($row['property_no'] ?? '') !== '' ? (string)$row['property_no'] : (($row['property_number'] ?? '') !== '' ? (string)$row['property_number'] : 'N/A')); ?></td>
                                        <td class="ps-3">
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['description'] ?? ''); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($row['category_name'] ?? ''); ?></div>
                                        </td>
                                        <td class="ps-3"><?php echo htmlspecialchars($row['office_name'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $status = $row['status'] ?? '';
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
                                                    $status_class = 'status-no-tag';
                                                    $display_status = 'No Tag';
                                                    break;
                                                default:
                                                    $status_class = 'status-unknown';
                                                    $display_status = ucfirst(str_replace('_', ' ', $status));
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $display_status; ?>
                                            </span>
                                        </td>
                                        <td class="text-end ps-3"><?php echo number_format((float)($row['value'] ?? 0), 2); ?></td>
                                        <td class="text-muted small ps-3"><?php echo htmlspecialchars($row['updated_at'] ?? ''); ?></td>
                                        <td class="ps-3">
                                            <a href="view_asset_items.php?asset_id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i> View Items
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No assets found.</td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
