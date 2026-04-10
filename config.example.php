<?php


declare(strict_types=1);
ini_set('default_charset', 'UTF-8');


/*
|--------------------------------------------------------------------------
| DATENBANK KONFIGURATION
|--------------------------------------------------------------------------
*/

$host   = "127.0.0.1";
$dbname = "docvault";
$user   = "jfischer";
$pass   = '#8763JjFi#mar3ils8runn$phpma';

/*
|--------------------------------------------------------------------------
| PDO VERBINDUNG (SOCKET für Synology MariaDB10)
|--------------------------------------------------------------------------
*/

try {
    $pdo = new PDO(
        "mysql:unix_socket=/run/mysqld/mysqld10.sock;dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| MASTER KEY
|--------------------------------------------------------------------------
*/

define(
    'DOCVAULT_MASTER_KEY',
    '9fB7xL2qK8mP4vR6tY3zW1aN5cD8hJ2uX7sQ4eT9kV6bL3nM1pZ8rC5yF2gH7'
);

define('DOCVAULT_PIN', '876387'); // DEINE PIN
/*
|--------------------------------------------------------------------------
| CORE HELPERS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/core/file_viewer.php";


