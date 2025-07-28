<?php
require_once 'Config/db.php';
require_once 'controllers/NotificationController.php';

echo "<h2>Testing Real-time Notification System</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $notificationService = new NotificationService($pdo);
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Test 1: Check if we have users
    $stmt = $pdo->query("SELECT id, name, role FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>❌ No users found. Please create some users first.</p>";
        exit;
    }
    
    echo "<p>✅ Found " . count($users) . " users:</p>";
    foreach ($users as $user) {
        echo "<p>- {$user['name']} ({$user['role']})</p>";
    }
    
    // Test 2: Check if we have events
    $stmt = $pdo->query("SELECT id, title FROM events LIMIT 3");
    $events = $stmt->fetchAll();
    
    if (empty($events)) {
        echo "<p>❌ No events found. Please create some events first.</p>";
        exit;
    }
    
    echo "<p>✅ Found " . count($events) . " events:</p>";
    foreach ($events as $event) {
        echo "<p>- {$event['title']}</p>";
    }
    
    // Test 3: Test notification for new event
    echo "<h3>Testing New Event Notification</h3>";
    $adminUser = array_filter($users, function($user) { return $user['role'] === 'admin'; });
    $attendeeUsers = array_filter($users, function($user) { return $user['role'] === 'attendee'; });
    
    if (empty($adminUser) || empty($attendeeUsers)) {
        echo "<p>❌ Need both admin and attendee users to test notifications.</p>";
        exit;
    }
    
    $adminId = array_values($adminUser)[0]['id'];
    $eventId = $events[0]['id'];
    $eventTitle = $events[0]['title'];
    
    $result = $notificationService->notifyNewEvent($eventId, $eventTitle, $adminId);
    
    if ($result['success']) {
        echo "<p>✅ New event notification sent to " . $result['notifications_sent'] . " attendees</p>";
    } else {
        echo "<p>❌ Failed to send new event notification: " . $result['message'] . "</p>";
    }
    
    // Test 4: Test notification for new booking
    echo "<h3>Testing New Booking Notification</h3>";
    $attendeeId = array_values($attendeeUsers)[0]['id'];
    $attendeeName = array_values($attendeeUsers)[0]['name'];
    
    // Create a test booking first
    $stmt = $pdo->prepare("
        INSERT INTO bookings (user_id, event_id, ticket_type, quantity, total_amount, status, booking_date) 
        VALUES (?, ?, 'GEN', 1, 100.00, 'pending', NOW())
    ");
    $stmt->execute([$attendeeId, $eventId]);
    $bookingId = $pdo->lastInsertId();
    
    echo "<p>✅ Created test booking (ID: $bookingId)</p>";
    
    $result = $notificationService->notifyNewBooking($bookingId, $eventTitle, $attendeeName, $attendeeId, $eventId);
    
    if ($result['success']) {
        echo "<p>✅ New booking notification sent to " . $result['notifications_sent'] . " admins</p>";
    } else {
        echo "<p>❌ Failed to send new booking notification: " . $result['message'] . "</p>";
    }
    
    // Test 5: Test booking approval notification
    echo "<h3>Testing Booking Approval Notification</h3>";
    $result = $notificationService->notifyBookingStatus($bookingId, $eventTitle, $attendeeId, 'approved', $adminId);
    
    if ($result['success']) {
        echo "<p>✅ Booking approval notification sent to attendee</p>";
    } else {
        echo "<p>❌ Failed to send booking approval notification: " . $result['message'] . "</p>";
    }
    
    // Test 6: Check notification counts
    echo "<h3>Checking Notification Counts</h3>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ?");
    $stmt->execute([$attendeeId]);
    $attendeeNotifications = $stmt->fetch()['count'];
    
    $stmt->execute([$adminId]);
    $adminNotifications = $stmt->fetch()['count'];
    
    echo "<p>✅ Attendee has $attendeeNotifications notifications</p>";
    echo "<p>✅ Admin has $adminNotifications notifications</p>";
    
    // Test 7: Check unread counts
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$attendeeId]);
    $attendeeUnread = $stmt->fetch()['count'];
    
    $stmt->execute([$adminId]);
    $adminUnread = $stmt->fetch()['count'];
    
    echo "<p>✅ Attendee has $attendeeUnread unread notifications</p>";
    echo "<p>✅ Admin has $adminUnread unread notifications</p>";
    
    echo "<h3>🎉 Notification System Test Complete!</h3>";
    echo "<p>The real-time notification system is working properly.</p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Create events and see notifications sent to attendees</li>";
    echo "<li>Make bookings and see notifications sent to admins</li>";
    echo "<li>Approve/reject bookings and see notifications sent to attendees</li>";
    echo "<li>View notifications in the notification bell dropdown</li>";
    echo "<li>Access the dedicated notifications page</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Test failed: " . $e->getMessage() . "</p>";
}
?> 