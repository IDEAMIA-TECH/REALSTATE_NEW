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

// Check if system_logs table exists, if not create it
try {
    $db->query("SELECT 1 FROM system_logs LIMIT 1");
} catch (PDOException $e) {
    // Create system_logs table
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('error', 'warning', 'info', 'debug') NOT NULL,
            message TEXT NOT NULL,
            context TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_level (level),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $db->exec($createTableSQL);
        $message = "System logs table created successfully";
    } catch (PDOException $e) {
        $error = "Failed to create system logs table: " . $e->getMessage();
    }
}

// Handle filters
$filters = [
    'level' => $_GET['level'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'search' => $_GET['search'] ?? null
];

// Build query with filters
$query = "
    SELECT * FROM system_logs
    WHERE 1=1
";

$params = [];

if ($filters['level']) {
    $query .= " AND level = ?";
    $params[] = $filters['level'];
}

if ($filters['start_date']) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $filters['start_date'];
}

if ($filters['end_date']) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $filters['end_date'];
}

if ($filters['search']) {
    $query .= " AND (message LIKE ? OR context LIKE ?)";
    $searchTerm = "%{$filters['search']}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY created_at DESC";

// Get logs
try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique log levels
    $levels = $db->query("SELECT DISTINCT level FROM system_logs")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Error fetching logs: " . $e->getMessage();
    $logs = [];
    $levels = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - <?php echo APP_NAME; ?></title>
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

        .log-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
            border-left: 4px solid;
        }

        .log-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        .log-card.error {
            border-left-color: #dc3545;
        }

        .log-card.warning {
            border-left-color: #ffc107;
        }

        .log-card.info {
            border-left-color: #17a2b8;
        }

        .log-card.debug {
            border-left-color: #6c757d;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .log-level {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }

        .log-level.error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .log-level.warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .log-level.info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .log-level.debug {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .log-timestamp {
            color: #666;
            font-size: 0.9rem;
        }

        .log-message {
            margin-bottom: 1rem;
            color: #333;
        }

        .log-context {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .filters-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            padding: 0.75rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-filter {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-filter:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-clear {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-clear:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .no-logs {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-logs i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="page-hero">
        <div class="container">
            <h1 class="text-white"><i class="fas fa-clipboard-list me-2"></i>System Logs</h1>
            <p class="lead text-white">Monitor and analyze system activities and events</p>
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

        <div class="filters-card">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="level" class="form-label">Log Level</label>
                    <select class="form-select" id="level" name="level">
                        <option value="">All Levels</option>
                        <?php foreach ($levels as $level): ?>
                            <option value="<?php echo $level; ?>" <?php echo $filters['level'] == $level ? 'selected' : ''; ?>>
                                <?php echo ucfirst($level); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $filters['start_date']; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $filters['end_date']; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                           placeholder="Search in messages...">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-filter">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-clear">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <?php if (empty($logs)): ?>
            <div class="no-logs">
                <i class="fas fa-clipboard-list"></i>
                <h4>No logs found</h4>
                <p>Try adjusting your filters or check back later</p>
            </div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <div class="log-card <?php echo $log['level']; ?>">
                    <div class="log-header">
                        <span class="log-level <?php echo $log['level']; ?>">
                            <?php echo ucfirst($log['level']); ?>
                        </span>
                        <span class="log-timestamp">
                            <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="log-message">
                        <?php echo htmlspecialchars($log['message']); ?>
                    </div>
                    
                    <?php if ($log['context']): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#contextModal<?php echo $log['id']; ?>">
                            <i class="fas fa-eye me-2"></i>View Context
                        </button>
                        
                        <!-- Context Modal -->
                        <div class="modal fade" id="contextModal<?php echo $log['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Log Context</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <pre class="log-context"><?php echo htmlspecialchars($log['context']); ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 