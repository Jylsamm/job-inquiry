// WorkConnect PH - Configuration for PHP Backend
const CONFIG = {
    API_BASE: window.location.origin + '/job-inquiry/api',
    DEBUG: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1',

    // API Endpoints
    ENDPOINTS: {
        AUTH: {
            LOGIN: 'auth.php?action=login',
            REGISTER: 'auth.php?action=register',
            LOGOUT: 'auth.php?action=logout',
            CHECK: 'auth.php?action=check'
        },
        JOBS: {
            FEATURED: 'jobs.php?action=featured',
            SEARCH: 'jobs.php?action=search',
            DETAILS: 'jobs.php?action=details',
            CATEGORIES: 'jobs.php?action=categories',
            APPLY: 'jobs.php?action=apply',
            SAVE: 'jobs.php?action=save'
        },
        APPLICATIONS: {
            MY_APPLICATIONS: 'applications.php?action=my_applications',
            EMPLOYER_APPLICATIONS: 'applications.php?action=employer_applications'
        }
    },

    // Job types for dropdowns
    JOB_TYPES: {
        'full_time': 'Full Time',
        'part_time': 'Part Time',
        'contract': 'Contract',
        'internship': 'Internship',
        'remote': 'Remote',
        'hybrid': 'Hybrid'
    },

    // Application statuses
    APPLICATION_STATUS: {
        'submitted': 'Submitted',
        'under_review': 'Under Review',
        'shortlisted': 'Shortlisted',
        'interview': 'Interview',
        'rejected': 'Rejected',
        'accepted': 'Accepted',
        'withdrawn': 'Withdrawn'
    }
};

// Utility functions
const Utils = {
    escapeHTML: (str) => {
        if (typeof str !== 'string') return str;
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    showMessage: (element, message, type = 'info') => {
        if (!element) return;
        element.textContent = message;
        element.className = `message ${type}`;
        element.classList.remove('hidden');

        if (type === 'success') {
            setTimeout(() => {
                element.classList.add('hidden');
            }, 5000);
        }
    },

    validateEmail: (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    validatePassword: (password) => {
        return password && password.length >= 8;
    },

    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Format currency
    formatCurrency: (amount, currency = 'PHP') => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    // Format date
    formatDate: (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Get job type label
    getJobTypeLabel: (jobType) => {
        return CONFIG.JOB_TYPES[jobType] || jobType;
    },

    // Get application status label
    getStatusLabel: (status) => {
        return CONFIG.APPLICATION_STATUS[status] || status;
    },
    
    // --- NEW / REPLACED AUTH UTILITIES ---
    
    // Checks session via API and redirects if necessary
    requireRole: async (requiredRole) => {
        showLoading();
        const response = await apiCall(CONFIG.ENDPOINTS.AUTH.CHECK, {}, 'GET');
        hideLoading();
        
        if (!response.success || !response.data.logged_in) {
            // Not logged in, redirect to login page
            window.location.href = 'login.php';
            return false;
        }

        const userRole = response.data.role;
        if (userRole !== requiredRole) {
            // Logged in but wrong role, redirect to home
            showNotification('Access Denied. You are not authorized for this page.', 'error');
            window.location.href = 'home.php';
            return false;
        }
        
        // Logged in and correct role
        return true;
    },
    
    // Checks if the user is currently logged in (based on API response)
    isLoggedIn: async () => {
        const response = await apiCall(CONFIG.ENDPOINTS.AUTH.CHECK, {}, 'GET');
        return response.success && response.data.logged_in;
    },
    
    // Retrieves the user role from the API check
    getUserRole: async () => {
        const response = await apiCall(CONFIG.ENDPOINTS.AUTH.CHECK, {}, 'GET');
        return response.data.role || null;
    },
    
    // Utility to get PageManager (needs Modal component utility)
    PageManager: class PageManager {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            this.pages = {};
        }

        registerPage(id, element) {
            this.pages[id] = element;
        }

        showPage(id) {
            Object.values(this.pages).forEach(page => {
                page.style.display = 'none';
            });
            if (this.pages[id]) {
                this.pages[id].style.display = 'block';
            }
        }
    },
    
    // Utility to manage Modals (since modal.js is missing but implied)
    modalManager: {
        register: (modalId) => {
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.error(`Modal with ID ${modalId} not found.`);
                return { open: () => {}, close: () => {} }; // Return a dummy object
            }
            
            const closeBtn = modal.querySelector('.close-modal');

            const open = () => {
                modal.setAttribute('aria-hidden', 'false');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            };
            
            const close = () => {
                modal.setAttribute('aria-hidden', 'true');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            };
            
            closeBtn?.addEventListener('click', close);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) close();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.style.display === 'flex') close();
            });
            
            return { open, close };
        }
    }
};

// Global notification system (will be used by all pages)
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    if (notification) {
        notification.textContent = message;
        notification.className = `notification ${type} show`;

        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    } else {
        // Fallback for pages without the PHP footer (like HTML dashboards)
        console.log(`[${type.toUpperCase()}] ${message}`);
        alert(message);
    }
}

// Loading overlay functions
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('hidden');
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('hidden');
}

// API call function
async function apiCall(endpoint, data = {}, method = 'POST') {
    // Only show loading for dashboard pages which don't have the PHP footer
    if (document.getElementById('loadingOverlay')) {
        showLoading();
    }

    try {
        const response = await fetch(`${CONFIG.API_BASE}/${endpoint}`, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: method !== 'GET' ? JSON.stringify(data) : null
        });

        const result = await response.json();
        if (document.getElementById('loadingOverlay')) {
            hideLoading();
        }
        return result;
    } catch (error) {
        if (document.getElementById('loadingOverlay')) {
            hideLoading();
        }
        showNotification('Network error. Please try again.', 'error');
        console.error('API Error:', error);
        return { success: false, message: 'Network error' };
    }
}

// Handle API responses
function handleApiResponse(response, successCallback = null, errorCallback = null) {
    if (response.success) {
        if (successCallback) successCallback(response.data);
        showNotification(response.message, 'success');
    } else {
        if (errorCallback) errorCallback(response.message);
        showNotification(response.message, 'error');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Auto-hide notifications after 5 seconds
    const notifications = document.querySelectorAll('.message:not(.hidden)');
    notifications.forEach(notification => {
        if (!notification.classList.contains('error')) {
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 5000);
        }
    });

    if (CONFIG.DEBUG) {
        console.log('WorkConnect PH - Frontend initialized');
    }
});

// Make utils globally available
window.Utils = Utils;
window.CONFIG = CONFIG;
