<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/csushpinsa/CSUSHPINSA.php';

// Initialize CSUSHPINSA class
$csushpinsa = new CSUSHPINSA();

// Get the last update date from the database
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT MAX(date) as last_date FROM home_price_index");
$lastDate = $stmt->fetch(PDO::FETCH_ASSOC)['last_date'];

// If no data exists, fetch last year's data
if (!$lastDate) {
    $startDate = date('Y-m-d', strtotime('-1 year'));
} else {
    $startDate = date('Y-m-d', strtotime($lastDate . ' +1 day'));
}

$endDate = date('Y-m-d');

// Fetch and store new data
if ($csushpinsa->fetchHistoricalData($startDate, $endDate)) {
    // Log success
    error_log("CSUSHPINSA data updated successfully from $startDate to $endDate");
    
    // Update property valuations
    $stmt = $db->query("
        SELECT p.id, p.effective_date
        FROM properties p
        WHERE p.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM property_valuations pv
            WHERE pv.property_id = p.id
            AND pv.valuation_date = CURDATE()
        )
    ");
    
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($properties as $property) {
        if ($csushpinsa->updatePropertyValuation($property['id'], $endDate)) {
            error_log("Property valuation updated for property ID: " . $property['id']);
        } else {
            error_log("Failed to update property valuation for property ID: " . $property['id']);
        }
    }
} else {
    error_log("Failed to update CSUSHPINSA data");
} 