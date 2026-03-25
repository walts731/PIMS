<?php
// Office Admin Notification JavaScript Include
// This file contains the notification functionality for all OFFICE_ADMIN pages
?>

<script>
// Office Admin Notification System
document.addEventListener('DOMContentLoaded', function() {
    console.log('Office Admin page loaded - initializing notifications...');
    
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    
    if (!notificationBadge) {
        console.error('Notification badge not found!');
        return;
    }
    
    console.log('Notification elements found:', {
        badge: !!notificationBadge,
        dropdown: !!notificationDropdown,
        list: !!notificationList
    });
    
    // Update notification badge
    function updateNotificationBadge() {
        fetch('notifications_handler.php?action=get_count', {
            credentials: 'include',
            timeout: 10000
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
                console.log('Badge updated to show:', count);
            } else {
                notificationBadge.style.display = 'none';
                console.log('Badge hidden (0 unread)');
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
            // For localhost issues, show indicator
            if (error.message.includes('timeout') || error.message.includes('network')) {
                notificationBadge.textContent = '?';
                notificationBadge.style.display = 'block';
            } else {
                notificationBadge.style.display = 'none';
            }
        });
    }
    
    // Load notifications when dropdown is clicked
    if (notificationDropdown && notificationList) {
        notificationDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Notification dropdown clicked');
            
            const dropdownMenu = notificationDropdown.nextElementSibling;
            if (dropdownMenu) {
                const isVisible = dropdownMenu.style.display !== 'none';
                dropdownMenu.style.display = isVisible ? 'none' : 'block';
                
                if (!isVisible) {
                    loadNotifications();
                }
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target)) {
                const dropdownMenu = notificationDropdown.nextElementSibling;
                if (dropdownMenu && dropdownMenu.style.display !== 'none') {
                    dropdownMenu.style.display = 'none';
                }
            }
        });
    }
    
    function loadNotifications() {
        console.log('Loading notifications...');
        
        notificationList.innerHTML = '<div class="notification-loading"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        fetch('notifications_handler.php?action=get_notifications&limit=5', {
            credentials: 'include',
            timeout: 10000
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
                notificationList.innerHTML = '<li><a class="dropdown-item text-muted">Network error - check console</a></li>';
            } else {
                notificationList.innerHTML = '<li><a class="dropdown-item text-muted">Error loading notifications</a></li>';
            }
        });
    }
    
    function displayNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            notificationList.innerHTML = '<li><a class="dropdown-item text-muted">No notifications</a></li>';
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
    
    // Make functions globally available
    window.updateNotificationBadge = updateNotificationBadge;
    window.loadNotifications = loadNotifications;
    window.displayNotifications = displayNotifications;
    window.getNotificationIcon = getNotificationIcon;
    
    // Initial update
    updateNotificationBadge();
    
    // Auto-refresh every 30 seconds
    setInterval(updateNotificationBadge, 30000);
});

// Mark notification as read when clicked
function markAsReadOnClick(notificationId, event) {
    if (!event.ctrlKey && !event.metaKey) {
        event.preventDefault();
        
        // Mark as read first
        fetch('notifications_handler.php?action=mark_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}`,
            credentials: 'include'
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

// Make this function globally available
window.markAsReadOnClick = markAsReadOnClick;
</script>

<style>
/* Additional notification styles for all pages */
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
