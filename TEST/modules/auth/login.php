<?php
// Start session at the very beginning
session_start();

// Load required files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/AuthController.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if ($_SESSION['role'] === 'admin') {
        header('Location: ' . BASE_URL . '/modules/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/modules/auth/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $auth = new AuthController();
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            // Redirect based on user role
            if ($_SESSION['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/modules/admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/modules/auth/dashboard.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Playfair Display', 'Segoe UI', serif;
            color: var(--text-color);
        }

        .login-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            min-height: 100vh;
            align-items: center;
        }

        .login-content {
            display: flex;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px var(--shadow-color);
            overflow: hidden;
            width: 100%;
        }

        .login-image {
            flex: 1;
            background: linear-gradient(rgba(74, 55, 40, 0.8), rgba(74, 55, 40, 0.8)), 
                        url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
        }

        .login-form {
            flex: 1;
            padding: 60px;
            background: white;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 30px;
            filter: brightness(0.9);
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }

        .login-header p {
            color: var(--secondary-color);
            font-size: 18px;
            font-weight: 300;
        }

        .form-control {
            margin-bottom: 25px;
            padding: 15px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            height: 55px;
            font-size: 16px;
            background-color: #FFFFFF;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem var(--shadow-color);
        }

        .input-group-text {
            height: 55px;
            border-radius: 12px 0 0 12px;
            background-color: var(--background-color);
            border: 2px solid var(--border-color);
            border-right: none;
            padding: 0 20px;
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            background-color: var(--primary-color);
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 20px;
        }

        .btn-login:hover {
            background-color: var(--hover-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--shadow-color);
        }

        .feature-list {
            margin-top: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: white;
            font-size: 18px;
        }

        .feature-item i {
            margin-right: 15px;
            color: var(--accent-color);
            font-size: 24px;
        }

        .alert {
            margin-bottom: 25px;
            border-radius: 12px;
            padding: 20px;
            border: none;
        }

        .alert-danger {
            background-color: #FDF2F2;
            color: #9B1C1C;
            border-left: 4px solid #9B1C1C;
        }

        .alert-success {
            background-color: #F3FAF7;
            color: #03543F;
            border-left: 4px solid #03543F;
        }

        @media (max-width: 768px) {
            .login-content {
                flex-direction: column;
            }
            
            .login-image {
                min-height: 300px;
                padding: 40px;
            }
            
            .login-form {
                padding: 40px;
            }

            .feature-list {
                margin-top: 30px;
            }

            .feature-item {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-content">
            <div class="login-image">
                <h2>Welcome to <?php echo APP_NAME; ?></h2>
                <p>Elevating Real Estate Management to New Heights</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-home"></i>
                        <span>Premium Property Portfolio Management</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Real-Time Market Analytics</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-handshake"></i>
                        <span>Seamless Client Experience</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Investment Management</span>
                    </div>
                </div>
            </div>
            <div class="login-form">
                <div class="login-header">
                    <img src="<?php echo BASE_URL; ?>/assets/images/parker-logo.png" alt="PARKER Logo" class="login-logo">
                    <p>Access your account to manage your properties</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 