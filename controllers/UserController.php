<?php
session_start();
require_once __DIR__ . '/../Config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$action = $_POST['action'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    switch ($action) {
        case 'updateProfile':
            updateProfile($pdo, $user_id);
            break;
            
        case 'changePassword':
            changePassword($pdo, $user_id);
            break;
            
        case 'exportData':
            exportUserData($pdo, $user_id);
            break;
            
        case 'deleteAccount':
            deleteAccount($pdo, $user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

function updateProfile($pdo, $user_id) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Check if email already exists for another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        return;
    }
    
    // Update user profile
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$name, $email, $phone, $user_id])) {
        // Update session data
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
}

function changePassword($pdo, $user_id) {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    
    // Validation
    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        return;
    }
    
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
        return;
    }
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt->execute([$hashedPassword, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to change password']);
    }
}

function exportUserData($pdo, $user_id) {
    // Get user data
    $stmt = $pdo->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userData = $stmt->fetch();
    
    // Get booking history
    $stmt = $pdo->prepare("
        SELECT b.id, b.ticket_type, b.quantity, b.total_amount, b.status, b.booking_date,
               e.title, e.date, e.time, e.venue, e.price
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
    
    // Prepare export data
    $exportData = [
        'user_info' => [
            'id' => $userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'role' => $userData['role'],
            'account_created' => $userData['created_at']
        ],
        'booking_history' => $bookings,
        'export_date' => date('Y-m-d H:i:s'),
        'total_bookings' => count($bookings)
    ];
    
    // Set headers for file download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="my_event_data.json"');
    header('Content-Length: ' . strlen(json_encode($exportData)));
    
    echo json_encode($exportData, JSON_PRETTY_PRINT);
}

function deleteAccount($pdo, $user_id) {
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete user's bookings
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete user account
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Destroy session
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to delete account: ' . $e->getMessage()]);
    }
}
?> 