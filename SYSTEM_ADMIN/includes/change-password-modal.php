<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-key"></i> Change Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../change_password.php" id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 8 characters long</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text" id="passwordMatch"></div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Password Requirements:</strong>
                        <ul class="mb-0 mt-2">
                            <li>At least 8 characters long</li>
                            <li>Include both letters and numbers</li>
                            <li>Consider using special characters for security</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="changePasswordBtn">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

