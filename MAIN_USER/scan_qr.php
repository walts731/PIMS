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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_scan_qr', 'Main user opened QR scanner');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #qr-reader video {
            border-radius: var(--border-radius-lg);
        }
        .scanner-container {
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php $page_title = 'QR Scanner'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-qr-code-scan me-2"></i>QR Scanner
                        </h1>
                        <p class="text-muted mb-0">Scan QR codes to quickly locate asset items.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="text-center mb-4">
                    <button id="startButton" class="btn btn-primary">
                        <i class="bi bi-camera-fill me-2"></i>Start Camera
                    </button>
                    <button id="stopButton" class="btn btn-danger d-none">
                        <i class="bi bi-stop-circle me-2"></i>Stop Camera
                    </button>
                </div>

                <div id="qr-reader" class="scanner-container"></div>

                <div id="result-container" class="mt-4" style="display: none;">
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle me-2"></i>QR Code Detected</h5>
                        <p id="result-text" class="mb-0"></p>
                        <div id="result-actions" class="mt-3"></div>
                    </div>
                </div>

                <div id="error-container" class="mt-4" style="display: none;">
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle me-2"></i>Error</h5>
                        <p id="error-text" class="mb-0"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        let html5QrCode = null;
        const startButton = document.getElementById('startButton');
        const stopButton = document.getElementById('stopButton');
        const resultContainer = document.getElementById('result-container');
        const errorContainer = document.getElementById('error-container');
        const resultText = document.getElementById('result-text');
        const errorText = document.getElementById('error-text');
        const resultActions = document.getElementById('result-actions');

        startButton.addEventListener('click', startScanner);
        stopButton.addEventListener('click', stopScanner);

        function startScanner() {
            html5QrCode = new Html5Qrcode("qr-reader");
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    const cameraId = devices[0].id;
                    html5QrCode.start(
                        cameraId,
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 }
                        },
                        (decodedText, decodedResult) => {
                            onScanSuccess(decodedText);
                        },
                        (errorMessage) => {
                            // Ignore scan errors
                        }
                    ).catch(err => {
                        showError('Unable to start camera: ' + err);
                    });
                    startButton.classList.add('d-none');
                    stopButton.classList.remove('d-none');
                } else {
                    showError('No camera found on this device.');
                }
            }).catch(err => {
                showError('Unable to access camera: ' + err);
            });
        }

        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    html5QrCode = null;
                }).catch(err => {
                    console.error('Failed to stop scanner:', err);
                });
            }
            startButton.classList.remove('d-none');
            stopButton.classList.add('d-none');
        }

        function onScanSuccess(decodedText) {
            stopScanner();
            resultText.textContent = decodedText;
            resultContainer.style.display = 'block';
            errorContainer.style.display = 'none';

            // Process QR code like admin version - direct redirect
            try {
                // Extract asset item ID from QR code
                const assetItemId = decodedText.trim();
                
                // Validate that it's a numeric ID
                if (!/^\d+$/.test(assetItemId)) {
                    throw new Error('Invalid QR code format');
                }
                
                console.log('Asset Item ID:', assetItemId);
                
                // Show loading state
                resultActions.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Processing QR code...</span>
                    </div>
                `;
                
                // Redirect to view_asset_item.php after a short delay
                setTimeout(() => {
                    window.location.href = `view_asset_item.php?id=${assetItemId}`;
                }, 1500);
                
            } catch (error) {
                console.error('Error processing QR code:', error);
                resultActions.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Invalid QR code format. Please scan a valid asset QR code.
                        <div class="mt-2">
                            <a href="search_handler.php?q=${encodeURIComponent(decodedText)}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-search me-2"></i>Search for this code
                            </a>
                        </div>
                    </div>
                `;
            }
        }

        function showError(message) {
            errorText.textContent = message;
            errorContainer.style.display = 'block';
            resultContainer.style.display = 'none';
        }
    </script>
</body>
</html>
