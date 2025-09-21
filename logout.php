<?php
/**
 * Logout Handler
 * HR Management System
 */

session_start();

// Clear all session variables
$_SESSION = array();

// If there's a session cookie, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?message=logged_out');
exit;
?>