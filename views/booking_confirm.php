<?php
session_start(); // Always start session at the top if using $_SESSION

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$pageTitle = "Booking Confirmation";

// Optional safe fallbacks (only for development/testing)
$event = $event ?? [];
$booking = $booking ?? [];
$seat = $seat ?? [];

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Booking Confirmed!</h2>
                    <p class="lead">Thank you for booking with भव्य Event</p>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Booking Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Event:</strong> <?php echo htmlspecialchars($event['title'] ?? ''); ?></p>
                                <p><strong>Date:</strong> <?php echo isset($event['date']) ? date('F j, Y', strtotime($event['date'])) : ''; ?></p>
                                <p><strong>Time:</strong> <?php echo isset($event['time']) ? date('g:i A', strtotime($event['time'])) : ''; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue'] ?? ''); ?></p>
                                <p><strong>Seat Number:</strong> <?php echo htmlspecialchars($seat['seat_number'] ?? ''); ?></p>
                                <p><strong>Booking Reference:</strong> ES-<?php echo isset($booking['booking_id']) ? str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT) : ''; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Payment Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Amount Paid:</strong> $<?php echo isset($event['price']) ? number_format($event['price'], 2) : '0.00'; ?></p>
                                <p><strong>Payment Status:</strong> 
                                    <span class="badge bg-<?php echo ($booking['payment_status'] ?? '') === 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($booking['payment_status'] ?? 'Pending'); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php if (($booking['payment_status'] ?? '') === 'pending'): ?>
                                    <p>Your booking is confirmed but payment is pending.</p>
                                    <a href="/payment.php?id=<?php echo urlencode($booking['booking_id']); ?>&csrf_token=<?php echo urlencode($csrf_token); ?>" class="btn btn-primary">
                                        Complete Payment Now
                                    </a>
                                <?php else: ?>
                                    <p><strong>Payment Method:</strong> <?php echo ucfirst($booking['payment_method'] ?? ''); ?></p>
                                    <p><strong>Payment Reference:</strong> <?php echo htmlspecialchars($booking['payment_reference'] ?? ''); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="/dashboard.php" class="btn btn-outline-primary">
                        <i class="bi bi-person-lines-fill"></i> View My Bookings
                    </a>
                    <a href="/event_detail.php?id=<?php echo $event['event_id'] ?? ''; ?>" class="btn btn-primary">
                        <i class="bi bi-calendar-event"></i> Back to Event
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>