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
                headers: {
                    'Content-Type': 'application/json',
                },
                body: method !== 'GET' ? JSON.stringify(data) : null
            });
            
            const result = await response.json();
            hideLoading();
            return result;
        } catch (error) {
            hideLoading();
            showNotification('Network error. Please try again.', 'error');
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    }
    </script>
</body>
</html>