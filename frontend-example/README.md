# React.js Frontend for Cashier Management API

This repository contains React.js components and services for consuming the Laravel backend cashier management API.

## 🚀 Quick Start

### Prerequisites
- Node.js (v14 or higher)
- npm or yarn
- Laravel backend running on `http://localhost:8000`

### Installation

1. **Install dependencies:**
```bash
npm install axios react react-dom
```

2. **For styling (Tailwind CSS):**
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

3. **Configure Tailwind CSS** (`tailwind.config.js`):
```javascript
module.exports = {
  content: [
    "./src/**/*.{js,jsx,ts,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

4. **Add Tailwind to your CSS** (`src/index.css`):
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

## 📁 File Structure

```
frontend-example/
├── services/
│   └── cashierService.js          # API service layer
├── CashierUpdate.jsx              # Basic component with direct axios
├── CashierUpdateWithService.jsx   # Improved component with service layer
└── README.md                      # This file
```

## 🔧 Configuration

### Environment Variables

Create a `.env` file in your React project root:

```env
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_AUTH_TOKEN_KEY=auth_token
```

### Authentication Setup

The components expect an authentication token stored in localStorage. You can modify the `getAuthToken()` function in the service to match your auth system:

```javascript
// In cashierService.js
const getAuthToken = () => {
  // Option 1: From localStorage
  return localStorage.getItem('auth_token');
  
  // Option 2: From sessionStorage
  // return sessionStorage.getItem('auth_token');
  
  // Option 3: From your auth context
  // return authContext.token;
};
```

## 📖 Usage Examples

### 1. Basic Usage (Direct Axios)

```jsx
import React, { useState } from 'react';
import CashierUpdate from './CashierUpdate';

function App() {
  const [showUpdateForm, setShowUpdateForm] = useState(false);
  const [selectedCashierId, setSelectedCashierId] = useState(null);

  const handleUpdateSuccess = (updatedCashier) => {
    console.log('Cashier updated:', updatedCashier);
    setShowUpdateForm(false);
    // Refresh your cashier list or update state
  };

  const handleCancel = () => {
    setShowUpdateForm(false);
  };

  return (
    <div className="App">
      <button 
        onClick={() => {
          setSelectedCashierId(1); // Replace with actual cashier ID
          setShowUpdateForm(true);
        }}
        className="px-4 py-2 bg-blue-600 text-white rounded"
      >
        Edit Cashier
      </button>

      {showUpdateForm && (
        <CashierUpdate
          cashierId={selectedCashierId}
          onUpdateSuccess={handleUpdateSuccess}
          onCancel={handleCancel}
        />
      )}
    </div>
  );
}
```

### 2. Advanced Usage (With Service Layer)

```jsx
import React, { useState } from 'react';
import CashierUpdateWithService from './CashierUpdateWithService';

function App() {
  const [showUpdateForm, setShowUpdateForm] = useState(false);
  const [selectedCashierId, setSelectedCashierId] = useState(null);

  const handleUpdateSuccess = (updatedCashier) => {
    console.log('Cashier updated:', updatedCashier);
    setShowUpdateForm(false);
    // Refresh your cashier list or update state
  };

  const handleCancel = () => {
    setShowUpdateForm(false);
  };

  return (
    <div className="App">
      <button 
        onClick={() => {
          setSelectedCashierId(1); // Replace with actual cashier ID
          setShowUpdateForm(true);
        }}
        className="px-4 py-2 bg-blue-600 text-white rounded"
      >
        Edit Cashier
      </button>

      {showUpdateForm && (
        <CashierUpdateWithService
          cashierId={selectedCashierId}
          onUpdateSuccess={handleUpdateSuccess}
          onCancel={handleCancel}
        />
      )}
    </div>
  );
}
```

### 3. Using the Service Layer Directly

```jsx
import React, { useState, useEffect } from 'react';
import cashierService from './services/cashierService';

function CashierList() {
  const [cashiers, setCashiers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadCashiers();
  }, []);

  const loadCashiers = async () => {
    try {
      setLoading(true);
      const response = await cashierService.getAllCashiers();
      setCashiers(response.data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (cashierId) => {
    if (window.confirm('Are you sure you want to delete this cashier?')) {
      try {
        await cashierService.deleteCashier(cashierId);
        loadCashiers(); // Refresh the list
      } catch (error) {
        alert(error.message);
      }
    }
  };

  const handleAssignToQueue = async (cashierId, queueId) => {
    try {
      await cashierService.assignToQueue(cashierId, queueId);
      loadCashiers(); // Refresh the list
    } catch (error) {
      alert(error.message);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div className="space-y-4">
      {cashiers.map(cashier => (
        <div key={cashier.id} className="border p-4 rounded">
          <h3>{cashier.name}</h3>
          <p>Status: {cashier.status}</p>
          <p>Queue: {cashier.queue?.name || 'No queue assigned'}</p>
          
          <div className="mt-2 space-x-2">
            <button 
              onClick={() => handleDelete(cashier.id)}
              className="px-3 py-1 bg-red-600 text-white rounded text-sm"
            >
              Delete
            </button>
            
            <button 
              onClick={() => handleAssignToQueue(cashier.id, 1)}
              className="px-3 py-1 bg-green-600 text-white rounded text-sm"
            >
              Assign to Queue 1
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}
```

## 🔒 Authentication

### Setting up Authentication

1. **Login and store token:**
```javascript
const login = async (credentials) => {
  try {
    const response = await axios.post('http://localhost:8000/api/login', credentials);
    const token = response.data.token;
    localStorage.setItem('auth_token', token);
    return response.data;
  } catch (error) {
    throw error;
  }
};
```

2. **Logout and clear token:**
```javascript
const logout = () => {
  localStorage.removeItem('auth_token');
  // Redirect to login page
  window.location.href = '/login';
};
```

3. **Check if user is authenticated:**
```javascript
const isAuthenticated = () => {
  return !!localStorage.getItem('auth_token');
};
```

## 🎨 Customization

### Styling

The components use Tailwind CSS classes. You can customize the styling by:

1. **Modifying Tailwind classes** in the components
2. **Adding custom CSS** classes
3. **Using a different CSS framework** (just replace the className attributes)

### Form Validation

The components handle validation errors from the API. You can add client-side validation:

```javascript
const validateForm = (data) => {
  const errors = {};
  
  if (!data.name.trim()) {
    errors.name = ['Name is required'];
  }
  
  if (data.email && !/\S+@\S+\.\S+/.test(data.email)) {
    errors.email = ['Please enter a valid email'];
  }
  
  return errors;
};
```

### Error Handling

The service layer provides centralized error handling. You can customize error messages:

```javascript
// In cashierService.js
handleError(error) {
  if (error.response?.status === 422) {
    return {
      type: 'validation',
      message: 'Please fix the validation errors below.',
      errors: error.response.data.errors || {}
    };
  }
  
  // Add custom error messages
  const errorMessages = {
    400: 'Invalid request data',
    401: 'Please login to continue',
    403: 'You do not have permission for this action',
    404: 'Cashier not found',
    500: 'Server error, please try again later'
  };
  
  return new Error(errorMessages[error.response?.status] || 'An error occurred');
}
```

## 🧪 Testing

### Unit Testing with Jest

```javascript
// CashierUpdate.test.js
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import CashierUpdate from './CashierUpdate';

// Mock the service
jest.mock('./services/cashierService');

test('renders update form', () => {
  render(<CashierUpdate cashierId={1} />);
  expect(screen.getByText('Update Cashier')).toBeInTheDocument();
});

test('submits form with correct data', async () => {
  const mockUpdateSuccess = jest.fn();
  render(
    <CashierUpdate 
      cashierId={1} 
      onUpdateSuccess={mockUpdateSuccess} 
    />
  );
  
  // Fill form and submit
  fireEvent.change(screen.getByLabelText('Name *'), {
    target: { value: 'John Doe' }
  });
  
  fireEvent.click(screen.getByText('Update Cashier'));
  
  await waitFor(() => {
    expect(mockUpdateSuccess).toHaveBeenCalled();
  });
});
```

## 🚨 Common Issues & Solutions

### CORS Issues

If you encounter CORS errors, ensure your Laravel backend has proper CORS configuration:

```php
// In config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'], // Your React app URL
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### Authentication Issues

1. **Token not being sent:** Check if the token is properly stored and retrieved
2. **Token expired:** Implement token refresh logic
3. **Wrong token format:** Ensure the token is sent as `Bearer {token}`

### API Endpoint Issues

1. **Wrong URL:** Verify the API_BASE_URL in your environment variables
2. **Missing headers:** Ensure Content-Type and Accept headers are set
3. **Wrong HTTP method:** Verify you're using PUT for updates

## 📚 Additional Resources

- [Axios Documentation](https://axios-http.com/docs/intro)
- [React Documentation](https://reactjs.org/docs/getting-started.html)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE). 