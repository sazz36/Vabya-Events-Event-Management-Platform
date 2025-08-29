<?php
$pageTitle = "Complete Payment";
ob_start();

// Avoid undefined variable errors
$event = $event ?? [];
$seat = $seat ?? [];
$booking = $booking ?? [];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">Complete Your Payment</h2>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Booking Summary</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Event:</strong> <?php echo htmlspecialchars($event['title'] ?? 'N/A'); ?></p>
                                <p><strong>Date:</strong> <?php echo isset($event['date']) ? date('F j, Y', strtotime($event['date'])) : 'N/A'; ?></p>
                                <p><strong>Time:</strong> <?php echo isset($event['time']) ? date('g:i A', strtotime($event['time'])) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue'] ?? 'N/A'); ?></p>
                                <p><strong>Seat Number:</strong> <?php echo htmlspecialchars($seat['seat_number'] ?? 'N/A'); ?></p>
                                <p><strong>Amount Due:</strong> Rs <?php echo isset($event['price']) ? number_format($event['price'], 2) : '0.00'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form action="/process_payment.php?id=<?php echo htmlspecialchars($booking['booking_id'] ?? ''); ?>" method="POST">
                    <div class="mb-4">
                        <h4 class="mb-3">Select Payment Method</h4>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="esewa" value="esewa" checked>
                            <label class="form-check-label" for="esewa">
                                <img src="/assets/images/esewa-logo.png" alt="eSewa" style="height: 24px; vertical-align: middle;">
                                eSewa
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="khalti" value="khalti">
                            <label class="form-check-label" for="khalti">
                                <img src="/assets/images/khalti-logo.png" alt="Khalti" style="height: 24px; vertical-align: middle;">
                                Khalti
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="creditCard" value="credit_card">
                            <label class="form-check-label" for="creditCard">
                                <i class="bi bi-credit-card"></i> Credit/Debit Card
                            </label>
                        </div>
                    </div>
                    
                    <div id="creditCardDetails" class="mb-4" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cardNumber" class="form-label">Card Number</label>
                                <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="expiryDate" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" placeholder="123">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cardName" class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="cardName" placeholder="John Doe">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="paymentReference" class="form-label">Payment Reference (Transaction ID)</label>
                        <input type="text" class="form-control" id="paymentReference" name="payment_reference" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-credit-card"></i> Complete Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const creditCardDetails = document.getElementById('creditCardDetails');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            creditCardDetails.style.display = this.value === 'credit_card' ? 'block' : 'none';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
