// Authentication JavaScript for PHP Backend
document.addEventListener('DOMContentLoaded', function () {
    console.log('WorkConnect PH - Auth Page Loaded');

    // DOM Elements
    const loginTab = document.querySelector('[data-tab="login"]');
    const registerTab = document.querySelector('[data-tab="register"]');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const authToggleBtn = document.getElementById('authToggleBtn');
    const authToggleText = document.getElementById('authToggleText');
    const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
    const companyNameField = document.getElementById('companyNameField');
    const loginSubmit = document.getElementById('loginSubmit');
    const registerSubmit = document.getElementById('registerSubmit');
    const loginMsg = document.getElementById('loginMsg');
    const registerMsg = document.getElementById('registerMsg');

    // Tab Switching
    function switchToLogin() {
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
        loginForm.classList.add('active');
        registerForm.classList.remove('active');
        authToggleText.textContent = "Don't have an account?";
        authToggleBtn.textContent = "Sign up";
    }

    function switchToRegister() {
        registerTab.classList.add('active');
        loginTab.classList.remove('active');
        registerForm.classList.add('active');
        loginForm.classList.remove('active');
        authToggleText.textContent = "Already have an account?";
        authToggleBtn.textContent = "Sign in";
    }

    // User type selection
    userTypeRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value === 'employer') {
                companyNameField.style.display = 'block';
                document.getElementById('companyName').required = true;
            } else {
                companyNameField.style.display = 'none';
                document.getElementById('companyName').required = false;
            }
        });
    });

    // Form Submission Handlers
    async function handleLogin(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const email = formData.get('email');
        const password = formData.get('password');

        // Validation
        if (!Utils.validateEmail(email)) {
            Utils.showMessage(loginMsg, 'Please enter a valid email address', 'error');
            return;
        }

        if (!password) {
            Utils.showMessage(loginMsg, 'Please enter your password', 'error');
            return;
        }

        Utils.showMessage(loginMsg, '🔄 Logging in...', 'info');

        const response = await apiCall('auth.php?action=login', {
            email: email,
            password: password
        });

        if (response.success) {
            Utils.showMessage(loginMsg, '✅ Login successful! Redirecting...', 'success');

            // Redirect based on role
            setTimeout(() => {
                const role = response.data;
                if (role === 'job_seeker') {
                    window.location.href = 'jobseeker.html';
                } else if (role === 'employer') {
                    window.location.href = 'employer.html';
                } else if (role === 'admin') {
                    window.location.href = 'admin.php';
                } else {
                    window.location.href = 'home.php';
                }
            }, 1500);
        } else {
            Utils.showMessage(loginMsg, `❌ ${response.message}`, 'error');
        }
    }

    async function handleRegister(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    
    // DEBUG: Check all form data
    console.log("=== DEBUG REGISTRATION FORM DATA ===");
    for (let [key, value] of formData.entries()) {
        console.log(key + ": " + value);
    }
    
    // Get the selected user_type from radio buttons
    const selectedUserType = document.querySelector('input[name="user_type"]:checked');
    console.log("Selected user_type radio:", selectedUserType ? selectedUserType.value : "none");
    
    const userData = {
        email: formData.get('email'),
        password: formData.get('password'),
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        phone: formData.get('phone'),
        user_type: selectedUserType ? selectedUserType.value : formData.get('user_type')
    };

    console.log("Final userData being sent:", userData);

    // Add company name for employers
    if (userData.user_type === 'employer') {
        userData.company_name = formData.get('company_name');
        console.log("Company name:", userData.company_name);
    }

    // Validation
    if (!userData.first_name || !userData.last_name) {
        Utils.showMessage(registerMsg, 'Please enter your name', 'error');
        return;
    }

    if (!Utils.validateEmail(userData.email)) {
        Utils.showMessage(registerMsg, 'Please enter a valid email address', 'error');
        return;
    }

    if (!Utils.validatePassword(userData.password)) {
        Utils.showMessage(registerMsg, 'Password must be at least 8 characters', 'error');
        return;
    }

    // Check if user_type is missing
    if (!userData.user_type) {
        Utils.showMessage(registerMsg, 'Please select a user type (Job Seeker or Employer)', 'error');
        return;
    }

    if (userData.user_type === 'employer' && !userData.company_name) {
        Utils.showMessage(registerMsg, 'Please enter your company name', 'error');
        return;
    }

    Utils.showMessage(registerMsg, '🔄 Creating account...', 'info');

    try {
        console.log("Sending registration data to API:", userData);
        
        const response = await apiCall('auth.php?action=register', userData);
        console.log("API Response:", response);

        if (response.success) {
            Utils.showMessage(registerMsg, '✅ Account created successfully!', 'success');

            // Auto-login after successful registration
            setTimeout(() => {
                switchToLogin();
                document.getElementById('loginEmail').value = userData.email;
                document.getElementById('loginPassword').value = userData.password;
                Utils.showMessage(loginMsg, '✅ Account created! Please click Login.', 'success');
            }, 1500);
        } else {
            Utils.showMessage(registerMsg, `❌ ${response.message}`, 'error');
        }
    } catch (error) {
        console.error("Registration error:", error);
        Utils.showMessage(registerMsg, `❌ Registration failed: ${error.message}`, 'error');
    }
}

    // Event Listeners
    loginTab.addEventListener('click', switchToLogin);
    registerTab.addEventListener('click', switchToRegister);

    authToggleBtn.addEventListener('click', function () {
        if (registerForm.classList.contains('active')) {
            switchToLogin();
        } else {
            switchToRegister();
        }
    });

    loginSubmit.addEventListener('submit', handleLogin);
    registerSubmit.addEventListener('submit', handleRegister);

    console.log('Auth page initialized successfully');
});