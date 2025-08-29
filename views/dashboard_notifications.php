<?php
session_start();
require_once __DIR__ . '/../Config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$pdo = $db->getConnection();
$userId = $_SESSION['user']['user_id'];

// Get user notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE recipient_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Get unread count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE recipient_id = ? AND is_read = 0
");
$stmt->execute([$userId]);
$unreadCount = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Event Sphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item.unread {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .notification-item.unread:hover {
            background-color: #bbdefb;
        }
        
        .notification-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .notification-type.event_created {
            background-color: #e8f5e8;
            color: #2e7d32;
        }
        
        .notification-type.booking_request {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .notification-type.booking_approved {
            background-color: #e8f5e8;
            color: #2e7d32;
        }
        
        .notification-type.booking_rejected {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard_notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard_bookings.php">
                                <i class="fas fa-ticket-alt"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard_profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Notifications</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($unreadCount > 0): ?>
                            <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                                <i class="fas fa-check-double"></i> Mark all as read
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bell"></i> 
                                    All Notifications
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge bg-primary ms-2"><?php echo $unreadCount; ?> unread</span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($notifications)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-bell-slash"></i>
                                        <h4>No notifications yet</h4>
                                        <p>You'll see notifications here when new events are added or booking status changes.</p>
                                    </div>
                                <?php else: ?>
                                    <div id="notificationsList">
                                        <?php foreach ($notifications as $notification): ?>
                                            <div class="notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" 
                                                 data-id="<?php echo $notification['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <span class="notification-type <?php echo $notification['type']; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                                            </span>
                                                            <span class="notification-time ms-3">
                                                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                            </span>
                                                        </div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                        <?php if ($notification['event_title']): ?>
                                                            <small class="text-info">
                                                                <i class="fas fa-calendar"></i> 
                                                                <?php echo htmlspecialchars($notification['event_title']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($notification['is_read'] == 0): ?>
                                                        <button class="btn btn-sm btn-outline-primary mark-read-btn" 
                                                                data-id="<?php echo $notification['id']; ?>">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark individual notification as read
            document.querySelectorAll('.mark-read-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const notificationId = this.dataset.id;
                    markAsRead(notificationId);
                });
            });

            // Mark all as read
            const markAllReadBtn = document.getElementById('markAllRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function() {
                    markAllAsRead();
                });
            }

            // Mark notification as read
            async function markAsRead(notificationId) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'markAsRead');
                    formData.append('notification_id', notificationId);
                    
                    const response = await fetch('controllers/NotificationController.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        // Remove unread styling
                        const item = document.querySelector(`[data-id="${notificationId}"]`);
                        if (item) {
                            item.classList.remove('unread');
                            const btn = item.querySelector('.mark-read-btn');
                            if (btn) btn.remove();
                        }
                        
                        // Update unread count
                        updateUnreadCount();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            // Mark all notifications as read
            async function markAllAsRead() {
                try {
                    const formData = new FormData();
                    formData.append('action', 'markAllAsRead');
                    
                    const response = await fetch('controllers/NotificationController.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        // Remove unread styling from all items
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                            const btn = item.querySelector('.mark-read-btn');
                            if (btn) btn.remove();
                        });
                        
                        // Hide mark all read button
                        if (markAllReadBtn) {
                            markAllReadBtn.style.display = 'none';
                        }
                        
                        // Update unread count
                        updateUnreadCount();
                    }
                } catch (error) {
                    console.error('Error marking all notifications as read:', error);
                }
            }

            // Update unread count
            async function updateUnreadCount() {
                try {
                    const response = await fetch('controllers/NotificationController.php?action=getUnreadCount');
                    const data = await response.json();
                    
                    if (data.success) {
                        const badge = document.querySelector('.badge.bg-primary');
                        if (data.count === 0) {
                            if (badge) badge.style.display = 'none';
                        } else {
                            if (badge) {
                                badge.style.display = 'inline';
                                badge.textContent = data.count;
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error updating unread count:', error);
                }
            }
        });
    </script>
</body>
</html> 