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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .navbar {
            background: var(--primary-color);
            padding: 1rem 0;
            box-shadow: 0 4px 12px var(--shadow-color);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            color: var(--background-color) !important;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: 0.5px;
        }

        .navbar-brand img {
            height: 50px;
            width: auto;
            margin-right: 15px;
            filter: brightness(0) invert(1);
            transition: all 0.3s ease;
        }

        .navbar-dark .navbar-nav .nav-link {
            color: var(--accent-color);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            position: relative;
        }

        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link.active {
            color: var(--background-color);
        }

        .navbar-dark .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--accent-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-dark .navbar-nav .nav-link:hover::after,
        .navbar-dark .navbar-nav .nav-link.active::after {
            width: 80%;
        }

        .dropdown-menu {
            background: var(--background-color);
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-color);
            padding: 1rem 0;
            margin-top: 10px;
        }

        .dropdown-item {
            color: var(--text-color);
            padding: 0.7rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: var(--primary-color);
            color: var(--background-color);
        }

        .dropdown-item i {
            color: var(--secondary-color);
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        .dropdown-item:hover i {
            color: var(--accent-color);
        }

        .dropdown-divider {
            border-color: var(--border-color);
            opacity: 0.1;
            margin: 0.5rem 0;
        }

        .navbar-toggler {
            border: 2px solid var(--accent-color);
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
            border-color: var(--background-color);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(210, 180, 140, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.5rem;
            }

            .navbar-brand img {
                height: 40px;
            }

            .dropdown-menu {
                border-radius: 0;
                box-shadow: none;
                background: transparent;
                padding: 0;
            }

            .dropdown-item {
                color: var(--accent-color);
                padding: 0.5rem 1rem;
            }

            .dropdown-item:hover {
                background: transparent;
                color: var(--background-color);
            }

            .dropdown-divider {
                border-color: var(--accent-color);
                opacity: 0.1;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>/assets/images/parker-logo.png" alt="PARKER Logo">
                <?php echo ($settings['app_name'] ?? APP_NAME); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cogs me-1"></i>Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/users.php">
                                            <i class="fas fa-users me-2"></i>Users
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/clients.php">
                                            <i class="fas fa-user-tie me-2"></i>Clients
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/properties.php">
                                            <i class="fas fa-building me-2"></i>Properties
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/reports.php">
                                            <i class="fas fa-chart-bar me-2"></i>Reports
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/activity_log.php">
                                            <i class="fas fa-history me-2"></i>Activity Log
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/backup.php">
                                            <i class="fas fa-database me-2"></i>Backup
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/logs.php">
                                            <i class="fas fa-file-alt me-2"></i>System Logs
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
                                        <i class="fas fa-user me-2"></i>Profile
                                    </a>
                                </li>
                                <?php if ($_SESSION['role'] !== 'property_owner' && $_SESSION['role'] !== 'view_only'): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/admin/settings.php">
                                        <i class="fas fa-cog me-2"></i>Settings
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
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
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/modules/auth/register.php">
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