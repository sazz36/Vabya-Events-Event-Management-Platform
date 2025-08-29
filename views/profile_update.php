<?php
require_once __DIR__ . '/../controllers/AuthController.php';
header('Content-Type: application/json');
global $profile_update_success;
$profile_update_success = false;
$controller = new AuthController();
ob_start();
$controller->updateProfile();
$output = ob_get_clean();
if ($profile_update_success && isset($_SESSION['success_message'])) {
    echo json_encode(['success' => true, 'message' => $_SESSION['success_message']]);
    unset($_SESSION['success_message']);
    exit;
}
// Try to get error message from session or output
$error = null;
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
} elseif (preg_match('/<div class=\"alert alert-danger\">(.*?)<\/div>/', $output, $m)) {
    $error = strip_tags($m[1]);
}
echo json_encode(['success' => false, 'message' => $error ?: 'Profile update failed.']);
exit; 