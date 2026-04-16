<?php
// Dark Mode Initialization Script
// This script handles dark mode functionality for all ADMIN pages

// Get dark mode setting from database or session
$dark_mode_enabled = false;

// Check session first (for immediate response)
if (isset($_SESSION['dark_mode'])) {
    $dark_mode_enabled = $_SESSION['dark_mode'];
} else {
    // Check database setting
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_name = 'dark_mode'");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $dark_mode_enabled = ($row['setting_value'] == 1);
        }
        // Store in session for faster access
        $_SESSION['dark_mode'] = $dark_mode_enabled;
    } catch (Exception $e) {
        // Default to light mode if there's an error
        $dark_mode_enabled = false;
        $_SESSION['dark_mode'] = false;
    }
}

// Handle dark mode toggle via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_dark_mode') {
    $new_dark_mode = isset($_POST['dark_mode']) ? ($_POST['dark_mode'] === 'true') : false;
    
    // Update session
    $_SESSION['dark_mode'] = $new_dark_mode;
    
    // Update database
    try {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_name, setting_value) 
                               VALUES ('dark_mode', ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
        $value = $new_dark_mode ? '1' : '0';
        $stmt->bind_param("ss", $value, $value);
        $stmt->execute();
        
        // Log the setting change
        logSystemAction($_SESSION['user_id'], 'update', 'system_settings', 
                       'Dark mode ' . ($new_dark_mode ? 'enabled' : 'disabled'));
        
        echo json_encode(['success' => true, 'dark_mode' => $new_dark_mode]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<script>
// Dark Mode JavaScript functionality
(function() {
    'use strict';
    
    // Dark mode state
    let isDarkMode = <?php echo $dark_mode_enabled ? 'true' : 'false'; ?>;
    
    // Initialize dark mode on page load
    function initDarkMode() {
        // Apply dark mode class to body
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
        }
        
        // Update any dark mode toggles
        updateDarkModeToggles();
        
        // Listen for system theme changes
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            darkModeQuery.addListener(handleSystemThemeChange);
        }
    }
    
    // Toggle dark mode
    function toggleDarkMode() {
        isDarkMode = !isDarkMode;
        
        // Update body class
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        
        // Update toggles
        updateDarkModeToggles();
        
        // Save preference via AJAX
        saveDarkModePreference();
        
        // Add transition effect
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        
        // Remove transition after animation completes
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }
    
    // Update all dark mode toggle elements
    function updateDarkModeToggles() {
        const toggles = document.querySelectorAll('.dark-mode-toggle');
        toggles.forEach(toggle => {
            // Update icon
            const icon = toggle.querySelector('i');
            if (icon) {
                if (isDarkMode) {
                    icon.classList.remove('bi-moon');
                    icon.classList.add('bi-sun');
                } else {
                    icon.classList.remove('bi-sun');
                    icon.classList.add('bi-moon');
                }
            }
            
            // Update title/tooltip
            toggle.title = isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode';
            
            // Update active state
            toggle.classList.toggle('active', isDarkMode);
        });
    }
    
    // Save dark mode preference to server
    function saveDarkModePreference() {
        const formData = new FormData();
        formData.append('action', 'toggle_dark_mode');
        formData.append('dark_mode', isDarkMode.toString());
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to save dark mode preference:', data.error);
            }
        })
        .catch(error => {
            console.error('Error saving dark mode preference:', error);
        });
    }
    
    // Handle system theme changes
    function handleSystemThemeChange(e) {
        // Optional: Auto-switch based on system preference
        // const prefersDark = e.matches;
        // if (prefersDark !== isDarkMode) {
        //     toggleDarkMode();
        // }
    }
    
    // Public API
    window.darkMode = {
        toggle: toggleDarkMode,
        isEnabled: () => isDarkMode,
        set: (enabled) => {
            if (enabled !== isDarkMode) {
                toggleDarkMode();
            }
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDarkMode);
    } else {
        initDarkMode();
    }
    
    // Add keyboard shortcut (Ctrl/Cmd + Shift + D)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
            e.preventDefault();
            toggleDarkMode();
        }
    });
    
})();
</script>

<style>
/* Dark Mode Toggle Button Styles */
.dark-mode-toggle {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.2rem;
    padding: 0.5rem;
    border-radius: var(--border-radius);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.dark-mode-toggle:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.1);
}

.dark-mode-toggle:active {
    transform: scale(0.95);
}

.dark-mode-toggle::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.dark-mode-toggle:hover::before {
    width: 100%;
    height: 100%;
}

/* Dark mode specific styles for the toggle */
body.dark-mode .dark-mode-toggle {
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.05);
}

body.dark-mode .dark-mode-toggle:hover {
    color: white;
    background: rgba(255, 255, 255, 0.15);
}

/* Animation for icon switching */
.dark-mode-toggle i {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.dark-mode-toggle:hover i {
    transform: rotate(20deg) scale(1.1);
}

/* Pulse animation for toggle when dark mode is active */
.dark-mode-toggle.active {
    animation: darkModePulse 2s infinite;
}

@keyframes darkModePulse {
    0%, 100% { 
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
    }
    50% { 
        box-shadow: 0 0 0 8px rgba(255, 255, 255, 0);
    }
}

/* Mobile responsive styles */
@media (max-width: 768px) {
    .dark-mode-toggle {
        font-size: 1rem;
        padding: 0.375rem;
    }
}

@media (max-width: 576px) {
    .dark-mode-toggle {
        font-size: 0.875rem;
        padding: 0.25rem;
    }
}
</style>
