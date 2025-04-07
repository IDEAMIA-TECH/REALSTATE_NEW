<?php
class AuthController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function login($username, $password) {
        try {
            $user = $this->user->authenticate($username, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Log successful login
                $this->logActivity($user['id'], 'login', 'User logged in successfully');
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $user
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        session_destroy();
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }

    public function register($data) {
        try {
            // Validate required fields
            if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
                throw new Exception('All fields are required');
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            // Create user
            $userId = $this->user->create($data);
            
            // Log registration
            $this->logActivity($userId, 'register', 'New user registered');
            
            return [
                'success' => true,
                'message' => 'User registered successfully',
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
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