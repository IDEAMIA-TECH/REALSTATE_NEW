<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/csushpinsa/CSUSHPINSA.php';

// Initialize database connection
$db = Database::getInstance()->getConnection();

// Function to send email notification
function sendContractClosureEmail($properties) {
    if (empty($properties)) {
        return;
    }

    $to = ADMIN_EMAIL;
    $subject = 'Contract Closure Report - ' . date('Y-m-d');
    
    $message = "<h2>Contract Closure Report</h2>";
    $message .= "<p>The following contracts were automatically closed today:</p>";
    $message .= "<table border='1' cellpadding='5' cellspacing='0'>";
    $message .= "<tr><th>Property ID</th><th>Address</th><th>Initial Index</th><th>Closing Index</th><th>Appreciation</th><th>Appreciation Rate</th></tr>";
    
    foreach ($properties as $property) {
        $message .= "<tr>";
        $message .= "<td>" . htmlspecialchars($property['id']) . "</td>";
        $message .= "<td>" . htmlspecialchars($property['address']) . "</td>";
        $message .= "<td>" . number_format($property['initial_index'], 2) . "</td>";
        $message .= "<td>" . number_format($property['closing_index'], 2) . "</td>";
        $message .= "<td>$" . number_format($property['appreciation'], 2) . "</td>";
        $message .= "<td>" . number_format($property['appreciation_rate'], 2) . "%</td>";
        $message .= "</tr>";
    }
    
    $message .= "</table>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_USERNAME . "\r\n";
    
    // Use PHPMailer if available, otherwise use mail()
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            $mail->setFrom(SMTP_USERNAME, APP_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    } else {
        mail($to, $subject, $message, $headers);
    }
}

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
            p.address,
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
    $closedProperties = [];

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
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([
                $property['expiration_date'],
                $closingIndex,
                $property['id']
            ]);

            // 5. Insert final valuation record
            $insertValuationStmt = $db->prepare("
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

            // Calculate the difference percentage
            $difference = (($closingIndex - $property['initial_index']) / $property['initial_index']) * 100;

            $insertValuationStmt->execute([
                $property['id'],
                $property['expiration_date'],
                $closingIndex,
                $property['initial_index'],
                $difference,
                $appreciation['appreciation']
            ]);

            // 6. Log the closure in activity_log
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
                    'appreciation_rate' => $appreciation['appreciation_rate'],
                    'closure_type' => 'automatic',
                    'closed_by' => 'system'
                ])
            ]);

            // Add to closed properties array for email notification
            $closedProperties[] = [
                'id' => $property['id'],
                'address' => $property['address'],
                'initial_index' => $property['initial_index'],
                'closing_index' => $closingIndex,
                'appreciation' => $appreciation['appreciation'],
                'appreciation_rate' => $appreciation['appreciation_rate']
            ];

            // Commit transaction
            $db->commit();

            error_log("Successfully closed contract for property ID: " . $property['id'] . 
                     " with closing index: " . $closingIndex . 
                     ", appreciation: " . $appreciation['appreciation'] . 
                     ", appreciation rate: " . $appreciation['appreciation_rate'] . "%");
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            error_log("Error closing contract for property ID " . $property['id'] . ": " . $e->getMessage());
        }
    }

    // Send email notification if any properties were closed
    if (!empty($closedProperties)) {
        sendContractClosureEmail($closedProperties);
    }

    error_log("Contract closure process completed.");

} catch (Exception $e) {
    error_log("Error in contract closure cron job: " . $e->getMessage());
} 