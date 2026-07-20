<?php
/**
 * db_config.php
 * Central database connection using PDO.
 * Update the credentials below to match your environment.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'healthcare_system');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // In production, log this instead of echoing it.
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
