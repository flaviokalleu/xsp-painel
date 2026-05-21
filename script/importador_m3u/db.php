<?php
// Arquivo: db.php

function conectar_banco() {
    // --- PREENCHA COM SEUS DADOS DO BANCO DE DADOS ---
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'xsp_panel';
    $user = getenv('DB_USER') ?: 'xsp';
    $pass = getenv('DB_PASS') ?: '';
    // ---------------------------------------------------

    try {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ];
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("<div class='alert alert-danger m-3'>ERRO DE CONEXÃO COM O BANCO DE DADOS: " . $e->getMessage() . "</div>");
    }
}
?>