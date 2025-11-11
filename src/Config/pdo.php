<?php
/**
 * Database Connection Class
 *
 * This class handles the connection to the MySQL database using PDO.
 * It reads its configuration from the environment variables
 * set in the docker-compose.yml file.
 */
class Database {

    // --- Connection Parameters ---

    /**
     * @var string The hostname for the database server.
     * 'db' is the service name defined in docker-compose.yml.
     */
    private $host = 'db';

    /**
     * @var string The name of the database to connect to.
     */
    private $db_name;

    /**
     * @var string The username for the database connection.
     */
    private $username;

    /**
     * @var string The password for the database connection.
     */
    private $password;

    /**
     * @var PDO|null The PDO connection object.
     */
    public $conn;

    /**
     * The constructor reads the environment variables when a new
     * Database object is created.
     */
    public function __construct() {
        // Read credentials from the container's environment variables
        $this->db_name = getenv('MYSQL_DATABASE');
        $this->username = getenv('MYSQL_USER');
        $this->password = getenv('MYSQL_PASSWORD');
    }

    /**
     * Establishes and returns the database connection.
     *
     * @return PDO|null The PDO connection object or null on failure.
     */
    public function getConnection() {

        $this->conn = null; // Start with a null connection

        // Create the DSN (Data Source Name) string
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";

        $options = [
            // 1. Set error mode to throw exceptions. This is critical for debugging.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // 2. Set the default fetch mode to associative array.
            // This means $row['first_name'] instead of $row[0].
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // 3. Turn off emulated prepared statements.
            // This tells PDO to use real, secure prepared statements.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // Create a new PDO (PHP Data Objects) instance
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            // If the connection fails, output the error message
            // In a production environment, you would log this to a file instead.
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Database Connection Error: " . $exception->getMessage()
            ]);
            exit(); // Stop the script if we can't connect
        }

        return $this->conn;
    }
}