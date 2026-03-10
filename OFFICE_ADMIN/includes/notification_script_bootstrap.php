<!-- Bootstrap-based Notification Script for OFFICE_ADMIN -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing Bootstrap notification system...');
    
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
    
    // Initialize Bootstrap dropdown with explicit boundary control
    let bootstrapDropdown = null;
    
    if (notificationDropdown && notificationList) {
        // Initialize Bootstrap dropdown with specific boundary
        try {
            bootstrapDropdown = new bootstrap.Dropdown(notificationDropdown, {
                autoClose: 'outside',
                boundary: 'viewport',
                reference: 'toggle',
                display: 'dynamic',
                popperConfig: {
                    strategy: 'fixed', // Use fixed positioning
                    placement: 'bottom-end' // Align to right edge
                }
            });
            console.log('Bootstrap dropdown initialized successfully');
        } catch (error) {
            console.error('Error initializing Bootstrap dropdown:', error);
            return;
        }
        
        // Load notifications when dropdown is shown
        notificationDropdown.addEventListener('show.bs.dropdown', function(e) {
            console.log('Dropdown shown - loading notifications...');
            
            // Force proper positioning
            const dropdownMenu = e.target.nextElementSibling;
            if (dropdownMenu) {
                dropdownMenu.style.position = 'absolute';
                dropdownMenu.style.right = '0';
                dropdownMenu.style.left = 'auto';
                dropdownMenu.style.top = '100%';
                dropdownMenu.style.transform = 'none';
                dropdownMenu.style.minWidth = '350px';
                dropdownMenu.style.maxWidth = '350px';
                dropdownMenu.style.zIndex = '1050';
            }
            
            loadNotifications();
        });
        
        // Update badge when dropdown is hidden
        notificationDropdown.addEventListener('hide.bs.dropdown', function() {
            console.log('Dropdown hidden - updating badge...');
            updateNotificationBadge();
        });
        
        // Prevent dropdown from repositioning on outside clicks
        notificationDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Notification dropdown clicked');
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
    
    console.log('Bootstrap notification system initialized successfully');
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
    overflow-y: auto;
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
    color: inherit !important;
    text-decoration: none !important;
}

.notification-item .dropdown-item:hover {
    background-color: #f8f9fa !important;
    color: inherit !important;
}

.notification-loading {
    padding: 1rem;
    text-align: center;
}

/* Force dropdown positioning - override Bootstrap completely */
.dropdown-menu {
    position: absolute !important;
    transform: none !important;
    top: 100% !important;
    right: 0 !important;
    left: auto !important;
    min-width: 350px !important;
    max-width: 350px !important;
    max-height: 400px !important;
    overflow-y: auto !important;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
    border-radius: var(--border-radius) !important;
    background: white !important;
    border: none !important;
    z-index: 1050 !important;
}

/* Override Bootstrap's show class positioning */
.dropdown-menu.show {
    position: absolute !important;
    transform: none !important;
    top: 100% !important;
    right: 0 !important;
    left: auto !important;
    min-width: 350px !important;
    max-width: 350px !important;
}

/* Prevent Bootstrap from repositioning on outside clicks */
.dropdown-menu[data-popper] {
    position: absolute !important;
    transform: none !important;
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .notification-dropdown,
    .dropdown-menu {
        width: 300px !important;
        min-width: 300px !important;
        max-width: 300px !important;
        right: 0 !important;
    }
}
</style>
