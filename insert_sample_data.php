<?php
require_once 'Config/db.php';

echo "<h2>Inserting Sample Data for Dashboard Testing</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Check if we have users
    $stmt = $pdo->query("SELECT user_id, name FROM users LIMIT 1");
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p>❌ No users found. Please create a user account first.</p>";
        exit;
    }
    
    $userId = $user['user_id'];
    echo "<p>✅ Using user: {$user['name']} (ID: $userId)</p>";
    
    // Insert sample events
    $sampleEvents = [
        [
            'title' => 'Tech Innovation Summit 2024',
            'description' => 'Join us for the biggest tech event of the year featuring AI, blockchain, and future technologies.',
            'date' => '2024-12-15',
            'time' => '09:00:00',
            'venue' => 'Convention Center, Hall A',
            'price' => 150.00,
            'created_by' => $userId
        ],
        [
            'title' => 'Designers Conference',
            'description' => 'A comprehensive conference for UI/UX designers and creative professionals.',
            'date' => '2024-11-20',
            'time' => '10:00:00',
            'venue' => 'Design Hub, Floor 3',
            'price' => 120.00,
            'created_by' => $userId
        ],
        [
            'title' => 'Digital Marketing Expo',
            'description' => 'Learn the latest trends in digital marketing and social media strategies.',
            'date' => '2024-10-10',
            'time' => '14:00:00',
            'venue' => 'Expo Center',
            'price' => 80.00,
            'created_by' => $userId
        ],
        [
            'title' => 'AI & Machine Learning Conference',
            'description' => 'Explore the future of artificial intelligence and machine learning applications.',
            'date' => '2024-09-22',
            'time' => '11:00:00',
            'venue' => 'Tech Institute',
            'price' => 200.00,
            'created_by' => $userId
        ]
    ];
    
    $eventIds = [];
    
    foreach ($sampleEvents as $event) {
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, date, time, venue, price, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $event['title'],
            $event['description'],
            $event['date'],
            $event['time'],
            $event['venue'],
            $event['price'],
            $event['created_by']
        ]);
        
        $eventIds[] = $pdo->lastInsertId();
        echo "<p>✅ Created event: {$event['title']}</p>";
    }
    
    // Insert sample bookings
    $sampleBookings = [
        [
            'event_id' => $eventIds[0], // Tech Innovation Summit (future event)
            'ticket_type' => 'VIP',
            'quantity' => 1,
            'total_amount' => 150.00,
            'status' => 'confirmed'
        ],
        [
            'event_id' => $eventIds[1], // Designers Conference (future event)
            'ticket_type' => 'GEN',
            'quantity' => 2,
            'total_amount' => 240.00,
            'status' => 'confirmed'
        ],
        [
            'event_id' => $eventIds[2], // Digital Marketing Expo (past event)
            'ticket_type' => 'GEN',
            'quantity' => 1,
            'total_amount' => 80.00,
            'status' => 'confirmed'
        ],
        [
            'event_id' => $eventIds[3], // AI Conference (past event)
            'ticket_type' => 'VIP',
            'quantity' => 1,
            'total_amount' => 200.00,
            'status' => 'confirmed'
        ]
    ];
    
    foreach ($sampleBookings as $booking) {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, event_id, ticket_type, quantity, total_amount, status, booking_date) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $booking['event_id'],
            $booking['ticket_type'],
            $booking['quantity'],
            $booking['total_amount'],
            $booking['status']
        ]);
        
        $bookingId = $pdo->lastInsertId();
        echo "<p>✅ Created booking ID: $bookingId</p>";
    }
    
    // Insert sample notifications
    $sampleNotifications = [
        [
            'event_title' => 'Tech Innovation Summit 2024',
            'user_name' => $user['name'],
            'message' => 'Your booking for Tech Innovation Summit 2024 has been confirmed!'
        ],
        [
            'event_title' => 'Designers Conference',
            'user_name' => $user['name'],
            'message' => 'Your booking for Designers Conference has been confirmed!'
        ]
    ];
    
    foreach ($sampleNotifications as $notification) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (event_title, user_name, message, status, created_at) 
            VALUES (?, ?, ?, 'approved', NOW())
        ");
        
        $stmt->execute([
            $notification['event_title'],
            $notification['user_name'],
            $notification['message']
        ]);
        
        echo "<p>✅ Created notification for: {$notification['event_title']}</p>";
    }
    
    echo "<h3>🎉 Sample Data Insertion Complete!</h3>";
    echo "<p>You now have:</p>";
    echo "<ul>";
    echo "<li>4 sample events (2 future, 2 past)</li>";
    echo "<li>4 sample bookings</li>";
    echo "<li>2 sample notifications</li>";
    echo "</ul>";
    echo "<p><a href='views/dashboard.php'>Go to Dashboard</a> to see the real data in action!</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 