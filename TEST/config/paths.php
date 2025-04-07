<?php
// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('MODULES_PATH', BASE_PATH . '/modules');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Define base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', rtrim($base_url, '/'));

// Define application constants
define('APP_NAME', 'Real Estate Management');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'UTC');
define('SESSION_NAME', 'REALESTATE_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour

// Define user roles
define('ROLE_ADMIN', 'admin');
define('ROLE_AGENT', 'agent');
define('ROLE_CLIENT', 'client'); 