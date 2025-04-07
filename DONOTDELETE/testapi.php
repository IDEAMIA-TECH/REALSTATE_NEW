<?php

// Database configuration
$dbHost = 'localhost';
$dbUsername = 'ideamiadev_realestate';
$dbPassword = 'fKoQ5HrJCn3?T#N!';
$dbName = 'ideamiadev_realestate';

// Create database connection
try {
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8", $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbName");
    $pdo->exec("USE $dbName");
    
    // Create table for housing price index data
    $createTableSQL = "CREATE TABLE IF NOT EXISTS housing_price_index (
        id INT AUTO_INCREMENT PRIMARY KEY,
        observation_date DATE NOT NULL,
        index_value DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_date (observation_date)
    )";
    $pdo->exec($createTableSQL);
    
    echo "Database and table created successfully.<br>";
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Your FRED API key
$apiKey = 'bf7de8e5b6c4328f21855e67a5bdd8f2';

// API endpoint and parameters
$url = 'https://api.stlouisfed.org/fred/series/observations';
$params = [
    'series_id' => 'CSUSHPINSA',
    'api_key' => $apiKey,
    'file_type' => 'json'
];

// Build the full URL with query string
$fullUrl = $url . '?' . http_build_query($params);

// Use cURL to make the API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);

// Prepare insert statement
$insertStmt = $pdo->prepare("INSERT IGNORE INTO housing_price_index (observation_date, index_value) VALUES (:date, :value)");

// Check if data is returned and insert into database
if (isset($data['observations'])) {
    echo "<h2>Inserting data into database...</h2>";
    $insertedCount = 0;
    $totalCount = count($data['observations']);
    
    try {
        $pdo->beginTransaction();
        
        foreach ($data['observations'] as $observation) {
            if ($observation['value'] !== '.') {  // Skip entries with no value
                $insertStmt->execute([
                    ':date' => $observation['date'],
                    ':value' => $observation['value']
                ]);
                $insertedCount++;
            }
        }
        
        $pdo->commit();
        echo "Successfully inserted/updated $insertedCount out of $totalCount records.<br>";
        
        // Display the latest records from database
        $query = "SELECT * FROM housing_price_index ORDER BY observation_date DESC LIMIT 10";
        $result = $pdo->query($query);
        
        echo "<h2>Latest 10 records in database:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Date</th><th>Index Value</th><th>Created At</th></tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['observation_date'] . "</td>";
            echo "<td>" . $row['index_value'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        echo "Error inserting data: " . $e->getMessage();
    }
} else {
    echo "No data found or there was an error with the API.";
}
?>