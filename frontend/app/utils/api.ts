import axios from 'axios';
import toast from 'react-hot-toast';

// Use import.meta.env instead of process.env for Vite compatibility
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:2027';
const API_TOKEN = import.meta.env.VITE_API_AUTH_TOKEN || '';

console.log("API_URL", { url: API_URL });

const api = axios.create({
  baseURL: API_URL,
  headers: {
    Authorization: API_TOKEN ? `Bearer ${API_TOKEN}` : '',
    Accept: 'application/json',
  }
});

// Add a response interceptor for global error handling
api.interceptors.response.use(
  (response) => {
    // Any status code within the range of 2xx causes this function to trigger
    return response;
  },
  (error) => {
    // Any status codes outside the range of 2xx cause this function to trigger
    const statusCode = error.response?.status;
    const errorMessage = error.response?.data?.message || 'An unexpected error occurred';
    
    // Don't show toast for 422 validation errors as they're handled specifically in components
    // This prevents duplicate toasts when components already handle these errors
    if (statusCode !== 422) {
      // Handle specific status codes
      if (statusCode === 401) {
        toast.error('Authentication error: Please log in again');
      } else if (statusCode === 403) {
        toast.error('You don\'t have permission to perform this action');
      } else if (statusCode === 404) {
        toast.error('Resource not found');
      } else if (statusCode >= 500) {
        toast.error('Server error: ' + errorMessage);
      } else if (statusCode !== undefined) {
        // For other status codes
        toast.error(errorMessage);
      } else {
        // Network errors or other issues
        toast.error('Network error: Unable to connect to server');
      }
    }
    
    return Promise.reject(error);
  }
);

export default api;
