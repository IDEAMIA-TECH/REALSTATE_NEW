<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background-color: #34495e;
        }
        .nav-links a.active {
            background-color: #3498db;
        }
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="<?php echo BASE_URL; ?>" class="logo"><?php echo APP_NAME; ?></a>
            <nav class="nav-links">
                <a href="<?php echo BASE_URL; ?>/client_form.html" <?php echo $current_page === 'client_form.html' ? 'class="active"' : ''; ?>>Client Registration</a>
                <a href="<?php echo BASE_URL; ?>/property_form.html" <?php echo $current_page === 'property_form.html' ? 'class="active"' : ''; ?>>Property Registration</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>/logout">Logout</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="main-content"> 