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

// Handle backup deletion
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $file = __DIR__ . '/../../backups/' . basename($_GET['file']);
    if (file_exists($file) && is_file($file)) {
        if (unlink($file)) {
            $message = "Backup deleted successfully.";
            
            // Log the activity
            $stmt = $db->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, details)
                VALUES (?, 'delete', 'backup', ?)
            ");
            $stmt->execute([$_SESSION['user_id'], "Backup deleted: " . basename($file)]);
        } else {
            $error = "Failed to delete backup file.";
        }
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
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        .page-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .backup-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .backup-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        .backup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .backup-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .backup-meta {
            display: flex;
            gap: 1rem;
            color: #666;
            font-size: 0.9rem;
        }

        .backup-size {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .backup-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            border: none;
        }

        .action-button:hover {
            transform: scale(1.1);
        }

        .btn-download {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .create-backup-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .create-backup-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .create-backup-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .create-backup-description {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .btn-create-backup {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
        }

        .btn-create-backup:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .no-backups {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-backups i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        .alert {
            border-radius: var(--border-radius);
            border: none;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="page-hero">
        <div class="container">
            <h1 class="text-white"><i class="fas fa-database me-2"></i>Database Backup</h1>
            <p class="lead text-white">Manage and download your database backups</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="create-backup-section">
            <i class="fas fa-database create-backup-icon"></i>
            <h2 class="create-backup-title">Create New Backup</h2>
            <p class="create-backup-description">
                Create a new backup of your database to ensure your data is safe and secure.
            </p>
            <form method="POST" action="">
                <button type="submit" name="create_backup" class="btn btn-create-backup">
                    <i class="fas fa-plus me-2"></i>Create Backup
                </button>
            </form>
        </div>

        <h3 class="h4 mb-4">Existing Backups</h3>

        <?php if (empty($backups)): ?>
            <div class="no-backups">
                <i class="fas fa-database"></i>
                <h4>No backups found</h4>
                <p>Create your first backup to get started</p>
            </div>
        <?php else: ?>
            <?php foreach ($backups as $backup): ?>
                <div class="backup-card">
                    <div class="backup-header">
                        <h5 class="backup-title">
                            <i class="fas fa-file-alt me-2"></i>
                            <?php echo htmlspecialchars($backup['filename']); ?>
                        </h5>
                        <div class="action-buttons">
                            <a href="?download=1&file=<?php echo urlencode($backup['filename']); ?>" 
                               class="action-button btn-download" title="Download Backup">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?delete=1&file=<?php echo urlencode($backup['filename']); ?>" 
                               class="action-button btn-delete" 
                               title="Delete Backup"
                               onclick="return confirm('Are you sure you want to delete this backup? This action cannot be undone.');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="backup-meta">
                        <div class="backup-size">
                            <i class="fas fa-hdd"></i>
                            <?php echo number_format($backup['size'] / 1024, 2) . ' KB'; ?>
                        </div>
                        <div class="backup-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo $backup['date']; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 