<?php
/**
 * Authentication & Authorization System
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {
    private static ?array $currentUser = null;
    
    /**
     * Read a single setting from system_settings table
     */
    public static function getSystemSetting(string $key, string $default = ''): string {
        try {
            $row = Database::fetch(
                "SELECT setting_value FROM system_settings WHERE setting_key = :key",
                [':key' => $key]
            );
            return $row ? $row['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Authenticate user with username/email and password
     */
    public static function login(string $username, string $password, bool $remember = false): array {
        $user = Database::fetch(
            "SELECT u.*, r.name as role_name, r.display_name as role_display, r.permissions, r.level 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE (u.username = :login OR u.email = :login) AND u.is_active = 1",
            [':login' => $username]
        );
        
        if (!$user) {
            throw new Exception('Invalid credentials');
        }
        
        // Read lockout settings from system_settings
        $lockoutEnabled = self::getSystemSetting('account_lockout_enabled', '1') === '1';
        $maxAttempts = (int) self::getSystemSetting('account_lockout_max_attempts', '5');
        $lockDuration = (int) self::getSystemSetting('account_lockout_duration', '30');
        
        // Check if account is locked (only if lockout is enabled)
        if ($lockoutEnabled && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
            throw new Exception('Account is temporarily locked. Please try again later.');
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            if ($lockoutEnabled) {
                // Increment login attempts
                Database::update('users', 
                    ['login_attempts' => $user['login_attempts'] + 1],
                    'id = :id',
                    [':id' => $user['id']]
                );
                
                // Lock account after max failed attempts
                if ($user['login_attempts'] + 1 >= $maxAttempts) {
                    Database::update('users',
                        ['locked_until' => date('Y-m-d H:i:s', strtotime("+{$lockDuration} minutes"))],
                        'id = :id',
                        [':id' => $user['id']]
                    );
                }
            }
            
            throw new Exception('Invalid credentials');
        }
        
        // Reset login attempts and update last login
        Database::update('users', [
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = :id', [':id' => $user['id']]);
        
        // Generate JWT token
        $token = self::generateToken($user, $remember);
        
        // Create session
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = $remember ? date('Y-m-d H:i:s', strtotime('+30 days')) : date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        Database::insert('user_sessions', [
            'user_id' => $user['id'],
            'session_token' => $sessionToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires_at' => $expiresAt
        ]);
        
        // Set cookies
        $cookieExpiry = $remember ? time() + (30 * 24 * 60 * 60) : 0;
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie('auth_token', $token, [
            'expires' => $cookieExpiry,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        setcookie('session_token', $sessionToken, [
            'expires' => $cookieExpiry,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        return [
            'user' => self::sanitizeUserData($user),
            'token' => $token,
            'session_token' => $sessionToken
        ];
    }
    
    /**
     * Generate JWT token
     */
    public static function generateToken(array $user, bool $longLived = false): string {
        $issuedAt = time();
        $expiration = $longLived ? $issuedAt + (30 * 24 * 60 * 60) : $issuedAt + (24 * 60 * 60);
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'sub' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role_name'],
            'role_level' => $user['level'],
            'org_id' => $user['organization_id']
        ];
        
        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }
    
    /**
     * Verify and decode JWT token
     */
    public static function verifyToken(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get current authenticated user
     */
    public static function getCurrentUser(): ?array {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        
        $token = $_COOKIE['auth_token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        
        if (!$token) {
            return null;
        }
        
        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);
        
        $decoded = self::verifyToken($token);
        if (!$decoded) {
            return null;
        }
        
        $user = Database::fetch(
            "SELECT u.*, r.name as role_name, r.display_name as role_display, r.permissions, r.level,
                    o.name as org_name, l.name as lab_name,
                    d.name as department_name
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             LEFT JOIN organizations o ON u.organization_id = o.id
             LEFT JOIN labs l ON u.lab_id = l.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.id = :id AND u.is_active = 1",
            [':id' => $decoded['sub']]
        );
        
        if ($user) {
            $user['permissions'] = json_decode($user['permissions'], true);
            self::$currentUser = $user;
        }
        
        return $user;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check(): bool {
        return self::getCurrentUser() !== null;
    }
    
    /**
     * Check if user has specific permission
     */
    public static function can(string $permission, ?int $resourceId = null): bool {
        $user = self::getCurrentUser();
        if (!$user) return false;
        
        // Admin has all permissions
        if ($user['role_name'] === 'admin') return true;
        
        $permissions = $user['permissions'] ?? [];
        
        // Check specific permission
        $parts = explode('.', $permission);
        $current = $permissions;
        foreach ($parts as $part) {
            if (isset($current[$part])) {
                $current = $current[$part];
            } else {
                return false;
            }
        }
        
        return $current === true || $current === 1;
    }
    
    /**
     * Require authentication
     */
    public static function requireAuth(): array {
        $user = self::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        return $user;
    }
    
    /**
     * Require specific permission
     */
    public static function requirePermission(string $permission, ?int $resourceId = null): void {
        if (!self::can($permission, $resourceId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
    }
    
    /**
     * Logout user
     */
    public static function logout(): void {
        $sessionToken = $_COOKIE['session_token'] ?? null;
        if ($sessionToken) {
            Database::delete('user_sessions', 'session_token = :token', [':token' => $sessionToken]);
        }
        
        setcookie('auth_token', '', ['expires' => 1, 'path' => '/']);
        setcookie('session_token', '', ['expires' => 1, 'path' => '/']);
        
        self::$currentUser = null;
    }
    
    /**
     * Register new user
     */
    public static function register(array $data): array {
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name', 'lab_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("{$field} is required");
            }
        }
        
        // Check if username exists
        $existing = Database::fetch("SELECT id FROM users WHERE username = :u", [':u' => $data['username']]);
        if ($existing) {
            throw new Exception('Username already exists');
        }
        
        // Check if email exists
        $existing = Database::fetch("SELECT id FROM users WHERE email = :e", [':e' => $data['email']]);
        if ($existing) {
            throw new Exception('Email already exists');
        }
        
        // Get default role (User)
        $role = Database::fetch("SELECT id FROM roles WHERE name = 'user'");
        if (!$role) {
            throw new Exception('Default role not found');
        }
        
        // Create user
        $userId = Database::insert('users', [
            'organization_id' => $data['organization_id'] ?? 1,
            'role_id' => $data['role_id'] ?? $role['id'],
            'lab_id' => $data['lab_id'],
            'manager_id' => $data['manager_id'] ?? null,
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'theme_preference' => $data['theme'] ?? DEFAULT_THEME,
            'language' => $data['language'] ?? DEFAULT_LANGUAGE
        ]);
        
        // Create notification settings
        Database::insert('notification_settings', ['user_id' => $userId]);
        
        return ['user_id' => $userId, 'message' => 'Registration successful. Awaiting approval from lab manager.'];
    }
    
    /**
     * Sanitize user data for output
     */
    private static function sanitizeUserData(array $user): array {
        unset($user['password_hash']);
        unset($user['api_token']);
        unset($user['login_attempts']);
        unset($user['locked_until']);
        
        $user['permissions'] = json_decode($user['permissions'], true);
        
        return $user;
    }
}
