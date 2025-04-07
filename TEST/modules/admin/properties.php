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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $stmt = $db->prepare("
                        INSERT INTO properties (
                            client_id, address, initial_valuation, agreed_pct,
                            total_fees, effective_date, term, option_price,
                            status, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
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
                        $_SESSION['user_id']
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
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Property Management</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPropertyModal">
                <i class="fas fa-plus"></i> Add Property
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
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
                                    <td><?php echo htmlspecialchars($property['id']); ?></td>
                                    <td><?php echo htmlspecialchars($property['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($property['address']); ?></td>
                                    <td>$<?php echo number_format($property['initial_valuation'], 2); ?></td>
                                    <td><?php echo number_format($property['agreed_pct'], 2); ?>%</td>
                                    <td><?php echo htmlspecialchars($property['effective_date']); ?></td>
                                    <td><?php echo htmlspecialchars($property['term']); ?> months</td>
                                    <td>
                                        <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($property['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewPropertyModal"
                                                data-property='<?php echo json_encode($property); ?>'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editPropertyModal"
                                                data-property='<?php echo json_encode($property); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deletePropertyModal"
                                                data-property-id="<?php echo $property['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Property Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <h6>Financial Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Initial Valuation:</th>
                                    <td id="view_initial_valuation"></td>
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
                    </div>
                    <div class="row mt-3">
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
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Valuation History</h6>
                            <div id="valuation_history" class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Current Value</th>
                                            <th>Appreciation</th>
                                            <th>Share Appreciation</th>
                                            <th>Terminal Value</th>
                                            <th>Projected Payoff</th>
                                            <th>Option Value</th>
                                        </tr>
                                    </thead>
                                    <tbody id="valuation_history_body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Documents</h6>
                            <div class="card">
                                <div class="card-body">
                                    <!-- Document Upload Form -->
                                    <form id="documentUploadForm" class="mb-3" enctype="multipart/form-data">
                                        <input type="hidden" name="property_id" id="document_property_id">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="document_name" placeholder="Document Name" required>
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select" name="document_type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="contract">Contract</option>
                                                    <option value="valuation">Valuation Report</option>
                                                    <option value="inspection">Inspection Report</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="file" class="form-control" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-upload"></i> Upload
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <!-- Documents Table -->
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Upload Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="documents_body">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once INCLUDES_PATH . '/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        document.getElementById('viewPropertyModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const property = JSON.parse(button.getAttribute('data-property'));
            
            // Set property ID for document upload
            document.getElementById('document_property_id').value = property.id;
            
            // Basic Information
            document.getElementById('view_id').textContent = property.id;
            document.getElementById('view_client_name').textContent = property.client_name;
            document.getElementById('view_address').textContent = property.address;
            document.getElementById('view_status').textContent = property.status.charAt(0).toUpperCase() + property.status.slice(1);
            
            // Financial Information
            document.getElementById('view_initial_valuation').textContent = '$' + parseFloat(property.initial_valuation).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('view_agreed_pct').textContent = property.agreed_pct + '%';
            document.getElementById('view_total_fees').textContent = '$' + parseFloat(property.total_fees).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('view_option_price').textContent = '$' + parseFloat(property.option_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Contract Details
            document.getElementById('view_effective_date').textContent = property.effective_date;
            document.getElementById('view_term').textContent = property.term + ' months';
            
            // Fetch and display valuation history and documents
            fetchValuationHistory(property.id);
            fetchDocuments(property.id);
        });
        
        // Handle document upload
        document.getElementById('documentUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('upload_document.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh documents list
                    fetchDocuments(document.getElementById('document_property_id').value);
                    
                    // Show success message
                    alert(data.message);
                    
                    // Reset form
                    this.reset();
                } else {
                    alert(data.error || 'Error uploading document');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error uploading document');
            });
        });
        
        function fetchValuationHistory(propertyId) {
            fetch(`get_valuation_history.php?property_id=${propertyId}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('valuation_history_body');
                    tbody.innerHTML = '';
                    
                    if (!data.success) {
                        tbody.innerHTML = `<tr><td colspan="7" class="text-center">${data.error || 'Error loading valuation history'}</td></tr>`;
                        return;
                    }
                    
                    if (!data.data || data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No valuation history available</td></tr>';
                        return;
                    }
                    
                    data.data.forEach(valuation => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${valuation.date}</td>
                            <td>$${parseFloat(valuation.value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td>${valuation.appreciation_rate}%</td>
                            <td>$${parseFloat(valuation.share_appreciation).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td>$${parseFloat(valuation.terminal_value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td>$${parseFloat(valuation.projected_payoff).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td>$${parseFloat(valuation.option_valuation).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching valuation history:', error);
                    const tbody = document.getElementById('valuation_history_body');
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Error loading valuation history</td></tr>';
                });
        }
        
        function fetchDocuments(propertyId) {
            fetch(`get_valuation_history.php?property_id=${propertyId}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('documents_body');
                    tbody.innerHTML = '';
                    
                    if (!data.success) {
                        tbody.innerHTML = `<tr><td colspan="4" class="text-center">${data.error || 'Error loading documents'}</td></tr>`;
                        return;
                    }
                    
                    if (!data.data.documents || data.data.documents.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No documents available</td></tr>';
                        return;
                    }
                    
                    data.data.documents.forEach(doc => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${doc.document_name}</td>
                            <td>${doc.document_type}</td>
                            <td>${doc.upload_date}</td>
                            <td>
                                <a href="${BASE_URL}/${doc.file_path}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching documents:', error);
                    const tbody = document.getElementById('documents_body');
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Error loading documents</td></tr>';
                });
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
    </script>
</body>
</html> 