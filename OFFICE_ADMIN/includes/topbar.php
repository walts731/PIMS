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

                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>

                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="bi bi-key"></i> Change Password</a></li>

                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>

                <li><hr class="dropdown-divider"></li>

                <li><a class="dropdown-item" href="../logout.php" onclick="event.preventDefault(); confirmLogout();"><i class="bi bi-box-arrow-right"></i> Logout</a></li>

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
</script>

<style>
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
