<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // Log para debugging
    error_log("Iniciando proceso de guardado de cliente");

    // Verificar si es una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Expected POST, got ' . $_SERVER['REQUEST_METHOD']);
    }

    // Obtener los datos del cliente
    $input = file_get_contents('php://input');
    error_log("Datos recibidos: " . $input);
    
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }

    // Validar campos requeridos
    $required_fields = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'state', 'zipCode'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Validar formato de email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Preparar la consulta SQL
    $sql = "INSERT INTO clients (firstName, lastName, email, phone, address, city, state, zipCode, created_at) 
            VALUES (:firstName, :lastName, :email, :phone, :address, :city, :state, :zipCode, NOW())";
    
    error_log("SQL Query: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    
    // Ejecutar la consulta con los datos del cliente
    $result = $stmt->execute([
        ':firstName' => $data['firstName'],
        ':lastName' => $data['lastName'],
        ':email' => $data['email'],
        ':phone' => $data['phone'],
        ':address' => $data['address'],
        ':city' => $data['city'],
        ':state' => $data['state'],
        ':zipCode' => $data['zipCode']
    ]);

    if ($result) {
        $response = [
            'success' => true,
            'message' => 'Client registered successfully',
            'clientId' => $pdo->lastInsertId()
        ];
        error_log("Cliente guardado exitosamente. ID: " . $pdo->lastInsertId());
    } else {
        throw new Exception('Error executing database query');
    }

} catch (Exception $e) {
    error_log("Error en save_client.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code(400);
}

echo json_encode($response);
?> 