// Authentication JavaScript for PHP Backend
document.addEventListener('DOMContentLoaded', function () {
    console.log('WorkConnect PH - Auth Page Loaded');

    // ✅ NEW: Retrieve the CSRF token from the meta tag in the HTML head
    const csrfToken = document.querySelector('meta[name="csrf-token"]') 
                      ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                      : null; // Fallback for safety

    if (!csrfToken) {
        console.error("CSRF Token not found! Authentication is vulnerable.");
    }
    
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

    // API helper: prefer modern apiService but fall back to legacy apiCall
    const API = (typeof window !== 'undefined' && window.apiService && typeof window.apiService.post === 'function')
        ? window.apiService
        : { post: async (endpoint, data) => await apiCall(endpoint, data) };

    // Tab Switching
    function switchToLogin() {
        // Update tab styles
        loginTab.classList.add('border-primary-600', 'text-primary-600');
        loginTab.classList.remove('text-gray-500', 'hover:text-gray-700');
        registerTab.classList.remove('border-primary-600', 'text-primary-600');
        registerTab.classList.add('text-gray-500', 'hover:text-gray-700');
        
        // Show/hide forms
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        
        // Update toggle text
        authToggleText.textContent = "Don't have an account?";
        authToggleBtn.textContent = "Sign up";
    }

    function switchToRegister() {
        // Update tab styles
        registerTab.classList.add('border-primary-600', 'text-primary-600');
        registerTab.classList.remove('text-gray-500', 'hover:text-gray-700');
        loginTab.classList.remove('border-primary-600', 'text-primary-600');
        loginTab.classList.add('text-gray-500', 'hover:text-gray-700');
        
        // Show/hide forms
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
        
        // Update toggle text
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

        // 🔑 INTEGRATION: Include CSRF token in the request payload
        const requestData = {
            email: email,
            password: password,
            csrf_token: csrfToken // ADDED CSRF token
        };

    const response = await API.post('auth.php?action=login', requestData);

        try {
            console.log('Login API raw response:', response);

            // Normalize success flag (handles true|1|'1'|'true')
            const successFlag = response && (
                response.success === true ||
                response.success === 1 ||
                response.success === '1' ||
                response.success === 'true'
            );

            if (!response) {
                throw new Error('No response from login API');
            }

            if (successFlag) {
                console.log('Login succeeded, response.data:', response.data);
                Utils.showMessage(loginMsg, '✅ Login successful! Redirecting...', 'success');

                // Redirect based on role with a safe fallback
                setTimeout(() => {
                    // response.data may be an object { role: '...' } or a string 'employer'
                    let role = null;
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            role = String(response.data).toLowerCase();
                        } else if (response.data.role) {
                            role = String(response.data.role).toLowerCase();
                        }
                    }
                    console.log('Resolved role for redirect:', role);

                    const routes = {
                        job_seeker: 'jobseeker.html',
                        employer: 'employer.html',
                        admin: 'admin.html'
                    };

                    const target = routes[role] || 'index.php';
                    window.location.href = target;
                }, 1500);
            } else {
                console.warn('Login failed, message:', response && response.message);
                Utils.showMessage(loginMsg, `❌ ${response && response.message ? response.message : 'Login failed'}`, 'error');
            }
        } catch (err) {
            console.error('Error handling login response:', err);
            Utils.showMessage(loginMsg, '❌ Unexpected error during login. See console for details.', 'error');
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
            user_type: selectedUserType ? selectedUserType.value : formData.get('user_type'),
            csrf_token: csrfToken // ADDED CSRF token
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
            
            const response = await API.post('auth.php?action=register', userData);
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

    // Event listeners are attached to the forms/buttons
    // Note: login form markup uses id="loginSubmit" for the <form> element in login.php
    const loginFormElement = document.getElementById('loginSubmit') || loginForm;
    if (!loginFormElement) {
        console.error('Login form element not found. Expected id="loginSubmit" or form with id loginForm.');
    } else {
        loginFormElement.addEventListener('submit', handleLogin);
    }

    if (!registerForm) {
        console.error('Register form element not found (id="registerSubmit").');
    } else {
        registerForm.addEventListener('submit', handleRegister);
    }

    console.log('Auth page initialized successfully');
});