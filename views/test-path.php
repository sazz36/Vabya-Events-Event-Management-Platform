<?php
// Test file to determine the correct URL path
echo "<h2>URL Path Test</h2>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>HTTP Host:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Full URL:</strong> http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";

// Calculate the correct redirect URI
$base_url = "http://" . $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$redirect_uri = $base_url . $script_path . "/google-callback.php";

echo "<p><strong>Calculated Redirect URI:</strong> " . $redirect_uri . "</p>";
echo "<p><strong>Recommended Redirect URI for Google Console:</strong></p>";
echo "<code>" . $redirect_uri . "</code>";
?> 