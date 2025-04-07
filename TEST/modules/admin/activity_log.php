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
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        .page-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .filters-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-label {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .filter-control {
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            width: 100%;
            transition: var(--transition);
        }

        .filter-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(41, 128, 185, 0.25);
        }

        .btn-filter {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
        }

        .btn-filter:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-clear {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
        }

        .btn-clear:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .activity-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }

        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .activity-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .activity-date {
            color: #666;
            font-size: 0.9rem;
        }

        .activity-badges {
            display: flex;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .badge-action {
            background: rgba(41, 128, 185, 0.1);
            color: var(--primary-color);
        }

        .badge-entity {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .activity-details {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--border-radius);
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
            <h1 class="text-white"><i class="fas fa-history me-2"></i>Activity Log</h1>
            <p class="lead text-white">Track and monitor all system activities</p>
        </div>
    </div>

    <div class="container">
        <div class="filters-section">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">User</label>
                        <select class="filter-control" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filters['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Action</label>
                        <select class="filter-control" id="action" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo $action; ?>" <?php echo $filters['action'] == $action ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Entity Type</label>
                        <select class="filter-control" id="entity_type" name="entity_type">
                            <option value="">All Types</option>
                            <?php foreach ($entityTypes as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $filters['entity_type'] == $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">Start Date</label>
                        <input type="date" class="filter-control" id="start_date" name="start_date" 
                               value="<?php echo $filters['start_date']; ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="filter-group">
                        <label class="filter-label">End Date</label>
                        <input type="date" class="filter-control" id="end_date" name="end_date" 
                               value="<?php echo $filters['end_date']; ?>">
                    </div>
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
                <i class="fas fa-history"></i>
                <h4>No activity logs found</h4>
                <p>Try adjusting your filters or check back later</p>
            </div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-user">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($log['user_name'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <h5 class="user-name"><?php echo htmlspecialchars($log['user_name']); ?></h5>
                                <span class="activity-date">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="activity-badges">
                            <span class="badge badge-action">
                                <i class="fas fa-bolt me-1"></i>
                                <?php echo ucfirst($log['action']); ?>
                            </span>
                            <span class="badge badge-entity">
                                <i class="fas fa-cube me-1"></i>
                                <?php echo ucfirst($log['entity_type']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="activity-details">
                        <?php echo htmlspecialchars($log['details']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 