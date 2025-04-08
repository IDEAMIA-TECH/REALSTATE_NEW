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
            DATE_ADD(p.effective_date, INTERVAL p.term MONTH) as expiration_date
        FROM properties p
        WHERE p.status = 'active'
        AND DATE_ADD(p.effective_date, INTERVAL p.term MONTH) <= CURDATE()
    ");
    $stmt->execute();
    $expiredProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the start of the process
    error_log("Starting contract closure process. Found " . count($expiredProperties) . " expired contracts.");

    foreach ($expiredProperties as $property) {
        try {
            // Start transaction
            $db->beginTransaction();

            // 1. Update property status to 'closed'
            $updateStmt = $db->prepare("
                UPDATE properties 
                SET 
                    status = 'closed',
                    closing_date = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$property['expiration_date'], $property['id']]);

            // 2. Get the CSUSHPINSA index for the closing date
            $csushpinsa = new CSUSHPINSA();
            $csushpinsa->updatePropertyValuation($property['id'], $property['expiration_date']);

            // 3. Log the closure in activity_log
            $logStmt = $db->prepare("
                INSERT INTO activity_log (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    details,
                    created_at
                ) VALUES (
                    0,
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
                    'closure_type' => 'automatic',
                    'closed_by' => 'system'
                ])
            ]);

            // Commit transaction
            $db->commit();

            error_log("Successfully closed contract for property ID: " . $property['id']);
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