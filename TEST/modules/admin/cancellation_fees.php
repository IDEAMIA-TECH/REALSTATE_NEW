<?php
session_start();
require_once __DIR__ . '/../../config.php';
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
                        INSERT INTO cancellation_fees (
                            state, region, fee_percentage, minimum_fee,
                            maximum_fee, effective_date, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $_POST['state'],
                        $_POST['region'],
                        $_POST['fee_percentage'],
                        $_POST['minimum_fee'],
                        $_POST['maximum_fee'],
                        $_POST['effective_date'],
                        $_SESSION['user_id']
                    ]);
                    
                    $message = 'Cancellation fee created successfully';
                } catch (PDOException $e) {
                    $error = 'Error creating cancellation fee: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $stmt = $db->prepare("
                        UPDATE cancellation_fees SET
                            state = ?,
                            region = ?,
                            fee_percentage = ?,
                            minimum_fee = ?,
                            maximum_fee = ?,
                            effective_date = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['state'],
                        $_POST['region'],
                        $_POST['fee_percentage'],
                        $_POST['minimum_fee'],
                        $_POST['maximum_fee'],
                        $_POST['effective_date'],
                        $_POST['fee_id']
                    ]);
                    
                    $message = 'Cancellation fee updated successfully';
                } catch (PDOException $e) {
                    $error = 'Error updating cancellation fee: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $db->prepare("DELETE FROM cancellation_fees WHERE id = ?");
                    $stmt->execute([$_POST['fee_id']]);
                    $message = 'Cancellation fee deleted successfully';
                } catch (PDOException $e) {
                    $error = 'Error deleting cancellation fee: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all cancellation fees
$stmt = $db->query("
    SELECT cf.*, u.username as created_by_name
    FROM cancellation_fees cf
    LEFT JOIN users u ON cf.created_by = u.id
    ORDER BY cf.state, cf.region, cf.effective_date DESC
");
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cancellation Fees - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/global.css" rel="stylesheet">
    <style>
        .fee-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--secondary-color);
        }

        .fee-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .fee-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .fee-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .fee-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .fee-meta {
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

        .btn-edit {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .btn-delete {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <?php require_once INCLUDES_PATH . '/header.php'; ?>
    
    <div class="container py-4">
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
            <h2 class="h4 mb-0">Cancellation Fees</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFeeModal">
                <i class="fas fa-plus me-2"></i>Add New Fee
            </button>
        </div>

        <div class="row">
            <?php foreach ($fees as $fee): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="fee-card">
                        <div class="fee-header">
                            <h5 class="fee-title">
                                <?php echo htmlspecialchars($fee['state']); ?>
                                <?php if ($fee['region']): ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($fee['region']); ?>)</small>
                                <?php endif; ?>
                            </h5>
                            <div class="action-buttons">
                                <button type="button" class="action-button btn-edit" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editFeeModal"
                                        data-fee='<?php echo json_encode($fee); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="action-button btn-delete"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteFeeModal"
                                        data-fee-id="<?php echo $fee['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="fee-details">
                            <div class="fee-meta">
                                <div class="meta-item">
                                    <i class="fas fa-percentage"></i>
                                    <?php echo number_format($fee['fee_percentage'], 2); ?>%
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-dollar-sign"></i>
                                    Min: $<?php echo number_format($fee['minimum_fee'], 2); ?>
                                </div>
                                <?php if ($fee['maximum_fee']): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-dollar-sign"></i>
                                        Max: $<?php echo number_format($fee['maximum_fee'], 2); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="fee-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    Effective: <?php echo date('M d, Y', strtotime($fee['effective_date'])); ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    Created by: <?php echo htmlspecialchars($fee['created_by_name']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Create Fee Modal -->
    <div class="modal fade" id="createFeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Cancellation Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="state" class="form-label">State</label>
                            <select class="form-select" id="state" name="state" required>
                                <option value="">Select State</option>
                                <option value="AL">Alabama</option>
                                <option value="AK">Alaska</option>
                                <option value="AZ">Arizona</option>
                                <option value="AR">Arkansas</option>
                                <option value="CA">California</option>
                                <option value="CO">Colorado</option>
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
                                <option value="HI">Hawaii</option>
                                <option value="ID">Idaho</option>
                                <option value="IL">Illinois</option>
                                <option value="IN">Indiana</option>
                                <option value="IA">Iowa</option>
                                <option value="KS">Kansas</option>
                                <option value="KY">Kentucky</option>
                                <option value="LA">Louisiana</option>
                                <option value="ME">Maine</option>
                                <option value="MD">Maryland</option>
                                <option value="MA">Massachusetts</option>
                                <option value="MI">Michigan</option>
                                <option value="MN">Minnesota</option>
                                <option value="MS">Mississippi</option>
                                <option value="MO">Missouri</option>
                                <option value="MT">Montana</option>
                                <option value="NE">Nebraska</option>
                                <option value="NV">Nevada</option>
                                <option value="NH">New Hampshire</option>
                                <option value="NJ">New Jersey</option>
                                <option value="NM">New Mexico</option>
                                <option value="NY">New York</option>
                                <option value="NC">North Carolina</option>
                                <option value="ND">North Dakota</option>
                                <option value="OH">Ohio</option>
                                <option value="OK">Oklahoma</option>
                                <option value="OR">Oregon</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="RI">Rhode Island</option>
                                <option value="SC">South Carolina</option>
                                <option value="SD">South Dakota</option>
                                <option value="TN">Tennessee</option>
                                <option value="TX">Texas</option>
                                <option value="UT">Utah</option>
                                <option value="VT">Vermont</option>
                                <option value="VA">Virginia</option>
                                <option value="WA">Washington</option>
                                <option value="WV">West Virginia</option>
                                <option value="WI">Wisconsin</option>
                                <option value="WY">Wyoming</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="region" class="form-label">Region (Optional)</label>
                            <input type="text" class="form-control" id="region" name="region">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fee Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="fee_type" id="fee_type_percentage" value="percentage" checked>
                                <label class="btn btn-outline-primary" for="fee_type_percentage">Percentage</label>
                                <input type="radio" class="btn-check" name="fee_type" id="fee_type_fixed" value="fixed">
                                <label class="btn btn-outline-primary" for="fee_type_fixed">Fixed Amount</label>
                            </div>
                        </div>

                        <div class="mb-3" id="percentage_fee_container">
                            <label for="fee_percentage" class="form-label">Fee Percentage</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="fee_percentage" 
                                       name="fee_percentage" step="0.01" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3" id="fixed_fee_container" style="display: none;">
                            <label for="fixed_fee" class="form-label">Fixed Fee</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="fixed_fee" 
                                       name="fixed_fee" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="effective_date" class="form-label">Effective Date</label>
                            <input type="date" class="form-control" id="effective_date" 
                                   name="effective_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Fee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Fee Modal -->
    <div class="modal fade" id="editFeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Cancellation Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="fee_id" id="edit_fee_id">
                        
                        <div class="mb-3">
                            <label for="edit_state" class="form-label">State</label>
                            <select class="form-select" id="edit_state" name="state" required>
                                <option value="">Select State</option>
                                <option value="AL">Alabama</option>
                                <option value="AK">Alaska</option>
                                <option value="AZ">Arizona</option>
                                <option value="AR">Arkansas</option>
                                <option value="CA">California</option>
                                <option value="CO">Colorado</option>
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
                                <option value="HI">Hawaii</option>
                                <option value="ID">Idaho</option>
                                <option value="IL">Illinois</option>
                                <option value="IN">Indiana</option>
                                <option value="IA">Iowa</option>
                                <option value="KS">Kansas</option>
                                <option value="KY">Kentucky</option>
                                <option value="LA">Louisiana</option>
                                <option value="ME">Maine</option>
                                <option value="MD">Maryland</option>
                                <option value="MA">Massachusetts</option>
                                <option value="MI">Michigan</option>
                                <option value="MN">Minnesota</option>
                                <option value="MS">Mississippi</option>
                                <option value="MO">Missouri</option>
                                <option value="MT">Montana</option>
                                <option value="NE">Nebraska</option>
                                <option value="NV">Nevada</option>
                                <option value="NH">New Hampshire</option>
                                <option value="NJ">New Jersey</option>
                                <option value="NM">New Mexico</option>
                                <option value="NY">New York</option>
                                <option value="NC">North Carolina</option>
                                <option value="ND">North Dakota</option>
                                <option value="OH">Ohio</option>
                                <option value="OK">Oklahoma</option>
                                <option value="OR">Oregon</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="RI">Rhode Island</option>
                                <option value="SC">South Carolina</option>
                                <option value="SD">South Dakota</option>
                                <option value="TN">Tennessee</option>
                                <option value="TX">Texas</option>
                                <option value="UT">Utah</option>
                                <option value="VT">Vermont</option>
                                <option value="VA">Virginia</option>
                                <option value="WA">Washington</option>
                                <option value="WV">West Virginia</option>
                                <option value="WI">Wisconsin</option>
                                <option value="WY">Wyoming</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_region" class="form-label">Region (Optional)</label>
                            <input type="text" class="form-control" id="edit_region" name="region">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fee Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="fee_type" id="edit_fee_type_percentage" value="percentage">
                                <label class="btn btn-outline-primary" for="edit_fee_type_percentage">Percentage</label>
                                <input type="radio" class="btn-check" name="fee_type" id="edit_fee_type_fixed" value="fixed">
                                <label class="btn btn-outline-primary" for="edit_fee_type_fixed">Fixed Amount</label>
                            </div>
                        </div>

                        <div class="mb-3" id="edit_percentage_fee_container">
                            <label for="edit_fee_percentage" class="form-label">Fee Percentage</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit_fee_percentage" 
                                       name="fee_percentage" step="0.01" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3" id="edit_fixed_fee_container" style="display: none;">
                            <label for="edit_fixed_fee" class="form-label">Fixed Fee</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="edit_fixed_fee" 
                                       name="fixed_fee" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_effective_date" class="form-label">Effective Date</label>
                            <input type="date" class="form-control" id="edit_effective_date" 
                                   name="effective_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Fee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Fee Modal -->
    <div class="modal fade" id="deleteFeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Cancellation Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="fee_id" id="delete_fee_id">
                        <p>Are you sure you want to delete this cancellation fee? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Fee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle fee type toggle
        document.querySelectorAll('input[name="fee_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const isPercentage = this.value === 'percentage';
                const container = this.closest('.modal').querySelector(isPercentage ? '#percentage_fee_container' : '#fixed_fee_container');
                const otherContainer = this.closest('.modal').querySelector(isPercentage ? '#fixed_fee_container' : '#percentage_fee_container');
                
                container.style.display = 'block';
                otherContainer.style.display = 'none';
                
                // Make the visible input required and the hidden one not required
                container.querySelector('input').required = true;
                otherContainer.querySelector('input').required = false;
            });
        });

        // Handle edit modal
        document.getElementById('editFeeModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const fee = JSON.parse(button.getAttribute('data-fee'));
            
            document.getElementById('edit_fee_id').value = fee.id;
            document.getElementById('edit_state').value = fee.state;
            document.getElementById('edit_region').value = fee.region || '';
            
            // Set fee type based on which field has a value
            if (fee.fee_percentage) {
                document.getElementById('edit_fee_type_percentage').checked = true;
                document.getElementById('edit_fee_percentage').value = fee.fee_percentage;
                document.getElementById('edit_percentage_fee_container').style.display = 'block';
                document.getElementById('edit_fixed_fee_container').style.display = 'none';
            } else if (fee.fixed_fee) {
                document.getElementById('edit_fee_type_fixed').checked = true;
                document.getElementById('edit_fixed_fee').value = fee.fixed_fee;
                document.getElementById('edit_fixed_fee_container').style.display = 'block';
                document.getElementById('edit_percentage_fee_container').style.display = 'none';
            }
            
            document.getElementById('edit_effective_date').value = fee.effective_date;
        });
        
        // Handle delete modal
        document.getElementById('deleteFeeModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const feeId = button.getAttribute('data-fee-id');
            document.getElementById('delete_fee_id').value = feeId;
        });
    </script>
</body>
</html> 