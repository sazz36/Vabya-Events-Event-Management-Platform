<?php
session_start();
require_once __DIR__ . '/../Config/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    switch ($action) {
        case 'update':
            updateEvent($pdo);
            break;
            
        case 'delete':
            deleteEvent($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

function updateEvent($pdo) {
    $event_id = $_POST['event_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $venue = $_POST['venue'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? '';

    // Only require essential fields
    if (empty($event_id) || empty($title) || empty($date) || empty($venue) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Required fields: title, date, venue, category']);
        return;
    }

    if (!is_numeric($price) || $price < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid price']);
        return;
    }

    try {
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/';
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
                return;
            }
            $filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                return;
            }
        }

        if ($image_path) {
            $stmt = $pdo->prepare("
                UPDATE events
                SET title = ?, description = ?, date = ?, time = ?, venue = ?, category = ?, price = ?, image = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $date, $time, $venue, $category, $price, $image_path, $event_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE events
                SET title = ?, description = ?, date = ?, time = ?, venue = ?, category = ?, price = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $date, $time, $venue, $category, $price, $event_id]);
        }

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or event not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating event: ' . $e->getMessage()]);
    }
}

function deleteEvent($pdo) {
    $event_id = $_POST['event_id'] ?? '';
    if (empty($event_id)) {
        echo json_encode(['success' => false, 'message' => 'Event ID is required']);
        return;
    }
    try {
        // Check for bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $booking_count = $stmt->fetchColumn();
        if ($booking_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete event with existing bookings. Please cancel all bookings first.']);
            return;
        }
        // Delete event and image
        $stmt = $pdo->prepare("SELECT image FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        if ($stmt->rowCount() > 0) {
            if ($event && $event['image']) {
                $image_path = __DIR__ . '/../uploads/' . $event['image'];
                if (file_exists($image_path)) unlink($image_path);
            }
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting event: ' . $e->getMessage()]);
    }
}
?> 