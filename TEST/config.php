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

// Session configuration
define('SESSION_NAME', 'REALESTATE_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour

// Base URL configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . $host . $basePath);

// Path configuration
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('MODULES_PATH', BASE_PATH . '/modules');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_AGENT', 'agent');
define('ROLE_CLIENT', 'client');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set(APP_TIMEZONE);