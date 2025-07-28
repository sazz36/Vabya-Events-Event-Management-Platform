<?php
// Recreate promotions table script
require_once __DIR__ . '/config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "🔄 Starting promotions table recreation...\n\n";
    
    // Step 1: Drop the existing table if it exists
    echo "📋 Step 1: Dropping existing promotions table...\n";
    $conn->exec("DROP TABLE IF EXISTS `promotions`");
    echo "✅ Existing promotions table dropped successfully!\n\n";
    
    // Step 2: Force cleanup of any remaining tablespace files
    echo "📋 Step 2: Cleaning up tablespace files...\n";
    try {
        // Try to discard tablespace if it exists
        $conn->exec("ALTER TABLE `promotions` DISCARD TABLESPACE");
        echo "✅ Tablespace discarded successfully!\n";
    } catch (PDOException $e) {
        // This is expected if table doesn't exist
        echo "ℹ️ No existing tablespace to discard (this is normal)\n";
    }
    
    // Step 3: Wait a moment for filesystem cleanup
    echo "📋 Step 3: Waiting for filesystem cleanup...\n";
    sleep(2);
    echo "✅ Cleanup wait completed!\n\n";
    
    // Step 4: Create the new promotions table
    echo "📋 Step 4: Creating new promotions table...\n";
    $createTableSQL = "
    CREATE TABLE `promotions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `code` varchar(20) NOT NULL,
      `discount_percent` int(3) NOT NULL,
      `valid_until` date NOT NULL,
      `event_id` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `code` (`code`),
      KEY `event_id` (`event_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($createTableSQL);
    echo "✅ New promotions table created successfully!\n\n";
    
    // Step 5: Verify the table was created
    echo "📋 Step 5: Verifying table structure...\n";
    $result = $conn->query("DESCRIBE promotions");
    $columns = $result->fetchAll();
    
    echo "📊 Table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']}\n";
    }
    echo "\n";
    
    // Step 6: Check if there are events to create sample promotions
    echo "📋 Step 6: Creating sample promotions...\n";
    $eventsStmt = $conn->query("SELECT id, title FROM events LIMIT 3");
    $events = $eventsStmt->fetchAll();
    
    if (!empty($events)) {
        // Insert sample promotions
        $samplePromotions = [
            ['SUMMER20', 20, '2024-12-31', $events[0]['id']],
            ['WELCOME10', 10, '2024-12-31', isset($events[1]) ? $events[1]['id'] : $events[0]['id']],
            ['FLASH25', 25, '2024-12-31', isset($events[2]) ? $events[2]['id'] : $events[0]['id']]
        ];
        
        $insertStmt = $conn->prepare("INSERT INTO promotions (code, discount_percent, valid_until, event_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($samplePromotions as $promo) {
            try {
                $insertStmt->execute($promo);
                echo "✅ Sample promotion '{$promo[0]}' created for event: " . 
                     (isset($events[array_search($promo[3], array_column($events, 'id'))]) ? 
                     $events[array_search($promo[3], array_column($events, 'id'))]['title'] : 'Unknown Event') . "\n";
            } catch (PDOException $e) {
                if ($e->getCode() != 23000) { // Not a duplicate key error
                    echo "⚠️ Warning: Could not create promotion '{$promo[0]}': " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "ℹ️ No events found in database. Sample promotions will be created when you add events.\n";
    }
    
    // Step 7: Final verification
    echo "\n📋 Step 7: Final verification...\n";
    $countStmt = $conn->query("SELECT COUNT(*) as count FROM promotions");
    $count = $countStmt->fetch()['count'];
    echo "✅ Promotions table contains {$count} promotion(s)\n";
    
    echo "\n🎉 Promotions table recreation completed successfully!\n";
    echo "🚀 You can now use all marketing tools in the admin dashboard.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // If it's still a tablespace error, provide manual solution
    if (strpos($e->getMessage(), 'Tablespace') !== false) {
        echo "\n🔧 MANUAL SOLUTION REQUIRED:\n";
        echo "The tablespace issue persists. Please follow these steps:\n\n";
        echo "1. Open phpMyAdmin: http://localhost/phpmyadmin\n";
        echo "2. Select your database: bhavyaevent\n";
        echo "3. Go to SQL tab and run these commands:\n\n";
        echo "   DROP TABLE IF EXISTS `promotions`;\n";
        echo "   FLUSH TABLES;\n";
        echo "   CREATE TABLE `promotions` (\n";
        echo "     `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        echo "     `code` varchar(20) NOT NULL,\n";
        echo "     `discount_percent` int(3) NOT NULL,\n";
        echo "     `valid_until` date NOT NULL,\n";
        echo "     `event_id` int(11) NOT NULL,\n";
        echo "     `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
        echo "     `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        echo "     PRIMARY KEY (`id`),\n";
        echo "     UNIQUE KEY `code` (`code`),\n";
        echo "     KEY `event_id` (`event_id`)\n";
        echo "   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
        echo "4. After running the SQL, refresh your admin dashboard.\n";
    } else {
        echo "🔧 Please check your database connection and try again.\n";
    }
}
?> 