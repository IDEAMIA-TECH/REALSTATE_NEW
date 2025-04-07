<?php
// Load required files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/User.php';

class AuthController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function login($username, $password) {
        $result = $this->user->authenticate($username, $password);
        
        if ($result['success']) {
            // The session variables are already set in the User class
            return [
                'success' => true,
                'message' => 'Login successful'
            ];
        }
        
        return [
            'success' => false,
            'message' => $result['message']
        ];
    }

    public function logout() {
        // Log the activity before destroying the session
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function register($data) {
        return $this->user->create($data);
    }

    private function logActivity($userId, $action, $details) {
        try {
            $sql = "INSERT INTO activity_log (user_id, action, entity_type, details) 
                    VALUES (:user_id, :action, 'user', :details)";
            
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'details' => $details
            ]);
        } catch (Exception $e) {
            // Log error but don't interrupt the main flow
            error_log("Activity log error: " . $e->getMessage());
        }
    }
} 