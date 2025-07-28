<?php
require_once 'BaseModel.php';

class Event extends BaseModel {
    public function __construct() {
        parent::__construct('events');
    }

    public function create($title, $description, $date, $time, $venue, $lat, $lng, $price, $createdBy) {
        $sql = "INSERT INTO events (title, description, date, time, venue, venue_lat, venue_lng, price, created_by) 
                VALUES (:title, :description, :date, :time, :venue, :lat, :lng, :price, :created_by)";
        $params = [
            ':title' => $title,
            ':description' => $description,
            ':date' => $date,
            ':time' => $time,
            ':venue' => $venue,
            ':lat' => $lat,
            ':lng' => $lng,
            ':price' => $price,
            ':created_by' => $createdBy
        ];
        $this->executeQuery($sql, $params);
        return $this->db->lastInsertId();
    }

    public function update($eventId, $title, $description, $date, $time, $venue, $lat, $lng, $price) {
        $sql = "UPDATE events SET 
                title = :title, 
                description = :description, 
                date = :date, 
                time = :time, 
                venue = :venue, 
                venue_lat = :lat, 
                venue_lng = :lng, 
                price = :price 
                WHERE event_id = :event_id";
        $params = [
            ':title' => $title,
            ':description' => $description,
            ':date' => $date,
            ':time' => $time,
            ':venue' => $venue,
            ':lat' => $lat,
            ':lng' => $lng,
            ':price' => $price,
            ':event_id' => $eventId
        ];
        return $this->executeQuery($sql, $params)->rowCount();
    }

    public function findUpcoming() {
        $sql = "SELECT e.*, u.name as organizer_name 
                FROM events e 
                JOIN users u ON e.created_by = u.user_id 
                WHERE e.date >= CURDATE() 
                ORDER BY e.date ASC, e.time ASC";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll();
    }

    public function findByIdWithOrganizer($eventId) {
        $sql = "SELECT e.*, u.name as organizer_name 
                FROM events e 
                JOIN users u ON e.created_by = u.user_id 
                WHERE e.event_id = :event_id";
        $stmt = $this->executeQuery($sql, [':event_id' => $eventId]);
        return $stmt->fetch();
    }

    public function findByOrganizer($organizerId) {
        $sql = "SELECT * FROM events WHERE created_by = :organizer_id ORDER BY date DESC";
        $stmt = $this->executeQuery($sql, [':organizer_id' => $organizerId]);
        return $stmt->fetchAll();
    }
}