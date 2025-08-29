<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            $role = isset($_POST['role']) && $_POST['role'] === 'admin' ? 'admin' : 'attendee';

            // Validate inputs
            $errors = [];
            if (empty($name)) $errors['name'] = 'Name is required';
            if (empty($email)) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif ($this->userModel->findByEmail($email)) {
                $errors['email'] = 'Email already registered';
            }
            if (empty($password)) $errors['password'] = 'Password is required';
            if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match';

            if (empty($errors)) {
                $userId = $this->userModel->create($name, $email, $password, $role);
                $_SESSION['user'] = [
                    'user_id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role
                ];
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_name'] = $name;
                
                // Set a cookie for dark mode preference
                setcookie('dark_mode', 'false', time() + (86400 * 30), "/");
                
                // Debug output to check if redirect is reached
                // Remove this after confirming redirect works
                // echo 'Registration successful, redirecting...';
                header("Location: dashboard.php");
                exit();
            }
        }
        
        require_once '../views/register.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            $errors = [];
            if (empty($email)) $errors['email'] = 'Email is required';
            if (empty($password)) $errors['password'] = 'Password is required';

            if (empty($errors)) {
                $user = $this->userModel->verifyPassword($email, $password);
                if ($user) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    // Set user session as array for dashboard compatibility
                    $_SESSION['user'] = [
                        'user_id' => $user['user_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                    // Also set old keys for backward compatibility
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];

                    // On successful login:
                    if (isset($_POST['remember_me']) && $_POST['remember_me'] == 'on') {
                        $token = bin2hex(random_bytes(32));
                        // Store $token in users table for this user (add 'remember_token' column if not present)
                        setcookie('rememberme', $token, time() + 60*60*24*30, '/', '', false, true);
                        // Example DB update:
                        // $stmt = $conn->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
                        // $stmt->execute([$token, $user_id]);
                    }
                    // Debug output to check if redirect is reached
                    // Remove this after confirming redirect works
                    // echo 'Login successful, redirecting...';
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin_panel.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $errors['login'] = 'Invalid email or password';
                }
            }
        }
        
        require_once '../views/login.php';
    }

    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // On logout:
        // setcookie('rememberme', '', time() - 3600, '/');
        // $stmt = $conn->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
        // $stmt->execute([$user_id]);
        
        // Redirect to home page
        header("Location: /");
        exit();
    }

    public function updateProfile() {
        global $profile_update_success;
        $profile_update_success = false;
        if (!isset($_SESSION['user']['user_id'])) {
            $_SESSION['error_message'] = 'Not logged in.';
            header('Location: login.php');
            exit();
        }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            if (empty($name)) $errors['name'] = 'Name is required';
            if (empty($email)) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
            if (empty($errors)) {
                $userId = $_SESSION['user']['user_id'];
                $rows = $this->userModel->updateProfile($userId, $name, $email);
                if ($rows > 0) {
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['success_message'] = 'Profile updated successfully!';
                    $profile_update_success = true;
                } else {
                    $_SESSION['error_message'] = 'No changes made or error updating profile.';
                }
            } else {
                $_SESSION['error_message'] = implode(' ', $errors);
            }
        }
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            require_once '../views/dashboard_profile.php';
        }
    }
}