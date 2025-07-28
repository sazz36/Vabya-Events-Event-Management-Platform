<?php
// Main dashboard home content (welcome, stats, bookings, profile, preferences)
?>
<style>
    /* Use exact same color scheme as main dashboard */
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

    /* Update notification icon colors to match admin dashboard */
    .notification-icon .text-success {
        color: var(--success) !important;
    }

    /* Update stat items to match admin dashboard metric cards */
    .stat-item {
        background: var(--light);
        border-radius: 16px;
        padding: 15px 20px;
        box-shadow: var(--card-shadow);
        border: 1px solid var(--light-gray);
        transition: var(--transition);
    }

    .stat-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--primary);
    }

    .stat-label {
        font-size: 15px;
        color: var(--gray);
        font-weight: 500;
    }

    /* Update status badges to match admin dashboard */
    .status-badge.status-upcoming {
        background: var(--primary);
        color: white;
    }

    .status-badge.status-completed {
        background: var(--success);
        color: white;
    }

    /* Update buttons to match admin dashboard */
    .btn-primary {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .action-btn.btn-outline {
        border: 1px solid var(--primary);
        color: var(--primary);
    }

    .action-btn.btn-outline:hover {
        background: var(--primary);
        color: white;
    }

    /* Update card styling to match admin dashboard */
    .card {
        background: var(--light);
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        border: 1px solid var(--light-gray);
    }

    .card-title {
        color: var(--primary-dark);
    }

    .view-all {
        color: var(--primary);
    }

    .view-all:hover {
        color: var(--primary-dark);
        background: rgba(94, 53, 177, 0.08);
    }
</style>

<main class="main-content">
    <!-- Welcome Card -->
    <div class="welcome-card">
        <h2>Welcome back, <?php echo $user_data['name']; ?>!</h2>
        <p>You have <?php echo $user_data['upcoming_events']; ?> upcoming events this month. Your next event is the Tech Innovation Summit starting tomorrow at 9:00 AM.</p>
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $user_data['events_attended']; ?></div>
                <div class="stat-label">Events Attended</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $user_data['upcoming_events']; ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $user_data['vip_bookings']; ?></div>
                <div class="stat-label">VIP Bookings</div>
            </div>
        </div>
    </div>

    <!-- Notifications Section -->
    <?php if (!empty($user_notifications)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bell"></i> Recent Notifications</h3>
        </div>
        <div class="notifications-list">
            <?php foreach ($user_notifications as $notification): ?>
            <div class="notification-item">
                <div class="notification-icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">Booking Approved!</div>
                    <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                    <div class="notification-time"><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Bookings - Horizontal Layout -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Upcoming Bookings</h3>
            <a href="?tab=bookings" class="view-all">View All</a>
        </div>
        <div class="horizontal-bookings-list">
            <?php if (!empty($dashboard_data['upcoming_bookings'])): ?>
                <?php foreach ($dashboard_data['upcoming_bookings'] as $booking): ?>
                <div class="booking-card">
                    <div class="booking-card-header">
                        <div class="event-name"><?php echo htmlspecialchars($booking['title']); ?></div>
                        <div class="booking-date"><?php echo $booking['date']; ?></div>
                    </div>
                    <div class="booking-card-details">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($booking['location']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-chair"></i>
                            <span>Seat: <?php echo htmlspecialchars($booking['seat']); ?></span>
                        </div>
                        <?php if (isset($booking['ticket'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Ticket: <?php echo htmlspecialchars($booking['ticket']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="booking-card-status">
                        <div class="status-badge status-upcoming"><?php echo htmlspecialchars($booking['status_text']); ?></div>
                        <button class="action-btn btn-outline" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-bookings-message">
                    <div class="no-bookings-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h4>No Upcoming Bookings</h4>
                    <p>You don't have any upcoming events booked yet.</p>
                    <a href="?tab=events" class="btn-primary">
                        <i class="fas fa-search"></i>
                        Browse Events
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Events - Horizontal Layout -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history"></i> Recent Events</h3>
            <a href="?tab=bookings" class="view-all">View All</a>
        </div>
        <div class="horizontal-bookings-list">
            <?php if (!empty($dashboard_data['past_bookings'])): ?>
                <?php foreach ($dashboard_data['past_bookings'] as $booking): ?>
                <div class="booking-card">
                    <div class="booking-card-header">
                        <div class="event-name"><?php echo htmlspecialchars($booking['title']); ?></div>
                        <div class="booking-date"><?php echo $booking['date']; ?></div>
                    </div>
                    <div class="booking-card-details">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($booking['location']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-chair"></i>
                            <span>Seat: <?php echo htmlspecialchars($booking['seat']); ?></span>
                        </div>
                    </div>
                    <div class="booking-card-status">
                        <div class="status-badge status-completed"><?php echo htmlspecialchars($booking['status_text']); ?></div>
                        <button class="action-btn btn-outline" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                            <i class="fas fa-info-circle"></i>
                            Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-bookings-message">
                    <div class="no-bookings-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h4>No Recent Events</h4>
                    <p>You haven't attended any events yet.</p>
                    <a href="?tab=events" class="btn-primary">
                        <i class="fas fa-search"></i>
                        Browse Events
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
 