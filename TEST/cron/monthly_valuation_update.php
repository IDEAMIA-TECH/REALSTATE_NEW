<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/csushpinsa/CSUSHPINSA.php';

// Initialize database connection and CSUSHPINSA class
$db = Database::getInstance()->getConnection();
$csushpinsa = new CSUSHPINSA();

try {
    // First, ensure we have the latest CSUSHPINSA index data
    $today = date('Y-m-d');
    $oneMonthAgo = date('Y-m-d', strtotime('-1 month'));
    
    error_log("Starting monthly valuation update for period: {$oneMonthAgo} to {$today}");
    
    // Fetch latest index data
    if (!$csushpinsa->fetchHistoricalData($oneMonthAgo, $today)) {
        throw new Exception("Failed to fetch latest CSUSHPINSA index data");
    }

    // Get all active properties
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.initial_valuation,
            p.initial_index,
            p.effective_date,
            p.term,
            DATE_ADD(p.effective_date, INTERVAL p.term MONTH) as expiration_date
        FROM properties p
        WHERE p.status = 'active'
        AND p.effective_date <= CURDATE()
        AND DATE_ADD(p.effective_date, INTERVAL p.term MONTH) >= CURDATE()
    ");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($properties) . " active properties to update");

    foreach ($properties as $property) {
        try {
            // Start transaction for each property
            $db->beginTransaction();

            // Get the current index value
            $indexStmt = $db->prepare("
                SELECT value 
                FROM home_price_index 
                WHERE date <= ?
                ORDER BY date DESC
                LIMIT 1
            ");
            $indexStmt->execute([$today]);
            $currentIndex = $indexStmt->fetchColumn();

            if (!$currentIndex) {
                throw new Exception("Could not get current index value for property {$property['id']}");
            }

            // Calculate the difference percentage
            $difference = (($currentIndex - $property['initial_index']) / $property['initial_index']) * 100;
            
            // Calculate appreciation in dollars
            $appreciation = ($difference / 100) * $property['initial_valuation'];

            // Insert new valuation record
            $insertStmt = $db->prepare("
                INSERT INTO property_valuations (
                    property_id,
                    valuation_date,
                    index_value,
                    initial_index,
                    diference,
                    appreciation,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $insertStmt->execute([
                $property['id'],
                $today,
                $currentIndex,
                $property['initial_index'],
                $difference,
                $appreciation
            ]);

            // Log the activity
            $logStmt = $db->prepare("
                INSERT INTO activity_log (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    details,
                    created_at
                ) VALUES (
                    NULL,
                    'monthly_valuation',
                    'property',
                    ?,
                    ?,
                    NOW()
                )
            ");

            $logStmt->execute([
                $property['id'],
                json_encode([
                    'valuation_date' => $today,
                    'initial_index' => $property['initial_index'],
                    'current_index' => $currentIndex,
                    'difference' => $difference,
                    'appreciation' => $appreciation
                ])
            ]);

            // Commit transaction
            $db->commit();

            error_log("Successfully updated valuation for property ID: " . $property['id'] . 
                     " with current index: " . $currentIndex . 
                     ", difference: " . $difference . "%" .
                     ", appreciation: $" . $appreciation);

        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            error_log("Error updating valuation for property ID " . $property['id'] . ": " . $e->getMessage());
        }
    }

    error_log("Monthly valuation update completed successfully");

} catch (Exception $e) {
    error_log("Error in monthly valuation update cron: " . $e->getMessage());
} 