<?php
$pageTitle = "Verify Email";
$hideNavigation = true;
require_once 'includes/header.php';

// Check if token is provided
$token = $_GET['token'] ?? '';
?>

<div class="auth-container">
    <section class="form-section">
        <div class="logo">
            <img src="assets/images/logoblue.png" alt="WorkConnect PH Logo" class="logo-img">
            <span class="logo-text">WorkConnect</span>
        </div>

        <div id="verificationResult">
            <?php if (empty($token)): ?>
                <h1>Email Verification</h1>
                <div class="message error">
                    <p>Invalid verification link. Please check your email for the correct verification link.</p>
                </div>
                <div class="auth-links">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <a href="forgot-password.php" class="auth-link">Need help?</a>
                </div>
            <?php else: ?>
                <h1>Verifying Your Email</h1>
                <div class="text-center">
                    <div class="loading-spinner" style="background: transparent; box-shadow: none;">
                        <i class="bi bi-arrow-repeat" style="color: var(--accent);"></i>
                        <p>Verifying your email address...</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <aside class="illustration-section">
        <div class="illustration">
           <img src="assets/images/logowhite.png" alt="WorkConnect PH Logo" class="illustration-icon">
        </div>
        <div class="illustration-text">
            <p class="welcome">Securing your account access.</p>
        </div>
    </aside>
</div>

<?php if (!empty($token)): ?>
<script>
// Auto-verify email when page loads with token
document.addEventListener('DOMContentLoaded', async function() {
    const token = '<?php echo $token; ?>';
    const csrfToken = '<?php echo $csrf_token; ?>';
    
    try {
        const response = await apiCall('auth.php?action=verify_email', {
            token: token,
            csrf_token: csrfToken
        });

        const resultDiv = document.getElementById('verificationResult');
        
        if (response.success) {
            resultDiv.innerHTML = `
                <h1>Email Verified!</h1>
                <div class="message success">
                    <p>${response.message}</p>
                    <p>Your email has been successfully verified. You can now access all features of your account.</p>
                </div>
                <div class="auth-links">
                    <a href="login.php" class="btn btn-primary">Continue to Login</a>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <h1>Verification Failed</h1>
                <div class="message error">
                    <p>${response.message}</p>
                    <p>The verification link may have expired or is invalid.</p>
                </div>
                <div class="auth-links">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <a href="forgot-password.php" class="auth-link">Need help?</a>
                </div>
            `;
        }
    } catch (error) {
        const resultDiv = document.getElementById('verificationResult');
        resultDiv.innerHTML = `
            <h1>Verification Error</h1>
            <div class="message error">
                <p>An error occurred during verification. Please try again later.</p>
            </div>
            <div class="auth-links">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        `;
    }
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>