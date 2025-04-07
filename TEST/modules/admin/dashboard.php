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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4">Admin Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Clients</h5>
                        <h2 class="card-text"><?php echo $stats['clients']; ?></h2>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/clients.php" class="text-white">
                            <i class="fas fa-users"></i> Manage Clients
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Properties</h5>
                        <h2 class="card-text"><?php echo $stats['properties']; ?></h2>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/properties.php" class="text-white">
                            <i class="fas fa-home"></i> Manage Properties
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Users</h5>
                        <h2 class="card-text"><?php echo $stats['users']; ?></h2>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/users.php" class="text-white">
                            <i class="fas fa-user-cog"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Valuations</h5>
                        <h2 class="card-text"><?php echo $stats['valuations']; ?></h2>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/reports.php" class="text-white">
                            <i class="fas fa-chart-line"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Access Links -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-cogs"></i> System Management
                        </h5>
                        <div class="list-group">
                            <a href="<?php echo BASE_URL; ?>/modules/admin/users.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user-cog"></i> User Management
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/admin/clients.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-users"></i> Client Management
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/admin/properties.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-home"></i> Property Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar"></i> Reports & Analytics
                        </h5>
                        <div class="list-group">
                            <a href="<?php echo BASE_URL; ?>/modules/admin/reports.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-alt"></i> Property Reports
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/admin/csushpinsa.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-line"></i> CSUSHPINSA Index
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/admin/activity_log.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-history"></i> Activity Log
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tools"></i> System Tools
                        </h5>
                        <div class="list-group">
                            <a href="<?php echo BASE_URL; ?>/modules/admin/settings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-cog"></i> System Settings
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/admin/backup.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-database"></i> Database Backup
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/admin/logs.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-alt"></i> System Logs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 