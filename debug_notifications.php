<?php
require_once 'Config/db.php';

echo "<h2>Debugging Notification System</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Check users and their roles
    echo "<h3>1. Checking Users and Roles</h3>";
    $stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>❌ No users found in database!</p>";
        exit;
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check events
    echo "<h3>2. Checking Events</h3>";
    $stmt = $pdo->query("SELECT id, title, created_by, created_at FROM events ORDER BY created_at DESC LIMIT 5");
    $events = $stmt->fetchAll();
    
    if (empty($events)) {
        echo "<p>❌ No events found in database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Created By</th><th>Created At</th></tr>";
        foreach ($events as $event) {
            echo "<tr>";
            echo "<td>{$event['id']}</td>";
            echo "<td>{$event['title']}</td>";
            echo "<td>{$event['created_by']}</td>";
            echo "<td>{$event['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check notifications table structure
    echo "<h3>3. Checking Notifications Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check existing notifications
    echo "<h3>4. Checking Existing Notifications</h3>";
    $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
    $notifications = $stmt->fetchAll();
    
    if (empty($notifications)) {
        echo "<p>❌ No notifications found in database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Recipient ID</th><th>Is Read</th><th>Created At</th></tr>";
        foreach ($notifications as $notification) {
            echo "<tr>";
            echo "<td>{$notification['id']}</td>";
            echo "<td>{$notification['type']}</td>";
            echo "<td>{$notification['title']}</td>";
            echo "<td>{$notification['recipient_id']}</td>";
            echo "<td>{$notification['is_read']}</td>";
            echo "<td>{$notification['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test notification creation manually
    echo "<h3>5. Testing Manual Notification Creation</h3>";
    
    // Find an admin and an attendee
    $adminUser = null;
    $attendeeUser = null;
    
    foreach ($users as $user) {
        if ($user['role'] === 'admin' && !$adminUser) {
            $adminUser = $user;
        }
        if ($user['role'] === 'attendee' && !$attendeeUser) {
            $attendeeUser = $user;
        }
    }
    
    if ($adminUser && $attendeeUser && !empty($events)) {
        echo "<p>✅ Found admin: {$adminUser['name']} and attendee: {$attendeeUser['name']}</p>";
        
        // Test creating a notification manually
        $eventId = $events[0]['id'];
        $eventTitle = $events[0]['title'];
        
        $stmt = $pdo->prepare("
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
            $attendeeUser['id'],
            $adminUser['id'],
            $eventId,
            $eventTitle,
            $attendeeUser['name']
        ]);
        
        echo "<p>✅ Created test notification for attendee {$attendeeUser['name']}</p>";
        
        // Check if notification was created
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ?");
        $stmt->execute([$attendeeUser['id']]);
        $count = $stmt->fetch()['count'];
        
        echo "<p>✅ Attendee {$attendeeUser['name']} now has {$count} notifications</p>";
        
    } else {
        echo "<p>❌ Cannot test - missing admin, attendee, or events</p>";
    }
    
    echo "<h3>6. Troubleshooting Steps</h3>";
    echo "<ol>";
    echo "<li>Make sure you have both admin and attendee users in the database</li>";
    echo "<li>Check that the event was created by an admin user</li>";
    echo "<li>Verify the notification bell is included in your layout</li>";
    echo "<li>Check browser console for JavaScript errors</li>";
    echo "<li>Ensure the user is logged in to see notifications</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 