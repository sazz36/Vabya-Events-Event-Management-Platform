<?php
session_start();
require_once __DIR__ . '/../Config/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user']['user_id']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    switch ($action) {
        case 'getBookings':
            getAttendeeBookings($pdo);
            break;
            
        case 'updateAttendee':
            updateAttendee($pdo);
            break;
            
        case 'sendNotification':
            sendNotification($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

function getAttendeeBookings($pdo) {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.booking_time, b.total_amount, b.status,
                   e.title as event_title, e.date as event_date
            FROM bookings b
            JOIN events e ON b.event_id = e.id
            JOIN users u ON b.user_id = u.id
            WHERE u.email = ?
            ORDER BY b.booking_time DESC
        ");
        
        $stmt->execute([$email]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'bookings' => $bookings]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching bookings: ' . $e->getMessage()]);
    }
}

function updateAttendee($pdo) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        return;
    }
    
    try {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != (SELECT id FROM users WHERE email = ? LIMIT 1)");
        $stmt->execute([$email, $email]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
            return;
        }
        
        // Update user information
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, email = ?, phone = ?, updated_at = NOW()
            WHERE email = ?
        ");
        
        $stmt->execute([$name, $email, $phone, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Attendee updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or attendee not found']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating attendee: ' . $e->getMessage()]);
    }
}

function sendNotification($pdo) {
    $recipient = $_POST['recipient'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $type = $_POST['type'] ?? 'general';
    
    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
        return;
    }
    
    // Extract email from recipient field (format: "Name (email)")
    preg_match('/\(([^)]+)\)/', $recipient, $matches);
    $email = $matches[1] ?? '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid recipient email']);
        return;
    }
    
    try {
        // Get user ID from email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Create notification record
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([$user['id'], $subject, $message, $type]);
        
        // In a real application, you would also send email/SMS here
        // For now, we'll just create the notification record
        
        echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error sending notification: ' . $e->getMessage()]);
    }
}
?> 