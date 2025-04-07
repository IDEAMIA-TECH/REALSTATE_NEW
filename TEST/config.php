<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ideamiadev_realestate');
define('DB_USER', 'ideamiadev_realestate');
define('DB_PASS', 'fKoQ5HrJCn3?T#N!');


// Application configuration
define('APP_NAME', 'Real Estate Management System');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'UTC');
define('BASE_URL', 'https://ideamia-dev.com/realestate/TEST');

// Session configuration
define('SESSION_NAME', 'REALESTATE_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour

// Path configuration
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('MODULES_PATH', BASE_PATH . '/modules');
define('INCLUDES_PATH', __DIR__ . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_AGENT', 'agent');
define('ROLE_CLIENT', 'client');

// Email configuration
define('SMTP_HOST', '');
define('SMTP_PORT', '');
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Security configuration
define('SESSION_TIMEOUT', 30); // minutes
define('MAX_LOGIN_ATTEMPTS', 5);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set(APP_TIMEZONE);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}