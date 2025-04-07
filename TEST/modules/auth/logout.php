<?php
// Initialize session
session_start();

// Create AuthController instance
$auth = new AuthController();

// Perform logout
$result = $auth->logout();

// Redirect to login page
header('Location: ' . BASE_URL . '/login.php');
exit; 