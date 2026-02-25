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
        function toggleSidebar() {
            sidebar.classList.toggle('show');
            
            // Save sidebar state to localStorage
            const isShown = sidebar.classList.contains('show');
            localStorage.setItem('sidebarShown', isShown);
            
            console.log('Sidebar is now shown:', isShown);
        }
        
        // Restore sidebar state from localStorage
        const isShown = localStorage.getItem('sidebarShown') === 'true';
        if (isShown) {
            sidebar.classList.add('show');
            console.log('Sidebar restored as shown');
        }
    } else {
        console.error('Sidebar elements not found');
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && mainWrapper) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
                localStorage.setItem('sidebarShown', 'false');
            }
        }
    });
    
    // Handle responsive sidebar
    function handleResponsiveSidebar() {
        if (window.innerWidth <= 768 && sidebar && mainWrapper) {
            sidebar.classList.remove('show');
        } else if (window.innerWidth > 768 && sidebar && mainWrapper) {
            const isShown = localStorage.getItem('sidebarShown') === 'true';
            if (isShown) {
                sidebar.classList.add('show');
            }
        }
    }
    
    // Initial check
    handleResponsiveSidebar();
    
    // Listen for window resize
    window.addEventListener('resize', handleResponsiveSidebar);
});
