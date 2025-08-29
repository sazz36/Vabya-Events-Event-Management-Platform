<?php
require_once __DIR__ . '/../models/Seat.php';

$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$seatModel = new Seat();
$seats = $seatModel->findByEvent($eventId);

// Group seats by type for layout
$vip = [];
$fanpit = [];
$general = [];
foreach ($seats as $seat) {
    if (strpos($seat['seat_number'], 'VIP') === 0) $vip[] = $seat;
    elseif (strpos($seat['seat_number'], 'FAN') === 0) $fanpit[] = $seat;
    else $general[] = $seat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Seat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .seat-row { display: flex; justify-content: center; margin-bottom: 10px; }
        .seat-btn { width: 48px; height: 48px; margin: 0 6px; border-radius: 8px; font-weight: 600; border: 2px solid #e0e0e0; transition: 0.2s; }
        .vip { background: linear-gradient(90deg, #ffd700, #ffec8b); border-color: #ffd700; }
        .fanpit { background: linear-gradient(90deg, #67e8f9, #38bdf8); border-color: #38bdf8; }
        .general { background: linear-gradient(90deg, #e0e7ff, #a5b4fc); border-color: #a5b4fc; }
        .booked { background: #ccc !important; color: #fff !important; cursor: not-allowed; border-color: #aaa; }
        .selected { box-shadow: 0 0 0 3px #6366f1; border-color: #6366f1; }
        .legend { margin-top: 30px; }
        .legend span { display: inline-block; margin-right: 20px; font-size: 1rem; }
        .legend .seat-btn { margin-right: 8px; vertical-align: middle; }
        .booking-status { margin-top: 25px; min-height: 30px; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center mt-4 mb-2">Select Your Seat</h2>
    <form id="seatForm">
        <div class="seat-row">
            <?php foreach ($vip as $seat): ?>
                <button type="button" class="seat-btn vip<?php if ($seat['status'] !== 'available') echo ' booked'; ?>" data-seat-id="<?= $seat['seat_id'] ?>" <?php if ($seat['status'] !== 'available') echo 'disabled'; ?>><?= htmlspecialchars($seat['seat_number']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="seat-row">
            <?php foreach ($fanpit as $seat): ?>
                <button type="button" class="seat-btn fanpit<?php if ($seat['status'] !== 'available') echo ' booked'; ?>" data-seat-id="<?= $seat['seat_id'] ?>" <?php if ($seat['status'] !== 'available') echo 'disabled'; ?>><?= htmlspecialchars($seat['seat_number']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="seat-row">
            <?php foreach ($general as $seat): ?>
                <button type="button" class="seat-btn general<?php if ($seat['status'] !== 'available') echo ' booked'; ?>" data-seat-id="<?= $seat['seat_id'] ?>" <?php if ($seat['status'] !== 'available') echo 'disabled'; ?>><?= htmlspecialchars($seat['seat_number']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="legend">
            <span><span class="seat-btn vip"></span> VIP</span>
            <span><span class="seat-btn fanpit"></span> Fanpit</span>
            <span><span class="seat-btn general"></span> General</span>
        </div>
        <input type="hidden" name="seat_id" id="selectedSeatId">
        <button type="submit" class="btn btn-primary mt-4" id="bookBtn" disabled>Book Selected Seat</button>
    </form>
    <div class="booking-status text-center fw-bold" id="bookingStatus"></div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    let selectedBtn = null;
    $('.seat-btn').not('.booked').on('click', function() {
        if ($(this).hasClass('booked')) return;
        $('.seat-btn').removeClass('selected');
        $(this).addClass('selected');
        $('#selectedSeatId').val($(this).data('seat-id'));
        $('#bookBtn').prop('disabled', false);
        selectedBtn = $(this);
    });
    $('#seatForm').on('submit', function(e) {
        e.preventDefault();
        var seatId = $('#selectedSeatId').val();
        if (!seatId) return;
        $('#bookBtn').prop('disabled', true);
        $('#bookingStatus').text('Booking...').css('color', '#333');
        $.ajax({
            url: 'ajax_book_seat.php',
            method: 'POST',
            data: { seat_id: seatId, event_id: <?= $eventId ?> },
            success: function(response) {
                if (response === 'success') {
                    selectedBtn.addClass('booked').removeClass('selected');
                    selectedBtn.prop('disabled', true);
                    $('#bookingStatus').text('Seat booked successfully!').css('color', '#16a34a');
                } else {
                    $('#bookingStatus').text('Booking failed: ' + response).css('color', '#dc2626');
                }
            },
            error: function() {
                $('#bookingStatus').text('Booking failed. Please try again.').css('color', '#dc2626');
            },
            complete: function() {
                $('#bookBtn').prop('disabled', false);
            }
        });
    });
});
</script>
</body>
</html> 