<?php
/**
 * Auth - Authentication, Authorization & Session Management
 * Handles login, registration, role verification, and brute-force protection.
 */

class Auth {
    private const SESSION_USER_KEY = 'shopease_auth_user';
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    /**
     * Authenticate user with email and password
     */
    public static function login(string $email, string $password): array {
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));

        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Please enter both email and password.'];
        }

        // Check rate limiting / brute force
        if (self::isRateLimited($email)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please try again after 15 minutes.'
            ];
        }

        // Find user by email
        $user = Database::fetch("SELECT * FROM `users` WHERE `email` = ? LIMIT 1", [$email]);

        if (!$user) {
            self::recordAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password credentials.'];
        }

        // Verify account is active
        if ((int)$user['is_active'] !== 1) {
            return ['success' => false, 'message' => 'Your account has been deactivated. Contact an administrator.'];
        }

        // Verify password hash
        if (!password_verify($password, $user['password'])) {
            self::recordAttempt($email);
            return ['success' => false, 'message' => 'Invalid email or password credentials.'];
        }

        // Login successful: clear attempts and regenerate session
        self::clearAttempts($email);
        session_regenerate_id(true);

        // Sanitize user data for session storage
        unset($user['password']);
        $_SESSION[self::SESSION_USER_KEY] = $user;
        Csrf::regenerate();

        return [
            'success' => true,
            'message' => 'Login successful.',
            'user'    => $user
        ];
    }

    /**
     * Register a new cashier account
     */
    public static function signup(string $name, string $email, string $password, string $confirmPassword): array {
        $name = trim($name);
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));

        if (empty($name) || strlen($name) < 2) {
            return ['success' => false, 'message' => 'Full name must be at least 2 characters.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please provide a valid email address.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        // Check if email already exists
        $existing = Database::fetch("SELECT `id` FROM `users` WHERE `email` = ? LIMIT 1", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'An account with this email address already exists.'];
        }

        // Hash password with bcrypt
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insert new user with default cashier role
        Database::query(
            "INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`, `theme`, `created_at`) 
             VALUES (?, ?, ?, 'cashier', 1, 'light', NOW())",
            [$name, $email, $passwordHash]
        );

        $newUserId = (int)Database::lastInsertId();

        // Automatically log in newly registered user
        $newUser = Database::fetch("SELECT `id`, `name`, `email`, `role`, `is_active`, `profile_photo`, `theme`, `created_at` FROM `users` WHERE `id` = ?", [$newUserId]);
        
        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_KEY] = $newUser;
        Csrf::regenerate();

        return [
            'success' => true,
            'message' => 'Registration successful! Welcome to ShopEase.',
            'user'    => $newUser
        ];
    }

    /**
     * Check if user is currently logged in
     */
    public static function check(): bool {
        return !empty($_SESSION[self::SESSION_USER_KEY]['id']);
    }

    /**
     * Retrieve the currently logged in user record
     */
    public static function user(): ?array {
        return $_SESSION[self::SESSION_USER_KEY] ?? null;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int {
        return isset($_SESSION[self::SESSION_USER_KEY]['id']) ? (int)$_SESSION[self::SESSION_USER_KEY]['id'] : null;
    }

    /**
     * Get current user role
     */
    public static function role(): ?string {
        return $_SESSION[self::SESSION_USER_KEY]['role'] ?? null;
    }

    /**
     * Check if logged in user is admin
     */
    public static function isAdmin(): bool {
        return self::role() === 'admin';
    }

    /**
     * Check if logged in user is cashier
     */
    public static function isCashier(): bool {
        return self::role() === 'cashier';
    }

    /**
     * Enforce login requirement on pages
     */
    public static function requireLogin(): void {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    /**
     * Enforce role-based access control
     */
    public static function requireRole($allowedRoles): void {
        self::requireLogin();
        $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            die('Access Denied: You do not have permission to view this resource.');
        }
    }

    /**
     * Log user out and destroy session
     */
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Record a failed login attempt for rate limiting
     */
    private static function recordAttempt(string $email): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        Database::query(
            "INSERT INTO `login_attempts` (`email`, `ip_address`, `attempted_at`) VALUES (?, ?, NOW())",
            [$email, $ip]
        );
    }

    /**
     * Check if the account/IP is currently rate-limited
     */
    private static function isRateLimited(string $email): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $timeWindow = date('Y-m-d H:i:s', strtotime('-' . self::LOCKOUT_MINUTES . ' minutes'));

        $stmt = Database::fetch(
            "SELECT COUNT(*) as `attempts` FROM `login_attempts` 
             WHERE (`email` = ? OR `ip_address` = ?) AND `attempted_at` > ?",
            [$email, $ip, $timeWindow]
        );

        return ($stmt && (int)$stmt['attempts'] >= self::MAX_ATTEMPTS);
    }

    /**
     * Clear recorded failed login attempts upon successful login
     */
    private static function clearAttempts(string $email): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        Database::query(
            "DELETE FROM `login_attempts` WHERE `email` = ? OR `ip_address` = ?",
            [$email, $ip]
        );
    }
}
