<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/User.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../csushpinsa/CSUSHPINSA.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Function to get the closest home price index for a given date
function getClosestHomePriceIndex($db, $targetDate) {
    // First try to get the exact date
    $stmt = $db->prepare("
        SELECT date, value 
        FROM home_price_index 
        WHERE date = ?
    ");
    $stmt->execute([$targetDate]);
    $exactMatch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exactMatch) {
        return $exactMatch;
    }
    
    // If no exact match, get the closest dates before and after
    $stmt = $db->prepare("
        (
            SELECT date, value, ABS(DATEDIFF(date, ?)) as diff
            FROM home_price_index 
            WHERE date < ?
            ORDER BY date DESC
            LIMIT 1
        )
        UNION ALL
        (
            SELECT date, value, ABS(DATEDIFF(date, ?)) as diff
            FROM home_price_index 
            WHERE date > ?
            ORDER BY date ASC
            LIMIT 1
        )
        ORDER BY diff ASC
        LIMIT 1
    ");
    $stmt->execute([$targetDate, $targetDate, $targetDate, $targetDate]);
    $closestMatch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $closestMatch;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    // Get the closest home price index for the effective date
                    $indexData = getClosestHomePriceIndex($db, $_POST['effective_date']);
                    $initial_index = $indexData ? $indexData['value'] : 0;
                    $index_date = $indexData ? $indexData['date'] : $_POST['effective_date'];

                    $stmt = $db->prepare("
                        INSERT INTO properties (
                            client_id, address, initial_valuation, agreed_pct,
                            total_fees, effective_date, term, option_price,
                            status, created_by, initial_index, initial_index_date
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $_POST['client_id'],
                        $_POST['address'],
                        $_POST['initial_valuation'],
                        $_POST['agreed_pct'],
                        $_POST['total_fees'],
                        $_POST['effective_date'],
                        $_POST['term'],
                        $_POST['option_price'],
                        $_SESSION['user_id'],
                        $initial_index,
                        $index_date
                    ]);
                    
                    $propertyId = $db->lastInsertId();
                    
                    // Create initial valuation
                    $csushpinsa = new CSUSHPINSA();
                    $csushpinsa->updatePropertyValuation($propertyId, $_POST['effective_date']);
                    
                    $message = 'Property created successfully';
                } catch (PDOException $e) {
                    $error = 'Error creating property: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $stmt = $db->prepare("
                        UPDATE properties SET
                            client_id = ?,
                            address = ?,
                            initial_valuation = ?,
                            agreed_pct = ?,
                            total_fees = ?,
                            effective_date = ?,
                            term = ?,
                            option_price = ?,
                            status = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['client_id'],
                        $_POST['address'],
                        $_POST['initial_valuation'],
                        $_POST['agreed_pct'],
                        $_POST['total_fees'],
                        $_POST['effective_date'],
                        $_POST['term'],
                        $_POST['option_price'],
                        $_POST['status'],
                        $_POST['property_id']
                    ]);
                    
                    $message = 'Property updated successfully';
                } catch (PDOException $e) {
                    $error = 'Error updating property: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $db->prepare("UPDATE properties SET status = 'archived' WHERE id = ?");
                    $stmt->execute([$_POST['property_id']]);
                    $message = 'Property archived successfully';
                } catch (PDOException $e) {
                    $error = 'Error archiving property: ' . $e->getMessage();
                }
                break;
            
            case 'cancel':
                try {
                    // Get the closest home price index for the cancellation date
                    $indexData = getClosestHomePriceIndex($db, $_POST['cancel_date']);
                    $closing_index = $indexData ? $indexData['value'] : 0;
                    $closing_index_date = $indexData ? $indexData['date'] : $_POST['cancel_date'];

                    $stmt = $db->prepare("
                        UPDATE properties SET
                            status = 'archived',
                            closing_index = ?,
                            closing_date = ?,
                            closing_index_date = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $closing_index,
                        $_POST['cancel_date'],
                        $closing_index_date,
                        $_POST['property_id']
                    ]);
                    
                    $message = 'Contract cancelled successfully';
                } catch (PDOException $e) {
                    $error = 'Error cancelling contract: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get clients for dropdown
$stmt = $db->query("SELECT id, name FROM clients WHERE status = 'active'");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get properties for listing
$stmt = $db->query("
    SELECT p.*, c.name as client_name
    FROM properties p
    LEFT JOIN clients c ON p.client_id = c.id
    ORDER BY p.created_at DESC
");
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - <?php echo APP_NAME; ?></title>
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

        .property-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .property-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .property-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .property-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-active {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .status-archived {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .property-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .property-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .property-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
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

        .btn-view {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .btn-edit {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .btn-cancel {
            background-color: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-title {
            font-weight: 600;
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

        .input-group-text {
            border-radius: var(--border-radius);
        }

        .document-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .document-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
        }

        .document-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .document-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(52, 152, 219, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3498db;
        }

        .document-actions {
            display: flex;
            gap: 0.5rem;
        }

        .debug-log-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 400px;
            max-height: 300px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 9999;
            overflow: hidden;
        }
        
        .debug-log-header {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .debug-log-content {
            padding: 10px;
            max-height: 250px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        
        .debug-entry {
            margin-bottom: 5px;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .debug-entry:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="page-hero">
        <div class="container">
            <h1 class="text-white"><i class="fas fa-home me-2"></i>Property Management</h1>
            <p class="lead text-white">Manage your real estate properties and valuations</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">All Properties</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPropertyModal">
                <i class="fas fa-plus me-2"></i>Add New Property
            </button>
        </div>

        <div class="row">
            <?php foreach ($properties as $property): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="property-card">
                        <div class="property-header">
                            <h5 class="property-title"><?php echo htmlspecialchars($property['address']); ?></h5>
                            <span class="property-status status-<?php echo htmlspecialchars($property['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($property['status'])); ?>
                            </span>
                        </div>

                        <div class="property-details">
                            <div class="property-value">
                                $<?php echo number_format($property['initial_valuation'], 2); ?>
                            </div>
                            <div class="property-meta">
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($property['client_name']); ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-percentage"></i>
                                    <?php echo number_format($property['agreed_pct'], 2); ?>%
                                </div>
                            </div>
                            <div class="property-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo htmlspecialchars($property['effective_date']); ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <?php echo htmlspecialchars($property['term']); ?> months
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar-times"></i>
                                    <?php 
                                        $effectiveDate = new DateTime($property['effective_date']);
                                        $expirationDate = clone $effectiveDate;
                                        $expirationDate->modify('+' . $property['term'] . ' months');
                                        echo $expirationDate->format('Y-m-d');
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="action-button btn-view" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#viewPropertyModal"
                                    data-property='<?php echo json_encode($property); ?>'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="action-button btn-edit" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editPropertyModal"
                                    data-property='<?php echo json_encode($property); ?>'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($property['status'] === 'active'): ?>
                            <button type="button" class="action-button btn-cancel"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelContractModal"
                                    data-property='<?php echo json_encode($property); ?>'>
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                            <button type="button" class="action-button btn-delete"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deletePropertyModal"
                                    data-property-id="<?php echo $property['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Create Property Modal -->
    <div class="modal fade" id="createPropertyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Client</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Select a client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="initial_valuation" class="form-label">Initial Valuation</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="initial_valuation" 
                                       name="initial_valuation" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="agreed_pct" class="form-label">Agreed Percentage</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="agreed_pct" 
                                       name="agreed_pct" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_fees" class="form-label">Total Fees</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="total_fees" 
                                       name="total_fees" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="effective_date" class="form-label">Effective Date</label>
                            <input type="date" class="form-control" id="effective_date" 
                                   name="effective_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="term" class="form-label">Term (months)</label>
                            <input type="number" class="form-control" id="term" 
                                   name="term" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="option_price" class="form-label">Option Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="option_price" 
                                       name="option_price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Property Modal -->
    <div class="modal fade" id="editPropertyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="property_id" id="edit_property_id">
                        
                        <div class="mb-3">
                            <label for="edit_client_id" class="form-label">Client</label>
                            <select class="form-select" id="edit_client_id" name="client_id" required>
                                <option value="">Select a client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="edit_address" name="address" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_initial_valuation" class="form-label">Initial Valuation</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="edit_initial_valuation" 
                                       name="initial_valuation" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_agreed_pct" class="form-label">Agreed Percentage</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit_agreed_pct" 
                                       name="agreed_pct" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_total_fees" class="form-label">Total Fees</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="edit_total_fees" 
                                       name="total_fees" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_effective_date" class="form-label">Effective Date</label>
                            <input type="date" class="form-control" id="edit_effective_date" 
                                   name="effective_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_term" class="form-label">Term (months)</label>
                            <input type="number" class="form-control" id="edit_term" 
                                   name="term" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_option_price" class="form-label">Option Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="edit_option_price" 
                                       name="option_price" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Property Modal -->
    <div class="modal fade" id="deletePropertyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Archive Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="property_id" id="delete_property_id">
                        <p>Are you sure you want to archive this property? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Archive Property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Property Modal -->
    <div class="modal fade" id="viewPropertyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Property Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Basic Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>ID:</th>
                                    <td id="view_id"></td>
                                </tr>
                                <tr>
                                    <th>Client:</th>
                                    <td id="view_client_name"></td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td id="view_address"></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td id="view_status"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Financial Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Initial Valuation:</th>
                                    <td id="view_initial_valuation"></td>
                                </tr>
                                <tr>
                                    <th>Initial Index:</th>
                                    <td id="view_initial_index"></td>
                                </tr>
                                <tr>
                                    <th>Initial Index Date:</th>
                                    <td id="view_initial_index_date"></td>
                                </tr>
                                <tr>
                                    <th>Agreed Percentage:</th>
                                    <td id="view_agreed_pct"></td>
                                </tr>
                                <tr>
                                    <th>Total Fees:</th>
                                    <td id="view_total_fees"></td>
                                </tr>
                                <tr>
                                    <th>Option Price:</th>
                                    <td id="view_option_price"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Contract Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Effective Date:</th>
                                    <td id="view_effective_date"></td>
                                </tr>
                                <tr>
                                    <th>Term:</th>
                                    <td id="view_term"></td>
                                </tr>
                                <tr>
                                    <th>Expiration Date:</th>
                                    <td id="view_expiration_date"></td>
                                </tr>
                                <tr>
                                    <th>Current Value:</th>
                                    <td id="view_current_value"></td>
                                </tr>
                                <tr>
                                    <th>User Profit:</th>
                                    <td id="view_user_profit"></td>
                                </tr>
                                <tr>
                                    <th>Closing Index:</th>
                                    <td id="view_closing_index"></td>
                                </tr>
                                <tr>
                                    <th>Closing Date:</th>
                                    <td id="view_closing_date"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6>Valuation History</h6>
                            <div id="valuation_history" class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Initial Value</th>
                                            <th>Beginning Index</th>
                                            <th>Update Index</th>
                                            <th>Difference</th>
                                            <th>Option Premium</th>
                                            <th>Appreciation</th>
                                            <th>Appreciation Participation</th>
                                            <th>Appreciation Share</th>
                                            <th>Calculation</th>
                                        </tr>
                                    </thead>
                                    <tbody id="valuationHistoryBody">
                                        <tr>
                                            <td colspan="9" class="text-center">Loading valuation history...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateValuationBtn">Update Valuation</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Contract Modal -->
    <div class="modal fade" id="cancelContractModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="property_id" id="cancel_property_id">
                        
                        <div class="mb-3">
                            <label for="cancel_date" class="form-label">Cancellation Date</label>
                            <input type="date" class="form-control" id="cancel_date" name="cancel_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Are you sure you want to cancel this contract? This action cannot be undone.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Cancel Contract</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/modules/csushpinsa/CSUSHPINSA.js"></script>
    <script>
        // Handle edit modal
        document.getElementById('editPropertyModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const property = JSON.parse(button.getAttribute('data-property'));
            
            document.getElementById('edit_property_id').value = property.id;
            document.getElementById('edit_client_id').value = property.client_id;
            document.getElementById('edit_address').value = property.address;
            document.getElementById('edit_initial_valuation').value = property.initial_valuation;
            document.getElementById('edit_agreed_pct').value = property.agreed_pct;
            document.getElementById('edit_total_fees').value = property.total_fees;
            document.getElementById('edit_effective_date').value = property.effective_date;
            document.getElementById('edit_term').value = property.term;
            document.getElementById('edit_option_price').value = property.option_price;
            document.getElementById('edit_status').value = property.status;
        });
        
        // Handle delete modal
        document.getElementById('deletePropertyModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const propertyId = button.getAttribute('data-property-id');
            document.getElementById('delete_property_id').value = propertyId;
        });
        
        // Handle view modal
        document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#viewPropertyModal"]').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                
                const property = JSON.parse(this.getAttribute('data-property'));
                
                // Basic Information
                document.getElementById('view_id').textContent = property.id;
                document.getElementById('view_client_name').textContent = property.client_name;
                document.getElementById('view_address').textContent = property.address;
                document.getElementById('view_status').textContent = property.status.charAt(0).toUpperCase() + property.status.slice(1);
                
                // Financial Information
                document.getElementById('view_initial_valuation').textContent = '$' + parseFloat(property.initial_valuation).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('view_initial_index').textContent = property.initial_index ? parseFloat(property.initial_index).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A';
                document.getElementById('view_initial_index_date').textContent = property.initial_index_date || 'N/A';
                document.getElementById('view_agreed_pct').textContent = property.agreed_pct + '%';
                document.getElementById('view_total_fees').textContent = '$' + parseFloat(property.total_fees).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('view_option_price').textContent = '$' + parseFloat(property.option_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                // Contract Details
                document.getElementById('view_effective_date').textContent = property.effective_date;
                document.getElementById('view_term').textContent = property.term + ' months';
                
                // Calculate and display expiration date
                const effectiveDate = new Date(property.effective_date);
                const expirationDate = new Date(effectiveDate);
                expirationDate.setMonth(expirationDate.getMonth() + parseInt(property.term));
                document.getElementById('view_expiration_date').textContent = expirationDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                // Fetch and display current value and user profit
                fetch(`get_valuation_history.php?property_id=${property.id}`, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.valuations && data.data.valuations.length > 0) {
                        const latestValuation = data.data.valuations[0];
                        const initialValue = parseFloat(property.initial_valuation);
                        const appreciationRate = parseFloat(latestValuation.appreciation_rate);
                        
                        console.log('Contract Details - Current Value Calculation:', {
                            initialValue,
                            appreciationRate,
                            calculation: `Current Value = ${initialValue} * (1 + (${appreciationRate} / 100))`,
                            result: initialValue * (1 + (appreciationRate / 100))
                        });

                        // Calculate current value: initial value * (1 + appreciation rate)
                        const currentValue = initialValue * (1 + (appreciationRate / 100));
                        const agreedPercentage = parseFloat(property.agreed_pct);
                        const appreciation = currentValue - initialValue;
                        // Calculate user profit: if appreciation > 0, multiply by agreed percentage, otherwise 0
                        const userProfit = appreciation > 0 ? appreciation * (agreedPercentage / 100) : 0;

                        document.getElementById('view_current_value').textContent = '$' + currentValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.getElementById('view_user_profit').textContent = '$' + userProfit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    } else {
                        document.getElementById('view_current_value').textContent = 'N/A';
                        document.getElementById('view_user_profit').textContent = 'N/A';
                    }
                })
                .catch(error => {
                    console.error('Error fetching current value:', error);
                    document.getElementById('view_current_value').textContent = 'Error';
                    document.getElementById('view_user_profit').textContent = 'Error';
                });

                // Closing Information
                document.getElementById('view_closing_index').textContent = property.closing_index ? parseFloat(property.closing_index).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A';
                document.getElementById('view_closing_date').textContent = property.closing_date || 'N/A';
                
                // Fetch and display valuation history with property data
                fetchValuationHistory(property.id, property);
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('viewPropertyModal'));
                modal.show();
            });
        });

        // Add event listener for modal hidden event
        document.getElementById('viewPropertyModal').addEventListener('hidden.bs.modal', function () {
            // Remove the modal backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            // Remove the modal-open class from body
            document.body.classList.remove('modal-open');
            // Reset the body padding
            document.body.style.paddingRight = '';
        });

        // Add event listener for modal close button
        document.querySelector('#viewPropertyModal .btn-close').addEventListener('click', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewPropertyModal'));
            if (modal) {
                modal.hide();
            }
        });

        // Add event listener for modal footer close button
        document.querySelector('#viewPropertyModal .btn-secondary').addEventListener('click', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewPropertyModal'));
            if (modal) {
                modal.hide();
            }
        });

        function fetchValuationHistory(propertyId, propertyData) {
            console.log('Fetching valuation history for property:', propertyId);
            console.log('Property data:', propertyData);
            
            // Show loading state
            const tbody = document.getElementById('valuationHistoryBody');
            tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
            
            fetch(`get_valuation_history.php?property_id=${propertyId}`, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Valuation history response:', data);
                
                if (data.success && data.data && data.data.valuations) {
                    // Ensure propertyData has all required fields
                    const requiredData = {
                        initial_valuation: parseFloat(propertyData.initial_valuation),
                        initial_index: parseFloat(propertyData.initial_index),
                        agreed_pct: parseFloat(propertyData.agreed_pct),
                        option_price: parseFloat(propertyData.option_price),
                        total_fees: parseFloat(propertyData.total_fees)
                    };
                    
                    console.log('Required property data:', requiredData);
                    updateValuationHistoryTable(data.data.valuations, requiredData);
                } else {
                    console.error('Invalid data structure:', data);
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center">No valuation history available</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error fetching valuation history:', error);
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error loading valuation history</td></tr>';
            });
        }
        
        function checkSession() {
            const hasSession = document.cookie.includes('PHPSESSID');
            console.log('Session check:', { 
                hasSession, 
                cookies: document.cookie,
                currentPath: window.location.pathname
            });
            
            if (!hasSession) {
                console.error('No active session found');
                return false;
            }
            
            // Verificar si estamos en la página correcta
            if (window.location.pathname.includes('dashboard.php')) {
                console.error('Redirected to dashboard - Session expired');
                return false;
            }
            
            return true;
        }

        function handleSessionExpired() {
            console.error('Session expired - Redirecting to login');
            window.location.href = 'login.php';
        }

        function fetchDocuments(propertyId) {
            console.log('Starting fetchDocuments for property:', propertyId);
            
            if (!checkSession()) {
                console.error('Session check failed in fetchDocuments');
                handleSessionExpired();
                return;
            }
            
            console.log('Fetching documents from get_documents.php');
            fetch(`get_documents.php?property_id=${propertyId}`, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
            .then(response => {
                console.log('Documents response received:', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries())
                });
                
                if (response.status === 401 || response.status === 403) {
                    console.error('Session expired in fetchDocuments - HTTP status:', response.status);
                    handleSessionExpired();
                    return;
                }
                
                if (!response.ok) {
                    console.error('HTTP error in fetchDocuments:', response.status);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text().then(text => {
                    console.log('Raw documents response:', text);
                    try {
                        const jsonData = JSON.parse(text);
                        console.log('Parsed documents JSON:', jsonData);
                        return jsonData;
                    } catch (e) {
                        console.error('JSON parse error in fetchDocuments:', e);
                        if (text.includes('dashboard.php') || text.includes('login.php')) {
                            console.error('Redirect detected in documents response');
                            handleSessionExpired();
                            return;
                        }
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then(data => {
                if (!data) return; // Si data es undefined (por la redirección)
                
                console.log('Processing documents data:', data);
                const container = document.getElementById('documents_list');
                container.innerHTML = '';
                
                if (!data.success) {
                    console.error('Error in documents data:', data.error);
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>Error loading documents</h6>
                            <p class="mb-0">${data.error || 'Error loading documents'}</p>
                        </div>
                    `;
                    return;
                }
                
                if (!data.documents || !Array.isArray(data.documents)) {
                    console.log('No documents available');
                    container.innerHTML = '<div class="alert alert-info">No documents available</div>';
                    return;
                }
                
                console.log('Rendering documents:', data.documents);
                data.documents.forEach(doc => {
                    const docElement = document.createElement('div');
                    docElement.className = 'document-item';
                    docElement.innerHTML = `
                        <div class="document-info">
                            <div class="document-icon">
                                <i class="fas fa-file-${getFileIcon(doc.document_type)}"></i>
                            </div>
                            <div>
                                <div class="fw-bold">${doc.document_name}</div>
                                <div class="small text-muted">${doc.document_type} • ${doc.upload_date}</div>
                            </div>
                        </div>
                        <div class="document-actions">
                            <a href="${BASE_URL}/${doc.file_path}" class="action-button btn-view" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                            <button type="button" class="action-button btn-delete" onclick="deleteDocument(${doc.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(docElement);
                });
            })
            .catch(error => {
                console.error('Error in fetchDocuments:', error);
                const container = document.getElementById('documents_list');
                
                if (error.message.includes('Session expired') || error.message.includes('Redirected to dashboard')) {
                    console.error('Session expired error in fetchDocuments');
                    handleSessionExpired();
                } else {
                    console.error('Other error in fetchDocuments:', error.message);
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>Error loading documents</h6>
                            <p class="mb-0">${error.message}</p>
                            <small>Please try again or contact support if the problem persists.</small>
                        </div>
                    `;
                }
            });
        }
        
        function getFileIcon(type) {
            switch (type) {
                case 'contract':
                    return 'contract';
                case 'valuation':
                    return 'chart-line';
                case 'inspection':
                    return 'clipboard-check';
                default:
                    return 'file';
            }
        }
        
        function deleteDocument(docId) {
            if (confirm('Are you sure you want to delete this document?')) {
                fetch('delete_document.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ doc_id: docId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh documents list
                        fetchDocuments(document.getElementById('document_property_id').value);
                        alert(data.message);
                    } else {
                        alert(data.error || 'Error deleting document');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting document');
                });
            }
        }

        // Función para mostrar/ocultar el log
        function toggleDebugLog() {
            const logContainer = document.getElementById('debug-log');
            logContainer.style.display = logContainer.style.display === 'none' ? 'block' : 'none';
        }

        // Función de logging personalizada
        function log(...args) {
            try {
                const timestamp = new Date().toISOString();
                const message = args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg, null, 2) : arg
                ).join(' ');
                
                // Mostrar en consola con timestamp
                console.log(`[${timestamp}]`, ...args);
            } catch (error) {
                console.error('Error in log function:', error);
            }
        }

        // Mostrar el log al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const logContainer = document.getElementById('debug-log');
            if (logContainer) {
                logContainer.style.display = 'block';
            }
        });

        // Handle cancel contract modal
        document.getElementById('cancelContractModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const property = JSON.parse(button.getAttribute('data-property'));
            document.getElementById('cancel_property_id').value = property.id;
        });

        // Handle valuation update
        document.getElementById('updateValuationBtn').addEventListener('click', function() {
            const propertyId = document.getElementById('view_id').textContent;
            const propertyData = {
                initial_valuation: parseFloat(document.getElementById('view_initial_valuation').textContent.replace(/[^0-9.-]+/g, '')),
                agreed_pct: parseFloat(document.getElementById('view_agreed_pct').textContent.replace('%', '')),
                term: document.getElementById('view_term').textContent.replace(' months', '')
            };
            
            // Get current date in YYYY-MM-DD format
            const today = new Date();
            const valuationDate = today.toISOString().split('T')[0];
            
            // Show loading state
            const tbody = document.getElementById('valuationHistoryBody');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
            
            // Make a fetch request to update the valuation
            fetch('update_valuation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    property_id: propertyId,
                    valuation_date: valuationDate
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Clear the table and show loading state
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
                    
                    // Fetch the updated valuation history
                    return fetch(`get_valuation_history.php?property_id=${propertyId}`, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                } else {
                    throw new Error(data.error || 'Error updating valuation');
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data && data.data.valuations) {
                    // Update the valuation history table with the nested valuations array
                    updateValuationHistoryTable(data.data.valuations, propertyData);
                    alert('Valuation updated successfully');
                } else {
                    throw new Error(data.error || 'Error loading valuation history');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">${error.message}</td></tr>`;
                alert('Error updating valuation: ' + error.message);
            });
        });

        // Function to update the valuation history table
        function updateValuationHistoryTable(valuations, propertyData) {
            if (!propertyData) {
                console.error('Property data is required for valuation history table');
                return;
            }

            const tbody = document.getElementById('valuationHistoryBody');
            tbody.innerHTML = '';
            
            if (!valuations || !Array.isArray(valuations) || valuations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center">No valuation history available</td></tr>';
                return;
            }
            
            // Get the original values from property data
            const initialValue = parseFloat(propertyData.initial_valuation);
            const initialIndex = parseFloat(propertyData.initial_index);
            const agreedPercentage = parseFloat(propertyData.agreed_pct);
            const optionPrice = parseFloat(propertyData.option_price);
            const totalFees = parseFloat(propertyData.total_fees);

            console.log('Valuation History - Property Data:', {
                initialValue,
                initialIndex,
                agreedPercentage,
                optionPrice,
                totalFees
            });

            valuations.forEach((valuation, index) => {
                const row = document.createElement('tr');
                
                // Calculate values based on the property_valuations table structure
                const indexValue = parseFloat(valuation.index_value);
                const difference = parseFloat(valuation.diference);
                const appreciation = parseFloat(valuation.appreciation);
                const appreciationShare = (agreedPercentage / 100) * appreciation;
                const calculation = optionPrice + appreciationShare + totalFees;
                
                console.log(`Valuation History - Row ${index + 1} Calculation:`, {
                    date: valuation.valuation_date,
                    initialValue,
                    initialIndex,
                    indexValue,
                    difference,
                    appreciation,
                    appreciationShare,
                    calculation
                });

                row.innerHTML = `
                    <td>${new Date(valuation.valuation_date).toLocaleDateString()}</td>
                    <td>$${initialValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>${initialIndex.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>${indexValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>${difference.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}%</td>
                    <td>$${optionPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>$${appreciation.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>${agreedPercentage.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}%</td>
                    <td>$${appreciationShare.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>$${calculation.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(row);
            });
        }
    </script>
</body>
</html> 