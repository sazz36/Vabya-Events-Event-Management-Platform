<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Config/db.php';
session_start();

// Load Google OAuth configuration
$config_file = __DIR__ . '/../Config/google_oauth.php';
if (!file_exists($config_file)) {
    die('Google OAuth configuration file not found. Please copy Config/google_oauth.example.php to Config/google_oauth.php and configure your credentials.');
}

$google_config = require $config_file;

$client = new Google_Client();
$client->setClientId($google_config['client_id']);
$client->setClientSecret($google_config['client_secret']);
$client->setRedirectUri($google_config['redirect_uri']);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $db = new Database();
    $conn = $db->getConnection();

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$userInfo->email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user'] = [
            'user_id' => $user['id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => $user['role']
        ];
    } else {
        // Try to insert the user
        try {
            $stmt = $conn->prepare("INSERT INTO users (name, email, role) VALUES (?, ?, ?)");
            $stmt->execute([$userInfo->name, $userInfo->email, 'attendee']);
            $user_id = $conn->lastInsertId();
            $_SESSION['user'] = [
                'user_id' => $user_id,
                'name'    => $userInfo->name,
                'email'   => $userInfo->email,
                'role'    => 'attendee'
            ];
        } catch (PDOException $e) {
            echo "DB Error: " . $e->getMessage();
            exit;
        }
    }

    // Debug: Show session before redirect
    // echo '<pre>'; print_r($_SESSION); echo '</pre>'; exit;

    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php?error=google');
    exit;
} 