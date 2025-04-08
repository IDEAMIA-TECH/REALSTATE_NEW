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