<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get statistics for dashboard
$stats = [
    'clients' => $db->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn(),
    'properties' => $db->query("SELECT COUNT(*) FROM properties WHERE status = 'active'")->fetchColumn(),
    'users' => $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'valuations' => $db->query("SELECT COUNT(*) FROM property_valuations")->fetchColumn()
];

// Get valuation statistics
$valuationStats = $db->query("
    SELECT 
        COUNT(DISTINCT property_id) as total_properties,
        COUNT(*) as total_valuations,
        AVG(appreciation) as avg_appreciation,
        MAX(appreciation) as max_appreciation,
        MIN(appreciation) as min_appreciation
    FROM property_valuations
    WHERE valuation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch(PDO::FETCH_ASSOC);

// Get recent activity
$recentActivity = $db->query("
    SELECT a.*, u.username 
    FROM activity_log a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent properties
$recentProperties = $db->query("
    SELECT 
        p.id,
        p.address,
        p.initial_valuation,
        p.status,
        p.created_at
    FROM properties p 
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent valuations
$recentValuations = $db->query("
    SELECT 
        pv.*,
        p.address,
        p.initial_valuation
    FROM property_valuations pv
    JOIN properties p ON pv.property_id = p.id
    ORDER BY pv.valuation_date DESC, pv.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get home price index data for the last 6 months
$sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
$indexData = $db->query("
    SELECT date, value 
    FROM home_price_index 
    WHERE date >= '$sixMonthsAgo' 
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the chart
$chartLabels = [];
$chartValues = [];
foreach ($indexData as $data) {
    $chartLabels[] = date('M Y', strtotime($data['date']));
    $chartValues[] = $data['value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0F4B35;
            --secondary-color: #15BE77;
            --accent-color: #86D789;
            --background-color: #FFFFFF;
            --text-color: #1E1E1E;
            --border-color: #E8F3F1;
            --hover-color: #0D3D2C;
            --shadow-color: rgba(15, 75, 53, 0.1);
            --gradient-start: #15BE77;
            --gradient-end: #0F4B35;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-hero {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.1)"/></svg>') no-repeat center;
            opacity: 0.1;
        }

        .dashboard-hero h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .dashboard-hero p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--border-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-color);
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.7;
        }

        .activity-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .activity-card h3 {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: var(--border-color);
            border-radius: 8px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .property-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .property-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .property-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(to top, rgba(15, 75, 53, 0.8), transparent);
        }

        .property-details {
            padding: 1.5rem;
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .property-location {
            color: var(--text-color);
            font-size: 0.9rem;
            opacity: 0.7;
            margin-bottom: 1rem;
        }

        .property-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-button {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--text-color);
        }

        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px var(--shadow-color);
            border-color: var(--secondary-color);
            color: var(--text-color);
            text-decoration: none;
        }

        .action-icon {
            width: 50px;
            height: 50px;
            background: var(--border-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin: 0 auto 1rem;
            transition: all 0.3s ease;
        }

        .action-button:hover .action-icon {
            background: var(--secondary-color);
            color: white;
        }

        .action-label {
            font-weight: 600;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .dashboard-hero {
                padding: 2rem 0;
                margin-bottom: 1.5rem;
            }

            .dashboard-hero h1 {
                font-size: 2rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .property-image {
                height: 150px;
            }
        }

        .valuation-stat {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .valuation-stat .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .valuation-stat .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .valuation-stat i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-hero">
            <div class="container">
                <h1>Welcome Back!</h1>
                <p>Track your real estate portfolio and performance metrics</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="<?php echo BASE_URL; ?>/modules/admin/users.php" class="action-button">
                <div class="action-icon"><i class="fas fa-users"></i></div>
                <div class="action-label">Manage Users</div>
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/admin/clients.php" class="action-button">
                <div class="action-icon"><i class="fas fa-user-tie"></i></div>
                <div class="action-label">Manage Clients</div>
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/admin/properties.php" class="action-button">
                <div class="action-icon"><i class="fas fa-building"></i></div>
                <div class="action-label">Manage Properties</div>
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/admin/reports.php" class="action-button">
                <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="action-label">View Reports</div>
            </a>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['clients']); ?></div>
                    <div class="stat-label">Active Clients</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-home"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['properties']); ?></div>
                    <div class="stat-label">Active Properties</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['valuations']); ?></div>
                    <div class="stat-label">Total Valuations</div>
                </div>
            </div>
        </div>

        <!-- Valuation Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-number"><?php echo number_format($valuationStats['total_valuations']); ?></div>
                    <div class="stat-label">Monthly Valuations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <div class="stat-number"><?php echo number_format($valuationStats['avg_appreciation'], 2); ?>%</div>
                    <div class="stat-label">Avg. Appreciation</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-number"><?php echo number_format($valuationStats['max_appreciation'], 2); ?>%</div>
                    <div class="stat-label">Highest Appreciation</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-number"><?php echo number_format($valuationStats['min_appreciation'], 2); ?>%</div>
                    <div class="stat-label">Lowest Appreciation</div>
                </div>
            </div>
        </div>

        <!-- Valuation Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Valuation Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="valuation-stat">
                                    <div class="stat-value text-success">
                                        <i class="fas fa-arrow-up"></i>
                                        <?php echo number_format($valuationStats['max_appreciation'], 2); ?>%
                                    </div>
                                    <div class="stat-label">Highest Appreciation</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="valuation-stat">
                                    <div class="stat-value text-danger">
                                        <i class="fas fa-arrow-down"></i>
                                        <?php echo number_format($valuationStats['min_appreciation'], 2); ?>%
                                    </div>
                                    <div class="stat-label">Lowest Appreciation</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="valuation-stat">
                                    <div class="stat-value text-primary">
                                        <i class="fas fa-building"></i>
                                        <?php echo number_format($valuationStats['total_properties']); ?>
                                    </div>
                                    <div class="stat-label">Properties Tracked</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Valuations -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Recent Valuations
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Valuation Date</th>
                                        <th>Index Value</th>
                                        <th>Appreciation</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentValuations as $valuation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($valuation['address']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($valuation['valuation_date'])); ?></td>
                                            <td>$<?php echo number_format(floatval($valuation['index_value']), 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $valuation['appreciation'] >= 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo number_format($valuation['appreciation'], 2); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/modules/admin/properties.php?action=view&id=<?php echo $valuation['property_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Home Price Index Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Home Price Index - Last 6 Months
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="priceIndexChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Properties Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-building me-2"></i>
                            Recent Properties
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Address</th>
                                        <th>Initial Value</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentProperties as $property): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($property['address'] ?? 'No Address'); ?></td>
                                            <td>$<?php echo number_format(floatval($property['initial_valuation'] ?? 0), 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($property['status'] ?? 'unknown'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($property['created_at'] ?? 'now')); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/modules/admin/properties.php?action=view&id=<?php echo $property['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once INCLUDES_PATH . '/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Home Price Index Chart
        const ctx = document.getElementById('priceIndexChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Home Price Index',
                    data: <?php echo json_encode($chartValues); ?>,
                    borderColor: '#15BE77',
                    backgroundColor: 'rgba(21, 190, 119, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#15BE77',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Index Value: ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 