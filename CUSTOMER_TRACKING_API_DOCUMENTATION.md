# Customer Tracking API Documentation

This document provides comprehensive documentation for the Customer Tracking System API endpoints.

## Base URL
```
http://your-domain.com/api
```

## Authentication
All endpoints require authentication. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Customer Tracking Endpoints

### 1. Get Tracking Information
**GET** `/tracking/{entry_id}`

Returns tracking information for a specific queue entry.

**Response:**
```json
{
    "success": true,
    "data": {
        "tracking_id": 1,
        "queue_number": 15,
        "order_status": "kitchen",
        "queue_name": "Customer Service",
        "queue_type": "regular",
        "cashier": {
            "name": "Cashier A",
            "is_active": true
        },
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "qr_code_url": "https://example.com/qr/entry-15",
        "estimated_wait_time": "10 minutes"
    },
    "message": "Tracking information retrieved successfully"
}
```

### 2. Update Order Status
**PATCH** `/tracking/{entry_id}/status`

Updates the order status for a queue entry.

**Request Body:**
```json
{
    "order_status": "preparing",
    "notes": "Order moved to preparation"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "entry_id": 15,
        "queue_number": 15,
        "order_status": "preparing",
        "queue_name": "Customer Service",
        "cashier_name": "Cashier A",
        "updated_at": "2024-01-15T10:35:00.000000Z"
    },
    "message": "Order status updated successfully"
}
```

### 3. Get All Tracking Records
**GET** `/tracking`

Returns all tracking records with optional filtering.

**Query Parameters:**
- `queue_id` - Filter by queue ID
- `status` - Filter by order status
- `date` - Filter by creation date (YYYY-MM-DD)
- `has_qr_code` - Filter by QR code availability (true/false)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "queue_entry_id": 15,
            "qr_code_url": "https://example.com/qr/entry-15",
            "created_at": "2024-01-15T10:00:00.000000Z",
            "entry": {
                "id": 15,
                "queue_number": 15,
                "order_status": "preparing",
                "queue": {
                    "name": "Customer Service",
                    "type": "regular"
                },
                "cashier": {
                    "name": "Cashier A"
                }
            }
        }
    ],
    "message": "Tracking records retrieved successfully"
}
```

### 4. Create Tracking Record
**POST** `/tracking`

Creates a new tracking record for a queue entry.

**Request Body:**
```json
{
    "queue_entry_id": 16,
    "qr_code_url": "https://example.com/qr/entry-16"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "queue_entry_id": 16,
        "qr_code_url": "https://example.com/qr/entry-16",
        "created_at": "2024-01-15T11:00:00.000000Z",
        "entry": {
            "queue_number": 16,
            "order_status": "queued",
            "queue": {
                "name": "Customer Service"
            }
        }
    },
    "message": "Tracking record created successfully"
}
```

### 5. Update Tracking Record
**PUT** `/tracking/{tracking_id}`

Updates an existing tracking record.

**Request Body:**
```json
{
    "qr_code_url": "https://example.com/qr/entry-15-updated"
}
```

### 6. Delete Tracking Record
**DELETE** `/tracking/{tracking_id}`

Deletes a tracking record.

### 7. Generate QR Code
**POST** `/tracking/{entry_id}/generate-qr`

Generates a new QR code for tracking.

**Response:**
```json
{
    "success": true,
    "data": {
        "tracking_id": 1,
        "qr_code_url": "https://example.com/qr/entry-15",
        "entry_id": 15,
        "queue_number": 15
    },
    "message": "QR code generated successfully"
}
```

### 8. Get Tracking Statistics
**GET** `/tracking/stats`

Returns comprehensive tracking statistics.

**Query Parameters:**
- `queue_id` - Filter by queue ID
- `date_range` - Filter by date range (start_date,end_date)

**Response:**
```json
{
    "success": true,
    "data": {
        "total_tracking": 25,
        "tracking_with_qr": 23,
        "qr_code_percentage": 92.0,
        "status_distribution": {
            "queued": 8,
            "kitchen": 5,
            "preparing": 3,
            "serving": 2,
            "completed": 6,
            "cancelled": 1
        },
        "daily_tracking": {
            "2024-01-15": 15,
            "2024-01-14": 10
        },
        "average_wait_time": "12 minutes",
        "most_active_queue": "Customer Service"
    },
    "message": "Tracking statistics retrieved successfully"
}
```

### 9. Search Tracking Records
**GET** `/tracking/search`

Searches tracking records by various criteria.

**Query Parameters:**
- `q` - Search query (queue number, queue name, cashier name)
- `status` - Filter by order status
- `queue_id` - Filter by queue ID
- `date` - Filter by creation date

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "queue_entry_id": 15,
            "qr_code_url": "https://example.com/qr/entry-15",
            "entry": {
                "queue_number": 15,
                "order_status": "preparing",
                "queue": {
                    "name": "Customer Service"
                },
                "cashier": {
                    "name": "Cashier A"
                }
            }
        }
    ],
    "message": "Search completed successfully"
}
```

### 10. Get Tracking History
**GET** `/tracking/{entry_id}/history`

Returns the complete history of a queue entry.

**Response:**
```json
{
    "success": true,
    "data": {
        "entry_id": 15,
        "queue_number": 15,
        "queue_name": "Customer Service",
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:35:00.000000Z",
        "current_status": "preparing",
        "cashier": "Cashier A",
        "quantity_purchased": null,
        "tracking_url": "https://example.com/qr/entry-15",
        "estimated_wait_time": "10 minutes",
        "estimated_completion": "10:45",
        "status_history": [
            {
                "status": "queued",
                "timestamp": "2024-01-15T10:00:00.000000Z"
            },
            {
                "status": "kitchen",
                "timestamp": "2024-01-15T10:30:00.000000Z"
            },
            {
                "status": "preparing",
                "timestamp": "2024-01-15T10:35:00.000000Z"
            }
        ]
    },
    "message": "Tracking history retrieved successfully"
}
```

### 11. Get Real-time Updates
**POST** `/tracking/real-time`

Returns real-time updates for multiple entries.

**Request Body:**
```json
{
    "entry_ids": [15, 16, 17]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "timestamp": "2024-01-15T10:35:00.000000Z",
        "updates": [
            {
                "entry_id": 15,
                "queue_number": 15,
                "order_status": "preparing",
                "queue_name": "Customer Service",
                "cashier": "Cashier A",
                "updated_at": "2024-01-15T10:35:00.000000Z",
                "tracking_url": "https://example.com/qr/entry-15",
                "estimated_wait_time": "10 minutes"
            }
        ]
    },
    "message": "Real-time updates retrieved successfully"
}
```

## Order Status Flow

The system supports the following order status transitions:

1. **queued** → **kitchen** or **cancelled**
2. **kitchen** → **preparing** or **cancelled**
3. **preparing** → **serving** or **cancelled**
4. **serving** → **completed** or **cancelled**
5. **completed** → No further transitions
6. **cancelled** → No further transitions

## Wait Time Calculation

The system calculates estimated wait times based on:
- Current queue position
- Average processing time per order
- Number of active cashiers
- Queue type (regular vs inventory)

Wait time formats:
- "Ready" - Order is ready for pickup
- "X minutes" - Estimated wait time
- "Xh Ym" - Longer wait times in hours and minutes

## QR Code Generation

QR codes are generated automatically when:
- A new queue entry is created
- A tracking record is created
- QR code generation is requested manually

QR codes contain:
- Entry ID
- Queue number
- Queue name
- Timestamp
- Tracking URL

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

## Usage Examples

### Getting Tracking Information
```bash
curl -X GET http://your-domain.com/api/tracking/15 \
  -H "Authorization: Bearer {token}"
```

### Updating Order Status
```bash
curl -X PATCH http://your-domain.com/api/tracking/15/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "order_status": "preparing"
  }'
```

### Searching Tracking Records
```bash
curl -X GET "http://your-domain.com/api/tracking/search?q=customer&status=queued" \
  -H "Authorization: Bearer {token}"
```

### Getting Tracking Statistics
```bash
curl -X GET "http://your-domain.com/api/tracking/stats?queue_id=1" \
  -H "Authorization: Bearer {token}"
```

## Notes

1. **Tracking Creation:**
   - Tracking records are created automatically with queue entries
   - QR codes are generated for customer access
   - Each entry can have only one tracking record

2. **Status Management:**
   - Status transitions are validated
   - Real-time updates are broadcasted
   - Wait times are calculated dynamically

3. **QR Code System:**
   - QR codes provide direct access to tracking information
   - Codes are unique per entry
   - URLs are publicly accessible for customer use

4. **Real-time Features:**
   - Status changes trigger immediate updates
   - Wait time calculations are real-time
   - Multiple entries can be tracked simultaneously

5. **Security:**
   - All endpoints require authentication
   - Input validation is enforced
   - Status transition validation prevents invalid changes

6. **Performance:**
   - Tracking data is cached when possible
   - Real-time updates are optimized
   - Statistics are calculated efficiently 