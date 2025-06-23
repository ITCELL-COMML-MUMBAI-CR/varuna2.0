<?php
/**
 * Database Singleton Class
 * Ensures that there is only one database connection instance per request.
 */
class Database {
    private static $instance = null;
    private $connection;

    // The constructor is private to prevent creating new instances directly.
    private function __construct() {
        try {
            // Use the credentials defined in config.php
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->connection->exec("SET time_zone = '+05:30';");
        } catch (PDOException $e) {
            // If connection fails, log the error and stop execution.
            error_log("CRITICAL DATABASE CONNECTION FAILED: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(["success" => false, "message" => "Critical server error."]));
        }
    }

    /**
     * Gets the single instance of the Database class.
     * @return Database The single Database instance.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the active PDO connection object.
     * @return PDO The PDO connection object.
     */
    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning of the instance.
    private function __clone() {}
}