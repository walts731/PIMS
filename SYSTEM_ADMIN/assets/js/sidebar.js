// SYSTEM_ADMIN Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainWrapper = document.getElementById('mainWrapper');
    const mainNavbar = document.getElementById('mainNavbar');
    
    if (!sidebar || !sidebarToggle || !sidebarOverlay || !mainWrapper) {
        console.error('Required sidebar elements not found');
        return;
    }
    
    // Toggle sidebar function
    function toggleSidebar() {
        const isActive = mainWrapper.classList.contains('sidebar-active');
        
        if (isActive) {
            // Close sidebar
            mainWrapper.classList.remove('sidebar-active');
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            sidebarToggle.classList.remove('sidebar-active');
            
            // Adjust navbar padding
            if (mainNavbar) {
                mainNavbar.classList.remove('sidebar-active');
            }
            
            // Adjust main content margin
            const mainContent = document.querySelector('.main-content');
            if (mainContent && window.innerWidth > 768) {
                mainContent.style.marginLeft = '0';
            }
        } else {
            // Open sidebar
            mainWrapper.classList.add('sidebar-active');
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            sidebarToggle.classList.add('sidebar-active');
            
            // Adjust navbar padding
            if (mainNavbar) {
                mainNavbar.classList.add('sidebar-active');
            }
            
            // Adjust main content margin
            const mainContent = document.querySelector('.main-content');
            if (mainContent && window.innerWidth > 768) {
                mainContent.style.marginLeft = '280px';
            }
        }
    }
    
    // Close sidebar function
    function closeSidebar() {
        mainWrapper.classList.remove('sidebar-active');
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        sidebarToggle.classList.remove('sidebar-active');
        
        // Adjust navbar padding
        if (mainNavbar) {
            mainNavbar.classList.remove('sidebar-active');
        }
        
        // Adjust main content margin
        const mainContent = document.querySelector('.main-content');
        if (mainContent && window.innerWidth > 768) {
            mainContent.style.marginLeft = '0';
        }
    }
    
    // Handle window resize
    function handleResize() {
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth <= 768) {
            // Mobile: always close sidebar on resize
            closeSidebar();
            if (mainContent) {
                mainContent.style.marginLeft = '0';
            }
        } else {
            // Desktop: adjust margin based on sidebar state
            if (mainWrapper.classList.contains('sidebar-active')) {
                if (mainContent) {
                    mainContent.style.marginLeft = '280px';
                }
            } else {
                if (mainContent) {
                    mainContent.style.marginLeft = '0';
                }
            }
            // Hide overlay on desktop
            sidebarOverlay.classList.remove('active');
        }
    }
    
    // Event listeners
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    
    sidebarOverlay.addEventListener('click', closeSidebar);
    
    window.addEventListener('resize', handleResize);
    
    // Close sidebar when clicking on navigation links (mobile)
    const sidebarLinks = document.querySelectorAll('.sidebar-nav-item');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
    
    // Handle ESC key to close sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mainWrapper.classList.contains('sidebar-active')) {
            closeSidebar();
        }
    });
    
    // Initialize
    handleResize();
    
    // Ensure sidebar is closed on page load (desktop default)
    if (window.innerWidth > 768) {
        closeSidebar();
    }
});

// Notification functionality
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.querySelector('.notification-bell');
    const notificationDropdown = document.querySelector('.notification-dropdown');
    const notificationBadge = document.getElementById('notificationBadge');
    
    if (notificationBell && notificationDropdown) {
        notificationBell.addEventListener('click', function(e) {
            e.preventDefault();
            notificationDropdown.classList.toggle('show');
        });
    }
    
    // Close notifications when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-bell') && !e.target.closest('.notification-dropdown')) {
            if (notificationDropdown) {
                notificationDropdown.classList.remove('show');
            }
        }
    });
    
    // Mark all as read functionality
    const markAllRead = document.querySelector('.mark-all-read');
    if (markAllRead) {
        markAllRead.addEventListener('click', function(e) {
            e.preventDefault();
            // Implement mark all as read logic
            if (notificationBadge) {
                notificationBadge.style.display = 'none';
            }
        });
    }
});

// Search suggestions functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    const searchForm = document.getElementById('searchForm');
    
    if (searchInput && searchSuggestions) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(function() {
                    // Fetch search suggestions
                    fetch(`../search_suggestions.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            displaySearchSuggestions(data);
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                        });
                }, 300);
            } else {
                searchSuggestions.style.display = 'none';
            }
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                searchSuggestions.style.display = 'block';
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                searchSuggestions.style.display = 'none';
            }
        });
    }
    
    function displaySearchSuggestions(suggestions) {
        if (!searchSuggestions) return;
        
        if (suggestions.length === 0) {
            searchSuggestions.innerHTML = '<div class="p-2 text-muted">No results found</div>';
        } else {
            let html = '';
            suggestions.forEach(item => {
                html += `
                    <a href="${item.url}" class="dropdown-item d-flex align-items-center">
                        <i class="bi ${item.icon} me-2"></i>
                        <div>
                            <div class="fw-bold">${item.name}</div>
                            <small class="text-muted">${item.type}</small>
                        </div>
                    </a>
                `;
            });
            searchSuggestions.innerHTML = html;
        }
        searchSuggestions.style.display = 'block';
    }
});

// User dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.querySelector('.user-dropdown');
    const userMenu = document.querySelector('.user-menu');
    
    if (userDropdown && userMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            userMenu.classList.toggle('show');
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown') && !e.target.closest('.user-menu')) {
                userMenu.classList.remove('show');
            }
        });
    }
});

// Logout confirmation
function confirmLogout() {
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
        const modal = new bootstrap.Modal(logoutModal);
        modal.show();
    }
}

// Loading states
function showLoading(element) {
    element.disabled = true;
    element.dataset.originalText = element.innerHTML;
    element.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Loading...';
}

function hideLoading(element) {
    element.disabled = false;
    element.innerHTML = element.dataset.originalText || 'Submit';
}

// Add spin animation
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #dee2e6;
        border-top: none;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .search-suggestions .dropdown-item {
        padding: 0.5rem 1rem;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .search-suggestions .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .notification-dropdown {
        min-width: 300px;
        max-height: 400px;
        overflow-y: auto;
    }
`;
document.head.appendChild(style);
