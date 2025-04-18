<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/database.php';

class CSUSHPINSA {
    private $apiKey;
    private $baseUrl;
    private $db;
    
    public function __construct() {
        $this->apiKey = 'bf7de8e5b6c4328f21855e67a5bdd8f2';
        $this->baseUrl = 'https://api.stlouisfed.org/fred/series/observations';
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function fetchHistoricalData($startDate = null, $endDate = null) {
        try {
            // Set default dates if not provided
            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-1 year'));
            }
            if (!$endDate) {
                $endDate = date('Y-m-d');
            }

            // Build URL with parameters
            $params = [
                'series_id' => 'CSUSHPINSA',
                'api_key' => $this->apiKey,
                'file_type' => 'json',
                'observation_start' => $startDate,
                'observation_end' => $endDate
            ];
            
            $url = $this->baseUrl . '?' . http_build_query($params);

            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            // Execute request
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('cURL Error: ' . curl_error($ch));
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode);
            }
            
            curl_close($ch);

            // Parse response
            $data = json_decode($response, true);
            if (!$data || !isset($data['observations'])) {
                throw new Exception('Invalid API response');
            }

            // Store observations in database
            $stmt = $this->db->prepare("
                INSERT INTO home_price_index (date, value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value)
            ");

            foreach ($data['observations'] as $observation) {
                if ($observation['value'] !== '.') { // FRED uses '.' for missing values
                    $stmt->execute([
                        $observation['date'],
                        $observation['value']
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('CSUSHPINSA Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getHistoricalData($startDate = null, $endDate = null) {
        $sql = "SELECT date, value FROM home_price_index";
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " WHERE date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function calculatePropertyAppreciation($propertyId, $valuationDate) {
        try {
            error_log("Starting property appreciation calculation for property ID: {$propertyId} and date: {$valuationDate}");
            
            // Get property details
            $stmt = $this->db->prepare("
                SELECT initial_valuation, effective_date, agreed_pct
                FROM properties
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$propertyId]);
            $property = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$property) {
                error_log("Property not found or inactive: {$propertyId}");
                return false;
            }
            
            error_log("Property details found: " . print_r($property, true));
            
            // Validate dates
            if (strtotime($valuationDate) < strtotime($property['effective_date'])) {
                error_log("Valuation date cannot be before effective date");
                return false;
            }
            
            // Get the closest index values for both dates
            $stmt = $this->db->prepare("
                SELECT date, value 
                FROM home_price_index 
                WHERE date <= ?
                ORDER BY date DESC
                LIMIT 1
            ");
            $stmt->execute([$valuationDate]);
            $currentIndex = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt->execute([$property['effective_date']]);
            $initialIndex = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Initial index value found: " . print_r($initialIndex, true));
            error_log("Current index value found: " . print_r($currentIndex, true));
            
            if (!$initialIndex || !$currentIndex) {
                error_log("Missing index values. Need to fetch historical data first.");
                // Try to fetch historical data
                if ($this->fetchHistoricalData($property['effective_date'], $valuationDate)) {
                    // Retry getting index values after fetching
                    $stmt->execute([$valuationDate]);
                    $currentIndex = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt->execute([$property['effective_date']]);
                    $initialIndex = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$initialIndex || !$currentIndex) {
                        error_log("Still missing index values after fetching historical data");
                        return false;
                    }
                } else {
                    error_log("Failed to fetch historical data");
                    return false;
                }
            }
            
            // Calculate appreciation
            $initialValue = $initialIndex['value'];
            $currentValue = $currentIndex['value'];
            
            error_log("Initial index value: {$initialValue}");
            error_log("Current index value: {$currentValue}");
            
            if ($initialValue == 0) {
                error_log("Initial index value is zero, cannot calculate appreciation");
                return false;
            }
            
            $appreciationRate = ($currentValue - $initialValue) / $initialValue;
            $appreciation = $property['initial_valuation'] * $appreciationRate;
            $shareAppreciation = $appreciation * ($property['agreed_pct'] / 100);
            
            error_log("Calculated appreciation rate: " . ($appreciationRate * 100) . "%");
            error_log("Calculated appreciation: {$appreciation}");
            error_log("Calculated share appreciation: {$shareAppreciation}");
            
            return [
                'appreciation' => $appreciation,
                'share_appreciation' => $shareAppreciation,
                'appreciation_rate' => $appreciationRate * 100
            ];
        } catch (Exception $e) {
            error_log("Error in calculatePropertyAppreciation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public function updatePropertyValuation($propertyId, $valuationDate) {
        try {
            // Get property details
            $stmt = $this->db->prepare("
                SELECT 
                    initial_valuation,
                    initial_index,
                    initial_index_date
                FROM properties
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$propertyId]);
            $property = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$property) {
                throw new Exception("Property not found or inactive");
            }

            error_log("Property Details: " . print_r([
                'initial_valuation' => $property['initial_valuation'],
                'initial_index' => $property['initial_index'],
                'initial_index_date' => $property['initial_index_date']
            ], true));
            
            // Get the current index value
            $stmt = $this->db->prepare("
                SELECT value 
                FROM home_price_index 
                WHERE date <= ?
                ORDER BY date DESC
                LIMIT 1
            ");
            $stmt->execute([$valuationDate]);
            $currentIndex = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentIndex) {
                throw new Exception("No index value found for date: {$valuationDate}");
            }

            error_log("Current Index Value: " . print_r($currentIndex, true));
            
            // Calculate difference and appreciation
            $indexValue = $currentIndex['value'];
            $initialIndex = $property['initial_index'];
            
            // Calculate difference as percentage: ((current - initial) / initial) * 100
            $difference = (($indexValue - $initialIndex) / $initialIndex) * 100;
            
            // Calculate appreciation in dollars
            $appreciation = ($difference / 100) * $property['initial_valuation'];

            error_log("Calculations: " . print_r([
                'indexValue' => $indexValue,
                'initialIndex' => $initialIndex,
                'difference' => $difference,
                'appreciation' => $appreciation
            ], true));
            
            // Check if valuation already exists for this date
            $stmt = $this->db->prepare("
                SELECT id FROM property_valuations 
                WHERE property_id = ? AND valuation_date = ?
            ");
            $stmt->execute([$propertyId, $valuationDate]);
            $existingValuation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingValuation) {
                // Update existing valuation
                $stmt = $this->db->prepare("
                    UPDATE property_valuations 
                    SET index_value = ?,
                        initial_index = ?,
                        diference = ?,
                        appreciation = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $indexValue,
                    $initialIndex,
                    $difference,
                    $appreciation,
                    $existingValuation['id']
                ]);

                error_log("Updated existing valuation: " . print_r([
                    'property_id' => $propertyId,
                    'valuation_date' => $valuationDate,
                    'index_value' => $indexValue,
                    'initial_index' => $initialIndex,
                    'diference' => $difference,
                    'appreciation' => $appreciation
                ], true));
            } else {
                // Insert new valuation
                $stmt = $this->db->prepare("
                    INSERT INTO property_valuations (
                        property_id, valuation_date, index_value,
                        initial_index, diference, appreciation
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $propertyId,
                    $valuationDate,
                    $indexValue,
                    $initialIndex,
                    $difference,
                    $appreciation
                ]);

                error_log("Inserted new valuation: " . print_r([
                    'property_id' => $propertyId,
                    'valuation_date' => $valuationDate,
                    'index_value' => $indexValue,
                    'initial_index' => $initialIndex,
                    'diference' => $difference,
                    'appreciation' => $appreciation
                ], true));
            }
            
            // Log the activity
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, details)
                VALUES (?, 'update', 'valuation', ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                "Updated property valuation for property ID {$propertyId} with appreciation {$appreciation}"
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Property Valuation Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getIndexComparison($propertyId) {
        // Get property details
        $stmt = $this->db->prepare("
            SELECT p.initial_valuation, p.effective_date, p.agreed_pct,
                   pv.valuation_date, pv.current_value
            FROM properties p
            LEFT JOIN property_valuations pv ON p.id = pv.property_id
            WHERE p.id = ?
            ORDER BY pv.valuation_date DESC
            LIMIT 1
        ");
        $stmt->execute([$propertyId]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$property) {
            return false;
        }
        
        // Get index values for comparison
        $stmt = $this->db->prepare("
            SELECT date, value
            FROM home_price_index
            WHERE date BETWEEN ? AND ?
            ORDER BY date ASC
        ");
        $stmt->execute([$property['effective_date'], $property['valuation_date']]);
        $indexData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate property appreciation rate
        $propertyAppreciation = ($property['current_value'] - $property['initial_valuation']) / $property['initial_valuation'] * 100;
        
        // Calculate index appreciation rate
        $initialIndex = $indexData[0]['value'];
        $finalIndex = $indexData[count($indexData) - 1]['value'];
        $indexAppreciation = ($finalIndex - $initialIndex) / $initialIndex * 100;
        
        return [
            'property_appreciation' => $propertyAppreciation,
            'index_appreciation' => $indexAppreciation,
            'difference' => $propertyAppreciation - $indexAppreciation,
            'index_data' => $indexData
        ];
    }
} 