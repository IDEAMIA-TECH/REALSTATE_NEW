<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../csushpinsa/CSUSHPINSA.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

$csushpinsa = new CSUSHPINSA();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'fetch_data':
                $startDate = $_POST['start_date'] ?? null;
                $endDate = $_POST['end_date'] ?? null;
                
                if ($csushpinsa->fetchHistoricalData($startDate, $endDate)) {
                    $message = 'CSUSHPINSA data fetched and stored successfully';
                } else {
                    $error = 'Failed to fetch CSUSHPINSA data';
                }
                break;
                
            case 'update_valuation':
                $propertyId = $_POST['property_id'] ?? null;
                $valuationDate = $_POST['valuation_date'] ?? date('Y-m-d');
                
                if ($csushpinsa->updatePropertyValuation($propertyId, $valuationDate)) {
                    $message = 'Property valuation updated successfully';
                } else {
                    $error = 'Failed to update property valuation';
                }
                break;
        }
    }
}

// Get historical data for display
$historicalData = $csushpinsa->getHistoricalData();

// Get properties for valuation update
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, address FROM properties WHERE status = 'active'");
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSUSHPINSA Integration - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            height: 400px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container mt-4">
        <h1>CSUSHPINSA Integration</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Fetch Historical Data</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="fetch_data">
                            
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                            
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download"></i> Fetch Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Update Property Valuation</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_valuation">
                            
                            <div class="mb-3">
                                <label for="property_id" class="form-label">Property</label>
                                <select class="form-select" id="property_id" name="property_id" required>
                                    <option value="">Select a property</option>
                                    <?php foreach ($properties as $property): ?>
                                        <option value="<?php echo $property['id']; ?>">
                                            <?php echo htmlspecialchars($property['address']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="valuation_date" class="form-label">Valuation Date</label>
                                <input type="date" class="form-control" id="valuation_date" name="valuation_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calculator"></i> Update Valuation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">CSUSHPINSA Index History</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="indexChart"></canvas>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Index Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historicalData as $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['date']); ?></td>
                                    <td><?php echo number_format($data['value'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize chart
        const ctx = document.getElementById('indexChart').getContext('2d');
        const dates = <?php echo json_encode(array_column($historicalData, 'date')); ?>;
        const values = <?php echo json_encode(array_column($historicalData, 'value')); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'CSUSHPINSA Index',
                    data: values,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>
</html> 