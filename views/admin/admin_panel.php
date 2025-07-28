<?php
$pageTitle = "Admin Panel";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Admin Dashboard</h1>
    <a href="/admin/create_event.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Create Event
    </a>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Events</h5>
                        <p class="card-text display-6"><?php echo count($events); ?></p>
                    </div>
                    <i class="bi bi-calendar-event" style="font-size: 2.5rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Bookings</h5>
                        <p class="card-text display-6">
                            <?php 
                                $totalBookings = array_sum(array_map(fn($e) => $e['booking_count'], $events));
                                echo $totalBookings;
                            ?>
                        </p>
                    </div>
                    <i class="bi bi-ticket-perforated" style="font-size: 2.5rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Revenue</h5>
                        <p class="card-text display-6">
                            $<?php 
                                $revenue = array_sum(array_map(
                                    fn($e) => $e['booking_count'] * $e['price'], 
                                    $events
                                ));
                                echo number_format($revenue, 2);
                            ?>
                        </p>
                    </div>
                    <i class="bi bi-cash" style="font-size: 2.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h4 class="mb-0">My Events</h4>
    </div>
    <div class="card-body">
        <?php if (empty($events)): ?>
            <div class="alert alert-info">
                You haven't created any events yet. <a href="/admin/create_event.php">Create your first event</a>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date & Time</th>
                            <th>Bookings</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <a href="/event_detail.php?id=<?php echo $event['event_id']; ?>">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($event['date'])); ?><br>
                                    <?php echo date('g:i A', strtotime($event['time'])); ?>
                                </td>
                                <td>
                                    <?php echo $event['booking_count']; ?> / 
                                    <?php echo $event['total_seats']; ?>
                                </td>
                                <td>$<?php echo number_format($event['booking_count'] * $event['price'], 2); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/edit_event.php?id=<?php echo $event['event_id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/admin/event_bookings.php?id=<?php echo $event['event_id']; ?>" 
                                           class="btn btn-outline-info" title="View Bookings">
                                            <i class="bi bi-ticket-detailed"></i>
                                        </a>
                                        <a href="/admin/delete_event.php?id=<?php echo $event['event_id']; ?>" 
                                           class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Export Data</h4>
    </div>
    <div class="card-body">
        <div class="d-flex gap-3">
            <a href="/admin/export.php?type=json" class="btn btn-outline-secondary">
                <i class="bi bi-filetype-json"></i> Export as JSON
            </a>
            <a href="/admin/export.php?type=xml" class="btn btn-outline-secondary">
                <i class="bi bi-filetype-xml"></i> Export as XML
            </a>
            <a href="/admin/export.php?type=csv" class="btn btn-outline-secondary">
                <i class="bi bi-filetype-csv"></i> Export as CSV
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// ✅ Fixed include path
include __DIR__ . '/../layout.php';
?>
