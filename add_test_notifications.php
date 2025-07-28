<?php
require_once 'Config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get a user and booking for testing
    $user = $conn->query("SELECT id FROM users LIMIT 1")->fetch();
    $booking = $conn->query("SELECT id FROM bookings LIMIT 1")->fetch();
    
    if (!$user || !$booking) {
        echo "No users or bookings found. Please create some first.\n";
        exit;
    }
    
    // Sample notifications
    $notifications = [
        [
            'booking_id' => $booking['id'],
            'event_title' => 'Tech Conference 2024',
            'user_name' => 'John Doe',
            'message' => 'Your booking for Tech Conference 2024 has been confirmed!',
            'type' => 'booking_confirmed',
            'is_read' => 0
        ],
        [
            'booking_id' => $booking['id'],
            'event_title' => 'Music Festival',
            'user_name' => 'John Doe',
            'message' => 'Reminder: Music Festival starts in 2 days. Don\'t forget to bring your ticket!',
            'type' => 'event_reminder',
            'is_read' => 0
        ],
        [
            'booking_id' => $booking['id'],
            'event_title' => 'Business Seminar',
            'user_name' => 'John Doe',
            'message' => 'Payment received successfully for Business Seminar.',
            'type' => 'payment_success',
            'is_read' => 1
        ],
        [
            'booking_id' => $booking['id'],
            'event_title' => 'Art Exhibition',
            'user_name' => 'John Doe',
            'message' => 'The Art Exhibition has been rescheduled to next week.',
            'type' => 'event_updated',
            'is_read' => 0
        ]
    ];
    
    // Insert notifications
    $stmt = $conn->prepare("
        INSERT INTO notifications (booking_id, event_title, user_name, message, type, is_read, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    foreach ($notifications as $notification) {
        $stmt->execute([
            $notification['booking_id'],
            $notification['event_title'],
            $notification['user_name'],
            $notification['message'],
            $notification['type'],
            $notification['is_read']
        ]);
    }
    
    echo "Test notifications added successfully!\n";
    echo "Added " . count($notifications) . " notifications.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 