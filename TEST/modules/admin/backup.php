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
$message = '';
$error = '';

// Handle backup creation
if (isset($_POST['create_backup'])) {
    try {
        // Get database configuration
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $name = DB_NAME;
        
        // Create backup directory if it doesn't exist
        $backupDir = __DIR__ . '/../../backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Generate filename with timestamp
        $filename = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Create backup command
        $command = "mysqldump --host={$host} --user={$user} --password={$pass} {$name} > {$filename}";
        
        // Execute backup
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0) {
            $message = "Backup created successfully: " . basename($filename);
            
            // Log the activity
            $stmt = $db->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, details)
                VALUES (?, 'create', 'backup', ?)
            ");
            $stmt->execute([$_SESSION['user_id'], "Database backup created: " . basename($filename)]);
        } else {
            throw new Exception("Failed to create backup. Please check database credentials and permissions.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle backup download
if (isset($_GET['download']) && isset($_GET['file'])) {
    $file = __DIR__ . '/../../backups/' . basename($_GET['file']);
    if (file_exists($file) && is_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        $error = "Backup file not found.";
    }
}

// Get list of existing backups
$backups = [];
$backupDir = __DIR__ . '/../../backups';
if (file_exists($backupDir)) {
    $files = glob($backupDir . '/backup_*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    // Sort backups by date, newest first
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4">Database Backup</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Create Backup Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Create New Backup</h5>
                <form method="POST" action="">
                    <button type="submit" name="create_backup" class="btn btn-primary">
                        <i class="fas fa-database"></i> Create Backup
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Existing Backups -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Existing Backups</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Date</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                    <td><?php echo $backup['date']; ?></td>
                                    <td><?php echo number_format($backup['size'] / 1024, 2) . ' KB'; ?></td>
                                    <td>
                                        <a href="?download=1&file=<?php echo urlencode($backup['filename']); ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($backups)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No backups found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 