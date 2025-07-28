<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load Google OAuth configuration
$config_file = __DIR__ . '/../config/google_oauth.php';
if (!file_exists($config_file)) {
    die('Google OAuth configuration file not found. Please copy config/google_oauth.example.php to config/google_oauth.php and configure your credentials.');
}

$google_config = require $config_file;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$client = new Google_Client();
$client->setClientId($google_config['client_id']);
$client->setClientSecret($google_config['client_secret']);
$client->setRedirectUri($google_config['redirect_uri']);
// ...existing code...
$client_id = getenv("GOOGLE_CLIENT_ID");
$client_secret = getenv("GOOGLE_CLIENT_SECRET");
// ...existing code...$client->addScope('email');
$client->addScope('profile');

$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit; 