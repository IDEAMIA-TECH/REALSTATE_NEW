<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'ideamiadev_realestate');
define('DB_PASS', 'fKoQ5HrJCn3?T#N!');
define('DB_NAME', 'ideamiadev_realestate');

// Application configuration
define('APP_NAME', 'Real Estate Management');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'UTC');

// Session configuration
define('SESSION_NAME', 'REALESTATE_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour

// Base URL configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', rtrim($base_url, '/'));

// Path configuration
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('MODULES_PATH', BASE_PATH . '/modules');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_AGENT', 'agent');
define('ROLE_CLIENT', 'client');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?> 