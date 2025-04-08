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
                    // Property Valuation Report - Latest valuation for each property
                    $stmt = $db->prepare("
                        SELECT 
                            p.id,
                            p.address,
                            p.initial_valuation,
                            p.initial_index,
                            p.agreed_pct,
                            p.option_price,
                            p.total_fees,
                            pv.valuation_date,
                            pv.index_value,
                            pv.diference,
                            pv.appreciation
                        FROM properties p
                        INNER JOIN (
                            SELECT property_id, MAX(valuation_date) as max_date
                            FROM property_valuations
                            GROUP BY property_id
                        ) latest ON p.id = latest.property_id
                        INNER JOIN property_valuations pv ON p.id = pv.property_id 
                            AND pv.valuation_date = latest.max_date
                        WHERE pv.valuation_date BETWEEN ? AND ?
                        ORDER BY pv.valuation_date DESC
                    ");
                    
                    // Debug logging
                    error_log("Property Valuation Report - Date Range: " . $startDate . " to " . $endDate);
                    
                    $stmt->execute([$startDate, $endDate]);
                    $reports['property_valuation'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Debug logging
                    error_log("Property Valuation Report - Number of records: " . count($reports['property_valuation']));
                    if (count($reports['property_valuation']) === 0) {
                        error_log("Property Valuation Report - No records found. Checking if there are any valuations in the date range...");
                        $checkStmt = $db->prepare("
                            SELECT COUNT(*) as count 
                            FROM (
                                SELECT property_id, MAX(valuation_date) as max_date
                                FROM property_valuations
                                GROUP BY property_id
                            ) latest
                            INNER JOIN property_valuations pv ON pv.property_id = latest.property_id 
                                AND pv.valuation_date = latest.max_date
                            WHERE pv.valuation_date BETWEEN ? AND ?
                        ");
                        $checkStmt->execute([$startDate, $endDate]);
                        $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
                        error_log("Property Valuation Report - Total latest valuations in date range: " . $count);
                    }
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
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        .page-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .report-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .report-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .export-button {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            border: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .export-button:hover {
            transform: translateY(-2px);
        }

        .btn-excel {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .btn-pdf {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .chart-container {
            height: 300px;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #eee;
            font-weight: 600;
            color: var(--primary-color);
        }

        .table td {
            vertical-align: middle;
        }

        .form-control, .form-select {
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            padding: 0.75rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .report-summary {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            flex: 1;
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .summary-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="page-hero">
        <div class="container">
            <h1 class="text-white"><i class="fas fa-chart-bar me-2"></i>Reports</h1>
            <p class="lead text-white">Generate and analyze detailed reports</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="report-card">
            <form method="POST" action="">
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
                        <i class="fas fa-chart-bar me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Report Results -->
        <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $type => $data): ?>
                <div class="report-card">
                    <div class="report-header">
                        <h5 class="report-title">
                            <i class="fas fa-file-alt me-2"></i>
                            <?php echo ucwords(str_replace('_', ' ', $type)); ?> Report
                        </h5>
                        <div class="export-buttons">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="generate_report">
                                <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($type); ?>">
                                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                <input type="hidden" name="export_type" value="excel">
                                <button type="submit" class="export-button btn-excel">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </form>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="generate_report">
                                <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($type); ?>">
                                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                <input type="hidden" name="export_type" value="pdf">
                                <button type="submit" class="export-button btn-pdf">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($type === 'property_valuation'): ?>
                        <?php if (empty($data)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No property valuations found for the selected date range.
                                <?php if (isset($count) && $count > 0): ?>
                                    <br>Note: There are <?php echo $count; ?> valuations in the database, but none match the selected criteria.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="report-summary">
                                <div class="summary-card">
                                    <div class="summary-value">
                                        $<?php echo number_format(array_sum(array_column($data, 'initial_valuation')), 2); ?>
                                    </div>
                                    <div class="summary-label">Total Initial Valuation</div>
                                </div>
                                <div class="summary-card">
                                    <div class="summary-value">
                                        <?php 
                                        $totalInitialValue = array_sum(array_column($data, 'initial_valuation'));
                                        $totalAppreciation = array_sum(array_column($data, 'appreciation'));
                                        $totalCurrentValue = $totalInitialValue + $totalAppreciation;
                                        echo '$' . number_format($totalCurrentValue, 2); 
                                        ?>
                                    </div>
                                    <div class="summary-label">Total Current Value</div>
                                </div>
                                <div class="summary-card">
                                    <div class="summary-value">
                                        <?php 
                                        $totalInitialValue = array_sum(array_column($data, 'initial_valuation'));
                                        $totalAppreciation = array_sum(array_column($data, 'appreciation'));
                                        $appreciationRate = $totalInitialValue > 0 ? 
                                            ($totalAppreciation / $totalInitialValue) * 100 : 0;
                                        echo number_format($appreciationRate, 2); 
                                        ?>%
                                    </div>
                                    <div class="summary-label">Average Appreciation</div>
                                </div>
                            </div>
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Property</th>
                                                <th>Initial Value</th>
                                                <th>Initial Index</th>
                                                <th>Current Index</th>
                                                <th>Difference</th>
                                                <th>Appreciation</th>
                                                <th>Share Appreciation</th>
                                                <th>Option Price</th>
                                                <th>Total Fees</th>
                                                <th>Calculation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data as $row): ?>
                                                <?php
                                                $initialValue = floatval($row['initial_valuation']);
                                                $initialIndex = floatval($row['initial_index']);
                                                $currentIndex = floatval($row['index_value']);
                                                $difference = $initialIndex > 0 ? (($currentIndex - $initialIndex) / $initialIndex) * 100 : 0;
                                                $appreciation = $initialValue * ($difference / 100);
                                                $shareAppreciation = $appreciation * ($row['agreed_pct'] / 100);
                                                $calculation = $row['option_price'] + $shareAppreciation + $row['total_fees'];
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                                                    <td>$<?php echo number_format($initialValue, 2); ?></td>
                                                    <td><?php echo number_format($initialIndex, 2); ?></td>
                                                    <td><?php echo number_format($currentIndex, 2); ?></td>
                                                    <td><?php echo number_format($difference, 2); ?>%</td>
                                                    <td>$<?php echo number_format($appreciation, 2); ?></td>
                                                    <td>$<?php echo number_format($shareAppreciation, 2); ?></td>
                                                    <td>$<?php echo number_format($row['option_price'], 2); ?></td>
                                                    <td>$<?php echo number_format($row['total_fees'], 2); ?></td>
                                                    <td>$<?php echo number_format($calculation, 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($type === 'client_activity'): ?>
                        <div class="report-summary">
                            <div class="summary-card">
                                <div class="summary-value">
                                    <?php echo count($data); ?>
                                </div>
                                <div class="summary-label">Total Clients</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-value">
                                    <?php echo array_sum(array_column($data, 'property_count')); ?>
                                </div>
                                <div class="summary-label">Total Properties</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-value">
                                    $<?php echo number_format(array_sum(array_column($data, 'total_valuation')), 2); ?>
                                </div>
                                <div class="summary-label">Total Valuation</div>
                            </div>
                        </div>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Email</th>
                                            <th>Properties</th>
                                            <th>Total Valuation</th>
                                            <th>Last Activity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
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
                        </div>
                    <?php elseif ($type === 'csushpinsa'): ?>
                        <div class="chart-container">
                            <canvas id="csushpinsaChart"></canvas>
                        </div>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Index Value</th>
                                            <th>Change</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $previousValue = null;
                                        foreach ($data as $row): 
                                            $change = $previousValue !== null ? 
                                                (($row['value'] - $previousValue) / $previousValue * 100) : 0;
                                            $previousValue = $row['value'];
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                                <td><?php echo number_format($row['value'], 2); ?></td>
                                                <td class="<?php echo $change >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $change >= 0 ? '+' : ''; ?>
                                                    <?php echo number_format($change, 2); ?>%
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php elseif ($type === 'user_activity'): ?>
                        <div class="report-summary">
                            <div class="summary-card">
                                <div class="summary-value">
                                    <?php echo count(array_unique(array_column($data, 'username'))); ?>
                                </div>
                                <div class="summary-label">Active Users</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-value">
                                    <?php echo array_sum(array_column($data, 'action_count')); ?>
                                </div>
                                <div class="summary-label">Total Actions</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-value">
                                    <?php echo count(array_unique(array_column($data, 'action'))); ?>
                                </div>
                                <div class="summary-label">Unique Actions</div>
                            </div>
                        </div>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
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
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="client-avatar me-2">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></div>
                                                            <div class="small text-muted"><?php echo htmlspecialchars($row['email']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $row['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                                        <?php echo ucfirst(htmlspecialchars($row['role'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['action']); ?></td>
                                                <td><?php echo htmlspecialchars($row['entity_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['action_count']); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
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
                        borderColor: 'rgb(52, 152, 219)',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>
</html> 