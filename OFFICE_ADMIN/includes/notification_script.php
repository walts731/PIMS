<!-- Universal Notification Script for OFFICE_ADMIN -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing notification system...');
    
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    
    if (!notificationBadge) {
        console.log('Notification badge not found on this page');
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
            credentials: 'include'
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
            notificationBadge.textContent = '?';
            notificationBadge.style.display = 'block';
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
    }
    
    function loadNotifications() {
        console.log('Loading notifications...');
        
        notificationList.innerHTML = '<div class="notification-loading"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        fetch('notifications_handler.php?action=get_notifications&limit=5', {
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications response:', data);
            
            if (!data.notifications || data.notifications.length === 0) {
                notificationList.innerHTML = '<li><a class="dropdown-item text-muted">No notifications</a></li>';
                return;
            }
            
            let html = '';
            data.notifications.forEach(notification => {
                const unreadClass = notification.is_read ? '' : 'unread';
                html += '<li class="notification-item ' + unreadClass + '"><a class="dropdown-item" href="' + notification.action_url + '"><div class="fw-bold">' + notification.title + '</div><div class="small text-muted">' + notification.message + '</div><div class="small text-muted">' + notification.time_ago + '</div>' + (!notification.is_read ? '<span class="badge bg-primary ms-2">New</span>' : '') + '</a></li>';
            });
            
            notificationList.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            notificationList.innerHTML = '<li><a class="dropdown-item text-muted">Error loading notifications</a></li>';
        });
    }
    
    // Initial update
    updateNotificationBadge();
    
    // Auto-refresh every 30 seconds
    setInterval(updateNotificationBadge, 30000);
    
    console.log('Notification system initialized successfully');
});
</script>

<style>
/* Notification Badge Styles */
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

.notification-loading {
    padding: 1rem;
    text-align: center;
}
</style>
