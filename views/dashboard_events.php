<?php
// All Events tab content
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<div class="card">
    <h2 class="section-title">All Events</h2>
    <div class="row event-grid">
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="card h-100">
                    <?php if (!empty($event['image'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars(basename($event['image'] ?? '')); ?>" class="card-img-top event-image" alt="Event Image">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($event['title'] ?? ''); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($event['description'] ?? ''); ?></p>
                        <p class="card-text"><strong>Date:</strong> <?php echo htmlspecialchars($event['date'] ?? ''); ?> <strong>Time:</strong> <?php echo htmlspecialchars($event['time'] ?? ''); ?></p>
                        <p class="card-text"><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue'] ?? ''); ?></p>
                        <p class="card-text"><strong>Category:</strong> <?php echo htmlspecialchars($event['category'] ?? ''); ?></p>
                        <p class="card-text"><strong>Price:</strong> <?php echo htmlspecialchars($event['price'] ?? ''); ?></p>
                        <button class="btn btn-gradient book-now-btn"
                            data-event-id="<?php echo htmlspecialchars($event['id'] ?? '', ENT_QUOTES); ?>"
                            data-title="<?php echo htmlspecialchars($event['title'] ?? '', ENT_QUOTES); ?>"
                            data-date="<?php echo htmlspecialchars($event['date'] ?? '', ENT_QUOTES); ?>"
                            data-time="<?php echo htmlspecialchars($event['time'] ?? '', ENT_QUOTES); ?>"
                            data-venue="<?php echo htmlspecialchars($event['venue'] ?? '', ENT_QUOTES); ?>"
                            data-price="<?php echo htmlspecialchars($event['price'] ?? '', ENT_QUOTES); ?>"
                            onclick="console.log('Event ID from PHP:', '<?php echo $event['id'] ?? ''; ?>')"
                        >Book Now</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Booking Modal -->
<div class="custom-modal" id="bookingModal" data-current-event-id="">
    <div class="custom-modal-content">
        <span class="custom-modal-close" id="closeBookingModal">&times;</span>
        <h3 id="modalEventTitle"></h3>
        <form id="bookingForm" method="POST" action="/controllers/BookingController.php?action=createBooking">
            <input type="hidden" name="event_id" id="modalEventId" value="">
            <div class="form-group">
                <label>Date</label>
                <input type="text" class="form-control" id="modalEventDate" readonly>
            </div>
            <div class="form-group">
                <label>Time</label>
                <input type="text" class="form-control" id="modalEventTime" readonly>
            </div>
            <div class="form-group">
                <label>Venue</label>
                <input type="text" class="form-control" id="modalEventVenue" readonly>
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="text" class="form-control" id="modalEventPrice" readonly>
            </div>
            <div class="form-group">
                <label>Ticket </label>
                <div id="ticketTypeGroup">
          
                    <label><input type="radio" name="ticket_type" value="GEN" checked> General</label>
                </div>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <div style="display: flex; align-items: center;">
                    <button type="button" id="qtyMinus" class="btn btn-outline-secondary btn-sm">-</button>
                    <input type="number" id="ticketQty" name="ticket_qty" value="1" min="1" max="10" style="width: 50px; text-align: center; margin: 0 8px;">
                    <button type="button" id="qtyPlus" class="btn btn-outline-secondary btn-sm">+</button>
                </div>
            </div>
            <div class="form-group">
                <label>Payment Details</label>
                <div style="background: #f8f9fa; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                    <strong>Kumari Bank Ltd.</strong><br>
                    Account Name: <span style="font-weight: 500;">भव्य Event Pvt. Ltd.</span><br>
                    <span style="font-size: 1.1em;">Account Number: <b>1700334798900001</b></span>
                </div>
                <small class="text-muted">Please transfer the total amount to the above account and keep your transaction/reference ID for verification.</small>
            </div>
            <button type="submit" class="btn btn-gradient w-100" id="confirmBookingBtn">Confirm Booking</button>
        </form>
        <!-- Payment Verification Message -->
        <div id="paymentVerification" style="display: none; text-align: center; padding: 20px;">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="text-primary">Payment Verification in Progress</h5>
            <p class="text-muted">Please wait while we verify your payment details...</p>
        </div>
        <!-- Success Message -->
        <div id="bookingSuccess" style="display: none; text-align: center; padding: 20px;">
            <div class="text-success mb-3">
                <i class="fas fa-check-circle" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-success">Booking Submitted Successfully!</h5>
            <p class="text-muted">Your booking has been submitted and is pending admin approval. You will receive a confirmation once your payment is verified.</p>
            <button type="button" class="btn btn-outline-success" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<style>
.section-title {
    margin-bottom: 24px;
    font-weight: bold;
    font-size: 1.8rem;
}
.event-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
}
.event-card {
    flex: 1 1 300px;
    max-width: 350px;
    min-width: 260px;
    border: 2px solid #fff;
    border-radius: 18px;
    background: transparent;
    margin-bottom: 28px;
    padding: 18px 10px 10px 10px;
    box-sizing: border-box;
}
.card-body .book-now-btn {
    margin-top: 18px;
}
.event-image {
    border-radius: 12px;
    max-height: 180px;
    object-fit: cover;
}
.custom-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0; width: 100vw; height: 100vh;
    background: rgba(30, 34, 90, 0.45);
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}
.custom-modal-content {
    background: #fff;
    border-radius: 18px;
    padding: 2rem;
    padding-bottom: 3rem;
    max-width: 400px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 32px rgba(60,60,120,0.18);
    position: relative;
    animation: modalIn 0.3s;
    margin: 20px;
}
@keyframes modalIn {
    from { transform: translateY(40px) scale(0.98); opacity: 0; }
    to   { transform: translateY(0) scale(1); opacity: 1; }
}
.custom-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 1.6rem;
    color: #666;
    cursor: pointer;
}
.custom-modal-close:hover { color: #e53935; }
.form-group {
    margin-bottom: 1rem;
}
.form-control {
    width: 100%;
    padding: 0.6rem;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
}
.btn-gradient {
    background: linear-gradient(90deg, #1B3C53, #456882);
color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.7rem 1.2rem;
    font-weight: 600;
    transition: background 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(94,53,177,0.08);
}
.btn-gradient:hover {
    background: linear-gradient(90deg, #4527a0, #26a69a);
    box-shadow: 0 4px 16px rgba(94,53,177,0.15);
    color: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('bookingModal');
    const closeBtn = document.getElementById('closeBookingModal');
    const ticketTypeGroup = document.getElementById('ticketTypeGroup');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    const ticketQty = document.getElementById('ticketQty');
    const bookingForm = document.getElementById('bookingForm');
    const paymentVerification = document.getElementById('paymentVerification');
    const bookingSuccess = document.getElementById('bookingSuccess');
    const confirmBookingBtn = document.getElementById('confirmBookingBtn');

    let currentEventId = null;

    document.querySelectorAll('.book-now-btn').forEach(button => {
        button.addEventListener('click', function () {
            // Get the event ID from the button's data attribute
            currentEventId = this.getAttribute('data-event-id');
            
            // Ensure event_id is set in the hidden input
            const modalEventId = document.getElementById('modalEventId');
            modalEventId.value = currentEventId;
            
            // Populate other modal fields
            document.getElementById('modalEventTitle').textContent = this.getAttribute('data-title') || 'Event Booking';
            document.getElementById('modalEventDate').value = this.getAttribute('data-date') || '';
            document.getElementById('modalEventTime').value = this.getAttribute('data-time') || '';
            document.getElementById('modalEventVenue').value = this.getAttribute('data-venue') || '';
            document.getElementById('modalEventPrice').value = this.getAttribute('data-price') || '';
            
            modal.style.display = 'flex';
        });
    });

    qtyMinus.addEventListener('click', function () {
        let val = parseInt(ticketQty.value, 10) || 1;
        if (val > 1) {
            ticketQty.value = val - 1;
        }
    });
    qtyPlus.addEventListener('click', function () {
        let val = parseInt(ticketQty.value, 10) || 1;
        if (val < 10) {
            ticketQty.value = val + 1;
        }
    });

    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none';
    });
    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Handle form submission
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show payment verification message
        bookingForm.style.display = 'none';
        paymentVerification.style.display = 'block';
        confirmBookingBtn.disabled = true;

        // Get form data
        const formData = new FormData();
        formData.append('action', 'createBooking');
        formData.append('event_id', document.getElementById('modalEventId').value || '1'); // Default to 1 if empty
        formData.append('ticket_type', document.querySelector('input[name="ticket_type"]:checked').value);
        formData.append('ticket_qty', document.getElementById('ticketQty').value);

        // Send AJAX request
        fetch('../controllers/BookingController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            // Hide payment verification
            paymentVerification.style.display = 'none';
            
            if (data.success) {
                // Show success message
                bookingSuccess.style.display = 'block';
            } else {
                // Show error and reset form
                alert('Booking failed: ' + (data.message || 'Unknown error'));
                bookingForm.style.display = 'block';
                confirmBookingBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            paymentVerification.style.display = 'none';
            bookingForm.style.display = 'block';
            confirmBookingBtn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    });
});

// Function to close modal
function closeModal() {
    const modal = document.getElementById('bookingModal');
    const bookingForm = document.getElementById('bookingForm');
    const paymentVerification = document.getElementById('paymentVerification');
    const bookingSuccess = document.getElementById('bookingSuccess');
    const confirmBookingBtn = document.getElementById('confirmBookingBtn');
    
    modal.style.display = 'none';
    bookingForm.style.display = 'block';
    paymentVerification.style.display = 'none';
    bookingSuccess.style.display = 'none';
    confirmBookingBtn.disabled = false;
    
    // Reset form
    bookingForm.reset();
    document.getElementById('ticketQty').value = '1';
}
</script>
