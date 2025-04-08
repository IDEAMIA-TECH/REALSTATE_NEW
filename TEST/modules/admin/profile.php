<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth/AuthController.php';

// Verificar autenticación
$auth = new AuthController();
if (!isset($_SESSION['user_id'])) {
    $auth->redirectToLogin();
}

// Obtener información del usuario actual
$user_id = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener información del usuario: " . $e->getMessage());
    $user = null;
}

$page_title = "User Profile";
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 0.5rem 0.5rem 0 0;
}

.profile-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 5px solid white;
    object-fit: cover;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.profile-name {
    font-size: 1.8rem;
    font-weight: 600;
    margin: 1rem 0 0.5rem;
}

.profile-email {
    color: rgba(255,255,255,0.8);
    font-size: 1.1rem;
}

.profile-card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.profile-card .card-header {
    background: white;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.profile-card .card-body {
    padding: 2rem;
}

.form-control {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    border: 1px solid #e0e0e0;
}

.form-control:focus {
    border-color: #6B73FF;
    box-shadow: 0 0 0 0.2rem rgba(107, 115, 255, 0.25);
}

.btn-primary {
    background: #6B73FF;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #000DFF;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 115, 255, 0.3);
}

.invalid-feedback {
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
}
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="profile-card">
                <div class="profile-header text-center">
                    <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-1.2.1&auto=format&fit=crop&w=150&q=80" 
                         alt="Profile Image" class="profile-image">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['first_name'] ?? '') . ' ' . htmlspecialchars($user['last_name'] ?? ''); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                </div>

                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="update_profile.php" method="POST" class="needs-validation" novalidate>
                        <h5 class="mb-4">Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter your first name.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter your last name.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Please enter a username.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-4">Change Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Leave blank if you don't want to change your password.</div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Leave blank if you don't want to change your password.</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 