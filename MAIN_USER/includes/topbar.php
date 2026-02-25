<nav class="navbar navbar-expand-lg navbar-dark" id="mainNavbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <?php echo ucfirst($page_title ?? 'Dashboard'); ?>
        </a>

        <div class="navbar-nav ms-auto align-items-center">
            <div class="nav-item me-3">
                <div class="search-container position-relative">
                    <form class="d-flex" action="search_handler.php" method="GET" id="searchForm">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q" id="searchInput"
                                   placeholder="Search..." autocomplete="off"
                                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                            <button class="btn btn-outline-light" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <div class="nav-item me-2">
                    <a href="scan_qr.php" class="nav-link text-white" title="QR Scanner">
                        <i class="bi bi-qr-code-scan"></i>
                    </a>
                </div>
                <div class="nav-item me-2">
                    <div class="dropdown">
                        <a class="nav-link text-white position-relative notification-bell" href="#" role="button" data-bs-toggle="dropdown" title="Notifications">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h6 class="mb-0">Notifications</h6>
                                <div class="notification-actions">
                                    <a href="#" class="mark-all-read" title="Mark all as read">
                                        <i class="bi bi-check2-all"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="notification-loading">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-footer">
                                <a href="#" class="view-all-notifications">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-3">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></div>
                            <div class="user-role">
                                <span class="badge bg-primary">Main User</span>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
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

/* Notification Dropdown Styles */
.notification-dropdown {
    width: 350px;
    max-height: 400px;
    border: none;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.notification-header h6 {
    color: #495057;
    font-weight: 600;
}

.notification-actions a {
    color: #6c757d;
    text-decoration: none;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.notification-actions a:hover {
    background: #e9ecef;
    color: #495057;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
    cursor: pointer;
    position: relative;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
    border-left: 3px solid var(--primary-color);
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0.5rem;
    width: 8px;
    height: 8px;
    background: var(--primary-color);
    border-radius: 50%;
    transform: translateY(-50%);
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.notification-message {
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.notification-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #adb5bd;
}

.notification-type {
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.notification-type.info { background: #d1ecf1; color: #0c5460; }
.notification-type.success { background: #d4edda; color: #155724; }
.notification-type.warning { background: #fff3cd; color: #856404; }
.notification-type.error { background: #f8d7da; color: #721c24; }
.notification-type.system { background: #e2e3e5; color: #383d41; }

.notification-actions-item {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.notification-actions-item button {
    padding: 0.25rem 0.5rem;
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.notification-actions-item .btn-mark-read {
    background: #28a745;
    color: white;
}

.notification-actions-item .btn-delete {
    background: #dc3545;
    color: white;
}

.notification-actions-item button:hover {
    opacity: 0.8;
}

.notification-loading {
    padding: 2rem;
    text-align: center;
}

.notification-empty {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
}

.notification-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e9ecef;
    text-align: center;
    background: #f8f9fa;
}

.notification-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
}

.notification-footer a:hover {
    text-decoration: underline;
}

.search-container .form-control {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: var(--border-radius);
    width: 250px;
    transition: width 0.3s ease;
}

.search-container .form-control:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    color: white;
    width: 300px;
}

.search-container .form-control::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.search-container .btn-outline-light {
    border-color: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
}

.search-container .btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
}

@media (max-width: 768px) {
    .search-container .form-control {
        width: 180px;
    }

    .search-container .form-control:focus {
        width: 200px;
    }
}

@media (max-width: 576px) {
    .search-container .form-control {
        width: 150px;
    }

    .search-container .form-control:focus {
        width: 170px;
    }

    .search-container .form-control::placeholder {
        font-size: 0.8rem;
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
    const notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            loadNotifications();
        });
    }
    
    // Setup mark all as read
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
    }
    
    // Auto-refresh notification count every 30 seconds
    setInterval(updateNotificationBadge, 30000);
});

function updateNotificationBadge() {
    fetch('notifications_handler.php?action=get_count', {
        credentials: 'include'  // Include cookies for session
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification count response:', data);
            const count = data.unread_count || 0;
            if (count > 0) {
                notificationBadge.textContent = count > 99 ? '99+' : count;
                notificationBadge.style.display = 'flex';
            } else {
                notificationBadge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching notification count:', error);
            // Hide badge on error to prevent confusion
            notificationBadge.style.display = 'none';
        });
}

function loadNotifications() {
    // Show loading state
    notificationList.innerHTML = `
        <div class="notification-loading">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    fetch('notifications_handler.php?action=get_notifications&limit=10', {
        credentials: 'include'  // Include cookies for session
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications response:', data);
            if (data.error) {
                throw new Error(data.error);
            }
            displayNotifications(data.notifications);
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
            notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Error loading notifications</p>
                    <small>${error.message}</small>
                </div>
            `;
        });
}

function displayNotifications(notifications) {
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="bi bi-bell-slash"></i>
                <p>No notifications</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const unreadClass = notification.is_read ? '' : 'unread';
        const typeClass = `notification-type ${notification.type}`;
        
        html += `
            <div class="notification-item ${unreadClass}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-meta">
                        <span class="notification-time">${notification.time_ago}</span>
                        <span class="${typeClass}">${notification.type}</span>
                    </div>
                    ${!notification.is_read ? `
                        <div class="notification-actions-item">
                            <button class="btn-mark-read" onclick="markNotificationAsRead(${notification.id})">
                                <i class="bi bi-check"></i> Mark as read
                            </button>
                            <button class="btn-delete" onclick="deleteNotificationItem(${notification.id})">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    notificationList.innerHTML = html;
    
    // Add click handlers for notification items
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking on action buttons
            if (e.target.closest('.notification-actions-item')) {
                return;
            }
            
            const notificationId = this.dataset.id;
            const isUnread = this.classList.contains('unread');
            
            if (isUnread) {
                markNotificationAsRead(notificationId);
            }
            
            // Navigate to related URL if available
            const actionUrl = this.querySelector('.notification-content').dataset.actionUrl;
            if (actionUrl && actionUrl !== '#') {
                window.location.href = actionUrl;
            }
        });
    });
}

function markNotificationAsRead(notificationId) {
    fetch('notifications_handler.php?action=mark_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`,
        credentials: 'include'  // Include cookies for session
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread class and actions
            const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                const actions = item.querySelector('.notification-actions-item');
                if (actions) {
                    actions.remove();
                }
            }
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllNotificationsAsRead() {
    fetch('notifications_handler.php?action=mark_all_read', {
        method: 'POST',
        credentials: 'include'  // Include cookies for session
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications to show updated state
            loadNotifications();
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function deleteNotificationItem(notificationId) {
    fetch('notifications_handler.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`,
        credentials: 'include'  // Include cookies for session
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove notification item
            const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (item) {
                item.remove();
                
                // Check if there are no more notifications
                const remainingItems = document.querySelectorAll('.notification-item');
                if (remainingItems.length === 0) {
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="bi bi-bell-slash"></i>
                            <p>No notifications</p>
                        </div>
                    `;
                }
            }
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error deleting notification:', error);
    });
}
</script>
