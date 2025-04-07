<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Set default response
$response = [
    'success' => true,
    'data' => []
];

try {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Check if property_id is provided
    if (!isset($_GET['property_id']) || !is_numeric($_GET['property_id'])) {
        throw new Exception('Invalid property ID');
    }

    $propertyId = (int)$_GET['property_id'];
    $db = Database::getInstance()->getConnection();

    // Get property details for initial values
    $stmt = $db->prepare("
        SELECT 
            initial_valuation,
            agreed_pct,
            option_price
        FROM properties
        WHERE id = ?
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        throw new Exception('Property not found');
    }

    // Get valuation history
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(valuation_date, '%Y-%m-%d') as date,
            current_value,
            appreciation,
            share_appreciation,
            terminal_value,
            projected_payoff,
            option_valuation
        FROM property_valuations
        WHERE property_id = ?
        ORDER BY valuation_date DESC
    ");
    
    $stmt->execute([$propertyId]);
    $valuations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add initial values to each valuation
    foreach ($valuations as &$valuation) {
        $valuation['initial_valuation'] = $property['initial_valuation'];
        $valuation['agreed_pct'] = $property['agreed_pct'];
        $valuation['option_price'] = $property['option_price'];
    }
    
    $response['data'] = $valuations;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Always return a JSON response
header('Content-Type: application/json');
echo json_encode($response); 