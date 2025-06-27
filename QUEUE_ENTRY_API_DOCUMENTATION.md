# Queue Entry Management API Documentation

This document provides comprehensive documentation for the Queue Entry Management System API endpoints.

## Base URL
```
http://your-domain.com/api
```

## Authentication
Most endpoints require authentication. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Queue Entry Endpoints

### 1. Get All Queue Entries
**GET** `/entries`

Returns a list of all queue entries with optional filtering.

**Query Parameters:**
- `status` - Filter by order status (queued, kitchen, preparing, serving, completed, cancelled)
- `cashier_id` - Filter by cashier ID
- `date` - Filter by creation date (YYYY-MM-DD)
- `queue_id` - Filter by queue ID

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "queue_id": 1,
            "queue_number": 1,
            "quantity_purchased": null,
            "cashier_id": 1,
            "order_status": "completed",
            "created_at": "2024-01-15T10:00:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z",
            "queue": {
                "id": 1,
                "name": "Customer Service",
                "type": "regular"
            },
            "cashier": {
                "id": 1,
                "name": "Cashier A"
            },
            "tracking": {
                "id": 1,
                "qr_code_url": "https://example.com/qr/entry-1"
            }
        }
    ],
    "message": "Queue entries retrieved successfully"
}
```

### 2. Create Queue Entry
**POST** `/entries`

Creates a new queue entry.

**Request Body:**
```json
{
    "queue_id": 2, // e.g., 1 = Food Queue, 2 = Coffee Queue
    "customer_name": "John Doe",
    "phone_number": "555-1234",
    "quantity_purchased": 150,
    "order_details": {
        "items": ["Latte", "Croissant"],
        "special_instructions": "Extra hot"
    },
    "notes": "Morning order",
    "cashier_id": 1,
    "order_status": "queued"
}
```

> **Note:**
> - `queue_id` selects the target queue for the entry (e.g., Food, Coffee, etc.).
> - You can retrieve available queues and their IDs using the `/queues` endpoint. For example:
>   - `1` = Food Queue
>   - `2` = Coffee Queue

**Required Parameters:**
- `queue_id` - ID of the queue to join (e.g., Food, Coffee, etc.)
- `customer_name` - Customer's full name
- `phone_number` - Customer's phone number
- `quantity_purchased` - Quantity purchased (must be at least 1)

**Optional Parameters:**
- `order_details` - JSON object containing order details
- `notes` - Additional notes about the order
- `cashier_id` - ID of the assigned cashier
- `order_status` - Initial order status (defaults to "queued")
- `estimated_wait_time` - Estimated wait time in minutes

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 6,
        "queue_id": 1,
        "queue_number": 6,
        "quantity_purchased": 150,
        "cashier_id": 1,
        "order_status": "queued",
        "created_at": "2024-01-15T15:00:00.000000Z",
        "updated_at": "2024-01-15T15:00:00.000000Z",
        "queue": {
            "id": 1,
            "name": "Customer Service",
            "type": "regular"
        },
        "cashier": {
            "id": 1,
            "name": "Cashier A"
        },
        "tracking": {
            "id": 6,
            "qr_code_url": "https://example.com/qr/entry-6"
        }
    },
    "message": "Queue entry created successfully"
}
```

### 3. Get Queue Entry Details
**GET** `/entries/{entry_id}`

Returns detailed information about a specific queue entry.

**Response:**
```json
{
    "success": true,
    "data": {
        "entry": {
            "id": 1,
            "queue_id": 1,
            "queue_number": 1,
            "quantity_purchased": null,
            "cashier_id": 1,
            "order_status": "completed",
            "created_at": "2024-01-15T10:00:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        },
        "queue_info": {
            "name": "Customer Service",
            "type": "regular",
            "status": "active"
        },
        "cashier_info": {
            "name": "Cashier A",
            "is_active": true
        },
        "tracking_info": {
            "qr_code_url": "https://example.com/qr/entry-1"
        }
    },
    "message": "Queue entry details retrieved successfully"
}
```

### 4. Update Queue Entry
**PUT** `/entries/{entry_id}`

Updates queue entry information.

**Request Body:**
```json
{
    "quantity_purchased": 200,
    "cashier_id": 2
}
```

### 5. Delete Queue Entry
**DELETE** `/entries/{entry_id}`

Deletes a queue entry (only if not completed).

### 6. Update Entry Status
**PATCH** `/entries/{entry_id}/status`

Updates the order status of a queue entry.

**Request Body:**
```json
{
    "order_status": "kitchen",
    "notes": "Order moved to kitchen"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "order_status": "kitchen",
        "queue": {
            "id": 1,
            "name": "Customer Service"
        },
        "cashier": {
            "id": 1,
            "name": "Cashier A"
        }
    },
    "message": "Queue entry status updated successfully"
}
```

### 7. Cancel Queue Entry
**POST** `/entries/{entry_id}/cancel`

Cancels a queue entry and restores inventory if applicable.

**Request Body:**
```json
{
    "reason": "Customer requested cancellation"
}
```

### 8. Get Entries by Status
**GET** `/entries/status/{status}`

Returns all entries with a specific status.

**Query Parameters:**
- `queue_id` - Filter by queue ID

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "queue_number": 1,
            "order_status": "queued",
            "queue": {
                "name": "Customer Service"
            }
        }
    ],
    "message": "Queue entries with status 'queued' retrieved successfully"
}
```

### 9. Get Active Entries for Queue
**GET** `/queues/{queue_id}/entries/active`

Returns active entries (queued, kitchen, preparing, serving) for a specific queue.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "queue_number": 1,
            "order_status": "queued",
            "cashier": {
                "name": "Cashier A"
            }
        }
    ],
    "message": "Active queue entries retrieved successfully"
}
```

### 10. Get Next Entry for Queue
**GET** `/queues/{queue_id}/entries/next`

Returns the next entry to be served for a specific queue.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "queue_number": 1,
        "order_status": "queued"
    },
    "message": "Next entry retrieved successfully"
}
```

### 11. Get Entries by Cashier
**GET** `/entries/cashier/{cashier_id}`

Returns all entries handled by a specific cashier.

**Query Parameters:**
- `status` - Filter by order status
- `date` - Filter by creation date

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "queue_number": 1,
            "order_status": "completed",
            "queue": {
                "name": "Customer Service"
            }
        }
    ],
    "message": "Cashier entries retrieved successfully"
}
```

### 12. Get Entry Statistics
**GET** `/entries/stats`

Returns comprehensive statistics for queue entries.

**Query Parameters:**
- `queue_id` - Filter by queue ID

**Response:**
```json
{
    "success": true,
    "data": {
        "total_entries": 25,
        "entries_by_status": {
            "queued": 10,
            "kitchen": 3,
            "preparing": 2,
            "serving": 1,
            "completed": 8,
            "cancelled": 1
        },
        "entries_by_hour": [
            {
                "hour": 10,
                "count": 5
            },
            {
                "hour": 11,
                "count": 8
            }
        ],
        "inventory_stats": {
            "total_quantity_sold": 1500,
            "average_quantity_per_order": 150
        }
    },
    "message": "Entry statistics retrieved successfully"
}
```

### 13. Bulk Update Entry Statuses
**POST** `/entries/bulk-update-status`

Updates the status of multiple entries at once.

**Request Body:**
```json
{
    "entry_ids": [1, 2, 3],
    "order_status": "kitchen"
}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "order_status": "kitchen"
        },
        {
            "id": 2,
            "order_status": "kitchen"
        },
        {
            "id": 3,
            "order_status": "kitchen"
        }
    ],
    "message": "3 entries updated successfully"
}
```

### 14. Get Entry Timeline
**GET** `/entries/{entry_id}/timeline`

Returns the timeline/history of a queue entry.

**Response:**
```json
{
    "success": true,
    "data": {
        "entry_id": 1,
        "queue_number": 1,
        "queue_name": "Customer Service",
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "current_status": "completed",
        "cashier": "Cashier A",
        "quantity_purchased": null,
        "tracking_url": "https://example.com/qr/entry-1"
    },
    "message": "Entry timeline retrieved successfully"
}
```

### 15. Search Entries
**GET** `/entries/search`

Searches entries by various criteria.

**Query Parameters:**
- `q` - Search query (queue number, queue name, cashier name)
- `status` - Filter by order status
- `queue_id` - Filter by queue ID
- `cashier_id` - Filter by cashier ID
- `date` - Filter by creation date

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "queue_number": 1,
            "order_status": "completed",
            "queue": {
                "name": "Customer Service"
            },
            "cashier": {
                "name": "Cashier A"
            }
        }
    ],
    "message": "Search completed successfully"
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

## Real-time Updates

The system broadcasts real-time updates using WebSocket events:

### Order Status Changed Event
- **Channel:** `queue.{queue_id}`, `entry.{entry_id}`, and `orders`
- **Event:** `order.status.changed`
- **Data:**
```json
{
    "entry_id": 1,
    "queue_id": 1,
    "queue_number": 1,
    "order_status": "kitchen",
    "queue_name": "Customer Service",
    "cashier_name": "Cashier A",
    "quantity_purchased": 150,
    "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

## Usage Examples

### Creating a Queue Entry
```bash
curl -X POST http://your-domain.com/api/entries \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "queue_id": 1,
    "customer_name": "John Doe",
    "phone_number": "555-1234",
    "quantity_purchased": 150,
    "order_details": {
        "items": ["Steak", "Fries"],
        "special_instructions": "Medium rare"
    },
    "notes": "VIP customer",
    "cashier_id": 1,
    "order_status": "queued"
  }'
```

### Updating Entry Status
```bash
curl -X PATCH http://your-domain.com/api/entries/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "order_status": "kitchen"
  }'
```

### Getting Active Entries
```bash
curl -X GET http://your-domain.com/api/queues/1/entries/active \
  -H "Authorization: Bearer {token}"
```

### Searching Entries
```bash
curl -X GET "http://your-domain.com/api/entries/search?q=customer&status=queued" \
  -H "Authorization: Bearer {token}"
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

1. **Queue Entry Creation:**
   - Automatically assigns the next queue number
   - Generates QR code for customer tracking
   - Updates queue current number
   - For inventory queues, validates and updates remaining quantity

2. **Status Management:**
   - Validates status transitions
   - Broadcasts real-time updates
   - Handles inventory restoration on cancellation

3. **Inventory Queues:**
   - Requires quantity_purchased for creation
   - Validates against remaining stock
   - Restores quantity on cancellation
   - Auto-closes queue when stock is depleted

4. **Real-time Features:**
   - All status changes are broadcasted
   - QR code generation for customer tracking
   - Queue updates trigger notifications

5. **Security:**
   - All endpoints require authentication
   - Input validation is enforced
   - Status transition validation prevents invalid changes 