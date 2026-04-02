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

$query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if ($query === '') {
    header('Location: dashboard.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'search', 'main_user_search', "Main user searched for: " . $query);

$results = [];
$error = null;

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed.';
} else {
    try {
        // Check if query matches exact status values or office names
        $query_lower = strtolower($query);
        
        // First check if it's an exact status match
        if (in_array($query_lower, ['serviceable', 'unserviceable', 'red_tagged', 'borrowed', 'no_tag', 'no tag', 'notag', 'no-tag'])) {
            // Normalize status value to proper database format
            $status_value = $query_lower;
            if ($query_lower === 'no tag' || $query_lower === 'notag' || $query_lower === 'no-tag') {
                $status_value = 'no_tag';
            }
            
            // Exact status match - prioritize this
            $stmt = $conn->prepare("
                SELECT ai.id, ai.description, ai.property_no, ai.status, ai.value,
                       a.description as asset_description,
                       o.office_name
                FROM asset_items ai
                LEFT JOIN assets a ON ai.asset_id = a.id
                LEFT JOIN offices o ON ai.office_id = o.id
                WHERE ai.status = ?
                ORDER BY ai.last_updated DESC
                LIMIT 20
            ");
            $stmt->bind_param('s', $status_value);
        } else {
            // Check if it's an office name match
            $office_stmt = $conn->prepare("SELECT id, office_name FROM offices WHERE LOWER(office_name) LIKE ?");
            $office_like = "%{$query_lower}%";
            $office_stmt->bind_param('s', $office_like);
            $office_stmt->execute();
            $office_result = $office_stmt->get_result();
            
            if ($office_result->num_rows > 0) {
                // Office match - search for assets in that office
                $office_ids = [];
                while ($office_row = $office_result->fetch_assoc()) {
                    $office_ids[] = $office_row['id'];
                }
                $office_stmt->close();
                
                $placeholders = str_repeat('?,', count($office_ids) - 1) . '?';
                $stmt = $conn->prepare("
                    SELECT ai.id, ai.description, ai.property_no, ai.status, ai.value,
                           a.description as asset_description,
                           o.office_name
                    FROM asset_items ai
                    LEFT JOIN assets a ON ai.asset_id = a.id
                    LEFT JOIN offices o ON ai.office_id = o.id
                    WHERE ai.office_id IN ($placeholders)
                    ORDER BY ai.last_updated DESC
                    LIMIT 20
                ");
                $stmt->bind_param(str_repeat('i', count($office_ids)), ...$office_ids);
            } else {
                $office_stmt->close();
                // Simple search - only show items containing the search term
                $stmt = $conn->prepare("
                    SELECT ai.id, ai.description, ai.property_no, ai.status, ai.value,
                           a.description as asset_description,
                           o.office_name
                    FROM asset_items ai
                    LEFT JOIN assets a ON ai.asset_id = a.id
                    LEFT JOIN offices o ON ai.office_id = o.id
                    WHERE (
                        ai.description LIKE ? OR
                        a.description LIKE ? OR
                        ai.property_no LIKE ? OR
                        ai.status LIKE ? OR
                        o.office_name LIKE ?
                    )
                    ORDER BY 
                        CASE 
                            WHEN ai.description LIKE ? THEN 1
                            ELSE 2
                        END,
                        ai.last_updated DESC
                    LIMIT 20
                ");
                $searchQuery = "%{$query}%";
                $stmt->bind_param('ssssss',
                    $searchQuery, // WHERE ai.description LIKE
                    $searchQuery, // WHERE a.description LIKE
                    $searchQuery, // WHERE ai.property_no LIKE
                    $searchQuery, // WHERE ai.status LIKE
                    $searchQuery, // WHERE o.office_name LIKE
                    $query // ORDER BY CASE ai.description LIKE (exact)
                );
            }
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = 'Search error: ' . $e->getMessage();
        error_log('Main User Search Error: ' . $e->getMessage());
    }
}

if (count($results) === 1) {
    header('Location: view_asset_item.php?id=' . (int)$results[0]['id']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php $page_title = 'Search Results'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-search me-2"></i>Search Results
                        </h1>
                        <p class="text-muted mb-0">
                            Results for: <strong><?php echo htmlspecialchars($query); ?></strong>
                        </p>
                        <?php if ($error): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small><?php echo htmlspecialchars($error); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a class="btn btn-outline-primary btn-sm" href="dashboard.php">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <?php if (!$error && !empty($results)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Property No</th>
                                    <th>Description</th>
                                    <th>Office</th>
                                    <th>Status</th>
                                    <th class="text-end">Value</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['property_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['description'] ?? ''); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($row['asset_description'] ?? ''); ?></div>
                                        </td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($row['office_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                                        <td class="text-end"><?php echo number_format((float)($row['value'] ?? 0), 2); ?></td>
                                        <td class="text-end">
                                            <a href="view_asset_item.php?id=<?php echo (int)($row['id'] ?? 0); ?>" class="btn btn-outline-info btn-sm">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">No results found for "<?php echo htmlspecialchars($query); ?>"</p>
                        <a href="dashboard.php" class="btn btn-outline-primary mt-3">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>
