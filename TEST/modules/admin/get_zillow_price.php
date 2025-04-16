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
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Cache-Control: max-age=0'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Cache-Control: max-age=0'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $propertyHtml = curl_exec($ch);
    curl_close($ch);

    // 5. Extraer precio usando múltiples patrones
    $price = null;
    
    // Patrón 1: Buscar en el JSON embebido
    preg_match('/"price":(\d+)/', $propertyHtml, $priceMatches);
    if (isset($priceMatches[1])) {
        $price = '$' . number_format($priceMatches[1]);
    }
    
    // Patrón 2: Buscar en el HTML
    if (!$price) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($propertyHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        
        // Intentar diferentes selectores
        $selectors = [
            '//span[@data-testid="price"]',
            '//span[contains(@class, "ds-price")]',
            '//span[contains(@class, "price")]',
            '//div[contains(@class, "price")]'
        ];
        
        foreach ($selectors as $selector) {
            $priceNode = $xpath->query($selector);
            if ($priceNode->length > 0) {
                $price = trim($priceNode->item(0)->nodeValue);
                break;
            }
        }
    }

    if ($price) {
        return $price;
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