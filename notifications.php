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

require_once __DIR__ . '/Config/db.php';
require_once __DIR__ . '/controllers/NotificationController.php';

$db = new Database();
$pdo = $db->getConnection();
$notificationService = new NotificationService($pdo);

// Get user data
$user_id = $_SESSION['user']['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

// Handle mark as read action
if (isset($_POST['mark_as_read'])) {
    $notification_id = intval($_POST['notification_id']);
    $notificationService->markAsRead($notification_id);
    header('Location: notifications.php?marked=1');
    exit;
}

// Handle mark all as read action
if (isset($_POST['mark_all_read'])) {
    $notificationService->markAllAsRead($user_id);
    header('Location: notifications.php?marked_all=1');
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get notifications for user
$notifications = $notificationService->getUserNotifications($user_id, $per_page, $offset);
$total_notifications = $notificationService->getTotalNotifications($user_id);
$total_pages = ceil($total_notifications / $per_page);

// Get unread count
$unread_count = $notificationService->getUnreadCount($user_id);
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
        }

        .dark-mode {
            --dark: var(--neutral-light);
            --light: var(--neutral-dark);
            --light-gray: #1e1e1e;
            --card-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
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

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
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

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
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

        .notification-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 30px;
            border-top: 1px solid var(--light-gray);
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
        }

        .page-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(67, 160, 71, 0.1);
            color: var(--success);
            border: 1px solid rgba(67, 160, 71, 0.2);
        }

        /* Dark mode support */
        .dark-mode .header,
        .dark-mode .notifications-container {
            background: var(--dark-card);
            color: var(--dark-text);
        }

        .dark-mode .notifications-header {
            border-bottom-color: var(--dark-border);
        }

        .dark-mode .notification-item {
            border-bottom-color: var(--dark-border);
        }

        .dark-mode .notification-item:hover {
            background: rgba(94, 53, 177, 0.1);
        }

        .dark-mode .notification-item.unread {
            background: rgba(94, 53, 177, 0.15);
        }

        .dark-mode .pagination {
            border-top-color: var(--dark-border);
        }

        .dark-mode .page-link {
            border-color: var(--dark-border);
            color: var(--dark-text);
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
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

            .notification-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Success Messages -->
        <?php if (isset($_GET['marked'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Notification marked as read successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['marked_all'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> All notifications marked as read successfully!
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>Notifications</h1>
                <p>Stay updated with all your event notifications</p>
            </div>
            <div class="header-actions">
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-outline">
                            <i class="fas fa-check-double"></i>
                            Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
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
                        <span class="stat-badge"><?php echo $total_notifications; ?></span>
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
                                
                                <div class="notification-actions">
                                    <?php if ($notification['is_read'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_as_read" class="btn btn-sm btn-outline">
                                                <i class="fas fa-check"></i>
                                                Mark read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Add click handler to mark notifications as read
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', function() {
                const form = this.querySelector('form');
                if (form) {
                    form.submit();
                }
            });
        });
    </script>
</body>
</html> 