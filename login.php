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
<div class="min-h-screen flex bg-gray-50">
    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div class="flex items-center mb-8">
                <img src="assets/images/logoblue.png" alt="WorkConnect PH Logo" class="h-12 w-auto">
                <span class="ml-3 text-xl font-semibold text-primary-600">WorkConnect</span>
            </div>

            <div class="flex border-b border-gray-200 mb-8">
                <button class="auth-tab px-6 py-3 font-medium text-sm transition-colors border-b-2 border-primary-600 text-primary-600" data-tab="login">Log in</button>
                <button class="auth-tab px-6 py-3 font-medium text-sm transition-colors text-gray-500 hover:text-gray-700" data-tab="register">Sign up</button>
            </div>

            <!-- Login Form -->
            <div id="loginForm" class="auth-form space-y-6">
                <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
                <form id="loginSubmit" class="space-y-6" action="api/auth.php?action=login" method="POST">
                    <input type="hidden" name="csrf_token" id="login_csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div>
                        <label for="loginEmail" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="loginEmail" name="email" placeholder="Enter your email" required 
                               class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                      focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="loginPassword" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required 
                               class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                      focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="forgot-password.php" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                            Forgot your password?
                        </a>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Log in
                    </button>
                    
                    <div id="loginMsg" class="hidden"></div>
                </form>
            </div>

            <!-- Registration Form -->
            <div id="registerForm" class="auth-form hidden space-y-6">
                <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
                
                <div class="space-y-4">
                    <p class="text-sm font-medium text-gray-700">I am a:</p>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex flex-col bg-white p-4 border rounded-lg cursor-pointer focus-within:ring-2 focus-within:ring-primary-500">
                            <input type="radio" name="user_type" value="job_seeker" checked class="sr-only peer">
                            <div class="flex flex-col items-center peer-checked:text-primary-600">
                                <i class="bi bi-person-badge text-2xl mb-2"></i>
                                <span class="text-sm font-medium">Job Seeker</span>
                            </div>
                        </label>
                        <label class="relative flex flex-col bg-white p-4 border rounded-lg cursor-pointer focus-within:ring-2 focus-within:ring-primary-500">
                            <input type="radio" name="user_type" value="employer" class="sr-only peer">
                            <div class="flex flex-col items-center peer-checked:text-primary-600">
                                <i class="bi bi-building text-2xl mb-2"></i>
                                <span class="text-sm font-medium">Employer</span>
                            </div>
                        </label>
                    </div>
                </div>

                <form id="registerSubmit" class="space-y-6" action="api/auth.php?action=register" method="POST">
                    <input type="hidden" name="csrf_token" id="register_csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="firstName" name="first_name" placeholder="Enter your first name" required 
                                   class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                          focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="lastName" name="last_name" placeholder="Enter your last name" required 
                                   class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                          focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        </div>
                    </div>
                    
                    <div id="companyNameField" class="hidden">
                        <label for="companyName" class="block text-sm font-medium text-gray-700">Company Name</label>
                        <input type="text" id="companyName" name="company_name" placeholder="Enter your company name" 
                               class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                      focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="registerEmail" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="registerEmail" name="email" placeholder="Enter your email" required 
                               class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                      focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="registerPassword" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="registerPassword" name="password" placeholder="Create a password (min. 8 characters)" required 
                               class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                      focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone (Optional)</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" 
                               class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md text-sm shadow-sm placeholder-gray-400
                                      focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Create Account
                    </button>
                    
                    <div id="registerMsg" class="hidden"></div>
                </form>
            </div>
        </div>
    </div>

    <div class="hidden lg:block relative w-0 flex-1 bg-gradient-to-br from-primary-600 to-secondary">
        <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-12">
            <img src="assets/images/logowhite.png" alt="WorkConnect PH Logo" class="h-24 w-auto mb-8">
            <p class="text-2xl font-semibold mb-4">Welcome to the future of work.</p>
            <p class="flex items-center space-x-2">
                <span id="authToggleText" class="text-white/80">Already have an account?</span>
                <button id="authToggleBtn" class="text-white underline hover:text-white/90">Sign in</button>
            </p>
        </div>
    </div>
</div>

<script src="assets/js/auth.js"></script>


<?php require_once 'includes/footer.php'; ?>