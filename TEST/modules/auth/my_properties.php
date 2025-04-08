<?php
// Start session and load configuration
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/User.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

// Get user information
$user = new User();
$userData = $user->getById($_SESSION['user_id']);

// Only allow non-admin users
if ($userData['role'] === 'admin') {
    header('Location: ' . BASE_URL . '/modules/admin/dashboard.php');
    exit;
}

// Get user's properties
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT 
        p.*,
        c.name as client_name,
        p.initial_valuation as current_value,
        p.initial_index,
        p.effective_date as valuation_date,
        p.option_price,
        p.term,
        p.agreed_pct
    FROM properties p
    LEFT JOIN clients c ON p.client_id = c.id
    WHERE p.client_id = :client_id
    AND p.status = 'active'
    ORDER BY p.created_at DESC
");
$stmt->execute([':client_id' => $userData['client_id']]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        .property-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .property-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        .property-status {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.9rem;
        }

        .property-body {
            padding: 1.5rem;
        }

        .property-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .property-detail {
            display: flex;
            align-items: center;
        }

        .property-detail i {
            width: 2rem;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .property-detail-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .property-detail-value {
            font-weight: 600;
            color: var(--primary-color);
        }

        .property-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .property-actions .btn {
            padding: 0.5rem 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: var(--border-radius);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .view-toggle {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .view-toggle .btn {
            padding: 0.5rem 1rem;
        }

        .view-toggle .btn.active {
            background: var(--primary-color);
            color: white;
        }

        .table-view {
            display: none;
        }

        .table-view.active {
            display: block;
        }

        .card-view {
            display: none;
        }

        .card-view.active {
            display: block;
        }

        .property-table {
            width: 100%;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .property-table th {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
        }

        .property-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .property-table tr:last-child td {
            border-bottom: none;
        }

        .property-table tr:hover {
            background: rgba(52, 152, 219, 0.05);
        }

        .property-table .status-badge {
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-home me-2"></i>
                My Properties
            </h1>
            <div class="view-toggle">
                <button class="btn btn-outline-primary active" onclick="toggleView('card')">
                    <i class="fas fa-th-large me-2"></i>Cards
                </button>
                <button class="btn btn-outline-primary" onclick="toggleView('table')">
                    <i class="fas fa-table me-2"></i>Table
                </button>
            </div>
        </div>

        <?php if (count($properties) > 0): ?>
            <!-- Card View -->
            <div class="card-view active">
                <div class="row">
                    <?php foreach ($properties as $property): ?>
                        <div class="col-md-6">
                            <div class="property-card">
                                <div class="property-header">
                                    <h2 class="property-title"><?php echo htmlspecialchars($property['address']); ?></h2>
                                    <span class="property-status">Active</span>
                                </div>
                                <div class="property-body">
                                    <div class="property-details">
                                        <div class="property-detail">
                                            <i class="fas fa-dollar-sign"></i>
                                            <div>
                                                <div class="property-detail-label">Initial Value</div>
                                                <div class="property-detail-value">$<?php echo number_format($property['initial_valuation'], 2); ?></div>
                                            </div>
                                        </div>
                                        <div class="property-detail">
                                            <i class="fas fa-percentage"></i>
                                            <div>
                                                <div class="property-detail-label">Agreed Percentage</div>
                                                <div class="property-detail-value"><?php echo $property['agreed_pct']; ?>%</div>
                                            </div>
                                        </div>
                                        <div class="property-detail">
                                            <i class="fas fa-calendar-alt"></i>
                                            <div>
                                                <div class="property-detail-label">Effective Date</div>
                                                <div class="property-detail-value"><?php echo date('M d, Y', strtotime($property['effective_date'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="property-detail">
                                            <i class="fas fa-clock"></i>
                                            <div>
                                                <div class="property-detail-label">Term</div>
                                                <div class="property-detail-value"><?php echo $property['term']; ?> months</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="property-actions">
                                        <button class="btn btn-outline-primary" onclick="window.location.href='<?php echo BASE_URL; ?>/modules/properties/view.php?id=<?php echo $property['id']; ?>'">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Table View -->
            <div class="table-view">
                <table class="property-table">
                    <thead>
                        <tr>
                            <th>Address</th>
                            <th>Initial Value</th>
                            <th>Agreed %</th>
                            <th>Effective Date</th>
                            <th>Term</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $property): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($property['address']); ?></td>
                                <td>$<?php echo number_format($property['initial_valuation'], 2); ?></td>
                                <td><?php echo $property['agreed_pct']; ?>%</td>
                                <td><?php echo date('M d, Y', strtotime($property['effective_date'])); ?></td>
                                <td><?php echo $property['term']; ?> months</td>
                                <td><span class="status-badge">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="window.location.href='<?php echo BASE_URL; ?>/modules/properties/view.php?id=<?php echo $property['id']; ?>'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-home"></i>
                <h3>No Properties Yet</h3>
                <p>You haven't added any properties to your portfolio yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleView(view) {
        // Update buttons
        document.querySelectorAll('.view-toggle .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Update views
        document.querySelector('.card-view').classList.remove('active');
        document.querySelector('.table-view').classList.remove('active');
        document.querySelector(`.${view}-view`).classList.add('active');

        // Save preference
        localStorage.setItem('propertyView', view);
    }

    // Load saved view preference
    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('propertyView') || 'card';
        const button = document.querySelector(`.view-toggle .btn[onclick*="${savedView}"]`);
        if (button) {
            button.click();
        }
    });
    </script>
</body>
</html> 