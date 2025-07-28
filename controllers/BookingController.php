<?php
session_start();
require_once __DIR__ . '/../Config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Ensure database tables exist
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create bookings table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            ticket_type VARCHAR(10) DEFAULT 'GEN',
            quantity INT DEFAULT 1,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
            booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_event_id (event_id)
        )
    ");
    
    // Check if ticket_type column exists, if not add it
    try {
        $pdo->query("SELECT ticket_type FROM bookings LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE bookings ADD COLUMN ticket_type VARCHAR(10) DEFAULT 'GEN' AFTER event_id");
        error_log("Added ticket_type column to bookings table");
    }
    
    // Check if quantity column exists, if not add it
    try {
        $pdo->query("SELECT quantity FROM bookings LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE bookings ADD COLUMN quantity INT DEFAULT 1 AFTER ticket_type");
        error_log("Added quantity column to bookings table");
    }
    
    // Check if total_amount column exists, if not add it
    try {
        $pdo->query("SELECT total_amount FROM bookings LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE bookings ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity");
        error_log("Added total_amount column to bookings table");
    }
    
    // Check if status column exists, if not add it
    try {
        $pdo->query("SELECT status FROM bookings LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE bookings ADD COLUMN status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending' AFTER total_amount");
        error_log("Added status column to bookings table");
    }
    
    // Check if booking_date column exists, if not add it
    try {
        $pdo->query("SELECT booking_date FROM bookings LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE bookings ADD COLUMN booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status");
        error_log("Added booking_date column to bookings table");
    }
    
    // Check if updated_at column exists, if not add it
    try {
        $pdo->query("SELECT updated_at FROM bookings LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE bookings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER booking_date");
        error_log("Added updated_at column to bookings table");
    }
    
    // Create notifications table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT,
            event_title VARCHAR(255),
            user_name VARCHAR(255),
            message TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_booking_id (booking_id),
            INDEX idx_status (status)
        )
    ");
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database setup failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'createBooking') {
        try {
            // Get form data
            $eventId = intval($_POST['event_id'] ?? 0);
            $ticketType = $_POST['ticket_type'] ?? 'GEN';
            $ticketQty = intval($_POST['ticket_qty'] ?? 1);
            $userId = $_SESSION['user']['user_id'];
            $userName = $_SESSION['user']['name'] ?? 'Unknown User';
            
            // Debug logging
            error_log("Received booking data: event_id=$eventId, ticket_type=$ticketType, ticket_qty=$ticketQty, user_id=$userId");
            
            // Validate inputs
            if (!$eventId || $eventId <= 0) {
                // Try to get the first available event if no event ID provided
                $stmt = $pdo->query("SELECT id, title, price FROM events LIMIT 1");
                $defaultEvent = $stmt->fetch();
                
                if ($defaultEvent) {
                    $eventId = $defaultEvent['id'];
                    error_log("Using default event ID: $eventId");
                } else {
                    throw new Exception('No events available for booking');
                }
            }
            
            if ($ticketQty < 1 || $ticketQty > 10) {
                throw new Exception('Invalid ticket quantity (must be between 1 and 10)');
            }
            
            // Create database connection
            $db = new Database();
            $pdo = $db->getConnection();
            
            // Get event details
            $stmt = $pdo->prepare("SELECT title, price FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            if (!$event) {
                throw new Exception('Event not found with ID: ' . $eventId);
            }
            
            // Calculate total amount
            $totalAmount = $event['price'] * $ticketQty;
            
            // Create booking record
            $stmt = $pdo->prepare("
                INSERT INTO bookings (user_id, event_id, ticket_type, quantity, total_amount, status, booking_date) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $userId,
                $eventId,
                $ticketType,
                $ticketQty,
                $totalAmount
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create booking record: ' . implode(', ', $stmt->errorInfo()));
            }
            
            $bookingId = $pdo->lastInsertId();
            
            if (!$bookingId) {
                throw new Exception('Failed to get booking ID after insert');
            }
            
            // Create notification for admin using the new notification service
            try {
                require_once __DIR__ . '/NotificationController.php';
                $notificationService = new NotificationService($pdo);
                $notificationService->notifyNewBooking($bookingId, $event['title'], $userName, $userId, $eventId);
            } catch (Exception $e) {
                error_log("Failed to create notification: " . $e->getMessage());
                // Don't fail the booking if notification fails
            }
            
            // Return success response
            echo json_encode([
                'success' => true,
                'booking_id' => $bookingId,
                'event_title' => $event['title'],
                'user_name' => $userName,
                'message' => 'Booking created successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Booking error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    } elseif ($action === 'cancelBooking') {
        try {
            $bookingId = intval($_POST['booking_id'] ?? 0);
            
            if (!$bookingId || $bookingId <= 0) {
                throw new Exception('Invalid booking ID');
            }
            
            // Verify the booking belongs to the current user
            $stmt = $pdo->prepare("SELECT b.*, e.title FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.id = ? AND b.user_id = ?");
            $stmt->execute([$bookingId, $userId]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                throw new Exception('Booking not found or you do not have permission to cancel it');
            }
            
            // Check if the event is in the future
            $eventDate = new DateTime($booking['date']);
            $today = new DateTime();
            
            if ($eventDate <= $today) {
                throw new Exception('Cannot cancel booking for past events');
            }
            
            // Update booking status to cancelled
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$bookingId]);
            
            if (!$result) {
                throw new Exception('Failed to cancel booking');
            }
            
            // Create notification for admin
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (booking_id, event_title, user_name, message, status, is_read, type, title) 
                    VALUES (?, ?, ?, ?, 'pending', 0, 'booking_cancelled', ?)
                ");
                
                $notificationMessage = "Booking cancelled for {$booking['title']} by {$userName}.";
                $stmt->execute([
                    $bookingId,
                    $booking['title'],
                    $userName,
                    $notificationMessage,
                    "Booking Cancelled: {$booking['title']}"
                ]);
            } catch (Exception $e) {
                error_log("Failed to create cancellation notification: " . $e->getMessage());
                // Don't fail the cancellation if notification fails
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Booking cancellation error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>