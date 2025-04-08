<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/csushpinsa/CSUSHPINSA.php';

// Initialize database connection
$db = Database::getInstance()->getConnection();

try {
    // Get all active properties that have expired
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.effective_date,
            p.term,
            p.status,
            p.initial_index,
            p.initial_valuation,
            DATE_ADD(p.effective_date, INTERVAL p.term MONTH) as expiration_date
        FROM properties p
        WHERE p.status = 'active'
        AND DATE_ADD(p.effective_date, INTERVAL p.term MONTH) <= CURDATE()
    ");
    $stmt->execute();
    $expiredProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the start of the process
    error_log("Starting contract closure process. Found " . count($expiredProperties) . " expired contracts.");

    $csushpinsa = new CSUSHPINSA();

    foreach ($expiredProperties as $property) {
        try {
            // Start transaction
            $db->beginTransaction();

            // 1. Verify property exists and is active
            $checkStmt = $db->prepare("SELECT status FROM properties WHERE id = ?");
            $checkStmt->execute([$property['id']]);
            $propertyStatus = $checkStmt->fetchColumn();
            
            if (!$propertyStatus || $propertyStatus !== 'active') {
                throw new Exception("Property {$property['id']} not found or not active");
            }

            // 2. Get the closing index value
            // First ensure we have the latest data
            $csushpinsa->fetchHistoricalData($property['effective_date'], $property['expiration_date']);
            
            // Get the index value for the closing date
            $indexStmt = $db->prepare("
                SELECT value 
                FROM home_price_index 
                WHERE date <= ?
                ORDER BY date DESC
                LIMIT 1
            ");
            $indexStmt->execute([$property['expiration_date']]);
            $closingIndex = $indexStmt->fetchColumn();
            
            if (!$closingIndex) {
                throw new Exception("Could not get closing index value for property {$property['id']}");
            }

            // 3. Calculate final appreciation
            $appreciation = $csushpinsa->calculatePropertyAppreciation($property['id'], $property['expiration_date']);
            
            if (!$appreciation) {
                throw new Exception("Could not calculate final appreciation for property {$property['id']}");
            }

            // 4. Update property status to 'closed' and add closing index
            $updateStmt = $db->prepare("
                UPDATE properties 
                SET 
                    status = 'closed',
                    closing_date = ?,
                    closing_index = ?,
                    final_appreciation = ?,
                    final_share_appreciation = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([
                $property['expiration_date'],
                $closingIndex,
                $appreciation['appreciation'],
                $appreciation['share_appreciation'],
                $property['id']
            ]);

            // 5. Log the closure in activity_log
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
                    'contract_closed',
                    'property',
                    ?,
                    ?,
                    NOW()
                )
            ");
            $logStmt->execute([
                $property['id'],
                json_encode([
                    'expiration_date' => $property['expiration_date'],
                    'initial_index' => $property['initial_index'],
                    'closing_index' => $closingIndex,
                    'initial_valuation' => $property['initial_valuation'],
                    'final_appreciation' => $appreciation['appreciation'],
                    'final_share_appreciation' => $appreciation['share_appreciation'],
                    'appreciation_rate' => $appreciation['appreciation_rate'],
                    'closure_type' => 'automatic',
                    'closed_by' => 'system'
                ])
            ]);

            // Commit transaction
            $db->commit();

            error_log("Successfully closed contract for property ID: " . $property['id'] . 
                     " with closing index: " . $closingIndex . 
                     ", appreciation: " . $appreciation['appreciation'] . 
                     ", share appreciation: " . $appreciation['share_appreciation']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            error_log("Error closing contract for property ID " . $property['id'] . ": " . $e->getMessage());
        }
    }

    error_log("Contract closure process completed.");

} catch (Exception $e) {
    error_log("Error in contract closure cron job: " . $e->getMessage());
} 