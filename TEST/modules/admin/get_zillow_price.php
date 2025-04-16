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

// Get the address from the request
$data = json_decode(file_get_contents('php://input'), true);
$address = $data['address'] ?? '';

if (empty($address)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Address is required']);
    exit;
}

// Function to get Zillow price from address
function getZillowPriceFromAddress($address) {
    // 1. Preparar búsqueda
    $query = urlencode($address);
    $searchUrl = "https://www.zillow.com/homes/{$query}_rb/";

    // 2. Realizar solicitud HTTP con cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    // 3. Buscar el primer enlace a una propiedad
    preg_match('/"zpid":"(\d+)"/', $html, $matches);
    if (!isset($matches[1])) {
        return "No se encontró propiedad";
    }

    $zpid = $matches[1];
    $propertyUrl = "https://www.zillow.com/homedetails/{$zpid}_zpid/";

    // 4. Obtener HTML del detalle de propiedad
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $propertyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
    ]);
    $propertyHtml = curl_exec($ch);
    curl_close($ch);

    // 5. Extraer precio desde etiqueta con data-testid="price"
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($propertyHtml);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $priceNode = $xpath->query('//span[@data-testid="price"]');

    if ($priceNode->length > 0) {
        return trim($priceNode->item(0)->nodeValue);
    }

    return "Precio no encontrado";
}

// Get the price
$price = getZillowPriceFromAddress($address);

// Return the response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'price' => $price
]); 