<?php
/**
 * WorkConnect PH - Footer Template
 */
?>
    </main>

    <!-- Notification System -->
    <div id="notification" class="notification hidden"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="loading-spinner">
            <i class="bi bi-arrow-repeat"></i>
            <p>Loading...</p>
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

    // AJAX helper
    async function apiCall(endpoint, data = {}, method = 'POST') {
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