<?php
$pageTitle = "Edit Event";
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">Edit Event</h2>
                
                <form action="/admin/edit_event.php?id=<?php echo $event['event_id']; ?>" method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Event Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($event['title']); ?>" required>
                            <div class="invalid-feedback">Please provide a title for the event.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price per Seat ($)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo htmlspecialchars($event['price']); ?>" min="0" step="0.01" required>
                            <div class="invalid-feedback">Please provide a valid price.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php 
                            echo htmlspecialchars($event['description']); 
                        ?></textarea>
                        <div class="invalid-feedback">Please provide a description.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($event['date']); ?>" required>
                            <div class="invalid-feedback">Please select a date.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="time" name="time" 
                                   value="<?php echo htmlspecialchars($event['time']); ?>" required>
                            <div class="invalid-feedback">Please select a time.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="venue" class="form-label">Venue</label>
                        <input type="text" class="form-control" id="venue" name="venue" 
                               value="<?php echo htmlspecialchars($event['venue']); ?>" required>
                        <div class="invalid-feedback">Please provide a venue.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="lat" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="lat" name="lat" 
                                   value="<?php echo htmlspecialchars($event['venue_lat']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lng" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="lng" name="lng" 
                                   value="<?php echo htmlspecialchars($event['venue_lng']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Seats</label>
                        <div class="p-3 bg-light rounded">
                            <?php foreach ($seats as $seat): ?>
                                <span class="badge bg-<?php echo $seat['status'] === 'available' ? 'success' : 'danger'; ?> me-1 mb-1">
                                    <?php echo htmlspecialchars($seat['seat_number']); ?>
                                    (<?php echo ucfirst($seat['status']); ?>)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_seat_numbers" class="form-label">Add More Seats (comma separated)</label>
                        <textarea class="form-control" id="new_seat_numbers" name="new_seat_numbers" rows="3"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Update Event</button>
                        <a href="/admin_panel.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Geocode venue address
    const venueInput = document.getElementById('venue');
    if (venueInput) {
        venueInput.addEventListener('change', function() {
            // In a real implementation, this would call Google Maps Geocoding API
            console.log('Geocoding address:', this.value);
            // Simulate geocoding response
            document.getElementById('lat').value = '27.7172';
            document.getElementById('lng').value = '85.3240';
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>