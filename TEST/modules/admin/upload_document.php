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

    // Check if required data is provided
    if (!isset($_POST['property_id']) || !is_numeric($_POST['property_id'])) {
        throw new Exception('Invalid property ID');
    }

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    $propertyId = (int)$_POST['property_id'];
    $file = $_FILES['document'];
    $documentType = $_POST['document_type'] ?? 'other';
    $documentName = $_POST['document_name'] ?? $file['name'];

    // Validate file type
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed types: PDF, JPEG, PNG, DOC, DOCX');
    }

    // Validate file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('File size exceeds 10MB limit');
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/properties/' . $propertyId . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save file');
    }

    // Save document info to database
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO property_documents (
            property_id,
            document_name,
            document_type,
            file_path,
            uploaded_by
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $relativePath = 'uploads/properties/' . $propertyId . '/' . $filename;
    $stmt->execute([
        $propertyId,
        $documentName,
        $documentType,
        $relativePath,
        $_SESSION['user_id']
    ]);

    $response['success'] = true;
    $response['message'] = 'Document uploaded successfully';
    $response['document'] = [
        'id' => $db->lastInsertId(),
        'name' => $documentName,
        'type' => $documentType,
        'path' => $relativePath,
        'upload_date' => date('Y-m-d H:i:s')
    ];

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 