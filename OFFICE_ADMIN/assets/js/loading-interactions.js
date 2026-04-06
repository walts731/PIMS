/**
 * Loading States and Micro-interactions JavaScript Utility Library
 * For OFFICE_ADMIN pages
 */

class LoadingManager {
    constructor() {
        this.activeLoaders = new Set();
        this.overlayElement = null;
        this.init();
    }

    init() {
        this.createOverlay();
        this.setupGlobalListeners();
    }

    createOverlay() {
        if (!this.overlayElement) {
            this.overlayElement = document.createElement('div');
            this.overlayElement.className = 'loading-overlay';
            this.overlayElement.innerHTML = `
                <div class="loading-overlay-content">
                    <div class="loading-overlay-spinner"></div>
                    <div class="loading-text">Loading...</div>
                </div>
            `;
            document.body.appendChild(this.overlayElement);
        }
    }

    setupGlobalListeners() {
        // Auto-hide overlay on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.overlayElement.style.display === 'flex') {
                this.hideOverlay();
            }
        });
    }

    // Show global loading overlay
    showOverlay(text = 'Loading...') {
        if (this.overlayElement) {
            this.overlayElement.querySelector('.loading-text').textContent = text;
            this.overlayElement.style.display = 'flex';
            this.activeLoaders.add('global');
        }
    }

    // Hide global loading overlay
    hideOverlay() {
        if (this.overlayElement) {
            this.overlayElement.style.display = 'none';
            this.activeLoaders.delete('global');
        }
    }

    // Show loading on button
    showButtonLoading(button, originalText = '') {
        if (!button) return;
        
        button.classList.add('btn-loading');
        button.dataset.originalText = button.textContent;
        if (originalText) {
            button.textContent = originalText;
        }
        button.disabled = true;
        this.activeLoaders.add(button);
    }

    // Hide loading on button
    hideButtonLoading(button) {
        if (!button) return;
        
        button.classList.remove('btn-loading');
        button.textContent = button.dataset.originalText || button.textContent;
        button.disabled = false;
        this.activeLoaders.delete(button);
    }

    // Show loading on card
    showCardLoading(card) {
        if (!card) return;
        
        card.classList.add('card-loading');
        this.activeLoaders.add(card);
    }

    // Hide loading on card
    hideCardLoading(card) {
        if (!card) return;
        
        card.classList.remove('card-loading');
        this.activeLoaders.delete(card);
    }

    // Show loading on table
    showTableLoading(table) {
        if (!table) return;
        
        table.classList.add('table-loading');
        this.activeLoaders.add(table);
    }

    // Hide loading on table
    hideTableLoading(table) {
        if (!table) return;
        
        table.classList.remove('table-loading');
        this.activeLoaders.delete(table);
    }

    // Create skeleton loader
    createSkeletonLoader(container, items = 3, type = 'text') {
        if (!container) return;

        const skeletons = [];
        for (let i = 0; i < items; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = `skeleton skeleton-${type}`;
            container.appendChild(skeleton);
            skeletons.push(skeleton);
        }
        return skeletons;
    }

    // Remove skeleton loaders
    removeSkeletonLoaders(container) {
        if (!container) return;
        
        const skeletons = container.querySelectorAll('.skeleton');
        skeletons.forEach(skeleton => skeleton.remove());
    }

    // Check if any loaders are active
    hasActiveLoaders() {
        return this.activeLoaders.size > 0;
    }

    // Clear all loaders
    clearAllLoaders() {
        this.activeLoaders.forEach(loader => {
            if (loader === 'global') {
                this.hideOverlay();
            } else if (loader.tagName === 'BUTTON') {
                this.hideButtonLoading(loader);
            } else if (loader.classList && loader.classList.contains('card')) {
                this.hideCardLoading(loader);
            } else if (loader.classList && loader.classList.contains('table')) {
                this.hideTableLoading(loader);
            }
        });
        this.activeLoaders.clear();
    }
}

// Micro-interactions Manager
class MicroInteractionsManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupButtonEffects();
        this.setupCardEffects();
        this.setupFormEffects();
        this.setupTableEffects();
        this.setupNotificationEffects();
    }

    setupButtonEffects() {
        // Add ripple effect to buttons
        document.addEventListener('click', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const button = e.target.closest('.interactive-button, .btn');
                if (button && !button.disabled) {
                    this.createRippleEffect(button, e);
                }
            }
        });

        // Add hover sound effect (optional)
        document.addEventListener('mouseover', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const button = e.target.closest('.interactive-button, .btn');
                if (button && !button.disabled) {
                    button.style.transform = 'translateY(-1px)';
                }
            }
        });

        document.addEventListener('mouseout', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const button = e.target.closest('.interactive-button, .btn');
                if (button) {
                    button.style.transform = 'translateY(0)';
                }
            }
        });
    }

    createRippleEffect(button, event) {
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');

        button.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    setupCardEffects() {
        // Add hover effects to cards
        document.addEventListener('mouseover', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const card = e.target.closest('.interactive-card, .card');
                if (card && !card.classList.contains('card-loading')) {
                    card.style.transform = 'translateY(-2px)';
                    card.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                }
            }
        });

        document.addEventListener('mouseout', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const card = e.target.closest('.interactive-card, .card');
                if (card) {
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = '';
                }
            }
        });
    }

    setupFormEffects() {
        // Add focus effects to form fields
        document.addEventListener('focus', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const field = e.target.closest('.form-control, .form-select');
                if (field) {
                    const formGroup = field.closest('.form-field-enhanced, .mb-3');
                    if (formGroup) {
                        formGroup.style.transform = 'translateY(-1px)';
                    }
                }
            }
        }, true);

        document.addEventListener('blur', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const field = e.target.closest('.form-control, .form-select');
                if (field) {
                    const formGroup = field.closest('.form-field-enhanced, .mb-3');
                    if (formGroup) {
                        formGroup.style.transform = 'translateY(0)';
                    }
                }
            }
        }, true);
    }

    setupTableEffects() {
        // Add hover effects to table rows
        document.addEventListener('mouseover', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const row = e.target.closest('tbody tr');
                if (row && !row.classList.contains('table-loading')) {
                    row.style.backgroundColor = 'rgba(91, 194, 242, 0.1)';
                }
            }
        });

        document.addEventListener('mouseout', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const row = e.target.closest('tbody tr');
                if (row) {
                    row.style.backgroundColor = '';
                }
            }
        });
    }

    setupNotificationEffects() {
        // Add entrance animations to notifications
        this.observeNotifications();
    }

    observeNotifications() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (node.classList.contains('alert') || node.classList.contains('notification')) {
                            node.classList.add('notification-enter');
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// AJAX Helper with Loading States
class AjaxHelper {
    constructor(loadingManager) {
        this.loadingManager = loadingManager;
        this.setupInterceptors();
    }

    setupInterceptors() {
        // Intercept fetch requests to show loading states
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const url = args[0];
            
            // Show loading for specific endpoints
            if (this.shouldShowLoading(url)) {
                this.loadingManager.showOverlay('Loading data...');
            }

            try {
                const response = await originalFetch(...args);
                return response;
            } finally {
                // Hide loading overlay
                setTimeout(() => {
                    this.loadingManager.hideOverlay();
                }, 300);
            }
        };
    }

    shouldShowLoading(url) {
        const loadingEndpoints = [
            'api/search',
            'api/get_',
            'dashboard.php',
            'requests.php',
            'notifications.php'
        ];

        return loadingEndpoints.some(endpoint => 
            typeof url === 'string' && url.includes(endpoint)
        );
    }

    // Enhanced fetch with button loading
    async fetchWithButtonLoading(url, options = {}, button = null) {
        if (button) {
            this.loadingManager.showButtonLoading(button, 'Loading...');
        }

        try {
            const response = await fetch(url, options);
            return response;
        } finally {
            if (button) {
                setTimeout(() => {
                    this.loadingManager.hideButtonLoading(button);
                }, 300);
            }
        }
    }

    // Enhanced fetch with table loading
    async fetchWithTableLoading(url, options = {}, table = null) {
        if (table) {
            this.loadingManager.showTableLoading(table);
        }

        try {
            const response = await fetch(url, options);
            return response;
        } finally {
            if (table) {
                setTimeout(() => {
                    this.loadingManager.hideTableLoading(table);
                }, 300);
            }
        }
    }
}

// Form Enhancement Manager
class FormEnhancementManager {
    constructor(loadingManager) {
        this.loadingManager = loadingManager;
        this.init();
    }

    init() {
        this.enhanceForms();
        this.setupValidation();
        this.setupAutoSave();
    }

    enhanceForms() {
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                this.addFormLoading(form);
                this.addProgressIndicator(form);
            });
        });
    }

    addFormLoading(form) {
        form.addEventListener('submit', (e) => {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.disabled) {
                this.loadingManager.showButtonLoading(submitButton, 'Submitting...');
            }
        });
    }

    addProgressIndicator(form) {
        const steps = form.querySelectorAll('.form-step');
        if (steps.length > 1) {
            this.createProgressBar(form, steps);
        }
    }

    createProgressBar(form, steps) {
        const progressContainer = document.createElement('div');
        progressContainer.className = 'progress mb-4';
        progressContainer.style.height = '4px';

        const progressBar = document.createElement('div');
        progressBar.className = 'progress-bar progress-enhanced';
        progressBar.style.width = '0%';

        progressContainer.appendChild(progressBar);
        form.insertBefore(progressContainer, form.firstChild);

        // Update progress on step change
        let currentStep = 0;
        const updateProgress = () => {
            const progress = ((currentStep + 1) / steps.length) * 100;
            progressBar.style.width = `${progress}%`;
        };

        // Add step navigation
        steps.forEach((step, index) => {
            const nextButton = step.querySelector('.next-step');
            const prevButton = step.querySelector('.prev-step');

            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    steps[currentStep].style.display = 'none';
                    currentStep = index + 1;
                    steps[currentStep].style.display = 'block';
                    updateProgress();
                });
            }

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    steps[currentStep].style.display = 'none';
                    currentStep = index - 1;
                    steps[currentStep].style.display = 'block';
                    updateProgress();
                });
            }
        });

        // Initialize first step
        steps.forEach((step, index) => {
            step.style.display = index === 0 ? 'block' : 'none';
        });
        updateProgress();
    }

    setupValidation() {
        document.addEventListener('input', (e) => {
            // Check if target has closest method
            if (e.target && typeof e.target.closest === 'function') {
                const field = e.target.closest('.form-control, .form-select');
                if (field) {
                    this.validateField(field);
                }
            }
        });
    }

    validateField(field) {
        const isValid = field.checkValidity();
        const feedback = field.parentNode.querySelector('.invalid-feedback');

        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            if (feedback) {
                feedback.style.display = 'none';
            }
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            if (feedback) {
                feedback.style.display = 'block';
            }
        }
    }

    setupAutoSave() {
        const forms = document.querySelectorAll('[data-auto-save]');
        forms.forEach(form => {
            let saveTimeout;
            
            form.addEventListener('input', (e) => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    this.autoSaveForm(form);
                }, 2000);
            });
        });
    }

    async autoSaveForm(form) {
        const formData = new FormData(form);
        const saveUrl = form.dataset.autoSave;

        try {
            await fetch(saveUrl, {
                method: 'POST',
                body: formData
            });
            
            this.showAutoSaveIndicator(form, 'saved');
        } catch (error) {
            this.showAutoSaveIndicator(form, 'error');
        }
    }

    showAutoSaveIndicator(form, status) {
        const indicator = form.querySelector('.auto-save-indicator') || 
                         this.createAutoSaveIndicator(form);

        indicator.className = `auto-save-indicator status-${status}`;
        indicator.textContent = status === 'saved' ? 'Saved' : 'Save failed';

        setTimeout(() => {
            indicator.textContent = '';
        }, 3000);
    }

    createAutoSaveIndicator(form) {
        const indicator = document.createElement('div');
        indicator.className = 'auto-save-indicator';
        form.appendChild(indicator);
        return indicator;
    }
}

// Initialize all managers
const loadingManager = new LoadingManager();
const microInteractions = new MicroInteractionsManager();
const ajaxHelper = new AjaxHelper(loadingManager);
const formEnhancement = new FormEnhancementManager(loadingManager);

// Global functions for easy access
window.showLoading = (text) => loadingManager.showOverlay(text);
window.hideLoading = () => loadingManager.hideOverlay();
window.showButtonLoading = (btn, text) => loadingManager.showButtonLoading(btn, text);
window.hideButtonLoading = (btn) => loadingManager.hideButtonLoading(btn);

// Utility functions
function createStatusIndicator(type = 'online') {
    const indicator = document.createElement('span');
    indicator.className = `status-indicator ${type}`;
    return indicator;
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show notification-enter`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, duration);
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LoadingManager,
        MicroInteractionsManager,
        AjaxHelper,
        FormEnhancementManager
    };
}
