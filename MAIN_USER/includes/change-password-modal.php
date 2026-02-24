<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="bi bi-key me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm" method="POST" action="../includes/change_password.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="8">
                        </div>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-key-fill"></i>
                            </span>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="password-strength-indicator mb-3">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%;"></div>
                        </div>
                        <small class="text-muted" id="passwordStrengthText">Enter a password</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="changePasswordBtn">
                        <i class="bi bi-key me-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const changePasswordBtn = document.getElementById('changePasswordBtn');

    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = '';

        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 25;
        if (/[a-z]/.test(password)) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 25;

        strength = Math.min(strength, 100);

        if (strength < 30) {
            feedback = 'Weak';
            strengthBar.className = 'progress-bar bg-danger';
        } else if (strength < 60) {
            feedback = 'Fair';
            strengthBar.className = 'progress-bar bg-warning';
        } else if (strength < 80) {
            feedback = 'Good';
            strengthBar.className = 'progress-bar bg-info';
        } else {
            feedback = 'Strong';
            strengthBar.className = 'progress-bar bg-success';
        }

        strengthBar.style.width = strength + '%';
        strengthText.textContent = feedback;
    }

    newPasswordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
    });

    changePasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const currentPassword = newPasswordInput.value;
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        let isValid = true;

        if (newPassword !== confirmPassword) {
            confirmPasswordInput.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'Passwords do not match';
            confirmPasswordInput.parentNode.appendChild(feedback);
            isValid = false;
        }

        if (newPassword.length < 8) {
            newPasswordInput.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'Password must be at least 8 characters long';
            newPasswordInput.parentNode.appendChild(feedback);
            isValid = false;
        }

        if (!isValid) return;

        // Show loading state
        changePasswordBtn.disabled = true;
        changePasswordBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Changing...';

        const formData = new FormData(changePasswordForm);

        fetch('../includes/change_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
                alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                alert.innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alert);

                // Close modal and reset form
                bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                changePasswordForm.reset();
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Enter a password';
            } else {
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                alert.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alert);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>
                An error occurred. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
        })
        .finally(() => {
            // Reset button state
            changePasswordBtn.disabled = false;
            changePasswordBtn.innerHTML = '<i class="bi bi-key me-2"></i>Change Password';

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.position-fixed').forEach(el => el.remove());
            }, 5000);
        });
    });
});
</script>
