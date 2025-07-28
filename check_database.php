<?php
require_once 'Config/db.php';

echo "<h2>Database Structure Check</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Check if bookings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'bookings'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Bookings table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE bookings");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Current Bookings Table Structure:</h3>";
        echo "<ul>";
        $requiredColumns = ['id', 'user_id', 'event_id', 'ticket_type', 'quantity', 'total_amount', 'status', 'booking_date'];
        $existingColumns = [];
        
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
            $existingColumns[] = $column['Field'];
        }
        echo "</ul>";
        
        // Check for missing columns
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        
        if (!empty($missingColumns)) {
            echo "<h3>Missing Columns:</h3>";
            echo "<ul>";
            foreach ($missingColumns as $column) {
                echo "<li>$column</li>";
            }
            echo "</ul>";
            
            // Add missing columns
            foreach ($missingColumns as $column) {
                switch ($column) {
                    case 'ticket_type':
                        $pdo->exec("ALTER TABLE bookings ADD COLUMN ticket_type VARCHAR(10) DEFAULT 'GEN' AFTER event_id");
                        echo "<p>✅ Added ticket_type column</p>";
                        break;
                    case 'quantity':
                        $pdo->exec("ALTER TABLE bookings ADD COLUMN quantity INT DEFAULT 1 AFTER ticket_type");
                        echo "<p>✅ Added quantity column</p>";
                        break;
                    case 'total_amount':
                        $pdo->exec("ALTER TABLE bookings ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity");
                        echo "<p>✅ Added total_amount column</p>";
                        break;
                }
            }
        } else {
            echo "<p>✅ All required columns exist!</p>";
        }
        
        // Test insert
        try {
            $stmt = $pdo->prepare("
                INSERT INTO bookings (user_id, event_id, ticket_type, quantity, total_amount, status) 
                VALUES (1, 1, 'GEN', 1, 100.00, 'pending')
            ");
            $stmt->execute();
            echo "<p>✅ Test insert successful!</p>";
            
            // Clean up test data
            $pdo->exec("DELETE FROM bookings WHERE user_id = 1 AND event_id = 1");
            echo "<p>✅ Test data cleaned up</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Test insert failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>⚠️ Bookings table does not exist. Creating it...</p>";
        
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
        echo "<p>✅ Created bookings table with all required columns</p>";
    }
    
    echo "<h3>Database Check Complete!</h3>";
    echo "<p>The booking system should now work properly.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 