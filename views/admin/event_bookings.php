<?php
$pageTitle = "Event Bookings";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Bookings for: <?php echo htmlspecialchars($event['title']); ?></h1>
    <a href="/admin_panel.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Admin Panel
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Booking Summary</h4>
            <div>
                <span class="badge bg-success me-2">
                    Paid: <?php echo $paidCount; ?>
                </span>
                <span class="badge bg-warning me-2">
                    Pending: <?php echo $pendingCount; ?>
                </span>
                <span class="badge bg-danger">
                    Cancelled: <?php echo $cancelledCount; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['date'])); ?></p>
            </div>
            <div class="col-md-4">
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['time'])); ?></p>
            </div>
            <div class="col-md-4">
                <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Booking Details</h4>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                        id="exportDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/admin/export_bookings.php?id=<?php echo $event['event_id']; ?>&type=json">
                        <i class="bi bi-filetype-json"></i> JSON
                    </a></li>
                    <li><a class="dropdown-item" href="/admin/export_bookings.php?id=<?php echo $event['event_id']; ?>&type=xml">
                        <i class="bi bi-filetype-xml"></i> XML
                    </a></li>
                    <li><a class="dropdown-item" href="/admin/export_bookings.php?id=<?php echo $event['event_id']; ?>&type=csv">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                No bookings found for this event.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Attendee</th>
                            <th>Seat</th>
                            <th>Booking Date</th>
                            <th>Payment Status</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>ES-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($booking['attendee_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($booking['booking_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['payment_status'] === 'paid' ? 'success' : 
                                             ($booking['payment_status'] === 'pending' ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($event['price'], 2); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($booking['payment_status'] === 'pending'): ?>
                                            <button class="btn btn-outline-success mark-paid-btn" 
                                                    data-booking-id="<?php echo $booking['booking_id']; ?>">
                                                <i class="bi bi-check-circle"></i> Mark Paid
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($booking['payment_status'] !== 'cancelled'): ?>
                                            <button class="btn btn-outline-danger cancel-booking-btn" 
                                                    data-booking-id="<?php echo $booking['booking_id']; ?>">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark booking as paid
    document.querySelectorAll('.mark-paid-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            if (confirm('Mark this booking as paid?')) {
                fetch(`/admin/mark_paid.php?id=${bookingId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    });

    // Cancel booking
    document.querySelectorAll('.cancel-booking-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            if (confirm('Cancel this booking? The seat will be made available again.')) {
                fetch(`/admin/cancel_booking.php?id=${bookingId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
