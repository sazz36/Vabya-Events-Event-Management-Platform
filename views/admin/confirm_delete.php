<?php
$pageTitle = "Confirm Delete";
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-body p-4 text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
                <h2 class="mt-3">Confirm Deletion</h2>
                <p class="lead">Are you sure you want to delete this event?</p>
                
                <div class="card mb-4">
                    <div class="card-body text-start">
                        <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                        <p class="text-muted">
                            <i class="bi bi-calendar-event"></i> 
                            <?php echo date('F j, Y', strtotime($event['date'])); ?> 
                            at <?php echo date('g:i A', strtotime($event['time'])); ?>
                        </p>
                        <p class="text-muted">
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['venue']); ?>
                        </p>
                        <p>
                            <strong>Bookings:</strong> 
                            <?php echo $bookingCount; ?> 
                            (<?php echo $paidCount; ?> paid, <?php echo $pendingCount; ?> pending)
                        </p>
                    </div>
                </div>
                
                <form action="/admin/delete_event.php?id=<?php echo $event['event_id']; ?>" method="POST">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Event
                        </button>
                        <a href="/admin_panel.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>