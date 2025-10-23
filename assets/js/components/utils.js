/**
 * Utility functions for common operations
 */
export const Utils = {
    /**
     * Show a message to the user
     * @param {HTMLElement} element - The element to show the message in
     * @param {string} message - The message to show
     * @param {string} type - The type of message (success, error)
     */
    showMessage(element, message, type = 'success') {
        element.textContent = message;
        element.className = `alert alert-${type}`;
        element.style.display = 'block';
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    },

    /**
     * Format a date string
     * @param {string} dateString - The date string to format
     * @returns {string} The formatted date
     */
    formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    },

    /**
     * Format currency
     * @param {number} amount - The amount to format
     * @param {string} currency - The currency code (default: PHP)
     * @returns {string} The formatted currency
     */
    formatCurrency(amount, currency = 'PHP') {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    /**
     * Validate email format
     * @param {string} email - The email to validate
     * @returns {boolean} Whether the email is valid
     */
    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    /**
     * Validate password strength
     * @param {string} password - The password to validate
     * @returns {boolean} Whether the password meets requirements
     */
    isValidPassword(password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
        const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        return regex.test(password);
    },

    /**
     * Escape HTML to prevent XSS
     * @param {string} html - The string to escape
     * @returns {string} The escaped string
     */
    escapeHTML(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    },

    /**
     * Debounce function for search inputs
     * @param {Function} func - The function to debounce
     * @param {number} wait - The time to wait in milliseconds
     * @returns {Function} The debounced function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};