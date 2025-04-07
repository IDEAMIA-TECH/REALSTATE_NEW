<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Set default response
$response = [
    'success' => false,
    'message' => '',
    'error' => ''
];

try {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['doc_id']) || !is_numeric($data['doc_id'])) {
        throw new Exception('Invalid document ID');
    }

    $docId = (int)$data['doc_id'];
    $db = Database::getInstance()->getConnection();

    // Get document info
    $stmt = $db->prepare("
        SELECT file_path 
        FROM property_documents 
        WHERE id = ? AND uploaded_by = ?
    ");
    
    $stmt->execute([$docId, $_SESSION['user_id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception('Document not found or unauthorized');
    }

    // Delete file
    $filePath = __DIR__ . '/../../' . $document['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete from database
    $stmt = $db->prepare("DELETE FROM property_documents WHERE id = ?");
    $stmt->execute([$docId]);

    $response['success'] = true;
    $response['message'] = 'Document deleted successfully';

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 