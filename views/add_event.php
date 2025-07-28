<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in and is admin
    session_start();
    if (!isset($_SESSION['user']['user_id']) || $_SESSION['user']['role'] !== 'admin') {
        header("Location: login.php");
        exit;
    }

    // Safe checks for all fields
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $venue = $_POST['venue'] ?? '';
    $category = $_POST['category'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $price = $_POST['price'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $registration_deadline = $_POST['registration_deadline'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate required fields
    if (empty($title) || empty($description) || empty($date) || empty($time) || empty($venue) || empty($category) || empty($capacity) || empty($price) || empty($contact_email) || empty($registration_deadline)) {
        $error_message = 'All fields are required!';
    }

    // Handle image upload and file size check
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['image']['size'] > 10 * 1024 * 1024) { // 10MB limit
            $error_message = 'Image file is too large. Maximum allowed size is 10MB.';
        } else {
            $imageTmp = $_FILES['image']['tmp_name'];
            $imageName = uniqid('event_', true) . '_' . $_FILES['image']['name'];
            $imagePath = 'uploads/' . $imageName;
            move_uploaded_file($imageTmp, __DIR__ . '/../uploads/' . $imageName);
        }
    }

    if (!$error_message) {
        $db = new Database();
        $conn = $db->getConnection();

        // Insert the event
        $stmt = $conn->prepare("INSERT INTO events (title, description, date, time, venue, category, capacity, price, image, contact_email, registration_deadline, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $date, $time, $venue, $category, $capacity, $price, $imagePath, $contact_email, $registration_deadline, $notes, $_SESSION['user']['user_id']]);
        
        $eventId = $conn->lastInsertId();
        
        // Send notifications to all attendees about the new event
        if ($eventId) {
            try {
                $notificationService = new NotificationService($conn);
                $result = $notificationService->notifyNewEvent($eventId, $title, $_SESSION['user']['user_id']);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = "Event created successfully! Notifications sent to " . $result['notifications_sent'] . " attendees.";
                } else {
                    $_SESSION['success_message'] = "Event created successfully! (Notification error: " . $result['message'] . ")";
                }
            } catch (Exception $e) {
                $_SESSION['success_message'] = "Event created successfully! (Notification error: " . $e->getMessage() . ")";
            }
        } else {
            $_SESSION['success_message'] = "Event created successfully!";
        }

        // Redirect to dashboard after adding event
        header("Location: admin_dashboard.php?event_added=1");
        exit;
    }
}
?>

<?php if ($error_message): ?>
    <div style="color: red; font-weight: bold; padding: 10px; background: #fff0f0; border: 1px solid #e53935; border-radius: 6px; margin: 20px;">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?> 