<?php
/**
 * Database - Singleton PDO Connection Manager
 * Ensures a single shared PDO instance with hardened settings.
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {}

    /**
     * Prevent object cloning
     */
    private function __clone() {}

    /**
     * Prevent object unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton Database instance");
    }

    /**
     * Get the active PDO instance
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // True native prepared statements
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$instance->exec("SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $e) {
                // Do not expose database passwords or details in error message
                error_log("Database Connection Error: " . $e->getMessage());
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
                } else {
                    die("A database connection error occurred. Please contact the administrator.");
                }
            }
        }

        return self::$instance;
    }

    /**
     * Shortcut to prepare and execute a parameterized query
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Shortcut to fetch a single row
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Shortcut to fetch all matching rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Shortcut to get last inserted ID
     */
    public static function lastInsertId(): string {
        return self::getInstance()->lastInsertId();
    }
}
