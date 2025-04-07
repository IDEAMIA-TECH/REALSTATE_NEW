<?php
require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        try {
            // Validate required fields
            if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
                return [
                    'success' => false,
                    'message' => 'All fields are required'
                ];
            }
            
            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE username = ? OR email = ?");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Username or email already exists'
                ];
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (username, password, email, role, status) 
                VALUES (?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $data['username'],
                $data['password'],
                $data['email'],
                $data['role'] ?? 'view_only'
            ]);
            
            return [
                'success' => true,
                'message' => 'User created successfully',
                'id' => $this->db->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    public function authenticate($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
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
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT id, username, email, role, status, created_at 
                    FROM {$this->table} 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }

    public function update($id, $data) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET email = :email, role = :role, status = :status 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'email' => $data['email'],
                'role' => $data['role'],
                'status' => $data['status']
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }

    public function changePassword($id, $newPassword) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET password = :password 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error changing password: " . $e->getMessage());
        }
    }

    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            // Log error but don't fail the login
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }
} 