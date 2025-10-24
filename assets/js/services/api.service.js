// API Service for handling all API requests
class ApiService {
    static #instance = null;
    #baseUrl = '/job-inquiry/api';
    #token = null;

    constructor() {
        this.#token = localStorage.getItem('token');
        this.timeout = 30000; // default timeout 30s
        this.retries = 2; // number of retries on network failure
    }

    static getInstance() {
        if (!ApiService.#instance) {
            ApiService.#instance = new ApiService();
        }
        return ApiService.#instance;
    }

    #getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        if (this.#token) {
            headers['Authorization'] = `Bearer ${this.#token}`;
        }
        
        return headers;
    }

    async #handleResponse(response) {
        const contentType = response.headers.get('content-type');
        const isJson = contentType && contentType.includes('application/json');
        const data = isJson ? await response.json() : await response.text();

        if (!response.ok) {
            throw new Error(isJson ? data.message : 'Network response was not ok');
        }

        return data;
    }

    // Public wrapper for other modules that need to call this
    async handleResponse(response) {
        return await this.#handleResponse(response);
    }

    // Internal fetch with timeout and retry
    async #fetchWithTimeout(url, opts = {}, timeout = null, retries = 0) {
        timeout = timeout || this.timeout;
        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), timeout);
        opts.signal = controller.signal;

        try {
            const res = await fetch(url, opts);
            clearTimeout(id);
            return res;
        } catch (err) {
            clearTimeout(id);
            if (retries > 0 && (err.name === 'AbortError' || err.name === 'TypeError')) {
                // Simple backoff
                await new Promise(r => setTimeout(r, 500));
                return this.#fetchWithTimeout(url, opts, timeout, retries - 1);
            }
            throw err;
        }
    }

    // Upload files using FormData, keep headers minimal so browser sets boundary
    async upload(endpoint, formData) {
        try {
            const opts = {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            const token = this.#token || localStorage.getItem('token');
            if (token) opts.headers['Authorization'] = `Bearer ${token}`;

            const response = await fetch(`${this.#baseUrl}/${endpoint}`, opts);
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Upload Error:', error);
            throw error;
        }
    }

    async get(endpoint, params = {}) {
        const url = new URL(`${this.#baseUrl}/${endpoint}`);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        try {
            const response = await this.#fetchWithTimeout(url, {
                method: 'GET',
                headers: this.#getHeaders()
            }, this.timeout, this.retries);
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Get Error:', error);
            throw error;
        }
    }

    async post(endpoint, data = {}) {
        try {
            const response = await this.#fetchWithTimeout(`${this.#baseUrl}/${endpoint}`, {
                method: 'POST',
                headers: this.#getHeaders(),
                body: JSON.stringify(data)
            }, this.timeout, this.retries);
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Post Error:', error);
            throw error;
        }
    }

    async put(endpoint, data = {}) {
        try {
            const response = await this.#fetchWithTimeout(`${this.#baseUrl}/${endpoint}`, {
                method: 'PUT',
                headers: this.#getHeaders(),
                body: JSON.stringify(data)
            }, this.timeout, this.retries);
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Put Error:', error);
            throw error;
        }
    }

    async delete(endpoint) {
        try {
            const response = await this.#fetchWithTimeout(`${this.#baseUrl}/${endpoint}`, {
                method: 'DELETE',
                headers: this.#getHeaders()
            }, this.timeout, this.retries);
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Delete Error:', error);
            throw error;
        }
    }

    setToken(token) {
        this.#token = token;
        if (token) {
            localStorage.setItem('token', token);
        } else {
            localStorage.removeItem('token');
        }
    }
}

// Job Service for handling job-related operations
class JobService {
    #api = ApiService.getInstance();

    async searchJobs(params) {
        return await this.#api.get('jobs/search', params);
    }

    async getJobDetails(jobId) {
        return await this.#api.get(`jobs/${jobId}`);
    }

    async applyForJob(jobId, application) {
        return await this.#api.post(`jobs/${jobId}/apply`, application);
    }

    async saveJob(jobId) {
        return await this.#api.post(`jobs/${jobId}/save`);
    }
}

// Auth Service for handling authentication
class AuthService {
    #api = ApiService.getInstance();

    async login(credentials) {
        const response = await this.#api.post('auth/login', credentials);
        if (response.token) {
            this.#api.setToken(response.token);
        }
        return response;
    }

    async register(userData) {
        return await this.#api.post('auth/register', userData);
    }

    async logout() {
        await this.#api.post('auth/logout');
        this.#api.setToken(null);
    }

    async forgotPassword(email) {
        return await this.#api.post('auth/forgot-password', { email });
    }

    async resetPassword(token, newPassword) {
        return await this.#api.post('auth/reset-password', {
            token,
            password: newPassword
        });
    }

    isAuthenticated() {
        return !!localStorage.getItem('token');
    }
}

// Profile Service for handling user profiles
class ProfileService {
    #api = ApiService.getInstance();

    async getProfile() {
        return await this.#api.get('profiles/me');
    }

    async updateProfile(profileData) {
        return await this.#api.put('profiles/me', profileData);
    }

    async uploadResume(file) {
        const formData = new FormData();
        formData.append('resume', file);

        return await this.#api.upload('profiles/resume', formData);
    }
}

// Export services
const _apiService = ApiService.getInstance();
const _jobService = new JobService();
const _authService = new AuthService();
const _profileService = new ProfileService();

// Expose to module consumers
export const apiService = _apiService;
export const jobService = _jobService;
export const authService = _authService;
export const profileService = _profileService;

// Also expose globally for legacy pages that don't load ES modules
try {
    window.apiService = _apiService;
    window.jobService = _jobService;
    window.authService = _authService;
    window.profileService = _profileService;
} catch (e) {
    // window may be undefined in some bundlers/environments
}