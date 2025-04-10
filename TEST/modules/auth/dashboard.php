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

// Calculate statistics
$totalProperties = count($properties);
$totalValue = 0;
$totalOptionPrice = 0;
$totalFees = 0;

foreach ($properties as $property) {
    $totalValue += $property['initial_valuation'];
    $totalOptionPrice += $property['option_price'];
    $totalFees += $property['total_fees'];
}

$averageAppreciation = $totalProperties > 0 ? $totalOptionPrice / $totalProperties : 0;

// Get role-specific dashboard items
$dashboardItems = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        .dashboard-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .activity-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }

        .activity-item:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .property-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            align-items: center;
            padding: 1rem;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .property-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }

        .property-icon i {
            font-size: 2rem;
            color: white;
        }

        .property-details {
            flex-grow: 1;
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .property-location {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .property-price {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .property-meta {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .property-meta-item {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.9rem;
        }

        .property-meta-item i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-button {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: none;
            width: 100%;
        }

        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .action-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .action-label {
            font-weight: 500;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="dashboard-hero">
        <div class="container">
            <h1 class="text-white"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            <p class="lead text-white">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
    </div>

    <div class="container">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <?php foreach ($dashboardItems as $item): ?>
                <a href="<?php echo $item['link']; ?>" class="action-button">
                    <div class="action-icon"><?php echo $item['icon']; ?></div>
                    <div class="action-label"><?php echo $item['title']; ?></div>
                    <small class="text-muted"><?php echo $item['description']; ?></small>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <a href="<?php echo BASE_URL . '/modules/auth/my_properties.php'; ?>">
                        <div class="stat-icon"><i class="fas fa-home"></i></div>
                        <div class="stat-number"><?php echo $totalProperties; ?></div>
                        <div class="stat-label">My Properties</div>
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-number">$<?php echo number_format($totalValue, 2); ?></div>
                    <div class="stat-label">Total Value</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-handshake"></i></div>
                    <div class="stat-number">$<?php echo number_format($totalOptionPrice, 2); ?></div>
                    <div class="stat-label">Option Price</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="stat-number">$<?php echo number_format($totalFees, 2); ?></div>
                    <div class="stat-label">Total Fees</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="activity-card">
                    <h3><i class="fas fa-history me-2"></i>Recent Activity</h3>
                    <?php if (count($properties) > 0): ?>
                        <?php foreach (array_slice($properties, 0, 5) as $property): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($property['street_address'] . ', ' . $property['city'] . ', ' . $property['state'] . ' ' . $property['zip_code']); ?></strong>
                                        <div class="text-muted">
                                            Effective Date: <?php echo date('M d, Y', strtotime($property['effective_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-primary">
                                            $<?php echo number_format($property['initial_valuation'], 2); ?>
                                        </div>
                                        <div class="text-muted">
                                            Term: <?php echo $property['term']; ?> months
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <p class="text-muted">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Properties -->
            <div class="col-md-6">
                <div class="activity-card">
                    <h3><i class="fas fa-building me-2"></i>Recent Properties</h3>
                    <?php if (count($properties) > 0): ?>
                        <?php foreach (array_slice($properties, 0, 3) as $property): ?>
                            <div class="property-card mb-3">
                                <div class="property-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="property-details">
                                    <div class="property-title"><?php echo htmlspecialchars($property['street_address'] . ', ' . $property['city'] . ', ' . $property['state'] . ' ' . $property['zip_code']); ?></div>
                                    <div class="property-meta">
                                        <div class="property-meta-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('M d, Y', strtotime($property['effective_date'])); ?>
                                        </div>
                                        <div class="property-meta-item">
                                            <i class="fas fa-clock"></i>
                                            <?php echo $property['term']; ?> months
                                        </div>
                                    </div>
                                    <div class="property-price">
                                        $<?php echo number_format($property['initial_valuation'], 2); ?>
                                        <small class="text-primary ms-2">
                                            <?php echo $property['agreed_pct']; ?>% agreed
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="property-card mb-3">
                            <div class="property-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="property-details">
                                <div class="property-title">No Properties Yet</div>
                                <div class="property-location">
                                    <i class="fas fa-plus-circle me-1"></i>
                                    Add your first property
                                </div>
                                <div class="property-price">
                                    $0.00
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 