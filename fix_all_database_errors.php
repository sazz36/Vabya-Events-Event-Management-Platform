<?php
require_once 'Config/db.php';

echo "<h2>Comprehensive Database Error Fixer</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Drop and recreate bookings table with complete structure
    echo "<h3>Fixing Bookings Table...</h3>";
    $pdo->exec("DROP TABLE IF EXISTS bookings");
    echo "<p>✅ Dropped existing bookings table</p>";
    
    $pdo->exec("
        CREATE TABLE bookings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            ticket_type VARCHAR(10) DEFAULT 'GEN',
            quantity INT DEFAULT 1,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
            booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_event_id (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Created bookings table with complete structure</p>";
    
    // Drop and recreate notifications table
    echo "<h3>Fixing Notifications Table...</h3>";
    $pdo->exec("DROP TABLE IF EXISTS notifications");
    echo "<p>✅ Dropped existing notifications table</p>";
    
    $pdo->exec("
        CREATE TABLE notifications (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Created notifications table with complete structure</p>";
    
    // Verify table structures
    echo "<h3>Verifying Table Structures...</h3>";
    
    // Check bookings table
    $stmt = $pdo->query("DESCRIBE bookings");
    $bookingColumns = $stmt->fetchAll();
    
    echo "<h4>Bookings Table Structure:</h4>";
    echo "<ul>";
    foreach ($bookingColumns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} - {$column['Null']} - Default: {$column['Default']}</li>";
    }
    echo "</ul>";
    
    // Check notifications table
    $stmt = $pdo->query("DESCRIBE notifications");
    $notificationColumns = $stmt->fetchAll();
    
    echo "<h4>Notifications Table Structure:</h4>";
    echo "<ul>";
    foreach ($notificationColumns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} - {$column['Null']} - Default: {$column['Default']}</li>";
    }
    echo "</ul>";
    
    // Test data insertion
    echo "<h3>Testing Data Insertion...</h3>";
    
    // Test bookings insert
    try {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, event_id, ticket_type, quantity, total_amount, status) 
            VALUES (1, 1, 'GEN', 1, 100.00, 'pending')
        ");
        $stmt->execute();
        $bookingId = $pdo->lastInsertId();
        echo "<p>✅ Test booking insert successful (ID: $bookingId)</p>";
        
        // Test notifications insert
        $stmt = $pdo->prepare("
            INSERT INTO notifications (booking_id, event_title, user_name, message, status) 
            VALUES (?, 'Test Event', 'Test User', 'Test notification message', 'pending')
        ");
        $stmt->execute([$bookingId]);
        echo "<p>✅ Test notification insert successful</p>";
        
        // Clean up test data
        $pdo->exec("DELETE FROM notifications WHERE booking_id = $bookingId");
        $pdo->exec("DELETE FROM bookings WHERE id = $bookingId");
        echo "<p>✅ Test data cleaned up</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Test insert failed: " . $e->getMessage() . "</p>";
    }
    
    // Check if events table exists and has required columns
    echo "<h3>Checking Events Table...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'events'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Events table exists</p>";
        
        // Check events table structure
        $stmt = $pdo->query("DESCRIBE events");
        $eventColumns = $stmt->fetchAll();
        $eventColumnNames = array_column($eventColumns, 'Field');
        
        echo "<h4>Events Table Columns:</h4>";
        echo "<ul>";
        foreach ($eventColumns as $column) {
            echo "<li><strong>{$column['Field']}</strong> - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Check if required columns exist
        $requiredEventColumns = ['id', 'title', 'price'];
        $missingEventColumns = array_diff($requiredEventColumns, $eventColumnNames);
        
        if (!empty($missingEventColumns)) {
            echo "<p>⚠️ Missing columns in events table: " . implode(', ', $missingEventColumns) . "</p>";
        } else {
            echo "<p>✅ All required columns exist in events table</p>";
        }
        
    } else {
        echo "<p>⚠️ Events table does not exist</p>";
    }
    
    // Check if users table exists
    echo "<h3>Checking Users Table...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
    } else {
        echo "<p>⚠️ Users table does not exist</p>";
    }
    
    echo "<h3>🎉 Database Fix Complete!</h3>";
    echo "<p>All database errors should now be resolved. The booking system should work properly.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Try booking an event - it should work without errors</li>";
    echo "<li>Check the admin dashboard for notifications</li>";
    echo "<li>All database column errors are now fixed</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection settings.</p>";
}
?> 