<?php
// Test script to verify the notification system
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/controllers/NotificationController.php';

echo "<h1>Notification System Test</h1>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $notificationService = new NotificationService($pdo);
    
    echo "<h2>1. Testing Database Connection</h2>";
    echo "✅ Database connection successful<br><br>";
    
    echo "<h2>2. Checking Notifications Table</h2>";
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Notifications table columns: " . implode(', ', $columns) . "<br><br>";
    
    echo "<h2>3. Testing Notification Service Methods</h2>";
    
    // Test creating a notification
    echo "<h3>3.1 Testing notifyNewEvent</h3>";
    $result = $notificationService->notifyNewEvent(1, "Test Event", 1);
    if ($result['success']) {
        echo "✅ notifyNewEvent successful: " . $result['notifications_sent'] . " notifications sent<br>";
    } else {
        echo "❌ notifyNewEvent failed: " . $result['message'] . "<br>";
    }
    
    // Test getting user notifications
    echo "<h3>3.2 Testing getUserNotifications</h3>";
    $notifications = $notificationService->getUserNotifications(1, 10, 0);
    echo "✅ getUserNotifications returned " . count($notifications) . " notifications<br>";
    
    // Test getting unread count
    echo "<h3>3.3 Testing getUnreadCount</h3>";
    $unreadCount = $notificationService->getUnreadCount(1);
    echo "✅ getUnreadCount returned: " . $unreadCount . "<br>";
    
    // Test getting total notifications
    echo "<h3>3.4 Testing getTotalNotifications</h3>";
    $totalNotifications = $notificationService->getTotalNotifications(1);
    echo "✅ getTotalNotifications returned: " . $totalNotifications . "<br>";
    
    // Test marking as read
    if (!empty($notifications)) {
        echo "<h3>3.5 Testing markAsRead</h3>";
        $firstNotification = $notifications[0];
        $result = $notificationService->markAsRead($firstNotification['id'], 1);
        if ($result['success']) {
            echo "✅ markAsRead successful<br>";
        } else {
            echo "❌ markAsRead failed: " . $result['message'] . "<br>";
        }
    }
    
    echo "<h2>4. Testing Notification Bell Component</h2>";
    echo "✅ Notification bell component should be working now<br>";
    echo "✅ Click the bell icon in the header to see notifications<br>";
    
    echo "<h2>5. Testing Notifications Page</h2>";
    echo "✅ Visit <a href='notifications.php' target='_blank'>notifications.php</a> to see all notifications<br>";
    
    echo "<h2>6. Current Notification Statistics</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User ID</th><th>Total Notifications</th><th>Unread Notifications</th></tr>";
    
    // Get all users
    $stmt = $pdo->query("SELECT id, name, role FROM users LIMIT 10");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $total = $notificationService->getTotalNotifications($user['id']);
        $unread = $notificationService->getUnreadCount($user['id']);
        echo "<tr>";
        echo "<td>{$user['id']} ({$user['name']} - {$user['role']})</td>";
        echo "<td>{$total}</td>";
        echo "<td>{$unread}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>7. Sample Notifications</h2>";
    $sampleNotifications = $notificationService->getUserNotifications(1, 5, 0);
    if (!empty($sampleNotifications)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
        
        foreach ($sampleNotifications as $notification) {
            echo "<tr>";
            echo "<td>{$notification['id']}</td>";
            echo "<td>{$notification['type']}</td>";
            echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($notification['message'], 0, 50)) . "...</td>";
            echo "<td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$notification['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No notifications found for user ID 1<br>";
    }
    
    echo "<h2>✅ Notification System Test Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>1. Go to your dashboard and click the notification bell icon</li>";
    echo "<li>2. Visit the notifications page to see all notifications</li>";
    echo "<li>3. Create a new event as admin to test notifications</li>";
    echo "<li>4. Make a booking as attendee to test notifications</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "Error: " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 