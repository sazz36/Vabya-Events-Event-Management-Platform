<?php
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}

// Simple database connection
try {
    require_once __DIR__ . '/Config/db.php';
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get user data
    $user_id = $_SESSION['user']['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    // Get notifications for user
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE recipient_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE recipient_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);
    $unread_result = $stmt->fetch();
    $unread_count = $unread_result['count'] ?? 0;
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    $notifications = [];
    $unread_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - भव्य Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5e35b1;
            --primary-dark: #4527a0;
            --primary-light: #7e57c2;
            --secondary: #26a69a;
            --accent: #ff7043;
            --dark: #263238;
            --light: #f5f7fb;
            --gray: #b0bec5;
            --light-gray: #eceff1;
            --success: #43a047;
            --warning: #ffb300;
            --danger: #e53935;
            --card-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: var(--primary-dark);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header-left p {
            color: var(--gray);
            font-size: 16px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .notifications-container {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .notifications-header {
            padding: 20px 30px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .notifications-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .notifications-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--gray);
        }

        .stat-badge {
            background: var(--primary);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .notifications-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 20px 30px;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: rgba(94, 53, 177, 0.05);
        }

        .notification-item.unread {
            background: rgba(94, 53, 177, 0.08);
            border-left: 4px solid var(--primary);
        }

        .notification-item.unread:hover {
            background: rgba(94, 53, 177, 0.12);
        }

        .notification-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .notification-main {
            flex: 1;
        }

        .notification-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
        }

        .notification-message {
            font-size: 14px;
            color: var(--dark);
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .notification-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 12px;
            color: var(--gray);
        }

        .notification-time {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .notification-type {
            background: var(--light-gray);
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }

        .unread-indicator {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .no-notifications {
            padding: 60px 30px;
            text-align: center;
            color: var(--gray);
        }

        .no-notifications i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-notifications h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .no-notifications p {
            font-size: 14px;
            max-width: 400px;
            margin: 0 auto;
        }

        .error-message {
            background: rgba(229, 57, 53, 0.1);
            color: var(--danger);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(229, 57, 53, 0.2);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .notifications-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .notifications-stats {
                width: 100%;
                justify-content: space-between;
            }

            .notification-content {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>Notifications</h1>
                <p>Stay updated with all your event notifications</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo $_SESSION['user']['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Notifications Container -->
        <div class="notifications-container">
            <div class="notifications-header">
                <div class="notifications-title">
                    All Notifications
                </div>
                <div class="notifications-stats">
                    <div class="stat-item">
                        <span>Total:</span>
                        <span class="stat-badge"><?php echo count($notifications); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Unread:</span>
                        <span class="stat-badge"><?php echo $unread_count; ?></span>
                    </div>
                </div>
            </div>

            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications yet</h3>
                        <p>You'll see notifications here when new events are created or when there are updates to your bookings.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                            <?php if ($notification['is_read'] == 0): ?>
                                <div class="unread-indicator"></div>
                            <?php endif; ?>
                            
                            <div class="notification-content">
                                <div class="notification-main">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-meta">
                                        <div class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                                        </div>
                                        <div class="notification-type">
                                            <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 