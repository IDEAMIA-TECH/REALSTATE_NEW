<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('DEBUG_MODE', true);
define('MAINTENANCE_MODE', false);

// Security settings
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12);

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_PATH', BASE_PATH . '/cache');
define('CACHE_LIFETIME', 3600); // 1 hour 