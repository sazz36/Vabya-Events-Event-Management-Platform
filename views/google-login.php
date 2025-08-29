<?php
require_once __DIR__ . '/../vendor/autoload.php';

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

$client->addScope('email');
$client->addScope('profile');

$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit; 

