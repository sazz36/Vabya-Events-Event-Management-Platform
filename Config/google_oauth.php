<?php
// Google OAuth Configuration
// Secrets are loaded from environment variables. Keep real values in .env (not committed).
//
// IMPORTANT: Ensure redirect_uri matches exactly what's configured in Google Cloud Console.
// To find the correct URI, visit: http://localhost/Event_sphere/Event_sphere/Event_sphere/views/test-path.php

// Attempt to load environment variables from project root using vlucas/phpdotenv if available.
// This is safe if the dependency is not installed; it will simply skip loading.
try {
    if (class_exists(\Dotenv\Dotenv::class)) {
        $projectRoot = dirname(__DIR__);
        $dotenv = \Dotenv\Dotenv::createImmutable($projectRoot);
        $dotenv->safeLoad();
    }
} catch (\Throwable $e) {
    // Silently ignore env loading errors to avoid breaking runtime.
}

// Resolve values from env (prefer $_ENV then getenv) with empty-string defaults.
$clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?: '';
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?: '';
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/Event_sphere/Event_sphere/Event_sphere/views/google-callback.php';

return [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
];