<?php
require_once 'Config/db.php';
require_once 'controllers/NotificationController.php';

echo "<h2>Manual Notification Test</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Check users
    $stmt = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('admin', 'attendee') ORDER BY role, id");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>❌ No users found!</p>";
        exit;
    }
    
    echo "<h3>Available Users:</h3>";
    foreach ($users as $user) {
        echo "<p>- {$user['name']} ({$user['role']}) - ID: {$user['id']}</p>";
    }
    
    // Find admin and attendee
    $admin = null;
    $attendee = null;
    
    foreach ($users as $user) {
        if ($user['role'] === 'admin' && !$admin) {
            $admin = $user;
        }
        if ($user['role'] === 'attendee' && !$attendee) {
            $attendee = $user;
        }
    }
    
    if (!$admin || !$attendee) {
        echo "<p>❌ Need both admin and attendee users!</p>";
        exit;
    }
    
    echo "<h3>Selected Users:</h3>";
    echo "<p>Admin: {$admin['name']} (ID: {$admin['id']})</p>";
    echo "<p>Attendee: {$attendee['name']} (ID: {$attendee['id']})</p>";
    
    // Create a test event
    echo "<h3>Creating Test Event...</h3>";
    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, date, time, venue, category, price, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $eventTitle = "Test Event - " . date('Y-m-d H:i:s');
    $stmt->execute([
        $eventTitle,
        "This is a test event for notification testing",
        date('Y-m-d', strtotime('+7 days')),
        '14:00:00',
        'Test Venue',
        'Test Category',
        50.00,
        $admin['id']
    ]);
    
    $eventId = $pdo->lastInsertId();
    echo "<p>✅ Created test event: {$eventTitle} (ID: {$eventId})</p>";
    
    // Test notification service
    echo "<h3>Testing Notification Service...</h3>";
    $notificationService = new NotificationService($pdo);
    
    // Test new event notification
    $result = $notificationService->notifyNewEvent($eventId, $eventTitle, $admin['id']);
    
    if ($result['success']) {
        echo "<p>✅ New event notification sent to {$result['notifications_sent']} attendees</p>";
    } else {
        echo "<p>❌ Failed to send new event notification: {$result['message']}</p>";
    }
    
    // Check if notification was created
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND type = 'event_created'");
    $stmt->execute([$attendee['id']]);
    $count = $stmt->fetch()['count'];
    
    echo "<p>✅ Attendee {$attendee['name']} now has {$count} event_created notifications</p>";
    
    // Show the notification details
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$attendee['id']]);
    $notification = $stmt->fetch();
    
    if ($notification) {
        echo "<h3>Latest Notification Details:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($notification as $key => $value) {
            echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    }
    
    // Test the notification API endpoint
    echo "<h3>Testing API Endpoint...</h3>";
    
    // Simulate a session
    session_start();
    $_SESSION['user']['user_id'] = $attendee['id'];
    
    // Test getting notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$attendee['id']]);
    $unreadCount = $stmt->fetch()['count'];
    
    echo "<p>✅ Unread notifications for attendee: {$unreadCount}</p>";
    
    echo "<h3>🎉 Test Complete!</h3>";
    echo "<p>Now try logging in as the attendee user and check the notification bell.</p>";
    echo "<p>Attendee login details: Use the attendee account you have</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 