import axios from 'axios'

const DASHBOARD_URL = 'https://monser-dm.nl'

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

api.interceptors.request.use(config => {
    const token = localStorage.getItem('admin_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// this interceptor has seen more 401s than my ex has seen red flags
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('admin_token')
            localStorage.removeItem('admin_user')
            localStorage.removeItem('unlocked_servers')
            window.location.href = DASHBOARD_URL
        }
        return Promise.reject(error)
    }
)

export const redirectToDashboard = () => {
    window.location.href = DASHBOARD_URL
}

export default api
