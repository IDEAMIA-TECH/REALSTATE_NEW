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
    <style>
        .log-level {
            font-weight: bold;
        }
        .log-level.error { color: #dc3545; }
        .log-level.warning { color: #ffc107; }
        .log-level.info { color: #17a2b8; }
        .log-level.debug { color: #6c757d; }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4">System Logs</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
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
                               value="<?php echo htmlspecialchars($filters['search']); ?>" 
                               placeholder="Search in messages...">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Level</th>
                                <th>Message</th>
                                <th>Context</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <span class="log-level <?php echo $log['level']; ?>">
                                            <?php echo ucfirst($log['level']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['message']); ?></td>
                                    <td>
                                        <?php if ($log['context']): ?>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#contextModal<?php echo $log['id']; ?>">
                                                <i class="fas fa-eye"></i> View
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
                                                            <pre><?php echo htmlspecialchars($log['context']); ?></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No logs found</td>
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