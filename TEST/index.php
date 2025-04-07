<?php
// Load configuration files first
require_once __DIR__ . '/config/paths.php';
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/env.php';
require_once CONFIG_PATH . '/database.php';

// Configure session after loading config files
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_name(SESSION_NAME);
session_start();

// Configure timezone
date_default_timezone_set(APP_TIMEZONE);

// Get current path
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace($base_path, '', $path);
$path = trim($path, '/');

// Define routes
$routes = [
    '' => MODULES_PATH . '/auth/login.php',
    'login' => MODULES_PATH . '/auth/login.php',
    'logout' => MODULES_PATH . '/auth/logout.php',
    'dashboard' => MODULES_PATH . '/admin/dashboard.php',
    'properties' => MODULES_PATH . '/properties/list.php',
    'clients' => MODULES_PATH . '/clients/list.php',
    'reports' => MODULES_PATH . '/reports/generate.php',
    'client_form.html' => __DIR__ . '/client_form.html',
    'property_form.html' => __DIR__ . '/property_form.html'
];

// Function to check authentication
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}

// Function to check permissions
function checkPermission($requiredRole) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}

// Include header
require_once INCLUDES_PATH . '/header.php';

// Handle the route
if (array_key_exists($path, $routes)) {
    $file = $routes[$path];
    
    // Check authentication for protected routes
    if ($path !== '' && $path !== 'login' && !str_ends_with($path, '.html')) {
        checkAuth();
    }
    
    // Check specific permissions
    if ($path === 'dashboard' || $path === 'reports') {
        checkPermission(ROLE_ADMIN);
    }
    
    if (file_exists($file)) {
        require_once $file;
    } else {
        header("HTTP/1.0 404 Not Found");
        require_once INCLUDES_PATH . '/404.php';
    }
} else {
    header("HTTP/1.0 404 Not Found");
    require_once INCLUDES_PATH . '/404.php';
}

// Include footer
require_once INCLUDES_PATH . '/footer.php';
