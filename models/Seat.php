<?php
require_once 'BaseModel.php';

class Seat extends BaseModel {
    public function __construct() {
        parent::__construct('seats');
    }

    public function createForEvent($eventId, $seatNumbers) {
        $this->db->beginTransaction();
        try {
            foreach ($seatNumbers as $seatNumber) {
                $sql = "INSERT INTO seats (event_id, seat_number) VALUES (:event_id, :seat_number)";
                $this->executeQuery($sql, [
                    ':event_id' => $eventId,
                    ':seat_number' => $seatNumber
                ]);
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findByEvent($eventId) {
        $sql = "SELECT * FROM seats WHERE event_id = :event_id ORDER BY seat_number ASC";
        $stmt = $this->executeQuery($sql, [':event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function findAvailableSeats($eventId) {
        $sql = "SELECT * FROM seats 
                WHERE event_id = :event_id AND status = 'available' 
                ORDER BY seat_number ASC";
        $stmt = $this->executeQuery($sql, [':event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function updateStatus($seatId, $status) {
        $sql = "UPDATE seats SET status = :status WHERE seat_id = :seat_id";
        return $this->executeQuery($sql, [
            ':status' => $status,
            ':seat_id' => $seatId
        ])->rowCount();
    }
}