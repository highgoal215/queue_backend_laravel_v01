# Widget Management API Documentation

This document provides comprehensive documentation for the Widget Management System API endpoints.

## Base URL
```
http://your-domain.com/api
```

## Authentication
All endpoints require authentication. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Widget Endpoints

### 1. Fetch Widget Data
**GET** `/widgets/data`

Returns widget data for a specific device or layout.

**Query Parameters:**
- `device_id` - Device ID to get widget data for
- `layout_id` - Layout ID to get widget data for
- `widget_types` - Comma-separated list of widget types to filter

**Response:**
```json
{
    "success": true,
    "data": {
        "device_id": "display-001",
        "layout": {
            "id": 1,
            "name": "Main Display Layout",
            "grid": {"columns": 12, "rows": 8}
        },
        "widgets": [
            {
                "id": 1,
                "type": "time",
                "position": {"x": 0, "y": 0, "w": 3, "h": 2},
                "settings": {"format": "H:i:s"},
                "data": {
                    "current_time": "14:30:25",
                    "timezone": "UTC",
                    "format": "H:i:s"
                }
            },
            {
                "id": 2,
                "type": "queue",
                "position": {"x": 3, "y": 0, "w": 6, "h": 4},
                "settings": {"queue_id": 1},
                "data": {
                    "current_number": "A015",
                    "estimated_wait": "5 minutes",
                    "total_in_queue": 12,
                    "queue_name": "Customer Service",
                    "queue_status": "active"
                }
            }
        ]
    },
    "message": "Widget data retrieved successfully"
}
```

### 2. Get Widget Statistics
**GET** `/widgets/stats`

Returns comprehensive statistics about widgets across all layouts.

**Response:**
```json
{
    "success": true,
    "data": {
        "total_widgets": 25,
        "widgets_by_type": {
            "time": 8,
            "date": 5,
            "queue": 6,
            "weather": 3,
            "announcement": 2,
            "custom": 1
        },
        "layouts_with_widgets": 5,
        "total_layouts": 6,
        "widget_usage_percentage": 83.33,
        "most_used_widget_type": "time",
        "average_widgets_per_layout": 4.17
    },
    "message": "Widget statistics retrieved successfully"
}
```

### 3. Get Real-time Widget Data
**GET** `/widgets/real-time`

Returns real-time data for widgets with optional filtering.

**Query Parameters:**
- `device_id` - Device ID to get real-time data for
- `include_queue_data` - Include queue data (true/false)
- `include_weather_data` - Include weather data (true/false)

**Response:**
```json
{
    "success": true,
    "data": {
        "timestamp": "2024-01-15T14:30:25.000000Z",
        "widgets": [
            {
                "id": 1,
                "type": "time",
                "data": {
                    "current_time": "14:30:25",
                    "timezone": "UTC"
                }
            },
            {
                "id": 2,
                "type": "queue",
                "data": {
                    "current_number": "A015",
                    "estimated_wait": "5 minutes",
                    "total_in_queue": 12,
                    "queue_name": "Customer Service"
                }
            }
        ]
    },
    "message": "Real-time widget data retrieved successfully"
}
```

### 4. Get Widget Preview Data
**GET** `/widgets/preview`

Returns preview data for widget types with sample settings.

**Query Parameters:**
- `widget_type` - Specific widget type to preview
- `settings` - JSON string of widget settings

**Response:**
```json
{
    "success": true,
    "data": {
        "widget_type": "queue",
        "settings": {"queue_id": 1},
        "preview_data": {
            "current_number": "A001",
            "estimated_wait": "5 minutes",
            "total_in_queue": 12,
            "queue_name": "Main Queue"
        }
    },
    "message": "Widget preview data retrieved successfully"
}
```

### 5. Get Widgets by Type
**GET** `/widgets/type/{type}`

Returns all widgets of a specific type across all layouts.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type": "time",
            "position": {"x": 0, "y": 0, "w": 3, "h": 2},
            "settings_json": {"format": "H:i:s"},
            "layout": {
                "id": 1,
                "name": "Main Display Layout",
                "device_id": "display-001"
            }
        }
    ],
    "message": "Widgets of type 'time' retrieved successfully"
}
```

### 6. Update Widget Settings
**PATCH** `/widgets/{widget_id}/settings`

Updates the settings for a specific widget.

**Request Body:**
```json
{
    "settings": {
        "format": "H:i",
        "timezone": "America/New_York"
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "type": "time",
        "settings_json": {
            "format": "H:i",
            "timezone": "America/New_York"
        },
        "updated_at": "2024-01-15T14:30:25.000000Z"
    },
    "message": "Widget settings updated successfully"
}
```

### 7. Get Widgets by Layout
**GET** `/layouts/{layout_id}/widgets`

Returns all widgets for a specific layout.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type": "time",
            "position": {"x": 0, "y": 0, "w": 3, "h": 2},
            "settings": {"format": "H:i:s"},
            "data": {
                "current_time": "14:30:25",
                "timezone": "UTC"
            }
        }
    ],
    "message": "Layout widgets retrieved successfully"
}
```

## Widget Types and Data Structures

### Time Widget
**Type:** `time`
**Settings:**
```json
{
    "format": "H:i:s",
    "timezone": "UTC"
}
```
**Data:**
```json
{
    "current_time": "14:30:25",
    "timezone": "UTC",
    "format": "H:i:s"
}
```

### Date Widget
**Type:** `date`
**Settings:**
```json
{
    "format": "l, F j, Y"
}
```
**Data:**
```json
{
    "current_date": "Monday, January 15, 2024",
    "day_of_week": "Monday",
    "format": "l, F j, Y"
}
```

### Queue Widget
**Type:** `queue`
**Settings:**
```json
{
    "queue_id": 1,
    "show_estimated_wait": true
}
```
**Data:**
```json
{
    "current_number": "A015",
    "estimated_wait": "5 minutes",
    "total_in_queue": 12,
    "queue_name": "Customer Service",
    "queue_status": "active"
}
```

### Weather Widget
**Type:** `weather`
**Settings:**
```json
{
    "location": "New York",
    "units": "celsius"
}
```
**Data:**
```json
{
    "temperature": "22°C",
    "condition": "Sunny",
    "location": "New York",
    "humidity": "65%",
    "wind_speed": "5 km/h",
    "updated_at": "2024-01-15T14:30:25.000000Z"
}
```

### Announcement Widget
**Type:** `announcement`
**Settings:**
```json
{
    "message": "Welcome to our service!",
    "type": "info",
    "duration": 5000
}
```
**Data:**
```json
{
    "message": "Welcome to our service!",
    "type": "info",
    "duration": 5000
}
```

### Custom Widget
**Type:** `custom`
**Settings:**
```json
{
    "data": {
        "title": "Custom Content",
        "content": "Any custom data"
    }
}
```
**Data:**
```json
{
    "title": "Custom Content",
    "content": "Any custom data"
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

## Usage Examples

### Getting Widget Data for Device
```bash
curl -X GET "http://your-domain.com/api/widgets/data?device_id=display-001" \
  -H "Authorization: Bearer {token}"
```

### Getting Real-time Data
```bash
curl -X GET "http://your-domain.com/api/widgets/real-time?include_queue_data=true" \
  -H "Authorization: Bearer {token}"
```

### Updating Widget Settings
```bash
curl -X PATCH http://your-domain.com/api/widgets/1/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "settings": {
        "format": "H:i",
        "timezone": "America/New_York"
    }
  }'
```

### Getting Widget Statistics
```bash
curl -X GET http://your-domain.com/api/widgets/stats \
  -H "Authorization: Bearer {token}"
```

## Notes

1. **Widget Data:**
   - Widget data is generated dynamically based on type and settings
   - Real-time data includes current values (time, queue status, etc.)
   - Preview data shows sample values for configuration

2. **Widget Positioning:**
   - Position data includes x, y coordinates and width/height
   - Coordinates are relative to the layout grid
   - Grid system supports responsive layouts

3. **Settings Management:**
   - Settings are stored as JSON
   - Each widget type has specific setting requirements
   - Settings can be updated without recreating the widget

4. **Real-time Updates:**
   - Real-time endpoints provide current data
   - Queue data includes live queue status
   - Time widgets show current time

5. **Performance:**
   - Widget data is cached when possible
   - Real-time data is fetched on demand
   - Statistics are calculated efficiently

6. **Security:**
   - All endpoints require authentication
   - Widget settings are validated
   - Device access is controlled 