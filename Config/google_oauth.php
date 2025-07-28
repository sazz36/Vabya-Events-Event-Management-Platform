<?php
// Google OAuth Configuration
// This file should be added to .gitignore to keep credentials secure

r<?php
<?php
return [
    'client_id' => getenv('GOOGLE_CLIENT_ID'),
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI'),
];