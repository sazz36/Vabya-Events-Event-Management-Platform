<?php
require_once 'Config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "Database connected successfully\n";
    
    // Check tables
    $result = $conn->query('SHOW TABLES')->fetchAll();
    echo "Tables: " . implode(', ', array_column($result, 0)) . "\n";
    
    // Check bookings data
    $bookingsCount = $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    echo "Total bookings: $bookingsCount\n";
    
    // Check events data
    $eventsCount = $conn->query("SELECT COUNT(*) FROM events")->fetchColumn();
    echo "Total events: $eventsCount\n";
    
    // Check users data
    $usersCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total users: $usersCount\n";
    
    // Check chart data
    $bookingsChartData = $conn->query("
        SELECT DATE_FORMAT(b.booking_time, '%b %Y') as month, COUNT(*) as count
        FROM bookings b
        WHERE b.booking_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(b.booking_time), MONTH(b.booking_time)
        ORDER BY b.booking_time ASC
    ")->fetchAll();
    
    echo "Bookings chart data: " . json_encode($bookingsChartData) . "\n";
    
    $revenueChartData = $conn->query("SELECT category, SUM(price) as revenue FROM events GROUP BY category")->fetchAll();
    echo "Revenue chart data: " . json_encode($revenueChartData) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 