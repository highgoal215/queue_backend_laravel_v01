# Queue Management API Documentation

This document provides comprehensive documentation for the Queue Management System API endpoints.

## Base URL
```
http://your-domain.com/api
```

## Authentication
Most endpoints require authentication. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Queue Endpoints

### 1. Get All Queues
**GET** `/queues`

Returns a list of all queues with their current status.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Regular Queue",
            "type": "regular",
            "status": "active",
            "current_number": 15,
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T14:45:00.000000Z"
        },
        {
            "id": 2,
            "name": "Steak Promotion",
            "type": "inventory",
            "max_quantity": 1000,
            "remaining_quantity": 750,
            "status": "active",
            "current_number": 8,
            "created_at": "2024-01-15T09:00:00.000000Z",
            "updated_at": "2024-01-15T14:30:00.000000Z"
        }
    ],
    "message": "Queues retrieved successfully"
}
```

### 2. Create Queue
**POST** `/queues`

Creates a new queue.

**Request Body:**
```json
{
    "name": "New Queue",
    "type": "regular",
}
```

For inventory queues:
```json
{
    "name": "Steak Promotion",
    "type": "inventory",
    "max_quantity": 1000,
    "remaining_quantity": 1000,
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 3,
        "name": "New Queue",
        "type": "regular",
        "status": "active",
        "current_number": 0,
        "created_at": "2024-01-15T15:00:00.000000Z",
        "updated_at": "2024-01-15T15:00:00.000000Z"
    },
    "message": "Queue created successfully"
}
```

### 3. Get Queue Details
**GET** `/queues/{queue_id}`

Returns detailed information about a specific queue including statistics.

**Response:**
```json
{
    "success": true,
    "data": {
        "queue": {
            "id": 1,
            "name": "Regular Queue",
            "type": "regular",
            "status": "active",
            "current_number": 15,
            "entries": [...],
            "cashiers": [...]
        },
        "statistics": {
            "total_entries": 25,
            "completed_entries": 10,
            "pending_entries": 15,
            "current_number": 15,
            "status": "active"
        }
    },
    "message": "Queue details retrieved successfully"
}
```

### 4. Update Queue
**PUT** `/queues/{queue_id}`

Updates queue information.

**Request Body:**
```json
{
    "name": "Updated Queue Name",
    "status": "paused"
}
```

### 5. Delete Queue
**DELETE** `/queues/{queue_id}`

Deletes a queue (only if no active entries exist).

### 6. Queue Control Operations

#### Reset Queue
**POST** `/queues/{queue_id}/reset`

Resets the queue number to 0.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "current_number": 0,
        "status": "active"
    },
    "message": "Queue reset successfully"
}
```

#### Pause Queue
**POST** `/queues/{queue_id}/pause`

Pauses the queue.

#### Resume Queue
**POST** `/queues/{queue_id}/resume`

Resumes a paused queue.

#### Close Queue
**POST** `/queues/{queue_id}/close`

Closes the queue.

### 7. Queue Number Operations

#### Call Next Number
**POST** `/queues/{queue_id}/call-next`

Increments the current number and returns the next number.

**Response:**
```json
{
    "success": true,
    "data": {
        "next_number": 16,
        "queue": {
            "id": 1,
            "current_number": 16,
            "status": "active"
        }
    },
    "message": "Next number called: 16"
}
```

#### Skip Current Number
**POST** `/queues/{queue_id}/skip`

Skips the current number without creating an entry.

#### Recall Current Number
**POST** `/queues/{queue_id}/recall`

Recalls the current number (useful for display purposes).

### 8. Inventory Management (Inventory Queues Only)

#### Adjust Stock
**POST** `/queues/{queue_id}/adjust-stock`

Adjusts the remaining quantity for inventory queues.

**Request Body:**
```json
{
    "new_quantity": 800
}
```

#### Undo Last Entry
**POST** `/queues/{queue_id}/undo-last-entry`

Undoes the last inventory entry and restores the quantity.

### 9. Queue Data

#### Get Queue Entries
**GET** `/queues/{queue_id}/entries`

Returns all entries for a specific queue.

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

#### Get Queue Analytics
**GET** `/queues/{queue_id}/analytics`

Returns comprehensive analytics for the queue.

**Response:**
```json
{
    "success": true,
    "data": {
        "statistics": {
            "total_entries": 25,
            "completed_entries": 10,
            "pending_entries": 15,
            "current_number": 15,
            "status": "active"
        },
        "entries_by_status": {
            "queued": 10,
            "kitchen": 3,
            "preparing": 2,
            "completed": 10
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
        "queue_type": "regular",
        "created_at": "2024-01-15T09:00:00.000000Z",
        "last_updated": "2024-01-15T14:45:00.000000Z"
    },
    "message": "Queue analytics retrieved successfully"
}
```

For inventory queues, additional data is included:
```json
{
    "inventory_info": {
        "max_quantity": 1000,
        "remaining_quantity": 750,
        "sold_quantity": 250,
        "sold_percentage": 25.0
    }
}
```

### 10. Get Queue Status
**GET** `/queues/{queue_id}/status`

Returns the current status and statistics of a queue.

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

## Real-time Updates

The system broadcasts real-time updates using WebSocket events:

### Queue Updated Event
- **Channel:** `queue.{queue_id}` and `queues`
- **Event:** `queue.updated`
- **Data:**
```json
{
    "queue_id": 1,
    "queue_name": "Regular Queue",
    "current_number": 16,
    "status": "active",
    "type": "regular",
    "remaining_quantity": null,
    "updated_at": "2024-01-15T14:45:00.000000Z"
}
```

### Stock Depleted Event
- **Channel:** `queue.{queue_id}`, `queues`, and `alerts`
- **Event:** `stock.depleted`
- **Data:**
```json
{
    "queue_id": 2,
    "queue_name": "Steak Promotion",
    "type": "stock_depleted",
    "message": "Stock has been depleted for queue: Steak Promotion",
    "remaining_quantity": 0,
    "max_quantity": 1000,
    "timestamp": "2024-01-15T14:45:00.000000Z"
}
```

## Usage Examples

### Creating a Regular Queue
```bash
curl -X POST http://your-domain.com/api/queues \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "Customer Service",
    "type": "regular",
    "status": "active"
  }'
```

### Creating an Inventory Queue
```bash
curl -X POST http://your-domain.com/api/queues \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "Limited Edition Sale",
    "type": "inventory",
    "max_quantity": 500,
    "remaining_quantity": 500,
    "status": "active"
  }'
```

### Calling Next Number
```bash
curl -X POST http://your-domain.com/api/queues/1/call-next \
  -H "Authorization: Bearer {token}"
```

### Adjusting Stock
```bash
curl -X POST http://your-domain.com/api/queues/2/adjust-stock \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "new_quantity": 400
  }'
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `422` - Validation Error
- `500` - Server Error

## Notes

1. **Queue Types:**
   - `regular`: Standard queue without quantity limits
   - `inventory`: Queue with quantity tracking for limited items

2. **Queue Status:**
   - `active`: Queue is running and accepting entries
   - `paused`: Queue is temporarily paused
   - `closed`: Queue is closed (no new entries)

3. **Order Status:**
   - `queued`: Entry is in queue
   - `kitchen`: Order is in kitchen
   - `preparing`: Order is being prepared
   - `serving`: Order is being served
   - `completed`: Order is completed
   - `cancelled`: Order is cancelled

4. **Real-time Features:**
   - All queue updates are broadcasted in real-time
   - Stock depletion triggers automatic alerts
   - Queue status changes are immediately reflected

5. **Security:**
   - All endpoints require authentication
   - Input validation is enforced
   - SQL injection protection is built-in 