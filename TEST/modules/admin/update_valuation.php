<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../csushpinsa/CSUSHPINSA.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['property_id']) || !isset($data['valuation_date'])) {
        throw new Exception('Missing required parameters');
    }
    
    $propertyId = $data['property_id'];
    $valuationDate = $data['valuation_date'];
    
    // Initialize CSUSHPINSA class
    $csushpinsa = new CSUSHPINSA();
    
    // Calculate appreciation
    $result = $csushpinsa->calculatePropertyAppreciation($propertyId, $valuationDate);
    
    if (!$result) {
        throw new Exception('Failed to calculate property appreciation');
    }
    
    // Update property valuation
    $success = $csushpinsa->updatePropertyValuation($propertyId, $valuationDate);
    
    if (!$success) {
        throw new Exception('Failed to update property valuation');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Valuation updated successfully',
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 