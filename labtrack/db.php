<?php
// Define configuration variables first
$host = 'localhost';
$db   = 'labtrack_db';$user = 'root';
$pass = '';$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn,$user, $pass,$options);
} catch (\PDOException $e) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=$charset", $user,$pass, $options);$pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$db`;");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS assets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_tag VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'Available',
            borrower VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (\PDOException $ex) {
        die("Database Connection Failed: " . $ex->getMessage());
    }
}
?>