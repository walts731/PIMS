// Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.createElement('div');
    const topbar = document.querySelector('.topbar');
    const mainContent = document.querySelector('.main-content');
    
    // Create overlay
    sidebarOverlay.className = 'sidebar-overlay';
    document.body.appendChild(sidebarOverlay);
    
    // Handle both toggle buttons
    function toggleSidebar() {
        const isOpen = sidebar.classList.contains('show');
        
        if (isOpen) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            // Update both toggle buttons
            document.querySelectorAll('.sidebar-toggle').forEach(btn => {
                btn.classList.remove('active');
            });
            // Adjust main content margin
            if (window.innerWidth > 1024) {
                mainContent.style.marginLeft = '0';
            }
        } else {
            sidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
            // Update both toggle buttons
            document.querySelectorAll('.sidebar-toggle').forEach(btn => {
                btn.classList.add('active');
            });
            // Adjust main content margin
            if (window.innerWidth > 1024) {
                mainContent.style.marginLeft = '280px';
            }
        }
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
        // Update both toggle buttons
        document.querySelectorAll('.sidebar-toggle').forEach(btn => {
            btn.classList.remove('active');
        });
        // Adjust main content margin
        if (window.innerWidth > 1024) {
            mainContent.style.marginLeft = '0';
        }
    }
    
    // Handle resize
    function handleResize() {
        if (window.innerWidth > 1024) {
            // Desktop: sidebar can be toggled but affects main content margin
            if (sidebar.classList.contains('show')) {
                mainContent.style.marginLeft = '280px';
            } else {
                mainContent.style.marginLeft = '0';
            }
            sidebarOverlay.classList.remove('show');
        } else {
            // Mobile/tablet: sidebar is always hidden by default
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.querySelectorAll('.sidebar-toggle').forEach(btn => {
                btn.classList.remove('active');
            });
            mainContent.style.marginLeft = '0';
        }
    }
    
    // Event listeners for both toggle buttons
    document.querySelectorAll('.sidebar-toggle').forEach(button => {
        button.addEventListener('click', toggleSidebar);
    });
    
    sidebarOverlay.addEventListener('click', closeSidebar);
    
    window.addEventListener('resize', handleResize);
    
    // Initialize on load
    handleResize();
    
    // Close sidebar when clicking on menu items (mobile)
    const menuLinks = document.querySelectorAll('.menu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                closeSidebar();
            }
        });
    });
    
    // Handle dropdown menus if any
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.nextElementSibling;
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add active state to current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('.menu-link');
    
    menuLinks.forEach(link => {
        const linkPath = new URL(link.href).pathname;
        if (currentPath === linkPath || currentPath.includes(linkPath.split('/').pop())) {
            link.closest('.menu-item').classList.add('active');
        }
    });
});

// Notification badge animation
function updateNotificationBadge(count) {
    const badge = document.querySelector('.topbar-notifications .badge');
    if (badge) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
        
        // Add pulse animation for new notifications
        if (count > 0) {
            badge.classList.add('animate-pulse');
            setTimeout(() => {
                badge.classList.remove('animate-pulse');
            }, 2000);
        }
    }
}

// User menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.querySelector('.topbar-user .dropdown-menu');
    
    if (userDropdown && userMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            userMenu.classList.toggle('show');
        });
    }
});

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.topbar-search input');
    const searchButton = document.querySelector('.topbar-search button');
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                // Implement search functionality
                console.log('Searching for:', query);
                // You can redirect to search results page or implement live search
            }
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchButton.click();
            }
        });
    }
});

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
    
    .animate-pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
