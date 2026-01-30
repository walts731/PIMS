<?php
session_start();

require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

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

$page_title = 'QR Scanner';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- HTML5 QR Code -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .scanner-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 500px;
        }
        
        .scanner-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .scanner-header h1 {
            color: #191BA9;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .scanner-header p {
            color: #6c757d;
            margin: 0;
        }
        
        #qr-reader {
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            background: #f8f9fa;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #qr-reader__dashboard_section_csr {
            border-radius: 15px;
        }
        
        .scanner-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .btn-scanner {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-scanner:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 27, 169, 0.3);
            color: white;
        }
        
        .btn-scanner:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }
        
        .btn-stop {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .btn-stop:hover {
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }
        
        .scanner-status {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .status-ready {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-scanning {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #99d6ff;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-success {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .back-button {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            color: #191BA9;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: white;
            color: #191BA9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }
        
        .instructions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .instructions h6 {
            color: #191BA9;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 1.2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .instructions li {
            margin-bottom: 0.25rem;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
        }
        
        @media (max-width: 576px) {
            .scanner-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .scanner-controls {
                flex-direction: column;
            }
            
            .btn-scanner {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-button">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
    
    <div class="container">
        <div class="scanner-container">
            <div class="scanner-header">
                <h1><i class="bi bi-qr-code-scan"></i> QR Scanner</h1>
                <p>Scan QR codes to quickly view asset information</p>
            </div>
            
            <div id="qr-reader"></div>
            
            <div class="scanner-status status-ready" id="scannerStatus">
                <i class="bi bi-qr-code"></i> Ready to scan
            </div>
            
            <div class="scanner-controls">
                <button class="btn btn-scanner" id="startButton" onclick="startScanner()">
                    <i class="bi bi-camera"></i> Start Scanner
                </button>
                <button class="btn btn-scanner btn-stop" id="stopButton" onclick="stopScanner()" style="display: none;">
                    <i class="bi bi-stop-circle"></i> Stop Scanner
                </button>
            </div>
            
            <div class="instructions">
                <h6><i class="bi bi-info-circle"></i> Instructions:</h6>
                <ul>
                    <li>Click "Start Scanner" to activate the camera</li>
                    <li>Position the QR code within the frame</li>
                    <li>The scanner will automatically detect and read the QR code</li>
                    <li>You will be redirected to the asset details page</li>
                </ul>
            </div>
            
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Processing QR code...</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let html5QrCode = null;
        let isScanning = false;
        
        function updateStatus(message, type) {
            const statusElement = document.getElementById('scannerStatus');
            statusElement.className = `scanner-status status-${type}`;
            statusElement.innerHTML = message;
        }
        
        function showLoading(show) {
            document.getElementById('loadingSpinner').style.display = show ? 'block' : 'none';
        }
        
        function updateButtons(scanning) {
            isScanning = scanning;
            document.getElementById('startButton').style.display = scanning ? 'none' : 'inline-block';
            document.getElementById('stopButton').style.display = scanning ? 'inline-block' : 'none';
        }
        
        function startScanner() {
            if (isScanning) return;
            
            updateStatus('<i class="bi bi-camera"></i> Starting camera...', 'scanning');
            updateButtons(true);
            
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };
            
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText, decodedResult) => {
                    // QR code detected successfully
                    console.log('QR Code detected:', decodedText);
                    
                    // Stop scanning immediately
                    stopScanner();
                    
                    // Show loading
                    showLoading(true);
                    updateStatus('<i class="bi bi-check-circle"></i> QR Code detected! Redirecting...', 'success');
                    
                    // Process the QR code data
                    processQRCode(decodedText);
                },
                (errorMessage) => {
                    // Ignore scan errors, they happen continuously
                    // console.log('QR scan error:', errorMessage);
                }
            ).then(() => {
                updateStatus('<i class="bi bi-qr-code-scan"></i> Scanning... Position QR code in frame', 'scanning');
            }).catch((err) => {
                console.error('Unable to start scanning:', err);
                updateStatus('<i class="bi bi-exclamation-triangle"></i> Failed to access camera. Please check permissions.', 'error');
                updateButtons(false);
            });
        }
        
        function stopScanner() {
            if (!isScanning || !html5QrCode) return;
            
            html5QrCode.stop().then(() => {
                console.log('Scanning stopped');
                updateStatus('<i class="bi bi-qr-code"></i> Scanner stopped', 'ready');
                updateButtons(false);
            }).catch((err) => {
                console.error('Failed to stop scanning:', err);
            });
        }
        
        function processQRCode(decodedText) {
            try {
                // Extract asset item ID from QR code
                // Assuming QR code contains only the asset_item_id
                const assetItemId = decodedText.trim();
                
                // Validate that it's a numeric ID
                if (!/^\d+$/.test(assetItemId)) {
                    throw new Error('Invalid QR code format');
                }
                
                console.log('Asset Item ID:', assetItemId);
                
                // Redirect to view_asset_item.php
                setTimeout(() => {
                    window.location.href = `view_asset_item.php?id=${assetItemId}`;
                }, 1000);
                
            } catch (error) {
                console.error('Error processing QR code:', error);
                showLoading(false);
                updateStatus('<i class="bi bi-exclamation-triangle"></i> Invalid QR code format. Please scan a valid asset QR code.', 'error');
                updateButtons(false);
            }
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (isScanning && html5QrCode) {
                html5QrCode.stop();
            }
        });
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && isScanning) {
                stopScanner();
            }
        });
    </script>
</body>
</html>
