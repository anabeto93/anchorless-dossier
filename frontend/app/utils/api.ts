import axios from 'axios';

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

export default api;
