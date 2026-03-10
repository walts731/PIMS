<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Debug Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Dashboard - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
        }
        
        .debug-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.9);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .test-result {
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 8px;
        }
        
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="debug-section">
            <h1><i class="bi bi-bug"></i> Notification System Debug</h1>
            <p class="text-muted">Step-by-step debugging of the notification system</p>
        </div>
        
        <!-- Step 1: Basic HTML Structure -->
        <div class="debug-section">
            <h3>Step 1: HTML Structure Check</h3>
            <div id="html-test"></div>
            
            <!-- Test notification bell structure -->
            <div class="topbar-notifications dropdown mb-3">
                <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">0</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <div id="notificationList">
                        <div class="notification-loading">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center" href="notifications.php">View all notifications</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Step 2: JavaScript Check -->
        <div class="debug-section">
            <h3>Step 2: JavaScript Check</h3>
            <div id="js-test"></div>
            <button class="btn btn-primary" onclick="testNotificationSystem()">Test Notification System</button>
            <button class="btn btn-success ms-2" onclick="manualSetBadge()">Manually Set Badge to "1"</button>
            <button class="btn btn-warning ms-2" onclick="testAPI()">Test API Directly</button>
        </div>
        
        <!-- Step 3: API Check -->
        <div class="debug-section">
            <h3>Step 3: API Response Check</h3>
            <div id="api-test"></div>
        </div>
        
        <!-- Step 4: Database Check -->
        <div class="debug-section">
            <h3>Step 4: Database Check</h3>
            <div id="db-test"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Debug logging
        console.log('=== NOTIFICATION DEBUG START ===');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, starting debug...');
            
            // Test 1: Check HTML elements
            const badge = document.getElementById('notificationBadge');
            const dropdown = document.getElementById('notificationDropdown');
            const list = document.getElementById('notificationList');
            
            const htmlTest = document.getElementById('html-test');
            htmlTest.innerHTML = `
                <div class="test-result ${badge ? 'success' : 'error'}">
                    <strong>Badge Element:</strong> ${badge ? '✅ Found' : '❌ Not Found'}
                </div>
                <div class="test-result ${dropdown ? 'success' : 'error'}">
                    <strong>Dropdown Element:</strong> ${dropdown ? '✅ Found' : '❌ Not Found'}
                </div>
                <div class="test-result ${list ? 'success' : 'error'}">
                    <strong>List Element:</strong> ${list ? '✅ Found' : '❌ Not Found'}
                </div>
            `;
            
            if (badge) {
                console.log('Badge element found:', badge);
                console.log('Badge display style:', badge.style.display);
                console.log('Badge content:', badge.textContent);
            }
            
            // Test 2: Check Bootstrap
            const jsTest = document.getElementById('js-test');
            jsTest.innerHTML = `
                <div class="test-result ${typeof bootstrap !== 'undefined' ? 'success' : 'error'}">
                    <strong>Bootstrap:</strong> ${typeof bootstrap !== 'undefined' ? '✅ Loaded' : '❌ Not Loaded'}
                </div>
                <div class="test-result ${typeof fetch !== 'undefined' ? 'success' : 'error'}">
                    <strong>Fetch API:</strong> ${typeof fetch !== 'undefined' ? '✅ Available' : '❌ Not Available'}
                </div>
            `;
            
            console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
        });
        
        // Test notification system
        function testNotificationSystem() {
            console.log('=== TESTING NOTIFICATION SYSTEM ===');
            
            const badge = document.getElementById('notificationBadge');
            if (!badge) {
                alert('Badge element not found!');
                return;
            }
            
            // Try to update badge
            console.log('Attempting to update badge...');
            
            fetch('notifications_handler.php?action=get_count', {
                credentials: 'include',
                timeout: 5000
            })
            .then(response => {
                console.log('API response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('API response data:', data);
                const count = data.unread_count || 0;
                
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'block';
                    console.log('Badge set to:', count);
                } else {
                    badge.style.display = 'none';
                    console.log('Badge hidden (0 unread)');
                }
                
                alert(`Success! Badge set to ${count > 0 ? count : 'hidden'}`);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            });
        }
        
        // Manual badge test
        function manualSetBadge() {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = '1';
                badge.style.display = 'block';
                console.log('Badge manually set to 1');
                alert('Badge manually set to 1');
            } else {
                alert('Badge element not found!');
            }
        }
        
        // Test API directly
        function testAPI() {
            console.log('=== TESTING API DIRECTLY ===');
            
            fetch('notifications_handler.php?action=get_count', {
                credentials: 'include',
                timeout: 5000
            })
            .then(response => response.text())
            .then(text => {
                console.log('Raw API response:', text);
                document.getElementById('api-test').innerHTML = `
                    <div class="test-result info">
                        <strong>API Response:</strong> ${text}
                    </div>
                `;
            })
            .catch(error => {
                console.error('API Error:', error);
                document.getElementById('api-test').innerHTML = `
                    <div class="test-result error">
                        <strong>API Error:</strong> ${error.message}
                    </div>
                `;
            });
        }
        
        // Test database via PHP
        <?php
        $user_id = $_SESSION['user_id'];
        $count_sql = "SELECT COUNT(*) as total, SUM(is_read = 0) as unread FROM notifications WHERE user_id = ?";
        $stmt = $conn->prepare($count_sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo "document.getElementById('db-test').innerHTML = `";
        echo "<div class='test-result success'>";
        echo "<strong>Database Total:</strong> {$row['total']}<br>";
        echo "<strong>Database Unread:</strong> {$row['unread']}";
        echo "</div>";
        echo "`;";
        ?>
    </script>
</body>
</html>
