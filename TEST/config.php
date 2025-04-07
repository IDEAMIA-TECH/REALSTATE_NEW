<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'ideamiadev_realestate');
define('DB_PASS', 'fKoQ5HrJCn3?T#N!');
define('DB_NAME', 'ideamiadev_realestate');

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