<?php
session_start();

if (!isset($event)) {
    // Example fallback: Redirect or show an error
    echo "Event not found.";
    exit;
}

$seats = $seats ?? []; // Avoid undefined warning

$pageTitle = $event['title'];
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h1 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin' && $_SESSION['user_id'] == $event['created_by']): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="eventActions" data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/admin/edit_event.php?id=<?php echo $event['event_id']; ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </a></li>
                                <li><a class="dropdown-item" href="/admin/delete_event.php?id=<?php echo $event['event_id']; ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/admin/event_bookings.php?id=<?php echo $event['event_id']; ?>">
                                    <i class="bi bi-ticket-detailed"></i> View Bookings
                                </a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="text-muted mb-3">
                    <i class="bi bi-person"></i> Organized by <?php echo htmlspecialchars($event['organizer_name']); ?>
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <span class="badge bg-primary rounded-pill fs-6">
                        <i class="bi bi-calendar-event"></i> 
                        <?php echo date('l, F j, Y', strtotime($event['date'])); ?>
                    </span>
                    <span class="badge bg-primary rounded-pill fs-6">
                        <i class="bi bi-clock"></i> 
                        <?php echo date('g:i A', strtotime($event['time'])); ?>
                    </span>
                    <span class="badge bg-primary rounded-pill fs-6">
                        <i class="bi bi-cash"></i> 
                        Rs <?php echo number_format($event['price'], 2); ?> per seat
                    </span>
                </div>

                <div class="mb-4">
                    <h4>About This Event</h4>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>

                <div class="mb-4">
                    <h4>Location</h4>
                    <div id="eventMap" style="height: 300px; width: 100%;" 
                         data-lat="<?php echo htmlspecialchars($event['venue_lat']); ?>" 
                         data-lng="<?php echo htmlspecialchars($event['venue_lng']); ?>"
                         data-venue="<?php echo htmlspecialchars($event['venue']); ?>"></div>
                    <p class="mt-2">
                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['venue']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Book Your Seat</h4>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-info">
                        Please <a href="/login.php">login</a> to book seats for this event.
                    </div>
                <?php else: ?>
                    <div id="seatSelection">
                        <div class="mb-3">
                            <label for="seatNumber" class="form-label">Available Seats</label>
                            <select class="form-select" id="seatNumber" required>
                                <option value="">Select a seat</option>
                                <?php foreach ($seats as $seat): ?>
                                    <?php if ($seat['status'] === 'available'): ?>
                                        <option value="<?php echo $seat['seat_id']; ?>">
                                            <?php echo htmlspecialchars($seat['seat_number']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button id="bookNowBtn" class="btn btn-primary" 
                                    data-event-id="<?php echo $event['event_id']; ?>">
                                Book Now
                            </button>
                        </div>
                    </div>

                    <div id="bookingForm" style="display: none;">
                        <form id="confirmBookingForm" method="POST" action="/create_booking.php">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <input type="hidden" id="selectedSeatId" name="seat_id" value="">

                            <div class="alert alert-success">
                                You've selected seat: <strong id="selectedSeatNumber"></strong>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">Confirm Booking</button>
                                <button type="button" id="cancelBookingBtn" class="btn btn-outline-secondary">
                                    Change Seat
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Event Details</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="bi bi-calendar-event"></i> 
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['date'])); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-clock"></i> 
                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($event['time'])); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-geo-alt"></i> 
                        <strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-cash"></i> 
                        <strong>Price:</strong> Rs <?php echo number_format($event['price'], 2); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-ticket-perforated"></i> 
                        <strong>Available Seats:</strong> 
                        <?php 
                            $availableSeats = array_filter($seats, fn($seat) => $seat['status'] === 'available');
                            echo count($availableSeats);
                        ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>


document.addEventListener('DOMContentLoaded', function() {
    const mapElement = document.getElementById('eventMap');
    if (mapElement) {
        const lat = parseFloat(mapElement.getAttribute('data-lat'));
        const lng = parseFloat(mapElement.getAttribute('data-lng'));
        const venue = mapElement.getAttribute('data-venue');
        
        if (lat && lng) {
            console.log(`Initialize Google Map here at ${lat}, ${lng} for ${venue}`);
        }
    }

    const seatSelection = document.getElementById('seatSelection');
    const bookingForm = document.getElementById('bookingForm');
    const bookNowBtn = document.getElementById('bookNowBtn');
    const cancelBookingBtn = document.getElementById('cancelBookingBtn');
    const seatNumberSelect = document.getElementById('seatNumber');
    const selectedSeatId = document.getElementById('selectedSeatId');
    const selectedSeatNumber = document.getElementById('selectedSeatNumber');

    if (bookNowBtn) {
        bookNowBtn.addEventListener('click', function() {
            const selectedSeat = seatNumberSelect.value;
            if (!selectedSeat) {
                alert('Please select a seat first');
                return;
            }

            const selectedOption = seatNumberSelect.options[seatNumberSelect.selectedIndex];
            selectedSeatId.value = selectedSeat;
            selectedSeatNumber.textContent = selectedOption.text;

            seatSelection.style.display = 'none';
            bookingForm.style.display = 'block';
        });
    }

    if (cancelBookingBtn) {
        cancelBookingBtn.addEventListener('click', function() {
            seatSelection.style.display = 'block';
            bookingForm.style.display = 'none';
            seatNumberSelect.value = '';
        });
    }

    // Simulated polling for seat updates
    setInterval(function() {
        console.log('Polling seat availability...');
    }, 30000);
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
