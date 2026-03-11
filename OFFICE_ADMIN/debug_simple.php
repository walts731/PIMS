<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get database counts
$count_sql = "SELECT COUNT(*) as total, SUM(is_read = 0) as unread FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$db_total = $row['total'];
$db_unread = $row['unread'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Notification Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ccc; padding: 20px; margin: 20px 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { padding: 10px; margin: 5px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Simple Notification Debug</h1>
    
    <div class="section">
        <h2>Database Check</h2>
        <p><strong>Total Notifications:</strong> <?php echo $db_total; ?></p>
        <p><strong>Unread Notifications:</strong> <?php echo $db_unread; ?></p>
        <p class="<?php echo $db_unread > 0 ? 'success' : 'info'; ?>">
            <?php echo $db_unread > 0 ? '✅ You have unread notifications!' : 'ℹ️ No unread notifications'; ?>
        </p>
    </div>
    
    <div class="section">
        <h2>HTML Structure Test</h2>
        <!-- Test notification bell -->
        <div class="topbar-notifications dropdown">
            <button class="btn btn-link position-relative" type="button" id="notificationDropdown">
                <i class="bi bi-bell">🔔</i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">0</span>
            </button>
        </div>
        
        <button onclick="checkElements()">Check Elements</button>
        <div id="element-results"></div>
    </div>
    
    <div class="section">
        <h2>JavaScript Tests</h2>
        <button onclick="testAPI()">Test API</button>
        <button onclick="manualSetBadge()">Set Badge to 1</button>
        <button onclick="hideBadge()">Hide Badge</button>
        <button onclick="checkConsole()">Check Console</button>
        
        <div id="api-results"></div>
    </div>
    
    <div class="section">
        <h2>Manual API Test</h2>
        <p><a href="notifications_handler.php?action=get_count" target="_blank">Open API in new tab</a></p>
        <p>You should see: {"unread_count":<?php echo $db_unread; ?>}</p>
    </div>
    
    <div class="section">
        <h2>Console Commands</h2>
        <p>Copy these commands to your browser console on the dashboard page:</p>
        <pre>
// Check if badge exists
const badge = document.getElementById('notificationBadge');
console.log('Badge found:', !!badge);

// Manually set badge to show unread count
if (badge) {
    badge.textContent = '<?php echo $db_unread; ?>';
    badge.style.display = 'block';
    console.log('Badge set to <?php echo $db_unread; ?>');
}

// Test API directly
fetch('notifications_handler.php?action=get_count', {credentials: 'include'})
    .then(r => r.json())
    .then(d => console.log('API Response:', d))
    .catch(e => console.error('API Error:', e));
        </pre>
    </div>

    <script>
        function checkElements() {
            const badge = document.getElementById('notificationBadge');
            const dropdown = document.getElementById('notificationDropdown');
            
            const results = document.getElementById('element-results');
            results.innerHTML = `
                <p class="${badge ? 'success' : 'error'}">Badge Element: ${badge ? '✅ Found' : '❌ Not Found'}</p>
                <p class="${dropdown ? 'success' : 'error'}">Dropdown Element: ${dropdown ? '✅ Found' : '❌ Not Found'}</p>
                ${badge ? `<p>Badge Display: ${badge.style.display}</p>` : ''}
                ${badge ? `<p>Badge Content: "${badge.textContent}"</p>` : ''}
            `;
        }
        
        function testAPI() {
            const results = document.getElementById('api-results');
            results.innerHTML = '<p class="info">Testing API...</p>';
            
            fetch('notifications_handler.php?action=get_count', {
                credentials: 'include',
                timeout: 5000
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                results.innerHTML = `
                    <p class="success">API Response: ${text}</p>
                `;
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    if (data.unread_count > 0) {
                        const badge = document.getElementById('notificationBadge');
                        if (badge) {
                            badge.textContent = data.unread_count;
                            badge.style.display = 'block';
                            results.innerHTML += '<p class="success">✅ Badge updated automatically!</p>';
                        }
                    }
                } catch (e) {
                    results.innerHTML += `<p class="error">JSON Parse Error: ${e.message}</p>`;
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                results.innerHTML = `<p class="error">❌ API Error: ${error.message}</p>`;
            });
        }
        
        function manualSetBadge() {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = '1';
                badge.style.display = 'block';
                alert('Badge manually set to 1');
            } else {
                alert('Badge element not found!');
            }
        }
        
        function hideBadge() {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.style.display = 'none';
                alert('Badge hidden');
            } else {
                alert('Badge element not found!');
            }
        }
        
        function checkConsole() {
            alert('Open browser console (F12) to see debug messages');
            console.log('=== NOTIFICATION DEBUG ===');
            console.log('User ID: <?php echo $user_id; ?>');
            console.log('Database unread: <?php echo $db_unread; ?>');
            
            const badge = document.getElementById('notificationBadge');
            console.log('Badge element:', badge);
            if (badge) {
                console.log('Badge display:', badge.style.display);
                console.log('Badge content:', badge.textContent);
            }
        }
        
        // Auto-run element check
        document.addEventListener('DOMContentLoaded', function() {
            checkElements();
            console.log('Debug page loaded');
        });
    </script>
</body>
</html>
