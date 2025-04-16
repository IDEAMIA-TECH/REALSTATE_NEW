<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get property ID from request
$propertyId = $_GET['property_id'] ?? 0;

if (empty($propertyId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Property ID is required']);
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Get documents for the property
    $stmt = $db->prepare("
        SELECT 
            id,
            document_name,
            document_type,
            file_path,
            created_at
        FROM property_documents
        WHERE property_id = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$propertyId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'documents' => $documents
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 