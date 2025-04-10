<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current settings from database
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $settings = [];
}

// If no settings in database, use config.php values
if (empty($settings)) {
    $settings = [
        'app_name' => APP_NAME,
        'base_url' => BASE_URL
    ];
}

// Set default page title if not already set, with fallback to APP_NAME constant
if (!isset($page_title)) {
    $page_title = $settings['app_name'] ?? APP_NAME;
} else {
    $page_title = $page_title . ' - ' . ($settings['app_name'] ?? APP_NAME);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .navbar {
            background: var(--primary-color);
            padding: 1rem 0;
            box-shadow: 0 4px 20px var(--shadow-color);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }

        .navbar-brand img {
            height: 40px;
            width: auto;
            margin-right: 10px;
            filter: brightness(0) invert(1);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .nav-link:hover, .nav-link.active {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px var(--shadow-color);
            border-radius: 12px;
            padding: 1rem 0;
            margin-top: 0.5rem;
            background: var(--primary-color);
        }

        .dropdown-item {
            padding: 0.7rem 1.5rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .dropdown-item i {
            color: var(--secondary-color);
            width: 20px;
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            color: white;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
        }

        .dropdown-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 0.5rem 0;
        }

        @media (max-width: 768px) {
            .navbar-collapse {
                background: var(--primary-color);
                padding: 1rem;
                border-radius: 12px;
                box-shadow: 0 10px 30px var(--shadow-color);
                margin-top: 1rem;
            }

            .navbar-toggler-icon {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>/assets/images/parker-logo.png" alt="PARKER Logo">
                <?php echo ($settings['app_name'] ?? APP_NAME); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php">
                                <i class="fas fa-chart-line me-1"></i>Dashboard
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cogs me-1"></i>Management
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/users.php">
                                            <i class="fas fa-users"></i>Users
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/clients.php">
                                            <i class="fas fa-user-tie"></i>Clients
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/properties.php">
                                            <i class="fas fa-building"></i>Properties
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/reports.php">
                                            <i class="fas fa-chart-bar"></i>Reports
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/activity_logs.php">
                                            <i class="fas fa-history me-2"></i>Activity Logs
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/cancellation_fees.php">
                                            <i class="fas fa-dollar-sign me-2"></i>Cancellation Fees
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/backup.php">
                                            <i class="fas fa-database me-2"></i>System Backup
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/profile.php">
                                        <i class="fas fa-user"></i>Profile
                                    </a>
                                </li>
                                <?php if ($_SESSION['role'] !== 'property_owner' && $_SESSION['role'] !== 'view_only'): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/settings.php">
                                        <i class="fas fa-cog"></i>Settings
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/auth/logout.php">
                                        <i class="fas fa-sign-out-alt"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/modules/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="<?php echo BASE_URL; ?>/modules/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
</body>
</html> 