<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Verificar si es una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Obtener los datos de la propiedad
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        throw new Exception('Invalid data received');
    }

    // Verificar que el cliente existe
    $checkClient = $pdo->prepare("SELECT id FROM clients WHERE id = ?");
    $checkClient->execute([$data['clientId']]);
    if (!$checkClient->fetch()) {
        throw new Exception('Invalid client ID');
    }

    // Preparar la consulta SQL
    $sql = "INSERT INTO properties (
                clientId, 
                propertyType, 
                address, 
                city, 
                state, 
                zipCode, 
                price, 
                squareMeters, 
                bedrooms, 
                bathrooms, 
                description, 
                created_at
            ) VALUES (
                :clientId, 
                :propertyType, 
                :address, 
                :city, 
                :state, 
                :zipCode, 
                :price, 
                :squareMeters, 
                :bedrooms, 
                :bathrooms, 
                :description, 
                NOW()
            )";
    
    $stmt = $pdo->prepare($sql);
    
    // Ejecutar la consulta con los datos de la propiedad
    $result = $stmt->execute([
        ':clientId' => $data['clientId'],
        ':propertyType' => $data['propertyType'],
        ':address' => $data['address'],
        ':city' => $data['city'],
        ':state' => $data['state'],
        ':zipCode' => $data['zipCode'],
        ':price' => $data['price'],
        ':squareMeters' => $data['squareMeters'],
        ':bedrooms' => $data['bedrooms'] ?? null,
        ':bathrooms' => $data['bathrooms'] ?? null,
        ':description' => $data['description'] ?? null
    ]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Property registered successfully',
            'propertyId' => $pdo->lastInsertId()
        ]);
    } else {
        throw new Exception('Error registering property');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 