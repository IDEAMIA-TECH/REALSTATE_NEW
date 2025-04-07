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
    SELECT * FROM properties 
    WHERE status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
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
            <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            <p class="lead">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
    </div>

    <div class="container">
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

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="activity-card">
                    <h3><i class="fas fa-history me-2"></i>Recent Activity</h3>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                    <?php echo htmlspecialchars($activity['action']); ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Properties -->
            <div class="col-md-6">
                <div class="activity-card">
                    <h3><i class="fas fa-building me-2"></i>Recent Properties</h3>
                    <?php foreach ($recentProperties as $property): ?>
                        <div class="property-card mb-3">
                            <div class="property-image" 
                                 style="background-image: url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
                            </div>
                            <div class="property-details">
                                <div class="property-title"><?php echo htmlspecialchars($property['title']); ?></div>
                                <div class="property-location">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($property['location']); ?>
                                </div>
                                <div class="property-price">
                                    $<?php echo number_format($property['price'], 2); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 