import axios from 'axios';

console.log("API_URL", { url: process.env.API_URL});

const api = axios.create({
  baseURL: `${process.env.API_URL || 'http://localhost:2027'}`,
  headers: {
    Authorization: `Bearer ${process.env.API_AUTH_TOKEN}`,
    Accept: 'application/json',
  }
});

export default api;
