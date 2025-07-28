<?php
// Fix booking status column to handle longer status values
require_once __DIR__ . '/config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "🔄 Checking and fixing booking status column...\n\n";
    
    // Check current status column structure
    echo "📋 Current status column structure:\n";
    $result = $conn->query("DESCRIBE bookings");
    $columns = $result->fetchAll();
    
    $statusColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            $statusColumn = $column;
            break;
        }
    }
    
    if ($statusColumn) {
        echo "  - Field: {$statusColumn['Field']}\n";
        echo "  - Type: {$statusColumn['Type']}\n";
        echo "  - Null: {$statusColumn['Null']}\n";
        echo "  - Default: {$statusColumn['Default']}\n\n";
        
        // Check if the column needs to be modified
        $currentType = strtolower($statusColumn['Type']);
        if (strpos($currentType, 'varchar(20)') !== false || strpos($currentType, 'varchar(10)') !== false) {
            echo "📋 Status column is too small. Modifying to VARCHAR(50)...\n";
            
            // Modify the status column to be larger
            $conn->exec("ALTER TABLE `bookings` MODIFY COLUMN `status` VARCHAR(50) DEFAULT 'pending'");
            echo "✅ Status column modified successfully!\n\n";
        } else {
            echo "✅ Status column is already large enough.\n\n";
        }
    } else {
        echo "❌ Status column not found in bookings table!\n";
        exit;
    }
    
    // Check current status values
    echo "📋 Current status values in bookings table:\n";
    $result = $conn->query("SELECT DISTINCT status FROM bookings");
    $statuses = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($statuses)) {
        echo "  - No bookings found\n";
    } else {
        foreach ($statuses as $status) {
            echo "  - '$status'\n";
        }
    }
    
    echo "\n📋 Testing status update...\n";
    
    // Test updating a booking status to "verified"
    $testStmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = (SELECT MIN(id) FROM bookings)");
    $testStmt->execute(['verified']);
    
    if ($testStmt->rowCount() > 0) {
        echo "✅ Status update test successful!\n";
        
        // Revert the test
        $testStmt->execute(['pending']);
        echo "✅ Test reverted back to 'pending'\n";
    } else {
        echo "⚠️ No bookings found to test with\n";
    }
    
    echo "\n🎉 Booking status column is now ready for payment verification!\n";
    echo "🚀 You can now use 'verified' status in the admin dashboard.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "🔧 Please check your database connection and try again.\n";
}
?> 