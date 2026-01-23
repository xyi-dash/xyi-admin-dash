import axios from 'axios';

const isDev = import.meta.env.DEV;

const DASHBOARD_URL = import.meta.env.VITE_DASHBOARD_URL || (isDev ? 'http://localhost:8000' : null);
const API_URL = import.meta.env.VITE_API_URL || (isDev ? 'http://localhost:8000/api' : null);

if (!DASHBOARD_URL || !API_URL) {
    throw new Error('env vars missing: did you forget to set VITE_DASHBOARD_URL and VITE_API_URL? rookie mistake');
}

const api = axios.create({
    baseURL: API_URL,
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
    }
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('admin_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// this interceptor has seen more 401s than my ex has seen red flags
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_user');
            localStorage.removeItem('unlocked_servers');
            window.location.href = DASHBOARD_URL;
        }
        return Promise.reject(error);
    }
);

export const redirectToDashboard = () => {
    window.location.href = DASHBOARD_URL;
};

export { DASHBOARD_URL };
export default api;
