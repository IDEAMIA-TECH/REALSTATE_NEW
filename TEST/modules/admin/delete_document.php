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

// Get document ID from request
$data = json_decode(file_get_contents('php://input'), true);
$docId = $data['doc_id'] ?? 0;

if (empty($docId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Document ID is required']);
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Get document info before deleting
    $stmt = $db->prepare("
        SELECT file_path 
        FROM property_documents 
        WHERE id = ?
    ");
    $stmt->execute([$docId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        throw new Exception('Document not found');
    }
    
    // Delete the file
    $filePath = __DIR__ . '/../../' . $document['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete the database record
    $stmt = $db->prepare("DELETE FROM property_documents WHERE id = ?");
    $stmt->execute([$docId]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 