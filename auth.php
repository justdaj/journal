<?php

// If already logged in via session, nothing to do
if (!isset($_SESSION['logged_in'])) {
    // Check for remember_token cookie
    if (!empty($_COOKIE['remember_token'])) {
        $tokenFromCookie = $_COOKIE['remember_token'] ?? '';
        $tokenFromFile = @file_get_contents(__DIR__ . '/remember_token.txt');

        if ($tokenFromFile && hash_equals(trim($tokenFromFile), trim($tokenFromCookie))) {
            // Token matches, log in the user
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
        }
    }

    // Still not logged in? Redirect to login page
    if (!isset($_SESSION['logged_in'])) {
        header('Location: login.php');
        exit;
    }
}
