<?php
/** Konfigurasi database Abah Laut untuk XAMPP. */
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_alan');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', '/abah-laut');
define('BASE_PATH', dirname(__DIR__));

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (PHP_SAPI !== 'cli') {
                http_response_code(500);
                die('<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>Koneksi Database Gagal</h2><p>Pastikan database <strong>db_alan</strong> sudah diimpor dari <code>database/db_alan.sql</code>.</p><p>Detail teknis disembunyikan demi keamanan.</p></div>');
            }
            throw $e;
        }
    }
    return $pdo;
}
