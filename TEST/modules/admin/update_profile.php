<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../auth/AuthController.php';

// Verificar autenticación
$auth = new AuthController();
if (!isset($_SESSION['user_id'])) {
    $auth->redirectToLogin();
}

// Obtener conexión a la base de datos
$db = getDBConnection();

// Obtener ID del usuario actual
$user_id = $_SESSION['user_id'];

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del formulario
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validar datos
        if (empty($username) || empty($email)) {
            throw new Exception("Username and email are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Verificar si el email ya está en uso por otro usuario
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Email is already in use by another user.");
        }

        // Verificar si el username ya está en uso por otro usuario
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Username is already in use by another user.");
        }

        // Si se proporcionó una nueva contraseña
        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception("Current password is required to change password.");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }

            // Verificar la contraseña actual
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect.");
            }

            // Actualizar con nueva contraseña
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashed_password, $user_id]);
        } else {
            // Actualizar sin cambiar la contraseña
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user_id]);
        }

        // Actualizar la sesión
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        $_SESSION['success'] = "Profile updated successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Redirigir de vuelta al perfil
header("Location: profile.php");
exit(); 