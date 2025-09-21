<?php
/**
 * Index Page - Entry Point
 * HR Management System
 */

require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in using User class
$currentUser = User::fromSession($db);
if ($currentUser) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
} else {
    // User is not logged in, redirect to login
    header('Location: login.php');
}

exit;
?>
