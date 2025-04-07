<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Preparar la consulta SQL
    $sql = "SELECT id, firstName, lastName, email, phone FROM clients ORDER BY lastName, firstName";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Obtener todos los clientes
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $clients
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 