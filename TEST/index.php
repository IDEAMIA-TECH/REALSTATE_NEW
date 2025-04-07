<?php
session_start();
require_once __DIR__ . '/config/paths.php';
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/env.php';
require_once CONFIG_PATH . '/database.php';

// Configurar zona horaria
date_default_timezone_set(APP_TIMEZONE);

// Configurar sesión
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_name(SESSION_NAME);

// Obtener la ruta actual
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace($base_path, '', $path);
$path = trim($path, '/');

// Definir rutas
$routes = [
    '' => MODULES_PATH . '/auth/login.php',
    'login' => MODULES_PATH . '/auth/login.php',
    'logout' => MODULES_PATH . '/auth/logout.php',
    'dashboard' => MODULES_PATH . '/admin/dashboard.php',
    'properties' => MODULES_PATH . '/properties/list.php',
    'clients' => MODULES_PATH . '/clients/list.php',
    'reports' => MODULES_PATH . '/reports/generate.php'
];

// Función para verificar autenticación
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}

// Función para verificar permisos
function checkPermission($requiredRole) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
}

// Manejar la ruta
if (array_key_exists($path, $routes)) {
    $file = $routes[$path];
    
    // Verificar autenticación para rutas protegidas
    if ($path !== '' && $path !== 'login') {
        checkAuth();
    }
    
    // Verificar permisos específicos
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
