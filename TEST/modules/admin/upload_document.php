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

// Get database connection
$db = Database::getInstance()->getConnection();

// Check if file was uploaded
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

// Get document details
$propertyId = $_POST['property_id'] ?? 0;
$documentType = $_POST['document_type'] ?? '';
$documentName = $_POST['document_name'] ?? '';

if (empty($propertyId) || empty($documentType) || empty($documentName)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/../../uploads/documents/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$fileExtension = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
$fileName = uniqid() . '.' . $fileExtension;
$filePath = $uploadDir . $fileName;

// Move uploaded file
if (!move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    exit;
}

// Save document record to database
try {
    $stmt = $db->prepare("
        INSERT INTO property_documents (
            property_id, 
            document_name, 
            document_type, 
            file_path, 
            uploaded_by
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    // La ruta debe ser relativa a la raíz del sitio, sin incluir modules/admin
    $relativePath = 'uploads/documents/' . $fileName;
    $stmt->execute([
        $propertyId,
        $documentName,
        $documentType,
        $relativePath,
        $_SESSION['user_id']
    ]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Document uploaded successfully',
        'document' => [
            'id' => $db->lastInsertId(),
            'name' => $documentName,
            'type' => $documentType,
            'path' => $relativePath,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (PDOException $e) {
    // Delete the uploaded file if database insert fails
    unlink($filePath);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 