<?php
/**
 * _xsp_db.php — Conector central do painel.
 *
 * Use SEMPRE este arquivo para abrir conexão com o banco. Não coloque
 * credenciais em outros lugares — todas as conexões devem passar por aqui.
 *
 *   $pdo = xsp_db();
 *   $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
 */
declare(strict_types=1);

if (!function_exists('xsp_db')) {
    function xsp_db(): PDO {
        static $pdo = null;
        if ($pdo !== null) return $pdo;

        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'xsp_panel';
        $user = getenv('DB_USER') ?: 'xsp';
        $pass = getenv('DB_PASS') ?: '';

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
        return $pdo;
    }
}

/** Versão mysqli (alguns arquivos do painel ainda usam mysqli) */
if (!function_exists('xsp_mysqli')) {
    function xsp_mysqli(): mysqli {
        static $conn = null;
        if ($conn !== null) return $conn;

        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'xsp_panel';
        $user = getenv('DB_USER') ?: 'xsp';
        $pass = getenv('DB_PASS') ?: '';

        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            error_log('mysqli connect failed: ' . $conn->connect_error);
            throw new RuntimeException('db connection failed');
        }
        $conn->set_charset('utf8mb4');
        return $conn;
    }
}
