<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if property_id is provided
if (!isset($_GET['property_id']) || !is_numeric($_GET['property_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid property ID']);
    exit;
}

$propertyId = (int)$_GET['property_id'];
$db = Database::getInstance()->getConnection();

try {
    // Get valuation history
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(valuation_date, '%Y-%m-%d') as date,
            current_value as value,
            appreciation_rate as appreciation
        FROM property_valuations
        WHERE property_id = ?
        ORDER BY valuation_date DESC
    ");
    
    $stmt->execute([$propertyId]);
    $valuations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    foreach ($valuations as &$valuation) {
        $valuation['value'] = number_format($valuation['value'], 2, '.', '');
        $valuation['appreciation'] = number_format($valuation['appreciation'], 2, '.', '');
    }
    
    header('Content-Type: application/json');
    echo json_encode($valuations);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 