# Figma Design Guide - Queue Management System

## Design System Foundation

### Color System
- **Primary Blue**: #2563eb
- **Primary Green**: #10b981  
- **Primary Red**: #ef4444
- **Primary Yellow**: #f59e0b
- **Status Colors**: Active (#10b981), Paused (#f59e0b), Closed (#ef4444)
- **Neutral Grays**: #f9fafb to #111827

### Typography
- **Font**: Inter (Google Fonts)
- **Sizes**: 12px to 48px scale
- **Weights**: Light (300), Regular (400), Medium (500), Semibold (600), Bold (700)

### Spacing System
- **Base Unit**: 4px
- **Scale**: 4px, 8px, 12px, 16px, 20px, 24px, 32px, 40px, 48px, 64px, 80px

## Component Library

### Buttons
- **Primary**: Blue background, white text, 8px radius
- **Secondary**: Gray background, dark text, border
- **Danger**: Red background, white text
- **Success**: Green background, white text

### Cards
- **Standard**: White background, subtle shadow, 12px radius
- **Elevated**: White background, stronger shadow, 12px radius

### Status Badges
- **Design**: Colored pills with white text, 12px radius
- **Colors**: Match status colors (active, paused, closed, etc.)

### Input Fields
- **Style**: White background, gray border, 8px radius
- **Focus**: Blue border, 2px width

## Screen Layouts

### 1. Admin Dashboard
```
Header (64px) - Logo, User Menu, Notifications
Sidebar (280px) - Navigation Menu
Main Content:
  - Quick Stats Row (4 cards)
  - Recent Activity & Alerts (2 columns)
```

### 2. Queue Management
```
Queue Header - Name, Status, Current Number, Actions
Controls Bar - Pause, Resume, Reset, Close
Entries Grid - 4-column layout of entry cards
```

### 3. Digital Display
```
Header - Business Name, Current Time
Main Display - Large "NOW SERVING" with queue number
Sidebar - Next numbers, cashier status, messages
```

### 4. Cashier Interface
```
Header - Cashier info, queue, status, time
Current Customer Panel - Customer details, order, status
Action Buttons - Complete, Skip, Call Next
Queue Preview - Upcoming customers grid
```

## Interactive Elements

### Hover States
- Cards: Shadow increase
- Buttons: Background color change
- Links: Underline or color change

### Loading States
- Skeleton screens with shimmer
- Spinners in primary blue
- Progress bars with color coding

### Error/Success States
- Error: Red text and borders
- Success: Green text and icons

## Responsive Design

### Breakpoints
- Mobile: 320px - 768px
- Tablet: 768px - 1024px  
- Desktop: 1024px - 1440px
- Large: 1440px+

### Mobile Adaptations
- Collapsible sidebar
- Single column layouts
- Full-width buttons
- Smaller text sizes

## Animation Guidelines

### Transitions
- Duration: 200ms
- Easing: Ease-in-out
- Properties: Transform, opacity, color

### Micro-interactions
- Button press: Scale down 2%
- Card hover: Translate Y -2px
- Modal open: Fade in + scale up

## Accessibility

### Color Contrast
- Text: Minimum 4.5:1 ratio
- Large text: Minimum 3:1 ratio

### Focus States
- Visible 2px blue outline
- Logical tab order
- Skip links available

## File Organization

### Figma Structure
```
📁 Design System
├── 🎨 Colors
├── 🔤 Typography  
├── 📏 Spacing
└── 🎯 Components

📁 Screens
├── 🏠 Dashboard
├── 📋 Queue Management
├── 👤 Cashier Interface
├── 📺 Digital Display
└── 📱 Mobile Views

📁 Components
├── 🔘 Buttons
├── 🃏 Cards
├── 🏷️ Badges
├── 📝 Forms
└── 🧭 Navigation
```

### Component Naming
```
Button/Primary
Button/Secondary
Card/Standard
Badge/Status
Input/Text
Navigation/Sidebar
```

This guide provides the foundation for creating consistent, professional designs for your queue management system in Figma. 