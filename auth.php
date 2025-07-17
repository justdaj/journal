<?php

// If not logged in via session, redirect to login
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}
