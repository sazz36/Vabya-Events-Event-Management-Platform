<?php
// Create promotions table script
require_once __DIR__ . '/config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create promotions table
    $sql = "
    CREATE TABLE IF NOT EXISTS `promotions` (
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
    
    $conn->exec($sql);
    echo "✅ Promotions table created successfully!\n";
    
    // Check if there are any events to create sample promotions
    $eventsStmt = $conn->query("SELECT id FROM events LIMIT 3");
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
                echo "✅ Sample promotion '{$promo[0]}' created successfully!\n";
            } catch (PDOException $e) {
                if ($e->getCode() != 23000) { // Not a duplicate key error
                    echo "⚠️ Warning: Could not create promotion '{$promo[0]}': " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n🎉 Promotions table setup complete! You can now use the marketing tools.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 