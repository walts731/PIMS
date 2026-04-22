/**
 * Dark Mode Toggle Functionality for PIMS OFFICE_ADMIN
 * Handles theme switching with localStorage persistence
 */

class ThemeManager {
    constructor() {
        this.storageKey = 'pims-theme';
        this.darkClass = 'dark-theme';
        this.init();
    }

    init() {
        // Load saved theme or default to light
        const savedTheme = this.getSavedTheme();
        this.setTheme(savedTheme);

        // Create and inject toggle button
        this.createToggleButton();

        // Listen for system theme changes
        this.listenForSystemThemeChanges();
    }

    getSavedTheme() {
        const saved = localStorage.getItem(this.storageKey);
        if (saved) {
            return saved === 'dark' ? 'dark' : 'light';
        }
        
        // Check system preference
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    setTheme(theme) {
        const html = document.documentElement;
        
        if (theme === 'dark') {
            html.setAttribute('data-theme', 'dark');
            html.classList.add(this.darkClass);
        } else {
            html.removeAttribute('data-theme');
            html.classList.remove(this.darkClass);
        }

        // Update toggle button state
        this.updateToggleButton(theme);
        
        // Save to localStorage
        localStorage.setItem(this.storageKey, theme);

        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { theme } 
        }));
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    createToggleButton() {
        // Find the topbar right section
        const topbarRight = document.querySelector('.topbar-right');
        if (!topbarRight) return;

        // Create toggle container
        const toggleContainer = document.createElement('div');
        toggleContainer.className = 'theme-toggle-container';
        toggleContainer.style.cssText = `
            display: flex;
            align-items: center;
            gap: 0.5rem;
        `;

        // Create toggle button
        const toggleButton = document.createElement('button');
        toggleButton.className = 'theme-toggle';
        toggleButton.setAttribute('aria-label', 'Toggle dark mode');
        toggleButton.innerHTML = `
            <div class="theme-toggle-slider">
                <span class="theme-toggle-icon">${this.getCurrentIcon()}</span>
            </div>
        `;

        // Add click handler
        toggleButton.addEventListener('click', () => this.toggleTheme());

        // Create label
        const label = document.createElement('span');
        label.className = 'theme-toggle-label';
        label.textContent = 'Dark Mode';
        label.style.cssText = `
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
        `;

        // Assemble and inject
        toggleContainer.appendChild(toggleButton);
        toggleContainer.appendChild(label);
        topbarRight.insertBefore(toggleContainer, topbarRight.firstChild);
    }

    updateToggleButton(theme) {
        const toggle = document.querySelector('.theme-toggle');
        const icon = document.querySelector('.theme-toggle-icon');
        const label = document.querySelector('.theme-toggle-label');

        if (toggle) {
            if (theme === 'dark') {
                toggle.classList.add('active');
            } else {
                toggle.classList.remove('active');
            }
        }

        if (icon) {
            icon.textContent = this.getCurrentIcon();
        }

        if (label) {
            label.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
        }
    }

    getCurrentIcon() {
        const currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        return currentTheme === 'dark' ? 'moon' : 'sun';
    }

    listenForSystemThemeChanges() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            // Only change if user hasn't explicitly set a preference
            if (!localStorage.getItem(this.storageKey)) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // Public method to get current theme
    getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    // Public method to check if dark mode is active
    isDarkMode() {
        return this.getCurrentTheme() === 'dark';
    }
}

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

// Also initialize immediately if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeManager = new ThemeManager();
    });
} else {
    window.themeManager = new ThemeManager();
}
