// API Service for handling all API requests
class ApiService {
    static #instance = null;
    #baseUrl = '/job-inquiry/api';
    #token = null;

    constructor() {
        this.#token = localStorage.getItem('token');
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

    async get(endpoint, params = {}) {
        const url = new URL(`${this.#baseUrl}/${endpoint}`);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: this.#getHeaders()
            });
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Get Error:', error);
            throw error;
        }
    }

    async post(endpoint, data = {}) {
        try {
            const response = await fetch(`${this.#baseUrl}/${endpoint}`, {
                method: 'POST',
                headers: this.#getHeaders(),
                body: JSON.stringify(data)
            });
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Post Error:', error);
            throw error;
        }
    }

    async put(endpoint, data = {}) {
        try {
            const response = await fetch(`${this.#baseUrl}/${endpoint}`, {
                method: 'PUT',
                headers: this.#getHeaders(),
                body: JSON.stringify(data)
            });
            return await this.#handleResponse(response);
        } catch (error) {
            console.error('API Put Error:', error);
            throw error;
        }
    }

    async delete(endpoint) {
        try {
            const response = await fetch(`${this.#baseUrl}/${endpoint}`, {
                method: 'DELETE',
                headers: this.#getHeaders()
            });
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

        const response = await fetch(`${this.#api.baseUrl}/profiles/resume`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: formData
        });

        return this.#api.handleResponse(response);
    }
}

// Export services
export const apiService = ApiService.getInstance();
export const jobService = new JobService();
export const authService = new AuthService();
export const profileService = new ProfileService();