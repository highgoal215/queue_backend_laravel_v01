# Queue Management System - Frontend Design Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Core Entities & Data Models](#core-entities--data-models)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Screen Layouts & UI Components](#screen-layouts--ui-components)
5. [API Integration Guide](#api-integration-guide)
6. [Real-time Features](#real-time-features)
7. [Design System Guidelines](#design-system-guidelines)
8. [Frontend Architecture](#frontend-architecture)
9. [Implementation Checklist](#implementation-checklist)

## System Overview

### Purpose
A comprehensive queue management system for businesses that need to manage customer queues, track orders, and display real-time information on digital displays.

### Key Features
- **Queue Management**: Create, manage, and control multiple queues
- **Order Tracking**: Real-time order status updates with QR code tracking
- **Cashier Management**: Assign and manage cashiers across queues
- **Digital Displays**: Customizable screen layouts with widgets
- **Inventory Management**: Track stock levels for inventory-based queues
- **Analytics**: Real-time statistics and reporting

## Core Entities & Data Models

### 1. Queue
**Purpose**: Represents a service line or order queue

**Key Properties**:
- `id`: Unique identifier
- `name`: Queue name (e.g., "Coffee Counter", "Food Orders")
- `type`: "regular" or "inventory"
- `status`: "active", "paused", "closed"
- `current_number`: Current queue number
- `max_quantity`: Maximum stock (inventory queues)
- `remaining_quantity`: Available stock (inventory queues)

**UI Representation**:
- Queue cards with status indicators
- Real-time queue number display
- Stock level indicators for inventory queues

### 2. Queue Entry
**Purpose**: Individual customer orders/entries in a queue

**Key Properties**:
- `id`: Unique identifier
- `queue_id`: Associated queue
- `queue_number`: Sequential number in queue
- `customer_name`: Customer's name
- `phone_number`: Contact number
- `order_details`: JSON object with order items
- `order_status`: "queued", "kitchen", "preparing", "serving", "completed", "cancelled"
- `quantity_purchased`: Items quantity (inventory queues)
- `estimated_wait_time`: Expected wait time
- `notes`: Additional notes
- `cashier_id`: Assigned cashier

**UI Representation**:
- Order cards with status badges
- Timeline view for order progress
- Customer information display

### 3. Cashier
**Purpose**: Staff members who serve customers

**Key Properties**:
- `id`: Unique identifier
- `name`: Cashier name
- `employee_id`: Employee ID
- `status`: Current status
- `assigned_queue_id`: Assigned queue
- `is_active`: Active status
- `is_available`: Availability status
- `current_customer_id`: Currently serving customer
- `total_served`: Total customers served
- `average_service_time`: Average service time
- `email`, `phone`: Contact information
- `role`: Job role
- `shift_start`, `shift_end`: Shift times

**UI Representation**:
- Cashier profile cards
- Availability status indicators
- Performance metrics display

### 4. Screen Layout
**Purpose**: Digital display configurations

**Key Properties**:
- `id`: Unique identifier
- `name`: Layout name
- `device_id`: Target device identifier
- `layout_config`: JSON configuration
- `is_default`: Default layout flag

**UI Representation**:
- Layout preview thumbnails
- Configuration panels
- Device assignment interface

### 5. Widget
**Purpose**: Display components for digital screens

**Key Properties**:
- `id`: Unique identifier
- `screen_layout_id`: Associated layout
- `type`: Widget type
- `position`: Position coordinates
- `settings`: Configuration settings

**Widget Types**:
- Queue Display: Shows current queue numbers
- Order Status: Real-time order updates
- Cashier Status: Staff availability
- Analytics: Statistics and charts
- Announcements: Messages and notifications

## User Roles & Permissions

### 1. Admin/Manager
**Capabilities**:
- Full system access
- Queue creation and management
- Cashier assignment and management
- Screen layout configuration
- Analytics and reporting
- System settings

**UI Requirements**:
- Dashboard with overview metrics
- Queue management interface
- Staff management panel
- Configuration settings
- Analytics dashboard

### 2. Cashier
**Capabilities**:
- View assigned queue
- Update order status
- Call next customer
- View customer information
- Basic queue operations

**UI Requirements**:
- Simplified queue interface
- Order management panel
- Customer information display
- Status update controls

### 3. Customer (Public)
**Capabilities**:
- Join queue
- Track order status via QR code
- View estimated wait times

**UI Requirements**:
- Queue joining interface
- QR code scanner
- Order tracking page
- Wait time display

## Screen Layouts & UI Components

### 1. Admin Dashboard
**Layout Structure**:
```
┌─────────────────────────────────────────────────────────┐
│ Header: Logo, User Menu, Notifications                  │
├─────────────────────────────────────────────────────────┤
│ Quick Stats: Total Queues, Active Orders, Staff Online  │
├─────────────────────────────────────────────────────────┤
│ Main Content Area                                       │
│ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐        │
│ │ Queue List  │ │ Recent      │ │ System      │        │
│ │             │ │ Orders      │ │ Alerts      │        │
│ └─────────────┘ └─────────────┘ └─────────────┘        │
├─────────────────────────────────────────────────────────┤
│ Footer: Status, Version, Support                        │
└─────────────────────────────────────────────────────────┘
```

**Key Components**:
- Navigation sidebar with menu items
- Quick action buttons
- Real-time statistics cards
- Recent activity feed
- System status indicators

### 2. Queue Management Interface
**Layout Structure**:
```
┌─────────────────────────────────────────────────────────┐
│ Queue Header: Name, Status, Controls                    │
├─────────────────────────────────────────────────────────┤
│ Queue Info: Current Number, Total Entries, Wait Time   │
├─────────────────────────────────────────────────────────┤
│ Action Buttons: Call Next, Pause, Reset, Close         │
├─────────────────────────────────────────────────────────┤
│ Entries List:                                           │
│ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐        │
│ │ #001        │ │ #002        │ │ #003        │        │
│ │ Customer    │ │ Customer    │ │ Customer    │        │
│ │ Status      │ │ Status      │ │ Status      │        │
│ └─────────────┘ └─────────────┘ └─────────────┘        │
└─────────────────────────────────────────────────────────┘
```

**Key Components**:
- Queue status indicator (Active/Paused/Closed)
- Current number display
- Entry cards with status badges
- Action buttons for queue control
- Search and filter options

### 3. Digital Display Screen
**Layout Structure**:
```
┌─────────────────────────────────────────────────────────┐
│ Header: Business Name, Current Time                     │
├─────────────────────────────────────────────────────────┤
│ Main Queue Display:                                     │
│ ┌─────────────────────────────────────────────────────┐ │
│ │                    NOW SERVING                     │ │
│ │                                                     │ │
│ │                   QUEUE #001                       │ │
│ │                                                     │ │
│ │                Coffee Counter                      │ │
│ └─────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│ Sidebar: Next Numbers, Cashier Status, Messages        │
└─────────────────────────────────────────────────────────┘
```

**Key Components**:
- Large, readable queue numbers
- Animated transitions
- Status indicators
- Cashier availability display
- Announcement ticker

### 4. Cashier Interface
**Layout Structure**:
```
┌─────────────────────────────────────────────────────────┐
│ Header: Cashier Name, Queue, Status                    │
├─────────────────────────────────────────────────────────┤
│ Current Customer:                                       │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Customer: John Doe                                  │ │
│ │ Order: Large Coffee, Croissant                      │ │
│ │ Status: Preparing                                   │ │
│ └─────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│ Action Buttons: Complete, Skip, Call Next              │
├─────────────────────────────────────────────────────────┤
│ Queue List: Upcoming customers                         │
└─────────────────────────────────────────────────────────┘
```

**Key Components**:
- Current customer display
- Order details panel
- Status update buttons
- Queue preview
- Performance metrics

## API Integration Guide

### Authentication
```javascript
// Login
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}

// Response includes token for subsequent requests
{
  "token": "Bearer token",
  "user": { ... }
}
```

### Queue Management
```javascript
// Get all queues
GET /api/queues

// Create queue
POST /api/queues
{
  "name": "Coffee Counter",
  "type": "regular",
  "max_quantity": null
}

// Update queue status
POST /api/queues/{id}/pause
POST /api/queues/{id}/resume
POST /api/queues/{id}/close

// Call next customer
POST /api/queues/{id}/call-next
```

### Queue Entries
```javascript
// Create entry
POST /api/entries
{
  "queue_id": 1,
  "customer_name": "John Doe",
  "phone_number": "+1234567890",
  "order_details": {
    "items": ["Large Coffee", "Croissant"],
    "total": 8.50
  },
  "estimated_wait_time": 15
}

// Update status
PATCH /api/entries/{id}/status
{
  "order_status": "preparing"
}

// Get entries by queue
GET /api/queues/{id}/entries
```

### Real-time Data
```javascript
// Get real-time queue data
GET /api/widgets/real-time

// Get queue analytics
GET /api/queues/{id}/analytics

// Get entry statistics
GET /api/entries/stats
```

## Real-time Features

### WebSocket Events
```javascript
// Listen for queue updates
socket.on('queue.updated', (data) => {
  updateQueueDisplay(data);
});

// Listen for new entries
socket.on('entry.created', (data) => {
  addNewEntry(data);
});

// Listen for status changes
socket.on('status.changed', (data) => {
  updateEntryStatus(data);
});
```

### Event Types
1. **QueueUpdated**: Queue status or current number changed
2. **OrderStatusChanged**: Entry status updated
3. **StockDepleted**: Inventory queue out of stock

### Real-time Updates
- Queue number changes
- Order status updates
- Cashier availability
- Stock level changes
- System notifications

## Design System Guidelines

### Color Palette
```css
/* Primary Colors */
--primary-blue: #2563eb;
--primary-green: #10b981;
--primary-red: #ef4444;
--primary-yellow: #f59e0b;

/* Status Colors */
--status-active: #10b981;
--status-paused: #f59e0b;
--status-closed: #ef4444;
--status-queued: #6b7280;
--status-preparing: #3b82f6;
--status-serving: #8b5cf6;
--status-completed: #10b981;
--status-cancelled: #ef4444;

/* Neutral Colors */
--gray-50: #f9fafb;
--gray-100: #f3f4f6;
--gray-200: #e5e7eb;
--gray-300: #d1d5db;
--gray-400: #9ca3af;
--gray-500: #6b7280;
--gray-600: #4b5563;
--gray-700: #374151;
--gray-800: #1f2937;
--gray-900: #111827;
```

### Typography
```css
/* Font Family */
font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

/* Font Sizes */
--text-xs: 0.75rem;
--text-sm: 0.875rem;
--text-base: 1rem;
--text-lg: 1.125rem;
--text-xl: 1.25rem;
--text-2xl: 1.5rem;
--text-3xl: 1.875rem;
--text-4xl: 2.25rem;
--text-5xl: 3rem;

/* Font Weights */
--font-light: 300;
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### Component Library
1. **Buttons**
   - Primary: Blue background, white text
   - Secondary: Gray background, dark text
   - Danger: Red background, white text
   - Success: Green background, white text

2. **Cards**
   - White background, subtle shadow
   - Rounded corners (8px)
   - Padding: 16px-24px

3. **Status Badges**
   - Small, colored pills
   - White text on colored background
   - Rounded corners (12px)

4. **Input Fields**
   - Light gray border
   - Focus state with blue border
   - Rounded corners (6px)

### Spacing System
```css
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-3: 0.75rem;   /* 12px */
--space-4: 1rem;      /* 16px */
--space-5: 1.25rem;   /* 20px */
--space-6: 1.5rem;    /* 24px */
--space-8: 2rem;      /* 32px */
--space-10: 2.5rem;   /* 40px */
--space-12: 3rem;     /* 48px */
--space-16: 4rem;     /* 64px */
--space-20: 5rem;     /* 80px */
```

## Frontend Architecture

### Recommended Tech Stack
1. **Framework**: React.js or Vue.js
2. **State Management**: Redux Toolkit or Pinia
3. **UI Library**: Tailwind CSS + Headless UI
4. **Real-time**: Socket.io or Pusher
5. **HTTP Client**: Axios
6. **Form Handling**: React Hook Form or VeeValidate
7. **Charts**: Chart.js or Recharts
8. **QR Code**: qrcode.js

### Project Structure
```
src/
├── components/
│   ├── common/
│   │   ├── Button.jsx
│   │   ├── Card.jsx
│   │   ├── Modal.jsx
│   │   └── StatusBadge.jsx
│   ├── queue/
│   │   ├── QueueCard.jsx
│   │   ├── QueueList.jsx
│   │   └── QueueControls.jsx
│   ├── entry/
│   │   ├── EntryCard.jsx
│   │   ├── EntryForm.jsx
│   │   └── EntryTimeline.jsx
│   ├── cashier/
│   │   ├── CashierCard.jsx
│   │   └── CashierList.jsx
│   └── display/
│       ├── DigitalDisplay.jsx
│       ├── QueueWidget.jsx
│       └── StatusWidget.jsx
├── pages/
│   ├── Dashboard.jsx
│   ├── QueueManagement.jsx
│   ├── CashierInterface.jsx
│   └── DigitalDisplay.jsx
├── services/
│   ├── api.js
│   ├── auth.js
│   ├── queue.js
│   └── websocket.js
├── store/
│   ├── index.js
│   ├── queueSlice.js
│   └── authSlice.js
└── utils/
    ├── constants.js
    ├── helpers.js
    └── validators.js
```

### State Management
```javascript
// Queue State Structure
{
  queues: {
    items: [],
    loading: false,
    error: null,
    selectedQueue: null
  },
  entries: {
    items: [],
    loading: false,
    error: null,
    filters: {}
  },
  cashiers: {
    items: [],
    loading: false,
    error: null
  },
  auth: {
    user: null,
    token: null,
    isAuthenticated: false
  }
}
```

## Implementation Checklist

### Phase 1: Core Setup
- [ ] Set up frontend project structure
- [ ] Configure authentication system
- [ ] Implement basic routing
- [ ] Set up API service layer
- [ ] Create basic UI components

### Phase 2: Queue Management
- [ ] Queue listing and creation
- [ ] Queue status management
- [ ] Entry creation and management
- [ ] Real-time queue updates
- [ ] Queue analytics display

### Phase 3: Cashier Interface
- [ ] Cashier login and assignment
- [ ] Order status management
- [ ] Customer information display
- [ ] Queue control operations
- [ ] Performance tracking

### Phase 4: Digital Display
- [ ] Screen layout configuration
- [ ] Widget system implementation
- [ ] Real-time display updates
- [ ] QR code generation
- [ ] Customer tracking interface

### Phase 5: Advanced Features
- [ ] Inventory management
- [ ] Advanced analytics
- [ ] Notification system
- [ ] Mobile responsiveness
- [ ] Offline capabilities

### Phase 6: Testing & Optimization
- [ ] Unit and integration tests
- [ ] Performance optimization
- [ ] Accessibility compliance
- [ ] Cross-browser testing
- [ ] User acceptance testing

## Additional Considerations

### Accessibility
- WCAG 2.1 AA compliance
- Keyboard navigation support
- Screen reader compatibility
- High contrast mode support
- Focus management

### Performance
- Lazy loading for components
- Image optimization
- Code splitting
- Caching strategies
- Bundle size optimization

### Security
- Input validation
- XSS prevention
- CSRF protection
- Secure API communication
- Role-based access control

### Mobile Support
- Responsive design
- Touch-friendly interfaces
- Offline functionality
- Push notifications
- Progressive Web App features

This documentation provides a comprehensive foundation for building the frontend and creating Figma designs that align with your Laravel backend system. The modular approach allows for iterative development and easy maintenance. 