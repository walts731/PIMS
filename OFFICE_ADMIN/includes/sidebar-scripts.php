// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar script loaded');
    
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');
    
    console.log('Elements found:', {
        sidebarToggle: !!sidebarToggle,
        sidebar: !!sidebar,
        mainWrapper: !!mainWrapper
    });
    
    if (sidebarToggle && sidebar && mainWrapper) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Toggle clicked');
            
            sidebar.classList.toggle('collapsed');
            mainWrapper.classList.toggle('sidebar-collapsed');
            
            // Save sidebar state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            console.log('Sidebar is now collapsed:', isCollapsed);
        });
        
        // Restore sidebar state from localStorage
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainWrapper.classList.add('sidebar-collapsed');
            console.log('Sidebar restored as collapsed');
        }
    } else {
        console.error('Sidebar elements not found');
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && mainWrapper) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.add('collapsed');
                mainWrapper.classList.add('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        }
    });
    
    // Handle responsive sidebar
    function handleResponsiveSidebar() {
        if (window.innerWidth <= 768 && sidebar && mainWrapper) {
            sidebar.classList.add('collapsed');
            mainWrapper.classList.add('sidebar-collapsed');
        } else if (window.innerWidth > 768 && sidebar && mainWrapper) {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (!isCollapsed) {
                sidebar.classList.remove('collapsed');
                mainWrapper.classList.remove('sidebar-collapsed');
            }
        }
    }
    
    // Initial check
    handleResponsiveSidebar();
    
    // Listen for window resize
    window.addEventListener('resize', handleResponsiveSidebar);
});
