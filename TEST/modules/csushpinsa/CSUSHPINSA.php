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
        // Get property details
        $stmt = $this->db->prepare("
            SELECT initial_valuation, effective_date, agreed_pct
            FROM properties
            WHERE id = ?
        ");
        $stmt->execute([$propertyId]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$property) {
            return false;
        }
        
        // Get index values
        $stmt = $this->db->prepare("
            SELECT value
            FROM home_price_index
            WHERE date IN (?, ?)
            ORDER BY date ASC
        ");
        $stmt->execute([$property['effective_date'], $valuationDate]);
        $indexValues = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($indexValues) !== 2) {
            return false;
        }
        
        // Calculate appreciation
        $initialIndex = $indexValues[0];
        $currentIndex = $indexValues[1];
        $appreciationRate = ($currentIndex - $initialIndex) / $initialIndex;
        
        $appreciation = $property['initial_valuation'] * $appreciationRate;
        $shareAppreciation = $appreciation * ($property['agreed_pct'] / 100);
        
        return [
            'appreciation' => $appreciation,
            'share_appreciation' => $shareAppreciation,
            'appreciation_rate' => $appreciationRate * 100
        ];
    }
    
    public function updatePropertyValuation($propertyId, $valuationDate) {
        $appreciation = $this->calculatePropertyAppreciation($propertyId, $valuationDate);
        
        if (!$appreciation) {
            return false;
        }
        
        // Get property details
        $stmt = $this->db->prepare("
            SELECT initial_valuation, agreed_pct, term, option_price
            FROM properties
            WHERE id = ?
        ");
        $stmt->execute([$propertyId]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate terminal value and projected payoff
        $currentValue = $property['initial_valuation'] + $appreciation['appreciation'];
        $terminalValue = $currentValue * (1 + ($appreciation['appreciation_rate'] / 100));
        $projectedPayoff = $terminalValue * ($property['agreed_pct'] / 100);
        $optionValuation = $projectedPayoff - $property['option_price'];
        
        // Store valuation
        $stmt = $this->db->prepare("
            INSERT INTO property_valuations (
                property_id, valuation_date, current_value, appreciation,
                share_appreciation, terminal_value, projected_payoff, option_valuation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $propertyId,
            $valuationDate,
            $currentValue,
            $appreciation['appreciation'],
            $appreciation['share_appreciation'],
            $terminalValue,
            $projectedPayoff,
            $optionValuation
        ]);
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