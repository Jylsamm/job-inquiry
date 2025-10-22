<?php
$pageTitle = "Forgot Password";
$hideNavigation = true;
require_once 'includes/header.php';
?>

<div class="auth-container">
    <section class="form-section">
        <div class="logo">
            <img src="assets/images/logoblue.png" alt="WorkConnect PH Logo" class="logo-img">
            <span class="logo-text">WorkConnect</span>
        </div>

        <h1>Reset Your Password</h1>
        <p>Enter your email address and we'll send you a link to reset your password.</p>

        <form id="forgotPasswordForm" class="form">
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required class="form-input">
            </div>
            
            <button type="submit" class="btn btn-primary full">Send Reset Link</button>
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
            <p class="welcome">Secure access to your account.</p>
        </div>
    </aside>
</div>

<script>
document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        action: 'forgot_password',
        email: formData.get('email'),
        csrf_token: formData.get('csrf_token')
    };

    const response = await apiCall('auth.php?action=forgot_password', data);
    
    if (response.success) {
        showNotification('Password reset link sent! Check your email.', 'success');
        if (response.data) {
            console.log('Debug URL:', response.data); // Remove in production
        }
    } else {
        showNotification(response.message, 'error');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>