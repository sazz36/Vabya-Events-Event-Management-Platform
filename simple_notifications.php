<?php
// Simple test version of notifications page
echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Simple Notifications Test</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
echo '.header { background: #f0f0f0; padding: 20px; border-radius: 8px; margin-bottom: 20px; }';
echo '.notification { background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<div class="header">';
echo '<h1>Simple Notifications Test</h1>';
echo '<p>If you can see this page properly formatted, PHP is working!</p>';
echo '</div>';

echo '<div class="notification">';
echo '<h3>Test Notification 1</h3>';
echo '<p>This is a test notification to verify the page is working.</p>';
echo '<small>Created: ' . date('Y-m-d H:i:s') . '</small>';
echo '</div>';

echo '<div class="notification">';
echo '<h3>Test Notification 2</h3>';
echo '<p>Another test notification to check formatting.</p>';
echo '<small>Created: ' . date('Y-m-d H:i:s') . '</small>';
echo '</div>';

echo '<p><a href="test.php">Go to PHP Test</a></p>';
echo '<p><a href="notifications.php">Go to Full Notifications Page</a></p>';

echo '</body>';
echo '</html>';
?> 