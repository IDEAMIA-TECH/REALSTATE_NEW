<?php
// Initialize session
session_start();

// Load required files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/AuthController.php';

try {
    // Create AuthController instance
    $auth = new AuthController();

    // Perform logout
    $result = $auth->logout();

    // Redirect to login page
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
} catch (Exception $e) {
    // Log the error
    error_log('Logout error: ' . $e->getMessage());
    
    // Redirect to login page even if there's an error
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
} 