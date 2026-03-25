<!-- Topbar -->

<header class="topbar" id="topbar">

    <div class="topbar-left">

        <!-- Sidebar Toggle Button -->

        <button class="sidebar-toggle" id="sidebarToggle">

            <i class="bi bi-list"></i>

        </button>

        <div class="topbar-title">

            <h5 class="mb-0"><?php echo $page_title ?? 'Office Admin'; ?></h5>

        </div>

    </div>

    

    <div class="topbar-right">

        <!-- Search -->

        <div class="topbar-search">

            <div class="input-group">

                <input type="text" class="form-control" placeholder="Search...">

                <button class="btn btn-outline-secondary" type="button">

                    <i class="bi bi-search"></i>

                </button>

            </div>

        </div>

        

        <!-- Notifications -->

        <div class="topbar-notifications dropdown">

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

        

        <!-- User Profile Dropdown -->

        <div class="nav-item dropdown">

            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="userDropdown">

                <div class="user-avatar me-3">

                    <i class="bi bi-person-circle"></i>

                </div>

                <div class="user-info">

                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>

                    <div class="user-role">

                        <?php 

                        $role = htmlspecialchars(ucfirst(str_replace('_', ' ', $_SESSION['role']))); 

                        $badge_class = 'bg-secondary';

                        if ($_SESSION['role'] === 'system_admin') {

                            $badge_class = 'bg-danger';

                        } elseif ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'office_admin') {

                            $badge_class = 'bg-warning text-dark';

                        } elseif ($_SESSION['role'] === 'user') {

                            $badge_class = 'bg-success';

                        }

                        ?>

                        <span class="badge <?php echo $badge_class; ?>"><?php echo $role; ?></span>

                    </div>

                </div>

            </a>

            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">

                <li><h6 class="dropdown-header">Account</h6></li>
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a class="dropdown-item" href="#" onclick="showSessionManagement()"><i class="bi bi-laptop"></i> Active Sessions</a></li>
                
                <li><h6 class="dropdown-header">Quick Actions</h6></li>
                <li><a class="dropdown-item" href="#" onclick="toggleTheme()"><i class="bi bi-moon"></i> Toggle Theme</a></li>
                <li><a class="dropdown-item" href="#" onclick="showKeyboardShortcuts()"><i class="bi bi-keyboard"></i> Keyboard Shortcuts</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportMyData()"><i class="bi bi-download"></i> Export My Data</a></li>
                
                <li><h6 class="dropdown-header">Support</h6></li>
                <li><a class="dropdown-item" href="#" onclick="showHelp()"><i class="bi bi-question-circle"></i> Help Center</a></li>
                <li><a class="dropdown-item" href="#" onclick="reportIssue()"><i class="bi bi-bug"></i> Report Issue</a></li>
                
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-primary" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>
                <li><a class="dropdown-item text-danger" href="../logout.php" onclick="event.preventDefault(); confirmLogout();"><i class="bi bi-box-arrow-right"></i> Logout</a></li>

            </ul>

        </div>

    </div>

</header>



<style>

/* User Dropdown Styles */

.user-avatar {

    font-size: 1.5rem;

    color: rgba(255, 255, 255, 0.8);

}



.user-info {

    text-align: left;

}



.user-name {

    font-weight: 600;

    color: white;

    font-size: 0.9rem;

    margin-bottom: 0.25rem;

}



.user-role .badge {

    font-size: 0.7rem;

    font-weight: 500;

    padding: 0.25rem 0.5rem;

    border-radius: 12px;

    text-transform: uppercase;

}



.navbar-dark .nav-link {

    color: rgba(255, 255, 255, 0.8) !important;

    transition: color 0.3s ease;

}



.navbar-dark .nav-link:hover {

    color: white !important;

}



.dropdown-menu {

    border: none;

    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);

    border-radius: var(--border-radius);

    margin-top: 0.5rem;

}



.dropdown-item {

    padding: 0.75rem 1rem;

    transition: background-color 0.2s ease;

}



.dropdown-item:hover {

    background-color: #f8f9fa;

}



.dropdown-item i {

    width: 16px;

    margin-right: 0.5rem;

}



/* Notification Bell Styles */

.notification-bell {

    font-size: 1.2rem;

    transition: transform 0.2s ease;

}



.notification-bell:hover {

    transform: scale(1.1);

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



/* Search Styles */

.topbar-search .form-control {

    background: rgba(255, 255, 255, 0.1);

    border: 1px solid rgba(255, 255, 255, 0.2);

    color: white;

    border-radius: var(--border-radius);

    width: 200px;

    transition: width 0.3s ease;

}



.topbar-search .form-control:focus {

    background: rgba(255, 255, 255, 0.15);

    border-color: rgba(255, 255, 255, 0.3);

    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);

    color: white;

    width: 250px;

}



.topbar-search .form-control::placeholder {

    color: rgba(255, 255, 255, 0.7);

}



.topbar-search .btn-outline-secondary {

    border-color: rgba(255, 255, 255, 0.2);

    color: rgba(255, 255, 255, 0.8);

}



.topbar-search .btn-outline-secondary:hover {

    background: rgba(255, 255, 255, 0.1);

    border-color: rgba(255, 255, 255, 0.3);

    color: white;

}



/* Responsive adjustments */

@media (max-width: 768px) {

    .topbar-search .form-control {

        width: 150px;

    }

    

    .topbar-search .form-control:focus {

        width: 180px;

    }

    

    .user-name {

        font-size: 0.8rem;

    }

    

    .user-avatar {

        font-size: 1.2rem;

    }

}

</style>



<script>
// Notification System
let notificationDropdown;
let notificationList;
let notificationBadge;
let notificationTimeout;

document.addEventListener('DOMContentLoaded', function() {
    notificationDropdown = document.getElementById('notificationDropdown');
    notificationList = document.getElementById('notificationList');
    notificationBadge = document.getElementById('notificationBadge');
    
    // Initialize notification system
    updateNotificationBadge();
    
    // Setup dropdown events
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function() {
            loadNotifications();
        });
    }
    
    // Auto-refresh notification count every 30 seconds
    setInterval(updateNotificationBadge, 30000);
});

function updateNotificationBadge() {
    fetch('notifications_handler.php?action=get_count', {
        credentials: 'include',  // Include cookies for session
        timeout: 10000  // 10 second timeout
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification count response:', data);
            const count = data.unread_count || 0;
            if (count > 0) {
                notificationBadge.textContent = count > 99 ? '99+' : count;
                notificationBadge.style.display = 'block';
            } else {
                notificationBadge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
            // For localhost issues, try a fallback approach
            if (error.message.includes('timeout') || error.message.includes('network')) {
                console.log('Network error detected, trying fallback...');
                // Set a default visible badge to indicate notifications exist
                notificationBadge.textContent = '?';
                notificationBadge.style.display = 'block';
            } else {
                notificationBadge.style.display = 'none';
            }
        });
}

function loadNotifications() {
    if (!notificationList) return;
    
    // Show loading state
    notificationList.innerHTML = `
        <div class="notification-loading">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    fetch('notifications_handler.php?action=get_notifications&limit=5', {
        credentials: 'include',
        timeout: 10000  // 10 second timeout
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications response:', data);
            displayNotifications(data.notifications);
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            if (error.message.includes('timeout') || error.message.includes('network')) {
                notificationList.innerHTML = `
                    <li><a class="dropdown-item text-muted">Network error - check console</a></li>
                    <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
                `;
            } else {
                notificationList.innerHTML = `
                    <li><a class="dropdown-item text-muted">Error loading notifications</a></li>
                `;
            }
        });
}

function displayNotifications(notifications) {
    if (!notifications || notifications.length === 0) {
        notificationList.innerHTML = `
            <li><a class="dropdown-item text-muted">No notifications</a></li>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const unreadClass = notification.is_read ? '' : 'unread';
        const typeIcon = getNotificationIcon(notification.type);
        
        html += `
            <li class="notification-item ${unreadClass}">
                <a class="dropdown-item" href="${notification.action_url}" onclick="markAsReadOnClick(${notification.id}, event)">
                    <div class="d-flex align-items-start">
                        <div class="me-2">
                            <i class="bi ${typeIcon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${notification.title}</div>
                            <div class="small text-muted">${notification.message}</div>
                            <div class="small text-muted">${notification.time_ago}</div>
                        </div>
                        ${!notification.is_read ? '<div class="ms-2"><span class="badge bg-primary">New</span></div>' : ''}
                    </div>
                </a>
            </li>
        `;
    });
    
    notificationList.innerHTML = html;
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'bi-check-circle-fill text-success';
        case 'warning': return 'bi-exclamation-triangle-fill text-warning';
        case 'error': return 'bi-x-circle-fill text-danger';
        case 'info': return 'bi-info-circle-fill text-info';
        case 'system': return 'bi-gear-fill text-secondary';
        default: return 'bi-bell-fill text-primary';
    }
}

function markAsReadOnClick(notificationId, event) {
    if (!event.ctrlKey && !event.metaKey) {
        event.preventDefault();
        
        // Mark as read first
        fetch('notifications_handler.php?action=mark_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Get the notification URL and navigate
                fetch('notifications_handler.php?action=get_notifications&limit=1&offset=0', {
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(notificationData => {
                    if (notificationData.notifications && notificationData.notifications.length > 0) {
                        const notification = notificationData.notifications[0];
                        window.location.href = notification.action_url;
                    }
                })
                .catch(error => {
                    console.error('Error getting notification URL:', error);
                });
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }
}

// Debug dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Office Admin topbar loaded, checking dropdown functionality...');
    
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded!');
    } else {
        console.log('Bootstrap is loaded successfully');
    }
    
    // Check dropdown elements
    const dropdowns = document.querySelectorAll('.dropdown');
    console.log('Found dropdowns:', dropdowns.length);
    
    // Initialize dropdowns manually if needed
    dropdowns.forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        if (toggle) {
            console.log('Initializing dropdown for:', toggle);
            try {
                const dropdownInstance = new bootstrap.Dropdown(toggle);
                console.log('Dropdown initialized successfully');
            } catch (error) {
                console.error('Error initializing dropdown:', error);
            }
        }
    });
});

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
    }
}

// Enhanced profile dropdown functions
function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Show notification
    showNotification(`Theme changed to ${newTheme}`, 'success');
}

function showKeyboardShortcuts() {
    const shortcuts = `
        <div class="modal fade" id="keyboardShortcutsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-keyboard"></i> Keyboard Shortcuts</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Navigation</h6>
                                <table class="table table-sm">
                                    <tr><td><kbd>Ctrl</kbd> + <kbd>K</kbd></td><td>Quick search</td></tr>
                                    <tr><td><kbd>Ctrl</kbd> + <kbd>/</kbd></td><td>Show shortcuts</td></tr>
                                    <tr><td><kbd>Ctrl</kbd> + <kbd>N</kbd></td><td>New item/request</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Actions</h6>
                                <table class="table table-sm">
                                    <tr><td><kbd>Ctrl</kbd> + <kbd>S</kbd></td><td>Save</td></tr>
                                    <tr><td><kbd>Ctrl</kbd> + <kbd>P</kbd></td><td>Print/Export</td></tr>
                                    <tr><td><kbd>Esc</kbd></td><td>Close modal</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if present
    const existingModal = document.getElementById('keyboardShortcutsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body and show
    document.body.insertAdjacentHTML('beforeend', shortcuts);
    const modal = new bootstrap.Modal(document.getElementById('keyboardShortcutsModal'));
    modal.show();
}

function exportMyData() {
    if (confirm('This will export your personal data including profile information and activity logs. Continue?')) {
        // Create loading notification
        showNotification('Preparing your data export...', 'info');
        
        // Simulate export process
        fetch('export_user_data.php', {
            method: 'POST',
            credentials: 'include'
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `my_data_export_${new Date().toISOString().split('T')[0]}.zip`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showNotification('Your data has been exported successfully!', 'success');
        })
        .catch(error => {
            console.error('Export error:', error);
            showNotification('Error exporting data. Please try again.', 'error');
        });
    }
}

function showHelp() {
    window.open('../help/index.html', '_blank', 'width=800,height=600,scrollbars=yes');
}

function reportIssue() {
    const issueModal = `
        <div class="modal fade" id="reportIssueModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-bug"></i> Report an Issue</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="issueForm">
                            <div class="mb-3">
                                <label class="form-label">Issue Type</label>
                                <select class="form-select" name="issue_type" required>
                                    <option value="">Select type...</option>
                                    <option value="bug">Bug Report</option>
                                    <option value="feature">Feature Request</option>
                                    <option value="performance">Performance Issue</option>
                                    <option value="ui">UI/UX Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="4" required 
                                          placeholder="Please describe the issue in detail..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Steps to Reproduce</label>
                                <textarea class="form-control" name="steps" rows="3" 
                                          placeholder="1. Go to...\n2. Click on...\n3. See error..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="submitIssue()">Submit Issue</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if present
    const existingModal = document.getElementById('reportIssueModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body and show
    document.body.insertAdjacentHTML('beforeend', issueModal);
    const modal = new bootstrap.Modal(document.getElementById('reportIssueModal'));
    modal.show();
}

function submitIssue() {
    const form = document.getElementById('issueForm');
    const formData = new FormData(form);
    
    // Add current page info
    formData.append('current_page', window.location.href);
    formData.append('user_agent', navigator.userAgent);
    
    fetch('report_issue.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('reportIssueModal')).hide();
            showNotification('Issue reported successfully. We\'ll look into it!', 'success');
        } else {
            showNotification('Error reporting issue. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Report error:', error);
        showNotification('Error reporting issue. Please try again.', 'error');
    });
}

function showSessionManagement() {
    // Load active sessions
    showNotification('Loading active sessions...', 'info');
    
    fetch('session_management.php?action=get_sessions', {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySessionModal(data.sessions);
        } else {
            showNotification('Error loading sessions: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Session management error:', error);
        showNotification('Error loading sessions. Please try again.', 'error');
    });
}

function displaySessionModal(sessions) {
    const sessionModal = `
        <div class="modal fade" id="sessionManagementModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-laptop"></i> Active Sessions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Manage your active sessions across different devices and browsers.
                        </div>
                        
                        <div class="sessions-list">
                            ${sessions.length === 0 ? 
                                '<p class="text-muted">No active sessions found.</p>' :
                                sessions.map(session => `
                                    <div class="session-item border rounded p-3 mb-3 ${session.is_current ? 'border-primary bg-light' : ''}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-${getDeviceIcon(session.user_agent)} me-2"></i>
                                                    <strong>${session.device_type || 'Unknown Device'}</strong>
                                                    ${session.is_current ? '<span class="badge bg-primary ms-2">Current Session</span>' : ''}
                                                </div>
                                                <div class="small text-muted">
                                                    <div><i class="bi bi-browser-chrome"></i> ${session.browser || 'Unknown Browser'}</div>
                                                    <div><i class="bi bi-geo-alt"></i> ${session.ip_address || 'Unknown IP'}</div>
                                                    <div><i class="bi bi-clock"></i> Last active: ${formatTime(session.last_activity)}</div>
                                                    <div><i class="bi bi-calendar"></i> Started: ${formatTime(session.created_at)}</div>
                                                </div>
                                            </div>
                                            ${!session.is_current ? `
                                                <button class="btn btn-sm btn-outline-danger" onclick="revokeSession('${session.session_id}')">
                                                    <i class="bi bi-x-circle"></i> Revoke
                                                </button>
                                            ` : ''}
                                        </div>
                                    </div>
                                `).join('')
                            }
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-warning" onclick="revokeAllSessions()">
                                <i class="bi bi-shield-exclamation"></i> Sign Out All Other Sessions
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if present
    const existingModal = document.getElementById('sessionManagementModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body and show
    document.body.insertAdjacentHTML('beforeend', sessionModal);
    const modal = new bootstrap.Modal(document.getElementById('sessionManagementModal'));
    modal.show();
}

function getDeviceIcon(userAgent) {
    if (!userAgent) return 'device-unknown';
    
    if (userAgent.includes('Mobile') || userAgent.includes('Android') || userAgent.includes('iPhone')) {
        return 'phone';
    } else if (userAgent.includes('Tablet') || userAgent.includes('iPad')) {
        return 'tablet';
    } else if (userAgent.includes('Windows') || userAgent.includes('Mac') || userAgent.includes('Linux')) {
        return 'laptop';
    }
    return 'device-unknown';
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minutes ago`;
    if (diffHours < 24) return `${diffHours} hours ago`;
    if (diffDays < 7) return `${diffDays} days ago`;
    
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function revokeSession(sessionId) {
    if (!confirm('Are you sure you want to revoke this session? The user will be logged out.')) {
        return;
    }
    
    fetch('session_management.php?action=revoke_session', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `session_id=${sessionId}`,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Session revoked successfully', 'success');
            showSessionManagement(); // Refresh the sessions list
        } else {
            showNotification('Error revoking session: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Revoke session error:', error);
        showNotification('Error revoking session. Please try again.', 'error');
    });
}

function revokeAllSessions() {
    if (!confirm('Are you sure you want to sign out all other sessions? You will remain logged in on this device.')) {
        return;
    }
    
    fetch('session_management.php?action=revoke_all_other', {
        method: 'POST',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Signed out ${data.revoked_count} other sessions`, 'success');
            showSessionManagement(); // Refresh the sessions list
        } else {
            showNotification('Error revoking sessions: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Revoke all sessions error:', error);
        showNotification('Error revoking sessions. Please try again.', 'error');
    });
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Essential User Profile Dropdown Functionality
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.getElementById('userDropdown');
    
    if (userDropdown) {
        // Initialize Bootstrap dropdown
        let bootstrapDropdown = null;
        
        // Try to initialize Bootstrap dropdown
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            bootstrapDropdown = new bootstrap.Dropdown(userDropdown);
            console.log('✓ Bootstrap dropdown initialized');
        }
        
        // Add click event listener to the dropdown toggle
        userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                // Use Bootstrap API if available
                if (bootstrapDropdown) {
                    bootstrapDropdown.toggle();
                    console.log('✓ Bootstrap toggle() called');
                } else {
                    // Fallback: Manual toggle
                    const dropdownMenu = userDropdown.nextElementSibling;
                    if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                        const isVisible = dropdownMenu.style.display !== 'none' && dropdownMenu.style.display !== '';
                        dropdownMenu.style.display = isVisible ? 'none' : 'block';
                        userDropdown.setAttribute('aria-expanded', !isVisible);
                        console.log(`✓ Manual toggle: ${!isVisible ? 'opened' : 'closed'}`);
                    }
                }
            } catch (error) {
                console.error('Dropdown error:', error);
            }
        });
        
        // Close dropdown when clicking outside (but not on dropdown items)
        document.addEventListener('click', function(e) {
            // Don't close if clicking on the dropdown toggle or its menu
            if (userDropdown.contains(e.target)) {
                return;
            }
            
            // Close if clicking outside
            const dropdownMenu = userDropdown.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                if (bootstrapDropdown) {
                    // Let Bootstrap handle closing
                    return;
                } else {
                    // Manual close
                    dropdownMenu.style.display = 'none';
                    userDropdown.setAttribute('aria-expanded', 'false');
                }
            }
        });
        
        // Handle dropdown item clicks without interfering with dropdown functionality
        const dropdownMenu = userDropdown.nextElementSibling;
        if (dropdownMenu) {
            const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
            
            dropdownItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // Allow normal link behavior for dropdown items
                    // Don't prevent default - let links work normally
                    console.log('Dropdown item clicked:', this.textContent.trim());
                    
                    // Close dropdown after a short delay to allow navigation
                    setTimeout(() => {
                        if (bootstrapDropdown) {
                            bootstrapDropdown.hide();
                        } else {
                            dropdownMenu.style.display = 'none';
                            userDropdown.setAttribute('aria-expanded', 'false');
                        }
                    }, 100);
                });
            });
        }
        
        // Handle escape key to close dropdown
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (bootstrapDropdown) {
                    bootstrapDropdown.hide();
                } else {
                    const dropdownMenu = userDropdown.nextElementSibling;
                    if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                        dropdownMenu.style.display = 'none';
                        userDropdown.setAttribute('aria-expanded', 'false');
                    }
                }
            }
        });
        
        console.log('✓ User profile dropdown functionality initialized');
    }
});
</script>

<style>
/* User Dropdown Styles */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.user-info {
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.user-role .badge {
    font-size: 0.75rem;
}

/* Notification Dropdown Styles */
.notification-dropdown {
    width: 350px;
    max-height: 400px;
    border: none;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    border-radius: var(--border-radius);
    margin-top: 0.5rem;
    overflow: hidden;
}

.notification-item {
    border-bottom: 1px solid #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
    border-left: 3px solid #5CC2F2;
}

.notification-item .dropdown-item {
    white-space: normal;
    padding: 0.75rem 1rem;
    border-radius: 0;
}

.notification-item .dropdown-item:hover {
    background-color: #f8f9fa;
}

.notification-loading {
    padding: 1rem;
    text-align: center;
}

.notification-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
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

/* Responsive adjustments */
@media (max-width: 576px) {
    .notification-dropdown {
        width: 300px;
    }
}
</style>
