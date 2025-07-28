<?php
// admin_dashboard.php
session_start();

// Database connection (must be before any database operations)
require_once __DIR__ . '/../config/db.php';
$db = new Database();
$conn = $db->getConnection();

// Handle booking actions (must be before any HTML output)
if (isset($_GET['delete']) && isset($_GET['tab']) && $_GET['tab'] === 'bookings') {
    $id = intval($_GET['delete']);
    $conn->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
    header('Location: ?tab=bookings&deleted=1');
    exit;
}
if (isset($_GET['verify']) && isset($_GET['tab']) && $_GET['tab'] === 'bookings') {
    $id = intval($_GET['verify']);
    try {
        // Get booking details before updating
        $stmt = $conn->prepare("
            SELECT b.*, e.title as event_title, u.name as user_name, u.id as user_id 
            FROM bookings b 
            JOIN events e ON b.event_id = e.id 
            JOIN users u ON b.user_id = u.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            // Try to update with payment verification columns
            $conn->prepare('UPDATE bookings SET status = "confirmed", payment_verified = 1, verified_at = NOW() WHERE id = ?')->execute([$id]);
            
            // Send notification to attendee
            require_once __DIR__ . '/../controllers/NotificationController.php';
            $notificationService = new NotificationService($conn);
            $notificationService->notifyBookingStatus(
                $id, 
                $booking['event_title'], 
                $booking['user_id'], 
                'approved', 
                $_SESSION['user']['user_id']
            );
        }
    } catch (PDOException $e) {
        // Fallback: just update status if payment columns don't exist
        $conn->prepare('UPDATE bookings SET status = "confirmed" WHERE id = ?')->execute([$id]);
    }
    header('Location: ?tab=bookings&verified=1');
    exit;
}
if (isset($_GET['unverify']) && isset($_GET['tab']) && $_GET['tab'] === 'bookings') {
    $id = intval($_GET['unverify']);
    try {
        // Get booking details before updating
        $stmt = $conn->prepare("
            SELECT b.*, e.title as event_title, u.name as user_name, u.id as user_id 
            FROM bookings b 
            JOIN events e ON b.event_id = e.id 
            JOIN users u ON b.user_id = u.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            // Try to update with payment verification columns
            $conn->prepare('UPDATE bookings SET status = "pending", payment_verified = 0, verified_at = NULL WHERE id = ?')->execute([$id]);
            
            // Send notification to attendee
            require_once __DIR__ . '/../controllers/NotificationController.php';
            $notificationService = new NotificationService($conn);
            $notificationService->notifyBookingStatus(
                $id, 
                $booking['event_title'], 
                $booking['user_id'], 
                'rejected', 
                $_SESSION['user']['user_id']
            );
        }
    } catch (PDOException $e) {
        // Fallback: just update status if payment columns don't exist
        $conn->prepare('UPDATE bookings SET status = "pending" WHERE id = ?')->execute([$id]);
    }
    header('Location: ?tab=bookings&unverified=1');
    exit;
}

// Handle settings form submissions
$alert = '';
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $admin_id = isset($_SESSION['user']['user_id']) ? intval($_SESSION['user']['user_id']) : 1;
    
    if ($name && $email) {
        $stmt = $conn->prepare('UPDATE users SET name=?, email=? WHERE id=?');
        if ($stmt->execute([$name, $email, $admin_id])) {
            $alert = '<div class="alert alert-success">Profile updated successfully!</div>';
            // Update session data
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
        } else {
            $alert = '<div class="alert alert-danger">Failed to update profile.</div>';
        }
    } else {
        $alert = '<div class="alert alert-warning">All fields are required.</div>';
    }
}

if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $admin_id = isset($_SESSION['user']['user_id']) ? intval($_SESSION['user']['user_id']) : 1;
    
    // Get current user data
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$admin_id]);
    $currentUser = $stmt->fetch();
    
    if ($current && $new && $confirm) {
        if (!password_verify($current, $currentUser['password_hash'])) {
            $alert = '<div class="alert alert-danger">Current password is incorrect.</div>';
        } elseif ($new !== $confirm) {
            $alert = '<div class="alert alert-warning">New passwords do not match.</div>';
        } elseif (strlen($new) < 6) {
            $alert = '<div class="alert alert-warning">Password must be at least 6 characters.</div>';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password_hash=? WHERE id=?');
            if ($stmt->execute([$hash, $admin_id])) {
                $alert = '<div class="alert alert-success">Password changed successfully!</div>';
            } else {
                $alert = '<div class="alert alert-danger">Failed to change password.</div>';
            }
        }
    } else {
        $alert = '<div class="alert alert-warning">All fields are required.</div>';
    }
}

$menuItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'chart-line'],
    ['id' => 'events', 'label' => 'Manage Events', 'icon' => 'calendar'],
    ['id' => 'bookings', 'label' => 'View Bookings', 'icon' => 'ticket-alt'],
    ['id' => 'attendees', 'label' => 'Attendee Management', 'icon' => 'users'],
    ['id' => 'venues', 'label' => 'Venue Management', 'icon' => 'map-marker-alt'],
    ['id' => 'schedule', 'label' => 'Event Schedule', 'icon' => 'calendar-alt'],
    ['id' => 'analytics', 'label' => 'Event Analytics', 'icon' => 'chart-bar'],
    ['id' => 'reports', 'label' => 'Reports', 'icon' => 'chart-pie'],
    ['id' => 'settings', 'label' => 'Settings', 'icon' => 'cog'],
];

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Fetch real metrics from the database for event organizers
$totalEvents = $conn->query("SELECT COUNT(*) FROM events")->fetchColumn();
$activeEvents = $conn->query("SELECT COUNT(*) FROM events WHERE date >= CURDATE()")->fetchColumn();
$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = $conn->query("SELECT COALESCE(SUM(b.total_amount), 0) FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.status = 'confirmed'")->fetchColumn();

$metrics = [
    ['title' => 'Total Events', 'value' => $totalEvents, 'icon' => 'calendar', 'color' => 'bg-blue-500', 'trend' => '+5%'],
    ['title' => 'Active Events', 'value' => $activeEvents, 'icon' => 'calendar-check', 'color' => 'bg-cyan-500', 'trend' => '+3%'],
    ['title' => 'Total Bookings', 'value' => $totalBookings, 'icon' => 'ticket-alt', 'color' => 'bg-green-500', 'trend' => '+18%'],
    ['title' => 'Total Revenue', 'value' => 'Rs ' . number_format($totalRevenue, 2), 'icon' => 'dollar-sign', 'color' => 'bg-purple-500', 'trend' => '+25%'],
];

// Recent Activity: event organizer focused
$recentBookings = $conn->query("SELECT b.booking_time, e.title FROM bookings b JOIN events e ON b.event_id = e.id ORDER BY b.booking_time DESC LIMIT 3")->fetchAll();
$recentEvents = $conn->query("SELECT title, created_at FROM events ORDER BY created_at DESC LIMIT 2")->fetchAll();
$recentActivity = [];
foreach ($recentBookings as $b) {
    $recentActivity[] = [
        'action' => 'New Booking: ' . $b['title'],
        'time' => date('M d, Y', strtotime($b['booking_time'])),
        'icon' => 'check-circle',
        'color' => 'text-green-500'
    ];
}
foreach ($recentEvents as $e) {
    $recentActivity[] = ['action' => 'Event Created: ' . $e['title'], 'time' => date('M d, Y', strtotime($e['created_at'])), 'icon' => 'plus', 'color' => 'text-blue-500'];
}

// Recent Event Bookings for organizers
$recentBookings = $conn->query("
    SELECT b.booking_time, b.total_amount, b.status, e.title, u.name as attendee_name 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    JOIN users u ON b.user_id = u.id 
    ORDER BY b.booking_time DESC 
    LIMIT 5
")->fetchAll();

// For charts, you can aggregate bookings by month (events removed from revenue calculation)
// Bookings per month for the last 6 months
$bookingsChartData = $conn->query("
    SELECT DATE_FORMAT(b.booking_time, '%b %Y') as month, COUNT(*) as count
    FROM bookings b
    WHERE b.booking_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(b.booking_time), MONTH(b.booking_time)
    ORDER BY b.booking_time ASC
")->fetchAll();

// Revenue by category
$revenueChartData = $conn->query("SELECT category, SUM(price) as revenue FROM events GROUP BY category")->fetchAll();

// Get summary statistics for reports
$totalUsers = $conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalEvents = $conn->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalBookings = $conn->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$totalRevenue = $conn->query('SELECT SUM(price) FROM events')->fetchColumn();

// Analytics chart data
$eventPopularityData = $conn->query("
    SELECT e.title, COUNT(b.id) as booking_count 
    FROM events e 
    LEFT JOIN bookings b ON e.id = b.event_id 
    GROUP BY e.id, e.title 
    ORDER BY booking_count DESC 
    LIMIT 10
")->fetchAll();

$revenueTrendsData = $conn->query("
    SELECT DATE_FORMAT(e.date, '%b %Y') as month, SUM(e.price) as revenue
    FROM events e
    WHERE e.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(e.date), MONTH(e.date)
    ORDER BY e.date ASC
")->fetchAll();

// CSV download functionality
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    $filename = $type . 'report' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    
    if ($type === 'users') {
        fputcsv($output, ['ID', 'Name', 'Email', 'Created At']);
        $rows = $conn->query('SELECT id, name, email, created_at FROM users')->fetchAll();
        foreach ($rows as $row) fputcsv($output, $row);
    } elseif ($type === 'events') {
        fputcsv($output, ['ID', 'Title', 'Date', 'Venue', 'Category', 'Price', 'Created At']);
        $rows = $conn->query('SELECT id, title, date, venue, category, price, created_at FROM events')->fetchAll();
        foreach ($rows as $row) fputcsv($output, $row);
    } elseif ($type === 'bookings') {
        fputcsv($output, ['ID', 'User ID', 'Event ID', 'Booking Time']);
        $rows = $conn->query('SELECT id, user_id, event_id, booking_time FROM bookings')->fetchAll();
        foreach ($rows as $row) fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
// Debug output for troubleshooting
// echo '<pre>Bookings Chart Data: ' . print_r($bookingsChartData, true) . '</pre>';
// echo '<pre>Revenue Chart Data: ' . print_r($revenueChartData, true) . '</pre>';

// Generate sample data if no real data exists
if (empty($bookingsChartData)) {
    $bookingsChartData = [
        ['month' => 'Jan', 'count' => 12],
        ['month' => 'Feb', 'count' => 19],
        ['month' => 'Mar', 'count' => 15],
        ['month' => 'Apr', 'count' => 25],
        ['month' => 'May', 'count' => 22],
        ['month' => 'Jun', 'count' => 30]
    ];
}

if (empty($revenueChartData)) {
    $revenueChartData = [
        ['category' => 'Technology', 'revenue' => 5000],
        ['category' => 'Business', 'revenue' => 3500],
        ['category' => 'Entertainment', 'revenue' => 2800],
        ['category' => 'Education', 'revenue' => 4200]
    ];
}

$stmt = $conn->query("SELECT * FROM events ORDER BY created_at DESC");
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>भव्य Event - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #1B3C53;
            --primary-dark: #0f2a3a;
            --primary-light: #2d5a7a;
            --secondary: #D2C1B6;
            --secondary-dark: #b8a99e;
            --secondary-light: #e8dcd4;
            --accent: #456882;
            --accent-dark: #3a5a6f;
            --accent-light: #5a7a8f;
            --neutral-dark: #1B3C53;
            --neutral-light: #F9F3EF;
            --danger: #d63031;
            --danger-dark: #b71540;
            --dark: var(--neutral-dark);
            --light: var(--neutral-light);
            --gray: #666;
            --light-gray: #f5f5f5;
            --success: #27ae60;
            --warning: #f39c12;
            --card-shadow: 0 6px 15px rgba(27, 60, 83, 0.15);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --sidebar-width: 280px;
            --content-gap: 40px;
            --chart-primary: #1B3C53;
            --chart-secondary: #D2C1B6;
            --chart-success: #27ae60;
            --chart-warning: #f39c12;
            --chart-danger: #d63031;
            --chart-accent: #456882;
        }

        .dark-mode {
            --dark: var(--neutral-light);
            --light: var(--neutral-dark);
            --light-gray: #1e1e1e;
            --card-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            transition: var(--transition);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .sidebar-footer {
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
        }
        
        .nav-item, .nav-link {
            color: rgba(255, 255, 255, 0.85);
        }
        
        .nav-item:hover, .nav-item.active, .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .main-content, .main-content-wrapper {
            background: var(--light);
            color: var(--dark);
        }
        
        .card, .metric-card {
            background: var(--light);
            color: var(--dark);
            box-shadow: var(--card-shadow);
        }
        
        .card-title, .metric-info p, .metric-info h4 {
            color: var(--primary-dark);
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--light-gray);
            color: var(--primary);
        }
        
        .btn-secondary:hover {
            background: var(--primary-light);
            color: white;
        }
        
        .badge.bg-success {
            background: var(--success) !important;
        }
        .badge.bg-warning {
            background: var(--warning) !important;
        }
        .badge.bg-danger {
            background: var(--danger) !important;
        }
        .badge.bg-primary {
            background: var(--primary) !important;
        }
        .badge.bg-info {
            background: var(--accent) !important;
        }
        .badge.bg-secondary {
            background: var(--secondary) !important;
        }
        .badge.bg-accent {
            background: var(--accent) !important;
        }
        .status-upcoming {
            background: rgba(67, 160, 71, 0.15);
            color: var(--success);
        }
        .status-completed {
            background: rgba(176, 190, 197, 0.2);
            color: var(--dark);
        }
        .status-badge {
            background: var(--primary);
            color: white;
        }
        .horizontal-bookings-list::-webkit-scrollbar-thumb {
            background: var(--primary);
        }
        .horizontal-bookings-list::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
        .no-bookings-icon {
            color: var(--primary);
        }
        .no-bookings-message h4 {
            color: var(--primary-dark);
        }
        .view-all, .card-action {
            color: var(--primary);
        }
        .view-all:hover, .card-action:hover {
            color: var(--primary-dark);
        }
        .form-label {
            color: var(--primary-dark);
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-light);
        }
        .alert-success {
            background: rgba(67, 160, 71, 0.1);
            color: var(--success);
        }
        .alert-danger {
            background: rgba(229, 57, 53, 0.1);
            color: var(--danger);
        }
        .alert-warning {
            background: rgba(255, 179, 0, 0.1);
            color: var(--warning);
        }
        .alert-info {
            background: rgba(41, 182, 246, 0.1);
            color: var(--accent);
        }
        .quick-action-btn.action-blue {
            background: var(--primary);
        }
        .quick-action-btn.action-green {
            background: var(--primary);
        }
        .quick-action-btn.action-purple {
            background: var(--primary-light);
        }
        .quick-action-btn.action-orange {
            background: var(--accent);
        }
        .metric-icon.bg-blue-500 { background: var(--primary); }
        .metric-icon.bg-cyan-500 { background: var(--primary); }
        .metric-icon.bg-green-500 { background: var(--success); }
        .metric-icon.bg-purple-500 { background: var(--primary-light); }
        .metric-icon.bg-orange-500 { background: var(--accent); }
        .metric-icon.bg-red-500 { background: var(--danger); }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body.dark-mode {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow-y: auto; /* Make sidebar scrollable */
        }

        .sidebar-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logo-text h1 {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .logo-text p {
            font-size: 0.875rem;
            color:rgb(255, 255, 255);
        }

        .sidebar-nav {
            flex: 1 1 auto;
            padding: 1rem 1.5rem;
            min-height: 0;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 10px;
            text-decoration: none;
            color:rgb(255, 255, 255);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .nav-item i {
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-footer {
            flex-shrink: 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            position: sticky;
            bottom: 0;
            z-index: 2;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            color:rgb(255, 255, 255);
            transition: all 0.3s ease;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.08);
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: white;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--gray-light);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        body.dark-mode .header {
            background-color: var(--dark-card);
            border-bottom-color: var(--dark-border);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark);
            letter-spacing: -0.5px;
        }

        body.dark-mode .header-title h2 {
            color: var(--dark-text);
        }

        .header-title p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        body.dark-mode .header-title p {
            color: #94a3b8;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(124, 58, 237, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(124, 58, 237, 0.25);
        }

        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--gray-light);
        }

        .btn-secondary:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        body.dark-mode .btn-secondary {
            background: #334155;
            color: var(--dark-text);
            border-color: var(--dark-border);
        }

        .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--gray-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        body.dark-mode .btn-icon {
            background: #334155;
            color: var(--dark-text);
            border-color: var(--dark-border);
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 4px 6px rgba(124, 58, 237, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 10px rgba(124, 58, 237, 0.3);
        }

        /* Dashboard Content */
        .dashboard-content {
            flex: 1;
            padding: 1.75rem;
            overflow-y: auto;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 1.75rem;
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            z-index: 0;
        }

        .welcome-card h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .welcome-card p {
            color:rgb(255, 255, 255);
            max-width: 600px;
            position: relative;
            z-index: 1;
            font-size: 1.05rem;
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.75rem;
            margin-bottom: 1.75rem;
        }

        .metric-card {
            background: white;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .metric-card {
            background: var(--dark-card);
            border-color: var(--dark-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        body.dark-mode .metric-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .metric-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-info h4 {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        body.dark-mode .metric-info h4 {
            color: #94a3b8;
        }

        .metric-info p {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -1px;
            margin-bottom: 0.25rem;
        }

        body.dark-mode .metric-info p {
            color: var(--dark-text);
        }

        .metric-trend {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .bg-blue-500 { background: var(--primary); }
        .bg-cyan-500 { background: var(--secondary); }
        .bg-green-500 { background: var(--success); }
        .bg-purple-500 { background: var(--primary-light); }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.75rem;
            margin-bottom: 1.75rem;
        }

        .card {
            background: white;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-light);
            transition: all 0.3s ease;
        }

        body.dark-mode .card {
            background: var(--dark-card);
            border-color: var(--dark-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        body.dark-mode .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }

        body.dark-mode .card-title {
            color: var(--dark-text);
        }

        .card-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .card-action:hover {
            color: var(--primary-dark);
            gap: 0.7rem;
        }

        .chart-container {
            height: 280px;
            position: relative;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem;
            background: var(--light);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        body.dark-mode .activity-item {
            background: #1a243a;
        }

        .activity-item:hover {
            background: white;
            transform: translateX(5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        body.dark-mode .activity-item:hover {
            background: #22304a;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .activity-icon {
            width: 44px;
            height: 44px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        body.dark-mode .activity-icon {
            background: #334155;
        }

        .activity-content h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        body.dark-mode .activity-content h4 {
            color: var(--dark-text);
        }

        .activity-content p {
            font-size: 0.9rem;
            color: var(--gray);
        }

        body.dark-mode .activity-content p {
            color: #94a3b8;
        }

        .text-blue-500 { color: var(--primary); }
        .text-green-500 { color: var(--success); }
        .text-cyan-500 { color: var(--secondary); }
        .text-yellow-500 { color: var(--warning); }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            text-align: left;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-light);
        }

        body.dark-mode .data-table th,
        body.dark-mode .data-table td {
            border-bottom: 1px solid var(--dark-border);
        }

        .data-table th {
            font-weight: 600;
            color: var(--dark);
            background: var(--light);
        }

        body.dark-mode .data-table th {
            color: var(--dark-text);
            background: #22304a;
        }

        .data-table tbody tr {
            transition: all 0.3s ease;
        }

        .data-table tbody tr:hover {
            background: var(--light);
        }

        body.dark-mode .data-table tbody tr:hover {
            background: #22304a;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-top: 1.25rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 1.25rem;
            border-radius: 14px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .quick-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }

        .action-blue {
            background: linear-gradient(135deg, #3b82f6, #0ea5e9);
        }

        .action-green {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .action-purple {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
        }
        
        .action-orange {
            background: linear-gradient(135deg, #f97316, #ea580c);
        }
        
        /* Attendee Management Styles */
        .attendee-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .attendee-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .attendee-search-container {
            background: var(--light);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        body.dark-mode .attendee-search-container {
            background: #22304a;
        }

        /* Form Styles */
        .form-label { 
            font-weight: 500; 
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
            background: var(--light);
            color: var(--dark);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.1);
            outline: none;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        /* Dark Mode Form Styles */
        body.dark-mode .form-control {
            background: var(--dark-card);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }
        
        body.dark-mode .form-label {
            color: var(--dark-text);
        }
        
        body.dark-mode .alert-success {
            background: rgba(16, 185, 129, 0.2);
        }
        
        body.dark-mode .alert-danger {
            background: rgba(239, 68, 68, 0.2);
        }
        
        body.dark-mode .alert-warning {
            background: rgba(245, 158, 11, 0.2);
        }

        .placeholder-content {
            background: white;
            padding: 3rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-light);
        }

        body.dark-mode .placeholder-content {
            background: var(--dark-card);
            border-color: var(--dark-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .placeholder-content h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--dark);
        }

        body.dark-mode .placeholder-content h3 {
            color: var(--dark-text);
        }

        .placeholder-content p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        body.dark-mode .placeholder-content p {
            color: #94a3b8;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            
            .main-content {
                margin-left: 220px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }

        /* Booking Management Styles */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .bg-success {
            background-color: var(--success) !important;
        }

        .bg-warning {
            background-color: var(--warning) !important;
        }

        .bg-danger {
            background-color: var(--danger) !important;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }

        .pagination {
            gap: 0.25rem;
        }

        .page-link {
            border-radius: 8px;
            border: 1px solid var(--gray-light);
            color: var(--primary);
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: invert(1);
        }

        /* Dark mode support for booking management */
        body.dark-mode .modal-content {
            background-color: var(--dark-card);
            color: var(--dark-text);
        }

        body.dark-mode .page-link {
            background-color: var(--dark-card);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }

        body.dark-mode .page-link:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Marketing Tools Styles */
        .text-primary { color: var(--primary) !important; }
        .text-success { color: var(--success) !important; }
        .text-purple { color: var(--primary-light) !important; }
        .text-orange { color: var(--warning) !important; }
        .text-warning { color: var(--warning) !important; }

        .bg-light {
            background-color: var(--light) !important;
        }

        body.dark-mode .bg-light {
            background-color: var(--dark-card) !important;
        }

        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            padding: 1rem 1.5rem;
            z-index: 9999;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .toast-notification.show {
            transform: translateX(0);
        }

        .toast-notification.toast-success {
            border-left-color: var(--success);
        }

        .toast-notification.toast-warning {
            border-left-color: var(--warning);
        }

        .toast-notification.toast-error {
            border-left-color: var(--danger);
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .toast-content i {
            font-size: 1.25rem;
        }

        .toast-success .toast-content i {
            color: var(--success);
        }

        .toast-warning .toast-content i {
            color: var(--warning);
        }

        .toast-error .toast-content i {
            color: var(--danger);
        }

        .toast-info .toast-content i {
            color: var(--primary);
        }

        /* Dark mode toast */
        body.dark-mode .toast-notification {
            background: var(--dark-card);
            color: var(--dark-text);
        }

        /* QR Code styles */
        #qrCodeContainer {
            background: white;
            border: 1px solid var(--gray-light);
            border-radius: 12px;
            padding: 1.5rem;
            display: inline-block;
        }

        body.dark-mode #qrCodeContainer {
            background: var(--dark-card);
            border-color: var(--dark-border);
        }

        /* Code display styles */
        code {
            font-family: 'Courier New', monospace;
            background: var(--light);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        body.dark-mode code {
            background: var(--dark-card);
            color: var(--dark-text);
        }
        
        /* Rating Stars */
        .rating {
            display: inline-flex;
            align-items: center;
        }
        
        .rating i {
            font-size: 14px;
            margin-right: 2px;
        }
        
        /* Calendar Styles */
        .calendar-controls {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: #dee2e6;
            border: 1px solid #dee2e6;
        }
        
        .calendar-day {
            background: white;
            padding: 10px;
            min-height: 80px;
            border: none;
            text-align: center;
        }
        
        .calendar-day.other-month {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .calendar-day.has-event {
            background-color: #e3f2fd;
            font-weight: 600;
        }
        
        .calendar-day.today {
            background-color: #007bff;
            color: white;
        }
        
        /* Event Item Styles */
        .event-item {
            transition: all 0.3s ease;
        }
        
        .event-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        /* Attendee and Venue Management Styles */
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .metric-info h4 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .metric-info p {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #333;
        }
        
        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .bg-blue-500 { background-color: var(--primary); }
        .bg-green-500 { background-color: var(--success); }
        .bg-purple-500 { background-color: var(--primary-light); }
        .bg-orange-500 { background-color: var(--accent); }
        .bg-red-500 { background-color: var(--danger); }

        /* Attendee Booking History Table Improvements */
        #attendeeBookings {
            max-width: 100%;
            overflow-x: auto;
            margin-top: 0.5rem;
        }
        #attendeeBookings table {
            width: 100%;
            min-width: 350px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(60,60,120,0.07);
            font-size: 0.98rem;
            border-collapse: separate;
            border-spacing: 0;
        }
        #attendeeBookings th, #attendeeBookings td {
            padding: 0.65rem 0.7rem;
            text-align: left;
            vertical-align: top;
            white-space: normal;
            word-break: break-word;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #attendeeBookings th {
            background: #f3f4f6;
            color: #5e35b1;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        #attendeeBookings td.status-col {
            background: #fafbfc;
            position: sticky;
            right: 0;
            z-index: 1;
            min-width: 80px;
            max-width: 120px;
            text-align: center;
        }
        #attendeeBookings tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.15s;
        }
        #attendeeBookings tr:nth-child(even) {
            background: #fafbfc;
        }
        #attendeeBookings tr:hover {
            background: #f5f0fa;
        }
        #attendeeBookings tr:last-child {
            border-bottom: none;
        }
        #attendeeBookings .badge {
            font-size: 0.95em;
            padding: 0.4em 0.8em;
            border-radius: 8px;
        }
        @media (max-width: 600px) {
            #attendeeBookings table {
                min-width: 320px;
                font-size: 0.92rem;
            }
            #attendeeBookings th, #attendeeBookings td {
                padding: 0.45rem 0.4rem;
                max-width: 90px;
            }
            #attendeeBookings td.status-col {
                min-width: 60px;
                max-width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="logo-text">
                        <h1>भव्य Event</h1>
                    <p>Event Organizer</p>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($menuItems as $item): ?>
                    <a href="?tab=<?php echo $item['id']; ?>" 
                       class="nav-item <?php echo $activeTab === $item['id'] ? 'active' : ''; ?>">
                        <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                        <span><?php echo $item['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <div class="header-title">
                        <h2>Event Organizer Dashboard</h2>
                        <p>Welcome back, Event Organizer! Here's your event management overview.</p>
                    </div>
                    <div class="header-actions">
                        <?php include __DIR__ . '/components/notification_bell.php'; ?>
                        
                        <a href="form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Create Event
                        </a>
                        <button id="darkModeToggle" class="btn-icon" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                  
                        <div class="user-avatar">EO</div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="dashboard-content">
                <?php if ($activeTab === 'dashboard'): ?>
                    <!-- Welcome Card -->
                    <div class="welcome-card">
                        <h3>Welcome back, Event Organizer!</h3>
                        <p>Here's a comprehensive overview of your events, bookings, and revenue. Manage your events efficiently and track your success!</p>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="metrics-grid">
                        <?php foreach ($metrics as $metric): ?>
                            <div class="metric-card">
                                <div class="metric-content">
                                    <div class="metric-info">
                                        <h4><?php echo $metric['title']; ?></h4>
                                        <p><?php echo $metric['value']; ?></p>
                                        <div class="metric-trend">
                                            <i class="fas fa-arrow-up"></i>
                                            <?php echo $metric['trend']; ?>
                                        </div>
                                    </div>
                                    <div class="metric-icon <?php echo $metric['color']; ?>">
                                        <i class="fas fa-<?php echo $metric['icon']; ?>"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Content Grid -->
                    <div class="content-grid">
                        <!-- Bookings Overview -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Bookings Overview</h3>
                                <a href="#" class="card-action" onclick="viewFullReport('bookings')">
                                    View Full Report
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="chart-container">
                                <canvas id="bookingsChart"></canvas>
                                <div id="bookingsNoData" class="no-data-message" style="display: none;">
                                    <div>
                                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                                        <p>No booking data available</p>
                                        <small>Bookings will appear here once events are created and bookings are made.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Statistics -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Booking Statistics</h3>
                                <a href="#" class="card-action" onclick="viewFullReport('statistics')">
                                    View Details
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                                <div id="revenueNoData" class="no-data-message" style="display: none;">
                                    <div>
                                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                        <p>No revenue data available</p>
                                        <small>Revenue statistics will appear here once events with pricing are created.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Analytics -->
                    <div class="content-grid">
                        <!-- User Activity -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">User Activity</h3>
                                <a href="#" class="card-action" onclick="viewFullReport('activity')">
                                    View Analytics
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="chart-container">
                                <canvas id="engagementChart"></canvas>
                                <div id="engagementNoData" class="no-data-message" style="display: none;">
                                    <div>
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <p>No user activity data available</p>
                                        <small>User activity will appear here once users start booking events.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Activity</h3>
                                <a href="#" class="card-action" onclick="viewAllActivity()">
                                    View All
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="activity-list">
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo $activity['color']; ?>">
                                            <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h4><?php echo $activity['action']; ?></h4>
                                            <p><?php echo $activity['time']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                                            <!-- Recent Event Bookings -->
                    <div class="card">
                        <div class="card-header">
                                <h3 class="card-title">Recent Event Bookings</h3>
                                <a href="#" class="card-action" onclick="viewAllBookings()">
                                    View All Bookings
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                            <th>Event</th>
                                            <th>Attendee</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ($recentBookings as $booking): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['title']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['attendee_name']); ?></td>
                                                <td>Rs <?php echo number_format($booking['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $booking['status'] === 'confirmed' ? 'bg-success' : ($booking['status'] === 'pending' ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($booking['booking_time'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="quick-actions">
                            <a href="form.php" class="quick-action-btn action-blue" onclick="createEvent()">
                                <i class="fas fa-plus"></i>
                                Create Event
                            </a>
                            <a href="?tab=attendees" class="quick-action-btn action-green">
                                <i class="fas fa-users"></i>
                                Manage Attendees
                            </a>
                            <a href="?tab=venues" class="quick-action-btn action-purple">
                                <i class="fas fa-map-marker-alt"></i>
                                Venue Management
                            </a>
                            <a href="?tab=analytics" class="quick-action-btn action-orange">
                                <i class="fas fa-chart-bar"></i>
                                Event Analytics
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Other tab content -->
                    <?php if ($activeTab === 'bookings'): ?>
                        <!-- Bookings Management Section -->
                        <?php
                        // Handle alert messages
                        if (isset($_GET['deleted'])) {
                            $alert = '<div class="alert alert-success">Booking deleted successfully!</div>';
                        }
                        if (isset($_GET['verified'])) {
                            $alert = '<div class="alert alert-success">Booking payment verified successfully!</div>';
                        }
                        if (isset($_GET['unverified'])) {
                            $alert = '<div class="alert alert-warning">Booking verification removed!</div>';
                        }

                        // Search/filter
                        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                        $where = $search ? 'WHERE u.name LIKE ? OR e.title LIKE ? OR DATE(b.booking_time) = ?' : '';
                        $params = $search ? ["%$search%", "%$search%", $search] : [];

                        // Pagination
                        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                        $perPage = 10;
                        $offset = ($page - 1) * $perPage;
                        $countStmt = $conn->prepare("SELECT COUNT(*) FROM bookings b JOIN events e ON b.event_id = e.id JOIN users u ON b.user_id = u.id $where");
                        $countStmt->execute($params);
                        $totalBookings = $countStmt->fetchColumn();
                        $totalPages = ceil($totalBookings / $perPage);

                        $query = "SELECT b.*, e.title as event_title, u.name as user_name, u.email as user_email 
                                 FROM bookings b 
                                 JOIN events e ON b.event_id = e.id 
                                 JOIN users u ON b.user_id = u.id 
                                 $where 
                                 ORDER BY b.booking_time DESC 
                                 LIMIT $perPage OFFSET $offset";
                        $stmt = $conn->prepare($query);
                        $stmt->execute($params);
                        $bookings = $stmt->fetchAll();
                        ?>

                        <!-- Search and Filter -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title">Manage Bookings</h3>
                            </div>
                            <div class="card-body">
                                <form method="get" class="d-flex gap-3 align-items-end">
                                    <input type="hidden" name="tab" value="bookings">
                                    <div class="flex-grow-1">
                                        <label class="form-label">Search Bookings</label>
                                        <input type="text" name="search" class="form-control" placeholder="Search by user, event, or date (YYYY-MM-DD)..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                        Search
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="?tab=bookings" class="btn btn-secondary">
                                            <i class="fas fa-times"></i>
                                            Clear
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <?php if (isset($alert)): ?>
                            <?= $alert ?>
                        <?php endif; ?>

                        <!-- Bookings Table -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">All Bookings (<?= $totalBookings ?> total)</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>User</th>
                                                <th>Event</th>
                                                <th>Booking Time</th>
                                                <th>Status</th>
                                                <th>Payment Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><?= $booking['id'] ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($booking['user_name']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($booking['user_email']) ?></small>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($booking['event_title']) ?></td>
                                                <td><?= date('M d, Y H:i', strtotime($booking['booking_time'])) ?></td>
                                                <td>
                                                    <span class="badge <?= $booking['status'] === 'confirmed' ? 'bg-success' : ($booking['status'] === 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                                        <?= ucfirst($booking['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // Check if payment verification columns exist by checking booking status
                                                    $paymentVerified = ($booking['status'] === 'confirmed');
                                                    ?>
                                                    <?php if ($paymentVerified): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle"></i> Verified
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock"></i> Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?= $booking['id'] ?>">
                                                            <i class="fas fa-eye"></i>
                                </button>
                                                        <?php if (!$paymentVerified): ?>
                                                            <a href="?tab=bookings&verify=<?= $booking['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Verify payment for this booking?')">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?tab=bookings&unverify=<?= $booking['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Remove verification for this booking?')">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?tab=bookings&delete=<?= $booking['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this booking?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                            </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card mt-4">
                                <div class="card-body">
                                    <nav>
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?tab=bookings&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                                    <a class="page-link" href="?tab=bookings&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?tab=bookings&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Booking Details Modals -->
                        <?php foreach ($bookings as $booking): ?>
                            <div class="modal fade" id="detailsModal<?= $booking['id'] ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?= $booking['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="detailsModalLabel<?= $booking['id'] ?>">Booking Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Booking ID:</strong></p>
                                                    <p class="text-muted"><?= $booking['id'] ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Status:</strong></p>
                                                    <span class="badge <?= $booking['status'] === 'confirmed' ? 'bg-success' : ($booking['status'] === 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                                        <?= ucfirst($booking['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>User Name:</strong></p>
                                                    <p class="text-muted"><?= htmlspecialchars($booking['user_name']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>User Email:</strong></p>
                                                    <p class="text-muted"><?= htmlspecialchars($booking['user_email']) ?></p>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Event:</strong></p>
                                                    <p class="text-muted"><?= htmlspecialchars($booking['event_title']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Booking Time:</strong></p>
                                                    <p class="text-muted"><?= date('M d, Y H:i:s', strtotime($booking['booking_time'])) ?></p>
                                                </div>
                                            </div>
                                            <?php if (isset($booking['total_amount'])): ?>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Total Amount:</strong></p>
                                                        <p class="text-muted">Rs <?= number_format($booking['total_amount'], 2) ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Payment Status:</strong></p>
                                                    <?php 
                                                    // Check if payment verification columns exist by checking booking status
                                                    $paymentVerified = ($booking['status'] === 'confirmed');
                                                    ?>
                                                    <?php if ($paymentVerified): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle"></i> Payment Verified
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock"></i> Payment Pending Verification
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Payment Actions:</strong></p>
                                                    <?php if (!$paymentVerified): ?>
                                                        <a href="?tab=bookings&verify=<?= $booking['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Verify payment for this booking?')">
                                                            <i class="fas fa-check"></i> Verify Payment
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?tab=bookings&unverify=<?= $booking['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Remove payment verification for this booking?')">
                                                            <i class="fas fa-times"></i> Remove Verification
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <a href="?tab=bookings&delete=<?= $booking['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this booking?')">
                                                <i class="fas fa-trash"></i>
                                                Delete Booking
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($activeTab === 'attendees'): ?>
                        <!-- Attendee Management Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Attendee Management</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="metric-card">
                                            <div class="metric-content">
                                                <div class="metric-info">
                                                    <h4>Total Attendees</h4>
                                                    <p><?php echo $conn->query("SELECT COUNT(DISTINCT user_id) FROM bookings")->fetchColumn(); ?></p>
                                                </div>
                                                <div class="metric-icon bg-blue-500">
                                                    <i class="fas fa-users"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                       
                                </div>
                                
                                <!-- Attendee List -->
                                <div class="mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4>Recent Attendees</h4>
                                        <div class="d-flex gap-2">
                                            <input type="text" class="form-control form-control-sm" id="attendeeSearch" placeholder="Search attendees..." style="width: 200px;">
                                            <select class="form-control form-control-sm" id="attendeeFilter" style="width: 150px;">
                                                <option value="">All Attendees</option>
                                                <option value="vip">VIP Only</option>
                                                <option value="recent">Recent (Last 30 days)</option>
                                                <option value="active">Active (Multiple events)</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" onclick="if(typeof exportAttendees === 'function') { exportAttendees(); } else { console.error('exportAttendees not defined'); }">
                                                <i class="fas fa-download"></i> Export
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="if(typeof bulkEmail === 'function') { bulkEmail(); } else { console.error('bulkEmail not defined'); }">
                                                <i class="fas fa-envelope"></i> Bulk Email
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Events Attended</th>
                                                    <th>Total Spent</th>
                                                    <th>Last Booking</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $attendees = $conn->query("
                                                    SELECT u.name, u.email, 
                                                           COUNT(b.id) as events_attended,
                                                           SUM(b.total_amount) as total_spent,
                                                           MAX(b.booking_time) as last_booking
                                                    FROM users u
                                                    JOIN bookings b ON u.id = b.user_id
                                                    GROUP BY u.id, u.name, u.email
                                                    ORDER BY last_booking DESC
                                                    LIMIT 10
                                                ")->fetchAll();
                                                ?>
                                                <?php foreach ($attendees as $attendee): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($attendee['name']) ?></td>
                                                        <td><?= htmlspecialchars($attendee['email']) ?></td>
                                                        <td><?= $attendee['events_attended'] ?></td>
                                                        <td>Rs <?php echo number_format($attendee['total_spent'], 2); ?></td>
                                                        <td><?= date('M d, Y', strtotime($attendee['last_booking'])) ?></td>
                                                        <td>
                                                            <div class="attendee-actions">
                                                                <button class="btn btn-sm btn-info" onclick="if(typeof viewAttendeeDetails === 'function') { viewAttendeeDetails('<?= htmlspecialchars($attendee['name']) ?>', '<?= htmlspecialchars($attendee['email']) ?>', <?= $attendee['events_attended'] ?>, <?= $attendee['total_spent'] ?>); } else { console.error('viewAttendeeDetails not defined'); }">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-primary" onclick="if(typeof sendEmailToAttendee === 'function') { sendEmailToAttendee('<?= htmlspecialchars($attendee['email']) ?>'); } else { console.error('sendEmailToAttendee not defined'); }">
                                                                    <i class="fas fa-envelope"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-warning" onclick="if(typeof editAttendee === 'function') { editAttendee('<?= htmlspecialchars($attendee['name']) ?>', '<?= htmlspecialchars($attendee['email']) ?>'); } else { console.error('editAttendee not defined'); }">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-success" onclick="if(typeof sendNotification === 'function') { sendNotification('<?= htmlspecialchars($attendee['name']) ?>', '<?= htmlspecialchars($attendee['email']) ?>'); } else { console.error('sendNotification not defined'); }">
                                                                    <i class="fas fa-bell"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attendee Management Modals -->
                        <!-- Attendee Details Modal -->
                        <div class="modal fade" id="attendeeDetailsModal" tabindex="-1" aria-labelledby="attendeeDetailsModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="attendeeDetailsModalLabel">Attendee Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Personal Information</h6>
                                                <p><strong>Name:</strong> <span id="attendeeName"></span></p>
                                                <p><strong>Email:</strong> <span id="attendeeEmail"></span></p>
                                                <p><strong>Events Attended:</strong> <span id="attendeeEvents"></span></p>
                                                <p><strong>Total Spent:</strong> <span id="attendeeSpent"></span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Booking History</h6>
                                                <div id="attendeeBookings" class="table-responsive">
                                                    <!-- Booking history will be loaded here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" onclick="sendEmailToAttendee(document.getElementById('attendeeEmail').textContent)">Send Email</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Attendee Modal -->
                        <div class="modal fade" id="editAttendeeModal" tabindex="-1" aria-labelledby="editAttendeeModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editAttendeeModalLabel">Edit Attendee</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="editAttendeeForm">
                                            <div class="mb-3">
                                                <label for="editAttendeeName" class="form-label">Name</label>
                                                <input type="text" class="form-control" id="editAttendeeName" name="name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editAttendeeEmail" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="editAttendeeEmail" name="email" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="editAttendeePhone" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="editAttendeePhone" name="phone">
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" onclick="saveAttendeeChanges()">Save Changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Send Notification Modal -->
                        <div class="modal fade" id="sendNotificationModal" tabindex="-1" aria-labelledby="sendNotificationModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="sendNotificationModalLabel">Send Notification</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="sendNotificationForm">
                                            <div class="mb-3">
                                                <label for="notificationRecipient" class="form-label">Recipient</label>
                                                <input type="text" class="form-control" id="notificationRecipient" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label for="notificationSubject" class="form-label">Subject</label>
                                                <input type="text" class="form-control" id="notificationSubject" name="subject" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="notificationMessage" class="form-label">Message</label>
                                                <textarea class="form-control" id="notificationMessage" name="message" rows="4" required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="notificationType" class="form-label">Notification Type</label>
                                                <select class="form-control" id="notificationType" name="type">
                                                    <option value="general">General</option>
                                                    <option value="event_update">Event Update</option>
                                                    <option value="reminder">Reminder</option>
                                                    <option value="promotion">Promotion</option>
                                                </select>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" onclick="sendNotificationMessage()">Send Notification</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($activeTab === 'venues'): ?>
                        <!-- Venue Management Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Venue Management</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="metric-card">
                                            <div class="metric-content">
                                                <div class="metric-info">
                                                    <h4>Total Venues</h4>
                                                    <p><?php echo $conn->query("SELECT COUNT(DISTINCT venue) FROM events")->fetchColumn(); ?></p>
                                                </div>
                                                <div class="metric-icon bg-green-500">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="metric-card">
                                            <div class="metric-content">
                                                <div class="metric-info">
                                                    <h4>Most Popular Venue</h4>
                                                    <p><?php 
                                                        $popularVenue = $conn->query("
                                                            SELECT venue, COUNT(*) as event_count 
                                                            FROM events 
                                                            GROUP BY venue 
                                                            ORDER BY event_count DESC 
                                                            LIMIT 1
                                                        ")->fetch();
                                                        echo $popularVenue ? htmlspecialchars(substr($popularVenue['venue'], 0, 20)) . '...' : 'N/A';
                                                    ?></p>
                                                </div>
                                                <div class="metric-icon bg-orange-500">
                                                    <i class="fas fa-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Venue List -->
                                <div class="mt-4">
                                    <h4>Venue Directory</h4>
                                    <div class="table-responsive">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Venue Name</th>
                                                    <th>Events Hosted</th>
                                                    <th>Total Attendees</th>
                                                    <th>Average Rating</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $venues = $conn->query("
                                                    SELECT e.venue,
                                                           COUNT(e.id) as events_hosted,
                                                           SUM(b.quantity) as total_attendees
                                                    FROM events e
                                                    LEFT JOIN bookings b ON e.id = b.event_id
                                                    GROUP BY e.venue
                                                    ORDER BY events_hosted DESC
                                                ")->fetchAll();
                                                ?>
                                                <?php foreach ($venues as $venue): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($venue['venue']) ?></td>
                                                        <td><?= $venue['events_hosted'] ?></td>
                                                        <td><?= $venue['total_attendees'] ?: 0 ?></td>
                                                        <td>
                                                            <div class="rating">
                                                                <i class="fas fa-star text-warning"></i>
                                                                <i class="fas fa-star text-warning"></i>
                                                                <i class="fas fa-star text-warning"></i>
                                                                <i class="fas fa-star text-warning"></i>
                                                                <i class="far fa-star text-warning"></i>
                                                                <span class="ms-2">4.0</span>
                                                            </div>
                                                        </td>

                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($activeTab === 'schedule'): ?>
                        <!-- Event Schedule Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Event Schedule & Calendar</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <!-- Calendar View -->
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="card-title">Event Calendar</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="calendar-controls mb-3">
                                                    <button class="btn btn-outline-primary" id="prevMonthBtn">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                                    <span class="mx-3 fw-bold" id="currentMonth"></span>
                                                    <button class="btn btn-outline-primary" id="nextMonthBtn">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                </div>
                                                <div class="calendar-grid" id="calendarGrid">
                                                    <!-- Calendar will be populated by JavaScript -->
                                                </div>
                                                <div id="calendarEventsList" class="mt-4"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Upcoming Events List -->
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="card-title">Upcoming Events</h4>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                $upcomingEvents = $conn->query("
                                                    SELECT id, title, date, time, venue, 
                                                           (SELECT COUNT(*) FROM bookings WHERE event_id = events.id) as booking_count
                                                    FROM events 
                                                    WHERE date >= CURDATE() 
                                                    ORDER BY date ASC, time ASC 
                                                    LIMIT 10
                                                ")->fetchAll();
                                                ?>
                                                <div class="upcoming-events-list">
                                                    <?php foreach ($upcomingEvents as $event): ?>
                                                        <div class="event-item mb-3 p-3 border rounded">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <h6 class="mb-1"><?= htmlspecialchars($event['title']) ?></h6>
                                                                    <p class="text-muted mb-1">
                                                                        <i class="fas fa-calendar"></i>
                                                                        <?= date('M d, Y', strtotime($event['date'])) ?>
                                                                    </p>
                                                                    <p class="text-muted mb-1">
                                                                        <i class="fas fa-clock"></i>
                                                                        <?= $event['time'] ?>
                                                                    </p>
                                                                    <p class="text-muted mb-0">
                                                                        <i class="fas fa-map-marker-alt"></i>
                                                                        <?= htmlspecialchars($event['venue']) ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-end">
                                                                    <span class="badge bg-primary"><?= $event['booking_count'] ?> bookings</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                // Fetch all events for the current year (for calendar use)
                                $allEvents = $conn->query("SELECT id, title, date, time, venue FROM events")->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <script>
                                // Function to get CSS variables
                                function getCSSVariable(variable) {
                                    return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
                                }
                                
                                // Calendar event data from PHP
                                const allEvents = <?php echo json_encode($allEvents); ?>;
                                
                                // Calendar logic
                                let today = new Date();
                                let currentMonth = today.getMonth();
                                let currentYear = today.getFullYear();
                                
                                function renderCalendar(month, year) {
                                    const calendarGrid = document.getElementById('calendarGrid');
                                    const currentMonthLabel = document.getElementById('currentMonth');
                                    const eventsList = document.getElementById('calendarEventsList');
                                    calendarGrid.innerHTML = '';
                                    eventsList.innerHTML = '';
                                    
                                    const monthNames = [
                                        'January', 'February', 'March', 'April', 'May', 'June',
                                        'July', 'August', 'September', 'October', 'November', 'December'
                                    ];
                                    currentMonthLabel.textContent = monthNames[month] + ' ' + year;
                                    
                                    // First day of the month
                                    const firstDay = new Date(year, month, 1);
                                    const startDay = firstDay.getDay(); // 0 (Sun) - 6 (Sat)
                                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                                    
                                    // Previous month's days for leading blanks
                                    const prevMonthDays = new Date(year, month, 0).getDate();
                                    let dayCells = [];
                                    for (let i = 0; i < startDay; i++) {
                                        dayCells.push(`<div class='calendar-day other-month'>${prevMonthDays - startDay + i + 1}</div>`);
                                    }
                                    // Current month days
                                    for (let d = 1; d <= daysInMonth; d++) {
                                        const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                                        const dayEvents = allEvents.filter(ev => ev.date === dateStr);
                                        let classes = 'calendar-day';
                                        if (dayEvents.length > 0) classes += ' has-event';
                                        if (d === today.getDate() && month === today.getMonth() && year === today.getFullYear()) classes += ' today';
                                        dayCells.push(`<div class='${classes}' data-date='${dateStr}' onclick='showEventsForDay("${dateStr}")'>${d}${dayEvents.length > 0 ? ' <span style=\'display:block;font-size:10px;color:#7c3aed\'>' + dayEvents.length + ' event' + (dayEvents.length>1?'s':'') + '</span>' : ''}</div>`);
                                    }
                                    // Fill out the rest of the week
                                    const totalCells = dayCells.length;
                                    for (let i = 0; i < (7 - (totalCells % 7)) % 7; i++) {
                                        dayCells.push(`<div class='calendar-day other-month'></div>`);
                                    }
                                    calendarGrid.innerHTML = dayCells.join('');
                                }
                                
                                function showEventsForDay(dateStr) {
                                    const eventsList = document.getElementById('calendarEventsList');
                                    const dayEvents = allEvents.filter(ev => ev.date === dateStr);
                                    if (dayEvents.length === 0) {
                                        eventsList.innerHTML = `<div class='alert alert-info'>No events for this day.</div>`;
                                        return;
                                    }
                                    let html = `<h5>Events on ${dateStr}</h5>`;
                                    dayEvents.forEach(ev => {
                                        html += `<div class='event-item mb-3 p-3 border rounded'>
                                            <h6 class='mb-1'>${ev.title}</h6>
                                            <p class='mb-1'><i class='fas fa-clock'></i> ${ev.time}</p>
                                            <p class='mb-1'><i class='fas fa-map-marker-alt'></i> ${ev.venue}</p>
                                        </div>`;
                                    });
                                    eventsList.innerHTML = html;
                                }
                                
                                document.getElementById('prevMonthBtn').onclick = function() {
                                    currentMonth--;
                                    if (currentMonth < 0) {
                                        currentMonth = 11;
                                        currentYear--;
                                    }
                                    renderCalendar(currentMonth, currentYear);
                                };
                                document.getElementById('nextMonthBtn').onclick = function() {
                                    currentMonth++;
                                    if (currentMonth > 11) {
                                        currentMonth = 0;
                                        currentYear++;
                                    }
                                    renderCalendar(currentMonth, currentYear);
                                };
                                // Initial render
                                renderCalendar(currentMonth, currentYear);
                                </script>
                            </div>
                        </div>
                    <?php elseif ($activeTab === 'analytics'): ?>
                        <!-- Event Analytics Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Event Performance Analytics</h3>
                            </div>
                            
                            <!-- Analytics Summary Cards -->
                            <div class="metrics-grid mb-4">
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Active Events</h4>
                                            <p><?php echo $conn->query("SELECT COUNT(*) FROM events WHERE date >= CURDATE()")->fetchColumn(); ?></p>
                                        </div>
                                        <div class="metric-icon bg-green-500">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Avg. Bookings/Event</h4>
                                            <p><?php 
                                                $avgBookings = $conn->query("SELECT AVG(booking_count) FROM (SELECT e.id, COUNT(b.id) as booking_count FROM events e LEFT JOIN bookings b ON e.id = b.event_id GROUP BY e.id) as event_bookings")->fetchColumn();
                                                echo number_format($avgBookings, 1);
                                            ?></p>
                                        </div>
                                        <div class="metric-icon bg-blue-500">
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Top Performing Event</h4>
                                            <p><?php 
                                                $topEvent = $conn->query("SELECT e.title, COUNT(b.id) as booking_count FROM events e LEFT JOIN bookings b ON e.id = b.event_id GROUP BY e.id ORDER BY booking_count DESC LIMIT 1")->fetch();
                                                echo $topEvent ? htmlspecialchars(substr($topEvent['title'], 0, 20)) . '...' : 'N/A';
                                            ?></p>
                                        </div>
                                        <div class="metric-icon bg-purple-500">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Monthly Growth</h4>
                                            <p><?php 
                                                $currentMonth = $conn->query("SELECT COUNT(*) FROM bookings WHERE MONTH(booking_time) = MONTH(CURDATE()) AND YEAR(booking_time) = YEAR(CURDATE())")->fetchColumn();
                                                $lastMonth = $conn->query("SELECT COUNT(*) FROM bookings WHERE MONTH(booking_time) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(booking_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
                                                $growth = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;
                                                echo ($growth >= 0 ? '+' : '') . number_format($growth, 1) . '%';
                                            ?></p>
                                        </div>
                                        <div class="metric-icon bg-<?php echo $growth >= 0 ? 'green' : 'red'; ?>-500">
                                            <i class="fas fa-trending-<?php echo $growth >= 0 ? 'up' : 'down'; ?>"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Analytics Charts -->
                            <div class="content-grid mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Event Popularity</h4>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="eventPopularityChart"></canvas>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Revenue Trends</h4>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="revenueTrendsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Quick Actions</h4>
                                </div>
                                <div class="quick-actions">
                                    <button class="quick-action-btn action-blue" onclick="window.location.href='?tab=reports'">
                                        <i class="fas fa-chart-line"></i>
                                        View Reports
                                    </button>
                                    <button class="quick-action-btn action-green" onclick="exportEventData()">
                                        <i class="fas fa-download"></i>
                                        Export Analytics
                                    </button>
                                    <button class="quick-action-btn action-purple" onclick="generateInsights()">
                                        <i class="fas fa-lightbulb"></i>
                                        Generate Insights
                                    </button>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'settings'): ?>
                        <!-- Settings Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Account Settings</h3>
                            </div>
                            <?php echo $alert; ?>
                            <div class="content-grid">
                                <div class="card">
                                    <h4 class="card-title mb-3">Update Profile</h4>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>" required>
                                        </div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                                <div class="card">
                                    <h4 class="card-title mb-3">Change Password</h4>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($activeTab === 'reports'): ?>
                        <!-- Reports Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Reports & Analytics</h3>
                            </div>
                            
                            <!-- Summary Cards -->
                            <div class="metrics-grid mb-4">
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Total Users</h4>
                                            <p><?php echo $totalUsers; ?></p>
                                        </div>
                                        <div class="metric-icon bg-blue-500">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Total Events</h4>
                                            <p><?php echo $totalEvents; ?></p>
                                        </div>
                                        <div class="metric-icon bg-cyan-500">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Total Bookings</h4>
                                            <p><?php echo $totalBookings; ?></p>
                                        </div>
                                        <div class="metric-icon bg-green-500">
                                            <i class="fas fa-ticket-alt"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-content">
                                        <div class="metric-info">
                                            <h4>Total Revenue</h4>
                                            <p>Rs <?php echo number_format($totalRevenue, 2); ?></p>
                                        </div>
                                        <div class="metric-icon bg-purple-500">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Charts -->
                            <div class="content-grid mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Bookings (Last 6 Months)</h4>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="reportsBookingsChart"></canvas>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Revenue by Category</h4>
                                    </div>
                                    <div class="chart-container">
                                        <canvas id="reportsRevenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Download Reports and Summary -->
                            <div class="content-grid">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Download Reports</h4>
                                    </div>
                                    <div class="quick-actions">
                                        <a href="?tab=reports&download=users" class="quick-action-btn action-blue">
                                            <i class="fas fa-download"></i>
                                            Users CSV
                                        </a>
                                        <a href="?tab=reports&download=events" class="quick-action-btn action-green">
                                            <i class="fas fa-download"></i>
                                            Events CSV
                                        </a>
                                        <a href="?tab=reports&download=bookings" class="quick-action-btn action-purple">
                                            <i class="fas fa-download"></i>
                                            Bookings CSV
                                        </a>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Summary</h4>
                                    </div>
                                    <div class="activity-list">
                                        <div class="activity-item">
                                            <div class="activity-icon text-blue-500">
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h4>Most Popular Category</h4>
                                                <p><strong><?php
                                                    $pop = $conn->query('SELECT category, COUNT(*) as cnt FROM events GROUP BY category ORDER BY cnt DESC LIMIT 1')->fetch();
                                                    echo $pop ? htmlspecialchars($pop['category']) : 'N/A';
                                                ?></strong></p>
                                            </div>
                                        </div>
                                        <div class="activity-item">
                                            <div class="activity-icon text-green-500">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h4>Peak Booking Month</h4>
                                                <p><strong><?php
                                                    $max = $conn->query("SELECT DATE_FORMAT(booking_time, '%b %Y') as month, COUNT(*) as cnt FROM bookings GROUP BY YEAR(booking_time), MONTH(booking_time) ORDER BY cnt DESC LIMIT 1")->fetch();
                                                    echo $max ? htmlspecialchars($max['month']) : 'N/A';
                                                ?></strong></p>
                                            </div>
                                        </div>
                                        <div class="activity-item">
                                            <div class="activity-icon text-purple-500">
                                                <i class="fas fa-dollar-sign"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h4>Highest Revenue Category</h4>
                                                <p><strong><?php
                                                    $maxrev = $conn->query('SELECT category, SUM(price) as rev FROM events GROUP BY category ORDER BY rev DESC LIMIT 1')->fetch();
                                                    echo $maxrev ? htmlspecialchars($maxrev['category']) : 'N/A';
                                                ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($activeTab !== 'events'): ?>
                        <div class="placeholder-content">
                            <h3><?php echo array_column($menuItems, 'label', 'id')[$activeTab] ?? 'Page'; ?></h3>
                            <p>This section is currently under development. We're working hard to bring you the best experience. Check back soon!</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
            <?php if ($activeTab === 'events'): ?>
            <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 0;">All Events</h2>
                    <div class="event-stats">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-image"></i> 
                            <?php 
                            $eventsWithImages = array_filter($events, function($event) { return !empty($event['image']); });
                            echo count($eventsWithImages) . '/' . count($events) . ' with images';
                            ?>
                        </span>
                    </div>
                </div>
                <div class="event-grid">
                    <?php foreach (
                        $events as $event): ?>
                        <div class="event-card">
                            <div class="event-img-wrap">
                                <?php if ($event['image']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars(basename($event['image'])); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-img" onload="this.parentElement.classList.add('loaded')" onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';this.parentElement.classList.add('loaded');">
                                    <div class="event-img-placeholder" style="display: none;">
                                        <i class="fas fa-image"></i>
                                        <span>Image Not Available</span>
                                    </div>
                                <?php else: ?>
                                    <div class="event-img-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>No Image Available</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="event-card-body">
                                <h5 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="event-desc"><?php echo htmlspecialchars($event['description']); ?></p>
                                <p class="event-meta"><strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?> <strong>Time:</strong> <?php echo htmlspecialchars($event['time']); ?></p>
                                <p class="event-meta"><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
                                <p class="event-meta"><strong>Category:</strong> <?php echo htmlspecialchars($event['category']); ?></p>
                                <p class="event-meta"><strong>Price:</strong> Rs <?php echo htmlspecialchars($event['price']); ?></p>
                                
                                <!-- Event Actions -->
                                <div class="event-actions">
                                    <button class="btn btn-primary btn-sm" onclick="editEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>', '<?php echo htmlspecialchars($event['description']); ?>', '<?php echo htmlspecialchars($event['date']); ?>', '<?php echo htmlspecialchars($event['time']); ?>', '<?php echo htmlspecialchars($event['venue']); ?>', '<?php echo htmlspecialchars($event['category']); ?>', '<?php echo htmlspecialchars($event['price']); ?>', '<?php echo htmlspecialchars($event['image'] ?? ''); ?>')">
                                        <i class="fas fa-edit"></i> Edit Event
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Edit Event Modal -->
            <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editEventForm" enctype="multipart/form-data">
                                <input type="hidden" id="editEventId" name="event_id">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editEventTitle" class="form-label">Event Title</label>
                                            <input type="text" class="form-control" id="editEventTitle" name="title" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editEventCategory" class="form-label">Category</label>
                                            <select class="form-control" id="editEventCategory" name="category" required>
                                                <option value="">Select Category</option>
                                                <option value="Concert">Concert</option>
                                                <option value="Conference">Conference</option>
                                                <option value="Workshop">Workshop</option>
                                                <option value="Seminar">Seminar</option>
                                                <option value="Exhibition">Exhibition</option>
                                                <option value="Sports">Sports</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="editEventDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editEventDescription" name="description" rows="3" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editEventDate" class="form-label">Date</label>
                                            <input type="date" class="form-control" id="editEventDate" name="date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editEventTime" class="form-label">Time</label>
                                            <input type="time" class="form-control" id="editEventTime" name="time" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editEventVenue" class="form-label">Venue</label>
                                            <input type="text" class="form-control" id="editEventVenue" name="venue" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editEventPrice" class="form-label">Price</label>
                                            <input type="number" class="form-control" id="editEventPrice" name="price" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="editEventImage" class="form-label">Event Image</label>
                                    <input type="file" class="form-control" id="editEventImage" name="image" accept="image/*">
                                    <small class="form-text text-muted">Leave empty to keep current image</small>
                                    <div id="currentImagePreview" class="mt-2"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="saveEventChanges()">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Delete Event Confirmation Modal -->
            <div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteEventModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete the event "<span id="deleteEventTitle"></span>"?</p>
                            <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" onclick="confirmDeleteEvent()">Delete Event</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            <style>
                .event-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                    gap: 2rem;
                    margin-bottom: 2rem;
                }
                .event-card {
                    background: #fff;
                    border-radius: 16px;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    transition: box-shadow 0.2s, transform 0.2s;
                    min-height: 420px;
                }
                .event-card:hover {
                    box-shadow: 0 8px 24px rgba(124,58,237,0.13);
                    transform: translateY(-4px) scale(1.02);
                }
                .event-img-wrap {
                    width: 100%;
                    height: 180px;
                    background: #f3f4f6;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .event-img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    border-top-left-radius: 16px;
                    border-top-right-radius: 16px;
                }
                
                .event-img-placeholder {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
                    color: #9ca3af;
                    border-top-left-radius: 16px;
                    border-top-right-radius: 16px;
                }
                
                .event-img-placeholder i {
                    font-size: 2.5rem;
                    margin-bottom: 0.5rem;
                    opacity: 0.7;
                }
                
                .event-img-placeholder span {
                    font-size: 0.875rem;
                    font-weight: 500;
                    text-align: center;
                }
                
                .event-img-wrap {
                    position: relative;
                    overflow: hidden;
                }
                
                .event-img-wrap::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                    animation: shimmer 2s infinite;
                    z-index: 1;
                }
                
                @keyframes shimmer {
                    0% { left: -100%; }
                    100% { left: 100%; }
                }
                
                .event-img-wrap.loaded::before {
                    display: none;
                }
                
                /* Lightbox Styles */
                .image-lightbox {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .lightbox-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .lightbox-content {
                    position: relative;
                    max-width: 90%;
                    max-height: 90%;
                    text-align: center;
                }
                
                .lightbox-image {
                    max-width: 100%;
                    max-height: 80vh;
                    object-fit: contain;
                    border-radius: 8px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                }
                
                .lightbox-caption {
                    color: white;
                    margin-top: 1rem;
                    font-size: 1.1rem;
                    font-weight: 500;
                }
                
                .lightbox-close {
                    position: absolute;
                    top: -40px;
                    right: 0;
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 0.5rem;
                    border-radius: 50%;
                    transition: background-color 0.3s;
                }
                
                .lightbox-close:hover {
                    background: rgba(255, 255, 255, 0.2);
                }
                
                /* Event stats styling */
                .event-stats .badge {
                    font-size: 0.875rem;
                    padding: 0.5rem 0.75rem;
                }
                
                .event-stats .badge i {
                    margin-right: 0.25rem;
                }
                
                /* Chart container styling */
                .chart-container {
                    position: relative;
                    height: 300px;
                    width: 100%;
                    margin: 1rem 0;
                }
                
                .chart-container canvas {
                    max-height: 100%;
                    max-width: 100%;
                }
                
                /* Ensure charts are visible */
                .card .chart-container {
                    min-height: 250px;
                    padding: 1rem;
                }
                
                /* No data message styling */
                .no-data-message {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 200px;
                    color: var(--gray);
                    font-style: italic;
                    text-align: center;
                }
                .event-card-body {
                    padding: 1.25rem 1.5rem 1.5rem 1.5rem;
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                }
                .event-title {
                    font-size: 1.3rem;
                    font-weight: 700;
                    margin-bottom: 0.5rem;
                    color: #7c3aed;
                }
                .event-desc {
                    font-size: 1rem;
                    color: #475569;
                    margin-bottom: 0.75rem;
                }
                .event-meta {
                    font-size: 0.98rem;
                    color: #334155;
                    margin-bottom: 0.3rem;
                }
                
                .event-actions {
                    margin-top: auto;
                    padding-top: 1rem;
                    border-top: 1px solid #e5e7eb;
                    display: flex;
                    gap: 0.5rem;
                }
                
                .event-actions .btn {
                    flex: 1;
                    font-size: 0.875rem;
                    padding: 0.5rem 0.75rem;
                }
                @media (max-width: 600px) {
                    .event-card-body {
                        padding: 1rem;
                    }
                    .event-img-wrap {
                        height: 140px;
                    }
                }
            </style>
        </div>
    </div>

    <script>

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkModeIcon = darkModeToggle.querySelector('i');
        
        // Check for saved dark mode preference
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        
        // Set initial dark mode state
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            darkModeIcon.classList.remove('fa-moon');
            darkModeIcon.classList.add('fa-sun');
        }
        
        // Toggle dark mode
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            
            if (document.body.classList.contains('dark-mode')) {
                darkModeIcon.classList.remove('fa-moon');
                darkModeIcon.classList.add('fa-sun');
                localStorage.setItem('darkMode', 'true');
            } else {
                darkModeIcon.classList.remove('fa-sun');
                darkModeIcon.classList.add('fa-moon');
                localStorage.setItem('darkMode', 'false');
            }
            
            // Update charts for dark mode
            updateChartsForDarkMode();
        });

        // Action Functions
        function createEvent() {
            window.location.href = 'form.php';
        }
        
        // Image loading enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for event images to show in lightbox
            const eventImages = document.querySelectorAll('.event-img');
            eventImages.forEach(img => {
                img.addEventListener('click', function() {
                    showImageLightbox(this.src, this.alt);
                });
                img.style.cursor = 'pointer';
            });
            
            // Add loading animation for images
            const imageWraps = document.querySelectorAll('.event-img-wrap');
            imageWraps.forEach(wrap => {
                const img = wrap.querySelector('.event-img');
                if (img) {
                    img.addEventListener('load', function() {
                        wrap.classList.add('loaded');
                    });
                    img.addEventListener('error', function() {
                        wrap.classList.add('loaded');
                    });
                }
            });
        });
        
        // Lightbox functionality
        function showImageLightbox(src, alt) {
            const lightbox = document.createElement('div');
            lightbox.className = 'image-lightbox';
            lightbox.innerHTML = `
                <div class="lightbox-overlay">
                    <div class="lightbox-content">
                        <img src="${src}" alt="${alt}" class="lightbox-image">
                        <div class="lightbox-caption">${alt}</div>
                        <button class="lightbox-close" onclick="closeLightbox()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(lightbox);
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            const lightbox = document.querySelector('.image-lightbox');
            if (lightbox) {
                lightbox.remove();
                document.body.style.overflow = '';
            }
        }
        
        // Close lightbox on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
        
        function exportData() {
            alert('Exporting dashboard data...');
            // In a real application, this would trigger a download
        }
        
        function viewFullReport(type) {
            alert(`Viewing full ${type} report...`);
            // In a real application, this would navigate to a detailed report page
        }
        
        function viewAllActivity() {
            alert('Viewing all recent activity...');
        }
        
        function viewAllBookings() {
            alert('Viewing all bookings...');
        }
        
        function exportBookings() {
            alert('Exporting bookings data...');
        }
        
        function viewAnalytics() {
            window.location.href = '?tab=reports';
        }
        
        function marketingTools() {
            alert('Opening marketing tools...');
        }
        
        // Attendee Management Functions - Moved to separate script block
        console.log('Main script block loaded');
        
        // Venue Management Functions
        function viewVenueDetails(venueName) {
            alert('Viewing details for venue: ' + venueName);
            // In a real application, this would open a modal with venue details
        }
        
        function editVenue(venueName) {
            alert('Editing venue: ' + venueName);
            // In a real application, this would open an edit form
        }
        
        // Calendar Functions
        function previousMonth() {
            // Implementation for calendar navigation
            alert('Previous month');
        }
        
        function nextMonth() {
            // Implementation for calendar navigation
            alert('Next month');
        }
        
        function exportEventData() {
            alert('Exporting event data...');
        }
        
        // Marketing Tools Functions
        function createPromotion() {
            // This is now handled by the form in the marketing section
            window.location.href = '?tab=marketing';
        }
        
        function emailCampaign() {
            // This is now handled by the form in the marketing section
            window.location.href = '?tab=marketing';
        }
        
        function socialMedia() {
            // This is now handled by the form in the marketing section
            window.location.href = '?tab=marketing';
        }
        
        function generateQR() {
            // This is now handled by the form in the marketing section
            window.location.href = '?tab=marketing';
        }

        // Marketing Tool Functions
        function copyToClipboard(text) {
            if (typeof text === 'string') {
                // Copy specific text
                navigator.clipboard.writeText(text).then(() => {
                    alert('Copied to clipboard!');
                });
            } else {
                // Copy from textarea element
                const element = document.getElementById(text);
                element.select();
                navigator.clipboard.writeText(element.value).then(() => {
                    alert('Copied to clipboard!');
                });
            }
        }

        function generateSocialPost() {
            const select = document.getElementById('socialEventSelect');
            const textarea = document.getElementById('socialMediaPost');
            
            if (!select.value) {
                alert('Please select an event first!');
                return;
            }
            
            const selectedOption = select.options[select.selectedIndex];
            const eventTitle = selectedOption.getAttribute('data-title');
            const eventDate = selectedOption.getAttribute('data-date');
            const eventVenue = selectedOption.getAttribute('data-venue');
            
            const templates = [
                `🎉 Don't miss out on "${eventTitle}"! Join us on ${new Date(eventDate).toLocaleDateString()} at ${eventVenue}. Book your tickets now! #भव्यEvent #LiveEvents`,
                
                `🔥 Exciting news! "${eventTitle}" is coming to ${eventVenue} on ${new Date(eventDate).toLocaleDateString()}. Secure your spot today! #भव्यEvent #MustAttend`,
                
                `📅 Save the date! "${eventTitle}" - ${new Date(eventDate).toLocaleDateString()} at ${eventVenue}. This is going to be amazing! #भव्यEvent #EventAlert`,
                
                `🎪 Get ready for "${eventTitle}"! ${new Date(eventDate).toLocaleDateString()} at ${eventVenue}. Limited tickets available! #भव्यEvent #LiveEntertainment`,
                
                `🌟 Experience something incredible! "${eventTitle}" on ${new Date(eventDate).toLocaleDateString()} at ${eventVenue}. Don't wait! #भव्यEvent #LiveEvents`
            ];
            
            const randomTemplate = templates[Math.floor(Math.random() * templates.length)];
            textarea.value = randomTemplate;
        }

        function generateQRCode() {
            const select = document.getElementById('qrEventSelect');
            const size = document.getElementById('qrSize').value;
            
            if (!select.value) {
                alert('Please select an event first!');
                return;
            }
            
            const selectedOption = select.options[select.selectedIndex];
            const url = selectedOption.getAttribute('data-url');
            
            // Generate QR code using a free API
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(url)}`;
            
            const container = document.getElementById('qrCodeContainer');
            const image = document.getElementById('qrCodeImage');
            const downloadBtn = document.getElementById('downloadQRBtn');
            
            image.src = qrUrl;
            container.style.display = 'block';
            downloadBtn.style.display = 'inline-block';
            
            // Store the URL for download
            downloadBtn.setAttribute('data-url', qrUrl);
            downloadBtn.setAttribute('data-filename', `qr-${select.value}-${size}.png`);
            
            alert('QR Code generated successfully!');
        }

        function downloadQRCode() {
            const downloadBtn = document.getElementById('downloadQRBtn');
            const url = downloadBtn.getAttribute('data-url');
            const filename = downloadBtn.getAttribute('data-filename');
            
            if (!url) {
                alert('Please generate a QR code first!');
                return;
            }
            
            // Create a temporary link to download the file
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            alert('QR Code downloaded!');
        }

        function deletePromotion(promoId) {
            if (confirm('Are you sure you want to delete this promotion?')) {
                // In a real application, you'd make an AJAX call to delete the promotion
                // For now, we'll just show a message
                alert('Promotion deleted successfully!');
                // Reload the page to refresh the promotions list
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        }

   

        // Initialize social media post generation on select change
        document.addEventListener('DOMContentLoaded', function() {
            const socialSelect = document.getElementById('socialEventSelect');
            if (socialSelect) {
                socialSelect.addEventListener('change', function() {
                    if (this.value) {
                        generateSocialPost();
                    } else {
                        document.getElementById('socialMediaPost').value = '';
                    }
                });
            }
        });

        function generateInsights() {
            alert('Generating AI-powered insights... This feature will analyze your event performance and provide actionable recommendations.');
        }

        // Function to get CSS variables
        function getCSSVariable(variable) {
            return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
        }

        // Pass PHP data to JS
        const bookingsChartData = <?php echo json_encode($bookingsChartData); ?>;
        const revenueChartData = <?php echo json_encode($revenueChartData); ?>;
        const eventPopularityData = <?php echo json_encode($eventPopularityData); ?>;
        const revenueTrendsData = <?php echo json_encode($revenueTrendsData); ?>;

        // Prepare data for Bookings Chart
        const bookingLabels = bookingsChartData.map(item => item.month);
        const bookingCounts = bookingsChartData.map(item => parseInt(item.count));

        // Prepare data for Statistics Chart
        const revenueLabels = revenueChartData.map(item => item.category);
        const revenueValues = revenueChartData.map(item => parseInt(item.revenue));

        // Prepare data for Analytics Charts
        const eventLabels = eventPopularityData.map(item => item.title.substring(0, 20) + '...');
        const eventCounts = eventPopularityData.map(item => parseInt(item.booking_count));
        const trendLabels = revenueTrendsData.map(item => item.month);
        const trendValues = revenueTrendsData.map(item => parseInt(item.revenue));

        // Initialize reports charts if on reports tab
        if (window.location.search.includes('tab=reports')) {
            // Reports Bookings Chart
            new Chart(document.getElementById('reportsBookingsChart'), {
                type: 'line',
                data: {
                    labels: bookingLabels,
                    datasets: [{
                        label: 'Bookings',
                            data: bookingCounts,
                            borderColor: getCSSVariable('--chart-primary'),
                            backgroundColor: 'rgba(94, 53, 177, 0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: getCSSVariable('--chart-primary'),
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 14 },
                            bodyFont: { size: 13 },
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // Reports Revenue Chart
            new Chart(document.getElementById('reportsRevenueChart'), {
                type: 'bar',
                data: {
                    labels: revenueLabels,
                    datasets: [{
                            label: 'Revenue',
                        data: revenueValues,
                        backgroundColor: [
                            'rgba(94, 53, 177, 0.8)',
                            'rgba(67, 160, 71, 0.8)',
                            'rgba(38, 166, 154, 0.8)',
                            'rgba(255, 112, 67, 0.8)',
                            'rgba(229, 57, 53, 0.8)',
                            'rgba(255, 179, 0, 0.8)'
                        ],
                        borderColor: [
                            'rgb(94, 53, 177)',
                            'rgb(67, 160, 71)',
                            'rgb(38, 166, 154)',
                            'rgb(255, 112, 67)',
                            'rgb(229, 57, 53)',
                            'rgb(255, 179, 0)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 14 },
                            bodyFont: { size: 13 },
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Revenue: $${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Initialize analytics charts if on analytics tab
        if (window.location.search.includes('tab=analytics')) {
            // Event Popularity Chart
            new Chart(document.getElementById('eventPopularityChart'), {
                type: 'bar',
                data: {
                    labels: eventLabels,
                    datasets: [{
                        label: 'Bookings',
                        data: eventCounts,
                        backgroundColor: 'rgba(94, 53, 177, 0.8)',
                        borderColor: 'rgb(94, 53, 177)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 14 },
                            bodyFont: { size: 13 },
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // Revenue Trends Chart
            new Chart(document.getElementById('revenueTrendsChart'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Revenue',
                        data: trendValues,
                            borderColor: getCSSVariable('--chart-success'),
                            backgroundColor: 'rgba(67, 160, 71, 0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: getCSSVariable('--chart-success'),
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 14 },
                            bodyFont: { size: 13 },
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Revenue: $${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Initialize main dashboard charts (always run)
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - Initializing charts...');
            
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }
            
            console.log('Chart.js is available');
            
            // Prepare data for User Activity Chart (real data from database)
            const totalUsers = <?php echo $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?>;
            const activeUsers = <?php echo $conn->query("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE booking_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(); ?>;
            const newUsers = <?php echo $conn->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(); ?>;
            const returningUsers = <?php echo $conn->query("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE booking_time >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND booking_time < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(); ?>;
            const inactiveUsers = Math.max(0, totalUsers - activeUsers - newUsers - returningUsers);
            
            console.log('Booking data:', { labels: bookingLabels, counts: bookingCounts });
            console.log('Revenue data:', { labels: revenueLabels, values: revenueValues });
            
            // Show no-data messages if there's no data
            if (bookingCounts.length === 0 || bookingCounts.every(count => count === 0)) {
                const noDataElement = document.getElementById('bookingsNoData');
                if (noDataElement) noDataElement.style.display = 'flex';
            }
            if (revenueValues.length === 0 || revenueValues.every(value => value === 0)) {
                const noDataElement = document.getElementById('revenueNoData');
                if (noDataElement) noDataElement.style.display = 'flex';
            }
            if (totalUsers === 0) {
                const noDataElement = document.getElementById('engagementNoData');
                if (noDataElement) noDataElement.style.display = 'flex';
            }
            
            // Initialize bookings chart
            const bookingsChartElement = document.getElementById('bookingsChart');
            console.log('Bookings chart element:', bookingsChartElement);
            if (bookingsChartElement) {
                globalBookingsChart = new Chart(bookingsChartElement, {
                    type: 'line',
                    data: {
                        labels: bookingLabels.length > 0 ? bookingLabels : ['No Data'],
                        datasets: [{
                            label: 'Bookings',
                            data: bookingCounts.length > 0 ? bookingCounts : [0],
                            borderColor: '#5e35b1',
                            backgroundColor: 'rgba(94, 53, 177, 0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: '#5e35b1',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                },
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return `Bookings: ${context.parsed.y}`;
                                    }
                                }
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        hover: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                });
            }

            // Statistics chart
            const revenueChartElement = document.getElementById('revenueChart');
            console.log('Revenue chart element:', revenueChartElement);
            if (revenueChartElement) {
                globalRevenueChart = new Chart(revenueChartElement, {
                    type: 'bar',
                    data: {
                        labels: revenueLabels.length > 0 ? revenueLabels : ['No Data'],
                        datasets: [{
                            label: 'Revenue by Category',
                            data: revenueValues.length > 0 ? revenueValues : [0],
                            backgroundColor: [
                                'rgba(124, 58, 237, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(236, 72, 153, 0.8)',
                                'rgba(6, 182, 212, 0.8)'
                            ],
                            borderColor: [
                                'rgb(124, 58, 237)',
                                'rgb(16, 185, 129)',
                                'rgb(59, 130, 246)',
                                'rgb(245, 158, 11)',
                                'rgb(236, 72, 153)',
                                'rgb(6, 182, 212)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            // User Activity chart
            const engagementChartElement = document.getElementById('engagementChart');
            console.log('Engagement chart element:', engagementChartElement);
            if (engagementChartElement) {
                globalEngagementChart = new Chart(engagementChartElement, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active Users', 'New Users', 'Returning Users', 'Inactive Users'],
                        datasets: [{
                            label: 'User Activity',
                            data: [activeUsers, newUsers, returningUsers, inactiveUsers],
                            backgroundColor: [
                                'rgba(124, 58, 237, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(203, 213, 225, 0.8)'
                            ],
                            borderColor: [
                                'rgb(124, 58, 237)',
                                'rgb(16, 185, 129)',
                                'rgb(59, 130, 246)',
                                'rgb(203, 213, 225)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
         
         // Global chart variables for dark mode updates
         let globalBookingsChart = null;
         let globalRevenueChart = null;
         let globalEngagementChart = null;
         
         // Update charts for dark mode
        function updateChartsForDarkMode() {
            if (!globalBookingsChart || !globalRevenueChart || !globalEngagementChart) {
                return; // Charts not initialized yet
            }
            
            if (document.body.classList.contains('dark-mode')) {
                // Update chart options for dark mode
                const scalesOptions = {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                };
                
                globalBookingsChart.options.scales.y = scalesOptions;
                globalBookingsChart.options.scales.x.ticks = scalesOptions.ticks;
                
                globalRevenueChart.options.scales.y = scalesOptions;
                globalRevenueChart.options.scales.x.ticks = scalesOptions.ticks;
                
                globalEngagementChart.options.plugins.legend.labels = {
                    color: 'rgba(255, 255, 255, 0.7)'
                };
            } else {
                // Reset to light mode options
                const scalesOptions = {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        color: '#334155'
                    }
                };
                
                globalBookingsChart.options.scales.y = scalesOptions;
                globalBookingsChart.options.scales.x.ticks = scalesOptions.ticks;
                
                globalRevenueChart.options.scales.y = scalesOptions;
                globalRevenueChart.options.scales.x.ticks = scalesOptions.ticks;
                
                globalEngagementChart.options.plugins.legend.labels = {
                    color: '#334155'
                };
            }
            
            // Update all charts
            globalBookingsChart.update();
            globalRevenueChart.update();
            globalEngagementChart.update();
        }

        // Add animations to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.metric-card, .card');
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            // Load notifications if on bookings tab
            if (window.location.search.includes('tab=bookings')) {
                loadNotifications();
            }
        });

        // Notification Management Functions
        function loadNotifications() {
            fetch('../controllers/NotificationController.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayNotifications(data.notifications);
                    } else {
                        document.getElementById('notificationsContainer').innerHTML = 
                            '<div class="text-center p-4"><p class="text-danger">Error loading notifications</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('notificationsContainer').innerHTML = 
                        '<div class="text-center p-4"><p class="text-danger">Error loading notifications</p></div>';
                });
        }

        function displayNotifications(notifications) {
            const container = document.getElementById('notificationsContainer');
            
            if (notifications.length === 0) {
                container.innerHTML = '<div class="text-center p-4"><p>No notifications found</p></div>';
                return;
            }

            let html = '';
            notifications.forEach(notification => {
                const statusClass = notification.status === 'pending' ? 'warning' : 
                                  notification.status === 'approved' ? 'success' : 'danger';
                const statusText = notification.status === 'pending' ? 'Pending Approval' : 
                                 notification.status === 'approved' ? 'Approved' : 'Rejected';
                
                html += `
                    <div class="notification-card mb-3 p-3 border rounded" style="background: white;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">${notification.event_title}</h5>
                                <p class="mb-1"><strong>User:</strong> ${notification.user_name}</p>
                                <p class="mb-1"><strong>Message:</strong> ${notification.message}</p>
                                <p class="mb-2"><strong>Date:</strong> ${new Date(notification.created_at).toLocaleString()}</p>
                                <span class="badge bg-${statusClass}">${statusText}</span>
                            </div>
                            ${notification.status === 'pending' ? `
                                <div class="ms-3">
                                    <button class="btn btn-success btn-sm me-2" onclick="approveBooking(${notification.id})">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectBooking(${notification.id})">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function approveBooking(notificationId) {
            if (confirm('Are you sure you want to approve this booking?')) {
                updateNotificationStatus(notificationId, 'approved');
            }
        }

        function rejectBooking(notificationId) {
            if (confirm('Are you sure you want to reject this booking?')) {
                updateNotificationStatus(notificationId, 'rejected');
            }
        }

        function updateNotificationStatus(notificationId, status) {
            fetch('../controllers/NotificationController.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(status === 'approved' ? 'Booking approved successfully!' : 'Booking rejected.');
                    loadNotifications(); // Refresh the list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function refreshNotifications() {
            loadNotifications();
        }
    </script>
    
    <!-- Attendee Management Script -->
    <script>
        console.log('Loading attendee management script...');
        
        // Define attendee management functions
        function viewAttendeeDetails(attendeeName, attendeeEmail, eventsAttended, totalSpent) {
            console.log('viewAttendeeDetails called with:', attendeeName, attendeeEmail, eventsAttended, totalSpent);
            
            // Populate modal with attendee details
            document.getElementById('attendeeName').textContent = attendeeName;
            document.getElementById('attendeeEmail').textContent = attendeeEmail;
            document.getElementById('attendeeEvents').textContent = eventsAttended;
            document.getElementById('attendeeSpent').textContent = 'Rs ' + parseFloat(totalSpent).toFixed(2);
            
            // Load booking history
            loadAttendeeBookings(attendeeEmail);
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('attendeeDetailsModal'));
            modal.show();
        }
        
        function loadAttendeeBookings(email) {
            console.log('Loading bookings for:', email);
            
            // Fetch booking history for the attendee
            fetch('../controllers/AttendeeController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=getBookings&email=${encodeURIComponent(email)}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    displayAttendeeBookings(data.bookings);
                } else {
                    console.error('Server error:', data.message);
                    document.getElementById('attendeeBookings').innerHTML = '<p class="text-muted">No booking history found.</p>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                document.getElementById('attendeeBookings').innerHTML = '<p class="text-danger">Error loading booking history.</p>';
            });
        }
        
        function displayAttendeeBookings(bookings) {
            const container = document.getElementById('attendeeBookings');
            if (bookings.length === 0) {
                container.innerHTML = '<p class="text-muted">No booking history found.</p>';
                return;
            }
            
            let html = `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            bookings.forEach(booking => {
                html += `
                    <tr>
                        <td>${booking.event_title}</td>
                        <td>${new Date(booking.event_date).toLocaleDateString()}</td>
                        <td>Rs ${parseFloat(booking.total_amount).toFixed(2)}</td>
                        <td class="status-col"><span class="badge bg-${booking.status === 'confirmed' ? 'success' : (booking.status === 'pending' ? 'warning' : 'danger')}">${booking.status}</span></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        function editAttendee(name, email) {
            console.log('editAttendee called with:', name, email);
            
            // Populate edit form
            document.getElementById('editAttendeeName').value = name;
            document.getElementById('editAttendeeEmail').value = email;
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('editAttendeeModal'));
            modal.show();
        }
        
        function saveAttendeeChanges() {
            const formData = new FormData(document.getElementById('editAttendeeForm'));
            formData.append('action', 'updateAttendee');
            
            fetch('../controllers/AttendeeController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendee updated successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editAttendeeModal'));
                    modal.hide();
                    // Refresh the page to show updated data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating attendee.');
            });
        }
        
        function sendNotification(name, email) {
            console.log('sendNotification called with:', name, email);
            
            // Populate notification form
            document.getElementById('notificationRecipient').value = name + ' (' + email + ')';
            document.getElementById('notificationSubject').value = '';
            document.getElementById('notificationMessage').value = '';
            document.getElementById('notificationType').value = 'general';
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('sendNotificationModal'));
            modal.show();
        }
        
        function sendNotificationMessage() {
            const formData = new FormData(document.getElementById('sendNotificationForm'));
            formData.append('action', 'sendNotification');
            
            fetch('../controllers/AttendeeController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notification sent successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('sendNotificationModal'));
                    modal.hide();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending notification.');
            });
        }
        
        function sendEmailToAttendee(email) {
            console.log('sendEmailToAttendee called with:', email);
            
            // Open default email client
            window.open('mailto:' + email + '?subject=भव्य Event - Important Information');
        }
        
        function filterAttendees() {
            const searchTerm = document.getElementById('attendeeSearch')?.value.toLowerCase() || '';
            const filterValue = document.getElementById('attendeeFilter')?.value || '';
            const table = document.querySelector('.data-table tbody');
            
            if (!table) return;
            
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(row => {
                const name = row.cells[0]?.textContent.toLowerCase() || '';
                const email = row.cells[1]?.textContent.toLowerCase() || '';
                const eventsAttended = parseInt(row.cells[2]?.textContent) || 0;
                
                let showRow = true;
                
                // Search filter
                if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Type filter
                if (filterValue) {
                    switch (filterValue) {
                        case 'vip':
                            showRow = eventsAttended > 0;
                            break;
                        case 'recent':
                            const lastBooking = row.cells[4]?.textContent || '';
                            const bookingDate = new Date(lastBooking);
                            const thirtyDaysAgo = new Date();
                            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                            showRow = bookingDate >= thirtyDaysAgo;
                            break;
                        case 'active':
                            showRow = eventsAttended > 1;
                            break;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
        
        function exportAttendees() {
            const table = document.querySelector('.data-table');
            if (!table) return;
            
            let csv = 'Name,Email,Events Attended,Total Spent,Last Booking\n';
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const name = row.cells[0]?.textContent || '';
                    const email = row.cells[1]?.textContent || '';
                    const events = row.cells[2]?.textContent || '';
                    const spent = row.cells[3]?.textContent || '';
                    const lastBooking = row.cells[4]?.textContent || '';
                    
                    csv += `"${name}","${email}","${events}","${spent}","${lastBooking}"\n`;
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'attendees_export_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
            
            alert('Attendee data exported successfully!');
        }
        
        function bulkEmail() {
            const table = document.querySelector('.data-table tbody');
            if (!table) return;
            
            const visibleRows = Array.from(table.querySelectorAll('tr')).filter(row => row.style.display !== 'none');
            
            if (visibleRows.length === 0) {
                alert('No attendees selected for bulk email.');
                return;
            }
            
            const emails = visibleRows.map(row => row.cells[1]?.textContent).filter(email => email);
            
            if (emails.length === 0) {
                alert('No valid email addresses found.');
                return;
            }
            
            const emailList = emails.join(',');
            const subject = 'भव्य Event - Important Update';
            const body = 'Dear Attendee,\n\nWe hope this message finds you well.\n\nBest regards,\nभव्य Event Team';
            
            window.open(`mailto:?bcc=${emailList}&subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`);
            
            alert(`Bulk email prepared for ${emails.length} attendees.`);
        }
        
        // Initialize search and filter
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing attendee functions...');
            
            const searchInput = document.getElementById('attendeeSearch');
            const filterSelect = document.getElementById('attendeeFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterAttendees);
            }
            
            if (filterSelect) {
                filterSelect.addEventListener('change', filterAttendees);
            }
            
            console.log('✅ Attendee functions initialized successfully');
        });
        
        console.log('Attendee management script loaded successfully');
        
        // Test function to check if controller is working
        function testAttendeeController() {
            fetch('../controllers/AttendeeController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Controller test result:', data);
                if (data.success) {
                    console.log('Controller is working!');
                } else {
                    console.log('Controller error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Controller test error:', error);
                console.log('Controller test failed');
            });
        }
        
        // Test the controller when page loads
        setTimeout(testAttendeeController, 1000);
        
        // Event Management Functions
        let currentEventId = null;
        
        function editEvent(id, title, description, date, time, venue, category, price, image) {
            currentEventId = id;
            document.getElementById('editEventId').value = id;
            document.getElementById('editEventTitle').value = title || '';
            document.getElementById('editEventDescription').value = description || '';
            document.getElementById('editEventDate').value = date || '';
            document.getElementById('editEventTime').value = time || '';
            document.getElementById('editEventVenue').value = venue || '';
            document.getElementById('editEventCategory').value = category || '';
            document.getElementById('editEventPrice').value = price || '';
            const imagePreview = document.getElementById('currentImagePreview');
            if (image && image.trim() !== '') {
                imagePreview.innerHTML = `<div class="current-image-preview"><img src="../uploads/${image}" alt="Current event image" style="max-width: 200px; max-height: 150px; border-radius: 8px;"><small class="text-muted">Current image</small></div>`;
            } else {
                imagePreview.innerHTML = '<small class="text-muted">No current image</small>';
            }
            const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
            modal.show();
        }

        function saveEventChanges() {
            if (!currentEventId) {
                alert('No event selected for editing.');
                return;
            }
            const form = document.getElementById('editEventForm');
            const formData = new FormData(form);
            formData.append('event_id', currentEventId);
            formData.append('action', 'update');
            const saveButton = document.querySelector('#editEventModal .btn-primary');
            const originalText = saveButton.textContent;
            saveButton.textContent = 'Saving...';
            saveButton.disabled = true;
            fetch('../controllers/EventController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                    modal.hide();
                    window.location.reload();
                } else {
                    alert(`Error updating event: ${data.message || 'Unknown error'}`);
                }
            })
            .catch(error => {
                alert(`Error updating event: ${error.message}`);
            })
            .finally(() => {
                saveButton.textContent = originalText;
                saveButton.disabled = false;
            });
        }

        function deleteEvent(id, title) {
            currentEventId = id;
            document.getElementById('deleteEventTitle').textContent = title || 'Unnamed Event';
            const modal = new bootstrap.Modal(document.getElementById('deleteEventModal'));
            modal.show();
        }

        function confirmDeleteEvent() {
            if (!currentEventId) {
                alert('No event selected for deletion.');
                return;
            }
            const deleteButton = document.querySelector('#deleteEventModal .btn-danger');
            const originalText = deleteButton.textContent;
            deleteButton.textContent = 'Deleting...';
            deleteButton.disabled = true;
            fetch('../controllers/EventController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&event_id=${encodeURIComponent(currentEventId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteEventModal'));
                    modal.hide();
                    window.location.reload();
                } else {
                    alert(`Error deleting event: ${data.message || 'Unknown error'}`);
                }
            })
            .catch(error => {
                alert(`Error deleting event: ${error.message}`);
            })
            .finally(() => {
                deleteButton.textContent = originalText;
                deleteButton.disabled = false;
            });
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>