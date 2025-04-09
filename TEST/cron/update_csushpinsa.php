<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/csushpinsa/CSUSHPINSA.php';

// --- Last Tuesday Validation ---
$today = new DateTime();
$month = (int)$today->format('m');
$nextWeek = (int)$today->modify('+7 days')->format('m');

if ($month !== $nextWeek) {
    // Today is the last Tuesday of the month
    // Reset date back to today (since we moved it with modify())
    $today = new DateTime();
    if ($today->format('N') != 2) {
        error_log("Not Tuesday – skipping CSUSHPINSA update.");
        return;
    }
} else {
    error_log("Not the last Tuesday of the month – skipping CSUSHPINSA update.");
    return;
}

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
    error_log("CSUSHPINSA data updated successfully from $startDate to $endDate");
} else {
    error_log("Failed to update CSUSHPINSA data");
}