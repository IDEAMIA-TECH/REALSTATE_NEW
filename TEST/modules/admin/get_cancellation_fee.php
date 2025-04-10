<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get state from query parameter
$state = isset($_GET['state']) ? $_GET['state'] : null;

if (!$state) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'State parameter is required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get cancellation fee for the state
    $stmt = $db->prepare("
        SELECT fee_type, fee_percentage, fixed_fee 
        FROM cancellation_fees 
        WHERE state = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$state]);
    $fee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    if ($fee) {
        echo json_encode([
            'success' => true,
            'fee' => $fee
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No cancellation fee found for this state'
        ]);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} 