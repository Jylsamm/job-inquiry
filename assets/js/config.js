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

    // Handle cases where API returns either { data: { role: '...' } } or { data: 'role' }
    const userRole = response.data && (typeof response.data === 'string' ? response.data : response.data.role);
        if (userRole !== requiredRole) {
                    // Logged in but wrong role, redirect to index
                    showNotification('Access Denied. You are not authorized for this page.', 'error');
                    window.location.href = 'index.php';
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
    return response.data && (typeof response.data === 'string' ? response.data : response.data.role) || null;
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

// --- Development-only API debug panel (shows last API response) ---
function ensureApiDebugPanel() {
    if (!CONFIG.DEBUG) return null;
    let panel = document.getElementById('apiDebugPanel');
    if (panel) return panel;
    panel = document.createElement('div');
    panel.id = 'apiDebugPanel';
    panel.style.position = 'fixed';
    panel.style.right = '12px';
    panel.style.bottom = '12px';
    panel.style.zIndex = 99999;
    panel.style.maxWidth = '420px';
    panel.style.maxHeight = '320px';
    panel.style.overflow = 'auto';
    panel.style.background = 'rgba(0,0,0,0.85)';
    panel.style.color = '#fff';
    panel.style.padding = '10px';
    panel.style.fontSize = '12px';
    panel.style.borderRadius = '6px';
    panel.style.boxShadow = '0 4px 12px rgba(0,0,0,0.4)';
    panel.innerText = 'API Debug Panel (development only)';
    document.body.appendChild(panel);
    return panel;
}

function updateApiDebugPanel(detail) {
    if (!CONFIG.DEBUG) return;
    const panel = ensureApiDebugPanel();
    if (!panel) return;
    try {
        panel.innerText = JSON.stringify(detail, null, 2);
    } catch (e) {
        panel.innerText = String(detail);
    }
}

// Expose to global for pages using PHP footer to update the debug panel
window.updateApiDebugPanel = updateApiDebugPanel;

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
            credentials: 'include', // include cookies for session
            headers: {
                'Content-Type': 'application/json',
            },
            body: method !== 'GET' ? JSON.stringify(data) : null
        });
        // If server returned non-2xx, try to parse error body and return structured error
        if (!response.ok) {
            let errorBody = null;
            try {
                errorBody = await response.json();
            } catch (e) {
                errorBody = await response.text();
            }
            const detail = { endpoint: endpoint, status: response.status, statusText: response.statusText, body: errorBody };
            console.error('API Error Response:', detail);
            updateApiDebugPanel(detail);
            if (document.getElementById('loadingOverlay')) {
                hideLoading();
            }
            return { success: false, message: (errorBody && errorBody.message) ? errorBody.message : `Server error ${response.status}` };
        }

        // Parse valid JSON response
        let result = null;
        try {
            result = await response.json();
        } catch (e) {
            // Response wasn't JSON
            const text = await response.text();
            const detail = { endpoint: endpoint, status: response.status, statusText: response.statusText, body: text };
            console.error('Failed to parse JSON response for', endpoint, detail);
            updateApiDebugPanel(detail);
            if (document.getElementById('loadingOverlay')) {
                hideLoading();
            }
            return { success: false, message: 'Invalid server response' };
        }

        if (document.getElementById('loadingOverlay')) {
            hideLoading();
        }
        return result;
    } catch (error) {
        if (document.getElementById('loadingOverlay')) {
            hideLoading();
        }
        const detail = { endpoint: endpoint, error: String(error) };
        console.error('API Error:', detail);
        updateApiDebugPanel(detail);
        showNotification('Network error. Please try again.', 'error');
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
