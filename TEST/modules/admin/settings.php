<?php
// Start session and load configuration
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';
$db = Database::getInstance()->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        try {
            // Start transaction
            $db->beginTransaction();

            // Update email settings in settings table
            $stmt = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_at)
                VALUES 
                    ('smtp_host', ?, NOW()),
                    ('smtp_port', ?, NOW()),
                    ('smtp_username', ?, NOW()),
                    ('smtp_password', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()
            ");

            // Execute for each email setting
            $emailSettings = [
                ['smtp_host', $_POST['smtp_host']],
                ['smtp_port', $_POST['smtp_port']],
                ['smtp_username', $_POST['smtp_username']],
                ['smtp_password', $_POST['smtp_password']]
            ];

            foreach ($emailSettings as $setting) {
                $stmt = $db->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        setting_value = VALUES(setting_value),
                        updated_at = NOW()
                ");
                $stmt->execute($setting);
            }

            // Log the activity
            $logStmt = $db->prepare("
                INSERT INTO activity_log (
                    user_id,
                    action,
                    entity_type,
                    entity_id,
                    details,
                    created_at
                ) VALUES (
                    ?,
                    'update_settings',
                    'system',
                    0,
                    ?,
                    NOW()
                )
            ");

            $logStmt->execute([
                $_SESSION['user_id'],
                json_encode([
                    'updated_settings' => [
                        'smtp_host' => $_POST['smtp_host'],
                        'smtp_port' => $_POST['smtp_port'],
                        'smtp_username' => $_POST['smtp_username'],
                        'smtp_password' => '********' // No registrar la contraseña real
                    ]
                ])
            ]);

            // Commit transaction
            $db->commit();
            $message = 'Settings updated successfully';

        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            $error = 'Error updating settings: ' . $e->getMessage();
        }
    }
}

// Get current settings from database
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $error = 'Error loading settings: ' . $e->getMessage();
    $settings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-container {
            padding: 20px;
        }
        .settings-card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container settings-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>System Settings</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_settings">
            
            <!-- General Settings -->
            <div class="card settings-card">
                <div class="card-header">
                    <h5 class="mb-0">General Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="app_name" class="form-label">Application Name</label>
                        <input type="text" class="form-control" id="app_name" name="app_name" 
                               value="<?php echo htmlspecialchars(APP_NAME); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="base_url" class="form-label">Base URL</label>
                        <input type="text" class="form-control" id="base_url" name="base_url" 
                               value="<?php echo htmlspecialchars(BASE_URL); ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div class="card settings-card">
                <div class="card-header">
                    <h5 class="mb-0">Email Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="smtp_port" class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="smtp_username" class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                               value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="smtp_password" class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                               value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Security Settings -->
            <div class="card settings-card">
                <div class="card-header">
                    <h5 class="mb-0">Security Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                        <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                               value="<?php echo htmlspecialchars(SESSION_TIMEOUT ?? '30'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="max_login_attempts" class="form-label">Maximum Login Attempts</label>
                        <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                               value="<?php echo htmlspecialchars(MAX_LOGIN_ATTEMPTS ?? '5'); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 