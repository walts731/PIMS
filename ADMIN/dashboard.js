// Dashboard JavaScript functionality

function refreshDashboard() {
    location.reload();
}

function exportData() {
    // Get data from the page instead of PHP variables
    const statCards = document.querySelectorAll('.stat-card');
    const totalItems = parseInt(statCards[0]?.querySelector('.stat-value')?.textContent?.replace(/,/g, '') || 0);
    const totalValue = parseFloat(statCards[1]?.querySelector('.stat-value')?.textContent?.replace(/[^0-9.]/g, '') || 0);
    const employeeCount = parseInt(statCards[2]?.querySelector('.stat-value')?.textContent?.replace(/,/g, '') || 0);
    
    const data = {
        timestamp: new Date().toISOString(),
        assets: {
            total_items: totalItems,
            serviceable: 0,
            in_use: 0,
            red_tagged: 0,
            maintenance: 0,
            value: totalValue
        },
        employees: {
            total: employeeCount,
            active: 0
        },
        forms: {
            total: 0,
            value: 0
        }
    };
    
    let csv = 'Category,Metric,Value\n';
    csv += `Assets,Total Items,${data.assets.total_items}\n`;
    csv += `Assets,Total Value,${data.assets.value}\n`;
    csv += `Employees,Total Count,${data.employees.total}\n`;
    csv += `Forms,Total Count,${data.forms.total}\n`;
    csv += `Forms,Total Value,${data.forms.value}\n`;
    
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
        
        // Get computed CSS variables for Chart.js styling
        const rootStyle = getComputedStyle(document.documentElement);
        const primaryColor = rootStyle.getPropertyValue('--primary-color').trim() || '#1E56A0';
        const primaryRgb = rootStyle.getPropertyValue('--primary-rgb').trim() || '30, 86, 160';
    
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
                        primaryColor,  // Primary color for Borrowed
                        '#6c757d',  // Gray for Disposed
                        '#6c757d'   // Gray for Unserviceable
                    ],
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    },
                    beforeDraw: function(chart) {
                        const ctx = chart.ctx;
                        const width = chart.width;
                        const height = chart.height;
                        
                        ctx.restore();
                        const fontSize = (height / 114).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";
                        
                        const totalAssets = chartData.reduce((a, b) => a + b, 0);
                        const text = totalAssets.toString();
                        const textX = Math.round((width - ctx.measureText(text).width) / 2);
                        const textY = height / 2 - 10;
                        
                        ctx.fillStyle = '#333';
                        ctx.fillText(text, textX, textY);
                        
                        // Add label below the number
                        ctx.font = (fontSize * 0.6) + "em sans-serif";
                        const label = "Total Assets";
                        const labelX = Math.round((width - ctx.measureText(label).width) / 2);
                        const labelY = height / 2 + 10;
                        
                        ctx.fillStyle = '#666';
                        ctx.fillText(label, labelX, labelY);
                        ctx.save();
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
                    label: 'Asset Count',
                    data: officeData.map(o => o.item_count),
                    backgroundColor: `rgba(${primaryRgb}, 0.8)`,
                    borderColor: `rgba(${primaryRgb}, 1)`,
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const officeName = officeData[context.dataIndex].office_name;
                                const count = context.parsed.y;
                                return `${officeName}: ${count} assets`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Offices',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            color: '#333',
                            padding: 10
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#666'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Assets Distribution',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            color: '#333',
                            padding: 10
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#666',
                            stepSize: 1
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
