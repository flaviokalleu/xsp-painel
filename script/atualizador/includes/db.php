<?php
// includes/db.php

function conectar_banco() {
    // --- PREENCHA COM SEUS DADOS ---
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'xsp_panel'; // O nome do seu banco de dados
    $user = getenv('DB_USER') ?: 'xsp';
    $pass = getenv('DB_PASS') ?: '';
    // --------------------------------

    try {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ];
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Se a conexão falhar, o script para e mostra o erro.
        die("ERRO DE CONEXÃO COM O BANCO DE DADOS: " . $e->getMessage());
    }
}
?>