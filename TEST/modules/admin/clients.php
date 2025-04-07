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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $stmt = $db->prepare("
                        INSERT INTO clients (
                            name, email, phone, address, status, created_by
                        ) VALUES (?, ?, ?, ?, 'active', ?)
                    ");
                    
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['address'],
                        $_SESSION['user_id']
                    ]);
                    
                    $message = 'Client created successfully';
                } catch (PDOException $e) {
                    $error = 'Error creating client: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $stmt = $db->prepare("
                        UPDATE clients SET
                            name = ?,
                            email = ?,
                            phone = ?,
                            address = ?,
                            status = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['address'],
                        $_POST['status'],
                        $_POST['client_id']
                    ]);
                    
                    $message = 'Client updated successfully';
                } catch (PDOException $e) {
                    $error = 'Error updating client: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $db->prepare("UPDATE clients SET status = 'archived' WHERE id = ?");
                    $stmt->execute([$_POST['client_id']]);
                    $message = 'Client archived successfully';
                } catch (PDOException $e) {
                    $error = 'Error archiving client: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get clients for listing
$stmt = $db->query("
    SELECT c.*, u.username as created_by_name
    FROM clients c
    LEFT JOIN users u ON c.created_by = u.id
    ORDER BY c.created_at DESC
");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - <?php echo APP_NAME; ?></title>
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

        .client-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .client-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .client-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            margin-right: 1rem;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--secondary-color);
        }

        .client-info {
            flex: 1;
        }

        .client-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .client-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .client-status {
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

        .btn-edit {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
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

        .client-properties {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .property-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="page-hero">
        <div class="container">
            <h1 class="text-white"><i class="fas fa-user-tie me-2"></i>Client Management</h1>
            <p class="lead text-white">Manage your clients and their properties</p>
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
            <h2 class="h4 mb-0">All Clients</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClientModal">
                <i class="fas fa-plus me-2"></i>Add New Client
            </button>
        </div>

        <div class="row">
            <?php foreach ($clients as $client): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="client-card">
                        <div class="d-flex align-items-center">
                            <div class="client-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="client-info">
                                <div class="client-name"><?php echo htmlspecialchars($client['name']); ?></div>
                                <div class="client-details">
                                    <div><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($client['email']); ?></div>
                                    <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($client['phone']); ?></div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="client-status status-<?php echo htmlspecialchars($client['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($client['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button type="button" class="action-button btn-edit" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editClientModal"
                                        data-client='<?php echo json_encode($client); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="action-button btn-delete"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteClientModal"
                                        data-client-id="<?php echo $client['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="client-properties">
                            <div class="d-flex flex-wrap">
                                <?php
                                // Get client's properties
                                $stmt = $db->prepare("SELECT * FROM properties WHERE client_id = ? AND status = 'active'");
                                $stmt->execute([$client['id']]);
                                $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($properties as $property):
                                ?>
                                    <span class="property-badge">
                                        <i class="fas fa-home me-1"></i>
                                        <?php echo htmlspecialchars($property['address']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Create Client Modal -->
    <div class="modal fade" id="createClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Client Modal -->
    <div class="modal fade" id="editClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="client_id" id="edit_client_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="3" required></textarea>
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
                        <button type="submit" class="btn btn-primary">Update Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Client Modal -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Archive Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="client_id" id="delete_client_id">
                        <p>Are you sure you want to archive this client? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Archive Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit modal
        document.getElementById('editClientModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const client = JSON.parse(button.getAttribute('data-client'));
            
            document.getElementById('edit_client_id').value = client.id;
            document.getElementById('edit_name').value = client.name;
            document.getElementById('edit_email').value = client.email;
            document.getElementById('edit_phone').value = client.phone;
            document.getElementById('edit_address').value = client.address;
            document.getElementById('edit_status').value = client.status;
        });
        
        // Handle delete modal
        document.getElementById('deleteClientModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button.getAttribute('data-client-id');
            document.getElementById('delete_client_id').value = clientId;
        });
    </script>
</body>
</html> 