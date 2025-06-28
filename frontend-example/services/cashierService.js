import axios from 'axios';

// API Configuration
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle common errors
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized - redirect to login
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

class CashierService {
  // Get all cashiers
  async getAllCashiers(filters = {}) {
    try {
      const response = await apiClient.get('/cashiers', { params: filters });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Get cashier by ID
  async getCashierById(id) {
    try {
      const response = await apiClient.get(`/cashiers/${id}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Create new cashier
  async createCashier(cashierData) {
    try {
      const response = await apiClient.post('/cashiers', cashierData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Update cashier
  async updateCashier(id, cashierData) {
    try {
      const response = await apiClient.put(`/cashiers/${id}`, cashierData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Delete cashier
  async deleteCashier(id) {
    try {
      const response = await apiClient.delete(`/cashiers/${id}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Assign cashier to queue
  async assignToQueue(cashierId, queueId) {
    try {
      const response = await apiClient.post(`/cashiers/${cashierId}/assign`, {
        assigned_queue_id: queueId
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Set cashier active status
  async setActiveStatus(cashierId, isActive) {
    try {
      const response = await apiClient.post(`/cashiers/${cashierId}/set-active`, {
        is_active: isActive
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Get queues with cashiers
  async getQueuesWithCashiers() {
    try {
      const response = await apiClient.get('/queues-with-cashiers');
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Get detailed cashier info
  async getDetailedInfo(filters = {}) {
    try {
      const response = await apiClient.get('/cashiers/detailed', { params: filters });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Get essential cashier info
  async getEssentialInfo(filters = {}) {
    try {
      const response = await apiClient.get('/cashiers/essential', { params: filters });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Get all queues (for dropdown)
  async getAllQueues() {
    try {
      const response = await apiClient.get('/queues');
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Handle API errors
  handleError(error) {
    if (error.response) {
      // Server responded with error status
      const { status, data } = error.response;
      
      switch (status) {
        case 400:
          return new Error('Bad request. Please check your data.');
        case 401:
          return new Error('Authentication failed. Please login again.');
        case 403:
          return new Error('You do not have permission to perform this action.');
        case 404:
          return new Error('Cashier not found.');
        case 422:
          return {
            type: 'validation',
            message: 'Please fix the validation errors.',
            errors: data.errors || {}
          };
        case 500:
          return new Error('Server error. Please try again later.');
        default:
          return new Error(data.message || 'An error occurred.');
      }
    } else if (error.request) {
      // Network error
      return new Error('Network error. Please check your connection.');
    } else {
      // Other error
      return new Error('An unexpected error occurred.');
    }
  }
}

export default new CashierService(); 