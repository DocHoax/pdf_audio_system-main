<?php
/**
 * EchoDoc - User Class
 * 
 * Handles user authentication, registration, and management
 */

require_once __DIR__ . '/db_config.php';

class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Register a new user
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $fullName
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register($username, $email, $password, $fullName = '') {
        if (!$this->pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        // Validate input
        $username = trim($username);
        $email = trim(strtolower($email));
        $fullName = trim($fullName);
        
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'message' => 'Username must be 3-50 characters'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check if username exists
        if ($this->usernameExists($username)) {
            return ['success' => false, 'message' => 'Username already taken'];
        }
        
        // Check if email exists
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, full_name, created_at)
                VALUES (:username, :email, :password, :full_name, NOW())
            ");
            
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':full_name' => $fullName ?: null
            ]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Create default user settings
            $this->createDefaultSettings($userId);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
            ];
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login a user
     * @param string $identifier Username or email
     * @param string $password
     * @return array ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function login($identifier, $password) {
        if (!$this->pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        $identifier = trim($identifier);
        
        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'message' => 'Please enter username/email and password'];
        }
        
        try {
            // Check if identifier is email or username
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, password, full_name, avatar, created_at
                FROM users 
                WHERE " . ($isEmail ? "email" : "username") . " = :identifier
            ");
            
            $stmt->execute([':identifier' => $isEmail ? strtolower($identifier) : $identifier]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username/email or password'];
            }
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid username/email or password'];
            }
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Remove password from user array
            unset($user['password']);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|null
     */
    public function getUserById($userId) {
        if (!$this->pdo) return null;
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, full_name, avatar, created_at, last_login
                FROM users WHERE id = :id
            ");
            $stmt->execute([':id' => $userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if username exists
     * @param string $username
     * @return bool
     */
    public function usernameExists($username) {
        if (!$this->pdo) return false;
        
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Check if email exists
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        if (!$this->pdo) return false;
        
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => strtolower($email)]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Update last login timestamp
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $stmt->execute([':id' => $userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Create default user settings
     * @param int $userId
     */
    private function createDefaultSettings($userId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_settings (user_id, preferred_voice, default_volume, theme)
                VALUES (:user_id, 'Idera', 1.00, 'light')
            ");
            $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Create settings error: " . $e->getMessage());
        }
    }
    
    /**
     * Update user profile
     * @param int $userId
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateProfile($userId, $data) {
        if (!$this->pdo) return ['success' => false, 'message' => 'Database connection failed'];
        
        $allowedFields = ['full_name', 'avatar', 'username'];
        $updates = [];
        $params = [':id' => $userId];
        
        // Handle username change with validation
        if (isset($data['username'])) {
            $username = trim($data['username']);
            
            // Validate username format
            if (strlen($username) < 3 || strlen($username) > 50) {
                return ['success' => false, 'message' => 'Username must be 3-50 characters'];
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                return ['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
            }
            
            // Check if username is taken by another user
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
            $stmt->execute([':username' => $username, ':user_id' => $userId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already taken'];
            }
            
            $data['username'] = $username;
        }
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        if (empty($updates)) return ['success' => false, 'message' => 'No fields to update'];
        
        try {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            return ['success' => $result, 'message' => $result ? 'Profile updated successfully!' : 'Failed to update profile'];
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Change user password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        if (!$this->pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters'];
        }
        
        try {
            // Get current password hash
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([':password' => $hashedPassword, ':id' => $userId]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
}
