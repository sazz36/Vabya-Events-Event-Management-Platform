<?php
require_once 'Config/db.php';

echo "<h2>Fixing Bookings Table Structure</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Drop and recreate bookings table to ensure correct structure
    $pdo->exec("DROP TABLE IF EXISTS bookings");
    echo "<p>✅ Dropped existing bookings table</p>";
    
    // Create bookings table with correct structure
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
        )
    ");
    echo "<p>✅ Created bookings table with correct structure</p>";
    
    // Verify table structure
    $stmt = $pdo->query("DESCRIBE bookings");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Bookings Table Structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
    
    // Test insert
    $stmt = $pdo->prepare("
        INSERT INTO bookings (user_id, event_id, ticket_type, quantity, total_amount, status) 
        VALUES (1, 1, 'GEN', 1, 100.00, 'pending')
    ");
    $stmt->execute();
    echo "<p>✅ Test insert successful!</p>";
    
    // Clean up test data
    $pdo->exec("DELETE FROM bookings WHERE user_id = 1 AND event_id = 1");
    echo "<p>✅ Test data cleaned up</p>";
    
    echo "<h3>Bookings Table Fixed Successfully!</h3>";
    echo "<p>The booking system should now work properly.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 