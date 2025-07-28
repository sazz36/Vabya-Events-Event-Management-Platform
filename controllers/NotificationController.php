<?php
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
require_once __DIR__ . '/../Config/db.php';

header('Content-Type: application/json');

class NotificationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create notification for new event (admin to all attendees)
     */
    public function notifyNewEvent($eventId, $eventTitle, $adminId) {
        try {
            // Get all attendees
            $stmt = $this->pdo->prepare("
                SELECT id, name, email 
                FROM users 
                WHERE role = 'attendee' AND id != ?
            ");
            $stmt->execute([$adminId]);
            $attendees = $stmt->fetchAll();
            
            $notificationCount = 0;
            foreach ($attendees as $attendee) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications (
                        type, title, message, recipient_id, sender_id, event_id, 
                        event_title, user_name, status, priority, created_at
                    ) VALUES (
                        'event_created', 'New Event Available', 
                        'A new event \"{$eventTitle}\" has been added. Check it out!',
                        ?, ?, ?, ?, ?, 'pending', 'medium', NOW()
                    )
                ");
                
                $stmt->execute([
                    $attendee['id'],
                    $adminId,
                    $eventId,
                    $eventTitle,
                    $attendee['name']
                ]);
                $notificationCount++;
            }
            
            return ['success' => true, 'notifications_sent' => $notificationCount];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create notification for new booking request (attendee to admin)
     */
    public function notifyNewBooking($bookingId, $eventTitle, $attendeeName, $attendeeId, $eventId) {
        try {
            // Get admin users
            $stmt = $this->pdo->prepare("
                SELECT id, name 
                FROM users 
                WHERE role = 'admin'
            ");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            $notificationCount = 0;
            foreach ($admins as $admin) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO notifications (
                        type, title, message, recipient_id, sender_id, event_id,
                        booking_id, event_title, user_name, status, priority, created_at
                    ) VALUES (
                        'booking_request', 'New Booking Request', 
                        '{$attendeeName} has requested to book \"{$eventTitle}\". Please review and approve.',
                        ?, ?, ?, ?, ?, ?, 'pending', 'high', NOW()
                    )
                ");
                
                $stmt->execute([
                    $admin['id'],
                    $attendeeId,
                    $eventId,
                    $bookingId,
                    $eventTitle,
                    $attendeeName
                ]);
                $notificationCount++;
            }
            
            return ['success' => true, 'notifications_sent' => $notificationCount];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create notification for booking approval/rejection (admin to attendee)
     */
    public function notifyBookingStatus($bookingId, $eventTitle, $attendeeId, $status, $adminId) {
        try {
            $title = $status === 'approved' ? 'Booking Approved' : 'Booking Rejected';
            $message = $status === 'approved' 
                ? "Your booking for '{$eventTitle}' has been approved! Your event is confirmed."
                : "Your booking for '{$eventTitle}' has been rejected. Please contact support for more information.";
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (
                    type, title, message, recipient_id, sender_id, event_id,
                    booking_id, event_title, user_name, status, priority, created_at
                ) VALUES (
                    'booking_{$status}', ?, ?, ?, ?, 
                    (SELECT event_id FROM bookings WHERE id = ?),
                    ?, ?, ?, '{$status}', 'medium', NOW()
                )
            ");
            
            $stmt->execute([
                $title,
                $message,
                $attendeeId,
                $adminId,
                $bookingId,
                $bookingId,
                $eventTitle,
                'Admin'
            ]);
            
            return ['success' => true, 'notification_sent' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get notifications for a specific user with pagination support
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
            SELECT * FROM notifications 
                WHERE recipient_id = ? 
            ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE recipient_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
    } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
            UPDATE notifications 
                SET is_read = 1, updated_at = NOW() 
                WHERE id = ? AND recipient_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, updated_at = NOW() 
                WHERE recipient_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get total number of notifications for a user
     */
    public function getTotalNotifications($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total 
                FROM notifications 
                WHERE recipient_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
}

// Only handle requests if this file is accessed directly (not included)
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    // Handle requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            $notificationService = new NotificationService($pdo);
            
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'markAsRead':
                    if (!isset($_SESSION['user']['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'User not logged in']);
                        exit;
                    }
                    
                    $notificationId = $_POST['notification_id'] ?? '';
                    $userId = $_SESSION['user']['user_id'];
                    
                    $result = $notificationService->markAsRead($notificationId, $userId);
                    echo json_encode($result);
                    break;
                    
                case 'markAllAsRead':
                    if (!isset($_SESSION['user']['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'User not logged in']);
                        exit;
                    }
                    
                    $userId = $_SESSION['user']['user_id'];
                    $result = $notificationService->markAllAsRead($userId);
                    echo json_encode($result);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            $notificationService = new NotificationService($pdo);
            
            if (!isset($_SESSION['user']['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'User not logged in']);
                exit;
            }
            
            $userId = $_SESSION['user']['user_id'];
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'getNotifications':
                    $limit = $_GET['limit'] ?? 20;
                    $notifications = $notificationService->getUserNotifications($userId, $limit);
                    echo json_encode(['success' => true, 'notifications' => $notifications]);
                    break;
                    
                case 'getUnreadCount':
                    $count = $notificationService->getUnreadCount($userId);
                    echo json_encode(['success' => true, 'count' => $count]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?> 