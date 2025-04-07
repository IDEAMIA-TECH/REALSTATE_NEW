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

// Handle filters
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'action' => $_GET['action'] ?? null,
    'entity_type' => $_GET['entity_type'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null
];

// Build query with filters
$query = "
    SELECT al.*, u.username as user_name
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";

$params = [];

if ($filters['user_id']) {
    $query .= " AND al.user_id = ?";
    $params[] = $filters['user_id'];
}

if ($filters['action']) {
    $query .= " AND al.action = ?";
    $params[] = $filters['action'];
}

if ($filters['entity_type']) {
    $query .= " AND al.entity_type = ?";
    $params[] = $filters['entity_type'];
}

if ($filters['start_date']) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $filters['start_date'];
}

if ($filters['end_date']) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $filters['end_date'];
}

$query .= " ORDER BY al.created_at DESC";

// Get activity logs
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$actions = $db->query("SELECT DISTINCT action FROM activity_log")->fetchAll(PDO::FETCH_COLUMN);
$entityTypes = $db->query("SELECT DISTINCT entity_type FROM activity_log")->fetchAll(PDO::FETCH_COLUMN);
$users = $db->query("SELECT id, username FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4">Activity Log</h1>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filters['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="action" class="form-label">Action</label>
                        <select class="form-select" id="action" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo $action; ?>" <?php echo $filters['action'] == $action ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="entity_type" class="form-label">Entity Type</label>
                        <select class="form-select" id="entity_type" name="entity_type">
                            <option value="">All Types</option>
                            <?php foreach ($entityTypes as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $filters['entity_type'] == $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
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
        
        <!-- Activity Log Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity Type</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($log['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($log['entity_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No activity logs found</td>
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