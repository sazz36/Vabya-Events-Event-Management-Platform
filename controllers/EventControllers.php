<?php
require_once '../models/Event.php';
require_once '../models/Seat.php';
require_once '../controllers/NotificationController.php';

class EventController {
    private $eventModel;
    private $seatModel;

    public function __construct() {
        $this->eventModel = new Event();
        $this->seatModel = new Seat();
    }

    public function createEvent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: /login.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $date = $_POST['date'];
            $time = $_POST['time'];
            $venue = trim($_POST['venue']);
            $lat = $_POST['lat'];
            $lng = $_POST['lng'];
            $price = $_POST['price'];
            $seatNumbers = explode(',', $_POST['seat_numbers']);
            $seatNumbers = array_map('trim', $seatNumbers);
            $seatNumbers = array_filter($seatNumbers);

            $errors = [];
            if (empty($title)) $errors['title'] = 'Event title is required';
            if (empty($description)) $errors['description'] = 'Description is required';
            if (empty($date)) $errors['date'] = 'Date is required';
            if (empty($time)) $errors['time'] = 'Time is required';
            if (empty($venue)) $errors['venue'] = 'Venue is required';
            if (!is_numeric($price) || $price < 0) $errors['price'] = 'Invalid price';
            if (empty($seatNumbers)) $errors['seat_numbers'] = 'At least one seat number is required';

            if (empty($errors)) {
                $this->eventModel->getConnection()->beginTransaction();
                try {
                    $eventId = $this->eventModel->create(
                        $title, $description, $date, $time, $venue, $lat, $lng, $price, $_SESSION['user_id']
                    );
                    
                    $this->seatModel->createForEvent($eventId, $seatNumbers);
                    
                    // Send notifications to all attendees about new event
                    $db = new Database();
                    $pdo = $db->getConnection();
                    $notificationService = new NotificationService($pdo);
                    $notificationService->notifyNewEvent($eventId, $title, $_SESSION['user_id']);
                    
                    $this->eventModel->getConnection()->commit();
                    
                    $_SESSION['success_message'] = 'Event created successfully! Notifications sent to all attendees.';
                    header("Location: /event_detail.php?id=$eventId");
                    exit();
                } catch (Exception $e) {
                    $this->eventModel->getConnection()->rollBack();
                    $errors['database'] = 'Error creating event: ' . $e->getMessage();
                }
            }
        }

        require_once '../views/admin/create_event.php';
    }

    public function listEvents() {
        $events = $this->eventModel->findUpcoming();
        require_once '../views/event_list.php';
    }

    public function showEvent($eventId) {
        $event = $this->eventModel->findByIdWithOrganizer($eventId);
        if (!$event) {
            header("Location: /404.php");
            exit();
        }

        $seats = $this->seatModel->findByEvent($eventId);
        require_once '../views/event_detail.php';
    }

    public function editEvent($eventId) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: /login.php");
            exit();
        }

        $event = $this->eventModel->findByIdWithOrganizer($eventId);
        if (!$event || $event['created_by'] != $_SESSION['user_id']) {
            header("Location: /403.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $date = $_POST['date'];
            $time = $_POST['time'];
            $venue = trim($_POST['venue']);
            $lat = $_POST['lat'];
            $lng = $_POST['lng'];
            $price = $_POST['price'];

            $errors = [];
            if (empty($title)) $errors['title'] = 'Event title is required';
            if (empty($description)) $errors['description'] = 'Description is required';
            if (empty($date)) $errors['date'] = 'Date is required';
            if (empty($time)) $errors['time'] = 'Time is required';
            if (empty($venue)) $errors['venue'] = 'Venue is required';
            if (!is_numeric($price) || $price < 0) $errors['price'] = 'Invalid price';

            if (empty($errors)) {
                $rowsAffected = $this->eventModel->update(
                    $eventId, $title, $description, $date, $time, $venue, $lat, $lng, $price
                );
                
                if ($rowsAffected > 0) {
                    $_SESSION['success_message'] = 'Event updated successfully!';
                    header("Location: /event_detail.php?id=$eventId");
                    exit();
                } else {
                    $errors['database'] = 'No changes made or error updating event';
                }
            }
        }

        $seats = $this->seatModel->findByEvent($eventId);
        require_once '../views/admin/edit_event.php';
    }

    public function deleteEvent($eventId) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: /login.php");
            exit();
        }

        $event = $this->eventModel->findByIdWithOrganizer($eventId);
        if (!$event || $event['created_by'] != $_SESSION['user_id']) {
            header("Location: /403.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->eventModel->delete($eventId);
                $_SESSION['success_message'] = 'Event deleted successfully!';
                header("Location: /admin_panel.php");
                exit();
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Error deleting event: ' . $e->getMessage();
                header("Location: /event_detail.php?id=$eventId");
                exit();
            }
        }

        require_once '../views/admin/confirm_delete.php';
    }

    public function getAvailableSeats($eventId) {
        header('Content-Type: application/json');
        $seats = $this->seatModel->findAvailableSeats($eventId);
        echo json_encode($seats);
        exit();
    }
}