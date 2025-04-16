<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get the address and property ID from the request
$data = json_decode(file_get_contents('php://input'), true);
$address = $data['address'] ?? '';
$propertyId = $data['property_id'] ?? 0;

if (empty($address) || empty($propertyId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Address and property ID are required']);
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Check if we need to update the price (only once per month)
$stmt = $db->prepare("SELECT zillow_price, zillow_price_updated_at FROM properties WHERE id = ?");
$stmt->execute([$propertyId]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

// If we have a price and it was updated less than a month ago, return it
if ($property && $property['zillow_price'] && $property['zillow_price_updated_at']) {
    $lastUpdate = new DateTime($property['zillow_price_updated_at']);
    $now = new DateTime();
    $diff = $now->diff($lastUpdate);
    
    if ($diff->m < 1) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'price' => '$' . number_format($property['zillow_price'], 2),
            'cached' => true
        ]);
        exit;
    }
}

// Function to get Zillow price from address
function getZillowPriceFromAddress($address) {
    error_log("Iniciando búsqueda para dirección: " . $address);
    
    // 1. Preparar búsqueda
    $query = urlencode($address);
    $searchUrl = "https://www.zillow.com/homes/{$query}_rb/";
    error_log("URL de búsqueda: " . $searchUrl);

    // Headers más realistas
    $headers = [
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
        'Cache-Control: max-age=0',
        'DNT: 1',
        'sec-ch-ua: "Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "macOS"',
        'Pragma: no-cache',
        'Referer: https://www.zillow.com/',
        'Cookie: zguid=23|%24b9b9b9b9-b9b9-b9b9-b9b9-b9b9b9b9b9b9; zgsession=1|b9b9b9b9-b9b9-b9b9-b9b9-b9b9b9b9b9b9'
    ];

    // 2. Realizar solicitud HTTP con cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $html = curl_exec($ch);
    
    // Log cURL verbose information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("cURL verbose log:\n" . $verboseLog);
    
    if (curl_errno($ch)) {
        error_log("Error en cURL: " . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log("HTTP Code: " . $httpCode);
    
    curl_close($ch);
    fclose($verbose);

    // 3. Buscar el primer enlace a una propiedad
    error_log("Buscando zpid en el HTML...");
    preg_match('/"zpid":"(\d+)"/', $html, $matches);
    error_log("Resultado de búsqueda de zpid: " . print_r($matches, true));
    
    if (!isset($matches[1])) {
        error_log("No se encontró zpid en el HTML");
        return null;
    }

    $zpid = $matches[1];
    $propertyUrl = "https://www.zillow.com/homedetails/{$zpid}_zpid/";
    error_log("URL de la propiedad: " . $propertyUrl);

    // 4. Obtener HTML del detalle de propiedad
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $propertyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $propertyHtml = curl_exec($ch);
    
    // Log cURL verbose information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("cURL verbose log (propiedad):\n" . $verboseLog);
    
    if (curl_errno($ch)) {
        error_log("Error en cURL (propiedad): " . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log("HTTP Code (propiedad): " . $httpCode);
    
    curl_close($ch);
    fclose($verbose);

    // 5. Extraer precio usando múltiples patrones
    $price = null;
    
    // Patrón 1: Buscar en el JSON embebido
    error_log("Buscando precio en JSON embebido...");
    preg_match('/"price":(\d+)/', $propertyHtml, $priceMatches);
    error_log("Resultado de búsqueda de precio en JSON: " . print_r($priceMatches, true));
    
    if (isset($priceMatches[1])) {
        $price = $priceMatches[1];
        error_log("Precio encontrado en JSON: " . $price);
    }
    
    // Patrón 2: Buscar en el HTML
    if (!$price) {
        error_log("Buscando precio en HTML...");
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
            error_log("Intentando selector: " . $selector);
            $priceNode = $xpath->query($selector);
            if ($priceNode->length > 0) {
                $priceText = trim($priceNode->item(0)->nodeValue);
                // Extraer solo los números del precio
                $price = preg_replace('/[^0-9]/', '', $priceText);
                error_log("Precio encontrado con selector " . $selector . ": " . $price);
                break;
            }
        }
    }

    if ($price) {
        error_log("Precio final encontrado: " . $price);
        return $price;
    }

    error_log("No se pudo encontrar el precio");
    return null;
}

// Get the price
error_log("Iniciando proceso de obtención de precio para: " . $address);
$price = getZillowPriceFromAddress($address);

if ($price) {
    // Update the price in the database
    $stmt = $db->prepare("UPDATE properties SET zillow_price = ?, zillow_price_updated_at = NOW() WHERE id = ?");
    $stmt->execute([$price, $propertyId]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'price' => '$' . number_format($price, 2),
        'cached' => false
    ]);
} else {
    // If we couldn't get a new price, return the cached one if available
    if ($property && $property['zillow_price']) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'price' => '$' . number_format($property['zillow_price'], 2),
            'cached' => true
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo obtener el precio de Zillow'
        ]);
    }
} 