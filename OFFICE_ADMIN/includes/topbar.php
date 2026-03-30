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

        <div class="topbar-search" id="topbarSearch">

            <div class="input-group">

                <input type="text" class="form-control" id="globalSearchInput" placeholder="Search assets, requests, users..." autocomplete="off">

                <button class="btn btn-outline-secondary" type="button" id="searchButton">

                    <i class="bi bi-search"></i>

                </button>

            </div>

            <!-- Search Results Dropdown -->

            <div class="search-results-dropdown" id="searchResultsDropdown" style="display: none;">

                <div class="search-results-header">

                    <span class="search-results-title">Search Results</span>

                    <button class="btn btn-sm btn-outline-secondary" id="clearSearchBtn">

                        <i class="bi bi-x"></i>

                    </button>

                </div>

                <div class="search-results-body" id="searchResultsBody">

                    <div class="search-loading">

                        <div class="spinner-border spinner-border-sm text-primary" role="status">

                            <span class="visually-hidden">Searching...</span>

                        </div>

                    </div>

                </div>

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



/* Enhanced Dropdown Menu Styles */
.dropdown-menu {
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.78), 0 4px 10px rgba(0, 0, 0, 0.05);
    border-radius: 12px;
    margin-top: 0.75rem;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(25, 27, 169, 0.08);
    animation: dropdownFadeIn 0.2s ease-out;
    overflow: hidden;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.dropdown-item {
    padding: 0.75rem 1.25rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0;
    position: relative;
    font-weight: 500;
    font-size: 0.9rem;
    color: #000000 !important;
    display: flex;
    align-items: center;
    margin: 0.25rem 0.5rem;
    border-radius: 8px;
}

.dropdown-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: var(--primary-gradient);
    transform: scaleY(0);
    transition: transform 0.2s ease;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, rgba(25, 27, 169, 0.05) 0%, rgba(92, 194, 242, 0.05) 100%);
    color: var(--primary-color);
    transform: translateX(2px);
}

.dropdown-item:hover::before {
    transform: scaleY(1);
}

.dropdown-item:active {
    background: linear-gradient(135deg, rgba(25, 27, 169, 0.1) 0%, rgba(92, 194, 242, 0.1) 100%);
    transform: translateX(1px);
}

.dropdown-item i {
    width: 18px;
    margin-right: 0.75rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover i {
    transform: scale(1.1);
}

.dropdown-header {
    background: linear-gradient(135deg, rgba(25, 27, 169, 0.05) 0%, rgba(92, 194, 242, 0.05) 100%);
    padding: 0.75rem 1.25rem;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #1a202c;
    border-bottom: 1px solid rgba(25, 27, 169, 0.12);
    margin-bottom: 0.25rem;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-top: 1px solid rgba(25, 27, 169, 0.12);
    background: none;
}

/* Enhanced User Profile Dropdown */
.nav-link.dropdown-toggle {
    padding: 0.5rem 1rem;
    border-radius: 12px;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.nav-link.dropdown-toggle:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.nav-link.dropdown-toggle[aria-expanded="true"] {
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* User Avatar Enhancements */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 2px 8px rgba(25, 27, 169, 0.3);
    transition: all 0.2s ease;
    position: relative;
}

.user-avatar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, transparent 50%);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.nav-link.dropdown-toggle:hover .user-avatar::after {
    opacity: 1;
}

.user-info {
    text-align: left;
}

.user-name {
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.user-role .badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.3rem 0.6rem;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

.topbar-search {
    position: relative;
}

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

/* Search Results Dropdown */

.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(25, 27, 169, 0.08);
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.05);
    margin-top: 0.5rem;
    z-index: 1000;
    animation: searchDropdownFadeIn 0.2s ease-out;
    max-height: 400px;
    overflow: hidden;
}

@keyframes searchDropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.search-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(25, 27, 169, 0.1);
    background: linear-gradient(135deg, rgba(25, 27, 169, 0.05) 0%, rgba(92, 194, 242, 0.05) 100%);
}

.search-results-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1a202c;
}

.search-results-body {
    max-height: 300px;
    overflow-y: auto;
}

.search-loading {
    padding: 2rem;
    text-align: center;
}

.search-result-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(25, 27, 169, 0.05);
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.search-result-item:hover {
    background: linear-gradient(135deg, rgba(25, 27, 169, 0.05) 0%, rgba(92, 194, 242, 0.05) 100%);
    transform: translateX(2px);
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.search-result-icon.asset {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.search-result-icon.request {
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.search-result-icon.user {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.search-result-content {
    flex-grow: 1;
    min-width: 0;
}

.search-result-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1a202c;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-result-subtitle {
    font-size: 0.8rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-result-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 0.5rem;
}

.search-no-results {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
}

.search-no-results i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
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
// Global Search System
let searchTimeout;
let searchResultsDropdown;
let searchResultsBody;
let globalSearchInput;
let searchButton;
let clearSearchBtn;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize search elements
    searchResultsDropdown = document.getElementById('searchResultsDropdown');
    searchResultsBody = document.getElementById('searchResultsBody');
    globalSearchInput = document.getElementById('globalSearchInput');
    searchButton = document.getElementById('searchButton');
    clearSearchBtn = document.getElementById('clearSearchBtn');
    
    if (!globalSearchInput || !searchResultsDropdown) {
        console.error('Search elements not found');
        return;
    }
    
    // Search input event listeners
    globalSearchInput.addEventListener('input', handleSearchInput);
    globalSearchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            showSearchResults();
        }
    });
    
    globalSearchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Search button click
    searchButton.addEventListener('click', performSearch);
    
    // Clear search button
    clearSearchBtn.addEventListener('click', clearSearch);
    
    // Close search when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#topbarSearch')) {
            hideSearchResults();
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+K to focus search
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            globalSearchInput.focus();
        }
        
        // Escape to close search results
        if (e.key === 'Escape') {
            hideSearchResults();
            globalSearchInput.blur();
        }
    });
});

function handleSearchInput() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 2) {
        hideSearchResults();
        return;
    }
    
    // Debounce search
    searchTimeout = setTimeout(() => {
        performSearch();
    }, 300);
}

function performSearch() {
    const query = globalSearchInput.value.trim();
    
    if (query.length < 2) {
        hideSearchResults();
        return;
    }
    
    showSearchResults();
    showSearchLoading();
    
    // Make API call
    fetch(`api/search_minimal.php?q=${encodeURIComponent(query)}&limit=8`, {
        credentials: 'include',
        timeout: 5000
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Search request failed');
        }
        return response.json();
    })
    .then(data => {
        console.log('Search response:', data); // Debug log
        if (data.success) {
            displaySearchResults(data.results, query);
        } else {
            showSearchError(data.message || 'Search failed');
            // Also log debug info for troubleshooting
            console.error('Search debug info:', data.debug);
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        showSearchError('Search temporarily unavailable');
    });
}

function showSearchResults() {
    searchResultsDropdown.style.display = 'block';
}

function hideSearchResults() {
    searchResultsDropdown.style.display = 'none';
}

function showSearchLoading() {
    searchResultsBody.innerHTML = `
        <div class="search-loading">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Searching...</span>
            </div>
        </div>
    `;
}

function showSearchError(message) {
    searchResultsBody.innerHTML = `
        <div class="search-no-results">
            <i class="bi bi-exclamation-triangle"></i>
            <div>${message}</div>
        </div>
    `;
}

function displaySearchResults(results, query) {
    if (results.length === 0) {
        searchResultsBody.innerHTML = `
            <div class="search-no-results">
                <i class="bi bi-search"></i>
                <div>No results found for "${query}"</div>
                <div class="small">Try searching with different keywords</div>
            </div>
        `;
        return;
    }
    
    let html = '';
    results.forEach(result => {
        const iconClass = getResultIconClass(result.type);
        const badgeClass = result.badge_class || 'bg-secondary';
        
        html += `
            <a href="${result.url}" class="search-result-item" onclick="handleSearchResultClick(event, '${result.url}')">
                <div class="search-result-icon ${result.type}">
                    <i class="bi ${iconClass}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${highlightSearchTerm(result.title, query)}</div>
                    <div class="search-result-subtitle">${highlightSearchTerm(result.subtitle || '', query)}</div>
                </div>
                <span class="badge ${badgeClass} search-result-badge">${result.badge}</span>
            </a>
        `;
    });
    
    searchResultsBody.innerHTML = html;
}

function getResultIconClass(type) {
    switch (type) {
        case 'asset': return 'bi-laptop';
        case 'request': return 'bi-arrow-left-right';
        case 'user': return 'bi-person';
        default: return 'bi-file-text';
    }
}

function highlightSearchTerm(text, term) {
    if (!text || !term) return text;
    
    const regex = new RegExp(`(${escapeRegExp(term)})`, 'gi');
    return text.replace(regex, '<strong>$1</strong>');
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function handleSearchResultClick(event, url) {
    // Allow normal navigation for most cases
    if (!event.ctrlKey && !event.metaKey) {
        hideSearchResults();
        globalSearchInput.value = '';
    }
}

function clearSearch() {
    globalSearchInput.value = '';
    hideSearchResults();
    globalSearchInput.focus();
}

// Notification System (existing code)
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
/* Responsive adjustments */
.notification-dropdown {
    width: 380px;
    max-height: 450px;
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.05);
    border-radius: 12px;
    margin-top: 0.75rem;
    overflow: hidden;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(25, 27, 169, 0.08);
    animation: dropdownFadeIn 0.2s ease-out;
}

.notification-item {
    border-bottom: 1px solid rgba(25, 27, 169, 0.1);
    transition: all 0.2s ease;
    position: relative;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: linear-gradient(135deg, rgba(92, 194, 242, 0.08) 0%, rgba(25, 27, 169, 0.05) 100%);
    border-left: 3px solid var(--secondary-color);
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: var(--secondary-color);
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(92, 194, 242, 0.2);
}

.notification-item .dropdown-item {
    white-space: normal;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    background: transparent;
    color: #000000 !important;
    transition: all 0.2s ease;
    margin: 0.25rem 0.5rem;
}

.notification-item .dropdown-item:hover {
    background: linear-gradient(135deg, rgba(25, 27, 169, 0.03) 0%, rgba(92, 194, 242, 0.03) 100%);
    color: var(--primary-color);
    transform: none;
}

.notification-item .dropdown-item:hover::before {
    display: none;
}

.notification-item .dropdown-item i {
    color: var(--secondary-color);
    font-size: 1.1rem;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.notification-loading {
    padding: 2rem;
    text-align: center;
    background: linear-gradient(135deg, rgba(25, 27, 169, 0.02) 0%, rgba(92, 194, 242, 0.02) 100%);
}

.notification-loading .spinner-border {
    width: 2rem;
    height: 2rem;
    border-width: 0.2em;
    color: var(--primary-color);
}

/* Enhanced Notification Badge */
.notification-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.95);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    animation: pulse 2s infinite, badgeGlow 3s ease-in-out infinite alternate;
}

@keyframes badgeGlow {
    from {
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    }
    to {
        box-shadow: 0 2px 12px rgba(220, 53, 69, 0.6);
    }
}

/* Notification Button Enhancement */
.topbar-notifications .btn {
    position: relative;
    padding: 0.5rem;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.2s ease;
    color: rgba(255, 255, 255, 0.9);
}

.topbar-notifications .btn:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    color: white;
}

.topbar-notifications .btn i {
    font-size: 1.1rem;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Responsive adjustments */

@media (max-width: 768px) {
    .dropdown-menu {
        margin-top: 0.5rem;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }
    
    .dropdown-item {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }

    .notification-dropdown {
        width: 320px;
        max-height: 400px;
    }
    
    .topbar-search .form-control {
        width: 150px;
    }

    .topbar-search .form-control:focus {
        width: 180px;
    }
    
    .search-results-dropdown {
        left: -50px;
        right: -50px;
        width: auto;
        max-width: calc(100vw - 100px);
    }

    .user-name {
        font-size: 0.8rem;
    }

    .user-avatar {
        font-size: 1.2rem;
    }
}

@media (max-width: 576px) {
    .search-results-dropdown {
        left: -20px;
        right: -20px;
        width: auto;
        max-width: calc(100vw - 40px);
    }
    
    .search-result-item {
        padding: 0.5rem 0.75rem;
    }
    
    .search-result-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
        margin-right: 0.5rem;
    }
    
    .search-result-title {
        font-size: 0.85rem;
    }
    
    .search-result-subtitle {
        font-size: 0.75rem;
    }
    
    .search-result-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }

    .dropdown-item {
        padding: 0.5rem 0.8rem;
    }

    .dropdown-header {
        padding: 0.6rem 1rem;
        font-size: 0.75rem;
    }

    .notification-item .dropdown-item {
        padding: 0.8rem 1rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .dropdown-menu {
        background: rgba(30, 30, 40, 0.98);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .dropdown-item {
        color: #e2e8f0;
    }
    
    .dropdown-item:hover {
        background: linear-gradient(135deg, rgba(25, 27, 169, 0.2) 0%, rgba(92, 194, 242, 0.2) 100%);
        color: white;
    }
    
    .dropdown-header {
        background: linear-gradient(135deg, rgba(25, 27, 169, 0.1) 0%, rgba(92, 194, 242, 0.1) 100%);
        color: var(--secondary-color);
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }
    
    .dropdown-divider {
        border-top-color: rgba(255, 255, 255, 0.1);
    }
    
    .notification-item {
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }
    
    .notification-item.unread {
        background: linear-gradient(135deg, rgba(92, 194, 242, 0.15) 0%, rgba(25, 27, 169, 0.1) 100%);
    }
}
</style>
