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
    SELECT 
        p.id,
        p.address as title,
        p.address as location,
        p.initial_valuation as price,
        p.status,
        p.created_at
    FROM properties p 
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC 
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
        :root {
            --primary-color: #4A3728;
            --secondary-color: #8B7355;
            --accent-color: #D2B48C;
            --background-color: #FAF6F1;
            --text-color: #4A3728;
            --border-color: #D2B48C;
            --hover-color: #6B4423;
            --shadow-color: rgba(74, 55, 40, 0.1);
        }

        body {
            background-color: var(--background-color);
            font-family: 'Playfair Display', 'Segoe UI', serif;
            color: var(--text-color);
        }

        .dashboard-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .dashboard-hero h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .stat-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Playfair Display', serif;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 500;
        }

        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .activity-card h3 {
            color: var(--primary-color);
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--accent-color);
        }

        .activity-item {
            padding: 1.2rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background-color: var(--background-color);
            border-radius: 10px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .property-image {
            height: 250px;
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
            background: linear-gradient(to top, rgba(0,0,0,0.5), transparent);
        }

        .property-details {
            padding: 2rem;
            background: white;
        }

        .property-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.8rem;
            font-family: 'Playfair Display', serif;
        }

        .property-location {
            color: var(--secondary-color);
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .property-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Playfair Display', serif;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .action-button {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .action-button:hover .action-icon,
        .action-button:hover .action-label {
            color: white;
        }

        .action-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.2rem;
            transition: all 0.3s ease;
        }

        .action-label {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            font-family: 'Playfair Display', serif;
        }

        @media (max-width: 768px) {
            .dashboard-hero {
                padding: 3rem 0;
                margin-bottom: 2rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .property-image {
                height: 200px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-hero">
            <div class="container">
                <h1><i class="fas fa-tachometer-alt me-2"></i>Welcome to Your Dashboard</h1>
                <p class="lead">Managing Excellence in Real Estate, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
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
                                <div class="property-title"><?php echo htmlspecialchars($property['title'] ?? 'No Address'); ?></div>
                                <div class="property-location">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($property['location'] ?? 'No Location'); ?>
                                </div>
                                <div class="property-price">
                                    $<?php echo number_format(floatval($property['price'] ?? 0), 2); ?>
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