<?php
//$host_name= 'db';
//$db_name = 'dailyway_db';
//$user = 'dailyway_user';
//$pass = 'dailyway_pass';
//$charset = 'utf8mb4';
//
//$dsn = "mysql:host=$host_name; dbname=$db_name; charset=$charset";
//
//try{
//    $pdo = new PDO($dsn, $user, $pass);
//    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//} catch (PDOException $e) {
//    throw new PDOException($e->getMessage(), (int)$e->getCode());
//}


/**
 * Database Connection (PDO)
 *
 * This file establishes the connection to the database using PDO
 * and returns the connection instance.
 *
 * It pulls credentials from environment variables, which is a
 * best practice for security and portability (especially with Docker).
 */

// 1. Get database credentials from environment variables
// These would be set in your 'docker-compose.yml' file.
$db_host = getenv('DB_HOST') ?: 'db'; // 'db' is often the service name in docker-compose
$db_port = getenv('DB_PORT') ?: '3306'; // Default MySQL/MariaDB port
$db_name = getenv('DB_NAME') ?: 'dailywag_db';
$db_user = getenv('DB_USER') ?: 'dailywag_user';
$db_pass = getenv('DB_PASS') ?: 'dailywag_pass';

// 2. Create the Data Source Name (DSN)
$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

// 3. Set PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch as associative arrays
    PDO::ATTR_EMULATE_PREPARES => false,                  // Use real prepared statements
];

// 4. Try to create the PDO instance (the connection)
try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // If connection fails, stop everything and show a clear error
    // This is safer than letting the app continue in a broken state
    throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
}

// 5. Return the connection object
// The 'index.php' file will 'require' this file and catch this $pdo object.
return $pdo;