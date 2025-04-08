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
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Plus Jakarta Sans', sans-serif;
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
            border-radius: 24px;
            box-shadow: 0 20px 40px var(--shadow-color);
            overflow: hidden;
            width: 100%;
        }

        .login-image {
            flex: 1;
            background: linear-gradient(rgba(15, 75, 53, 0.85), rgba(15, 75, 53, 0.85)), 
                        url('<?php echo BASE_URL; ?>/assets/images/investment-team.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
            position: relative;
        }

        .login-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to top, rgba(15, 75, 53, 0.95), transparent);
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
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .login-header p {
            color: var(--text-color);
            font-size: 16px;
            font-weight: 400;
            opacity: 0.8;
        }

        .form-control {
            margin-bottom: 25px;
            padding: 15px 20px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            height: 55px;
            font-size: 15px;
            background-color: #FFFFFF;
            color: var(--text-color);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(21, 190, 119, 0.1);
        }

        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 10px;
        }

        .input-group-text {
            height: 55px;
            border-radius: 12px 0 0 12px;
            background-color: var(--border-color);
            border: 2px solid var(--border-color);
            border-right: none;
            padding: 0 20px;
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            background-color: var(--secondary-color);
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            color: white;
            margin-top: 20px;
        }

        .btn-login:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--shadow-color);
        }

        .feature-list {
            margin-top: 40px;
            z-index: 1;
            position: relative;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            color: white;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .feature-item i {
            margin-right: 15px;
            color: var(--secondary-color);
            font-size: 24px;
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
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
                font-size: 14px;
                padding: 12px 15px;
            }

            .feature-item i {
                font-size: 20px;
                width: 35px;
                height: 35px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-content">
            <div class="login-image">
                <h2>Welcome to <?php echo APP_NAME; ?></h2>
                <p>Pioneering Strategies For Your Financial Success</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Smart Investment Planning</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Personalized Risk Management</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-handshake"></i>
                        <span>Strategic Portfolio Allocation</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Real-Time Market Analytics</span>
                    </div>
                </div>
            </div>
            <div class="login-form">
                <div class="login-header">
                    <img src="<?php echo BASE_URL; ?>/assets/images/parker-logo.png" alt="PARKER Logo" class="login-logo">
                    <h1>Welcome Back</h1>
                    <p>Enter your credentials to access your account</p>
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
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        Sign In <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 