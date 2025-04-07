<?php
class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO {$this->table} (username, password, email, role) 
                    VALUES (:username, :password, :email, :role)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'username' => $data['username'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'email' => $data['email'],
                'role' => $data['role']
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error creating user: " . $e->getMessage());
        }
    }

    public function authenticate($username, $password) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE username = :username AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            throw new Exception("Authentication error: " . $e->getMessage());
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
} 