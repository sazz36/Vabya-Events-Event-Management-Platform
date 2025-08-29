<?php
require_once 'BaseModel.php';

class Booking extends BaseModel {
    public function __construct() {
        parent::__construct('bookings');
    }

    public function create($userId, $eventId, $seatId) {
        $sql = "INSERT INTO bookings (user_id, event_id, seat_id) 
                VALUES (:user_id, :event_id, :seat_id)";
        $params = [
            ':user_id' => $userId,
            ':event_id' => $eventId,
            ':seat_id' => $seatId
        ];
        $this->executeQuery($sql, $params);
        return $this->db->lastInsertId();
    }

    public function findByUser($userId) {
        $sql = "SELECT b.*, e.title as event_title, e.date as event_date, 
                e.time as event_time, e.venue as event_venue, s.seat_number
                FROM bookings b
                JOIN events e ON b.event_id = e.event_id
                JOIN seats s ON b.seat_id = s.seat_id
                WHERE b.user_id = :user_id
                ORDER BY b.booking_date DESC";
        $stmt = $this->executeQuery($sql, [':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findByEvent($eventId) {
        $sql = "SELECT b.*, u.name as attendee_name, s.seat_number
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                JOIN seats s ON b.seat_id = s.seat_id
                WHERE b.event_id = :event_id
                ORDER BY b.booking_date DESC";
        $stmt = $this->executeQuery($sql, [':event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function updatePaymentStatus($bookingId, $status, $method = null, $reference = null) {
        $sql = "UPDATE bookings SET 
                payment_status = :status,
                payment_method = :method,
                payment_reference = :reference
                WHERE booking_id = :booking_id";
        $params = [
            ':status' => $status,
            ':method' => $method,
            ':reference' => $reference,
            ':booking_id' => $bookingId
        ];
        return $this->executeQuery($sql, $params)->rowCount();
    }

    public function cancel($bookingId) {
        $this->db->beginTransaction();
        try {
            // Get seat_id from booking
            $sql = "SELECT seat_id FROM bookings WHERE booking_id = :booking_id";
            $stmt = $this->executeQuery($sql, [':booking_id' => $bookingId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            // Update booking status
            $this->updatePaymentStatus($bookingId, 'cancelled');
            
            // Free up the seat
            $seatModel = new Seat();
            $seatModel->updateStatus($booking['seat_id'], 'available');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}