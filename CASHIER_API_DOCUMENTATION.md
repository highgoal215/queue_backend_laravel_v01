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

## Cashier Endpoints

### 1. Get All Cashiers
**GET** `/cashiers`

Returns a list of all cashiers. Supports filtering by `is_active`, `assigned_queue_id`, and `role`.

**Query Parameters:**
- `is_active` (boolean)
- `assigned_queue_id` (integer)
- `role` (string)

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