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
        let isDropdownOpen = false;
        
        // Disable Bootstrap dropdown for this element
        if (notificationDropdown.getAttribute('data-bs-toggle')) {
            notificationDropdown.removeAttribute('data-bs-toggle');
            notificationDropdown.removeAttribute('data-bs-auto-close');
        }
        
        // Find and disable Bootstrap dropdown if it exists
        const bootstrapDropdown = notificationDropdown.parentElement.querySelector('.dropdown-menu');
        if (bootstrapDropdown) {
            bootstrapDropdown.removeAttribute('data-bs-popper');
        }
        
        notificationDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Notification dropdown clicked');
            
            const dropdownMenu = notificationDropdown.nextElementSibling;
            if (dropdownMenu) {
                if (!isDropdownOpen) {
                    // Open dropdown
                    dropdownMenu.style.display = 'block';
                    dropdownMenu.style.position = 'absolute';
                    dropdownMenu.style.top = '100%';
                    dropdownMenu.style.right = '0';
                    dropdownMenu.style.left = 'auto';
                    dropdownMenu.style.transform = 'translateX(0)';
                    dropdownMenu.style.zIndex = '1050';
                    dropdownMenu.style.minWidth = '350px';
                    dropdownMenu.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.15)';
                    dropdownMenu.style.borderRadius = 'var(--border-radius)';
                    dropdownMenu.style.background = 'white';
                    dropdownMenu.style.border = 'none';
                    dropdownMenu.style.overflow = 'hidden';
                    
                    // Load notifications only when opening
                    loadNotifications();
                    isDropdownOpen = true;
                    
                    // Add click outside listener
                    setTimeout(() => {
                        document.addEventListener('click', closeDropdownOutside);
                    }, 100);
                } else {
                    // Close dropdown
                    dropdownMenu.style.display = 'none';
                    isDropdownOpen = false;
                    document.removeEventListener('click', closeDropdownOutside);
                }
            }
        });
        
        function closeDropdownOutside(e) {
            const dropdownMenu = notificationDropdown.nextElementSibling;
            if (dropdownMenu && !notificationDropdown.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
                isDropdownOpen = false;
                document.removeEventListener('click', closeDropdownOutside);
            }
        }
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
    background: white;
    display: none;
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
    display: block;
    width: 100%;
}

.notification-item .dropdown-item:hover {
    background-color: #f8f9fa !important;
    color: inherit !important;
}

.notification-loading {
    padding: 1rem;
    text-align: center;
}

/* Override Bootstrap dropdown completely */
.dropdown-menu {
    position: absolute !important;
    transform: none !important;
    top: 100% !important;
    right: 0 !important;
    left: auto !important;
    min-width: 350px !important;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
    border-radius: var(--border-radius) !important;
    background: white !important;
    border: none !important;
    overflow: hidden !important;
}

/* Prevent Bootstrap from repositioning */
.dropdown-menu.show {
    position: absolute !important;
    transform: none !important;
    top: 100% !important;
    right: 0 !important;
    left: auto !important;
}

/* Ensure dropdown doesn't go off screen */
@media (max-width: 768px) {
    .notification-dropdown,
    .dropdown-menu {
        width: 300px !important;
        min-width: 300px !important;
        right: -50px !important;
    }
}
</style>
