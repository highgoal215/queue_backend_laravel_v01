import React, { useState, useEffect } from 'react';
import cashierService from './services/cashierService';

const CashierUpdateWithService = ({ cashierId, onUpdateSuccess, onCancel }) => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    role: '',
    status: 'active',
    is_active: true,
    is_available: true,
    assigned_queue_id: '',
    shift_start: '',
    shift_end: ''
  });

  const [queues, setQueues] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState('');

  // Fetch cashier data and available queues
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        
        // Fetch cashier details and queues in parallel
        const [cashierResponse, queuesResponse] = await Promise.all([
          cashierService.getCashierById(cashierId),
          cashierService.getAllQueues()
        ]);

        const cashier = cashierResponse.data;
        setFormData({
          name: cashier.name || '',
          email: cashier.email || '',
          phone: cashier.phone || '',
          role: cashier.role || '',
          status: cashier.status || 'active',
          is_active: cashier.is_active,
          is_available: cashier.is_available,
          assigned_queue_id: cashier.assigned_queue_id || '',
          shift_start: cashier.shift_start ? cashier.shift_start.substring(11, 16) : '',
          shift_end: cashier.shift_end ? cashier.shift_end.substring(11, 16) : ''
        });

        setQueues(queuesResponse.data);
      } catch (error) {
        console.error('Error fetching data:', error);
        setMessage(error.message || 'Failed to load cashier data');
      } finally {
        setLoading(false);
      }
    };

    if (cashierId) {
      fetchData();
    }
  }, [cashierId]);

  // Handle form input changes
  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));

    // Clear error for this field when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});
    setMessage('');

    try {
      const response = await cashierService.updateCashier(cashierId, formData);
      
      if (response.success) {
        setMessage('Cashier updated successfully!');
        if (onUpdateSuccess) {
          onUpdateSuccess(response.data);
        }
      }
    } catch (error) {
      console.error('Update error:', error);
      
      if (error.type === 'validation') {
        // Validation errors
        setErrors(error.errors);
        setMessage(error.message);
      } else {
        setMessage(error.message);
      }
    } finally {
      setLoading(false);
    }
  };

  if (loading && !formData.name) {
    return (
      <div className="flex justify-center items-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span className="ml-2">Loading cashier data...</span>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
      <h2 className="text-2xl font-bold mb-6 text-gray-800">Update Cashier</h2>
      
      {message && (
        <div className={`mb-4 p-3 rounded ${
          message.includes('successfully') 
            ? 'bg-green-100 text-green-700 border border-green-300' 
            : 'bg-red-100 text-red-700 border border-red-300'
        }`}>
          {message}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Name Field */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Name *
          </label>
          <input
            type="text"
            name="name"
            value={formData.name}
            onChange={handleInputChange}
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.name ? 'border-red-500' : 'border-gray-300'
            }`}
            placeholder="Enter cashier name"
          />
          {errors.name && (
            <p className="text-red-500 text-sm mt-1">{errors.name[0]}</p>
          )}
        </div>

        {/* Email Field */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Email
          </label>
          <input
            type="email"
            name="email"
            value={formData.email}
            onChange={handleInputChange}
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.email ? 'border-red-500' : 'border-gray-300'
            }`}
            placeholder="Enter email address"
          />
          {errors.email && (
            <p className="text-red-500 text-sm mt-1">{errors.email[0]}</p>
          )}
        </div>

        {/* Phone Field */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Phone
          </label>
          <input
            type="tel"
            name="phone"
            value={formData.phone}
            onChange={handleInputChange}
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.phone ? 'border-red-500' : 'border-gray-300'
            }`}
            placeholder="Enter phone number"
          />
          {errors.phone && (
            <p className="text-red-500 text-sm mt-1">{errors.phone[0]}</p>
          )}
        </div>

        {/* Role Field */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Role
          </label>
          <input
            type="text"
            name="role"
            value={formData.role}
            onChange={handleInputChange}
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.role ? 'border-red-500' : 'border-gray-300'
            }`}
            placeholder="Enter role (e.g., Senior Cashier)"
          />
          {errors.role && (
            <p className="text-red-500 text-sm mt-1">{errors.role[0]}</p>
          )}
        </div>

        {/* Status Field */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Status
          </label>
          <select
            name="status"
            value={formData.status}
            onChange={handleInputChange}
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.status ? 'border-red-500' : 'border-gray-300'
            }`}
          >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="break">Break</option>
          </select>
          {errors.status && (
            <p className="text-red-500 text-sm mt-1">{errors.status[0]}</p>
          )}
        </div>

        {/* Assigned Queue Field */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Assigned Queue
          </label>
          <select
            name="assigned_queue_id"
            value={formData.assigned_queue_id}
            onChange={handleInputChange}
            className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
              errors.assigned_queue_id ? 'border-red-500' : 'border-gray-300'
            }`}
          >
            <option value="">No Queue Assigned</option>
            {queues.map(queue => (
              <option key={queue.id} value={queue.id}>
                {queue.name} ({queue.type})
              </option>
            ))}
          </select>
          {errors.assigned_queue_id && (
            <p className="text-red-500 text-sm mt-1">{errors.assigned_queue_id[0]}</p>
          )}
        </div>

        {/* Shift Times */}
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Shift Start
            </label>
            <input
              type="time"
              name="shift_start"
              value={formData.shift_start}
              onChange={handleInputChange}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.shift_start ? 'border-red-500' : 'border-gray-300'
              }`}
            />
            {errors.shift_start && (
              <p className="text-red-500 text-sm mt-1">{errors.shift_start[0]}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Shift End
            </label>
            <input
              type="time"
              name="shift_end"
              value={formData.shift_end}
              onChange={handleInputChange}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.shift_end ? 'border-red-500' : 'border-gray-300'
              }`}
            />
            {errors.shift_end && (
              <p className="text-red-500 text-sm mt-1">{errors.shift_end[0]}</p>
            )}
          </div>
        </div>

        {/* Boolean Fields */}
        <div className="grid grid-cols-2 gap-4">
          <div className="flex items-center">
            <input
              type="checkbox"
              name="is_active"
              checked={formData.is_active}
              onChange={handleInputChange}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label className="ml-2 block text-sm text-gray-700">
              Active
            </label>
          </div>

          <div className="flex items-center">
            <input
              type="checkbox"
              name="is_available"
              checked={formData.is_available}
              onChange={handleInputChange}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label className="ml-2 block text-sm text-gray-700">
              Available
            </label>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex justify-end space-x-3 pt-4">
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
            disabled={loading}
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={loading}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {loading ? (
              <div className="flex items-center">
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Updating...
              </div>
            ) : (
              'Update Cashier'
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default CashierUpdateWithService; 