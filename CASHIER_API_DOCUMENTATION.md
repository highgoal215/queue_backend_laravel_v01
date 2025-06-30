# Cashier Management API Documentation

This document provides comprehensive documentation for the Cashier Management System API endpoints.

## Base URL
```
http://your-domain.com/api
```

## Authentication
Most endpoints require authentication. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Pagination
Most list endpoints now support pagination to prevent large response bodies. Pagination parameters:
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default varies by endpoint)

Response includes pagination metadata:
```json
{
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75,
    "from": 1,
    "to": 15
  }
}
```

## Cashier Endpoints

### 1. Get All Cashiers
**GET** `/cashiers`

Returns a paginated list of all cashiers. Supports filtering by `is_active`, `assigned_queue_id`, and `role`.

**Query Parameters:**
- `is_active` (boolean)
- `assigned_queue_id` (integer)
- `role` (string)
- `status` (string)
- `is_available` (boolean)
- `page` (integer, default: 1)
- `per_page` (integer, default: 15)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Cashier A",
            "assigned_queue_id": 1,
            "is_active": true,
            "email": "cashierA@example.com",
            "phone": "1234567890",
            "role": "main",
            "shift_start": "09:00:00",
            "shift_end": "17:00:00",
            "queue": {
                "id": 1,
                "name": "Customer Service"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 35,
        "from": 1,
        "to": 15
    },
    "message": "Cashiers retrieved successfully"
}
```

### 2. Create Cashier
**POST** `/cashiers`

Creates a new cashier.

**Request Body:**
```json
{
    "name": "Cashier B",
    "assigned_queue_id": 2,
    "is_active": true,
    "email": "cashierB@example.com",
    "phone": "0987654321",
    "role": "backup",
    "shift_start": "10:00",
    "shift_end": "18:00"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Cashier B",
        "assigned_queue_id": 2,
        "is_active": true,
        "email": "cashierB@example.com",
        "phone": "0987654321",
        "role": "backup",
        "shift_start": "10:00:00",
        "shift_end": "18:00:00"
    },
    "message": "Cashier created successfully"
}
```

### 3. Get Cashier Details
**GET** `/cashiers/{cashier_id}`

Returns detailed information about a specific cashier.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Cashier A",
        "assigned_queue_id": 1,
        "is_active": true,
        "email": "cashierA@example.com",
        "phone": "1234567890",
        "role": "main",
        "shift_start": "09:00:00",
        "shift_end": "17:00:00",
        "queue": {
            "id": 1,
            "name": "Customer Service"
        }
    },
    "message": "Cashier details retrieved successfully"
}
```

### 4. Update Cashier
**PUT** `/cashiers/{cashier_id}`

Updates cashier information.

**Request Body:**
```json
{
    "name": "Cashier A Updated",
    "is_active": false,
    "email": "cashierAnew@example.com",
    "phone": "1112223333",
    "role": "main",
    "shift_start": "08:00",
    "shift_end": "16:00"
}
```

### 5. Delete Cashier
**DELETE** `/cashiers/{cashier_id}`

Deletes a cashier.

### 6. Assign Cashier to Queue
**POST** `/cashiers/{cashier_id}/assign`

Assigns a cashier to a specific queue.

**Request Body:**
```json
{
    "assigned_queue_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "assigned_queue_id": 2,
        "queue": {
            "id": 2,
            "name": "Steak Promotion"
        }
    },
    "message": "Cashier assigned to queue successfully"
}
```

### 7. Activate/Deactivate Cashier
**POST** `/cashiers/{cashier_id}/set-active`

Activates or deactivates a cashier.

**Request Body:**
```json
{
    "is_active": false
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "is_active": false
    },
    "message": "Cashier status updated successfully"
}
```

### 8. Get All Queues with Cashiers
**GET** `/queues-with-cashiers`

Returns all queues with their assigned cashiers.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Customer Service",
            "cashiers": [
                {
                    "id": 1,
                    "name": "Cashier A"
                }
            ]
        },
        {
            "id": 2,
            "name": "Steak Promotion",
            "cashiers": [
                {
                    "id": 2,
                    "name": "Cashier B"
                }
            ]
        }
    ],
    "message": "Queues with cashiers retrieved successfully"
}
```

### 2. Get Detailed Cashier Information
**GET** `/cashiers/detailed`

Returns detailed cashier information with performance metrics, current status, and recent activity. This endpoint is paginated to handle large datasets efficiently.

**Query Parameters:**
- `is_active` (boolean)
- `assigned_queue_id` (integer)
- `role` (string)
- `status` (string)
- `is_available` (boolean)
- `page` (integer, default: 1)
- `per_page` (integer, default: 10)

**Response:**
```json
{
    "success": true,
    "data": {
        "cashiers": [
            {
                "id": 1,
                "basic_info": {
                    "name": "Cashier A",
                    "employee_id": "EMP001",
                    "email": "cashierA@example.com",
                    "phone": "1234567890",
                    "role": "main"
                },
                "status_info": {
                    "is_active": true,
                    "is_available": true,
                    "status": "active",
                    "shift_status": "on_shift",
                    "current_workload": 3
                },
                "performance_metrics": {
                    "total_served": 150,
                    "average_service_time": "5.2 minutes",
                    "efficiency_score": "11.5 entries/hour",
                    "today_entries": 12,
                    "week_entries": 45,
                    "completion_rate": "95.6%"
                }
            }
        ],
        "summary": {
            "active_cashiers": 8,
            "available_cashiers": 6,
            "on_shift_cashiers": 5
        },
        "filters_applied": {}
    },
    "pagination": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 10,
        "total": 15,
        "from": 1,
        "to": 10
    },
    "message": "Detailed cashier information retrieved successfully"
}
```

### 3. Get Essential Cashier Information
**GET** `/cashiers/essential`

Returns essential cashier information with minimal fields for quick overview. This endpoint is paginated for efficient data retrieval.

**Query Parameters:**
- `is_active` (boolean)
- `assigned_queue_id` (integer)
- `role` (string)
- `status` (string)
- `page` (integer, default: 1)
- `per_page` (integer, default: 20)

**Response:**
```json
{
    "success": true,
    "data": {
        "cashiers": [
            {
                "id": 1,
                "cashier_name": "Cashier A",
                "employee_id": "EMP001",
                "role": "main",
                "status": "active",
                "shift_start": "09:00",
                "shift_end": "17:00",
                "queue_name": "Customer Service",
                "total_served": 150
            }
        ],
        "filters_applied": {}
    },
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 15,
        "from": 1,
        "to": 15
    },
    "message": "Essential cashier information retrieved successfully"
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error description"
}
```

For validation errors:
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `422` - Validation Error
- `500` - Server Error

## Notes

1. **Cashier Assignment:**
   - Cashiers can be assigned to any queue or left unassigned
   - Assignment is managed via the `/assign` endpoint

2. **Activation:**
   - Cashiers can be activated or deactivated via the `/set-active` endpoint

3. **Filtering:**
   - List cashiers by active status, assigned queue, or role

4. **Security:**
   - All endpoints require authentication
   - Input validation is enforced
   - Unique constraints on name and email 