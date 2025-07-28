<?php
session_start();
// Removed session debug output

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}
// Role check: Only allow attendees
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'attendee') {
    header('Location: admin_dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../Config/languages.php';

// Handle language change
if (isset($_POST['change_language'])) {
    $_SESSION['language'] = $_POST['language'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT * FROM events ORDER BY created_at DESC");
$events = $stmt->fetchAll();

// Get active tab from URL
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get user data from database
$user_id = $_SESSION['user']['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_row = $stmt->fetch();
$user_data = [
    'name' => $user_row['name'],
    'email' => $user_row['email'],
    'role' => ucfirst($user_row['role']),
    'profile_image' => '', // Add if you have profile image support
    'joined_date' => isset($user_row['created_at']) ? $user_row['created_at'] : '',
    'events_attended' => 0, // Will be calculated below
    'upcoming_events' => 0, // Will be calculated below
    'vip_bookings' => 0, // Will be calculated below
    'pending_bookings' => 0 // Will be calculated below
];

// Fetch real upcoming bookings (confirmed bookings for future events)
$stmt = $conn->prepare("
    SELECT b.id, b.ticket_type, b.quantity, b.total_amount, b.status, b.booking_date,
           e.title, e.date, e.time, e.venue, e.price
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ? AND b.status = 'confirmed' AND e.date >= CURDATE()
    ORDER BY e.date ASC, e.time ASC
    LIMIT 5
");
$stmt->execute([$user_id]);
$upcoming_bookings = $stmt->fetchAll();

// Fetch real past bookings (completed bookings for past events)
$stmt = $conn->prepare("
    SELECT b.id, b.ticket_type, b.quantity, b.total_amount, b.status, b.booking_date,
           e.title, e.date, e.time, e.venue, e.price
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ? AND e.date < CURDATE()
    ORDER BY e.date DESC, e.time DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$past_bookings = $stmt->fetchAll();

// Calculate user statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN e.date < CURDATE() THEN 1 END) as events_attended,
        COUNT(CASE WHEN e.date >= CURDATE() AND b.status = 'confirmed' THEN 1 END) as upcoming_events,
        COUNT(CASE WHEN b.ticket_type = 'VIP' THEN 1 END) as vip_bookings,
        COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_bookings
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$user_data['events_attended'] = $stats['events_attended'] ?? 0;
$user_data['upcoming_events'] = $stats['upcoming_events'] ?? 0;
$user_data['vip_bookings'] = $stats['vip_bookings'] ?? 0;
$user_data['pending_bookings'] = $stats['pending_bookings'] ?? 0;

// Format bookings data for display
$dashboard_data = [
    'upcoming_bookings' => [],
    'past_bookings' => []
];

// Format upcoming bookings
foreach ($upcoming_bookings as $booking) {
    $eventDate = new DateTime($booking['date']);
    $today = new DateTime();
    $diff = $today->diff($eventDate);
    
    $status_text = '';
    if ($diff->days == 0) {
        $status_text = 'Today';
    } elseif ($diff->days == 1) {
        $status_text = 'Tomorrow';
    } else {
        $status_text = $diff->days . ' days away';
    }
    
    $dashboard_data['upcoming_bookings'][] = [
        'id' => $booking['id'],
        'title' => $booking['title'],
        'date' => date('M d, Y', strtotime($booking['date'])),
        'location' => $booking['venue'],
        'seat' => $booking['ticket_type'] . '-' . str_pad($booking['id'], 2, '0', STR_PAD_LEFT),
        'ticket' => '#' . strtoupper(substr($booking['title'], 0, 3)) . date('Y', strtotime($booking['date'])) . '-' . $booking['ticket_type'] . str_pad($booking['id'], 2, '0', STR_PAD_LEFT),
        'status' => 'upcoming',
        'status_text' => $status_text
    ];
}

// Format past bookings
foreach ($past_bookings as $booking) {
    $dashboard_data['past_bookings'][] = [
        'id' => $booking['id'],
        'title' => $booking['title'],
        'date' => date('M d, Y', strtotime($booking['date'])),
        'location' => $booking['venue'],
        'seat' => $booking['ticket_type'] . '-' . str_pad($booking['id'], 2, '0', STR_PAD_LEFT),
        'status' => 'completed',
        'status_text' => 'Attended'
    ];
}

// Initialize empty notifications array
$user_notifications = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>भव्य Event | Attendee Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --light: var(--neutral-light);
            --dark: var(--neutral-dark);
            --text-dark: var(--neutral-dark);
            --text-light: #ffffff;
            --light-gray: #f5f5f5;
            --gray: #666;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #3498db;
            --card-bg-light: #ffffff;
            --card-bg-dark: #1e1e1e;
            --shadow-light: 0 8px 32px rgba(27, 60, 83, 0.15);
            --shadow-dark: 0 8px 32px rgba(0, 0, 0, 0.3);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --sidebar-width: 280px;
            --content-gap: 40px;
        }

        .dark-mode {
            --dark: var(--neutral-light);
            --light: var(--neutral-dark);
            --light-gray: #1e1e1e;
            --card-shadow: var(--shadow-dark);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            transition: var(--transition);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            grid-template-rows: 80px 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
            min-height: 100vh;
        }

        /* Header Styles */
        .header {
            grid-area: header;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--content-gap);
            background-color: var(--light);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            z-index: 10;
            position: sticky;
            top: 0;
            margin-left: var(--content-gap);
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: var(--light-gray);
            padding: 10px 20px;
            border-radius: 30px;
            width: 350px;
            transition: var(--transition);
        }

        .search-bar:focus-within {
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .search-bar input {
            background: transparent;
            border: none;
            margin-left: 10px;
            width: 100%;
            outline: none;
            color: var(--dark);
            font-size: 15px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .theme-toggle {
            background: var(--light-gray);
            border: none;
            width: 50px;
            height: 26px;
            border-radius: 13px;
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 0 3px;
            transition: var(--transition);
        }

        .theme-toggle::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            transition: var(--transition);
            left: 3px;
        }

        .dark-mode .theme-toggle {
            background: var(--primary);
        }

        .dark-mode .theme-toggle::before {
            transform: translateX(24px);
        }

        .theme-toggle:hover {
            transform: scale(1.05);
        }

        .theme-toggle .fa-moon {
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .theme-toggle .fa-sun {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dark-mode .theme-toggle .fa-moon {
            opacity: 0;
        }

        .dark-mode .theme-toggle .fa-sun {
            opacity: 1;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 30px;
            transition: var(--transition);
        }

        .user-profile:hover {
            background: var(--light-gray);
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }

        .notifications {
            position: relative;
            cursor: pointer;
        }

        .notifications i {
            font-size: 22px;
            color: var(--dark);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            font-size: 10px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* Notification Dropdown Styles */
        .notifications {
            position: relative;
            cursor: pointer;
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 380px;
            max-height: 500px;
            background: var(--light);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--light-gray);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 20px 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .notification-header h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }

        .mark-all-read {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .mark-all-read:hover {
            background: rgba(27, 60, 83, 0.1);
        }

        .notification-list {
            max-height: 350px;
            overflow-y: auto;
            padding: 0;
        }

        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 3px;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
            position: relative;
            cursor: pointer;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: rgba(27, 60, 83, 0.05);
        }

        .notification-item.unread {
            background: rgba(27, 60, 83, 0.08);
        }

        .notification-item.unread:hover {
            background: rgba(27, 60, 83, 0.12);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--primary);
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .notification-message {
            font-size: 13px;
            color: var(--dark);
            line-height: 1.4;
            margin-bottom: 6px;
            opacity: 0.8;
        }

        .notification-time {
            font-size: 11px;
            color: var(--gray);
            font-weight: 500;
        }

        .unread-indicator {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .no-notifications {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
        }

        .no-notifications i {
            font-size: 48px;
            color: var(--gray);
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-notifications p {
            color: var(--gray);
            font-size: 14px;
            margin: 0;
        }

        .notification-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--light-gray);
            text-align: center;
        }

        .view-all-notifications {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
        }

        .view-all-notifications:hover {
            color: var(--primary);
        }

        /* Sidebar Styles */
        .sidebar {
            grid-area: sidebar;
            background: #1B3C53;
            color: white;
            padding: 30px 20px;
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            overflow-y: auto;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 20;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            padding: 0 10px;
        }

        .logo h1 {
            font-size: 26px;
            font-weight: 700;
            background: linear-gradient(to right, var(--light), var(--light-gray));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo-icon {
            font-size: 30px;
            color: var(--secondary);
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-link i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content-wrapper {
            grid-area: main;
            display: flex;
            justify-content: center;
            padding: 30px var(--content-gap);
            margin-left: var(--content-gap);
            min-height: calc(100vh - 80px);
        }

        .main-content-container {
            width: 100%;
            max-width: 1400px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .main-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            align-content: start;
        }

        .welcome-card {
            grid-column: 1 / -1;
            background: #1B3C53;
            color: white;
            border-radius: 20px;
            padding: 35px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: var(--shadow-light);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -60px;
            right: -60px;
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            bottom: -60px;
            right: 40px;
        }

        .welcome-card h2 {
            font-size: 32px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .welcome-card p {
            font-size: 17px;
            max-width: 600px;
            opacity: 0.9;
            position: relative;
            z-index: 2;
            line-height: 1.6;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            z-index: 2;
            position: relative;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 15px 20px;
            backdrop-filter: blur(5px);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 15px;
            opacity: 0.9;
        }

        .card {
            background: #1B3C53;
            color: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            background: rgba(255, 255, 255, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .view-all {
            color: white;
            font-weight: 500;
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            padding: 6px 12px;
            border-radius: 8px;
        }

        .view-all:hover {
            color: var(--secondary);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Horizontal Bookings List */
        .horizontal-bookings-list {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: thin;
            scrollbar-color: var(--secondary) var(--light-gray);
        }

        .horizontal-bookings-list::-webkit-scrollbar {
            height: 8px;
        }

        .horizontal-bookings-list::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 4px;
        }

        .horizontal-bookings-list::-webkit-scrollbar-thumb {
            background: var(--secondary);
            border-radius: 4px;
        }

        .horizontal-bookings-list::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-dark);
        }

        /* --- Professional Booking Card Styles --- */
        .booking-card {
            min-width: 340px;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 24px 20px 20px 20px;
            border-radius: 20px;
            background-color: #1B3C53;
            color: white;
            transition: var(--transition);
            border: 1.5px solid #e0e0e0;
            box-shadow: 0 4px 24px rgba(27,60,83,0.07);
            flex-shrink: 0;
            margin-bottom: 18px;
        }

        .booking-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 6px;
        }
        @media (max-width: 600px) {
            .booking-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
        }

        .event-name {
            flex: 1;
            font-weight: 700;
            font-size: 1.1rem;
            line-height: 1.3;
            word-break: break-word;
            margin-bottom: 2px;
            color: white;
        }
        .booking-date {
            background: var(--primary);
            color: white;
            font-size: 0.98rem;
            font-weight: 600;
            border-radius: 16px;
            padding: 4px 16px;
            margin-left: 8px;
            white-space: nowrap;
            align-self: flex-start;
        }
        .booking-card-details {
            display: flex;
            flex-direction: column;
            gap: 13px;
            font-size: 15px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 22px;
            word-break: break-word;
        }
        .detail-item span {
            flex: 1;
            word-break: break-word;
            line-height: 1.5;
        }
        .detail-item i {
            color: var(--primary);
            font-size: 17px;
            min-width: 17px;
            text-align: center;
            flex-shrink: 0;
        }
        .booking-card-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 10px;
            gap: 10px;
        }
        .status-badge {
            padding: 7px 18px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .action-btn, .btn-outline {
            min-width: 90px;
            padding: 8px 16px;
            font-size: 15px;
        }
        @media (max-width: 600px) {
            .booking-card {
                min-width: 95vw;
                max-width: 98vw;
                padding: 16px 8px 14px 8px;
                gap: 10px;
            }
            .event-name {
                font-size: 1rem;
            }
            .booking-date {
                font-size: 0.92rem;
                padding: 3px 10px;
            }
            .booking-card-details {
                font-size: 14px;
                gap: 8px;
            }
            .action-btn, .btn-outline {
                min-width: 70px;
                font-size: 14px;
                padding: 7px 10px;
            }
        }
        /* --- End Professional Booking Card Styles --- */

        /* No Bookings Message */
        .no-bookings-message {
            min-width: 320px;
            max-width: 380px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            border-radius: 16px;
            background: var(--light-gray);
            border: 2px dashed var(--primary);
            text-align: center;
            flex-shrink: 0;
        }

        .no-bookings-icon {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .no-bookings-message h4 {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .no-bookings-message p {
            font-size: 14px;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .no-bookings-message .btn-primary {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        /* Vertical Bookings List (for backward compatibility) */
        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .booking {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 20px;
            border-radius: 16px;
            background: var(--light-gray);
            transition: var(--transition);
        }

        .booking:hover {
            transform: translateX(5px);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-name {
            font-weight: 600;
            font-size: 18px;
            color: white
        }

        .booking-date {
            font-size: 14px;
            color: white;
            background: rgba(27, 60, 83, 0.1);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 500;
        }

        .booking-details {
            display: flex;
            gap: 20px;
            font-size: 14px;
            flex-wrap: wrap;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 20px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .detail-item span {
            flex: 1;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.4;
        }

        .detail-item i {
            color: var(--primary);
            font-size: 16px;
            min-width: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .booking-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-upcoming {
            background: rgba(67, 160, 71, 0.15);
            color: var(--success);
        }

        .status-completed {
            background: rgba(176, 190, 197, 0.2);
            color: var(--dark);
        }

        .action-btn {
            padding: 8px 18px;
            border-radius: 10px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(27, 60, 83, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: white;
        }

        .btn-outline:hover {
            background: rgba(27, 60, 83, 0.08);
            transform: translateY(-2px);
        }
        
        /* Placeholder Content */
        .placeholder-content {
            background: var(--light);
            padding: 3rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--light-gray);
            grid-column: 1 / -1;
            margin-top: 20px;
        }
        
        body.dark-mode .placeholder-content {
            background: var(--light);
            border-color: var(--dark);
            box-shadow: var(--shadow-dark);
        }
        
        .placeholder-content h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--primary);
        }
        
        body.dark-mode .placeholder-content h3 {
            color: var(--primary-light);
        }
        
        .placeholder-content p {
            color: var(--dark);
            max-width: 500px;
            margin: 0 auto;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        body.dark-mode .placeholder-content p {
            color: var(--dark);
        }
        
        .placeholder-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }
        
        body.dark-mode .placeholder-icon {
            color: var(--primary-light);
        }

        /* Profile Card */
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .profile-email {
            color: var(--secondary);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .stat-card {
            background: var(--light-gray);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color:black;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 15px;
            color: black;
            opacity: 0.8;
        }

        /* Preferences Card */
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .setting-name {
            font-weight: 600;
            font-size: 16px;
        }

        .setting-desc {
            font-size: 14px;
            color: var(--dark);
            opacity: 0.7;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray);
            transition: var(--transition);
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: var(--transition);
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(24px);
        }

        /* Responsive */
        @media (max-width: 1100px) {
            :root {
                --sidebar-width: 0px;
                --content-gap: 20px;
            }
            .dashboard {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "header"
                    "main";
            }
            .sidebar {
                display: none;
            }
            .main-content-wrapper {
                margin-left: 0;
                padding: 20px;
            }
            .header {
                margin-left: 0;
                padding: 0 20px;
            }
            .welcome-card {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .welcome-card h2 {
                font-size: 26px;
            }
            
            .header {
                padding: 0 15px;
            }
            
            .search-bar {
                width: 200px;
            }
            
            .stats {
                flex-direction: column;
                gap: 12px;
            }

            .main-content-wrapper {
                padding: 15px;
            }

            :root {
                --content-gap: 15px;
            }

            /* Mobile styles for horizontal bookings */
            .horizontal-bookings-list {
                gap: 15px;
                padding: 5px 0;
            }

            .booking-card {
                min-width: 280px;
                max-width: 320px;
                padding: 15px;
            }

            .booking-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .booking-card-details {
                gap: 10px;
            }

            .booking-card-status {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }

            /* Mobile styles for notification dropdown */
            .notification-dropdown {
                width: 320px;
                right: -50px;
            }

            .notification-item {
                padding: 12px 15px;
            }

            .notification-icon {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }

            .notification-title {
                font-size: 13px;
            }

            .notification-message {
                font-size: 12px;
            }

            .notification-time {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-calendar-check logo-icon"></i>
                <h1>भव्य Event</h1>
            </div>
            <nav class="nav-links">
                <a href="?tab=dashboard" class="nav-link <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span><?php echo t('dashboard'); ?></span>
                </a>
                <a href="?tab=bookings" class="nav-link <?php echo $activeTab === 'bookings' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span><?php echo t('my_bookings'); ?></span>
                </a>
                <a href="?tab=events" class="nav-link <?php echo $activeTab === 'events' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo t('events'); ?></span>
                </a>
                <a href="?tab=profile" class="nav-link <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span><?php echo t('profile'); ?></span>
                </a>
                <a href="?tab=settings" class="nav-link <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span><?php echo t('settings'); ?></span>
                </a>
                <a href="?tab=help" class="nav-link <?php echo $activeTab === 'help' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i>
                    <span><?php echo t('help_center'); ?></span>
                </a>
                <div style="height: 30px;"></div>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?php echo t('logout'); ?></span>
                </a>
            </nav>
            <!-- Sidebar user info (add below logo or at bottom of sidebar) -->
            <div class="sidebar-user-info" style="margin: 30px 0 0 0; padding: 16px 10px; background: var(--light-gray); border-radius: 12px; text-align: center;">
                <div style="font-weight: 600; font-size: 1.1em; color: var(--primary-dark);">
                    <?php echo htmlspecialchars($user_data['name']); ?>
                </div>
                <div style="font-size: 0.97em; color: var(--gray); word-break: break-all;">
                    <?php echo htmlspecialchars($user_data['email']); ?>
                </div>
            </div>
        </aside>

        <!-- Header -->
        <header class="header">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="<?php echo t('search_placeholder'); ?>">
            </div>
            <div class="header-right">
                <?php include __DIR__ . '/components/notification_bell.php'; ?>
                
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                    <i class="fas fa-moon" style="font-size: 12px; color: var(--dark); position: absolute; left: 6px; z-index: 1;"></i>
                    <i class="fas fa-sun" style="font-size: 12px; color: white; position: absolute; right: 6px; z-index: 1; opacity: 0;"></i>
                </button>

                <div class="user-profile">
                    <div class="user-avatar">
                        <?php
                        $initials = '';
                        $nameParts = explode(' ', trim($user_data['name']));
                        if (count($nameParts) > 1) {
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                        } else {
                            $initials = strtoupper(substr($user_data['name'], 0, 2));
                        }
                        echo $initials;
                        ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user_data['name']); ?></div>
                        <div class="user-email" style="font-size:12px; color:var(--gray); "><?php echo htmlspecialchars($user_data['email']); ?></div>
                        <div class="user-role"><?php echo $user_data['role']; ?></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Wrapper -->
        <div class="main-content-wrapper">
            <div class="main-content-container">
                <?php
                    switch ($activeTab) {
                        case 'bookings':
                            include __DIR__ . '/dashboard_bookings.php';
                            break;
                        case 'events':
                            include __DIR__ . '/dashboard_events.php';
                            break;
                        case 'profile':
                            include __DIR__ . '/dashboard_profile.php';
                            break;
                        case 'settings':
                            include __DIR__ . '/dashboard_settings.php';
                            break;
                        case 'help':
                            include __DIR__ . '/dashboard_help.php';
                            break;
                        default:
                            include __DIR__ . '/dashboard_home.php';
                    }
                ?>
            </div>
        </div>
    </div>

    <script>
        // Dark mode toggle functionality
        const darkModeToggle = document.getElementById('darkModeToggle');
        const themeToggleBtn = document.getElementById('themeToggle');
        const body = document.body;
        
        // Check for saved theme preference
        if (localStorage.getItem('darkMode')) {
            body.classList.add('dark-mode');
            if (darkModeToggle) darkModeToggle.checked = true;
        }
        
        // Toggle dark mode
        function toggleDarkMode() {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
            } else {
                localStorage.removeItem('darkMode');
            }
        }
        
        // Add event listeners for both toggles
        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', toggleDarkMode);
        }
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', toggleDarkMode);
        }
        
        // Booking cancellation function
        function cancelBooking(bookingId) {
            const bookingCard = event.target.closest('.booking-card');
            const eventName = bookingCard.querySelector('.event-name').textContent;
            
            if (confirm(`Are you sure you want to cancel your booking for "${eventName}"?`)) {
                // Send AJAX request to cancel booking
                fetch('controllers/BookingController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=cancelBooking&booking_id=${bookingId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        const statusBadge = bookingCard.querySelector('.status-badge');
                        const cancelButton = bookingCard.querySelector('.btn-outline');
                        
                        statusBadge.textContent = 'Cancelled';
                        statusBadge.className = 'status-badge status-completed';
                        cancelButton.innerHTML = '<i class="fas fa-check"></i> Cancelled';
                        cancelButton.disabled = true;
                        cancelButton.style.opacity = '0.7';
                        cancelButton.style.cursor = 'not-allowed';
                        
                        // Show toast notification
                        showToast(`Booking for "${eventName}" has been cancelled`);
                    } else {
                        showToast(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while cancelling the booking');
                });
            }
        }

        // View booking details function
        function viewBookingDetails(bookingId) {
            // You can implement a modal or redirect to booking details page
            window.location.href = `?tab=bookings&booking_id=${bookingId}`;
        }
        
        // Toast notification function
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
        


        // Add styles for toast notifications
        const style = document.createElement('style');
        style.textContent = `
            .toast-notification {
                position: fixed;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: var(--primary);
                color: white;
                padding: 15px 25px;
                border-radius: 12px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                opacity: 0;
                transition: transform 0.3s ease, opacity 0.3s ease;
            }
            
            .toast-notification.show {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>