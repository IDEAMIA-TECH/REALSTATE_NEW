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
    <style>
        .dashboard-container {
            padding: 20px;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .dashboard-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .logout-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container dashboard-container">
        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($userData['username']); ?>!</h1>
            <p class="lead">You are logged in as <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        </div>
        
        <div class="row">
            <?php foreach ($dashboardItems as $item): ?>
                <div class="col-md-4 mb-4">
                    <a href="<?php echo $item['link']; ?>" class="text-decoration-none">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <div class="card-icon"><?php echo $item['icon']; ?></div>
                                <h5 class="card-title"><?php echo $item['title']; ?></h5>
                                <p class="card-text text-muted"><?php echo $item['description']; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" class="btn btn-danger logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 