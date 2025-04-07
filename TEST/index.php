<?php
// Load configuration first
require_once __DIR__ . '/config.php';

// Redirect to login page
header('Location: ' . BASE_URL . '/modules/auth/login.php');
exit;
?> 