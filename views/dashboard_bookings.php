<?php
// My Bookings tab content
$user_id = $_SESSION['user']['user_id'];

// Fetch user's bookings from database
$stmt = $conn->prepare("
    SELECT b.*, e.title, e.date, e.time, e.venue, e.price, e.category
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->execute([$user_id]);
$user_bookings = $stmt->fetchAll();

// Separate bookings by status
$confirmed_bookings = array_filter($user_bookings, function($booking) {
    return $booking['status'] === 'confirmed';
});

$pending_bookings = array_filter($user_bookings, function($booking) {
    return $booking['status'] === 'pending';
});

$cancelled_bookings = array_filter($user_bookings, function($booking) {
    return $booking['status'] === 'cancelled';
});
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> My Bookings</h3>
        <div class="booking-stats">
            <span class="stat-item">
                <i class="fas fa-check-circle text-success"></i>
                <?php echo count($confirmed_bookings); ?> Confirmed
            </span>
            <span class="stat-item">
                <i class="fas fa-clock text-warning"></i>
                <?php echo count($pending_bookings); ?> Pending
            </span>
            <span class="stat-item">
                <i class="fas fa-times-circle text-danger"></i>
                <?php echo count($cancelled_bookings); ?> Cancelled
            </span>
        </div>
    </div>
    
    <!-- Confirmed Bookings -->
    <?php if (!empty($confirmed_bookings)): ?>
    <div class="booking-section">
        <h4 class="section-title text-success">
            <i class="fas fa-check-circle"></i> Confirmed Bookings
        </h4>
        <div class="bookings-list">
            <?php foreach ($confirmed_bookings as $booking): ?>
            <div class="booking confirmed-booking">
                <div class="booking-header">
                    <div class="event-name"><?php echo htmlspecialchars($booking['title']); ?></div>
                    <div class="booking-date"><?php echo date('M d, Y', strtotime($booking['date'])); ?></div>
                </div>
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($booking['venue']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('h:i A', strtotime($booking['time'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Ticket Type: <?php echo htmlspecialchars($booking['ticket_type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-hashtag"></i>
                        <span>Quantity: <?php echo htmlspecialchars($booking['quantity']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Total: $<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                </div>
                <div class="booking-status">
                    <div class="status-badge status-confirmed">
                        <i class="fas fa-check-circle"></i> Confirmed
                    </div>
                    <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pending Bookings -->
    <?php if (!empty($pending_bookings)): ?>
    <div class="booking-section">
        <h4 class="section-title text-warning">
            <i class="fas fa-clock"></i> Pending Bookings
        </h4>
        <div class="bookings-list">
            <?php foreach ($pending_bookings as $booking): ?>
            <div class="booking pending-booking">
                <div class="booking-header">
                    <div class="event-name"><?php echo htmlspecialchars($booking['title']); ?></div>
                    <div class="booking-date"><?php echo date('M d, Y', strtotime($booking['date'])); ?></div>
                </div>
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($booking['venue']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('h:i A', strtotime($booking['time'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Ticket Type: <?php echo htmlspecialchars($booking['ticket_type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-hashtag"></i>
                        <span>Quantity: <?php echo htmlspecialchars($booking['quantity']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Total: $<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                </div>
                <div class="booking-status">
                    <div class="status-badge status-pending">
                        <i class="fas fa-clock"></i> Pending Approval
                    </div>
                    <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cancelled Bookings -->
    <?php if (!empty($cancelled_bookings)): ?>
    <div class="booking-section">
        <h4 class="section-title text-danger">
            <i class="fas fa-times-circle"></i> Cancelled Bookings
        </h4>
        <div class="bookings-list">
            <?php foreach ($cancelled_bookings as $booking): ?>
            <div class="booking cancelled-booking">
                <div class="booking-header">
                    <div class="event-name"><?php echo htmlspecialchars($booking['title']); ?></div>
                    <div class="booking-date"><?php echo date('M d, Y', strtotime($booking['date'])); ?></div>
                </div>
                <div class="booking-details">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($booking['venue']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('h:i A', strtotime($booking['time'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Ticket Type: <?php echo htmlspecialchars($booking['ticket_type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-hashtag"></i>
                        <span>Quantity: <?php echo htmlspecialchars($booking['quantity']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Total: $<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                </div>
                <div class="booking-status">
                    <div class="status-badge status-cancelled">
                        <i class="fas fa-times-circle"></i> Cancelled
                    </div>
                    <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- No Bookings Message -->
    <?php if (empty($user_bookings)): ?>
    <div class="no-bookings">
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h4>No Bookings Yet</h4>
            <p>You haven't made any bookings yet. Start by exploring events and making your first booking!</p>
            <a href="?tab=events" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Events
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.booking-stats {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    font-weight: 500;
}

.booking-section {
    margin-bottom: 30px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.booking {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.booking:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.confirmed-booking {
    border-left: 4px solid #28a745;
}

.pending-booking {
    border-left: 4px solid #ffc107;
}

.cancelled-booking {
    border-left: 4px solid #dc3545;
    opacity: 0.7;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.event-name {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

.booking-date {
    font-size: 14px;
    color: #6c757d;
    font-weight: 500;
}

.booking-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #495057;
}

.detail-item i {
    color: #6c757d;
    width: 16px;
}

.booking-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-confirmed {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.booking-id {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

.no-bookings {
    text-align: center;
    padding: 60px 20px;
}

.empty-state {
    max-width: 400px;
    margin: 0 auto;
}

.empty-state i {
    font-size: 64px;
    color: #dee2e6;
    margin-bottom: 20px;
}

.empty-state h4 {
    color: #6c757d;
    margin-bottom: 10px;
}

.empty-state p {
    color: #adb5bd;
    margin-bottom: 25px;
    line-height: 1.6;
}

.btn-primary {
    background: var(--primary);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}
</style> 