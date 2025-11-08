<?php echo "Backend is working!"; ?>
<?php
$host = 'db';
$db   = 'testdb';
$user = 'testuser';
$pass = 'testpass';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "MySQL connection success!";
} catch (\PDOException $e) {
    echo "MySQL connection failed: " . $e->getMessage();
}
?>
