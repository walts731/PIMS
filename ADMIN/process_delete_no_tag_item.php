<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';
require_once '../includes/asset_specific_manager.php';

checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'includes/check_permissions.php';
adminRequirePermission('assets.delete', 'can_delete', 'assets.php');

$is_ajax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

function sendDeleteResponse(bool $success, string $message, int $status_code = 200): void
{
    global $is_ajax;

    if ($is_ajax) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $success ? 'success' : 'danger';
    header('Location: no_inventory_tag.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'delete') {
    sendDeleteResponse(false, 'Invalid request.', 400);
}

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
if ($item_id <= 0) {
    sendDeleteResponse(false, 'Invalid asset item ID.', 400);
}

$item_stmt = $conn->prepare(
    "SELECT ai.id, ai.asset_id, ai.description, ai.status, ai.property_no, a.description AS asset_description
     FROM asset_items ai
     LEFT JOIN assets a ON ai.asset_id = a.id
     WHERE ai.id = ?"
);
$item_stmt->bind_param('i', $item_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
$item = $item_result->fetch_assoc();
$item_stmt->close();

if (!$item) {
    sendDeleteResponse(false, 'Asset item not found.', 404);
}

if ($item['status'] !== 'no_tag') {
    sendDeleteResponse(false, 'Only untagged items can be deleted from this page.', 403);
}

$tables_by_item_id = [
    'peripherals',
    'asset_desktop_computers',
    'asset_computers',
    'asset_furniture',
    'asset_machinery',
    'asset_office_equipment',
    'asset_software',
    'asset_land_info',
    'asset_land',
    'asset_vehicles',
];

try {
    $conn->begin_transaction();

    foreach ($tables_by_item_id as $table) {
        $check = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
        if (!$check || $check->num_rows === 0) {
            continue;
        }

        $delete_stmt = $conn->prepare("DELETE FROM `$table` WHERE asset_item_id = ?");
        if ($delete_stmt) {
            $delete_stmt->bind_param('i', $item_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
    }

    $delete_item_stmt = $conn->prepare("DELETE FROM asset_items WHERE id = ? AND status = 'no_tag'");
    $delete_item_stmt->bind_param('i', $item_id);
    if (!$delete_item_stmt->execute() || $delete_item_stmt->affected_rows === 0) {
        throw new Exception('Failed to delete asset item.');
    }
    $delete_item_stmt->close();

    $asset_id = (int) $item['asset_id'];
    if ($asset_id > 0) {
        $count_stmt = $conn->prepare('SELECT COUNT(*) AS item_count FROM asset_items WHERE asset_id = ?');
        $count_stmt->bind_param('i', $asset_id);
        $count_stmt->execute();
        $count_row = $count_stmt->get_result()->fetch_assoc();
        $count_stmt->close();

        if ((int) ($count_row['item_count'] ?? 0) === 0) {
            $category_stmt = $conn->prepare(
                'SELECT ac.category_code
                 FROM assets a
                 LEFT JOIN asset_categories ac ON a.asset_categories_id = ac.id
                 WHERE a.id = ?'
            );
            $category_stmt->bind_param('i', $asset_id);
            $category_stmt->execute();
            $category_row = $category_stmt->get_result()->fetch_assoc();
            $category_stmt->close();

            if (!empty($category_row['category_code'])) {
                $assetManager = new AssetSpecificManager($conn);
                $assetManager->deleteSpecificAssetData($asset_id, $category_row['category_code']);
            }

            $delete_asset_stmt = $conn->prepare('DELETE FROM assets WHERE id = ?');
            $delete_asset_stmt->bind_param('i', $asset_id);
            $delete_asset_stmt->execute();
            $delete_asset_stmt->close();
        }
    }

    $conn->commit();

    $label = $item['description'] ?: ($item['asset_description'] ?? 'Asset item');
    logSystemAction(
        $_SESSION['user_id'],
        'delete',
        'no_inventory_tag',
        "Permanently deleted untagged asset item: {$label} (ID: {$item_id})"
    );

    sendDeleteResponse(true, 'Asset item permanently deleted.');
} catch (Exception $e) {
    $conn->rollback();
    error_log('Error deleting no-tag asset item: ' . $e->getMessage());
    sendDeleteResponse(false, 'Failed to delete asset item: ' . $e->getMessage(), 500);
}
