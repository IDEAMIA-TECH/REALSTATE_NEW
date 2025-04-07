<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Set default response
$response = [
    'success' => true,
    'data' => []
];

try {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Check if property_id is provided
    if (!isset($_GET['property_id']) || !is_numeric($_GET['property_id'])) {
        throw new Exception('Invalid property ID');
    }

    $propertyId = (int)$_GET['property_id'];
    $db = Database::getInstance()->getConnection();

    // Check if property_valuations table exists
    try {
        $db->query("SELECT 1 FROM property_valuations LIMIT 1");
    } catch (PDOException $e) {
        // Create property_valuations table if it doesn't exist
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS property_valuations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                valuation_date DATE NOT NULL,
                current_value DECIMAL(15,2) NOT NULL,
                appreciation_rate DECIMAL(5,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                INDEX idx_property_date (property_id, valuation_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $db->exec($createTableSQL);
    }

    // Get valuation history
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(valuation_date, '%Y-%m-%d') as date,
            current_value as value,
            appreciation as appreciation_rate,
            share_appreciation,
            terminal_value,
            projected_payoff,
            option_valuation
        FROM property_valuations
        WHERE property_id = ?
        ORDER BY valuation_date DESC
    ");
    
    $stmt->execute([$propertyId]);
    $valuations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no valuations exist, create initial valuation from property data
    if (empty($valuations)) {
        $stmt = $db->prepare("
            SELECT 
                initial_valuation,
                effective_date,
                agreed_pct
            FROM properties
            WHERE id = ?
        ");
        $stmt->execute([$propertyId]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($property) {
            $valuations[] = [
                'date' => $property['effective_date'],
                'value' => number_format($property['initial_valuation'], 2, '.', ''),
                'appreciation_rate' => '0.00',
                'share_appreciation' => '0.00',
                'terminal_value' => number_format($property['initial_valuation'], 2, '.', ''),
                'projected_payoff' => '0.00',
                'option_valuation' => '0.00'
            ];
        }
    } else {
        // Format the data
        foreach ($valuations as &$valuation) {
            $valuation['value'] = number_format($valuation['value'], 2, '.', '');
            $valuation['appreciation_rate'] = number_format($valuation['appreciation_rate'], 2, '.', '');
            $valuation['share_appreciation'] = number_format($valuation['share_appreciation'], 2, '.', '');
            $valuation['terminal_value'] = number_format($valuation['terminal_value'], 2, '.', '');
            $valuation['projected_payoff'] = number_format($valuation['projected_payoff'], 2, '.', '');
            $valuation['option_valuation'] = number_format($valuation['option_valuation'], 2, '.', '');
        }
    }
    
    $response['data'] = $valuations;

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Always return a JSON response
header('Content-Type: application/json');
echo json_encode($response); 