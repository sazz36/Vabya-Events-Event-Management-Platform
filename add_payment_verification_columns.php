<?php
// Add payment verification columns to bookings table
require_once __DIR__ . '/config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "🔄 Adding payment verification columns to bookings table...\n\n";
    
    // Check if columns exist
    $columns = $conn->query("SHOW COLUMNS FROM bookings")->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [];
    
    // Check for payment_verified column
    if (!in_array('payment_verified', $columns)) {
        $columnsToAdd[] = "ADD COLUMN `payment_verified` TINYINT(1) DEFAULT 0 COMMENT '1 if payment is verified, 0 if pending'";
        echo "📋 Will add payment_verified column\n";
    } else {
        echo "✅ payment_verified column already exists\n";
    }
    
    // Check for verified_at column
    if (!in_array('verified_at', $columns)) {
        $columnsToAdd[] = "ADD COLUMN `verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when payment was verified'";
        echo "📋 Will add verified_at column\n";
    } else {
        echo "✅ verified_at column already exists\n";
    }
    
    // Add columns if needed
    if (!empty($columnsToAdd)) {
        echo "\n📋 Adding missing columns...\n";
        $alterSQL = "ALTER TABLE `bookings` " . implode(', ', $columnsToAdd);
        $conn->exec($alterSQL);
        echo "✅ Columns added successfully!\n";
    } else {
        echo "\n✅ All payment verification columns already exist!\n";
    }
    
    // Verify the table structure
    echo "\n📋 Current bookings table structure:\n";
    $result = $conn->query("DESCRIBE bookings");
    $tableColumns = $result->fetchAll();
    
    foreach ($tableColumns as $column) {
        echo "  - {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Default']}\n";
    }
    
    echo "\n🎉 Payment verification system is ready!\n";
    echo "🚀 You can now verify payments in the admin dashboard bookings section.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Please check your database connection and try again.\n";
}
?> 