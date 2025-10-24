<?php
/**
 * WorkConnect PH - Footer Template
 */
?>
    </main>

    <!-- Notification System -->
    <div id="notification" class="fixed top-4 right-4 transform transition-transform duration-300 translate-x-full z-50">
        <div class="flex items-center p-4 text-white rounded-lg shadow-lg">
            <div class="notification-content"></div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl flex flex-col items-center">
            <i class="bi bi-arrow-repeat text-3xl text-primary-600 animate-spin"></i>
            <p class="mt-2 text-gray-700">Loading...</p>
        </div>
    </div>

    <script>
    // Global notification system
    function showNotification(message, type = 'info') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${type} show`;
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    }

    // Loading overlay
    function showLoading() {
        document.getElementById('loadingOverlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.add('hidden');
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

    // AJAX helper (backwards-compatible). If the modern ApiService is available use it,
    // otherwise fall back to the legacy fetch-based implementation.
    async function apiCall(endpoint, data = {}, method = 'POST') {
        // If ApiService is loaded (module or global), proxy to it for unified behavior
        try {
            if (window.apiService && typeof window.apiService.post === 'function') {
                // Map legacy endpoint strings to ApiService path expectations
                // e.g. 'auth.php?action=login' -> 'auth.php?action=login'
                // ApiService expects endpoint relative to /api (it has baseUrl configured)
                const lower = method.toLowerCase();
                if (lower === 'get') {
                    return await window.apiService.get(endpoint, data || {});
                } else if (lower === 'post') {
                    return await window.apiService.post(endpoint, data || {});
                } else if (lower === 'put') {
                    return await window.apiService.put(endpoint, data || {});
                } else if (lower === 'delete') {
                    return await window.apiService.delete(endpoint);
                }
            }
        } catch (e) {
            // If proxying fails for any reason, continue to legacy fallback
            console.warn('apiCall proxy to ApiService failed, using fallback:', e);
        }

        // Legacy fallback (kept for pages that don't load ApiService)
        showLoading();
        try {
            const response = await fetch(`api/${endpoint}`, {
                method: method,
                credentials: 'include', // ensure cookies (session) are sent
                headers: {
                    'Content-Type': 'application/json',
                },
                body: method !== 'GET' ? JSON.stringify(data) : null
            });

            if (!response.ok) {
                let errorBody = null;
                try {
                    errorBody = await response.json();
                } catch (e) {
                    errorBody = await response.text();
                }
                const detail = { endpoint: endpoint, status: response.status, statusText: response.statusText, body: errorBody };
                console.error('API Error Response:', detail);
                try { window.updateApiDebugPanel && window.updateApiDebugPanel(detail); } catch(e) {}
                hideLoading();
                return { success: false, message: (errorBody && errorBody.message) ? errorBody.message : `Server error ${response.status}` };
            }

            let result = null;
            try {
                result = await response.json();
            } catch (e) {
                const text = await response.text();
                const detail = { endpoint: endpoint, status: response.status, statusText: response.statusText, body: text };
                console.error('Failed to parse JSON response for', endpoint, detail);
                try { window.updateApiDebugPanel && window.updateApiDebugPanel(detail); } catch(e) {}
                hideLoading();
                return { success: false, message: 'Invalid server response' };
            }

            hideLoading();
            return result;
        } catch (error) {
            hideLoading();
            const detail = { endpoint: endpoint, error: String(error) };
            try { window.updateApiDebugPanel && window.updateApiDebugPanel(detail); } catch(e) {}
            showNotification('Network error. Please try again.', 'error');
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    }
    </script>
</body>
</html>