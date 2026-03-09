// Dashboard JavaScript functionality

function refreshDashboard() {
    location.reload();
}

function exportData() {
    // Get data from the page instead of PHP variables
    const totalItems = parseInt(document.querySelector('.stat-value')?.textContent?.replace(/,/g, '') || 0);
    const serviceableItems = parseInt(document.querySelector('.stat-sublabel')?.textContent?.match(/\d+/)?.[0] || 0);
    const totalValue = parseFloat(document.querySelectorAll('.stat-value')[1]?.textContent?.replace(/[^0-9.]/g, '') || 0);
    const totalForms = parseInt(document.querySelectorAll('.stat-value')[2]?.textContent?.replace(/,/g, '') || 0);
    const fuelStock = parseInt(document.querySelectorAll('.stat-value')[3]?.textContent?.replace(/,/g, '') || 0);
    
    const data = {
        timestamp: new Date().toISOString(),
        assets: {
            total_items: totalItems,
            serviceable: serviceableItems,
            in_use: 0,
            red_tagged: 0,
            maintenance: 0,
            value: totalValue
        },
        forms: {
            total: totalForms,
            value: 0,
            par: 0,
            ics: 0,
            ris: 0,
            iirup: 0,
            itr: 0
        },
        fuel: {
            stock: fuelStock,
            today_transactions: 0
        }
    };
    
    let csv = 'Category,Metric,Value\n';
    csv += `Assets,Total Items,${data.assets.total_items}\n`;
    csv += `Assets,Serviceable,${data.assets.serviceable}\n`;
    csv += `Assets,Red Tagged,${data.assets.red_tagged}\n`;
    csv += `Assets,Value,${data.assets.value}\n`;
    csv += `Forms,Total Count,${data.forms.total}\n`;
    csv += `Forms,Total Value,${data.forms.value}\n`;
    csv += `Fuel,Stock (L),${data.fuel.stock}\n`;
    csv += `Fuel,Today's Transactions,${data.fuel.today_transactions}\n`;
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `dashboard_export_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Chart initialization
try {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded - Starting chart initialization');
        
        // Debug: Check if Chart is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            console.error('Available window.Chart:', window.Chart);
            return;
        }
    
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.color = '#666';
        
        // Debug: Check if canvas elements exist
        const assetStatusCanvas = document.getElementById('assetStatusChart');
        const officeCanvas = document.getElementById('officeChart');
        
        if (!assetStatusCanvas) {
            console.error('Asset status canvas not found!');
            return;
        }
        
        if (!officeCanvas) {
            console.error('Office canvas not found!');
            return;
        }
        
        console.log('Initializing charts...');
        
        // Get chart data from the page - more specific selectors
        const assetStatusSection = document.querySelector('#assetStatusChart').closest('.section-card');
        const serviceableCount = parseInt(assetStatusSection.querySelector('.text-success')?.textContent?.replace(/,/g, '') || 0);
        const redTaggedCount = parseInt(Array.from(assetStatusSection.querySelectorAll('.text-danger'))[0]?.textContent?.replace(/,/g, '') || 0);
        const maintenanceCount = parseInt(assetStatusSection.querySelector('.text-warning')?.textContent?.replace(/,/g, '') || 0);
        const borrowedCount = parseInt(Array.from(assetStatusSection.querySelectorAll('.text-primary'))[0]?.textContent?.replace(/,/g, '') || 0);
        const disposedCount = parseInt(Array.from(assetStatusSection.querySelectorAll('.text-danger'))[1]?.textContent?.replace(/,/g, '') || 0);
        const unserviceableCount = parseInt(assetStatusSection.querySelector('.text-secondary')?.textContent?.replace(/,/g, '') || 0);
        
        // Ensure all segments are visible by using minimum values for zero counts
        const chartData = [
            serviceableCount || 0.01,
            redTaggedCount || 0.01,
            maintenanceCount || 0.01,
            borrowedCount || 0.01,
            disposedCount || 0.01,
            unserviceableCount || 0.01
        ];
        
        console.log('Asset Status Data:', {
            serviceable: serviceableCount,
            red_tagged: redTaggedCount,
            maintenance: maintenanceCount,
            borrowed: borrowedCount,
            disposed: disposedCount,
            unserviceable: unserviceableCount
        });
        
        const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
        const assetStatusChart = new Chart(assetStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Serviceable', 'Red Tagged', 'Maintenance', 'Borrowed', 'Disposed', 'Unserviceable'],
                datasets: [{
                    data: chartData,
                    backgroundColor: [
                        '#28a745',  // Green for Serviceable
                        '#dc3545',  // Red for Red Tagged
                        '#ffc107',  // Yellow for Maintenance
                        '#6f42c1',  // Purple for Borrowed
                        '#fd7e14',  // Orange for Disposed
                        '#6c757d'   // Gray for Unserviceable
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const originalValues = [serviceableCount, redTaggedCount, maintenanceCount, borrowedCount, disposedCount, unserviceableCount];
                                const value = originalValues[context.dataIndex] || 0;
                                const total = originalValues.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        console.log('Asset status chart created successfully');
        
        const officeCtx = document.getElementById('officeChart').getContext('2d');
        
        // Get office data from the page
        const officeDataScript = document.getElementById('officeData');
        const officeData = officeDataScript ? JSON.parse(officeDataScript.textContent) : [];
        console.log('Office Distribution Data:', officeData);
        const officeChart = new Chart(officeCtx, {
            type: 'bar',
            data: {
                labels: officeData.map(o => o.office_name.substring(0, 15)),
                datasets: [{
                    label: 'Item Value',
                    data: officeData.map(o => o.item_value),
                    backgroundColor: '#5CC2F2',
                    borderRadius: 4,
                    barThickness: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'PHP ' + (value / 1000000).toFixed(1) + 'M';
                            },
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
        
        console.log('Office chart created successfully');
    });
} catch (error) {
    console.error('Chart initialization error:', error);
    console.error('Error stack:', error.stack);
}

// Password visibility toggles
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle functions
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        
        if (toggle && input) {
            toggle.addEventListener('click', function() {
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        }
    }
    
    // Setup password toggles
    setupPasswordToggle('toggleCurrentPassword', 'current_password');
    setupPasswordToggle('toggleNewPassword', 'new_password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    
    // Password validation
    function validatePassword() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchMessage = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('changePasswordBtn');
        
        if (!newPassword || !confirmPassword || !matchMessage || !submitBtn) return;
        
        if (confirmPassword.value === '') {
            matchMessage.textContent = '';
            matchMessage.className = 'form-text';
            submitBtn.disabled = false;
            return;
        }
        
        if (newPassword.value === confirmPassword.value) {
            matchMessage.textContent = 'Passwords match';
            matchMessage.className = 'form-text text-success';
            submitBtn.disabled = false;
        } else {
            matchMessage.textContent = 'Passwords do not match';
            matchMessage.className = 'form-text text-danger';
            submitBtn.disabled = true;
        }
    }
    
    // Setup password validation
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (newPasswordField && confirmPasswordField) {
        newPasswordField.addEventListener('input', validatePassword);
        confirmPasswordField.addEventListener('input', validatePassword);
    }
    
    // Form submission
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('changePasswordBtn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Changing...';
            
            fetch('../change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                    if (modal) modal.hide();
                    
                    // Show success alert
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        <i class="bi bi-check-circle"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // Remove alert after 5 seconds
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                    
                    // Reset form
                    this.reset();
                    validatePassword();
                } else {
                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        <i class="bi bi-exclamation-triangle"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // Remove alert after 5 seconds
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                alertDiv.style.zIndex = '9999';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle"></i> An error occurred. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);
                
                // Remove alert after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
