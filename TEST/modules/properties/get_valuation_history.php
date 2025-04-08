<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Set default response
$response = [
    'success' => true,
    'data' => [
        'valuations' => []
    ]
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Check if property_id is provided
    if (!isset($_GET['property_id']) || !is_numeric($_GET['property_id'])) {
        throw new Exception('Invalid property ID');
    }

    $propertyId = (int)$_GET['property_id'];
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    $db = Database::getInstance()->getConnection();

    // If user is not admin, verify they own the property
    if ($userRole !== 'admin') {
        $stmt = $db->prepare("
            SELECT p.id 
            FROM properties p
            JOIN clients c ON p.client_id = c.id
            JOIN users u ON c.id = u.client_id
            WHERE p.id = ? AND u.id = ?
        ");
        $stmt->execute([$propertyId, $userId]);
        if (!$stmt->fetch()) {
            throw new Exception('Unauthorized access to this property');
        }
    }

    // Get property details for initial values
    $stmt = $db->prepare("
        SELECT 
            initial_valuation,
            initial_index,
            initial_index_date,
            agreed_pct,
            option_price,
            total_fees
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
            valuation_date,
            index_value,
            initial_index,
            diference,
            appreciation
        FROM property_valuations
        WHERE property_id = ?
        ORDER BY valuation_date DESC
    ");
    
    $stmt->execute([$propertyId]);
    $valuations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no valuations exist, create initial valuation
    if (empty($valuations)) {
        $valuations[] = [
            'valuation_date' => $property['initial_index_date'],
            'index_value' => $property['initial_index'],
            'initial_index' => $property['initial_index'],
            'diference' => 0,
            'appreciation' => 0
        ];
    }
    
    // Add property details to each valuation
    foreach ($valuations as &$valuation) {
        $valuation['initial_valuation'] = $property['initial_valuation'];
        $valuation['agreed_pct'] = $property['agreed_pct'];
        $valuation['option_price'] = $property['option_price'];
        $valuation['total_fees'] = $property['total_fees'];
    }
    
    $response['data']['valuations'] = $valuations;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Always return a JSON response
header('Content-Type: application/json');
echo json_encode($response); 