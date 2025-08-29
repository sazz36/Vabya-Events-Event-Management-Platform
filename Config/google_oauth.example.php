<?php
// Google OAuth Configuration Example (env-based)
// Put real values in a .env file at the project root (not committed):
//   GOOGLE_CLIENT_ID="your-google-client-id"
//   GOOGLE_CLIENT_SECRET="your-google-client-secret"
//   GOOGLE_REDIRECT_URI="http://localhost/Event_sphere/Event_sphere/Event_sphere/views/google-callback.php"
// Config/google_oauth.php will read from env and return the array below.

return [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/Event_sphere/Event_sphere/Event_sphere/views/google-callback.php'
];