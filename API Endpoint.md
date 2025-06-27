## Queue Endpoints
http://localhost:8000/api
### 1. Get All Queues
**GET** `/queues`
### 2. Create Queue
**POST** `/queues`
### 3. Get Queue Details
**GET** `/queues/{queue_id}`
### 4. Update/Edit Queue
**PUT** `/queues/{queue_id}`
### 5. Delete Queue
**DELETE** `/queues/{queue_id}`
### 6. Queue Control Operations
#### Reset Queue
**POST** `/queues/{queue_id}/reset`
#### Pause Queue
**POST** `/queues/{queue_id}/pause`
#### Resume Queue
**POST** `/queues/{queue_id}/resume`


#### Close Queue
**POST** `/queues/{queue_id}/close`
### 7. Queue Number Operations
#### Call Next Number
**POST** `/queues/{queue_id}/call-next`
#### Wueue status
**POST** `/queues/{queue_id}/status`
#### Skip Current Number
**POST** `/queues/{queue_id}/skip`
#### Recall Current Number
**POST** `/queues/{queue_id}/recall`
#### Adjust Stock
**POST** `/queues/{queue_id}/adjust-stock`
#### Undo Last Entry
**POST** `/queues/{queue_id}/undo-last-entry`

### 9. Queue Data
#### Get Queue Entries
**GET** `/queues/{queue_id}/entries`
#### Get Queue Analytics
**GET** `/queues/{queue_id}/analytics
### 10. Get Queue Status
**GET** `/queues/{queue_id}/status`

### Queue Updated Event
- **Channel:** `queue.{queue_id}` and `queues`
- **Event:** `queue.updated`

### Stock Depleted Event
- **Channel:** `queue.{queue_id}`, `queues`, and `alerts`
- **Event:** `stock.depleted`



## Queue Entry Endpoints

### 1. Get All Queue Entries
https://localhost:8000/api
**GET** `/entries`
**Query Parameters:**
- `status` - Filter by order status (queued, kitchen, preparing, serving, completed, cancelled)
- `cashier_id` - Filter by cashier ID
- `date` - Filter by creation date (YYYY-MM-DD)
- `queue_id` - Filter by queue ID

### 2. Create Queue Entry
**POST** `/entries`
### 3. Get Queue Entry Details
**GET** `/entries/{entry_id}`
### 4. Update Queue Entry
**PUT** `/entries/{entry_id}`
### 5. Delete Queue Entry
**DELETE** `/entries/{entry_id}`
### 6. Update Entry Status
**PATCH** `/entries/{entry_id}/status`
### 7. Cancel Queue Entry
**POST** `/entries/{entry_id}/cancel`
### 8. Get Entries by Status
**GET** `/entries/status/{status}`
### 9. Get Active Entries for Queue
**GET** `/queues/{queue_id}/entries/active`
### 10. Get Next Entry for Queue
**GET** `/queues/{queue_id}/entries/next`
### 11. Get Entries by Cashier
**GET** `/entries/cashier/{cashier_id}`
### 12. Get Entry Statistics
**GET** `/entries/stats`
### 13. Bulk Update Entry Statuses
**POST** `/entries/bulk-update-status`
### 14. Get Entry Timeline
**GET** `/entries/{entry_id}/timeline`
### 15. Search Entries
**GET** `/entries/search`


## Cashier Endpoints
https://localhost:8000/api
### 1. Get All Cashiers
**GET** `/cashiers`
### 2. Create Cashier
**POST** `/cashiers`
### 3. Get Cashier Details
**GET** `/cashiers/{cashier_id}`
### 4. Update Cashier
**PUT** `/cashiers/{cashier_id}`
### 5. Delete Cashier
**DELETE** `/cashiers/{cashier_id}`
### 6. Assign Cashier to Queue
**POST** `/cashiers/{cashier_id}/assign`
### 7. Activate/Deactivate Cashier
**POST** `/cashiers/{cashier_id}/set-active`
### 8. Get All Queues with Cashiers
**GET** `/queues-with-cashiers`


### 1. Fetch Widget Data
**GET** `/widgets/data`
### 2. Get Widget Statistics
**GET** `/widgets/stats`
### 3. Get Real-time Widget Data
**GET** `/widgets/real-time`
### 4. Get Widget Preview Data
**GET** `/widgets/preview`
### 5. Get Widgets by Type
**GET** `/widgets/type/{type}`
### 6. Update Widget Settings
**PATCH** `/widgets/{widget_id}/settings`
### 7. Get Widgets by Layout
**GET** `/layouts/{layout_id}/widgets`