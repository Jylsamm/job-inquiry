<?php
$pageTitle = "Reset Password";
$hideNavigation = true;
require_once 'includes/header.php';

// Check if token is provided
$token = $_GET['token'] ?? '';
if (empty($token)) {
    echo "<div class='auth-container'><div class='form-section'><div class='message error'>Invalid or missing reset token.</div><a href='forgot-password.php' class='btn btn-primary'>Request New Reset Link</a></div></div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="auth-container">
    <section class="form-section">
        <div class="logo">
            <img src="assets/images/logoblue.png" alt="WorkConnect PH Logo" class="logo-img">
            <span class="logo-text">WorkConnect</span>
        </div>

        <h1>Set New Password</h1>
        <p>Enter your new password below.</p>

        <form id="resetPasswordForm" class="form">
            <input type="hidden" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="password" class="form-label">New Password</label>
                <input type="password" id="password" name="password" placeholder="Enter new password (min. 8 characters)" required class="form-input" minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required class="form-input" minlength="8">
            </div>
            
            <button type="submit" class="btn btn-primary full">Reset Password</button>
            <div id="message" class="message hidden"></div>
        </form>

        <div class="auth-links">
            <a href="login.php" class="auth-link">Back to Login</a>
        </div>
    </section>

    <aside class="illustration-section">
        <div class="illustration">
           <img src="assets/images/logowhite.png" alt="WorkConnect PH Logo" class="illustration-icon">
        </div>
        <div class="illustration-text">
            <p class="welcome">Secure your account with a new password.</p>
        </div>
    </aside>
</div>

<script>
document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const token = document.getElementById('token').value;
    const csrfToken = document.getElementById('csrf_token').value;

    // Validation
    if (password.length < 8) {
        showNotification('Password must be at least 8 characters long.', 'error');
        return;
    }

    if (password !== confirmPassword) {
        showNotification('Passwords do not match.', 'error');
        return;
    }

    const data = {
        action: 'reset_password',
        token: token,
        password: password,
        csrf_token: csrfToken
    };

    const response = await apiCall('auth.php?action=reset_password', data);
    
    if (response.success) {
        showNotification('Password reset successfully! Redirecting to login...', 'success');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2000);
    } else {
        showNotification(response.message, 'error');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>