<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database configuration
$dbHost = 'localhost';
$dbUsername = 'ideamiadev_realestate';
$dbPassword = 'fKoQ5HrJCn3?T#N!';
$dbName = 'ideamiadev_realestate';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all index data ordered by date
    $query = "SELECT observation_date, index_value as CSUSHPINSA FROM housing_price_index ORDER BY observation_date ASC";
    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 