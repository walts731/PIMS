<?php
session_start();
require_once '../config.php';
require_once 'includes/cloud_api.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';
$state = $_GET['state'] ?? '';

// If provider is not in GET parameters, try to get it from state
if (empty($provider) && !empty($state)) {
    $stateData = json_decode(base64_decode($state), true);
    if ($stateData && isset($stateData['provider'])) {
        $provider = $stateData['provider'];
    }
}

// If still no provider, default to google_drive (most common case)
if (empty($provider)) {
    $provider = 'google_drive';
}

if ($error) {
    $_SESSION['cloud_error'] = "OAuth error: " . htmlspecialchars($error);
    header('Location: cloud_config.php');
    exit();
}

if (empty($code)) {
    $_SESSION['cloud_error'] = "Missing authorization code from OAuth provider";
    header('Location: cloud_config.php');
    exit();
}

try {
    $result = CloudOAuthHandler::handleCallback($provider, $code, $state);
    
    if ($result['success']) {
        $_SESSION['cloud_success'] = "Successfully connected to " . ucwords(str_replace('_', ' ', $provider));
        
        // If there's a pending backup upload, continue it
        if ($state) {
            $stateData = json_decode(base64_decode($state), true);
            if ($stateData && isset($stateData['local_file'])) {
                $_SESSION['pending_cloud_upload'] = [
                    'provider' => $provider,
                    'local_file' => $stateData['local_file'],
                    'remote_file' => $stateData['remote_file']
                ];
                header('Location: backup.php');
                exit();
            }
        }
    }
    
    header('Location: cloud_config.php');
    
} catch (Exception $e) {
    $_SESSION['cloud_error'] = "Connection failed: " . $e->getMessage();
    header('Location: cloud_config.php');
}
?>
