<?php
// Start session and load configuration
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ReportExporter.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';
$reports = [];
$db = Database::getInstance()->getConnection();

// Handle report generation and exports
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $reportType = $_POST['report_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        
        try {
            switch ($reportType) {
                case 'property_valuation':
                    // Property Valuation Report
                    $stmt = $db->prepare("
                        SELECT 
                            p.id,
                            p.address,
                            p.initial_valuation,
                            p.agreed_pct,
                            pv.valuation_date,
                            pv.current_value,
                            pv.appreciation,
                            pv.share_appreciation,
                            pv.terminal_value,
                            pv.projected_payoff,
                            pv.option_valuation
                        FROM properties p
                        LEFT JOIN property_valuations pv ON p.id = pv.property_id
                        WHERE pv.valuation_date BETWEEN ? AND ?
                        ORDER BY pv.valuation_date DESC
                    ");
                    $stmt->execute([$startDate, $endDate]);
                    $reports['property_valuation'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'client_activity':
                    // Client Activity Report
                    $stmt = $db->prepare("
                        SELECT 
                            c.id,
                            c.name,
                            c.email,
                            COUNT(p.id) as property_count,
                            SUM(p.initial_valuation) as total_valuation,
                            al.action,
                            al.created_at
                        FROM clients c
                        LEFT JOIN properties p ON c.id = p.client_id
                        LEFT JOIN activity_log al ON c.id = al.entity_id AND al.entity_type = 'client'
                        WHERE al.created_at BETWEEN ? AND ?
                        GROUP BY c.id
                        ORDER BY al.created_at DESC
                    ");
                    $stmt->execute([$startDate, $endDate]);
                    $reports['client_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'csushpinsa':
                    // CSUSHPINSA Index Report
                    $stmt = $db->prepare("
                        SELECT 
                            date,
                            value
                        FROM home_price_index
                        WHERE date BETWEEN ? AND ?
                        ORDER BY date ASC
                    ");
                    $stmt->execute([$startDate, $endDate]);
                    $reports['csushpinsa'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'user_activity':
                    // User Activity Report
                    $stmt = $db->prepare("
                        SELECT 
                            u.username,
                            u.email,
                            u.role,
                            COUNT(al.id) as action_count,
                            al.action,
                            al.entity_type,
                            al.created_at
                        FROM users u
                        LEFT JOIN activity_log al ON u.id = al.user_id
                        WHERE al.created_at BETWEEN ? AND ?
                        GROUP BY u.id, al.action
                        ORDER BY al.created_at DESC
                    ");
                    $stmt->execute([$startDate, $endDate]);
                    $reports['user_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
            }
            
            // Handle export requests
            if (isset($_POST['export_type'])) {
                $exportType = $_POST['export_type'];
                $reportData = $reports[$reportType] ?? [];
                
                if (!empty($reportData)) {
                    $title = ucwords(str_replace('_', ' ', $reportType)) . ' Report';
                    $exporter = new ReportExporter($reportData, $reportType, $title);
                    
                    if ($exportType === 'excel') {
                        $exporter->exportToExcel();
                    } elseif ($exportType === 'pdf') {
                        $exporter->exportToPDF();
                    }
                }
            }
            
            $message = 'Report generated successfully';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reports-container {
            padding: 20px;
        }
        .report-card {
            margin-bottom: 20px;
        }
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
        .export-buttons {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container reports-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Reports</h1>
            <?php if (!empty($reports)): ?>
                <div class="export-buttons">
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="generate_report">
                        <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($_POST['report_type'] ?? ''); ?>">
                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                        <input type="hidden" name="export_type" value="excel">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </form>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="generate_report">
                        <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($_POST['report_type'] ?? ''); ?>">
                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                        <input type="hidden" name="export_type" value="pdf">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="mb-4">
            <input type="hidden" name="action" value="generate_report">
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">Select a report type</option>
                            <option value="property_valuation">Property Valuation Report</option>
                            <option value="client_activity">Client Activity Report</option>
                            <option value="csushpinsa">CSUSHPINSA Index Report</option>
                            <option value="user_activity">User Activity Report</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> Generate Report
                </button>
            </div>
        </form>
        
        <!-- Report Results -->
        <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $type => $data): ?>
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo ucwords(str_replace('_', ' ', $type)); ?> Report</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($type === 'property_valuation'): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Property ID</th>
                                            <th>Address</th>
                                            <th>Initial Value</th>
                                            <th>Current Value</th>
                                            <th>Appreciation</th>
                                            <th>Share Appreciation</th>
                                            <th>Terminal Value</th>
                                            <th>Projected Payoff</th>
                                            <th>Option Valuation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                                <td>$<?php echo number_format($row['initial_valuation'], 2); ?></td>
                                                <td>$<?php echo number_format($row['current_value'], 2); ?></td>
                                                <td>$<?php echo number_format($row['appreciation'], 2); ?></td>
                                                <td>$<?php echo number_format($row['share_appreciation'], 2); ?></td>
                                                <td>$<?php echo number_format($row['terminal_value'], 2); ?></td>
                                                <td>$<?php echo number_format($row['projected_payoff'], 2); ?></td>
                                                <td>$<?php echo number_format($row['option_valuation'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($type === 'client_activity'): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Client ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Properties</th>
                                            <th>Total Valuation</th>
                                            <th>Last Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td><?php echo htmlspecialchars($row['property_count']); ?></td>
                                                <td>$<?php echo number_format($row['total_valuation'], 2); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($type === 'csushpinsa'): ?>
                            <div class="chart-container">
                                <canvas id="csushpinsaChart"></canvas>
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
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                                <td><?php echo number_format($row['value'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($type === 'user_activity'): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Action</th>
                                            <th>Entity Type</th>
                                            <th>Action Count</th>
                                            <th>Last Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                                <td><?php echo htmlspecialchars($row['action']); ?></td>
                                                <td><?php echo htmlspecialchars($row['entity_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['action_count']); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize CSUSHPINSA chart if data exists
        <?php if (isset($reports['csushpinsa'])): ?>
            const ctx = document.getElementById('csushpinsaChart').getContext('2d');
            const dates = <?php echo json_encode(array_column($reports['csushpinsa'], 'date')); ?>;
            const values = <?php echo json_encode(array_column($reports['csushpinsa'], 'value')); ?>;
            
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
                    maintainAspectRatio: false
                }
            });
        <?php endif; ?>
    </script>
</body>
</html> 