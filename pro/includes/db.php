<?php
require_once __DIR__ . '/config.php';

function pro_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . PRO_DB_HOST . ';dbname=' . PRO_DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, PRO_DB_USER, PRO_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    return $pdo;
}
