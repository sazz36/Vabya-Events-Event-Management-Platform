<?php
require_once 'Config/db.php';

echo "<h2>Enhancing Notifications Table for Real-time System</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Add new columns to notifications table for real-time features
    $alterQueries = [
        "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS type ENUM('event_created', 'booking_request', 'booking_approved', 'booking_rejected', 'payment_received', 'general') DEFAULT 'general' AFTER status",
        "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS title VARCHAR(255) DEFAULT 'Notification' AFTER type",
        "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0 AFTER title",
        "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS recipient_id INT DEFAULT NULL AFTER is_read",
        "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS sender_id INT DEFAULT NULL AFTER recipient_id",
        "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS event_id INT DEFAULT NULL AFTER sender_id",
        "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS priority ENUM('low', 'medium', 'high') DEFAULT 'medium' AFTER event_id"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "<p>✅ Executed: " . substr($query, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            echo "<p>⚠️ Column might already exist: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add indexes for better performance
    $indexQueries = [
        "CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_recipient ON notifications(recipient_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at)"
    ];
    
    foreach ($indexQueries as $query) {
        try {
            $pdo->exec($query);
            echo "<p>✅ Index created successfully</p>";
        } catch (PDOException $e) {
            echo "<p>⚠️ Index might already exist: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>✅ Notifications table enhanced successfully!</h3>";
    echo "<p>The real-time notification system is now ready to use.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Enhancement failed: " . $e->getMessage() . "</p>";
}
?> 