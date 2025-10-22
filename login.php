<?php
/**
 * WorkConnect PH - Login Page
 */

// Load core config/functions early so we can check session and redirect before sending HTML
require_once 'includes/config.php';

// If already logged in, redirect users away from the login page to their dashboard/home
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === 'employer') {
        redirect('employer.html');
    } elseif ($role === 'job_seeker') {
        redirect('jobseeker.html');
    } elseif ($role === 'admin') {
        redirect('admin.php');
    } else {
        redirect('index.php');
    }
}

$pageTitle = "Login";
$hideNavigation = true;
require_once 'includes/header.php';
?>
<div class="auth-container">
    <section class="form-section">
        <div class="logo">
            <img src="assets/images/logoblue.png" alt="WorkConnect PH Logo" class="logo-img">
            <span class="logo-text">WorkConnect</span>
        </div>

        <div class="auth-tabs">
            <button class="auth-tab active" data-tab="login">Log in</button>
            <button class="auth-tab" data-tab="register">Sign up</button>
        </div>

        <!-- Login Form -->
        <div id="loginForm" class="auth-form active">
            <h1>Welcome back</h1>
            <form id="loginSubmit" class="form">
                <div class="form-group">
                    <label for="loginEmail" class="form-label">Email</label>
                    <input type="email" id="loginEmail" name="email" placeholder="Enter your email" required class="form-input">
                </div>
                <div class="form-group">
                    <label for="loginPassword" class="form-label">Password</label>
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required class="form-input">
                </div>
                <!-- Add this after the login form in login.php -->
                <div class="auth-links">
                    <a href="forgot-password.php" class="auth-link">Forgot your password?</a>
                </div>
                <button type="submit" class="btn btn-primary full">Log in</button>
                <div id="loginMsg" class="message hidden"></div>
            </form>
        </div>

        <!-- Registration Form -->
        <div id="registerForm" class="auth-form">
            <h1>Create your account</h1>
            
            <div class="role-selection">
                <p class="label">I am a:</p>
                <div class="role-options">
                    <label class="role-option">
                        <input type="radio" name="user_type" value="job_seeker" checked>
                        <div class="role-card">
                            <i class="bi bi-person-badge"></i>
                            <span>Job Seeker</span>
                        </div>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="user_type" value="employer">
                        <div class="role-card">
                            <i class="bi bi-building"></i>
                            <span>Employer</span>
                        </div>
                    </label>
                </div>
            </div>

            <form id="registerSubmit" class="form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" id="firstName" name="first_name" placeholder="Enter your first name" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" id="lastName" name="last_name" placeholder="Enter your last name" required class="form-input">
                    </div>
                </div>
                
                <div class="form-group" id="companyNameField" style="display: none;">
                    <label for="companyName" class="form-label">Company Name</label>
                    <input type="text" id="companyName" name="company_name" placeholder="Enter your company name" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="registerEmail" class="form-label">Email</label>
                    <input type="email" id="registerEmail" name="email" placeholder="Enter your email" required class="form-input">
                </div>
                <div class="form-group">
                    <label for="registerPassword" class="form-label">Password</label>
                    <input type="password" id="registerPassword" name="password" placeholder="Create a password (min. 8 characters)" required class="form-input">
                </div>
                <div class="form-group">
                    <label for="phone" class="form-label">Phone (Optional)</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" class="form-input">
                </div>
                <button type="submit" class="btn btn-primary full">Create Account</button>
                <div id="registerMsg" class="message hidden"></div>
            </form>
        </div>
    </section>

    <aside class="illustration-section">
        <div class="illustration">
           <img src="assets/images/logowhite.png" alt="WorkConnect PH Logo" class="illustration-icon">
        </div>
        <div class="illustration-text">
            <p class="welcome">Welcome to the future of work.</p>
            <p class="toggle">
                <span id="authToggleText">Already have an account?</span>&nbsp;
                <button id="authToggleBtn" class="toggle-btn">Sign in</button>
            </p>
        </div>
    </aside>
</div>

<script src="assets/js/auth.js"></script>


<?php require_once 'includes/footer.php'; ?>