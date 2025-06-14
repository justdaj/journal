<?php
require 'bootstrap.php';

// Clear session
$_SESSION = [];
session_unset();
session_destroy();

// Remove remember_token cookie
setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);

// Delete the token file
$tokenFile = __DIR__ . '/remember_token.txt';
if (file_exists($tokenFile)) {
    unlink($tokenFile);
}

// Redirect to login
header('Location: login.php');
exit;
