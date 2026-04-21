<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SESSION['role'] !== 'system_admin') {
    http_response_code(403);
    exit('Unauthorized');
}

$backup_id = intval($_GET['id'] ?? 0);
$file_type  = $_GET['type'] ?? ''; // 'database' or 'files'

if (!$backup_id || !in_array($file_type, ['database', 'files'])) {
    http_response_code(400);
    exit('Invalid request');
}

$stmt = $conn->prepare("SELECT * FROM backups WHERE id = ?");
$stmt->bind_param("i", $backup_id);
$stmt->execute();
$backup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$backup) {
    http_response_code(404);
    exit('Backup not found');
}

if ($file_type === 'database') {
    $file_path = $backup['file_path'] . '_database.sql';
    $mime      = 'application/sql';
    $download_name = basename($backup['file_path']) . '_database.sql';
} else {
    $file_path = $backup['file_path'] . '_files.zip';
    $mime      = 'application/zip';
    $download_name = basename($backup['file_path']) . '_files.zip';
}

// Resolve absolute path safely
$real_path = realpath($file_path);
$allowed_dir = realpath('../backups');

if (!$real_path || !$allowed_dir || strpos($real_path, $allowed_dir) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

if (!file_exists($real_path)) {
    http_response_code(404);
    exit('File not found');
}

logSystemAction($_SESSION['user_id'], 'backup_downloaded', 'backup_system',
    "Downloaded backup: {$backup['name']} ({$file_type})");

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($real_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');
ob_end_clean();
readfile($real_path);
exit;
