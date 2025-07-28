<?php
// Include database connection
require_once __DIR__ . '/../../Config/db.php';

// Get unread notification count for current user
$unreadCount = 0;
if (isset($_SESSION['user']['user_id'])) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE recipient_id = ? AND is_read = 0
        ");
        $stmt->execute([$_SESSION['user']['user_id']]);
        $result = $stmt->fetch();
        $unreadCount = $result['count'] ?? 0;
    } catch (Exception $e) {
        // Silently fail
    }
}
?>

<!-- Notification Bell Component -->
<div class="notification-bell-container">
    <button class="notification-bell-btn" id="notificationBell" title="Notifications">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge" id="notificationBadge">
                <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
            </span>
        <?php endif; ?>
    </button>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h6>Notifications</h6>
            <?php if ($unreadCount > 0): ?>
                <button class="mark-all-read-btn" id="markAllRead">Mark all read</button>
            <?php endif; ?>
        </div>
        
        <div id="notificationList" class="notification-list">
            <div class="loading-notifications">
                <i class="fas fa-spinner fa-spin"></i> Loading notifications...
            </div>
        </div>
        
        <div class="notification-footer">
            <!-- Removed 'View all notifications' link as requested -->
        </div>
    </div>
</div>

<style>
.notification-bell-container {
    position: relative;
    display: inline-block;
}

.notification-bell-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    position: relative;
    transition: all 0.3s ease;
    color: var(--dark);
    font-size: 18px;
}

.notification-bell-btn:hover {
    background: rgba(27, 60, 83, 0.1);
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #e53935;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1.2;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    max-height: 400px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border: 1px solid #e0e0e0;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    margin-top: 8px;
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
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.notification-header h6 {
    margin: 0;
    font-weight: 600;
    color: var(--primary);
    font-size: 14px;
}

.mark-all-read-btn {
    background: none;
    border: 1px solid var(--primary);
    color: var(--primary);
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mark-all-read-btn:hover {
    background: var(--primary);
    color: white;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.loading-notifications {
    text-align: center;
    padding: 20px;
    color: #666;
    font-size: 14px;
}

.notification-item {
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: rgba(27, 60, 83, 0.1);
    border-left: 4px solid var(--primary);
}

.notification-item.unread:hover {
    background-color: rgba(27, 60, 83, 0.15);
}

.notification-title {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 4px;
    color: var(--primary);
}

.notification-message {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notification-time {
    font-size: 11px;
    color: #999;
}

.notification-footer {
    padding: 12px 20px;
    border-top: 1px solid #f0f0f0;
    text-align: center;
}

.view-all-link {
    color: var(--primary);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.view-all-link:hover {
    color: var(--primary-dark);
}

/* Dark mode support */
.dark-mode .notification-dropdown {
    background: var(--dark-card);
    border-color: var(--dark-border);
    color: var(--dark-text);
}

.dark-mode .notification-header {
    border-bottom-color: var(--dark-border);
}

.dark-mode .notification-item {
    border-bottom-color: var(--dark-border);
}

.dark-mode .notification-item:hover {
    background-color: rgba(27, 60, 83, 0.1);
}

.dark-mode .notification-item.unread {
    background-color: rgba(27, 60, 83, 0.15);
}

.dark-mode .notification-footer {
    border-top-color: var(--dark-border);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .notification-dropdown {
        width: 300px;
        right: -50px;
    }
}
</style>

<script>
class NotificationManager {
    constructor() {
        this.bell = document.getElementById('notificationBell');
        this.dropdown = document.getElementById('notificationDropdown');
        this.badge = document.getElementById('notificationBadge');
        this.list = document.getElementById('notificationList');
        this.markAllReadBtn = document.getElementById('markAllRead');
        this.unreadCount = <?php echo $unreadCount; ?>;
        
        this.init();
    }
    
    init() {
        this.loadNotifications();
        this.setupEventListeners();
        this.startPolling();
    }
    
    setupEventListeners() {
        // Toggle dropdown
        this.bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.bell.contains(e.target) && !this.dropdown.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Mark all as read
        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }
        
        // View all notifications
        const viewAllBtn = document.getElementById('viewAllNotifications');
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = '../notifications.php';
            });
        }
    }
    
    toggleDropdown() {
        this.dropdown.classList.toggle('show');
    }
    
    closeDropdown() {
        this.dropdown.classList.remove('show');
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('../controllers/NotificationController.php?action=getNotifications&limit=10');
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.notifications);
            } else {
                this.renderError('Failed to load notifications');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.renderError('Failed to load notifications');
        }
    }
    
    renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            this.list.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="fas fa-bell-slash"></i>
                    <div class="mt-2">No notifications</div>
                </div>
            `;
            return;
        }
        
        this.list.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.is_read == 0 ? 'unread' : ''}" 
                 data-id="${notification.id}" onclick="notificationManager.markAsRead(${notification.id})">
                <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                <div class="notification-time">${this.formatTime(notification.created_at)}</div>
            </div>
        `).join('');
    }
    
    renderError(message) {
        this.list.innerHTML = `
            <div class="text-center text-danger py-3">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="mt-2">${message}</div>
            </div>
        `;
    }
    
    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('action', 'markAsRead');
            formData.append('notification_id', notificationId);
            
            const response = await fetch('../controllers/NotificationController.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                // Remove unread styling
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                
                // Update unread count
                this.updateUnreadCount(this.unreadCount - 1);
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'markAllAsRead');
            
            const response = await fetch('../controllers/NotificationController.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                // Remove unread styling from all items
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Update unread count
                this.updateUnreadCount(0);
                
                // Hide mark all read button
                if (this.markAllReadBtn) {
                    this.markAllReadBtn.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    updateUnreadCount(count) {
        this.unreadCount = Math.max(0, count);
        
        if (this.unreadCount === 0) {
            if (this.badge) {
                this.badge.style.display = 'none';
            }
            if (this.markAllReadBtn) {
                this.markAllReadBtn.style.display = 'none';
            }
        } else {
            if (this.badge) {
                this.badge.style.display = 'block';
                this.badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            }
        }
    }
    
    startPolling() {
        // Poll for new notifications every 30 seconds
        setInterval(async () => {
            try {
                const response = await fetch('../controllers/NotificationController.php?action=getUnreadCount');
                const data = await response.json();
                
                if (data.success && data.count !== this.unreadCount) {
                    this.updateUnreadCount(data.count);
                    
                    // If count increased, reload notifications
                    if (data.count > this.unreadCount) {
                        this.loadNotifications();
                    }
                }
            } catch (error) {
                console.error('Error polling notifications:', error);
            }
        }, 30000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        
        return date.toLocaleDateString();
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});
</script> 