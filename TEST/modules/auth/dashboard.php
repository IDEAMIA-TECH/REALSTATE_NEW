<?php
// Start session and load configuration
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/User.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

// Get user information
$user = new User();
$userData = $user->getById($_SESSION['user_id']);

// Get user's properties
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT 
        p.*,
        c.name as client_name,
        pv.current_value,
        pv.appreciation_percentage,
        pv.valuation_date
    FROM properties p
    LEFT JOIN clients c ON p.client_id = c.id
    LEFT JOIN property_valuations pv ON p.id = pv.property_id
    WHERE p.client_id = :client_id
    ORDER BY p.created_at DESC
");
$stmt->execute([':client_id' => $userData['client_id']]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalProperties = count($properties);
$totalValue = 0;
$totalAppreciation = 0;
$totalValuations = 0;

foreach ($properties as $property) {
    $totalValue += $property['current_value'] ?? 0;
    $totalAppreciation += $property['appreciation_percentage'] ?? 0;
    if ($property['valuation_date']) {
        $totalValuations++;
    }
}

$averageAppreciation = $totalProperties > 0 ? $totalAppreciation / $totalProperties : 0;

// Get role-specific dashboard items
$dashboardItems = [];
switch ($_SESSION['role']) {
    case 'admin':
        $dashboardItems = [
            [
                'title' => 'User Management',
                'icon' => '👥',
                'link' => BASE_URL . '/modules/admin/users.php',
                'description' => 'Manage system users and roles'
            ],
            [
                'title' => 'System Settings',
                'icon' => '⚙️',
                'link' => BASE_URL . '/modules/admin/settings.php',
                'description' => 'Configure system settings'
            ],
            [
                'title' => 'Reports',
                'icon' => '📊',
                'link' => BASE_URL . '/modules/admin/reports.php',
                'description' => 'View system reports and analytics'
            ]
        ];
        break;
    case 'property_owner':
        $dashboardItems = [
            [
                'title' => 'My Properties',
                'icon' => '🏠',
                'link' => BASE_URL . '/modules/properties/list.php',
                'description' => 'Manage your property listings'
            ],
            [
                'title' => 'Client Management',
                'icon' => '👥',
                'link' => BASE_URL . '/modules/clients/list.php',
                'description' => 'Manage your clients'
            ],
            [
                'title' => 'Property Valuations',
                'icon' => '💰',
                'link' => BASE_URL . '/modules/valuations/list.php',
                'description' => 'View property valuations'
            ]
        ];
        break;
    case 'view_only':
        $dashboardItems = [
            [
                'title' => 'My Properties',
                'icon' => '🏠',
                'link' => BASE_URL . '/modules/properties/view.php',
                'description' => 'View your properties'
            ],
            [
                'title' => 'Property Valuations',
                'icon' => '💰',
                'link' => BASE_URL . '/modules/valuations/view.php',
                'description' => 'View property valuations'
            ],
            [
                'title' => 'My Profile',
                'icon' => '👤',
                'link' => BASE_URL . '/modules/profile/view.php',
                'description' => 'Manage your profile'
            ]
        ];
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        .dashboard-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .activity-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }

        .activity-item:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .property-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .property-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }

        .property-details {
            padding: 1.5rem;
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .property-location {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .property-price {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-button {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: none;
            width: 100%;
        }

        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .action-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .action-label {
            font-weight: 500;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="dashboard-hero">
        <div class="container">
            <h1 class="text-white"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            <p class="lead text-white">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
    </div>

    <div class="container">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <?php foreach ($dashboardItems as $item): ?>
                <a href="<?php echo $item['link']; ?>" class="action-button">
                    <div class="action-icon"><?php echo $item['icon']; ?></div>
                    <div class="action-label"><?php echo $item['title']; ?></div>
                    <small class="text-muted"><?php echo $item['description']; ?></small>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-home"></i></div>
                    <div class="stat-number"><?php echo $totalProperties; ?></div>
                    <div class="stat-label">My Properties</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-number">$<?php echo number_format($totalValue, 2); ?></div>
                    <div class="stat-label">Total Value</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <div class="stat-number"><?php echo number_format($averageAppreciation, 2); ?>%</div>
                    <div class="stat-label">Avg. Appreciation</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-number"><?php echo $totalValuations; ?></div>
                    <div class="stat-label">Valuations</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="activity-card">
                    <h3><i class="fas fa-history me-2"></i>Recent Activity</h3>
                    <?php if (count($properties) > 0): ?>
                        <?php foreach (array_slice($properties, 0, 5) as $property): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($property['address']); ?></strong>
                                        <div class="text-muted">
                                            Last valuation: <?php echo date('M d, Y', strtotime($property['valuation_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-success">
                                            <?php echo number_format($property['appreciation_percentage'], 2); ?>%
                                        </div>
                                        <div class="text-muted">
                                            $<?php echo number_format($property['current_value'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <p class="text-muted">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Properties -->
            <div class="col-md-6">
                <div class="activity-card">
                    <h3><i class="fas fa-building me-2"></i>Recent Properties</h3>
                    <?php if (count($properties) > 0): ?>
                        <?php foreach (array_slice($properties, 0, 3) as $property): ?>
                            <div class="property-card mb-3">
                                <div class="property-image" 
                                     style="background-image: url('<?php echo $property['image_url'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80'; ?>');">
                                </div>
                                <div class="property-details">
                                    <div class="property-title"><?php echo htmlspecialchars($property['address']); ?></div>
                                    <div class="property-location">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                                    </div>
                                    <div class="property-price">
                                        $<?php echo number_format($property['current_value'], 2); ?>
                                        <small class="text-success ms-2">
                                            <?php echo number_format($property['appreciation_percentage'], 2); ?>%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="property-card mb-3">
                            <div class="property-image" 
                                 style="background-image: url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
                            </div>
                            <div class="property-details">
                                <div class="property-title">No Properties Yet</div>
                                <div class="property-location">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    Add your first property
                                </div>
                                <div class="property-price">
                                    $0.00
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 