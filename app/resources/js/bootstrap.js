import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Base API URL - configured for Docker environment
 * Use .env for API_URL override
 */
const API_BASE = import.meta.env.VITE_API_URL || '/';

/**
 * Inertia request interceptor - attach Sanctum token
 */
axios.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('signic_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

/**
 * 401 handler - redirect to login
 */
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && window.location.pathname !== '/login') {
            localStorage.removeItem('signic_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);