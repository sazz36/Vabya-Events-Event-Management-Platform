# Real-time Notification System for Event Sphere

## Overview

This document describes the implementation of a comprehensive real-time notification system for the Event Sphere platform. The system provides instant notifications for both admins and attendees when important events occur in the system.

## Features

### 🔔 **Real-time Notifications**
- **New Event Notifications**: When an admin creates a new event, all attendees receive instant notifications
- **Booking Request Notifications**: When an attendee makes a booking, all admins receive notifications
- **Booking Status Notifications**: When an admin approves/rejects a booking, the attendee receives a notification
- **Real-time Updates**: Notifications appear instantly without page refresh

### 📱 **User Interface**
- **Notification Bell**: Dropdown notification bell in the navigation bar
- **Unread Count Badge**: Shows number of unread notifications
- **Dedicated Notifications Page**: Full notifications management interface
- **Mark as Read**: Individual and bulk mark as read functionality

### 🔄 **Auto-refresh**
- **Polling System**: Checks for new notifications every 30 seconds
- **Live Updates**: Badge count and notification list update automatically

## Database Schema

### Enhanced Notifications Table

```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('event_created', 'booking_request', 'booking_approved', 'booking_rejected', 'payment_received', 'general') DEFAULT 'general',
    title VARCHAR(255) DEFAULT 'Notification',
    message TEXT,
    recipient_id INT DEFAULT NULL,
    sender_id INT DEFAULT NULL,
    event_id INT DEFAULT NULL,
    booking_id INT,
    event_title VARCHAR(255),
    user_name VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_read TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_recipient (recipient_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);
```

## Implementation Details

### 1. NotificationService Class

The core notification functionality is implemented in the `NotificationService` class located in `controllers/NotificationController.php`.

#### Key Methods:

- `notifyNewEvent($eventId, $eventTitle, $adminId)`: Sends notifications to all attendees when a new event is created
- `notifyNewBooking($bookingId, $eventTitle, $attendeeName, $attendeeId, $eventId)`: Sends notifications to all admins when a booking is made
- `notifyBookingStatus($bookingId, $eventTitle, $attendeeId, $status, $adminId)`: Sends notifications to attendees when booking status changes
- `getUserNotifications($userId, $limit)`: Retrieves notifications for a specific user
- `getUnreadCount($userId)`: Gets unread notification count for a user
- `markAsRead($notificationId, $userId)`: Marks a notification as read
- `markAllAsRead($userId)`: Marks all notifications as read for a user

### 2. Frontend Components

#### Notification Bell Component (`views/components/notification_bell.php`)
- Dropdown notification interface
- Real-time unread count badge
- Mark as read functionality
- Auto-refresh every 30 seconds

#### Notifications Page (`views/dashboard_notifications.php`)
- Dedicated notifications management page
- Full notification history
- Bulk mark as read functionality
- Filtering and sorting options

### 3. Integration Points

#### Event Creation
When an admin creates a new event in `controllers/EventControllers.php`:
```php
// Send notifications to all attendees about new event
$notificationService = new NotificationService($pdo);
$notificationService->notifyNewEvent($eventId, $title, $_SESSION['user_id']);
```

#### Booking Creation
When an attendee makes a booking in `controllers/BookingController.php`:
```php
// Create notification for admin using the new notification service
$notificationService = new NotificationService($pdo);
$notificationService->notifyNewBooking($bookingId, $event['title'], $userName, $userId, $eventId);
```

#### Booking Approval/Rejection
When an admin approves or rejects a booking in `views/admin_dashboard.php`:
```php
// Send notification to attendee
$notificationService = new NotificationService($pdo);
$notificationService->notifyBookingStatus($id, $booking['event_title'], $booking['user_id'], 'approved', $_SESSION['user']['user_id']);
```

## Setup Instructions

### 1. Database Setup
Run the database enhancement script:
```bash
php enhance_notifications_table.php
```

### 2. Test the System
Run the test script to verify everything is working:
```bash
php test_notification_system.php
```

### 3. Include Notification Bell
The notification bell is automatically included in the main layout (`views/layout.php`).

## Usage Examples

### For Admins
1. **Create a new event**: All attendees will receive a notification
2. **View booking requests**: See notifications when attendees make bookings
3. **Approve/reject bookings**: Attendees receive status update notifications

### For Attendees
1. **Receive new event notifications**: Get notified when new events are added
2. **Make bookings**: Admins receive notification of your booking request
3. **Get booking status updates**: Receive notifications when bookings are approved/rejected

## Notification Types

| Type | Description | Recipient | Trigger |
|------|-------------|-----------|---------|
| `event_created` | New event added | All attendees | Admin creates event |
| `booking_request` | New booking request | All admins | Attendee makes booking |
| `booking_approved` | Booking approved | Attendee | Admin approves booking |
| `booking_rejected` | Booking rejected | Attendee | Admin rejects booking |
| `payment_received` | Payment received | Admin | Payment verification |
| `general` | General notification | Specific user | System events |

## API Endpoints

### GET Endpoints
- `controllers/NotificationController.php?action=getNotifications&limit=20`: Get user notifications
- `controllers/NotificationController.php?action=getUnreadCount`: Get unread count

### POST Endpoints
- `controllers/NotificationController.php` with `action=markAsRead`: Mark notification as read
- `controllers/NotificationController.php` with `action=markAllAsRead`: Mark all as read

## JavaScript Integration

The notification system uses a `NotificationManager` class that handles:
- Loading notifications via AJAX
- Real-time polling for new notifications
- Mark as read functionality
- UI updates and animations

## Customization

### Adding New Notification Types
1. Add the new type to the database ENUM
2. Create a new method in `NotificationService`
3. Call the method from the appropriate controller
4. Add styling for the new notification type

### Modifying Notification Messages
Edit the message templates in the `NotificationService` methods to customize notification content.

### Changing Polling Frequency
Modify the `setInterval` call in the `NotificationManager` class (currently 30 seconds).

## Troubleshooting

### Common Issues

1. **Notifications not appearing**: Check database connection and user roles
2. **Badge not updating**: Verify JavaScript is loading properly
3. **Polling not working**: Check browser console for JavaScript errors

### Debug Mode
Enable debug logging by adding error logging to the `NotificationService` methods.

## Performance Considerations

- Notifications are paginated (default 20 per page)
- Unread count is cached and updated efficiently
- Database indexes optimize query performance
- Polling interval is configurable to balance responsiveness and server load

## Security

- All notifications are user-specific (recipient_id validation)
- SQL injection protection via prepared statements
- XSS protection via HTML escaping
- Session-based authentication required

## Future Enhancements

- Push notifications via WebSockets
- Email notifications
- SMS notifications
- Notification preferences per user
- Advanced filtering and search
- Notification templates
- Bulk notification management

---

This notification system provides a solid foundation for real-time communication between admins and attendees in the Event Sphere platform. 