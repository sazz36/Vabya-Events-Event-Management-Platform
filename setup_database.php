<?php
require_once 'Config/db.php';

echo "<h2>Database Setup for भव्य Event Booking System</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Create bookings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            ticket_type VARCHAR(10) DEFAULT 'GEN',
            quantity INT DEFAULT 1,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
            booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_event_id (event_id)
        )
    ");
    echo "<p>✅ Bookings table created/verified!</p>";
    
    // Create notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT,
            event_title VARCHAR(255),
            user_name VARCHAR(255),
            message TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_booking_id (booking_id),
            INDEX idx_status (status)
        )
    ");
    echo "<p>✅ Notifications table created/verified!</p>";
    
    // Check if events table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'events'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Events table exists!</p>";
        
        // Show sample events
        $stmt = $pdo->query("SELECT event_id, title, price FROM events LIMIT 5");
        $events = $stmt->fetchAll();
        if (count($events) > 0) {
            echo "<h3>Sample Events:</h3>";
            echo "<ul>";
            foreach ($events as $event) {
                echo "<li>ID: {$event['event_id']} - {$event['title']} - Price: {$event['price']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>⚠️ No events found in database</p>";
        }
    } else {
        echo "<p>⚠️ Events table does not exist!</p>";
    }
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists!</p>";
    } else {
        echo "<p>⚠️ Users table does not exist!</p>";
    }
    
    echo "<h3>Database Setup Complete!</h3>";
    echo "<p>The booking system should now work properly.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database setup failed: " . $e->getMessage() . "</p>";
}
?> 